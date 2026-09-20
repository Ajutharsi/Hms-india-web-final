<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\HospitalSetting;
use App\Models\PlatformSetting;
use App\Models\User;
use App\Mail\PlatformTestEmail;
use App\Services\SystemHealthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Super Admin Global Settings — see SUPER_ADMIN_GLOBAL_SETTINGS_REPORT.md.
 * A brand-new, platform-owner-only settings screen, deliberately separate
 * from the existing `SettingsController`/`HospitalSetting` (this
 * installation's own operational/clinical settings, kept untouched per
 * SUPER_ADMIN_MASTER_PLAN.md §4.3/§5.8). Platform Features reuses
 * `HospitalSetting::features_enabled` directly rather than introducing a
 * second toggle store.
 */
class GlobalSettingsController extends Controller
{
    private const MODULE = 'platform_settings';

    public function __construct(private readonly SystemHealthService $health)
    {
    }

    public function index()
    {
        $settings = PlatformSetting::current();
        $hospitalSettings = HospitalSetting::get();

        return view('super-admin.settings.index', [
            'settings'        => $settings,
            'features'        => $hospitalSettings->features_enabled ?? HospitalSetting::DEFAULT_FEATURES,
            'featureModules'  => HospitalSetting::DEFAULT_FEATURES,
            'systemInfo'      => [
                'application' => $this->health->application(),
                'database'    => $this->health->database(),
                'cache'       => $this->health->cache(),
                'queue'       => $this->health->queue(),
                'storage'     => $this->health->storage(),
            ],
            'isDownForMaintenance' => app()->isDownForMaintenance(),
            'timezones'       => \DateTimeZone::listIdentifiers(),
        ]);
    }

    /** Logs only the fields that actually changed — the first real consumer of ActivityLog's old/new-value columns. */
    private function logChanges(PlatformSetting $settings, array $before, string $action, string $description): void
    {
        $after = $settings->only(array_keys($before));
        $old = [];
        $new = [];

        foreach ($before as $key => $value) {
            if ($value !== $after[$key]) {
                $old[$key] = $value;
                $new[$key] = $after[$key];
            }
        }

        if (empty($old)) {
            return;
        }

        ActivityLog::log($action, self::MODULE, $description, $settings, $old, $new);
    }

    public function updatePlatform(Request $request)
    {
        $validated = $request->validate([
            'platform_name'        => 'required|string|max:255',
            'company_name'         => 'nullable|string|max:255',
            'platform_description' => 'nullable|string|max:1000',
            'support_email'        => 'nullable|email|max:255',
            'support_phone'        => 'nullable|string|max:20',
            'website'              => 'nullable|string|max:255',
            'copyright_text'       => 'nullable|string|max:255',
            'logo'                 => 'nullable|image|max:2048',
            'favicon'              => 'nullable|image|max:512',
        ]);

        $settings = PlatformSetting::current();
        $before = $settings->only(['platform_name', 'company_name', 'platform_description', 'support_email', 'support_phone', 'website', 'copyright_text']);
        $data = collect($validated)->except(['logo', 'favicon'])->toArray();

        if ($request->hasFile('logo')) {
            if ($settings->logo_path) {
                Storage::disk('public')->delete($settings->logo_path);
            }
            $data['logo_path'] = $request->file('logo')->store('platform', 'public');
        }
        if ($request->hasFile('favicon')) {
            if ($settings->favicon_path) {
                Storage::disk('public')->delete($settings->favicon_path);
            }
            $data['favicon_path'] = $request->file('favicon')->store('platform', 'public');
        }

        $settings->update($data);
        $this->logChanges($settings, $before, 'platform_settings_updated', 'Platform branding settings updated');

        return redirect()->route('super-admin.settings.index')->with('success', 'Platform settings updated.');
    }

    public function updateRegional(Request $request)
    {
        $validated = $request->validate([
            'default_country'     => 'nullable|string|max:255',
            'default_currency'    => 'nullable|string|max:10',
            'default_timezone'    => 'nullable|string|max:100|timezone',
            'default_language'    => 'nullable|string|max:10',
            'default_date_format' => 'nullable|string|max:30',
            'default_time_format' => 'nullable|in:12h,24h',
        ]);

        $settings = PlatformSetting::current();
        $before = $settings->only(array_keys($validated));
        $settings->update($validated);
        $this->logChanges($settings, $before, 'regional_settings_updated', 'Regional settings updated');

        return redirect()->route('super-admin.settings.index')->with('success', 'Regional settings updated.');
    }

    public function updateSecurity(Request $request)
    {
        $validated = $request->validate([
            'password_min_length'      => 'required|integer|min:6|max:32',
            'session_timeout_minutes'  => 'required|integer|min:5|max:1440',
            'remember_me_days'         => 'required|integer|min:1|max:3650',
            'max_login_attempts'       => 'required|integer|min:3|max:20',
            'lockout_duration_minutes' => 'required|integer|min:1|max:1440',
        ]);

        $validated['password_require_uppercase'] = $request->boolean('password_require_uppercase');
        $validated['password_require_number']    = $request->boolean('password_require_number');
        $validated['password_require_symbol']    = $request->boolean('password_require_symbol');
        $validated['two_factor_enabled']         = $request->boolean('two_factor_enabled');

        $settings = PlatformSetting::current();
        $before = $settings->only(array_keys($validated));
        $settings->update($validated);
        $this->logChanges($settings, $before, 'security_settings_updated', 'Authentication & security settings updated');

        return redirect()->route('super-admin.settings.index')->with('success', 'Security settings updated.');
    }

    public function updateEmail(Request $request)
    {
        $validated = $request->validate([
            'mail_mailer'       => 'nullable|in:smtp,log,array,sendmail',
            'mail_host'         => 'nullable|string|max:255',
            'mail_port'         => 'nullable|integer|min:1|max:65535',
            'mail_encryption'   => 'nullable|in:tls,ssl,none',
            'mail_username'     => 'nullable|string|max:255',
            'mail_password'     => 'nullable|string|max:255',
            'mail_from_name'    => 'nullable|string|max:255',
            'mail_from_address' => 'nullable|email|max:255',
        ]);

        $settings = PlatformSetting::current();
        // Never diff/log the password itself into ActivityLog — see §Security.
        $before = collect($validated)->except('mail_password')->keys()
            ->mapWithKeys(fn ($k) => [$k => $settings->$k])->all();

        // A blank password field means "leave the stored credential
        // unchanged" — the form never renders the real value back, so an
        // empty submit must not silently wipe a working SMTP password.
        if (! $request->filled('mail_password')) {
            unset($validated['mail_password']);
        }

        $settings->update($validated);
        $this->logChanges($settings, $before, 'email_settings_updated', 'Email (SMTP) settings updated');

        return redirect()->route('super-admin.settings.index')->with('success', 'Email settings updated.');
    }

    public function sendTestEmail(Request $request)
    {
        $request->validate(['test_email' => 'required|email']);

        try {
            Mail::to($request->test_email)->send(new PlatformTestEmail(config('platform.name', config('app.name'))));

            ActivityLog::log('test_email_sent', self::MODULE, "Test email sent to {$request->test_email} via mailer [" . config('mail.default') . ']');

            return redirect()->route('super-admin.settings.index')->with('success', "Test email sent to {$request->test_email} (mailer: " . config('mail.default') . ').');
        } catch (\Throwable $e) {
            ActivityLog::log('test_email_failed', self::MODULE, "Test email to {$request->test_email} failed: " . $e->getMessage());

            return redirect()->route('super-admin.settings.index')->with('error', 'Test email failed: ' . $e->getMessage());
        }
    }

    /** Reuses HospitalSetting::features_enabled directly — no second feature-flag store. */
    public function updateFeatures(Request $request)
    {
        $request->validate(['features' => 'nullable|array', 'features.*' => 'boolean']);

        $hospitalSettings = HospitalSetting::first() ?? HospitalSetting::create(HospitalSetting::get()->toArray());
        $before = $hospitalSettings->features_enabled ?? HospitalSetting::DEFAULT_FEATURES;

        $features = HospitalSetting::DEFAULT_FEATURES;
        foreach (array_keys($features) as $key) {
            $features[$key] = $request->boolean("features.$key");
        }

        $hospitalSettings->update(['features_enabled' => $features]);

        $changed = array_diff_assoc($features, $before);
        if (! empty($changed)) {
            ActivityLog::log('platform_features_updated', self::MODULE, 'Platform feature toggles updated', $hospitalSettings, $before, $features);
        }

        return redirect()->route('super-admin.settings.index')->with('success', 'Feature toggles updated.');
    }

    public function resetDefaults()
    {
        $settings = PlatformSetting::current();
        $before = $settings->toArray();

        // Deliberately excludes mail_* credentials and maintenance_message —
        // resetting those could silently break a working SMTP integration
        // or wipe an in-progress maintenance notice; "safe" defaults only.
        $settings->update(PlatformSetting::defaults());

        ActivityLog::log('platform_settings_reset', self::MODULE, 'Global Settings reset to defaults', $settings, $before, PlatformSetting::defaults());

        return redirect()->route('super-admin.settings.index')->with('success', 'Settings reset to defaults.');
    }

    public function enableMaintenance(Request $request)
    {
        $request->validate(['message' => 'nullable|string|max:500']);

        $settings = PlatformSetting::current();
        $settings->update(['maintenance_message' => $request->message]);

        $secret = Str::random(32);

        try {
            // This Laravel version's `down` command has no `--message`
            // option — the message is instead read straight from
            // PlatformSetting (just saved above) by resources/views/errors/503.blade.php.
            Artisan::call('down', [
                '--secret' => $secret,
                '--retry'  => 60,
            ]);
        } catch (\Throwable $e) {
            return redirect()->route('super-admin.settings.index')->with('error', 'Could not enable maintenance mode: ' . $e->getMessage());
        }

        ActivityLog::log('maintenance_enabled', self::MODULE, 'Maintenance mode enabled: ' . auth()->user()->name);

        // Route the acting super admin through the bypass-secret URL so
        // their own browser can keep using the app (including turning
        // maintenance back off) while every other visitor sees the
        // maintenance page — Laravel's own built-in mechanism, not a
        // custom bypass.
        return redirect('/' . $secret);
    }

    public function disableMaintenance()
    {
        try {
            Artisan::call('up');
        } catch (\Throwable $e) {
            return redirect()->route('super-admin.settings.index')->with('error', 'Could not disable maintenance mode: ' . $e->getMessage());
        }

        ActivityLog::log('maintenance_disabled', self::MODULE, 'Maintenance mode disabled: ' . auth()->user()->name);

        return redirect()->route('super-admin.settings.index')->with('success', 'Maintenance mode disabled.');
    }

    /** Flags every user account to change their password on next request — see RequirePasswordChange middleware. */
    public function forcePasswordChange()
    {
        $count = User::query()->update(['must_change_password' => true]);

        ActivityLog::log('force_password_change', self::MODULE, "Forced password change for {$count} user account(s)");

        return redirect()->route('super-admin.settings.index')->with('success', "Password change required for {$count} user account(s) on next login.");
    }
}
