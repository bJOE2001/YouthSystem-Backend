<?php

namespace App\Notifications;

use App\Models\EcesproApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewEcesproApplicationNotification extends Notification
{
    use Queueable;

    public $application;

    /**
     * Create a new notification instance.
     */
    public function __construct(EcesproApplication $application)
    {
        $this->application = $application;
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
        $name = trim("{$this->application->first_name} {$this->application->last_name}");
        
        return [
            'title' => 'New ECESPRO Application',
            'message' => "{$name} has submitted a new ECESPRO scholarship application.",
            'description' => '',
            'application_id' => $this->application->id,
            'url' => '/admin/ecespro-scholarship/applications',
        ];
    }
}
