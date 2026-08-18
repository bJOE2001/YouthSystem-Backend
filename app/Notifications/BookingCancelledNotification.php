<?php

namespace App\Notifications;

use App\Mail\BookingCancelledEmail;
use App\Models\BookingRequest;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Notifications\Notification;

class BookingCancelledNotification extends Notification
{
    use Queueable;

    public $booking;

    public ?string $remarks;

    public function __construct(BookingRequest $booking, ?string $remarks = null)
    {
        $this->booking = $booking;
        $this->remarks = $remarks;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): Mailable
    {
        /** @var User $notifiable */
        return (new BookingCancelledEmail($notifiable, $this->booking, $this->remarks))
            ->to($notifiable->email);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $reasonText = $this->remarks ? " Reason: {$this->remarks}" : '';

        return [
            'title' => 'Booking Cancelled',
            'message' => "Your booking for {$this->booking->facility->name} on {$this->booking->date} was cancelled by administrator.{$reasonText}",
            'booking_id' => $this->booking->id,
            'facility_id' => $this->booking->facility_id,
            'remarks' => $this->remarks,
            'url' => '/youth/facilities',
        ];
    }
}
