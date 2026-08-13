<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Youth Account Validated</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; -webkit-font-smoothing: antialiased;">
    <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color: #f1f5f9; padding: 40px 15px;">
        <tr>
            <td align="center">
                <!-- Email Main Card -->
                <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="max-width: 560px; background-color: #ffffff; border-radius: 14px; overflow: hidden; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.08), 0 8px 10px -6px rgba(0, 0, 0, 0.04);">
                    
                    <!-- Body Content -->
                    <tr>
                        <td style="padding: 38px 32px 30px 32px;">
                            
                            <!-- Centered Public HTTPS Logo Above Hello Name -->
                            <div style="text-align: center; margin-bottom: 24px;">
                                <img src="https://i.imgur.com/1ebdGUz.png" alt="Youth Logo" style="height: 76px; width: auto; display: inline-block;" />
                            </div>

                            <h2 style="color: #0f172a; margin: 0 0 14px 0; font-size: 19px; font-weight: 600;">
                                Hello {{ $user->name }},
                            </h2>
                            
                            <p style="color: #334155; margin: 0 0 26px 0; font-size: 15px; line-height: 1.6;">
                                Great news! Your youth registration profile has been officially reviewed, validated, and approved by your Sangguniang Kabataan (SK) Official.
                            </p>

                            <!-- Credentials Box -->
                            <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; margin: 0 0 28px 0;">
                                <tr>
                                    <td style="padding: 24px; text-align: left;">
                                        <p style="color: #475569; margin: 0 0 16px 0; font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">
                                            Your Login Account Credentials
                                        </p>
                                        
                                        <!-- Registered Email -->
                                        <div style="margin-bottom: 16px;">
                                            <span style="color: #64748b; font-size: 12px; font-weight: 500; display: block; margin-bottom: 4px;">Email Address</span>
                                            <span style="color: #0f172a; font-size: 15px; font-weight: 600;">{{ $user->email }}</span>
                                        </div>

                                        <!-- Initial Password -->
                                        <div>
                                            <span style="color: #64748b; font-size: 12px; font-weight: 500; display: block; margin-bottom: 6px;">Initial Password (Birthdate: MMDDYY)</span>
                                            <span style="display: inline-block; background-color: #e8f5ee; color: #0b6b3a; font-family: 'Courier New', Courier, monospace; font-size: 17px; font-weight: 700; letter-spacing: 2px; padding: 7px 18px; border-radius: 6px; border: 1px solid #b7e4c7;">
                                                {{ $plainPassword }}
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            <!-- CTA Button -->
                            <div style="text-align: center; margin: 0 0 28px 0;">
                                <a href="{{ $loginUrl }}" target="_blank" style="display: inline-block; background: linear-gradient(135deg, #07823f 0%, #0b6b3a 100%); color: #ffffff; text-decoration: none; padding: 14px 34px; font-size: 15px; font-weight: 600; border-radius: 8px; box-shadow: 0 4px 12px rgba(7, 130, 63, 0.3);">
                                    Log In to Your Account &rarr;
                                </a>
                            </div>

                            <!-- Security Warning Card -->
                            <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color: #fffbeb; border-left: 4px solid #f59e0b; border-radius: 4px; margin: 0 0 10px 0;">
                                <tr>
                                    <td style="padding: 14px 18px;">
                                        <p style="color: #92400e; margin: 0; font-size: 13px; line-height: 1.5; text-align: left;">
                                            <strong>Security Reminder:</strong> For your account security, please update your password after logging in for the first time.
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f8fafc; padding: 22px 30px; text-align: center; border-top: 1px solid #e2e8f0;">
                            <p style="color: #475569; margin: 0 0 4px 0; font-size: 12px; font-weight: 600;">
                                Tagum City Youth Development Office & Federation of Sangguniang Kabataan
                            </p>
                            <p style="color: #94a3b8; margin: 0; font-size: 11px;">
                                This is an automated notification. Please do not reply directly to this email.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
