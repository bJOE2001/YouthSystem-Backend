@extends('emails.layouts.master')

@section('title', $emailTemplate['subject'] ?? 'Certificate of Participation')

@section('content')
    <h2 style="color: {{ $emailLayout['heading_color'] ?? '#0f172a' }}; margin: 0 0 12px 0; font-size: 20px; font-weight: 700; text-align: center;">
        {{ $emailTemplate['heading'] ?? 'Certificate of Participation' }}
    </h2>
    
    <p style="color: {{ $emailLayout['text_color'] ?? '#475569' }}; margin: 0 0 24px 0; font-size: 15px; line-height: 1.6; text-align: center; white-space: pre-line;">
        {{ $emailTemplate['body'] ?? ('Dear ' . $user->name . ', thank you for actively participating in our community development programs!') }}
    </p>

    <!-- Activity Summary Box -->
    <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; margin: 0 0 28px 0;">
        <tr>
            <td style="padding: 22px 24px; text-align: left;">
                <p style="color: {{ $emailLayout['secondary_color'] ?? '#0b6b3a' }}; margin: 0 0 12px 0; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">
                    Activity Details
                </p>
                
                <!-- Activity Name -->
                <div style="margin-bottom: 12px;">
                    <span style="color: #64748b; font-size: 12px; font-weight: 500; display: block; margin-bottom: 2px;">{{ $isEvent ? 'Event Name' : 'Sports Program' }}</span>
                    <span style="color: {{ $emailLayout['heading_color'] ?? '#0f172a' }}; font-size: 16px; font-weight: 700;">{{ $activity->name }}</span>
                </div>

                <!-- Classification / Type -->
                <div style="margin-bottom: 12px;">
                    <span style="color: #64748b; font-size: 12px; font-weight: 500; display: block; margin-bottom: 2px;">Category</span>
                    <span style="color: #334155; font-size: 14px; font-weight: 600;">{{ $isEvent ? ($activity->ppa_classification ?? 'Youth Event') : ($activity->type ?? 'Sports Program') }}</span>
                </div>

                <!-- Status Badge -->
                <div>
                    <span style="color: #64748b; font-size: 12px; font-weight: 500; display: block; margin-bottom: 4px;">Status</span>
                    <span style="display: inline-block; background-color: #dcfce7; color: #166534; font-size: 12px; font-weight: 700; padding: 4px 10px; border-radius: 9999px;">
                        Completed & Attended
                    </span>
                </div>
            </td>
        </tr>
    </table>

    <!-- Attachment Callout -->
    @if(!empty($emailTemplate['attachment_notice']))
    <div style="background-color: #eff6ff; border: 1px solid #bfdbfe; border-radius: 10px; padding: 16px 20px; margin-bottom: 28px;">
        <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0">
            <tr>
                <td width="36" valign="top">
                    <span style="font-size: 24px; line-height: 1;">📎</span>
                </td>
                <td style="color: #1e40af; font-size: 14px; line-height: 1.5;">
                    <strong>PDF Certificate Attached:</strong> {{ $emailTemplate['attachment_notice'] }}
                </td>
            </tr>
        </table>
    </div>
    @endif

    <!-- CTA Button -->
    <div style="text-align: center; margin: 0 0 12px 0;">
        <a href="{{ $activitiesUrl }}" target="_blank" style="display: inline-block; background: linear-gradient(135deg, {{ $emailLayout['primary_color'] ?? '#07823f' }} 0%, {{ $emailLayout['secondary_color'] ?? '#0b6b3a' }} 100%); color: {{ $emailLayout['button_text_color'] ?? '#ffffff' }}; text-decoration: none; padding: 14px 34px; font-size: 15px; font-weight: 600; border-radius: 8px; box-shadow: 0 4px 12px rgba(7, 130, 63, 0.3);">
            {{ $emailTemplate['button_text'] ?? 'View in My Activities →' }}
        </a>
    </div>
@endsection
