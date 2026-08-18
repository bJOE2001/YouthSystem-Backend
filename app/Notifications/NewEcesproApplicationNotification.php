<?php

namespace App\Notifications;

use App\Mail\NewEcesproApplicationEmail;
use App\Models\EcesproApplication;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Notifications\Notification;

class NewEcesproApplicationNotification extends Notification
{
    use Queueable;

    public $application;

    public function __construct(EcesproApplication $application)
    {
        $this->application = $application;
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): Mailable
    {
        /** @var User $notifiable */
        return (new NewEcesproApplicationEmail($notifiable, $this->application))
            ->to($notifiable->email);
    }

    public function toArray(object $notifiable): array
    {
        $applicantName = $this->application->user->name ?? 'Applicant';

        return [
            'title' => 'New ECESPRO Application',
            'message' => "A new ECESPRO scholarship application has been submitted by {$applicantName}.",
            'application_id' => $this->application->id,
            'url' => '/admin/scholarship/ecespro/'.$this->application->id,
        ];
    }
}
