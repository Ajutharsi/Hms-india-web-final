<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enforces Global Settings' "Force Password Change" action
 * (`users.must_change_password`) — redirects any authenticated user with
 * the flag set to their profile page (which already handles password
 * changes) until they actually change their password. See
 * SUPER_ADMIN_GLOBAL_SETTINGS_REPORT.md.
 */
class RequirePasswordChange
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('user/profile*', 'login', 'logout', 'register', 'forgot-password', 'reset-password/*', 'up', 'session/ping', 'impersonate/*',
            'api/login', 'api/logout', 'api/register', 'api/forgot-password', 'api/me', 'api/*/profile')) {
            return $next($request);
        }

        $user = $request->user();

        if ($user && $user->must_change_password) {
            // The app can't be redirected to a web page; it gets a flag it
            // can act on, and its own profile screen stays reachable above
            // so the password can actually be changed.
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'You must change your password before continuing.',
                    'must_change_password' => true,
                ], 403);
            }

            return redirect()->route('profile.show')
                ->with('warning', 'You must change your password before continuing.');
        }

        return $next($request);
    }
}
