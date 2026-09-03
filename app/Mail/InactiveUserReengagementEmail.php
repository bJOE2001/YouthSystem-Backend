<?php

namespace App\Mail;

use App\Models\User;
use App\Services\EmailTemplateService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InactiveUserReengagementEmail extends Mailable
{
    use Queueable, SerializesModels;

    public int $daysInactive;

    public string $lastLoginFormatted;

    public function __construct(
        public User $user,
        ?int $daysInactive = null,
        ?string $lastLoginFormatted = null
    ) {
        if ($daysInactive !== null) {
            $this->daysInactive = $daysInactive;
        } else {
            $referenceDate = $this->user->last_login_at ?? $this->user->created_at ?? now();
            $this->daysInactive = max(1, (int) now()->diffInDays($referenceDate));
        }

        if ($lastLoginFormatted !== null) {
            $this->lastLoginFormatted = $lastLoginFormatted;
        } else {
            $this->lastLoginFormatted = $this->user->last_login_at
                ? $this->user->last_login_at->format('F d, Y')
                : 'Never logged in';
        }
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $loginUrl = config('app.frontend_url', config('app.url', 'http://localhost')).'/#/login';
        $portalUrl = config('app.frontend_url', config('app.url', 'http://localhost')).'/#/youth/dashboard';

        $rendered = app(EmailTemplateService::class)->render('inactive_user_reengagement', [
            'user_name' => $this->user->name,
            'user_email' => $this->user->email,
            'last_login_formatted' => $this->lastLoginFormatted,
            'days_inactive' => (string) $this->daysInactive,
            'login_url' => $loginUrl,
            'portal_url' => $portalUrl,
        ]);

        return new Envelope(
            subject: $rendered['subject'] ?: "We Miss You, {$this->user->name}! Discover What's New at TCYDO",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $loginUrl = config('app.frontend_url', config('app.url', 'http://localhost')).'/#/login';
        $portalUrl = config('app.frontend_url', config('app.url', 'http://localhost')).'/#/youth/dashboard';

        return new Content(
            view: 'emails.inactive-user-reengagement',
            with: [
                'user' => $this->user,
                'daysInactive' => $this->daysInactive,
                'lastLoginFormatted' => $this->lastLoginFormatted,
                'loginUrl' => $loginUrl,
                'portalUrl' => $portalUrl,
            ],
        );
    }
}
