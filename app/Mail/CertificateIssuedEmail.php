<?php

namespace App\Mail;

use App\Models\Event;
use App\Models\SportsProgram;
use App\Models\User;
use App\Services\EmailTemplateService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CertificateIssuedEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public Event|SportsProgram $activity,
        public string $pdfContent,
        public string $filename
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $rendered = app(EmailTemplateService::class)->render('certificate_issued', [
            'user_name' => $this->user->name,
            'activity_name' => $this->activity->name,
            'category' => $this->activity instanceof Event ? ($this->activity->ppa_classification ?? 'Youth Event') : ($this->activity->type ?? 'Sports Program'),
            'activities_url' => config('app.frontend_url', config('app.url', 'http://localhost')).'/#/youth/my-activities',
        ]);

        return new Envelope(
            subject: $rendered['subject'] ?: "Certificate of Participation - {$this->activity->name}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $activitiesUrl = config('app.frontend_url', config('app.url', 'http://localhost')).'/#/youth/my-activities';

        return new Content(
            view: 'emails.certificate-issued',
            with: [
                'user' => $this->user,
                'activity' => $this->activity,
                'activitiesUrl' => $activitiesUrl,
                'isEvent' => $this->activity instanceof Event,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => $this->pdfContent, $this->filename)
                ->withMime('application/pdf'),
        ];
    }
}
