@extends('emails.layouts.master')

@section('title', $emailTemplate['subject'] ?? 'We Miss You! | Tagum City Youth Portal')

@section('content')
    <h2 style="color: {{ $emailLayout['heading_color'] ?? '#0f172a' }}; margin: 0 0 14px 0; font-size: 20px; font-weight: 700; text-align: center;">
        {{ $emailTemplate['heading'] ?? ('We Miss You, ' . $user->name . '!') }}
    </h2>
    
    <p style="color: {{ $emailLayout['text_color'] ?? '#334155' }}; margin: 0 0 24px 0; font-size: 15px; line-height: 1.6; text-align: center; white-space: pre-line;">
        {{ $emailTemplate['body'] ?? "It has been a while since you last visited the Tagum City Youth Development Portal.\n\nNew youth programs, sports tournaments, workshops, and scholarship opportunities are happening right now. Don't miss out on community activities tailored for you!" }}
    </p>

    <!-- Highlights Card -->
    <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; margin: 0 0 26px 0;">
        <tr>
            <td style="padding: 22px 24px; text-align: left;">
                <p style="color: {{ $emailLayout['secondary_color'] ?? '#0b6b3a' }}; margin: 0 0 14px 0; font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">
                    What's Waiting For You
                </p>
                
                <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0">
                    <tr>
                        <td style="padding: 6px 0; font-size: 14px; color: #334155; line-height: 1.5;">
                            🎓 <strong>ECESPRO Scholarship:</strong> Check application statuses, exam schedules, and document compliance.
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 6px 0; font-size: 14px; color: #334155; line-height: 1.5;">
                            🏆 <strong>Sports & Events:</strong> Register for youth leagues, seminars, and community empowerment activities.
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 6px 0; font-size: 14px; color: #334155; line-height: 1.5;">
                            🏢 <strong>Facility Booking:</strong> Reserve slots at the Tagum City Youth Gymnasium and facilities.
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 6px 0; font-size: 14px; color: #334155; line-height: 1.5;">
                            📜 <strong>Digital Certificates:</strong> Access and download verified certificates for attended programs.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- CTA Button -->
    <div style="text-align: center; margin: 0 0 24px 0;">
        <a href="{{ $loginUrl }}" target="_blank" style="display: inline-block; background: linear-gradient(135deg, {{ $emailLayout['primary_color'] ?? '#07823f' }} 0%, {{ $emailLayout['secondary_color'] ?? '#0b6b3a' }} 100%); color: {{ $emailLayout['button_text_color'] ?? '#ffffff' }}; text-decoration: none; padding: 14px 34px; font-size: 15px; font-weight: 600; border-radius: 8px; box-shadow: 0 4px 12px rgba(7, 130, 63, 0.3);">
            {{ $emailTemplate['button_text'] ?? 'Log In & Explore →' }}
        </a>
    </div>

    <!-- Footnote / Password Assistance -->
    @if(!empty($emailTemplate['footnote']))
    <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color: #f1f5f9; border-radius: 8px; margin: 0 0 8px 0;">
        <tr>
            <td style="padding: 12px 18px; text-align: center;">
                <p style="color: #64748b; margin: 0; font-size: 12px; line-height: 1.5;">
                    💡 {{ $emailTemplate['footnote'] }}
                </p>
            </td>
        </tr>
    </table>
    @endif
@endsection
