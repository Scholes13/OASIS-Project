<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

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
        $exceptions->respond(function (Response $response, Throwable $exception, Request $request) {
            // Handle 419 Page Expired (CSRF Token Mismatch)
            // Redirect back with flash message instead of showing ugly error modal
            if ($response->getStatusCode() === 419) {
                return back()->with([
                    'error' => 'The page expired, please try again.',
                ]);
            }

            // Handle common HTTP errors in production with proper Inertia error page
            // In local/testing, let the default error handler show detailed debug info
            if (! app()->environment(['local', 'testing'])
                && in_array($response->getStatusCode(), [500, 503, 404, 403])
            ) {
                // Set root view explicitly since HandleInertiaRequests middleware
                // may not have run (e.g., 404 on non-existent routes)
                Inertia::setRootView('layouts.inertia');

                return Inertia::render('ErrorPage', [
                    'status' => $response->getStatusCode(),
                ])
                    ->toResponse($request)
                    ->setStatusCode($response->getStatusCode());
            }

            return $response;
        });
    })->create();
