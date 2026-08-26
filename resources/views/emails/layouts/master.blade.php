<!DOCTYPE html>
<html lang="en" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>@yield('title', $emailLayout['header_title'] ?? 'Youth System Notification')</title>
    <!--[if mso]>
    <style type="text/css">
        body, table, td {font-family: Arial, Helvetica, sans-serif !important;}
    </style>
    <![endif]-->
</head>
<body style="margin: 0; padding: 0; background-color: {{ $emailLayout['body_bg_color'] ?? '#f1f5f9' }}; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; -webkit-font-smoothing: antialiased; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%;">
    <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color: {{ $emailLayout['body_bg_color'] ?? '#f1f5f9' }}; padding: 36px 14px;">
        <tr>
            <td align="center" valign="top">
                <!-- Master Email Card Container -->
                <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="max-width: 580px; width: 100%; background-color: {{ $emailLayout['card_bg_color'] ?? '#ffffff' }}; border-radius: {{ $emailLayout['card_border_radius'] ?? '14px' }}; overflow: hidden; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.08), 0 8px 10px -6px rgba(0, 0, 0, 0.04); border: 1px solid #e2e8f0;">
                    
                    <!-- Top Accent Gradient Bar -->
                    <tr>
                        <td style="background: linear-gradient(135deg, {{ $emailLayout['primary_color'] ?? '#07823f' }} 0%, {{ $emailLayout['secondary_color'] ?? '#0b6b3a' }} 100%); height: 8px; font-size: 0; line-height: 0;">&nbsp;</td>
                    </tr>

                    <!-- Email Header Area -->
                    <tr>
                        <td align="center" style="padding: 32px 32px 16px 32px; text-align: center;">
                            @if(!empty($emailLayout['show_logo']) && !empty($emailLayout['logo_url']))
                                <div style="margin-bottom: {{ (!empty($emailLayout['show_header_title'])) ? '14px' : '8px' }};">
                                    <img src="{{ $emailLayout['logo_url'] }}" alt="{{ $emailLayout['header_title'] ?? 'TCYDO Logo' }}" style="height: {{ $emailLayout['logo_height'] ?? '76px' }}; max-height: 100px; width: auto; display: inline-block; border: 0; outline: none; text-decoration: none;" />
                                </div>
                            @endif

                            @if(!empty($emailLayout['show_header_title']))
                                <h1 style="color: {{ $emailLayout['heading_color'] ?? '#0f172a' }}; font-size: 20px; font-weight: 700; margin: 0 0 4px 0; letter-spacing: -0.3px;">
                                    {{ $emailLayout['header_title'] ?? 'Tagum City Youth Development Office' }}
                                </h1>
                                @if(!empty($emailLayout['header_subtitle']))
                                    <p style="color: {{ $emailLayout['secondary_color'] ?? '#0b6b3a' }}; font-size: 13px; font-weight: 600; margin: 0; text-transform: uppercase; letter-spacing: 0.5px;">
                                        {{ $emailLayout['header_subtitle'] }}
                                    </p>
                                @endif
                            @endif
                        </td>
                    </tr>

                    <!-- Main Dynamic Content Slot -->
                    <tr>
                        <td style="padding: 8px 32px 28px 32px; color: {{ $emailLayout['text_color'] ?? '#334155' }}; font-size: 15px; line-height: 1.6;">
                            @yield('content')
                        </td>
                    </tr>

                    <!-- Footer Section -->
                    <tr>
                        <td style="background-color: #f8fafc; padding: 24px 32px; border-top: 1px solid #e2e8f0; text-align: center;">
                            
                            @if(!empty($emailLayout['footer_text']))
                                <p style="color: {{ $emailLayout['heading_color'] ?? '#0f172a' }}; margin: 0 0 4px 0; font-size: 13px; font-weight: 700; line-height: 1.4;">
                                    {{ $emailLayout['footer_text'] }}
                                </p>
                            @endif

                            @if(!empty($emailLayout['footer_tagline']))
                                <p style="color: {{ $emailLayout['secondary_color'] ?? '#0b6b3a' }}; margin: 0 0 12px 0; font-size: 12px; font-style: italic; font-weight: 500;">
                                    {{ $emailLayout['footer_tagline'] }}
                                </p>
                            @endif

                            <!-- Contact Details -->
                            @if(!empty($emailLayout['show_footer_contact']))
                                <div style="margin: 0 0 14px 0; font-size: 12px; color: #64748b; line-height: 1.5;">
                                    @if(!empty($emailLayout['office_address']))
                                        <div>📍 {{ $emailLayout['office_address'] }}</div>
                                    @endif
                                    @if(!empty($emailLayout['contact_email']) || !empty($emailLayout['contact_phone']))
                                        <div style="margin-top: 3px;">
                                            @if(!empty($emailLayout['contact_email']))
                                                <span>✉️ <a href="mailto:{{ $emailLayout['contact_email'] }}" style="color: {{ $emailLayout['primary_color'] ?? '#07823f' }}; text-decoration: none;">{{ $emailLayout['contact_email'] }}</a></span>
                                            @endif
                                            @if(!empty($emailLayout['contact_phone']))
                                                <span style="margin-left: 8px;">📞 {{ $emailLayout['contact_phone'] }}</span>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            @endif

                            <!-- Social Links -->
                            @if(!empty($emailLayout['show_social_links']) && !empty($emailLayout['social_links']) && is_array($emailLayout['social_links']))
                                <div style="margin: 0 0 14px 0;">
                                    @foreach($emailLayout['social_links'] as $social)
                                        @if(!empty($social['url']))
                                            <a href="{{ $social['url'] }}" target="_blank" style="display: inline-block; margin: 0 5px; color: {{ $emailLayout['primary_color'] ?? '#07823f' }}; text-decoration: none; font-size: 12px; font-weight: 600; padding: 4px 8px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 6px;">
                                                {{ ucfirst($social['platform'] ?? 'Link') }}
                                            </a>
                                        @endif
                                    @endforeach
                                </div>
                            @endif

                            <!-- Automated Disclaimer Notice -->
                            @if(!empty($emailLayout['footer_disclaimer']))
                                <p style="color: #94a3b8; margin: 0 0 8px 0; font-size: 11px; line-height: 1.4;">
                                    {{ $emailLayout['footer_disclaimer'] }}
                                </p>
                            @endif

                            <!-- Copyright Notice -->
                            <p style="color: #94a3b8; margin: 0; font-size: 11px;">
                                {{ $emailLayout['copyright_text_formatted'] ?? str_replace('{year}', date('Y'), $emailLayout['copyright_text'] ?? '© '.date('Y').' Tagum City Youth Development Office. All rights reserved.') }}
                            </p>
                        </td>
                    </tr>

                </table>
                <!-- End Master Card -->
            </td>
        </tr>
    </table>
</body>
</html>
