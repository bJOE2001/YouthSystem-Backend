<?php

namespace App\Notifications;

use App\Channels\SmsChannel;
use App\Mail\BookingConfirmedEmail;
use App\Models\BookingRequest;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Notifications\Notification;

class BookingConfirmedNotification extends Notification
{
    use Queueable;

    public $booking;

    public function __construct(BookingRequest $booking)
    {
        $this->booking = $booking;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail', SmsChannel::class];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): Mailable
    {
        /** @var User $notifiable */
        return (new BookingConfirmedEmail($notifiable, $this->booking))
            ->to($notifiable->email);
    }

    /**
     * Get the SMS representation of the notification.
     */
    public function toSms(object $notifiable): string
    {
        $facilityName = $this->booking->facility->name ?? 'Facility';
        $date = $this->booking->date ?? '';

        return "TCYSDO: Your reservation for {$facilityName} on {$date} has been confirmed. Thank you!";
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Booking Confirmed',
            'message' => "Your reservation for {$this->booking->facility->name} on {$this->booking->date} is confirmed.",
            'booking_id' => $this->booking->id,
            'facility_id' => $this->booking->facility_id,
            'url' => '/youth/facilities',
        ];
    }
}
