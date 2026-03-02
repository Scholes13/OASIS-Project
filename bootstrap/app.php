<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Global middleware for Inertia
        // EnsureBusinessUnitSelected MUST run before HandleInertiaRequests
        // so that navigation data has access to current_business_unit_id
        $middleware->web(append: [
            \App\Http\Middleware\EnsureBusinessUnitSelected::class,
            \App\Http\Middleware\HandleInertiaRequests::class,
        ]);

        // Sanctum stateful middleware for SPA authentication
        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);

        $middleware->alias([
            'ensure.business.unit.selected' => \App\Http\Middleware\EnsureBusinessUnitSelected::class,
            'check.business.unit.access' => \App\Http\Middleware\CheckBusinessUnitAccess::class,
            'admin.access' => \App\Http\Middleware\AdminAccess::class,
            'purchasing.admin.access' => \App\Http\Middleware\PurchasingAdminAccess::class,
            'activity.reporting.access' => \App\Http\Middleware\ActivityReportingAccess::class,
            'activity.admin.access' => \App\Http\Middleware\ActivityAdminAccess::class,
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
