<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate of Participation</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; -webkit-font-smoothing: antialiased;">
    <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color: #f1f5f9; padding: 40px 15px;">
        <tr>
            <td align="center">
                <!-- Email Main Card -->
                <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="max-width: 580px; background-color: #ffffff; border-radius: 14px; overflow: hidden; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.08), 0 8px 10px -6px rgba(0, 0, 0, 0.04);">
                    
                    <!-- Top Accent Banner -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #07823f 0%, #0b6b3a 100%); height: 8px;"></td>
                    </tr>

                    <!-- Body Content -->
                    <tr>
                        <td style="padding: 38px 32px 30px 32px;">
                            
                            <!-- Logo -->
                            <div style="text-align: center; margin-bottom: 24px;">
                                <img src="https://i.imgur.com/1ebdGUz.png" alt="TCYDO Logo" style="height: 76px; width: auto; display: inline-block;" />
                            </div>

                            <h2 style="color: #0f172a; margin: 0 0 12px 0; font-size: 20px; font-weight: 700; text-align: center;">
                                Certificate of Participation
                            </h2>
                            
                            <p style="color: #475569; margin: 0 0 24px 0; font-size: 15px; line-height: 1.6; text-align: center;">
                                Dear <strong>{{ $user->name }}</strong>, thank you for actively participating in our community development programs!
                            </p>

                            <!-- Activity Summary Box -->
                            <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; margin: 0 0 28px 0;">
                                <tr>
                                    <td style="padding: 22px 24px; text-align: left;">
                                        <p style="color: #0b6b3a; margin: 0 0 12px 0; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">
                                            Activity Details
                                        </p>
                                        
                                        <!-- Activity Name -->
                                        <div style="margin-bottom: 12px;">
                                            <span style="color: #64748b; font-size: 12px; font-weight: 500; display: block; margin-bottom: 2px;">{{ $isEvent ? 'Event Name' : 'Sports Program' }}</span>
                                            <span style="color: #0f172a; font-size: 16px; font-weight: 700;">{{ $activity->name }}</span>
                                        </div>

                                        <!-- Classification / Type -->
                                        <div style="margin-bottom: 12px;">
                                            <span style="color: #64748b; font-size: 12px; font-weight: 500; display: block; margin-bottom: 2px;">Category</span>
                                            <span style="color: #334155; font-size: 14px; font-weight: 600;">{{ $isEvent ? ($activity->ppa_classification ?? 'Youth Event') : ($activity->type ?? 'Sports Program') }}</span>
                                        </div>

                                        <!-- Status Badge -->
                                        <div>
                                            <span style="color: #64748b; font-size: 12px; font-weight: 500; display: block; margin-bottom: 4px;">Status</span>
                                            <span style="display: inline-block; background-color: #dcfce7; color: #166534; font-size: 12px; font-weight: 700; padding: 4px 10px; border-radius: 9999px;">
                                                Completed & Attended
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            <!-- Attachment Callout -->
                            <div style="background-color: #eff6ff; border: 1px solid #bfdbfe; border-radius: 10px; padding: 16px 20px; margin-bottom: 28px;">
                                <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0">
                                    <tr>
                                        <td width="36" valign="top">
                                            <span style="font-size: 24px; line-height: 1;">📎</span>
                                        </td>
                                        <td style="color: #1e40af; font-size: 14px; line-height: 1.5;">
                                            <strong>PDF Certificate Attached:</strong> Your official personalized Certificate of Participation has been attached to this email. You can download and save it directly from your email attachments.
                                        </td>
                                    </tr>
                                </table>
                            </div>

                            <!-- CTA Button -->
                            <div style="text-align: center; margin: 0 0 28px 0;">
                                <a href="{{ $activitiesUrl }}" target="_blank" style="display: inline-block; background: linear-gradient(135deg, #07823f 0%, #0b6b3a 100%); color: #ffffff; text-decoration: none; padding: 14px 34px; font-size: 15px; font-weight: 600; border-radius: 8px; box-shadow: 0 4px 12px rgba(7, 130, 63, 0.3);">
                                    View in My Activities &rarr;
                                </a>
                            </div>

                            <!-- Signoff -->
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
