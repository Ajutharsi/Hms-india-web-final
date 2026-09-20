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
    if ($request->is('subscription/*', 'login', 'logout', 'register', 'forgot-password', 'reset-password/*', 'up', 'session/ping')) {
        return $next($request);
    }

    // Super admin always has access
    if (auth()->check() && auth()->user()->hasRole('super_admin')) {
        return $next($request);
    }

    $subscription = Cache::get('current_subscription') ?? Subscription::resolveCurrent();

    if (!$subscription) {
        // Fail closed: no subscription exists for the platform's default
        // hospital, so gate access rather than granting everyone unrestricted
        // access (see SUPER_ADMIN_MODULE_ANALYSIS.md §14).
        return redirect()->route('subscription.expired');
    }

    if ($subscription->status === 'suspended') {
        return redirect()->route('subscription.suspended');
    }

    if ($subscription->status === 'cancelled' || $subscription->isExpired()) {
        return redirect()->route('subscription.expired');
    }

    return $next($request);
}
}
