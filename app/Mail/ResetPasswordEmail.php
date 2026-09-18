<?php

namespace App\Mail;

use App\Services\EmailTemplateService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ResetPasswordEmail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public object $user,
        public string $token,
        public string $resetUrl
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $expireMinutes = (string) config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60);

        $rendered = app(EmailTemplateService::class)->render('reset_password', [
            'user_name' => $this->user->name ?? 'User',
            'user_email' => $this->user->email ?? '',
            'reset_url' => $this->resetUrl,
            'expire_minutes' => $expireMinutes,
        ]);

        return new Envelope(
            subject: ! empty($rendered['subject']) ? $rendered['subject'] : 'Reset Your Password - Tagum City Youth Development Office',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $expireMinutes = (int) config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60);

        return new Content(
            view: 'emails.reset-password',
            with: [
                'user' => $this->user,
                'token' => $this->token,
                'resetUrl' => $this->resetUrl,
                'expireMinutes' => $expireMinutes,
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
