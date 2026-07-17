<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AnnouncementResource;
use App\Models\Announcement;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    public function index(Request $request)
    {
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
}
