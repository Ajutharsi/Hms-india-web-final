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
        if ($request->is('user/profile*', 'login', 'logout', 'register', 'forgot-password', 'reset-password/*', 'up', 'session/ping', 'impersonate/*')) {
            return $next($request);
        }

        $user = auth()->user();

        if ($user && $user->must_change_password) {
            return redirect()->route('profile.show')
                ->with('warning', 'You must change your password before continuing.');
        }

        return $next($request);
    }
}
