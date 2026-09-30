<?php

namespace App\Mail;

use App\Services\EmailTemplateService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BatchCancelledEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public mixed $user,
        public string $batchName,
        public string $stage,
        public string $reason
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $userName = $this->user->name ?? trim(($this->user->first_name ?? '').' '.($this->user->last_name ?? '')) ?: 'Applicant';
        $portalUrl = config('app.frontend_url', config('app.url', 'http://localhost')).'/#/youth/scholarship/ecespro';

        $rendered = app(EmailTemplateService::class)->render('batch_cancelled', [
            'user_name' => $userName,
            'stage' => $this->stage,
            'batch_name' => $this->batchName,
            'reason' => $this->reason,
            'portal_url' => $portalUrl,
        ]);

        return new Envelope(
            subject: $rendered['subject'] ?: "ECESPRO {$this->stage} Schedule Cancelled - {$this->batchName}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $userName = $this->user->name ?? trim(($this->user->first_name ?? '').' '.($this->user->last_name ?? '')) ?: 'Applicant';
        $portalUrl = config('app.frontend_url', config('app.url', 'http://localhost')).'/#/youth/scholarship/ecespro';

        return new Content(
            view: 'emails.batch-cancelled',
            with: [
                'user' => $this->user,
                'userName' => $userName,
                'batchName' => $this->batchName,
                'stage' => $this->stage,
                'reason' => $this->reason,
                'portalUrl' => $portalUrl,
            ],
        );
    }
}
