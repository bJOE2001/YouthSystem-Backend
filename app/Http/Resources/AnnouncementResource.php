<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;

class AnnouncementResource extends JsonResource
{
    /**
     * Cache for read announcement IDs mapped by user ID for the current request.
     *
     * @var array<string, array<int>>
     */
    protected static array $userReadAnnouncementIds = [];

    public static function clearReadCache(): void
    {
        static::$userReadAnnouncementIds = [];
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = auth()->user()
            ?? $request->user()
            ?? auth('sanctum')->user()
            ?? ($request->bearerToken() ? PersonalAccessToken::findToken($request->bearerToken())?->tokenable : null);

        $isRead = false;
        if ($user) {
            $userKey = (string) $user->id;
            if (! isset(static::$userReadAnnouncementIds[$userKey])) {
                static::$userReadAnnouncementIds[$userKey] = DB::table('announcement_user')
                    ->where('user_id', $user->id)
                    ->pluck('announcement_id')
                    ->map(fn ($id) => (int) $id)
                    ->all();
            }
            $isRead = in_array((int) $this->id, static::$userReadAnnouncementIds[$userKey], true);
        }

        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'datePosted' => $this->created_at ? $this->created_at->format('Y-m-d') : null,
            'author_role' => $this->user?->role,
            'author_name' => $this->user?->name,
            'barangay' => $this->user?->barangay ?? $this->user?->profile?->barangay,
            'is_read' => $isRead,
            'isNew' => ! $isRead,
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
        ];
    }
}
