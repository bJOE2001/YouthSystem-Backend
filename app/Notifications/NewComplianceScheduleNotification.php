<?php

namespace App\Notifications;

use App\Mail\NewComplianceScheduleEmail;
use App\Models\EcesproComplianceSchedule;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Notifications\Notification;

class NewComplianceScheduleNotification extends Notification
{
    use Queueable;

    public $schedule;

    /**
     * Create a new notification instance.
     */
    public function __construct(EcesproComplianceSchedule $schedule)
    {
        $this->schedule = $schedule;
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
        return (new NewComplianceScheduleEmail($notifiable, $this->schedule))
            ->to($notifiable->email);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'New Compliance Schedule',
            'message' => "A new compliance schedule for {$this->schedule->school_year} ({$this->schedule->semester}) has been posted. Please submit your requirements.",
            'schedule_id' => $this->schedule->id,
            'url' => '/youth/scholarship/ecespro/requirements',
        ];
    }
}
