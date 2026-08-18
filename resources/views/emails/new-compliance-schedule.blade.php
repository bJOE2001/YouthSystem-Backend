<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compliance Schedule Notice</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; -webkit-font-smoothing: antialiased;">
    <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color: #f1f5f9; padding: 40px 15px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="max-width: 580px; background-color: #ffffff; border-radius: 14px; overflow: hidden; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.08), 0 8px 10px -6px rgba(0, 0, 0, 0.04);">
                    <tr>
                        <td style="background: linear-gradient(135deg, #07823f 0%, #0b6b3a 100%); height: 8px;"></td>
                    </tr>
                    <tr>
                        <td style="padding: 38px 32px 30px 32px;">
                            <div style="text-align: center; margin-bottom: 24px;">
                                <img src="https://i.imgur.com/1ebdGUz.png" alt="TCYDO Logo" style="height: 76px; width: auto; display: inline-block;" />
                            </div>

                            <h2 style="color: #0f172a; margin: 0 0 12px 0; font-size: 20px; font-weight: 700; text-align: center;">
                                New Compliance Submission Schedule
                            </h2>
                            
                            <p style="color: #475569; margin: 0 0 24px 0; font-size: 15px; line-height: 1.6; text-align: center;">
                                Hello <strong>{{ $user->name }}</strong>, a new compliance schedule has been posted for the <strong>{{ $schedule->school_year }} ({{ $schedule->semester }})</strong> term. Please review the submission requirements and deadline below.
                            </p>

                            <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; margin: 0 0 28px 0;">
                                <tr>
                                    <td style="padding: 22px 24px; text-align: left;">
                                        <p style="color: #0b6b3a; margin: 0 0 12px 0; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">
                                            Schedule Details
                                        </p>
                                        
                                        <div style="margin-bottom: 12px;">
                                            <span style="color: #64748b; font-size: 12px; font-weight: 500; display: block; margin-bottom: 2px;">Title</span>
                                            <span style="color: #0f172a; font-size: 16px; font-weight: 700;">{{ $schedule->title }}</span>
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

                            <div style="text-align: center; margin: 0 0 28px 0;">
                                <a href="{{ $requirementsUrl }}" target="_blank" style="display: inline-block; background: linear-gradient(135deg, #07823f 0%, #0b6b3a 100%); color: #ffffff; text-decoration: none; padding: 14px 34px; font-size: 15px; font-weight: 600; border-radius: 8px; box-shadow: 0 4px 12px rgba(7, 130, 63, 0.3);">
                                    Submit Compliance Documents &rarr;
                                </a>
                            </div>

                            <p style="color: #64748b; font-size: 13px; line-height: 1.6; margin: 0; text-align: center;">
                                Tagum City Youth Development Office (TCYDO)<br>
                                <em>Empowering the Youth, Building the Future.</em>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
