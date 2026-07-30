<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class EcesproApplicationStatusNotification extends Notification
{
    use Queueable;

    public $application;

    public $status;

    public $customMessage;

    public function __construct($application, string $status, ?string $customMessage = null)
    {
        $this->application = $application;
        $this->status = $status;
        $this->customMessage = $customMessage;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $messages = [
            'Submitted' => 'Your ECESPRO scholarship application has been received and is submitted for review.',
            'Under Review' => 'Your ECESPRO scholarship application and uploaded documents are currently under review.',
            'Qualified for Exam' => 'Congratulations! You have qualified for the ECESPRO Qualifying Examination. Stand by for your schedule.',
            'Exam Scheduled' => 'Your ECESPRO Qualifying Examination has been scheduled! Check your application timeline for details.',
            'Failed Exam' => 'Update on your ECESPRO application: Examination completed — Did not meet the qualifying score.',
            'Failed in Exam' => 'Update on your ECESPRO application: Examination completed — Did not meet the qualifying score.',
            'Qualified for Interview' => 'Congratulations! You passed the qualifying examination and qualified for the ECESPRO Panel Interview.',
            'Interview Scheduled' => 'Your ECESPRO Panel Interview has been scheduled! Check your application timeline for schedule details.',
            'Failed Interview' => 'Update on your ECESPRO application: Panel interview completed — Did not pass the interview stage.',
            'Failed in Interview' => 'Update on your ECESPRO application: Panel interview completed — Did not pass the interview stage.',
            'Qualified for Contract' => 'Congratulations! You passed the interview and qualified for ECESPRO Contract Signing & Orientation.',
            'Contract Scheduled' => 'Your ECESPRO Contract Signing & Orientation schedule has been posted.',
            'Approved' => '🎉 Congratulations! Your ECESPRO Scholarship application is APPROVED! Your scholar account is now active.',
            'Rejected' => 'Update on your ECESPRO application: Your application was not approved.',
        ];

        $title = 'ECESPRO Application Update';
        $message = $this->customMessage ?? ($messages[$this->status] ?? "Your ECESPRO application status is updated to {$this->status}.");

        return [
            'title' => $title,
            'message' => $message,
            'application_id' => $this->application->id,
            'status' => $this->status,
            'url' => '/youth/scholarship/ecespro',
        ];
    }
}
