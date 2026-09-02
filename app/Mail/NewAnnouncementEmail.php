<?php

namespace App\Mail;

use App\Models\Announcement;
use App\Models\User;
use App\Services\EmailTemplateService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewAnnouncementEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public Announcement $announcement
    ) {}

    protected function getAnnouncementUrl(): string
    {
        $role = $this->user->role;
        $isSk = $role === \App\Enums\UserRole::SkAdmin || $role === 'sk_admin' || ($role instanceof \BackedEnum && $role->value === 'sk_admin');
        $path = $isSk ? '/#/announcements' : '/#/youth/announcements';

        return config('app.frontend_url', config('app.url', 'http://localhost')).$path;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $rendered = app(EmailTemplateService::class)->render('new_announcement', [
            'user_name' => $this->user->name,
            'announcement_title' => $this->announcement->title,
            'announcement_description' => $this->announcement->description ?? '',
            'published_date' => $this->announcement->created_at ? $this->announcement->created_at->format('F d, Y') : date('F d, Y'),
            'announcement_url' => $this->getAnnouncementUrl(),
        ]);

        return new Envelope(
            subject: $rendered['subject'] ?: "Announcement: {$this->announcement->title}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.new-announcement',
            with: [
                'user' => $this->user,
                'announcement' => $this->announcement,
                'announcementUrl' => $this->getAnnouncementUrl(),
            ],
        );
    }
}
