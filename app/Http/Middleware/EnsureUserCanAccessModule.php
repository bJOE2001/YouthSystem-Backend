<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserCanAccessModule
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $module): Response
    {
        $user = $request->user();

        abort_unless($user instanceof User, Response::HTTP_UNAUTHORIZED);

        // Root Admin has unrestricted access to all modules
        if ($user->isRootAdmin()) {
            return $next($request);
        }

        // Scanner module has multi-persona access: Admin, SK Admin, permitted Sub-Admin, and scanning Scholar
        if ($module === 'scanner') {
            if ($user->role === UserRole::SkAdmin || $user->role === 'sk_admin' || $user->role?->value === 'sk_admin') {
                return $next($request);
            }

            if ($user->isSubAdmin() && $user->canAccessModule('scanner')) {
                return $next($request);
            }

            if ($user->canScanAsScholar()) {
                return $next($request);
            }

            abort(Response::HTTP_FORBIDDEN, 'You do not have permission to access the scanner module.');
        }

        // Sub-Admins must have the specific module in their permissions
        if ($user->isSubAdmin()) {
            if ($user->canAccessModule($module)) {
                return $next($request);
            }

            abort(Response::HTTP_FORBIDDEN, "You do not have permission to access the {$module} module.");
        }

        return $next($request);
    }
}
