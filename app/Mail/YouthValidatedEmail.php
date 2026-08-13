<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class YouthValidatedEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $plainPassword
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Youth Account Has Been Validated',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $loginUrl = config('app.frontend_url', config('app.url', 'http://localhost'));

        return new Content(
            view: 'emails.youth-validated',
            with: [
                'user' => $this->user,
                'plainPassword' => $this->plainPassword,
                'loginUrl' => $loginUrl,
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
        return [];
    }
}
