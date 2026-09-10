<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ScholarComplianceHardCopyPromptNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $schoolYear,
        public string $semester,
        public ?string $instructions = null,
        public array $requiredDocuments = []
    ) {}

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
        $docList = ! empty($this->requiredDocuments)
            ? implode(', ', array_filter($this->requiredDocuments))
            : 'Grades, COR/COE';

        $defaultMsg = "Your digital requirements for S.Y. {$this->schoolYear} ({$this->semester}) have been uploaded. Please submit your physical hard copies ({$docList}) enclosed in a long brown folder to the Tagum City Youth Development Office (TCYDO).";

        return [
            'title' => 'Submit Physical Hard Copies',
            'message' => $this->instructions ? "{$defaultMsg} Note: {$this->instructions}" : $defaultMsg,
            'url' => '/youth/scholarship/ecespro/requirements',
            'school_year' => $this->schoolYear,
            'semester' => $this->semester,
            'action_required' => 'submit_hard_copy',
            'required_documents' => $this->requiredDocuments,
        ];
    }
}
