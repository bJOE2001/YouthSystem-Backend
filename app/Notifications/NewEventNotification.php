<?php

namespace App\Notifications;

use App\Mail\NewEventEmail;
use App\Models\Event;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Notifications\Notification;

class NewEventNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public $event;

    public function __construct(Event $event)
    {
        $this->event = $event;
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
        return (new NewEventEmail($notifiable, $this->event))
            ->to($notifiable->email);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $schedule = $this->event->start_date ? $this->event->start_date->format('M d, Y') : '';

        return [
            'title' => 'New Event Posted',
            'message' => "A new event '{$this->event->name}' has been scheduled".($schedule ? " for {$schedule}." : '.'),
            'event_id' => $this->event->id,
            'url' => '/youth/events',
        ];
    }
}
