<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subjectTitle ?? 'ECESPRO Scholarship Update' }}</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; -webkit-font-smoothing: antialiased;">
    <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color: #f1f5f9; padding: 40px 15px;">
        <tr>
            <td align="center">
                <!-- Email Main Card -->
                <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="max-width: 580px; background-color: #ffffff; border-radius: 14px; overflow: hidden; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.08), 0 8px 10px -6px rgba(0, 0, 0, 0.04);">
                    
                    <!-- Body Content -->
                    <tr>
                        <td style="padding: 38px 32px 30px 32px;">
                            
                            <!-- Centered Public HTTPS Logo Above Greeting -->
                            <div style="text-align: center; margin-bottom: 24px;">
                                <img src="https://i.imgur.com/1ebdGUz.png" alt="Youth Logo" style="height: 76px; width: auto; display: inline-block;" />
                            </div>

                            <!-- Greeting -->
                            <h2 style="color: #0f172a; margin: 0 0 14px 0; font-size: 19px; font-weight: 600;">
                                Hello {{ $recipientName }},
                            </h2>
                            
                            <!-- Status Headline Message -->
                            <p style="color: #334155; margin: 0 0 24px 0; font-size: 15px; line-height: 1.6;">
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
                                            <p style="color: #0b6b3a; margin: 0 0 14px 0; font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">
                                                📝 Qualifying Examination Schedule
                                            </p>
                                            
                                            <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0">
                                                @if(!empty($metadata['batch_name']))
                                                <tr>
                                                    <td style="padding: 5px 0; color: #64748b; font-size: 13px; width: 110px;">Batch:</td>
                                                    <td style="padding: 5px 0; color: #0f172a; font-size: 14px; font-weight: 600;">{{ $metadata['batch_name'] }}</td>
                                                </tr>
                                                @endif
                                                @if(!empty($metadata['exam_date']))
                                                <tr>
                                                    <td style="padding: 5px 0; color: #64748b; font-size: 13px;">Date:</td>
                                                    <td style="padding: 5px 0; color: #0f172a; font-size: 14px; font-weight: 600;">{{ \Carbon\Carbon::parse($metadata['exam_date'])->format('F d, Y (l)') }}</td>
                                                </tr>
                                                @endif
                                                @if(!empty($metadata['time']))
                                                <tr>
                                                    <td style="padding: 5px 0; color: #64748b; font-size: 13px;">Time:</td>
                                                    <td style="padding: 5px 0; color: #0f172a; font-size: 14px; font-weight: 600;">{{ $metadata['time'] }}</td>
                                                </tr>
                                                @endif
                                                @if(!empty($metadata['venue']))
                                                <tr>
                                                    <td style="padding: 5px 0; color: #64748b; font-size: 13px;">Venue:</td>
                                                    <td style="padding: 5px 0; color: #0f172a; font-size: 14px; font-weight: 600;">{{ $metadata['venue'] }}</td>
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
                                            <p style="color: #0b6b3a; margin: 0 0 14px 0; font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">
                                                🎙️ Panel Interview Schedule
                                            </p>
                                            
                                            <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0">
                                                @if(!empty($metadata['batch_name']))
                                                <tr>
                                                    <td style="padding: 5px 0; color: #64748b; font-size: 13px; width: 110px;">Batch:</td>
                                                    <td style="padding: 5px 0; color: #0f172a; font-size: 14px; font-weight: 600;">{{ $metadata['batch_name'] }}</td>
                                                </tr>
                                                @endif
                                                @if(!empty($metadata['interview_date']))
                                                <tr>
                                                    <td style="padding: 5px 0; color: #64748b; font-size: 13px;">Date:</td>
                                                    <td style="padding: 5px 0; color: #0f172a; font-size: 14px; font-weight: 600;">{{ \Carbon\Carbon::parse($metadata['interview_date'])->format('F d, Y (l)') }}</td>
                                                </tr>
                                                @endif
                                                @if(!empty($metadata['time']))
                                                <tr>
                                                    <td style="padding: 5px 0; color: #64748b; font-size: 13px;">Time:</td>
                                                    <td style="padding: 5px 0; color: #0f172a; font-size: 14px; font-weight: 600;">{{ $metadata['time'] }}</td>
                                                </tr>
                                                @endif
                                                @if(!empty($metadata['panel']))
                                                <tr>
                                                    <td style="padding: 5px 0; color: #64748b; font-size: 13px;">Panel:</td>
                                                    <td style="padding: 5px 0; color: #0f172a; font-size: 14px; font-weight: 600;">{{ $metadata['panel'] }}</td>
                                                </tr>
                                                @endif
                                                @if(!empty($metadata['mode']))
                                                <tr>
                                                    <td style="padding: 5px 0; color: #64748b; font-size: 13px;">Mode/Venue:</td>
                                                    <td style="padding: 5px 0; color: #0f172a; font-size: 14px; font-weight: 600;">{{ $metadata['mode'] }}</td>
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
                            <div style="text-align: center; margin: 0 0 26px 0;">
                                <a href="{{ $portalUrl }}" target="_blank" style="display: inline-block; background: linear-gradient(135deg, #07823f 0%, #0b6b3a 100%); color: #ffffff; text-decoration: none; padding: 14px 34px; font-size: 15px; font-weight: 600; border-radius: 8px; box-shadow: 0 4px 12px rgba(7, 130, 63, 0.3);">
                                    View Application Portal &rarr;
                                </a>
                            </div>

                            <!-- Reminder Note -->
                            <p style="color: #64748b; margin: 0; font-size: 12px; text-align: center; line-height: 1.5;">
                                You can check your complete real-time application timeline and announcements anytime in your youth portal.
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f8fafc; padding: 22px 30px; text-align: center; border-top: 1px solid #e2e8f0;">
                            <p style="color: #475569; margin: 0 0 4px 0; font-size: 12px; font-weight: 600;">
                                City of Tagum &bull; Tagum City Youth Development Office (TCYDO)
                            </p>
                            <p style="color: #94a3b8; margin: 0; font-size: 11px;">
                                This is an automated notification from the ECESPRO Scholarship Management System. Please do not reply directly to this email.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
