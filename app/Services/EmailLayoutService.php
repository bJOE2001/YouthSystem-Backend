<?php

namespace App\Services;

use App\Mail\TestEmailLayout;
use App\Models\SystemSetting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;

class EmailLayoutService
{
    /**
     * Default email layout configuration.
     *
     * @return array<string, mixed>
     */
    public static function getDefaults(): array
    {
        return [
            'logo_url' => 'https://i.imgur.com/1ebdGUz.png',
            'image_path' => null,
            'show_logo' => true,
            'logo_height' => '76px',
            'header_title' => 'Tagum City Youth Development Office',
            'header_subtitle' => 'Federation of Sangguniang Kabataan',
            'show_header_title' => false,
            'primary_color' => '#07823f',
            'secondary_color' => '#0b6b3a',
            'accent_color' => '#0b6b3a',
            'body_bg_color' => '#f1f5f9',
            'card_bg_color' => '#ffffff',
            'card_border_radius' => '14px',
            'heading_color' => '#0f172a',
            'text_color' => '#334155',
            'button_bg_color' => '#07823f',
            'button_text_color' => '#ffffff',
            'footer_text' => 'Tagum City Youth Development Office & Federation of Sangguniang Kabataan',
            'footer_tagline' => 'Empowering the Youth, Building the Future.',
            'footer_disclaimer' => 'This is an automated notification. Please do not reply directly to this email.',
            'contact_email' => 'youth@tagum.gov.ph',
            'contact_phone' => '(084) 123-4567',
            'office_address' => 'Tagum City Hall, Tagum City, Davao del Norte',
            'show_footer_contact' => true,
            'show_social_links' => true,
            'social_links' => [
                ['platform' => 'facebook', 'url' => 'https://facebook.com'],
                ['platform' => 'instagram', 'url' => 'https://instagram.com'],
            ],
            'copyright_text' => '© {year} Tagum City Youth Development Office. All rights reserved.',
        ];
    }

    /**
     * Get the active email layout settings merged with defaults.
     *
     * @return array<string, mixed>
     */
    public function getSettings(): array
    {
        $setting = SystemSetting::where('key', 'email_layout')->first();
        $saved = ($setting && is_array($setting->value)) ? $setting->value : [];
        $defaults = self::getDefaults();

        $merged = array_merge($defaults, $saved);

        // Resolve logo URL
        $imagePath = $merged['image_path'] ?? null;
        if (! empty($imagePath)) {
            $merged['logo_url'] = filter_var($imagePath, FILTER_VALIDATE_URL)
                ? $imagePath
                : url('storage/'.$imagePath);
        } elseif (empty($merged['logo_url'])) {
            $merged['logo_url'] = $defaults['logo_url'];
        }

        // Format copyright text with current year
        if (isset($merged['copyright_text'])) {
            $merged['copyright_text_formatted'] = str_replace('{year}', date('Y'), $merged['copyright_text']);
        }

        return $merged;
    }

    /**
     * Update email layout settings.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function updateSettings(array $input, ?UploadedFile $logoFile = null, bool $removeLogo = false): array
    {
        $setting = SystemSetting::firstOrCreate(['key' => 'email_layout'], ['value' => []]);
        $currentValue = is_array($setting->value) ? $setting->value : [];
        $defaults = self::getDefaults();

        $imagePath = $currentValue['image_path'] ?? null;

        if ($removeLogo) {
            if ($imagePath && Storage::disk('public')->exists($imagePath)) {
                Storage::disk('public')->delete($imagePath);
            }
            $imagePath = null;
        }

        if ($logoFile) {
            if ($imagePath && Storage::disk('public')->exists($imagePath)) {
                Storage::disk('public')->delete($imagePath);
            }
            $imagePath = $logoFile->store('email_logo', 'public');
        }

        // Clean and filter social links
        $cleanedSocialLinks = [];
        if (isset($input['social_links']) && is_array($input['social_links'])) {
            foreach ($input['social_links'] as $item) {
                if (! empty($item['url'])) {
                    $cleanedSocialLinks[] = [
                        'platform' => $item['platform'] ?? 'website',
                        'url' => trim($item['url']),
                    ];
                }
            }
        } elseif (array_key_exists('social_links', $input) && empty($input['social_links'])) {
            $cleanedSocialLinks = [];
        } else {
            $cleanedSocialLinks = $currentValue['social_links'] ?? $defaults['social_links'];
        }

        $booleanHelper = function ($key, $default) use ($input, $currentValue) {
            if (array_key_exists($key, $input)) {
                return filter_var($input[$key], FILTER_VALIDATE_BOOLEAN);
            }

            return isset($currentValue[$key]) ? (bool) $currentValue[$key] : $default;
        };

        $updatedData = [
            'image_path' => $imagePath,
            'logo_url' => $input['logo_url'] ?? ($currentValue['logo_url'] ?? $defaults['logo_url']),
            'show_logo' => $booleanHelper('show_logo', $defaults['show_logo']),
            'logo_height' => $input['logo_height'] ?? ($currentValue['logo_height'] ?? $defaults['logo_height']),
            'header_title' => $input['header_title'] ?? ($currentValue['header_title'] ?? $defaults['header_title']),
            'header_subtitle' => $input['header_subtitle'] ?? ($currentValue['header_subtitle'] ?? $defaults['header_subtitle']),
            'show_header_title' => $booleanHelper('show_header_title', $defaults['show_header_title']),
            'primary_color' => $input['primary_color'] ?? ($currentValue['primary_color'] ?? $defaults['primary_color']),
            'secondary_color' => $input['secondary_color'] ?? ($currentValue['secondary_color'] ?? $defaults['secondary_color']),
            'accent_color' => $input['accent_color'] ?? ($currentValue['accent_color'] ?? $defaults['accent_color']),
            'body_bg_color' => $input['body_bg_color'] ?? ($currentValue['body_bg_color'] ?? $defaults['body_bg_color']),
            'card_bg_color' => $input['card_bg_color'] ?? ($currentValue['card_bg_color'] ?? $defaults['card_bg_color']),
            'card_border_radius' => $input['card_border_radius'] ?? ($currentValue['card_border_radius'] ?? $defaults['card_border_radius']),
            'heading_color' => $input['heading_color'] ?? ($currentValue['heading_color'] ?? $defaults['heading_color']),
            'text_color' => $input['text_color'] ?? ($currentValue['text_color'] ?? $defaults['text_color']),
            'button_bg_color' => $input['button_bg_color'] ?? ($currentValue['button_bg_color'] ?? $defaults['button_bg_color']),
            'button_text_color' => $input['button_text_color'] ?? ($currentValue['button_text_color'] ?? $defaults['button_text_color']),
            'footer_text' => $input['footer_text'] ?? ($currentValue['footer_text'] ?? $defaults['footer_text']),
            'footer_tagline' => $input['footer_tagline'] ?? ($currentValue['footer_tagline'] ?? $defaults['footer_tagline']),
            'footer_disclaimer' => $input['footer_disclaimer'] ?? ($currentValue['footer_disclaimer'] ?? $defaults['footer_disclaimer']),
            'contact_email' => $input['contact_email'] ?? ($currentValue['contact_email'] ?? $defaults['contact_email']),
            'contact_phone' => $input['contact_phone'] ?? ($currentValue['contact_phone'] ?? $defaults['contact_phone']),
            'office_address' => $input['office_address'] ?? ($currentValue['office_address'] ?? $defaults['office_address']),
            'show_footer_contact' => $booleanHelper('show_footer_contact', $defaults['show_footer_contact']),
            'show_social_links' => $booleanHelper('show_social_links', $defaults['show_social_links']),
            'social_links' => $cleanedSocialLinks,
            'copyright_text' => $input['copyright_text'] ?? ($currentValue['copyright_text'] ?? $defaults['copyright_text']),
        ];

        $setting->update(['value' => $updatedData]);

        return $this->getSettings();
    }

    /**
     * Render the email HTML preview.
     *
     * @param  array<string, mixed>|null  $customSettings
     */
    public function renderPreview(?array $customSettings = null): string
    {
        $layout = $customSettings ? array_merge($this->getSettings(), $customSettings) : $this->getSettings();

        return View::make('emails.test-email', [
            'emailLayout' => $layout,
            'previewMode' => true,
        ])->render();
    }

    /**
     * Send a test email to the specified recipient.
     */
    public function sendTestEmail(string $recipientEmail): void
    {
        $layout = $this->getSettings();
        Mail::to($recipientEmail)->send(new TestEmailLayout($layout));
    }
}
