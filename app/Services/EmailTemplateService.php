<?php

namespace App\Services;

use App\Models\SystemSetting;

class EmailTemplateService
{
    /**
     * Get all default email templates definitions.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function getDefaults(): array
    {
        return [
            'youth_validated' => [
                'name' => 'Youth Account Validated',
                'description' => 'Sent to registered youth members when their profile is verified and approved by SK.',
                'subject' => 'Youth Account Approved & Validated',
                'heading' => 'Hello {user_name},',
                'body' => "Great news! Your youth registration profile has been officially reviewed, validated, and approved by your Sangguniang Kabataan (SK) Official.\n\nYou can now log in to the youth portal using your registered email and your temporary birthdate password.",
                'button_text' => 'Log In to Your Account →',
                'security_notice' => 'For your account security, please update your password after logging in for the first time.',
                'placeholders' => [
                    '{user_name}' => 'Full name of the youth member',
                    '{user_email}' => 'Registered email address',
                    '{initial_password}' => 'Default temporary password (birthdate: MMDDYY)',
                    '{login_url}' => 'Direct link to login page',
                ],
                'sample_data' => [
                    'user_name' => 'Juan Dela Cruz',
                    'user_email' => 'juan.delacruz@gmail.com',
                    'initial_password' => '051202',
                    'login_url' => config('app.frontend_url', config('app.url', 'http://localhost')).'/#/login',
                ],
            ],

            'booking_confirmed' => [
                'name' => 'Facility Booking Confirmed',
                'description' => 'Sent to users when their facility reservation is directly booked and confirmed.',
                'subject' => 'Facility Booking Confirmed - {facility_name}',
                'heading' => 'Facility Booking Confirmed!',
                'body' => 'Hello {user_name}, your reservation for {facility_name} has been directly booked and confirmed. Please review your booking schedule details below.',
                'button_text' => 'View in My Bookings →',
                'placeholders' => [
                    '{user_name}' => 'Name of the reserving user',
                    '{facility_name}' => 'Name of the youth/sports facility',
                    '{booking_date}' => 'Reserved booking date (e.g. October 15, 2026)',
                    '{booking_time}' => 'Time schedule slot (e.g. 08:00 AM - 12:00 PM)',
                    '{purpose}' => 'Purpose of booking',
                    '{portal_url}' => 'Link to user facilities portal',
                ],
                'sample_data' => [
                    'user_name' => 'Maria Santos',
                    'facility_name' => 'Tagum City Youth Gymnasium',
                    'booking_date' => 'October 15, 2026',
                    'booking_time' => '08:00 AM - 12:00 PM',
                    'purpose' => 'Barangay Youth Basketball Tournament',
                    'portal_url' => config('app.frontend_url', config('app.url', 'http://localhost')).'/#/youth/facilities',
                ],
            ],

            'booking_cancelled' => [
                'name' => 'Facility Booking Cancelled',
                'description' => 'Sent to users when their facility booking reservation is cancelled by an administrator.',
                'subject' => 'Facility Booking Cancelled - {facility_name}',
                'heading' => 'Facility Booking Cancelled',
                'body' => 'Hello {user_name}, we are writing to inform you that your booking for {facility_name} has been cancelled by the administration.',
                'button_text' => 'Explore Available Facilities →',
                'footnote' => 'If you have questions or wish to book a different time slot or facility, you may view availability on the portal.',
                'placeholders' => [
                    '{user_name}' => 'Name of the reserving user',
                    '{facility_name}' => 'Name of the cancelled facility',
                    '{booking_date}' => 'Scheduled booking date',
                    '{booking_time}' => 'Scheduled time slot',
                    '{remarks}' => 'Administrator cancellation reason / remarks',
                    '{portal_url}' => 'Link to facilities page',
                ],
                'sample_data' => [
                    'user_name' => 'Maria Santos',
                    'facility_name' => 'Tagum City Youth Gymnasium',
                    'booking_date' => 'October 15, 2026',
                    'booking_time' => '08:00 AM - 12:00 PM',
                    'remarks' => 'Facility under scheduled maintenance and repairs.',
                    'portal_url' => config('app.frontend_url', config('app.url', 'http://localhost')).'/#/youth/facilities',
                ],
            ],

            'new_announcement' => [
                'name' => 'Official Announcement',
                'description' => 'Broadcast notification sent to youth members when a new announcement is posted.',
                'subject' => 'Announcement: {announcement_title}',
                'heading' => 'Official Announcement',
                'body' => 'Hello {user_name}, a new official announcement has been published by TCYDO. Please check the details below.',
                'button_text' => 'View on Portal →',
                'placeholders' => [
                    '{user_name}' => 'Recipient user name',
                    '{announcement_title}' => 'Title of the announcement',
                    '{announcement_description}' => 'Announcement body text',
                    '{published_date}' => 'Date posted',
                    '{announcement_url}' => 'Link to portal announcements',
                ],
                'sample_data' => [
                    'user_name' => 'Youth Member',
                    'announcement_title' => 'Tagum City Youth Leadership Summit 2026',
                    'announcement_description' => 'Registration is now open for all barangay youth leaders for the annual leadership empowerment camp.',
                    'published_date' => date('F d, Y'),
                    'announcement_url' => config('app.frontend_url', config('app.url', 'http://localhost')).'/#/youth/announcements',
                ],
            ],

            'new_event' => [
                'name' => 'New Youth Event / Activity',
                'description' => 'Sent to youth members when a new event or sports program is posted.',
                'subject' => 'New Youth Activity: {event_name}',
                'heading' => 'New Youth Event Posted!',
                'body' => 'Hello {user_name}, a new youth activity has just been scheduled. Join in, gain new skills, and connect with fellow youth leaders!',
                'button_text' => 'Join This Event →',
                'placeholders' => [
                    '{user_name}' => 'Recipient user name',
                    '{event_name}' => 'Name of the event / activity',
                    '{classification}' => 'PPA Classification or sports category',
                    '{location}' => 'Event venue / location',
                    '{event_date}' => 'Event date schedule',
                    '{event_time}' => 'Time schedule',
                    '{event_url}' => 'Link to activity page',
                ],
                'sample_data' => [
                    'user_name' => 'Youth Leader',
                    'event_name' => 'Youth Environmental Action & Tree Planting 2026',
                    'classification' => 'Environmental Protection & Climate Action',
                    'location' => 'Tagum City Botanical Park, Magugpo North',
                    'event_date' => 'September 20, 2026',
                    'event_time' => '07:00 AM - 11:30 AM',
                    'event_url' => config('app.frontend_url', config('app.url', 'http://localhost')).'/#/youth/events',
                ],
            ],

            'certificate_issued' => [
                'name' => 'Certificate of Participation',
                'description' => 'Sent to participants upon successful attendance in events or sports programs.',
                'subject' => 'Certificate of Participation - {activity_name}',
                'heading' => 'Certificate of Participation',
                'body' => 'Dear {user_name}, thank you for actively participating in our community development programs! Your official certificate has been issued.',
                'button_text' => 'View in My Activities →',
                'attachment_notice' => 'Your official personalized Certificate of Participation has been attached to this email as a PDF. You can download and print it directly.',
                'placeholders' => [
                    '{user_name}' => 'Participant name',
                    '{activity_name}' => 'Event or sports program title',
                    '{category}' => 'Category or classification',
                    '{activities_url}' => 'Link to user activities page',
                ],
                'sample_data' => [
                    'user_name' => 'Jane Doe',
                    'activity_name' => 'Summer Youth Digital Skills Bootcamp',
                    'category' => 'Youth Development & Digital Literacy',
                    'activities_url' => config('app.frontend_url', config('app.url', 'http://localhost')).'/#/youth/my-activities',
                ],
            ],

            'new_compliance_schedule' => [
                'name' => 'Compliance Schedule Notice',
                'description' => 'Sent to ECESPRO scholarship recipients when a new semester document compliance window opens.',
                'subject' => 'New Compliance Submission Schedule - {schedule_title}',
                'heading' => 'New Compliance Submission Schedule',
                'body' => 'Hello {user_name}, a new compliance schedule has been posted for the {school_year} ({semester}) term. Please review the submission requirements and deadline below.',
                'button_text' => 'Submit Compliance Documents →',
                'placeholders' => [
                    '{user_name}' => 'Scholar name',
                    '{schedule_title}' => 'Title of the compliance schedule',
                    '{school_year}' => 'School year (e.g. 2026-2027)',
                    '{semester}' => 'Semester (e.g. 1st Semester)',
                    '{submission_period}' => 'Date range for submissions',
                    '{instructions}' => 'Admin submission instructions',
                    '{requirements_url}' => 'Link to compliance submission page',
                ],
                'sample_data' => [
                    'user_name' => 'Carlos Mendoza',
                    'schedule_title' => '1st Semester S.Y. 2026-2027 Document Compliance',
                    'school_year' => 'S.Y. 2026-2027',
                    'semester' => '1st Semester',
                    'submission_period' => 'October 01, 2026 - October 25, 2026',
                    'instructions' => 'Upload your Certificate of Registration (COR) and Official Certificate of Grades.',
                    'requirements_url' => config('app.frontend_url', config('app.url', 'http://localhost')).'/#/youth/ecespro-requirements',
                ],
            ],

            'ecespro_status' => [
                'name' => 'ECESPRO Status Updates',
                'description' => 'Sent to scholarship applicants upon stage progression (Exam Scheduled, Interview Scheduled, Approved, For Revision).',
                'subject' => 'ECESPRO Scholarship Update',
                'heading' => 'Hello {recipient_name},',
                'body' => '{status_message}',
                'button_text' => 'View Application Portal →',
                'placeholders' => [
                    '{recipient_name}' => 'Scholar/Applicant name',
                    '{status}' => 'Application status',
                    '{status_message}' => 'Stage headline or status update message',
                    '{portal_url}' => 'Link to youth portal',
                ],
                'sample_data' => [
                    'recipient_name' => 'Kyla Mae Ramos',
                    'status' => 'Exam Scheduled',
                    'status_message' => 'Congratulations! You have been qualified to take the ECESPRO Qualifying Examination.',
                    'portal_url' => config('app.frontend_url', config('app.url', 'http://localhost')).'/#/youth/ecespro',
                ],
            ],

            'inactive_user_reengagement' => [
                'name' => 'Inactive User Re-engagement',
                'description' => 'Sent to registered youth members who have not logged in for a while to encourage them to explore new opportunities.',
                'subject' => '👋 We Miss You, {user_name}! Discover What\'s New at TCYDO',
                'heading' => 'We Miss You, {user_name}!',
                'body' => "It has been a while since you last visited the Tagum City Youth Development Portal.\n\nNew youth programs, upcoming sports tournaments, leadership workshops, and scholarship updates are happening right now. Don't miss out on community opportunities tailored for you!",
                'button_text' => 'Log In & Explore →',
                'footnote' => 'If you have forgotten your password, you can easily reset it using the "Forgot Password" link on the login page.',
                'placeholders' => [
                    '{user_name}' => 'Full name of the youth member',
                    '{user_email}' => 'Registered email address',
                    '{last_login_formatted}' => 'Date of last login or "Never logged in"',
                    '{days_inactive}' => 'Number of days since last login or account creation',
                    '{login_url}' => 'Direct link to login page',
                    '{portal_url}' => 'Link to youth portal',
                ],
                'sample_data' => [
                    'user_name' => 'Alex Rivera',
                    'user_email' => 'alex.rivera@gmail.com',
                    'last_login_formatted' => 'June 15, 2026',
                    'days_inactive' => '45',
                    'login_url' => config('app.frontend_url', config('app.url', 'http://localhost')).'/#/login',
                    'portal_url' => config('app.frontend_url', config('app.url', 'http://localhost')).'/#/youth/dashboard',
                ],
            ],
        ];
    }

    /**
     * Get all templates with saved custom overrides.
     *
     * @return array<string, array<string, mixed>>
     */
    public function getAllTemplates(): array
    {
        $defaults = self::getDefaults();
        $setting = SystemSetting::where('key', 'email_templates')->first();
        $saved = ($setting && is_array($setting->value)) ? $setting->value : [];

        $result = [];
        foreach ($defaults as $key => $default) {
            $custom = $saved[$key] ?? [];
            $result[$key] = array_merge($default, [
                'key' => $key,
                'is_customized' => ! empty($custom),
                'custom' => $custom,
                'subject' => $custom['subject'] ?? $default['subject'],
                'heading' => $custom['heading'] ?? $default['heading'],
                'body' => $custom['body'] ?? $default['body'],
                'button_text' => $custom['button_text'] ?? $default['button_text'],
                'security_notice' => $custom['security_notice'] ?? ($default['security_notice'] ?? null),
                'attachment_notice' => $custom['attachment_notice'] ?? ($default['attachment_notice'] ?? null),
                'footnote' => $custom['footnote'] ?? ($default['footnote'] ?? null),
            ]);
        }

        return $result;
    }

    /**
     * Get a specific template by key.
     *
     * @return array<string, mixed>|null
     */
    public function getTemplate(string $key): ?array
    {
        $all = $this->getAllTemplates();

        return $all[$key] ?? null;
    }

    /**
     * Update a specific email template.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function updateTemplate(string $key, array $data): array
    {
        $defaults = self::getDefaults();
        if (! isset($defaults[$key])) {
            throw new \InvalidArgumentException("Invalid email template key: {$key}");
        }

        $setting = SystemSetting::firstOrCreate(['key' => 'email_templates'], ['value' => []]);
        $currentValue = is_array($setting->value) ? $setting->value : [];

        $currentValue[$key] = [
            'subject' => trim((string) ($data['subject'] ?? '')),
            'heading' => trim((string) ($data['heading'] ?? '')),
            'body' => trim((string) ($data['body'] ?? '')),
            'button_text' => trim((string) ($data['button_text'] ?? '')),
            'security_notice' => isset($data['security_notice']) ? trim((string) $data['security_notice']) : null,
            'attachment_notice' => isset($data['attachment_notice']) ? trim((string) $data['attachment_notice']) : null,
            'footnote' => isset($data['footnote']) ? trim((string) $data['footnote']) : null,
        ];

        // Clean nulls
        $currentValue[$key] = array_filter($currentValue[$key], fn ($v) => $v !== null && $v !== '');

        $setting->update(['value' => $currentValue]);

        return $this->getTemplate($key);
    }

    /**
     * Reset a specific email template back to default.
     *
     * @return array<string, mixed>
     */
    public function resetTemplate(string $key): array
    {
        $setting = SystemSetting::where('key', 'email_templates')->first();
        if ($setting && is_array($setting->value) && isset($setting->value[$key])) {
            $value = $setting->value;
            unset($value[$key]);
            $setting->update(['value' => $value]);
        }

        return $this->getTemplate($key);
    }

    /**
     * Render dynamic template fields by replacing {placeholder} tags with values.
     *
     * @param  array<string, mixed>  $variables
     * @return array<string, mixed>
     */
    public function render(string $key, array $variables = []): array
    {
        $template = $this->getTemplate($key);
        if (! $template) {
            return [];
        }

        $replacePlaceholders = function (?string $text) use ($variables): string {
            if ($text === null) {
                return '';
            }

            foreach ($variables as $varKey => $varVal) {
                if (is_scalar($varVal) || (is_object($varVal) && method_exists($varVal, '__toString'))) {
                    $text = str_replace('{'.$varKey.'}', (string) $varVal, $text);
                }
            }

            return $text;
        };

        return [
            'subject' => $replacePlaceholders($template['subject'] ?? ''),
            'heading' => $replacePlaceholders($template['heading'] ?? ''),
            'body' => $replacePlaceholders($template['body'] ?? ''),
            'button_text' => $replacePlaceholders($template['button_text'] ?? ''),
            'security_notice' => $replacePlaceholders($template['security_notice'] ?? ''),
            'attachment_notice' => $replacePlaceholders($template['attachment_notice'] ?? ''),
            'footnote' => $replacePlaceholders($template['footnote'] ?? ''),
        ];
    }
}
