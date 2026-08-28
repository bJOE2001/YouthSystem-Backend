@extends('emails.layouts.master')

@section('title', $emailTemplate['subject'] ?? 'Facility Booking Confirmed')

@section('content')
    <h2 style="color: {{ $emailLayout['heading_color'] ?? '#0f172a' }}; margin: 0 0 12px 0; font-size: 20px; font-weight: 700; text-align: center;">
        {{ $emailTemplate['heading'] ?? 'Facility Booking Confirmed!' }}
    </h2>
    
    <p style="color: {{ $emailLayout['text_color'] ?? '#475569' }}; margin: 0 0 24px 0; font-size: 15px; line-height: 1.6; text-align: center; white-space: pre-line;">
        {{ $emailTemplate['body'] ?? ('Hello ' . $user->name . ', your reservation for ' . ($facility->name ?? 'the requested facility') . ' has been directly booked and confirmed.') }}
    </p>

    <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; margin: 0 0 28px 0;">
        <tr>
            <td style="padding: 22px 24px; text-align: left;">
                <p style="color: {{ $emailLayout['secondary_color'] ?? '#0b6b3a' }}; margin: 0 0 12px 0; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">
                    Booking Summary
                </p>
                
                <div style="margin-bottom: 12px;">
                    <span style="color: #64748b; font-size: 12px; font-weight: 500; display: block; margin-bottom: 2px;">Facility</span>
                    <span style="color: {{ $emailLayout['heading_color'] ?? '#0f172a' }}; font-size: 16px; font-weight: 700;">{{ $facility->name ?? 'TCYDO Facility' }}</span>
                </div>

                <div style="margin-bottom: 12px;">
                    <span style="color: #64748b; font-size: 12px; font-weight: 500; display: block; margin-bottom: 2px;">Date</span>
                    <span style="color: #334155; font-size: 14px; font-weight: 600;">📅 {{ $booking->date ?? 'N/A' }}</span>
                </div>

                <div style="margin-bottom: 12px;">
                    <span style="color: #64748b; font-size: 12px; font-weight: 500; display: block; margin-bottom: 2px;">Time Schedule</span>
                    <span style="color: #334155; font-size: 14px; font-weight: 600;">⏰ {{ $booking->start_time ?? '' }} - {{ $booking->end_time ?? '' }}</span>
                </div>

                @if(!empty($booking->purpose))
                <div style="margin-bottom: 12px;">
                    <span style="color: #64748b; font-size: 12px; font-weight: 500; display: block; margin-bottom: 2px;">Purpose</span>
                    <span style="color: #334155; font-size: 14px;">{{ $booking->purpose }}</span>
                </div>
                @endif

                <div>
                    <span style="color: #64748b; font-size: 12px; font-weight: 500; display: block; margin-bottom: 4px;">Status</span>
                    <span style="display: inline-block; background-color: #dcfce7; color: #166534; font-size: 12px; font-weight: 700; padding: 4px 10px; border-radius: 9999px;">
                        Booked & Confirmed
                    </span>
                </div>
            </td>
        </tr>
    </table>

    <div style="text-align: center; margin: 0 0 12px 0;">
        <a href="{{ $portalUrl }}" target="_blank" style="display: inline-block; background: linear-gradient(135deg, {{ $emailLayout['primary_color'] ?? '#07823f' }} 0%, {{ $emailLayout['secondary_color'] ?? '#0b6b3a' }} 100%); color: {{ $emailLayout['button_text_color'] ?? '#ffffff' }}; text-decoration: none; padding: 14px 34px; font-size: 15px; font-weight: 600; border-radius: 8px; box-shadow: 0 4px 12px rgba(7, 130, 63, 0.3);">
            {{ $emailTemplate['button_text'] ?? 'View in My Bookings →' }}
        </a>
    </div>
@endsection
