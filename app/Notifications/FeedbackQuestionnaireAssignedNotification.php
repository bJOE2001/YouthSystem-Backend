<?php

namespace App\Notifications;

use App\Enums\UserRole;
use App\Models\Event;
use App\Models\FeedbackQuestionnaire;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class FeedbackQuestionnaireAssignedNotification extends Notification
{
    use Queueable;

    public $activity;
    public FeedbackQuestionnaire $questionnaire;

    /**
     * Create a new notification instance.
     */
    public function __construct(object $activity, FeedbackQuestionnaire $questionnaire)
    {
        $this->activity = $activity;
        $this->questionnaire = $questionnaire;
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

        $isEvent = $this->activity instanceof Event;
        $activityType = $isEvent ? 'event' : 'sport';
        $activityId = (string) $this->activity->id;
        $activityName = $this->activity->name ?? 'Completed Activity';

        $prefix = $isSk ? '/sk' : '/youth';
        $url = "{$prefix}/feedback/questionnaire?activity_id={$activityId}&activity_type={$activityType}&questionnaire_id={$this->questionnaire->id}";

        return [
            'title' => "Feedback Questionnaire: {$activityName}",
            'message' => "Please evaluate \"{$activityName}\". Your feedback helps organizers improve future youth programs.",
            'description' => "Please evaluate \"{$activityName}\". Your feedback helps organizers improve future youth programs.",
            'type' => 'feedback_questionnaire',
            'activity_type' => $activityType,
            'activity_id' => $activityId,
            'activity_name' => $activityName,
            'questionnaire_id' => $this->questionnaire->id,
            'questionnaire_title' => $this->questionnaire->title,
            'url' => $url,
        ];
    }
}

