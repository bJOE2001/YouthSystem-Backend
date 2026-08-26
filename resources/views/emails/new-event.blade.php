@extends('emails.layouts.master')

@section('title', $emailTemplate['subject'] ?? 'New Event Announcement')

@section('content')
    <h2 style="color: {{ $emailLayout['heading_color'] ?? '#0f172a' }}; margin: 0 0 12px 0; font-size: 20px; font-weight: 700; text-align: center;">
        {{ $emailTemplate['heading'] ?? 'New Youth Event Posted!' }}
    </h2>
    
    <p style="color: {{ $emailLayout['text_color'] ?? '#475569' }}; margin: 0 0 24px 0; font-size: 15px; line-height: 1.6; text-align: center; white-space: pre-line;">
        {{ $emailTemplate['body'] ?? ('Hello ' . $user->name . ', a new youth activity has just been scheduled. Join in, gain new skills, and connect with fellow youth leaders!') }}
    </p>

    <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; margin: 0 0 28px 0;">
        <tr>
            <td style="padding: 22px 24px; text-align: left;">
                <p style="color: {{ $emailLayout['secondary_color'] ?? '#0b6b3a' }}; margin: 0 0 12px 0; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">
                    Event Overview
                </p>
                
                <div style="margin-bottom: 12px;">
                    <span style="color: #64748b; font-size: 12px; font-weight: 500; display: block; margin-bottom: 2px;">Event Title</span>
                    <span style="color: {{ $emailLayout['heading_color'] ?? '#0f172a' }}; font-size: 17px; font-weight: 700;">{{ $event->name }}</span>
                </div>

                @if(!empty($event->ppa_classification))
                <div style="margin-bottom: 12px;">
                    <span style="color: #64748b; font-size: 12px; font-weight: 500; display: block; margin-bottom: 2px;">Classification</span>
                    <span style="color: #334155; font-size: 14px; font-weight: 600;">{{ $event->ppa_classification }}</span>
                </div>
                @endif

                @if(!empty($event->location))
                <div style="margin-bottom: 12px;">
                    <span style="color: #64748b; font-size: 12px; font-weight: 500; display: block; margin-bottom: 2px;">Location</span>
                    <span style="color: #334155; font-size: 14px;">📍 {{ $event->location }}</span>
                </div>
                @endif

                <div style="margin-bottom: 12px;">
                    <span style="color: #64748b; font-size: 12px; font-weight: 500; display: block; margin-bottom: 2px;">Date & Schedule</span>
                    <span style="color: #334155; font-size: 14px; font-weight: 600;">
                        📅 {{ $event->start_date ? $event->start_date->format('M d, Y') : 'TBA' }}
                        @if($event->start_time)
                            ({{ $event->start_time }} - {{ $event->end_time }})
                        @endif
                    </span>
                </div>

                @if(!empty($event->primary_objective_1))
                <div>
                    <span style="color: #64748b; font-size: 12px; font-weight: 500; display: block; margin-bottom: 2px;">Primary Objective</span>
                    <span style="color: #475569; font-size: 13px; line-height: 1.5;">{{ $event->primary_objective_1 }}</span>
                </div>
                @endif
            </td>
        </tr>
    </table>

    <div style="text-align: center; margin: 0 0 12px 0;">
        <a href="{{ $eventUrl }}" target="_blank" style="display: inline-block; background: linear-gradient(135deg, {{ $emailLayout['primary_color'] ?? '#07823f' }} 0%, {{ $emailLayout['secondary_color'] ?? '#0b6b3a' }} 100%); color: {{ $emailLayout['button_text_color'] ?? '#ffffff' }}; text-decoration: none; padding: 14px 34px; font-size: 15px; font-weight: 600; border-radius: 8px; box-shadow: 0 4px 12px rgba(7, 130, 63, 0.3);">
            {{ $emailTemplate['button_text'] ?? 'Join This Event →' }}
        </a>
    </div>
@endsection
