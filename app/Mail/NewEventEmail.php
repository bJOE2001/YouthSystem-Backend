<?php

namespace App\Mail;

use App\Models\Event;
use App\Models\User;
use App\Services\EmailTemplateService;
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
        $rendered = app(EmailTemplateService::class)->render('new_event', [
            'user_name' => $this->user->name,
            'event_name' => $this->event->name,
            'classification' => $this->event->ppa_classification ?? '',
            'location' => $this->event->location ?? '',
            'event_date' => $this->event->start_date ? $this->event->start_date->format('M d, Y') : '',
            'event_time' => $this->event->start_time ? trim($this->event->start_time.' - '.$this->event->end_time) : '',
            'event_url' => config('app.frontend_url', config('app.url', 'http://localhost')).'/#/youth/events',
        ]);

        return new Envelope(
            subject: $rendered['subject'] ?: "New Event: {$this->event->name}",
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
