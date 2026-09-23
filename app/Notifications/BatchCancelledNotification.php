<?php

namespace App\Notifications;

use App\Channels\SmsChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BatchCancelledNotification extends Notification
{
    use Queueable;

    public $batchName;
    public $stage;
    public $reason;

    /**
     * Create a new notification instance.
     *
     * @param string $batchName
     * @param string $stage e.g., "Exam", "Panel Interview", "Contract Signing", "Grant Release"
     * @param string $reason
     */
    public function __construct(string $batchName, string $stage, string $reason)
    {
        $this->batchName = $batchName;
        $this->stage = $stage;
        $this->reason = $reason;
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
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
                    ->subject("ECESPRO {$this->stage} Schedule Cancelled")
                    ->greeting("Hello " . ($notifiable->first_name ?? $notifiable->name ?? 'Applicant') . "!")
                    ->line("Your scheduled {$this->stage} for batch '{$this->batchName}' has been cancelled.")
                    ->line("Reason: {$this->reason}")
                    ->action('View Youth Portal', url('/'))
                    ->line('Thank you!');
    }

    /**
     * Get the SMS representation of the notification.
     */
    public function toSms(object $notifiable): string
    {
        return "TCYSDO: Your ECESPRO {$this->stage} schedule (Batch: {$this->batchName}) has been cancelled. Reason: {$this->reason}.";
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => "ECESPRO {$this->stage} Schedule Cancelled",
            'message' => "Your scheduled {$this->stage} for batch '{$this->batchName}' has been cancelled. Reason: {$this->reason}",
            'url' => '/youth/scholarship/ecespro',
            'metadata' => [
                'batch_name' => $this->batchName,
                'stage' => $this->stage,
                'reason' => $this->reason
            ],
        ];
    }
}
