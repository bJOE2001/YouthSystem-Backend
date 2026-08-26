@extends('emails.layouts.master')

@section('title', $emailTemplate['subject'] ?? ($subjectTitle ?? 'ECESPRO Scholarship Update'))

@section('content')
    <!-- Greeting -->
    <h2 style="color: {{ $emailLayout['heading_color'] ?? '#0f172a' }}; margin: 0 0 14px 0; font-size: 19px; font-weight: 600;">
        {{ $emailTemplate['heading'] ?? ('Hello ' . $recipientName . ',') }}
    </h2>
    
    <!-- Status Headline Message -->
    <p style="color: {{ $emailLayout['text_color'] ?? '#334155' }}; margin: 0 0 24px 0; font-size: 15px; line-height: 1.6; white-space: pre-line;">
        {{ $statusMessage }}
    </p>

    <!-- ========================================== -->
    <!-- DYNAMIC DETAILS BOX ACCORDING TO STAGE     -->
    <!-- ========================================== -->

    @if(in_array($status, ['Exam Scheduled']) && !empty($metadata))
        <!-- Exam Schedule Card -->
        <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; margin: 0 0 26px 0;">
            <tr>
                <td style="padding: 22px; text-align: left;">
                    <p style="color: {{ $emailLayout['secondary_color'] ?? '#0b6b3a' }}; margin: 0 0 14px 0; font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">
                        📝 Qualifying Examination Schedule
                    </p>
                    
                    <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0">
                        @if(!empty($metadata['batch_name']))
                        <tr>
                            <td style="padding: 5px 0; color: #64748b; font-size: 13px; width: 110px;">Batch:</td>
                            <td style="padding: 5px 0; color: {{ $emailLayout['heading_color'] ?? '#0f172a' }}; font-size: 14px; font-weight: 600;">{{ $metadata['batch_name'] }}</td>
                        </tr>
                        @endif
                        @if(!empty($metadata['exam_date']))
                        <tr>
                            <td style="padding: 5px 0; color: #64748b; font-size: 13px;">Date:</td>
                            <td style="padding: 5px 0; color: {{ $emailLayout['heading_color'] ?? '#0f172a' }}; font-size: 14px; font-weight: 600;">{{ \Carbon\Carbon::parse($metadata['exam_date'])->format('F d, Y (l)') }}</td>
                        </tr>
                        @endif
                        @if(!empty($metadata['time']))
                        <tr>
                            <td style="padding: 5px 0; color: #64748b; font-size: 13px;">Time:</td>
                            <td style="padding: 5px 0; color: {{ $emailLayout['heading_color'] ?? '#0f172a' }}; font-size: 14px; font-weight: 600;">{{ $metadata['time'] }}</td>
                        </tr>
                        @endif
                        @if(!empty($metadata['venue']))
                        <tr>
                            <td style="padding: 5px 0; color: #64748b; font-size: 13px;">Venue:</td>
                            <td style="padding: 5px 0; color: {{ $emailLayout['heading_color'] ?? '#0f172a' }}; font-size: 14px; font-weight: 600;">{{ $metadata['venue'] }}</td>
                        </tr>
                        @endif
                    </table>

                    <div style="margin-top: 14px; padding-top: 12px; border-top: 1px dashed #cbd5e1; color: #475569; font-size: 12px; line-height: 1.5;">
                        <strong>Reminders:</strong> Please bring a valid ID, scientific calculator (if applicable), and black ballpen. Arrive at least 30 minutes prior to your scheduled examination time.
                    </div>
                </td>
            </tr>
        </table>

    @elseif(in_array($status, ['Interview Scheduled']) && !empty($metadata))
        <!-- Interview Schedule Card -->
        <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; margin: 0 0 26px 0;">
            <tr>
                <td style="padding: 22px; text-align: left;">
                    <p style="color: {{ $emailLayout['secondary_color'] ?? '#0b6b3a' }}; margin: 0 0 14px 0; font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">
                        🎙️ Panel Interview Schedule
                    </p>
                    
                    <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0">
                        @if(!empty($metadata['batch_name']))
                        <tr>
                            <td style="padding: 5px 0; color: #64748b; font-size: 13px; width: 110px;">Batch:</td>
                            <td style="padding: 5px 0; color: {{ $emailLayout['heading_color'] ?? '#0f172a' }}; font-size: 14px; font-weight: 600;">{{ $metadata['batch_name'] }}</td>
                        </tr>
                        @endif
                        @if(!empty($metadata['interview_date']))
                        <tr>
                            <td style="padding: 5px 0; color: #64748b; font-size: 13px;">Date:</td>
                            <td style="padding: 5px 0; color: {{ $emailLayout['heading_color'] ?? '#0f172a' }}; font-size: 14px; font-weight: 600;">{{ \Carbon\Carbon::parse($metadata['interview_date'])->format('F d, Y (l)') }}</td>
                        </tr>
                        @endif
                        @if(!empty($metadata['time']))
                        <tr>
                            <td style="padding: 5px 0; color: #64748b; font-size: 13px;">Time:</td>
                            <td style="padding: 5px 0; color: {{ $emailLayout['heading_color'] ?? '#0f172a' }}; font-size: 14px; font-weight: 600;">{{ $metadata['time'] }}</td>
                        </tr>
                        @endif
                        @if(!empty($metadata['panel']))
                        <tr>
                            <td style="padding: 5px 0; color: #64748b; font-size: 13px;">Panel:</td>
                            <td style="padding: 5px 0; color: {{ $emailLayout['heading_color'] ?? '#0f172a' }}; font-size: 14px; font-weight: 600;">{{ $metadata['panel'] }}</td>
                        </tr>
                        @endif
                        @if(!empty($metadata['mode']))
                        <tr>
                            <td style="padding: 5px 0; color: #64748b; font-size: 13px;">Mode/Venue:</td>
                            <td style="padding: 5px 0; color: {{ $emailLayout['heading_color'] ?? '#0f172a' }}; font-size: 14px; font-weight: 600;">{{ $metadata['mode'] }}</td>
                        </tr>
                        @endif
                    </table>

                    <div style="margin-top: 14px; padding-top: 12px; border-top: 1px dashed #cbd5e1; color: #475569; font-size: 12px; line-height: 1.5;">
                        <strong>Reminders:</strong> Please wear smart casual / semi-formal attire and be ready 15 minutes before your interview schedule.
                    </div>
                </td>
            </tr>
        </table>

    @elseif(in_array($status, ['Approved']))
        <!-- Scholar Confirmation Card -->
        <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px; margin: 0 0 26px 0;">
            <tr>
                <td style="padding: 22px; text-align: left;">
                    <p style="color: #166534; margin: 0 0 10px 0; font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">
                        🎓 Official Scholar Confirmation
                    </p>
                    <p style="color: #15803d; margin: 0; font-size: 14px; line-height: 1.6;">
                        You are now an officially verified <strong>Educational Assistance Program (ECESPRO) Scholar</strong> of the City Government of Tagum. Please check your youth portal for your scholar updates and semester compliance schedules.
                    </p>
                </td>
            </tr>
        </table>

    @elseif($status === 'For Revision')
        <!-- Document Revision Request Card -->
        <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color: #fffbeb; border-left: 4px solid #f59e0b; border-radius: 4px; margin: 0 0 26px 0;">
            <tr>
                <td style="padding: 18px 20px; text-align: left;">
                    <p style="color: #92400e; margin: 0 0 8px 0; font-size: 13px; font-weight: 700; text-transform: uppercase;">
                        ⚠️ Action Required: Document Revision
                    </p>
                    @if(!empty($metadata['document_name']))
                    <p style="color: #78350f; margin: 0 0 6px 0; font-size: 14px;">
                        <strong>Document:</strong> {{ $metadata['document_name'] }}
                    </p>
                    @endif
                    @if(!empty($metadata['remarks']))
                    <p style="color: #78350f; margin: 0; font-size: 13px; line-height: 1.5;">
                        <strong>Reviewer Remarks:</strong> {{ $metadata['remarks'] }}
                    </p>
                    @endif
                </td>
            </tr>
        </table>

    @elseif(in_array($status, ['Qualified for Exam', 'Qualified for Interview', 'Qualified for Contract']))
        <!-- Milestone Qualified Card -->
        <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px; margin: 0 0 26px 0;">
            <tr>
                <td style="padding: 20px; text-align: left;">
                    <p style="color: #166534; margin: 0 0 6px 0; font-size: 14px; font-weight: 700;">
                        🌟 Milestone Unlocked
                    </p>
                    <p style="color: #15803d; margin: 0; font-size: 13px; line-height: 1.5;">
                        Your application has successfully passed the evaluation and advanced to the next phase. Please keep your lines open and check your portal for schedule announcements.
                    </p>
                </td>
            </tr>
        </table>

    @elseif(in_array($status, ['Failed Exam', 'Failed in Exam', 'Failed Interview', 'Failed in Interview', 'Rejected']))
        <!-- Result Notice Card -->
        <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; margin: 0 0 26px 0;">
            <tr>
                <td style="padding: 18px 20px; text-align: left;">
                    <p style="color: #475569; margin: 0; font-size: 13px; line-height: 1.5;">
                        We appreciate the time, dedication, and effort you invested into your ECESPRO scholarship application. We encourage you to participate in upcoming youth programs and future scholarship opportunities offered by Tagum City.
                    </p>
                </td>
            </tr>
        </table>
    @endif

    <!-- CTA Button -->
    <div style="text-align: center; margin: 0 0 24px 0;">
        <a href="{{ $portalUrl }}" target="_blank" style="display: inline-block; background: linear-gradient(135deg, {{ $emailLayout['primary_color'] ?? '#07823f' }} 0%, {{ $emailLayout['secondary_color'] ?? '#0b6b3a' }} 100%); color: {{ $emailLayout['button_text_color'] ?? '#ffffff' }}; text-decoration: none; padding: 14px 34px; font-size: 15px; font-weight: 600; border-radius: 8px; box-shadow: 0 4px 12px rgba(7, 130, 63, 0.3);">
            {{ $emailTemplate['button_text'] ?? 'View Application Portal →' }}
        </a>
    </div>

    <!-- Reminder Note -->
    <p style="color: #64748b; margin: 0; font-size: 12px; text-align: center; line-height: 1.5;">
        You can check your complete real-time application timeline and announcements anytime in your youth portal.
    </p>
@endsection
