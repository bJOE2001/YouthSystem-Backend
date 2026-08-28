<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TestEmailLayout extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @param  array<string, mixed>  $emailLayout
     */
    public function __construct(
        public array $emailLayout = []
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $title = $this->emailLayout['header_title'] ?? 'TCYDO System';

        return new Envelope(
            subject: "Email Layout Test - {$title}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.test-email',
            with: [
                'emailLayout' => $this->emailLayout,
                'previewMode' => false,
            ],
        );
    }
}
