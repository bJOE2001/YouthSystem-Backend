<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AnnouncementResource;
use App\Models\Announcement;
use App\Models\User;
use App\Notifications\NewAnnouncementNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class AnnouncementController extends Controller
{
    public function index(Request $request)
    {
        if (! $request->user() && $request->bearerToken()) {
            $token = \Laravel\Sanctum\PersonalAccessToken::findToken($request->bearerToken());
            if ($token && $token->tokenable) {
                auth()->setUser($token->tokenable);
                $request->setUserResolver(fn () => $token->tokenable);
            }
        }

        $query = Announcement::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->input('owner') === 'me') {
            $query->where('user_id', auth('sanctum')->id());
        }

        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');

        // Mapping frontend sortBy 'datePosted' or 'name' (which actually is title)
        if ($sortBy === 'datePosted') {
            $sortBy = 'created_at';
        } elseif ($sortBy === 'name') {
            $sortBy = 'title';
        }

        $query->orderBy($sortBy, $sortOrder);

        $perPage = $request->input('per_page', 10);
        $announcements = $perPage > 0 ? $query->paginate($perPage) : $query->get();

        return AnnouncementResource::collection($announcements);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
        ]);

        $validated['user_id'] = auth('sanctum')->id();

        $announcement = Announcement::create($validated);

        $youthUsers = User::where('role', 'youth')->get();
        Notification::send($youthUsers, new NewAnnouncementNotification($announcement));

        return new AnnouncementResource($announcement);
    }

    public function update(Request $request, Announcement $announcement)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
        ]);

        $announcement->update($validated);

        return new AnnouncementResource($announcement);
    }

    public function destroy(Announcement $announcement)
    {
        $announcement->delete();

        return response()->json(['message' => 'Announcement deleted successfully']);
    }

    public function markAsRead(Request $request, Announcement $announcement)
    {
        $user = $request->user() ?? auth('sanctum')->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $user->readAnnouncements()->syncWithoutDetaching([
            $announcement->id => ['read_at' => now()],
        ]);

        $user->unreadNotifications()
            ->where('data->announcement_id', $announcement->id)
            ->update(['read_at' => now()]);

        AnnouncementResource::clearReadCache();

        return response()->json([
            'message' => 'Announcement marked as read',
            'announcement' => new AnnouncementResource($announcement),
        ]);
    }

    public function markAllAsRead(Request $request)
    {
        $user = $request->user() ?? auth('sanctum')->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $allAnnouncementIds = Announcement::pluck('id')->all();
        $syncData = [];
        $now = now();
        foreach ($allAnnouncementIds as $id) {
            $syncData[$id] = ['read_at' => $now];
        }

        $user->readAnnouncements()->syncWithoutDetaching($syncData);

        $user->unreadNotifications()
            ->where('type', 'App\\Notifications\\NewAnnouncementNotification')
            ->update(['read_at' => $now]);

        return response()->json([
            'message' => 'All announcements marked as read',
        ]);
    }
}
