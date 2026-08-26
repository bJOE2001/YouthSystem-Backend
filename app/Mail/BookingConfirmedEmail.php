<?php

namespace App\Mail;

use App\Models\BookingRequest;
use App\Models\User;
use App\Services\EmailTemplateService;
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

        $rendered = app(EmailTemplateService::class)->render('booking_confirmed', [
            'user_name' => $this->user->name,
            'facility_name' => $facilityName,
            'booking_date' => $this->booking->date ?? '',
            'booking_time' => trim(($this->booking->start_time ?? '').' - '.($this->booking->end_time ?? '')),
            'purpose' => $this->booking->purpose ?? '',
            'portal_url' => config('app.frontend_url', config('app.url', 'http://localhost')).'/#/youth/facilities',
        ]);

        return new Envelope(
            subject: $rendered['subject'] ?: "Facility Booking Confirmed - {$facilityName}",
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
