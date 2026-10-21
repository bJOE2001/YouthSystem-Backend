<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ParticipantCancelledNotification extends Notification
{
    use Queueable;

    public string $activityName;
    public string $activityType;
    public ?string $customMessage;

    /**
     * Create a new notification instance.
     *
     * @param string $activityName The name of the event or sports program
     * @param string $activityType 'event' or 'sports'
     * @param string|null $customMessage Optional override message
     */
    public function __construct(string $activityName, string $activityType = 'event', ?string $customMessage = null)
    {
        $this->activityName = $activityName;
        $this->activityType = $activityType;
        $this->customMessage = $customMessage;
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
        $label = $this->activityType === 'sports' ? 'Sports Program' : 'Event';
        $defaultMessage = "Your registration for the {$label} \"{$this->activityName}\" has been cancelled by the organizer.";

        return [
            'title' => 'Registration Cancelled',
            'message' => $this->customMessage ?: $defaultMessage,
            'description' => $this->customMessage ?: $defaultMessage,
            'url' => $this->activityType === 'sports' ? '/youth/sports' : '/youth/events',
            'activity_name' => $this->activityName,
            'activity_type' => $this->activityType,
        ];
    }
}
