<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Cache;
use App\Models\HospitalSetting;
use App\Models\PlatformSetting;
use App\Models\Subscription;
use Illuminate\Validation\Rules\Password;
use Laravel\Fortify\Fortify;

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
        // The app's only unauthenticated endpoints take credentials, so
        // they are what a guesser would hammer. Keyed by email + IP the way
        // Fortify keys the web login: a whole hospital often shares one
        // outbound IP, and one person's typo shouldn't lock out the rest.
        RateLimiter::for('api-login', function (Request $request) {
            $key = Str::transliterate(Str::lower((string) $request->input('email')) . '|' . $request->ip());

            return [Limit::perMinute(5)->by($key), Limit::perMinute(30)->by($request->ip())];
        });

        // Everything behind a token. Until this, one stolen token could
        // pull the whole patient database as fast as the server would
        // answer — 70 requests in a row drew no complaint at all.
        //
        // 120 a minute is well clear of ordinary use (a screen load is one
        // or two requests, and a person cannot open sixty screens a
        // minute) while making a bulk scrape take hours instead of
        // seconds. Keyed by the signed-in user so one account cannot spend
        // a whole hospital's allowance, and by IP for anything that gets
        // this far unauthenticated.
        RateLimiter::for('api', function (Request $request) {
            return $request->user()
                ? Limit::perMinute(120)->by('user:' . $request->user()->id)
                : Limit::perMinute(30)->by('ip:' . $request->ip());
        });

        Vite::useStyleTagAttributes(function (?string $src, string $url, ?array $chunk, ?array $manifest) {
            if ($src !== null) {
                return [
                    'class' => preg_match("/(resources\/assets\/vendor\/scss\/(rtl\/)?core)-?.*/i", $src) ? 'template-customizer-core-css' : (preg_match("/(resources\/assets\/vendor\/scss\/(rtl\/)?theme)-?.*/i", $src) ? 'template-customizer-theme-css' : '')
                ];
            }
            return [];
        });

Fortify::redirects('login', function () {
    $user = auth()->user();
    if ($user->hasRole('super_admin'))   return '/';
    if ($user->hasRole('admin'))         return '/';
    if ($user->hasRole('doctor'))        return '/';
    if ($user->hasRole('receptionist'))  return '/';
    if ($user->hasRole('lab_assistant')) return '/';
    if ($user->hasRole('pharmacist'))    return '/';
    if ($user->hasRole('nurse'))         return '/';
    if ($user->hasRole('staff'))         return '/';
    if ($user->hasRole('patient'))       return '/';
    return '/';
});

if (! (app()->runningInConsole() && app()->environment('testing'))) {
    try {
        if (Schema::hasTable('subscriptions') && Subscription::count() === 0) {
            Subscription::create([
                'hospital_name'    => config('app.name', 'HMS'),
                'plan'             => 'free_trial',
                'plan_id'          => Schema::hasTable('plans') ? \App\Models\Plan::where('key', 'free_trial')->value('id') : null,
                'trial_started_at' => now(),
                'expires_at'       => now()->addDays(14),
                'status'           => 'active',
            ]);
        }
    } catch (\Throwable $e) {

    }
}

if (app()->runningInConsole()) {
    return;
}

try {
    if (Schema::hasTable('hospital_settings')) {
        $settings = Cache::get('hospital_settings');
        $settings ? HospitalSetting::applyToConfig($settings) : HospitalSetting::refreshCache();
    }

    if (Schema::hasTable('subscriptions')) {
        $subscription = Cache::get('current_subscription');
        $subscription ? Subscription::applyToConfig($subscription) : Subscription::refreshCurrent();
    }

    if (Schema::hasTable('platform_settings')) {
        $platformSettings = Cache::get('platform_settings');
        $platformSettings ? PlatformSetting::applyToConfig($platformSettings) : PlatformSetting::refreshCache();
    }
} catch (\Throwable $e) {

}

        // Global Settings' password policy (Auth & Security) — the same
        // Password::default()/Password::defaults() rule every existing
        // password-entry point already calls (CreateNewUser, ResetUserPassword,
        // UserProfileController); this is the one place that rule is defined,
        // not a second validation mechanism.
        Password::defaults(function () {
            $rule = Password::min((int) config('platform.password_min_length', 8));

            if (config('platform.password_require_uppercase')) {
                $rule->mixedCase();
            }
            if (config('platform.password_require_number')) {
                $rule->numbers();
            }
            if (config('platform.password_require_symbol')) {
                $rule->symbols();
            }

            return $rule;
        });
    }


}
