<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Global middleware for Livewire timeout handling and auth persistence
        $middleware->web(append: [
            \App\Http\Middleware\SetTimeoutForLivewire::class,
            \App\Http\Middleware\EnsureLivewireAuthPersistence::class,
        ]);
        
        $middleware->alias([
            'ensure.business.unit.selected' => \App\Http\Middleware\EnsureBusinessUnitSelected::class,
            'check.business.unit.access' => \App\Http\Middleware\CheckBusinessUnitAccess::class,
            'admin.access' => \App\Http\Middleware\AdminAccess::class,
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'livewire.timeout' => \App\Http\Middleware\SetTimeoutForLivewire::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
