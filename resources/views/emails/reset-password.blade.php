@extends('emails.layouts.master')

@section('title', 'Reset Your Password')

@section('content')
    <h2 style="color: {{ $emailLayout['heading_color'] ?? '#0f172a' }}; margin: 0 0 14px 0; font-size: 19px; font-weight: 600;">
        Hello {{ $user->name }},
    </h2>
    
    <p style="color: {{ $emailLayout['text_color'] ?? '#334155' }}; margin: 0 0 26px 0; font-size: 15px; line-height: 1.6;">
        You are receiving this email because we received a password reset request for your account. If you did not request a password reset, no further action is required.
    </p>

    <!-- CTA Button -->
    <div style="text-align: center; margin: 0 0 24px 0;">
        <a href="{{ $resetUrl }}" target="_blank" style="display: inline-block; background: linear-gradient(135deg, {{ $emailLayout['primary_color'] ?? '#07823f' }} 0%, {{ $emailLayout['secondary_color'] ?? '#0b6b3a' }} 100%); color: {{ $emailLayout['button_text_color'] ?? '#ffffff' }}; text-decoration: none; padding: 14px 34px; font-size: 15px; font-weight: 600; border-radius: 8px; box-shadow: 0 4px 12px rgba(7, 130, 63, 0.3);">
            Reset Password
        </a>
    </div>

    <!-- Fallback link -->
    <div style="margin-top: 32px; padding-top: 24px; border-top: 1px solid #e2e8f0;">
        <p style="color: #64748b; font-size: 12px; margin: 0; line-height: 1.5; word-break: break-all;">
            If you're having trouble clicking the "Reset Password" button, copy and paste the URL below into your web browser:
            <br><br>
            <a href="{{ $resetUrl }}" style="color: {{ $emailLayout['primary_color'] ?? '#07823f' }};">{{ $resetUrl }}</a>
        </p>
    </div>
@endsection
