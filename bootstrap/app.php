<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\LocaleMiddleware;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Every API route, authenticated or not. The login group keeps its
        // own tighter `api-login` limiter on top of this.
        $middleware->api(prepend: ['throttle:api']);

        $middleware->web(LocaleMiddleware::class);
        $middleware->web(\App\Http\Middleware\CheckSubscription::class);
        $middleware->web(\App\Http\Middleware\RequirePasswordChange::class);
        $middleware->web(\App\Http\Middleware\LogPermissionDenials::class);

        // Global Settings must always be reachable so a super_admin can
        // turn maintenance mode back off even without the bypass-secret
        // cookie (e.g. a different browser/device than the one that
        // enabled it) — see SUPER_ADMIN_GLOBAL_SETTINGS_REPORT.md.
        $middleware->preventRequestsDuringMaintenance(except: [
            'super-admin/settings*',
        ]);
        $middleware->api(prepend: [EnsureFrontendRequestsAreStateful::class]);

        $middleware->alias([
            'role'               => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission'         => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'subscription'       => \App\Http\Middleware\CheckSubscription::class,
            'password.change'    => \App\Http\Middleware\RequirePasswordChange::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
