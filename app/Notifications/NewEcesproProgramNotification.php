<?php

namespace App\Notifications;

use App\Enums\UserRole;
use App\Models\EcesproProgram;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewEcesproProgramNotification extends Notification
{
    use Queueable;

    public $program;

    /**
     * Create a new notification instance.
     */
    public function __construct(EcesproProgram $program)
    {
        $this->program = $program;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $role = $notifiable instanceof User ? $notifiable->role : null;
        $isSk = $role === UserRole::SkAdmin || $role === 'sk_admin' || ($role instanceof \BackedEnum && $role->value === 'sk_admin');

        return [
            'title' => $this->program->title,
            'message' => $this->program->description ?: "A new scholarship program ({$this->program->school_year}) is now open for application.",
            'description' => $this->program->description ?? '',
            'program_id' => $this->program->id,
            'school_year' => $this->program->school_year,
            'url' => $isSk ? '/sk/scholarship/ecespro' : '/youth/scholarship/ecespro',
        ];
    }
}
