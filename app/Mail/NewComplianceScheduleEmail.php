<?php

namespace App\Mail;

use App\Models\EcesproComplianceSchedule;
use App\Models\User;
use App\Services\EmailTemplateService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewComplianceScheduleEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public EcesproComplianceSchedule $schedule
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $rendered = app(EmailTemplateService::class)->render('new_compliance_schedule', [
            'user_name' => $this->user->name,
            'schedule_title' => $this->schedule->title,
            'school_year' => $this->schedule->school_year ?? '',
            'semester' => $this->schedule->semester ?? '',
            'submission_period' => ($this->schedule->start_date ? $this->schedule->start_date->format('M d, Y') : '').' - '.($this->schedule->end_date ? $this->schedule->end_date->format('M d, Y') : ''),
            'instructions' => $this->schedule->instructions ?? '',
            'requirements_url' => config('app.frontend_url', config('app.url', 'http://localhost')).'/#/youth/scholarship/ecespro/requirements',
        ]);

        return new Envelope(
            subject: $rendered['subject'] ?: "Compliance Notice: {$this->schedule->title} ({$this->schedule->school_year})",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $requirementsUrl = config('app.frontend_url', config('app.url', 'http://localhost')).'/#/youth/scholarship/ecespro/requirements';

        return new Content(
            view: 'emails.new-compliance-schedule',
            with: [
                'user' => $this->user,
                'schedule' => $this->schedule,
                'requirementsUrl' => $requirementsUrl,
            ],
        );
    }
}
