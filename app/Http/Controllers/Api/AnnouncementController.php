<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Resources\AnnouncementResource;
use App\Models\Announcement;
use App\Models\User;
use App\Notifications\NewAnnouncementNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\PersonalAccessToken;

class AnnouncementController extends Controller
{
    public function index(Request $request)
    {
        if (! $request->user() && $request->bearerToken()) {
            $token = PersonalAccessToken::findToken($request->bearerToken());
            if ($token && $token->tokenable) {
                auth()->setUser($token->tokenable);
                $request->setUserResolver(fn () => $token->tokenable);
            }
        }

        $query = Announcement::with(['user.youthProfile', 'user.skOfficial']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->input('owner') === 'me') {
            /** @var User|null $user */
            $user = $request->user() ?? auth('sanctum')->user();
            if (! $user) {
                abort(401, 'Unauthenticated.');
            }

            if ($user->isRootAdmin() || ($user->isSubAdmin() && $user->canAccessModule('announcements'))) {
                // Citywide Admin announcements created by any Admin or Sub-Admin
                $query->whereHas('user', function ($uq) {
                    $uq->whereIn('role', [UserRole::Admin->value, UserRole::SubAdmin->value, 'admin', 'sub_admin']);
                });
            } elseif ($user->isSubAdmin()) {
                abort(403, 'You do not have permission to access the announcements module.');
            } elseif ($user->role === UserRole::SkAdmin) {
                $skBarangay = $user->getBarangay();
                if (! $skBarangay) {
                    abort(403, 'No barangay assigned to this SK Admin account.');
                }

                // Shared announcements created by SK Admins of this barangay only
                $query->whereHas('user', function ($uq) use ($skBarangay) {
                    $uq->whereIn('role', [UserRole::SkAdmin->value, 'sk_admin'])
                        ->whereHas('skOfficial', function ($oq) use ($skBarangay) {
                            $oq->whereRaw('LOWER(barangay) = ?', [strtolower(trim($skBarangay))]);
                        });
                });
            } else {
                abort(403, 'Unauthorized to view management announcements.');
            }
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

    protected function authorizeAnnouncementModification(User $user, Announcement $announcement): void
    {
        if ($user->isRootAdmin()) {
            return;
        }

        if ($user->isSubAdmin()) {
            if (! $user->canAccessModule('announcements')) {
                abort(403, 'You do not have permission to access the announcements module.');
            }
            $creatorRole = $announcement->user?->role;
            if ($creatorRole === UserRole::SkAdmin) {
                abort(403, 'Sub-Admins cannot modify barangay-managed SK announcements.');
            }
            return;
        }

        if ($user->role === UserRole::SkAdmin) {
            $skBarangay = $user->getBarangay();
            $creatorBarangay = $announcement->user?->getBarangay();
            if (! $skBarangay || strtolower(trim((string) $creatorBarangay)) !== strtolower(trim((string) $skBarangay))) {
                abort(403, 'You are not authorized to modify announcements outside your assigned barangay.');
            }
            return;
        }

        abort(403, 'Unauthorized.');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
        ]);

        $validated['user_id'] = auth('sanctum')->id();

        $announcement = Announcement::create($validated);

        $recipients = User::where(function ($query) {
            $query->whereIn('role', [
                UserRole::Youth->value,
                UserRole::SkAdmin->value,
                'youth',
                'sk_admin',
            ]);
        })->get();

        Notification::send($recipients, new NewAnnouncementNotification($announcement));

        return new AnnouncementResource($announcement);
    }

    public function update(Request $request, Announcement $announcement)
    {
        /** @var User $user */
        $user = $request->user() ?? auth('sanctum')->user();
        $this->authorizeAnnouncementModification($user, $announcement);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
        ]);

        $announcement->update($validated);

        return new AnnouncementResource($announcement);
    }

    public function destroy(Announcement $announcement)
    {
        /** @var User $user */
        $user = request()->user() ?? auth('sanctum')->user();
        $this->authorizeAnnouncementModification($user, $announcement);

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
