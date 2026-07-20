<?php

use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\EnsureUserIsActive;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();

        $middleware->validateCsrfTokens(except: [
            'api/*',
        ]);

        $middleware->alias([
            'active' => EnsureUserIsActive::class,
            'role' => EnsureUserHasRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                $bearerToken = $request->bearerToken();
                $reason = 'unauthenticated';
                $message = 'Unauthenticated.';

                if ($bearerToken) {
                    $tokenModel = PersonalAccessToken::findToken($bearerToken);
                    if ($tokenModel) {
                        $reason = 'session_expired';
                        $message = 'Your session has expired due to inactivity.';
                    } else {
                        $reason = 'logged_in_elsewhere';
                        $message = 'Your account was logged in from another device.';
                    }
                }

                return response()->json([
                    'message' => $message,
                    'reason' => $reason,
                ], 401);
            }
        });

        $exceptions->render(function (ValidationException $e, $request) {
            Log::error('Validation Failed: ', $e->errors());
        });
        $exceptions->reportable(function (ValidationException $e) {
            Log::error('Validation failed: ', $e->errors());
        });
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request, Throwable $exception): bool => $request->is('api/*', 'login', 'logout')
                || $request->expectsJson(),
        );
    })->create();
