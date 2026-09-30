<?php

namespace App\Notifications;

use App\Channels\SmsChannel;
use App\Mail\GrantReleaseEmail;
use App\Models\EcesproGrantReleaseBatch;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Notifications\Notification;

class GrantReleaseNotification extends Notification
{
    use Queueable;

    protected $batch;

    /**
     * Create a new notification instance.
     */
    public function __construct(EcesproGrantReleaseBatch $batch)
    {
        $this->batch = $batch;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', SmsChannel::class];
    }

    /**
     * Get the SMS representation of the notification.
     */
    public function toSms(object $notifiable): string
    {
        $formattedDate = $this->batch->release_date ? $this->batch->release_date->format('M d, Y') : '';
        $time = $this->batch->time ?? '';
        $venue = $this->batch->venue ?? '';

        return "TCYSDO: ECESPRO Grant Release - Batch: {$this->batch->batch_name}, Date: {$formattedDate}, Time: {$time}, Venue: {$venue}. Please bring valid ID.";
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): Mailable
    {
        return (new GrantReleaseEmail(
            $notifiable,
            $this->batch
        ))->to($notifiable->email);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
