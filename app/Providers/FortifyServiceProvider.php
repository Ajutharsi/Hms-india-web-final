<?php

namespace App\Providers;

use App\Actions\Fortify\AuthenticateUser;
use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Models\ActivityLog;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;
use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events\Login;

class FortifyServiceProvider extends ServiceProvider
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
        Event::listen(Login::class, function ($event) {
            $event->user->update(['last_login' => now()]);

            // Login Events — Audit & Activity Centre. See
            // SUPER_ADMIN_AUDIT_ACTIVITY_REPORT.md; the master plan (§5.10)
            // already flagged auth events as a coverage gap in ActivityLog.
            ActivityLog::log('login_success', 'auth', "{$event->user->name} logged in", $event->user);
        });

        Fortify::loginView(function () {
            return view('auth.login');
        });

    Fortify::registerView(function () {
        return view('auth.register');
    });

    Fortify::requestPasswordResetLinkView(function () {
        return view('auth.forgot-password');
    });

    Fortify::resetPasswordView(function ($request) {
    return view('auth.reset-password', ['request' => $request]);
});

    Fortify::confirmPasswordView(function () {
        return view('auth.confirm-password');
    });

    Fortify::verifyEmailView(function () {
        return view('auth.verify-email');
    });

    Fortify::twoFactorChallengeView(function () {
        return view('auth.two-factor-challenge');
    });

        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::authenticateUsing(function (Request $request) {
            return app(AuthenticateUser::class)($request);
        });

        // Max attempts / lockout duration are Global Settings (Auth &
        // Security) fields — read via config('platform.*'), which
        // PlatformSetting::applyToConfig() keeps current, so a saved
        // change takes effect on the very next login attempt with no
        // restart/cache-clear needed. See SUPER_ADMIN_GLOBAL_SETTINGS_REPORT.md.
        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinutes(
                (int) config('platform.lockout_duration_minutes', 1),
                (int) config('platform.max_login_attempts', 5)
            )->by($throttleKey);
        });
    }
}
