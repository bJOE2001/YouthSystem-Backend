<?php

namespace App\Notifications;

use App\Models\EcesproApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class EcesproScholarStatusNotification extends Notification
{
    use Queueable;

    public $application;
    public string $status;
    public ?string $customMessage;
    public array $metadata;

    public function __construct($application, string $status, ?string $customMessage = null, array $metadata = [])
    {
        $this->application = $application;
        $this->status = $status;
        $this->customMessage = $customMessage;
        $this->metadata = $metadata;
    }

    public function via(object $notifiable): array
    {
        return ['database']; // Keeping it simple for now, can add 'mail' later if needed
    }

    public function toArray(object $notifiable): array
    {
        $title = 'ECESPRO Scholarship Update';
        $message = $this->customMessage;

        if (!$message) {
            switch ($this->status) {
                case 'Active':
                    $message = 'Your ECESPRO Scholarship is now active! You can now start rendering your volunteer hours.';
                    break;
                case 'Inactive':
                    $message = 'Your ECESPRO Scholarship status has been marked as Inactive. Please contact the administrator for details.';
                    break;
                case 'Deferred':
                    $message = 'Your ECESPRO Scholarship has been deferred. Please contact the administrator for more information.';
                    break;
                case 'Terminated':
                    $message = 'Your ECESPRO Scholarship has been terminated.';
                    break;
                default:
                    $message = "Your ECESPRO Scholarship status has been updated to {$this->status}.";
                    break;
            }
        }

        return [
            'title' => $title,
            'message' => $message,
            'application_id' => $this->application ? $this->application->id : null,
            'status' => $this->status,
            'url' => '/youth/scholarship/ecespro',
            'metadata' => $this->metadata,
        ];
    }
}
