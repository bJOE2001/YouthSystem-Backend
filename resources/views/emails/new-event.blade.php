<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Event Announcement</title>
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
                                New Youth Event Posted!
                            </h2>
                            
                            <p style="color: #475569; margin: 0 0 24px 0; font-size: 15px; line-height: 1.6; text-align: center;">
                                Hello <strong>{{ $user->name }}</strong>, a new youth activity has just been scheduled. Join in, gain new skills, and connect with fellow youth leaders!
                            </p>

                            <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; margin: 0 0 28px 0;">
                                <tr>
                                    <td style="padding: 22px 24px; text-align: left;">
                                        <p style="color: #0b6b3a; margin: 0 0 12px 0; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">
                                            Event Overview
                                        </p>
                                        
                                        <div style="margin-bottom: 12px;">
                                            <span style="color: #64748b; font-size: 12px; font-weight: 500; display: block; margin-bottom: 2px;">Event Title</span>
                                            <span style="color: #0f172a; font-size: 17px; font-weight: 700;">{{ $event->name }}</span>
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

                            <div style="text-align: center; margin: 0 0 28px 0;">
                                <a href="{{ $eventUrl }}" target="_blank" style="display: inline-block; background: linear-gradient(135deg, #07823f 0%, #0b6b3a 100%); color: #ffffff; text-decoration: none; padding: 14px 34px; font-size: 15px; font-weight: 600; border-radius: 8px; box-shadow: 0 4px 12px rgba(7, 130, 63, 0.3);">
                                    Join This Event &rarr;
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
