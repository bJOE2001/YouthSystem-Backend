<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $notifications = auth('sanctum')->user()->notifications()->paginate($perPage);

        return NotificationResource::collection($notifications);
    }

    public function unreadCount()
    {
        $count = auth('sanctum')->user()->unreadNotifications()->count();

        return response()->json(['count' => $count]);
    }

    public function markAsRead($id)
    {
        $notification = auth('sanctum')->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return new NotificationResource($notification);
    }

    public function markAllAsRead()
    {
        auth('sanctum')->user()->unreadNotifications->markAsRead();

        return response()->json(['message' => 'All notifications marked as read']);
    }

    public function destroy($id)
    {
        $notification = auth('sanctum')->user()->notifications()->findOrFail($id);
        $notification->delete();

        return response()->json(['message' => 'Notification deleted successfully']);
    }
}
