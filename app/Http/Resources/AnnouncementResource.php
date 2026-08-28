<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

class AnnouncementResource extends JsonResource
{
    /**
     * Cache for read announcement IDs for the current user in this request.
     *
     * @var array<int>|null
     */
    protected static ?array $userReadAnnouncementIds = null;

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $request->user() ?? auth('sanctum')->user();

        if ($user) {
            if (static::$userReadAnnouncementIds === null) {
                static::$userReadAnnouncementIds = DB::table('announcement_user')
                    ->where('user_id', $user->id)
                    ->pluck('announcement_id')
                    ->all();
            }
            $isRead = in_array($this->id, static::$userReadAnnouncementIds, true);
        } else {
            $isRead = false;
        }

        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'datePosted' => $this->created_at->format('Y-m-d'),
            'is_read' => $isRead,
            'isNew' => ! $isRead,
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
        ];
    }
}
