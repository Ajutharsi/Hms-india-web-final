<?php

namespace App\Actions\Fortify;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Fortify;

class AuthenticateUser
{
    public function __invoke(Request $request): ?User
    {
        $request->validate([
            Fortify::username() => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where(
            Fortify::username(),
            $request->input(Fortify::username())
        )->first();

        // incorrect
        if (!$user || !Hash::check($request->input('password'), $user->password)) {
            // Failed Logins — Audit & Activity Centre. Logs the attempted
            // identifier (already non-secret — it's the login form's own
            // username/email field), never the password. See
            // SUPER_ADMIN_AUDIT_ACTIVITY_REPORT.md.
            ActivityLog::log(
                'login_failed',
                'auth',
                "Failed login attempt for \"{$request->input(Fortify::username())}\"",
                $user
            );

            throw ValidationException::withMessages([
                'login' => 'Invalid email or password.',
            ]);
        }

        // Remember Me — duration is a Global Settings (Auth & Security)
        // field; Laravel has no first-class config key for this (it's a
        // guard property, default 400 days), so it's set directly on the
        // guard here. The actual `login()` call is deliberately left to
        // Fortify's own pipeline (AttemptToAuthenticate::handleUsingCustomCallback,
        // which calls `$this->guard->login($user, $request->boolean('remember'))`
        // right after this action returns) — that pipeline treats anything
        // falsy returned from this action as a FAILED login, so calling
        // login() here and returning null (as a previous version of this
        // action did) made every successful "remember me" login look like
        // a failed one to Fortify. `auth()->guard()` resolves the same
        // cached guard instance Fortify's pipeline logs in on, so the
        // custom duration set here still applies. See
        // SUPER_ADMIN_GLOBAL_SETTINGS_REPORT.md.
        if ($request->boolean('remember')) {
            $days = (int) config('platform.remember_me_days', 400);
            auth()->guard()->setRememberDuration(max(1, $days) * 1440);
        }

        return $user;
    }
}