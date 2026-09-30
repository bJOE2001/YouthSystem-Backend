<?php

namespace App\Mail;

use App\Models\EcesproGrantReleaseBatch;
use App\Services\EmailTemplateService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class GrantReleaseEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public mixed $user,
        public EcesproGrantReleaseBatch $batch
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $userName = $this->user->name ?? trim(($this->user->first_name ?? '').' '.($this->user->last_name ?? '')) ?: 'Scholar';
        $formattedDate = $this->batch->release_date ? $this->batch->release_date->format('F d, Y') : 'To be announced';
        $portalUrl = config('app.frontend_url', config('app.url', 'http://localhost')).'/#/youth/scholarship/ecespro';

        $rendered = app(EmailTemplateService::class)->render('grant_release', [
            'user_name' => $userName,
            'batch_name' => $this->batch->batch_name ?? 'Grant Release',
            'release_date' => $formattedDate,
            'time' => (string) ($this->batch->time ?? ''),
            'venue' => (string) ($this->batch->venue ?? ''),
            'portal_url' => $portalUrl,
        ]);

        return new Envelope(
            subject: $rendered['subject'] ?: "ECESPRO Grant Release Schedule - {$this->batch->batch_name}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $userName = $this->user->name ?? trim(($this->user->first_name ?? '').' '.($this->user->last_name ?? '')) ?: 'Scholar';
        $formattedDate = $this->batch->release_date ? $this->batch->release_date->format('F d, Y') : 'To be announced';
        $portalUrl = config('app.frontend_url', config('app.url', 'http://localhost')).'/#/youth/scholarship/ecespro';

        return new Content(
            view: 'emails.grant-release',
            with: [
                'user' => $this->user,
                'userName' => $userName,
                'batch' => $this->batch,
                'formattedDate' => $formattedDate,
                'portalUrl' => $portalUrl,
            ],
        );
    }
}
