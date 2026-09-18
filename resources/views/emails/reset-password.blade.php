@extends('emails.layouts.master')

@section('title', !empty($emailTemplate['subject']) ? $emailTemplate['subject'] : 'Reset Your Password - Tagum City Youth Development Office')

@section('content')
    <h2 style="color: {{ $emailLayout['heading_color'] ?? '#0f172a' }}; margin: 0 0 16px 0; font-size: 20px; font-weight: 700; letter-spacing: -0.3px;">
        {{ !empty($emailTemplate['heading']) ? $emailTemplate['heading'] : ('Hello ' . ($user->name ?? 'User') . ',') }}
    </h2>
    
    <p style="color: {{ $emailLayout['text_color'] ?? '#334155' }}; margin: 0 0 24px 0; font-size: 15px; line-height: 1.65; white-space: pre-line;">
        {{ !empty($emailTemplate['body']) ? $emailTemplate['body'] : "We received a request to reset the password for your account.\n\nClick the button below to choose a new password. For security reasons, this link will safely expire in " . ($expireMinutes ?? 60) . " minutes." }}
    </p>

    <!-- Reset Details Box -->
    <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; margin: 0 0 26px 0;">
        <tr>
            <td style="padding: 18px 22px; text-align: left;">
                <p style="color: #475569; margin: 0 0 12px 0; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.6px;">
                    Password Reset Request Details
                </p>
                <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="font-size: 13px;">
                    <tr>
                        <td style="padding: 4px 0; color: #64748b; width: 140px;">Account Email:</td>
                        <td style="padding: 4px 0; color: {{ $emailLayout['heading_color'] ?? '#0f172a' }}; font-weight: 600;">
                            {{ $user->email ?? '' }}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 4px 0; color: #64748b;">Link Validity:</td>
                        <td style="padding: 4px 0; color: {{ $emailLayout['secondary_color'] ?? '#0b6b3a' }}; font-weight: 700;">
                            {{ $expireMinutes ?? 60 }} Minutes
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- CTA Button -->
    <div style="text-align: center; margin: 0 0 26px 0;">
        <a href="{{ $resetUrl }}" target="_blank" style="display: inline-block; background: linear-gradient(135deg, {{ $emailLayout['primary_color'] ?? '#07823f' }} 0%, {{ $emailLayout['secondary_color'] ?? '#0b6b3a' }} 100%); color: {{ $emailLayout['button_text_color'] ?? '#ffffff' }}; text-decoration: none; padding: 14px 36px; font-size: 15px; font-weight: 600; border-radius: 8px; box-shadow: 0 4px 14px rgba(7, 130, 63, 0.25); letter-spacing: 0.2px;">
            {{ !empty($emailTemplate['button_text']) ? $emailTemplate['button_text'] : 'Reset Password →' }}
        </a>
    </div>

    <!-- Security Warning Card -->
    @if(!empty($emailTemplate['security_notice']))
    <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color: #fffbeb; border-left: 4px solid #f59e0b; border-radius: 4px; margin: 0 0 20px 0;">
        <tr>
            <td style="padding: 14px 18px;">
                <p style="color: #92400e; margin: 0; font-size: 13px; line-height: 1.5; text-align: left;">
                    <strong>Security Reminder:</strong> {{ $emailTemplate['security_notice'] }}
                </p>
            </td>
        </tr>
    </table>
    @endif

    <!-- Fallback link -->
    <div style="margin-top: 24px; padding-top: 20px; border-top: 1px solid #e2e8f0;">
        <p style="color: #64748b; font-size: 12px; margin: 0 0 6px 0; line-height: 1.5;">
            If you're having trouble clicking the "Reset Password" button, copy and paste the URL below into your web browser:
        </p>
        <p style="margin: 0; word-break: break-all; font-size: 12px; line-height: 1.4;">
            <a href="{{ $resetUrl }}" style="color: {{ $emailLayout['primary_color'] ?? '#07823f' }}; text-decoration: underline;">{{ $resetUrl }}</a>
        </p>
    </div>
@endsection
