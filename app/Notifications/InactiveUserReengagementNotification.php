<?php

namespace App\Notifications;

use App\Mail\InactiveUserReengagementEmail;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Notifications\Notification;

class InactiveUserReengagementNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ?int $daysInactive = null,
        public ?string $lastLoginFormatted = null
    ) {}

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
        return (new InactiveUserReengagementEmail($notifiable, $this->daysInactive, $this->lastLoginFormatted))
            ->to($notifiable->email);
    }

    /**
     * Get the array representation of the notification for the database channel.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'We Miss You!',
            'message' => "It's been a while! Discover new youth activities, scholarships, and opportunities available on the portal.",
            'url' => '/youth/dashboard',
            'type' => 'reengagement',
        ];
    }
}
