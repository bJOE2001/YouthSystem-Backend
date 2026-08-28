@extends('emails.layouts.master')

@section('title', $emailTemplate['subject'] ?? 'Compliance Schedule Notice')

@section('content')
    <h2 style="color: {{ $emailLayout['heading_color'] ?? '#0f172a' }}; margin: 0 0 12px 0; font-size: 20px; font-weight: 700; text-align: center;">
        {{ $emailTemplate['heading'] ?? 'New Compliance Submission Schedule' }}
    </h2>
    
    <p style="color: {{ $emailLayout['text_color'] ?? '#475569' }}; margin: 0 0 24px 0; font-size: 15px; line-height: 1.6; text-align: center; white-space: pre-line;">
        {{ $emailTemplate['body'] ?? ('Hello ' . $user->name . ', a new compliance schedule has been posted for the ' . $schedule->school_year . ' (' . $schedule->semester . ') term. Please review the submission requirements and deadline below.') }}
    </p>

    <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; margin: 0 0 28px 0;">
        <tr>
            <td style="padding: 22px 24px; text-align: left;">
                <p style="color: {{ $emailLayout['secondary_color'] ?? '#0b6b3a' }}; margin: 0 0 12px 0; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">
                    Schedule Details
                </p>
                
                <div style="margin-bottom: 12px;">
                    <span style="color: #64748b; font-size: 12px; font-weight: 500; display: block; margin-bottom: 2px;">Title</span>
                    <span style="color: {{ $emailLayout['heading_color'] ?? '#0f172a' }}; font-size: 16px; font-weight: 700;">{{ $schedule->title }}</span>
                </div>

                <div style="margin-bottom: 12px;">
                    <span style="color: #64748b; font-size: 12px; font-weight: 500; display: block; margin-bottom: 2px;">Academic Term</span>
                    <span style="color: #334155; font-size: 14px; font-weight: 600;">{{ $schedule->school_year }} &bull; {{ $schedule->semester }}</span>
                </div>

                <div style="margin-bottom: 12px;">
                    <span style="color: #64748b; font-size: 12px; font-weight: 500; display: block; margin-bottom: 2px;">Submission Period</span>
                    <span style="color: #be123c; font-size: 14px; font-weight: 700;">
                        📅 {{ $schedule->start_date ? $schedule->start_date->format('M d, Y') : 'Start Date' }} - {{ $schedule->end_date ? $schedule->end_date->format('M d, Y') : 'Deadline' }}
                    </span>
                </div>

                @if(!empty($schedule->instructions))
                <div>
                    <span style="color: #64748b; font-size: 12px; font-weight: 500; display: block; margin-bottom: 2px;">Instructions</span>
                    <span style="color: #475569; font-size: 13px; line-height: 1.5;">{{ $schedule->instructions }}</span>
                </div>
                @endif
            </td>
        </tr>
    </table>

    <div style="text-align: center; margin: 0 0 12px 0;">
        <a href="{{ $requirementsUrl }}" target="_blank" style="display: inline-block; background: linear-gradient(135deg, {{ $emailLayout['primary_color'] ?? '#07823f' }} 0%, {{ $emailLayout['secondary_color'] ?? '#0b6b3a' }} 100%); color: {{ $emailLayout['button_text_color'] ?? '#ffffff' }}; text-decoration: none; padding: 14px 34px; font-size: 15px; font-weight: 600; border-radius: 8px; box-shadow: 0 4px 12px rgba(7, 130, 63, 0.3);">
            {{ $emailTemplate['button_text'] ?? 'Submit Compliance Documents →' }}
        </a>
    </div>
@endsection
