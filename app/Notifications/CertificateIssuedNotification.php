<?php

namespace App\Notifications;

use App\Mail\CertificateIssuedEmail;
use App\Models\Event;
use App\Models\SportsProgram;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Notifications\Notification;

class CertificateIssuedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Event|SportsProgram $activity,
        public string $pdfContent,
        public string $filename
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): Mailable
    {
        /** @var User $notifiable */
        return (new CertificateIssuedEmail(
            $notifiable,
            $this->activity,
            $this->pdfContent,
            $this->filename
        ))->to($notifiable->email);
    }

    /**
     * Get the array representation of the notification for database channel.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $isEvent = $this->activity instanceof Event;
        $unifiedId = $isEvent ? "event_{$this->activity->id}" : "sport_{$this->activity->id}";

        return [
            'title' => 'Certificate Issued',
            'message' => "Your Certificate of Participation for '{$this->activity->name}' has been emailed to you and is ready for download.",
            'activity_id' => $unifiedId,
            'activity_name' => $this->activity->name,
            'url' => '/youth/my-activities',
        ];
    }
}
