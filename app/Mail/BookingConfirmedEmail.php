<?php

namespace App\Mail;

use App\Models\BookingRequest;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingConfirmedEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public BookingRequest $booking
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $facilityName = $this->booking->facility->name ?? 'Facility';

        return new Envelope(
            subject: "Facility Booking Confirmed - {$facilityName}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $portalUrl = config('app.frontend_url', config('app.url', 'http://localhost')).'/#/youth/facilities';

        return new Content(
            view: 'emails.booking-confirmed',
            with: [
                'user' => $this->user,
                'booking' => $this->booking,
                'facility' => $this->booking->facility,
                'portalUrl' => $portalUrl,
            ],
        );
    }
}
