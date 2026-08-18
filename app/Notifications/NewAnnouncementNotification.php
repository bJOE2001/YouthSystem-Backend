<?php

namespace App\Notifications;

use App\Mail\NewAnnouncementEmail;
use App\Models\Announcement;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
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
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): Mailable
    {
        /** @var User $notifiable */
        return (new NewAnnouncementEmail($notifiable, $this->announcement))
            ->to($notifiable->email);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'New Announcement',
            'message' => "A new announcement has been posted: '{$this->announcement->title}'.",
            'announcement_id' => $this->announcement->id,
            'url' => '/youth/announcements',
        ];
    }
}
