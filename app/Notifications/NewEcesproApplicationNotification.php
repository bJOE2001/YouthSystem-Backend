<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;


class NewEcesproApplicationNotification extends Notification
{
    use Queueable;

    public $application;

    public function __construct($application)
    {
        $this->application = $application;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'New ECESPRO Application',
            'message' => "A new ECESPRO scholarship application has been submitted by {$this->application->user->name}.",
            'application_id' => $this->application->id,
            'url' => '/admin/scholarship/ecespro/' . $this->application->id,
        ];
    }
}
