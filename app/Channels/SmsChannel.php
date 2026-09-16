<?php

namespace App\Channels;

use App\Models\User;
use App\Services\SmsService;
use Illuminate\Notifications\Notification;

class SmsChannel
{
    /**
     * Create a new channel instance.
     */
    public function __construct(
        protected SmsService $smsService
    ) {}

    /**
     * Send the given notification via SMS.
     */
    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toSms')) {
            return;
        }

        $message = $notification->toSms($notifiable);
        if (empty($message)) {
            return;
        }

        $to = $notifiable->routeNotificationFor('sms', $notification);
        if (empty($to)) {
            return;
        }

        $user = $notifiable instanceof User ? $notifiable : null;
        $eventType = class_basename($notification);

        $this->smsService->send((string) $to, (string) $message, $user, $eventType);
    }
}
