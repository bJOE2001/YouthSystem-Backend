@extends('emails.layouts.master')

@section('title', $emailTemplate['subject'] ?? 'TCYDO Announcement')

@section('content')
    <h2 style="color: {{ $emailLayout['heading_color'] ?? '#0f172a' }}; margin: 0 0 12px 0; font-size: 20px; font-weight: 700; text-align: center;">
        {{ $emailTemplate['heading'] ?? 'Official Announcement' }}
    </h2>
    
    <p style="color: {{ $emailLayout['text_color'] ?? '#475569' }}; margin: 0 0 24px 0; font-size: 15px; line-height: 1.6; text-align: center; white-space: pre-line;">
        {{ $emailTemplate['body'] ?? ('Hello ' . $user->name . ', a new official announcement has been published by TCYDO.') }}
    </p>

    <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; margin: 0 0 28px 0;">
        <tr>
            <td style="padding: 22px 24px; text-align: left;">
                <p style="color: {{ $emailLayout['secondary_color'] ?? '#0b6b3a' }}; margin: 0 0 12px 0; font-size: 14px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">
                    {{ $announcement->title }}
                </p>
                
                <div style="color: #334155; font-size: 14px; line-height: 1.6; white-space: pre-line;">
                    {{ $announcement->description }}
                </div>

                <div style="margin-top: 14px; font-size: 12px; color: #64748b;">
                    Posted on {{ $announcement->created_at ? $announcement->created_at->format('F d, Y') : 'Recently' }}
                </div>
            </td>
        </tr>
    </table>

    <div style="text-align: center; margin: 0 0 12px 0;">
        <a href="{{ $announcementUrl }}" target="_blank" style="display: inline-block; background: linear-gradient(135deg, {{ $emailLayout['primary_color'] ?? '#07823f' }} 0%, {{ $emailLayout['secondary_color'] ?? '#0b6b3a' }} 100%); color: {{ $emailLayout['button_text_color'] ?? '#ffffff' }}; text-decoration: none; padding: 14px 34px; font-size: 15px; font-weight: 600; border-radius: 8px; box-shadow: 0 4px 12px rgba(7, 130, 63, 0.3);">
            {{ $emailTemplate['button_text'] ?? 'View on Portal →' }}
        </a>
    </div>
@endsection
