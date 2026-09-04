<?php

namespace App\Providers;

use App\Services\EmailLayoutService;
use App\Services\EmailTemplateService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Auth\Notifications\ResetPassword;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        resetPassword::createUrlUsing(function($user, string $token){
        return env('FRONTEND_URL', 'http://localhost:9000') . '/#/reset-password?token=' . $token . '&email=' . urlencode($user->email);
        });
        RateLimiter::for('login', function (Request $request): Limit {
            $email = (string) $request->string('email')->trim()->lower();

            return Limit::perMinute(5)->by($email.'|'.$request->ip());
        });

        View::composer('emails.*', function ($view): void {
            if (! $view->offsetExists('emailLayout')) {
                $view->with('emailLayout', app(EmailLayoutService::class)->getSettings());
            }

            if (! $view->offsetExists('emailTemplate')) {
                $viewName = $view->getName();
                $templateKey = str_replace(['emails.', '-'], ['', '_'], $viewName);

                $viewData = $view->getData();
                $variables = [];

                if (isset($viewData['user'])) {
                    $variables['user_name'] = $viewData['user']->name ?? '';
                    $variables['user_email'] = $viewData['user']->email ?? '';
                }
                if (isset($viewData['recipientName'])) {
                    $variables['recipient_name'] = (string) $viewData['recipientName'];
                }
                if (isset($viewData['plainPassword'])) {
                    $variables['initial_password'] = (string) $viewData['plainPassword'];
                }
                if (isset($viewData['loginUrl'])) {
                    $variables['login_url'] = (string) $viewData['loginUrl'];
                }
                if (isset($viewData['facility'])) {
                    $variables['facility_name'] = $viewData['facility']->name ?? '';
                }
                if (isset($viewData['booking'])) {
                    $b = $viewData['booking'];
                    $variables['facility_name'] = $b->facility->name ?? ($viewData['facility']->name ?? '');
                    $variables['booking_date'] = (string) ($b->date ?? '');
                    $variables['booking_time'] = trim(($b->start_time ?? '').' - '.($b->end_time ?? ''));
                    $variables['purpose'] = (string) ($b->purpose ?? '');
                    $variables['remarks'] = (string) ($b->remarks ?? '');
                    $variables['requester_name'] = $b->user->name ?? ($viewData['user']->name ?? '');
                    $variables['requester_email'] = $b->user->email ?? ($viewData['user']->email ?? '');
                }
                if (isset($viewData['announcement'])) {
                    $a = $viewData['announcement'];
                    $variables['announcement_title'] = (string) ($a->title ?? '');
                    $variables['announcement_description'] = (string) ($a->description ?? '');
                    $variables['published_date'] = isset($a->created_at) ? $a->created_at->format('F d, Y') : date('F d, Y');
                }
                if (isset($viewData['event'])) {
                    $e = $viewData['event'];
                    $variables['event_name'] = (string) ($e->title ?? $e->name ?? '');
                    $variables['classification'] = (string) ($e->classification ?? $e->category ?? '');
                    $variables['location'] = (string) ($e->location ?? $e->venue ?? '');
                    $variables['event_date'] = (string) ($e->event_date ?? $e->start_date ?? '');
                    $variables['event_time'] = (string) ($e->event_time ?? $e->start_time ?? '');
                }
                if (isset($viewData['activityTitle'])) {
                    $variables['activity_name'] = (string) $viewData['activityTitle'];
                }
                if (isset($viewData['categoryName'])) {
                    $variables['category'] = (string) $viewData['categoryName'];
                }
                if (isset($viewData['schedule'])) {
                    $s = $viewData['schedule'];
                    $variables['schedule_title'] = (string) ($s->title ?? '');
                    $variables['school_year'] = (string) ($s->school_year ?? '');
                    $variables['semester'] = (string) ($s->semester ?? '');
                    $variables['submission_period'] = (string) ($s->submission_period ?? '');
                    $variables['instructions'] = (string) ($s->instructions ?? '');
                }
                if (isset($viewData['application'])) {
                    $app = $viewData['application'];
                    $variables['applicant_name'] = (string) ($app->applicant_name ?? $app->user->name ?? '');
                    $variables['applicant_email'] = (string) ($app->applicant_email ?? $app->user->email ?? '');
                    $variables['course'] = (string) ($app->course ?? '');
                    $variables['year_level'] = (string) ($app->year_level ?? '');
                    $variables['school'] = (string) ($app->school ?? '');
                }
                if (isset($viewData['status'])) {
                    $variables['status'] = (string) $viewData['status'];
                }
                if (isset($viewData['statusMessage'])) {
                    $variables['status_message'] = (string) $viewData['statusMessage'];
                }
                if (isset($viewData['portalUrl'])) {
                    $variables['portal_url'] = (string) $viewData['portalUrl'];
                }
                if (isset($viewData['announcementUrl'])) {
                    $variables['announcement_url'] = (string) $viewData['announcementUrl'];
                }
                if (isset($viewData['eventUrl'])) {
                    $variables['event_url'] = (string) $viewData['eventUrl'];
                }
                if (isset($viewData['activitiesUrl'])) {
                    $variables['activities_url'] = (string) $viewData['activitiesUrl'];
                }
                if (isset($viewData['requirementsUrl'])) {
                    $variables['requirements_url'] = (string) $viewData['requirementsUrl'];
                }
                if (isset($viewData['adminUrl'])) {
                    $variables['admin_url'] = (string) $viewData['adminUrl'];
                }
                if (isset($viewData['loginUrl'])) {
                    $variables['login_url'] = (string) $viewData['loginUrl'];
                }
                if (isset($viewData['daysInactive'])) {
                    $variables['days_inactive'] = (string) $viewData['daysInactive'];
                }
                if (isset($viewData['lastLoginFormatted'])) {
                    $variables['last_login_formatted'] = (string) $viewData['lastLoginFormatted'];
                }

                // Add any scalar parameters directly
                foreach ($viewData as $k => $v) {
                    if (is_scalar($v) && ! isset($variables[Str::snake($k)])) {
                        $variables[Str::snake($k)] = (string) $v;
                    }
                }

                $rendered = app(EmailTemplateService::class)->render($templateKey, $variables);
                $view->with('emailTemplate', $rendered);
            }
        });
    }
}


