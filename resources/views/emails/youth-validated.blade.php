@extends('emails.layouts.master')

@section('title', $emailTemplate['subject'] ?? 'Youth Account Validated')

@section('content')
    <h2 style="color: {{ $emailLayout['heading_color'] ?? '#0f172a' }}; margin: 0 0 14px 0; font-size: 19px; font-weight: 600;">
        {{ $emailTemplate['heading'] ?? ('Hello ' . $user->name . ',') }}
    </h2>
    
    <p style="color: {{ $emailLayout['text_color'] ?? '#334155' }}; margin: 0 0 26px 0; font-size: 15px; line-height: 1.6; white-space: pre-line;">
        {{ $emailTemplate['body'] ?? 'Great news! Your youth registration profile has been officially reviewed, validated, and approved by your Sangguniang Kabataan (SK) Official.' }}
    </p>

    <!-- Credentials Box -->
    <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; margin: 0 0 28px 0;">
        <tr>
            <td style="padding: 24px; text-align: left;">
                <p style="color: #475569; margin: 0 0 16px 0; font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">
                    Your Login Account Credentials
                </p>
                
                <!-- Registered Email -->
                <div style="margin-bottom: 16px;">
                    <span style="color: #64748b; font-size: 12px; font-weight: 500; display: block; margin-bottom: 4px;">Email Address</span>
                    <span style="color: {{ $emailLayout['heading_color'] ?? '#0f172a' }}; font-size: 15px; font-weight: 600;">{{ $user->email }}</span>
                </div>

                <!-- Initial Password -->
                <div>
                    <span style="color: #64748b; font-size: 12px; font-weight: 500; display: block; margin-bottom: 6px;">Initial Password (Birthdate: MMDDYY)</span>
                    <span style="display: inline-block; background-color: #e8f5ee; color: {{ $emailLayout['secondary_color'] ?? '#0b6b3a' }}; font-family: 'Courier New', Courier, monospace; font-size: 17px; font-weight: 700; letter-spacing: 2px; padding: 7px 18px; border-radius: 6px; border: 1px solid #b7e4c7;">
                        {{ $plainPassword }}
                    </span>
                </div>
            </td>
        </tr>
    </table>

    <!-- CTA Button -->
    <div style="text-align: center; margin: 0 0 24px 0;">
        <a href="{{ $loginUrl }}" target="_blank" style="display: inline-block; background: linear-gradient(135deg, {{ $emailLayout['primary_color'] ?? '#07823f' }} 0%, {{ $emailLayout['secondary_color'] ?? '#0b6b3a' }} 100%); color: {{ $emailLayout['button_text_color'] ?? '#ffffff' }}; text-decoration: none; padding: 14px 34px; font-size: 15px; font-weight: 600; border-radius: 8px; box-shadow: 0 4px 12px rgba(7, 130, 63, 0.3);">
            {{ $emailTemplate['button_text'] ?? 'Log In to Your Account →' }}
        </a>
    </div>

    <!-- Security Warning Card -->
    @if(!empty($emailTemplate['security_notice']))
    <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color: #fffbeb; border-left: 4px solid #f59e0b; border-radius: 4px; margin: 0 0 10px 0;">
        <tr>
            <td style="padding: 14px 18px;">
                <p style="color: #92400e; margin: 0; font-size: 13px; line-height: 1.5; text-align: left;">
                    <strong>Security Reminder:</strong> {{ $emailTemplate['security_notice'] }}
                </p>
            </td>
        </tr>
    </table>
    @endif
@endsection
