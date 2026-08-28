@extends('emails.layouts.master')

@section('title', 'Email Layout Test Preview')

@section('content')
    <h2 style="color: {{ $emailLayout['heading_color'] ?? '#0f172a' }}; margin: 0 0 12px 0; font-size: 20px; font-weight: 700; text-align: center;">
        Email Layout Test & Preview
    </h2>

    <p style="color: {{ $emailLayout['text_color'] ?? '#334155' }}; margin: 0 0 22px 0; font-size: 15px; line-height: 1.6; text-align: center;">
        This is a live preview test email showing how your configured branding, colors, typography, and footer layout will appear across all system notifications.
    </p>

    <!-- Sample Summary Box -->
    <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; margin: 0 0 24px 0;">
        <tr>
            <td style="padding: 22px 24px; text-align: left;">
                <p style="color: {{ $emailLayout['secondary_color'] ?? '#0b6b3a' }}; margin: 0 0 12px 0; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">
                    ✨ Sample Configuration Summary
                </p>

                <div style="margin-bottom: 10px;">
                    <span style="color: #64748b; font-size: 12px; font-weight: 500; display: block; margin-bottom: 2px;">Header Title</span>
                    <span style="color: {{ $emailLayout['heading_color'] ?? '#0f172a' }}; font-size: 15px; font-weight: 600;">{{ $emailLayout['header_title'] ?? 'Tagum City Youth Development Office' }}</span>
                </div>

                <div style="margin-bottom: 10px;">
                    <span style="color: #64748b; font-size: 12px; font-weight: 500; display: block; margin-bottom: 2px;">Primary Brand Color</span>
                    <span style="display: inline-flex; align-items: center; gap: 8px;">
                        <span style="display: inline-block; width: 14px; height: 14px; background-color: {{ $emailLayout['primary_color'] ?? '#07823f' }}; border-radius: 3px; border: 1px solid #cbd5e1; vertical-align: middle;"></span>
                        <code style="color: #334155; font-size: 13px;">{{ $emailLayout['primary_color'] ?? '#07823f' }}</code>
                    </span>
                </div>

                <div>
                    <span style="color: #64748b; font-size: 12px; font-weight: 500; display: block; margin-bottom: 2px;">Secondary Accent Color</span>
                    <span style="display: inline-flex; align-items: center; gap: 8px;">
                        <span style="display: inline-block; width: 14px; height: 14px; background-color: {{ $emailLayout['secondary_color'] ?? '#0b6b3a' }}; border-radius: 3px; border: 1px solid #cbd5e1; vertical-align: middle;"></span>
                        <code style="color: #334155; font-size: 13px;">{{ $emailLayout['secondary_color'] ?? '#0b6b3a' }}</code>
                    </span>
                </div>
            </td>
        </tr>
    </table>

    <!-- Sample Call To Action Button -->
    <div style="text-align: center; margin: 0 0 24px 0;">
        <a href="{{ config('app.frontend_url', config('app.url', 'http://localhost')) }}" target="_blank" style="display: inline-block; background: linear-gradient(135deg, {{ $emailLayout['primary_color'] ?? '#07823f' }} 0%, {{ $emailLayout['secondary_color'] ?? '#0b6b3a' }} 100%); color: {{ $emailLayout['button_text_color'] ?? '#ffffff' }}; text-decoration: none; padding: 14px 34px; font-size: 15px; font-weight: 600; border-radius: 8px; box-shadow: 0 4px 12px rgba(7, 130, 63, 0.25);">
            Sample Action Button &rarr;
        </a>
    </div>

    <!-- Security & Status Alert Box -->
    <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color: #ecfdf5; border-left: 4px solid {{ $emailLayout['primary_color'] ?? '#07823f' }}; border-radius: 4px; margin: 0;">
        <tr>
            <td style="padding: 14px 18px;">
                <p style="color: #065f46; margin: 0; font-size: 13px; line-height: 1.5; text-align: left;">
                    <strong>System Note:</strong> All system notification emails (scholarships, registrations, activities, and bookings) will dynamically utilize this layout.
                </p>
            </td>
        </tr>
    </table>
@endsection
