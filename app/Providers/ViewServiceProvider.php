<?php

namespace App\Providers;

use App\Models\Drug;
use App\Models\HospitalSetting;
use App\Services\DashboardService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ViewServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        /*
         * PERFORMANCE NOTE: View::composer('*') fires for EVERY rendered view,
         * including every @include/partial — previously that meant the two
         * Drug COUNT queries below ran dozens of times per request.
         *
         * Fix: everything here is memoized per-request so the work happens
         * exactly once, and the drug counters are additionally cached for 60s
         * and scoped to the few views that render them.
         *
         * A raw `static` variable here would memoize per PHP *process*, not
         * per request — harmless under classic PHP-FPM (one process dies per
         * request) but wrong wherever a process outlives a single request:
         * PHPUnit feature tests that fire several simulated requests in one
         * test method (e.g. impersonation start/dashboard/stop), and Octane
         * workers in production. In both cases a stale 'currentRole' (and
         * the rest of $shared) from an earlier request/user would leak into
         * a later one. Scoping the memo to the container instance and
         * clearing it on RequestHandled ties it to the actual request
         * boundary instead of process lifetime.
         */
        View::composer('*', function ($view) {
            if (! $this->app->bound('view.composer.shared')) {
                $subscription = Cache::get('current_subscription');

                // Row-level feature overrides (see
                // SUPER_ADMIN_FEATURE_MANAGEMENT_MULTI_TENANCY_REPORT.md) — a
                // user linked to a specific hospital (today: admin accounts
                // created via "Login as Hospital Admin", real per-hospital
                // hospital_id) sees THAT hospital's own override if one has
                // been set, instead of always the single global row. This is
                // the only path that costs an extra query; every other role
                // (no hospital_id) keeps the exact same cached, zero-extra-query
                // behavior as before.
                $hospitalId = auth()->user()?->hospital_id;

                if ($hospitalId) {
                    $hospitalSettings = HospitalSetting::forHospital($hospitalId);
                    $hospitalFeatures = $hospitalSettings->features_enabled ?? HospitalSetting::DEFAULT_FEATURES;
                } else {
                    // Cache-aside with write-back: a miss re-populates the cache via the
                    // model's own refreshCache() instead of querying fresh on every request.
                    // Falls back to an empty, unsaved model (never a bare null) if no
                    // settings row exists yet — every view reading this expects an object.
                    $hospitalSettings = Cache::get('hospital_settings') ?? HospitalSetting::refreshCache() ?? new HospitalSetting();
                    $hospitalFeatures = config('hospital.features', HospitalSetting::DEFAULT_FEATURES);
                }

                $this->app->instance('view.composer.shared', [
                    'hospitalSettings'     => $hospitalSettings,
                    'hospitalFeatures'     => $hospitalFeatures,
                    'currentRole'          => auth()->user()?->roles->first()?->name ?? 'guest',
                    'currentSubscription'  => $subscription,
                    'subscriptionDaysLeft' => $subscription ? $subscription->daysRemaining() : 0,
                ]);
            }

            $view->with($this->app->make('view.composer.shared'));
        });

        $this->app['events']->listen(\Illuminate\Foundation\Http\Events\RequestHandled::class, function () {
            if ($this->app->bound('view.composer.shared')) {
                $this->app->forgetInstance('view.composer.shared');
            }
        });

        // NOTE: the dashboard used to get a `detailStats` composer here that forced
        // DashboardService::detailStats() (~30 queries) to run on every dashboard
        // render for every role, whether that role's UI used it or not. Removed —
        // SuperAdminDashboardController and AdminDashboardController (the only two
        // roles whose UI reads detailStats) now pass it explicitly themselves.

        // Drug alert counters — only the views that render them, cached 60s.
        View::composer([
            'layouts.sections.menu.verticalMenu',
            'blood-bank.index',
            'pharmacy.drugs.index',
        ], function ($view) {
            $view->with('lowStockCount', Cache::remember('alerts.low_stock_count', 60, fn() =>
                Drug::where('current_stock', '<=', config('hospital.low_stock', 10))
                    ->where('status', 'active')
                    ->count()
            ));

            // Same definition and same cache key DashboardService::systemAlerts() uses
            // (DashboardService::nearExpiryDrugsCount()) — previously this composer had
            // its own separate implementation with a different filter, so this badge and
            // the System Alerts panel could show two different "near expiry" numbers on
            // the same page.
            $view->with('nearExpiryCount', app(DashboardService::class)->nearExpiryDrugsCount());
        });

        // Sidebar menu badges — previously inline queries in verticalMenu.blade.php.
        View::composer('layouts.sections.menu.verticalMenu', function ($view) {
            $view->with('pendingLabCount', Cache::remember('alerts.pending_lab_count', 60, fn() =>
                \App\Models\LabOrder::where('status', 'pending')->count()
            ));
            $view->with('openInvoiceCount', Cache::remember('alerts.open_invoice_count', 60, fn() =>
                \App\Models\Invoice::whereIn('status', ['sent', 'partial', 'overdue'])->count()
            ));
        });
    }
}
