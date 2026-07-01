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
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withBroadcasting(
        __DIR__.'/../routes/channels.php',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(\App\Http\Middleware\PreventSearchIndexing::class);

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
            'it.support.access' => \App\Http\Middleware\ITSupportAccess::class,
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->respond(function (Response $response, Throwable $exception, Request $request) {
            // Handle 419 Page Expired (CSRF Token Mismatch)
            // If the user is no longer authenticated, redirect to login instead of back
            // (going back to an authenticated page would just fail again)
            if ($response->getStatusCode() === 419) {
                if (! $request->user()) {
                    return redirect()->route('login')->with([
                        'error' => 'Your session has expired. Please log in again.',
                    ]);
                }

                return back()->with([
                    'error' => 'The page expired, please try again.',
                ]);
            }

            // Handle common HTTP errors with proper Inertia error page
            // Show custom error pages unless debug mode is intentionally enabled.
            if (in_array($response->getStatusCode(), [403, 404, 503])
                || (! config('app.debug') && ! app()->environment(['local', 'testing']) && $response->getStatusCode() === 500)
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
