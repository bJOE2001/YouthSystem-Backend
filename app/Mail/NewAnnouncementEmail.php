<?php

namespace App\Mail;

use App\Models\Announcement;
use App\Models\User;
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

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Announcement: {$this->announcement->title}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $announcementUrl = config('app.frontend_url', config('app.url', 'http://localhost')).'/#/youth/announcements';

        return new Content(
            view: 'emails.new-announcement',
            with: [
                'user' => $this->user,
                'announcement' => $this->announcement,
                'announcementUrl' => $announcementUrl,
            ],
        );
    }
}
