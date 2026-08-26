<?php

namespace App\Mail;

use App\Services\EmailTemplateService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EcesproApplicationStatusEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public mixed $user,
        public mixed $application,
        public string $status,
        public ?string $customMessage = null,
        public array $statusMetadata = []
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
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

        $defaultSubject = $subjects[$this->status] ?? "ECESPRO Application Update - {$this->status}";
        $recipientName = $this->user->name ?? ($this->application->first_name ?? 'Applicant');

        $rendered = app(EmailTemplateService::class)->render('ecespro_status', [
            'recipient_name' => $recipientName,
            'status' => $this->status,
            'status_message' => $this->customMessage ?? $defaultSubject,
            'portal_url' => config('app.frontend_url', config('app.url', 'http://localhost')).'/youth/scholarship/ecespro',
        ]);

        return new Envelope(
            subject: $rendered['subject'] ?: $defaultSubject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
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
        $recipientName = $this->user->name ?? ($this->application->first_name ?? 'Applicant');
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

        return new Content(
            view: 'emails.ecespro-status',
            with: [
                'user' => $this->user,
                'application' => $this->application,
                'recipientName' => $recipientName,
                'status' => $this->status,
                'statusMessage' => $statusMessage,
                'metadata' => $this->statusMetadata,
                'portalUrl' => $portalUrl,
                'subjectTitle' => $subject,
            ],
        );
    }
}
