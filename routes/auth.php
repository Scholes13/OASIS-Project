<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    // Login - React/Inertia
    Route::get('login', [LoginController::class, 'show'])->name('login');
    Route::post('login', [LoginController::class, 'login']);

    // Password reset routes - redirect to login with message for now
    // TODO: Implement React/Inertia password reset pages
    Route::get('forgot-password', function () {
        return redirect()->route('login')->with('info', 'Password reset functionality coming soon. Please contact administrator.');
    })->name('password.request');

    Route::get('reset-password/{token}', function () {
        return redirect()->route('login')->with('info', 'Password reset functionality coming soon. Please contact administrator.');
    })->name('password.reset');
});

Route::middleware('auth')->group(function () {
    // Email verification routes - redirect to dashboard for now
    // TODO: Implement React/Inertia email verification pages
    Route::get('verify-email', function () {
        return redirect()->route('dashboard')->with('info', 'Email verification functionality coming soon.');
    })->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::get('confirm-password', function () {
        return redirect()->route('dashboard')->with('info', 'Password confirmation functionality coming soon.');
    })->name('password.confirm');

    // Logout routes - GET for direct navigation, POST for form submission
    Route::get('logout', [LoginController::class, 'logout'])->name('logout');
    Route::post('logout', [LoginController::class, 'logout']);
});
