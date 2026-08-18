<?php

namespace App\Mail;

use App\Models\EcesproApplication;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewEcesproApplicationEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $admin,
        public EcesproApplication $application
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $applicantName = trim(($this->application->first_name ?? '').' '.($this->application->last_name ?? ''));
        $applicantName = $applicantName ?: ($this->application->user->name ?? 'Applicant');

        return new Envelope(
            subject: "New ECESPRO Application: {$applicantName}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $adminUrl = config('app.frontend_url', config('app.url', 'http://localhost'))."#/admin/scholarship/ecespro/{$this->application->id}";
        $applicantName = trim(($this->application->first_name ?? '').' '.($this->application->last_name ?? ''));
        $applicantName = $applicantName ?: ($this->application->user->name ?? 'Applicant');

        return new Content(
            view: 'emails.new-ecespro-application',
            with: [
                'admin' => $this->admin,
                'application' => $this->application,
                'applicantName' => $applicantName,
                'adminUrl' => $adminUrl,
            ],
        );
    }
}
