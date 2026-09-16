<?php

namespace App\Services;

use App\Models\SmsLog;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SmsService
{
    /**
     * Normalize phone number to standard 11-digit Philippine mobile format (09XXXXXXXXX).
     */
    public function normalizePhoneNumber(?string $number): ?string
    {
        if ($number === null || trim($number) === '') {
            return null;
        }

        // Remove all non-digit characters
        $digits = preg_replace('/\D+/', '', $number);

        // Convert international format: 639XXXXXXXXX -> 09XXXXXXXXX
        if (str_starts_with($digits, '639') && strlen($digits) === 12) {
            $digits = '0'.substr($digits, 2);
        } elseif (str_starts_with($digits, '9') && strlen($digits) === 10) {
            // Convert 9XXXXXXXXX -> 09XXXXXXXXX
            $digits = '0'.$digits;
        }

        // Check if it matches valid Philippine mobile format: 09 + 9 digits (total 11 digits)
        if (preg_match('/^09\d{9}$/', $digits)) {
            return $digits;
        }

        return null;
    }

    /**
     * Send an SMS via the Yeastar GSM Gateway.
     *
     * @return array{success: bool, message: string, log?: SmsLog}
     */
    public function send(string $phoneNumber, string $message, ?User $user = null, ?string $eventType = null): array
    {
        if (! config('sms.enabled', true)) {
            Log::info('SMS dispatch skipped: SMS is disabled in configuration.', [
                'recipient' => $phoneNumber,
                'event_type' => $eventType,
            ]);

            return [
                'success' => false,
                'message' => 'SMS service is disabled in configuration.',
            ];
        }

        $recipient = $this->normalizePhoneNumber($phoneNumber);

        if ($recipient === null) {
            Log::warning('SMS dispatch failed: Invalid recipient phone number.', [
                'provided_number' => $phoneNumber,
                'event_type' => $eventType,
            ]);

            $log = SmsLog::create([
                'user_id' => $user?->id,
                'recipient' => $phoneNumber,
                'message' => $message,
                'status' => 'failed',
                'response_code' => 'INVALID_PHONE',
                'response_body' => 'Recipient phone number is invalid for Philippine mobile format (expected 09XXXXXXXXX).',
                'event_type' => $eventType,
            ]);

            return [
                'success' => false,
                'message' => 'Recipient phone number is invalid.',
                'log' => $log,
            ];
        }

        $gatewayUrl = config('sms.gateway_url', 'http://192.168.100.52/cgi/WebCGI?1500101=account=apiuser&password=apipass&port=1');
        $timeout = (int) config('sms.timeout', 10);
        $separator = str_contains($gatewayUrl, '?') ? '&' : '?';

        $url = sprintf(
            '%s%sdestination=%s&content=%s',
            $gatewayUrl,
            $separator,
            urlencode($recipient),
            urlencode($message)
        );

        try {
            $response = Http::timeout($timeout)->connectTimeout(5)->get($url);
            $body = $response->body();

            $isSuccess = $response->successful()
                && (stripos($body, 'Response: Success') !== false || stripos($body, 'Commit successfully') !== false);

            $log = SmsLog::create([
                'user_id' => $user?->id,
                'recipient' => $recipient,
                'message' => $message,
                'status' => $isSuccess ? 'success' : 'failed',
                'response_code' => (string) $response->status(),
                'response_body' => Str::limit($body, 1000),
                'event_type' => $eventType,
            ]);

            if (! $isSuccess) {
                Log::warning('SMS gateway returned non-success response.', [
                    'recipient' => $recipient,
                    'status_code' => $response->status(),
                    'body' => $body,
                    'event_type' => $eventType,
                ]);
            }

            return [
                'success' => $isSuccess,
                'message' => $isSuccess ? 'SMS sent successfully.' : 'SMS gateway rejected the message.',
                'log' => $log,
            ];
        } catch (\Throwable $e) {
            Log::error('SMS dispatch connection exception: '.$e->getMessage(), [
                'recipient' => $recipient,
                'event_type' => $eventType,
            ]);

            $log = SmsLog::create([
                'user_id' => $user?->id,
                'recipient' => $recipient,
                'message' => $message,
                'status' => 'failed',
                'response_code' => 'CONNECTION_ERROR',
                'response_body' => Str::limit($e->getMessage(), 1000),
                'event_type' => $eventType,
            ]);

            return [
                'success' => false,
                'message' => 'Unable to connect to SMS gateway: '.$e->getMessage(),
                'log' => $log,
            ];
        }
    }

    /**
     * Check connectivity to the SMS gateway.
     *
     * @return array{reachable: bool, message: string, gateway_url: string}
     */
    public function checkConnection(): array
    {
        $gatewayUrl = config('sms.gateway_url', 'http://192.168.100.52/cgi/WebCGI?1500101=account=apiuser&password=apipass&port=1');

        try {
            $response = Http::timeout(4)->connectTimeout(3)->get($gatewayUrl);

            return [
                'reachable' => $response->status() < 500,
                'message' => 'Gateway endpoint responded with status '.$response->status(),
                'gateway_url' => $gatewayUrl,
            ];
        } catch (\Throwable $e) {
            return [
                'reachable' => false,
                'message' => 'Cannot reach gateway: '.$e->getMessage(),
                'gateway_url' => $gatewayUrl,
            ];
        }
    }
}
