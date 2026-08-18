<?php

namespace App\Mail;

use App\Models\Event;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewEventEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public Event $event
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "New Event: {$this->event->name}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $eventUrl = config('app.frontend_url', config('app.url', 'http://localhost')).'/#/youth/events';

        return new Content(
            view: 'emails.new-event',
            with: [
                'user' => $this->user,
                'event' => $this->event,
                'eventUrl' => $eventUrl,
            ],
        );
    }
}
