<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New ECESPRO Application</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; -webkit-font-smoothing: antialiased;">
    <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color: #f1f5f9; padding: 40px 15px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="max-width: 580px; background-color: #ffffff; border-radius: 14px; overflow: hidden; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.08), 0 8px 10px -6px rgba(0, 0, 0, 0.04);">
                    <tr>
                        <td style="background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%); height: 8px;"></td>
                    </tr>
                    <tr>
                        <td style="padding: 38px 32px 30px 32px;">
                            <div style="text-align: center; margin-bottom: 24px;">
                                <img src="https://i.imgur.com/1ebdGUz.png" alt="TCYDO Logo" style="height: 76px; width: auto; display: inline-block;" />
                            </div>

                            <h2 style="color: #0f172a; margin: 0 0 12px 0; font-size: 20px; font-weight: 700; text-align: center;">
                                New ECESPRO Scholarship Application
                            </h2>
                            
                            <p style="color: #475569; margin: 0 0 24px 0; font-size: 15px; line-height: 1.6; text-align: center;">
                                A new scholarship application has been submitted and is ready for initial document verification and qualification review.
                            </p>

                            <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; margin: 0 0 28px 0;">
                                <tr>
                                    <td style="padding: 22px 24px; text-align: left;">
                                        <p style="color: #0369a1; margin: 0 0 12px 0; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">
                                            Applicant Information
                                        </p>
                                        
                                        <div style="margin-bottom: 12px;">
                                            <span style="color: #64748b; font-size: 12px; font-weight: 500; display: block; margin-bottom: 2px;">Applicant Name</span>
                                            <span style="color: #0f172a; font-size: 16px; font-weight: 700;">{{ $applicantName }}</span>
                                        </div>

                                        @if(!empty($application->email_address))
                                        <div style="margin-bottom: 12px;">
                                            <span style="color: #64748b; font-size: 12px; font-weight: 500; display: block; margin-bottom: 2px;">Email Address</span>
                                            <span style="color: #334155; font-size: 14px;">{{ $application->email_address }}</span>
                                        </div>
                                        @endif

                                        @if(!empty($application->course))
                                        <div style="margin-bottom: 12px;">
                                            <span style="color: #64748b; font-size: 12px; font-weight: 500; display: block; margin-bottom: 2px;">Course / Program</span>
                                            <span style="color: #334155; font-size: 14px; font-weight: 600;">{{ $application->course }} ({{ $application->year_level ?? 'N/A' }})</span>
                                        </div>
                                        @endif

                                        @if(!empty($application->school))
                                        <div style="margin-bottom: 12px;">
                                            <span style="color: #64748b; font-size: 12px; font-weight: 500; display: block; margin-bottom: 2px;">School / University</span>
                                            <span style="color: #334155; font-size: 14px;">{{ $application->school }}</span>
                                        </div>
                                        @endif

                                        <div>
                                            <span style="color: #64748b; font-size: 12px; font-weight: 500; display: block; margin-bottom: 4px;">Submission Status</span>
                                            <span style="display: inline-block; background-color: #dbeafe; color: #1e40af; font-size: 12px; font-weight: 700; padding: 4px 10px; border-radius: 9999px;">
                                                Submitted &bull; Awaiting Review
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            <div style="text-align: center; margin: 0 0 28px 0;">
                                <a href="{{ $adminUrl }}" target="_blank" style="display: inline-block; background: linear-gradient(135deg, #07823f 0%, #0b6b3a 100%); color: #ffffff; text-decoration: none; padding: 14px 34px; font-size: 15px; font-weight: 600; border-radius: 8px; box-shadow: 0 4px 12px rgba(7, 130, 63, 0.3);">
                                    Review Application in Admin Portal &rarr;
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
