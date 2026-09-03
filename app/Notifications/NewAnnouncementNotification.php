<?php

namespace App\Notifications;

use App\Enums\UserRole;
use App\Models\Announcement;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewAnnouncementNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public $announcement;

    public function __construct(Announcement $announcement)
    {
        $this->announcement = $announcement;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $role = $notifiable instanceof User ? $notifiable->role : null;
        $isSk = $role === UserRole::SkAdmin || $role === 'sk_admin' || ($role instanceof \BackedEnum && $role->value === 'sk_admin');

        return [
            'title' => $this->announcement->title,
            'message' => $this->announcement->description ?? '',
            'description' => $this->announcement->description ?? '',
            'announcement_id' => $this->announcement->id,
            'url' => $isSk ? '/announcements' : '/youth/announcements',
        ];
    }
}
