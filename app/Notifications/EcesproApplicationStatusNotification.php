<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EcesproApplicationStatusNotification extends Notification
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
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $subjects = [
            'Submitted' => 'ECESPRO Application Received',
            'Under Review' => 'ECESPRO Application Under Review',
            'Qualified for Exam' => 'Congratulations! You Qualified for the ECESPRO Examination',
            'Exam Scheduled' => 'ECESPRO Qualifying Examination Schedule Details',
            'Failed Exam' => 'Update on Your ECESPRO Examination Result',
            'Failed in Exam' => 'Update on Your ECESPRO Examination Result',
            'Qualified for Interview' => 'Congratulations! You Passed the Exam & Qualified for Interview',
            'Interview Scheduled' => 'ECESPRO Panel Interview Schedule Details',
            'Failed Interview' => 'Update on Your ECESPRO Panel Interview Result',
            'Failed in Interview' => 'Update on Your ECESPRO Panel Interview Result',
            'Qualified for Contract' => 'Congratulations! You Passed the Interview & Qualified for Contract Signing',
            'Contract Scheduled' => 'ECESPRO Contract Signing & Orientation Schedule',
            'Approved' => '🎉 Congratulations! Your ECESPRO Scholarship is Approved',
            'Rejected' => 'Update on Your ECESPRO Scholarship Application',
            'For Revision' => 'Action Required: ECESPRO Document Revision Requested',
        ];

        $subject = $subjects[$this->status] ?? "ECESPRO Application Update - {$this->status}";
        $recipientName = $notifiable->name ?? ($this->application->first_name ?? 'Applicant');
        $portalUrl = config('app.frontend_url', config('app.url', 'http://localhost')).'/youth/scholarship/ecespro';

        $messages = [
            'Submitted' => 'Your ECESPRO scholarship application has been received and is submitted for review.',
            'Under Review' => 'Your ECESPRO scholarship application and uploaded documents are currently under review.',
            'Qualified for Exam' => 'Great news! Your application and documents have been reviewed and you have officially qualified for the ECESPRO Qualifying Examination.',
            'Exam Scheduled' => 'Your ECESPRO Qualifying Examination has been scheduled! Please review your exam schedule details and guidelines below.',
            'Failed Exam' => 'Thank you for taking the ECESPRO Qualifying Examination. We regret to inform you that your score did not meet the qualifying threshold for this batch.',
            'Failed in Exam' => 'Thank you for taking the ECESPRO Qualifying Examination. We regret to inform you that your score did not meet the qualifying threshold for this batch.',
            'Qualified for Interview' => 'Congratulations! You successfully passed the Qualifying Examination and have officially advanced to the ECESPRO Panel Interview stage.',
            'Interview Scheduled' => 'Your ECESPRO Panel Interview has been scheduled! Please review your interview schedule details and reminders below.',
            'Failed Interview' => 'Thank you for attending the ECESPRO Panel Interview. We regret to inform you that you were not selected to advance to the contract signing stage.',
            'Failed in Interview' => 'Thank you for attending the ECESPRO Panel Interview. We regret to inform you that you were not selected to advance to the contract signing stage.',
            'Qualified for Contract' => 'Congratulations! You successfully passed the Panel Interview and are now qualified for the ECESPRO Contract Signing and Orientation.',
            'Contract Scheduled' => 'Your ECESPRO Contract Signing and Orientation schedule has been posted. Please check your schedule and details below.',
            'Approved' => '🎉 Huge congratulations! Your ECESPRO Scholarship contract is confirmed and your official scholar account is now active.',
            'Rejected' => 'Thank you for applying for the ECESPRO Scholarship program. Your application was reviewed but could not be approved at this time.',
            'For Revision' => 'One or more of your uploaded requirement documents requires revision. Please review the reviewer remarks below and re-upload the document on your portal.',
        ];

        $statusMessage = $this->customMessage ?? ($messages[$this->status] ?? "Your ECESPRO application status has been updated to {$this->status}.");

        return (new MailMessage)
            ->subject($subject)
            ->view('emails.ecespro-status', [
                'user' => $notifiable,
                'application' => $this->application,
                'recipientName' => $recipientName,
                'status' => $this->status,
                'statusMessage' => $statusMessage,
                'metadata' => $this->metadata,
                'portalUrl' => $portalUrl,
                'subjectTitle' => $subject,
            ]);
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
            'For Revision' => 'Your uploaded requirement document requires revision. Please check your application and reupload.',
        ];

        $title = $this->status === 'For Revision' ? 'ECESPRO Document Revision Required' : 'ECESPRO Application Update';
        $message = $this->customMessage ?? ($messages[$this->status] ?? "Your ECESPRO application status is updated to {$this->status}.");

        return [
            'title' => $title,
            'message' => $message,
            'application_id' => $this->application->id,
            'status' => $this->status,
            'url' => '/youth/scholarship/ecespro',
            'metadata' => $this->metadata,
        ];
    }
}
