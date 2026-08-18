<?php

namespace App\Mail;

use App\Models\BookingRequest;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewBookingRequestEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $admin,
        public BookingRequest $booking
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $facilityName = $this->booking->facility->name ?? 'Facility';
        $requesterName = $this->booking->user->name ?? 'A user';

        return new Envelope(
            subject: "New Facility Booking: {$facilityName} by {$requesterName}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $portalUrl = config('app.frontend_url', config('app.url', 'http://localhost')).'/#/admin/booking-requests';

        return new Content(
            view: 'emails.new-booking-request',
            with: [
                'admin' => $this->admin,
                'booking' => $this->booking,
                'facility' => $this->booking->facility,
                'requester' => $this->booking->user,
                'portalUrl' => $portalUrl,
            ],
        );
    }
}
