@extends('emails.layouts.master')

@section('title', !empty($emailTemplate['subject']) ? $emailTemplate['subject'] : ('ECESPRO Grant Release Schedule - ' . ($batch->batch_name ?? '')))

@section('content')
    <h2 style="color: {{ $emailLayout['heading_color'] ?? '#0f172a' }}; margin: 0 0 12px 0; font-size: 20px; font-weight: 700; text-align: center;">
        {{ !empty($emailTemplate['heading']) ? $emailTemplate['heading'] : 'ECESPRO Grant Release Schedule' }}
    </h2>
    
    <p style="color: {{ $emailLayout['text_color'] ?? '#475569' }}; margin: 0 0 24px 0; font-size: 15px; line-height: 1.6; text-align: center; white-space: pre-line;">
        {{ !empty($emailTemplate['body']) ? $emailTemplate['body'] : ('Hello ' . ($userName ?? 'Scholar') . ', you have been scheduled for the ECESPRO Grant Release. Please review your distribution details below.') }}
    </p>

    <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; margin: 0 0 24px 0;">
        <tr>
            <td style="padding: 22px 24px; text-align: left;">
                <p style="color: {{ $emailLayout['secondary_color'] ?? '#0b6b3a' }}; margin: 0 0 12px 0; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">
                    Release Schedule Details
                </p>
                
                <div style="margin-bottom: 12px;">
                    <span style="color: #64748b; font-size: 12px; font-weight: 500; display: block; margin-bottom: 2px;">Batch Name</span>
                    <span style="color: {{ $emailLayout['heading_color'] ?? '#0f172a' }}; font-size: 16px; font-weight: 700;">{{ $batch->batch_name ?? 'Grant Release Batch' }}</span>
                </div>

                <div style="margin-bottom: 12px;">
                    <span style="color: #64748b; font-size: 12px; font-weight: 500; display: block; margin-bottom: 2px;">Release Date</span>
                    <span style="color: #be123c; font-size: 15px; font-weight: 700;">📅 {{ $formattedDate }}</span>
                </div>

                @if(!empty($batch->time))
                <div style="margin-bottom: 12px;">
                    <span style="color: #64748b; font-size: 12px; font-weight: 500; display: block; margin-bottom: 2px;">Scheduled Time</span>
                    <span style="color: #334155; font-size: 14px; font-weight: 600;">⏰ {{ $batch->time }}</span>
                </div>
                @endif

                @if(!empty($batch->venue))
                <div style="margin-bottom: 12px;">
                    <span style="color: #64748b; font-size: 12px; font-weight: 500; display: block; margin-bottom: 2px;">Venue / Distribution Location</span>
                    <span style="color: #334155; font-size: 14px; font-weight: 600;">📍 {{ $batch->venue }}</span>
                </div>
                @endif

                <div>
                    <span style="color: #64748b; font-size: 12px; font-weight: 500; display: block; margin-bottom: 4px;">Status</span>
                    <span style="display: inline-block; background-color: #dcfce7; color: #15803d; font-size: 12px; font-weight: 700; padding: 4px 10px; border-radius: 9999px;">
                        Scheduled
                    </span>
                </div>
            </td>
        </tr>
    </table>

    <!-- Security / ID Notice -->
    @if(!empty($emailTemplate['security_notice']))
    <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color: #fffbeb; border-left: 4px solid #f59e0b; border-radius: 4px; margin: 0 0 24px 0;">
        <tr>
            <td style="padding: 14px 18px;">
                <p style="color: #92400e; margin: 0; font-size: 13px; line-height: 1.5; text-align: left;">
                    <strong>Requirement:</strong> {{ $emailTemplate['security_notice'] }}
                </p>
            </td>
        </tr>
    </table>
    @endif

    <div style="text-align: center; margin: 0 0 16px 0;">
        <a href="{{ $portalUrl }}" target="_blank" style="display: inline-block; background: linear-gradient(135deg, {{ $emailLayout['primary_color'] ?? '#07823f' }} 0%, {{ $emailLayout['secondary_color'] ?? '#0b6b3a' }} 100%); color: {{ $emailLayout['button_text_color'] ?? '#ffffff' }}; text-decoration: none; padding: 14px 34px; font-size: 15px; font-weight: 600; border-radius: 8px; box-shadow: 0 4px 12px rgba(7, 130, 63, 0.3);">
            {{ !empty($emailTemplate['button_text']) ? $emailTemplate['button_text'] : 'View in Scholarship Portal →' }}
        </a>
    </div>

    @if(!empty($emailTemplate['footnote']))
    <div style="margin-top: 24px; padding-top: 16px; border-top: 1px dashed #e2e8f0; text-align: center;">
        <p style="color: #64748b; font-size: 13px; line-height: 1.5; margin: 0;">
            {{ $emailTemplate['footnote'] }}
        </p>
    </div>
    @endif
@endsection
