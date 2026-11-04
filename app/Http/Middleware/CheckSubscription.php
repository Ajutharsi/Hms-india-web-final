<?php

namespace App\Http\Middleware;

use App\Models\Subscription;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscription
{
    /**
     * Handle an incoming request.
     */
   public function handle(Request $request, Closure $next): Response
{
    // Exclude these routes always — plumbing/session-infrastructure routes
    // that must keep working even while a subscription is expired/absent.
    if ($request->is('subscription/*', 'login', 'logout', 'register', 'forgot-password', 'reset-password/*', 'up', 'session/ping',
        'api/login', 'api/logout', 'api/register', 'api/forgot-password')) {
        return $next($request);
    }

    // Super admin always has access
    if ($request->user()?->hasRole('super_admin')) {
        return $next($request);
    }

    $subscription = Cache::get('current_subscription') ?? Subscription::resolveCurrent();

    // Fail closed: no subscription exists for the platform's default
    // hospital, so gate access rather than granting everyone unrestricted
    // access (see SUPER_ADMIN_MODULE_ANALYSIS.md §14).
    if (!$subscription) {
        return $this->deny($request, 'expired', "This hospital's subscription is not active. Please contact your administrator.");
    }

    if ($subscription->status === 'suspended') {
        return $this->deny($request, 'suspended', "This hospital's subscription is suspended. Please contact your administrator.");
    }

    if ($subscription->status === 'cancelled' || $subscription->isExpired()) {
        return $this->deny($request, 'expired', "This hospital's subscription has expired. Please contact your administrator.");
    }

    return $next($request);
}

    /**
     * The mobile app has no session and no pages to land on, so it gets a
     * 403 with a reason it can show; browsers keep the existing redirect.
     */
    private function deny(Request $request, string $state, string $message): Response
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message, 'subscription_state' => $state], 403);
        }

        return redirect()->route($state === 'suspended' ? 'subscription.suspended' : 'subscription.expired');
    }
}
