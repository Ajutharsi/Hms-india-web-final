<?php

namespace App\Http\Middleware;

use App\Models\ActivityLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Permission failures — Audit & Activity Centre. Registered as a global
 * `web` middleware so it observes the final response for every request,
 * including ones role/permission middleware further down the pipeline
 * denies.
 *
 * Deliberately checks the response status rather than catching
 * `Spatie\Permission\Exceptions\UnauthorizedException` directly: Laravel's
 * routing Pipeline renders route-middleware exceptions into a Response
 * *before* returning control to global middleware like this one, so by the
 * time `$next($request)` returns, the exception is already gone — only its
 * rendered 403 response remains. (A `renderable()`/`reportable()` hook on
 * the exception handler was tried first and also doesn't work: this app's
 * bound `ExceptionHandler` is swapped for Collision's own adapter in any
 * console-booted process — which includes `php artisan test` — so those
 * callbacks are silently never invoked there and the feature would be
 * untestable.) See SUPER_ADMIN_AUDIT_ACTIVITY_REPORT.md.
 */
class LogPermissionDenials
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($response->getStatusCode() === 403) {
            $user = auth()->user();

            ActivityLog::log(
                'permission_denied',
                'auth',
                ($user?->name ?? 'Guest') . ' was denied access to ' . $request->path(),
                $user
            );
        }

        return $response;
    }
}
