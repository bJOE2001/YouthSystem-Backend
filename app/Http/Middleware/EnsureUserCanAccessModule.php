<?php

namespace App\Http\Middleware;

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
