<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ErrorLogController extends Controller
{
    /**
     * Log a frontend error to the Laravel backend.
     * 
     * This endpoint receives error details from the React frontend and logs them
     * with user context for debugging and monitoring purposes.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => 'required|string|max:1000',
            'stack' => 'nullable|string|max:5000',
            'url' => 'nullable|string|max:500',
            'userAgent' => 'nullable|string|max:500',
            'timestamp' => 'nullable|string',
            'level' => 'nullable|string|in:error,warning,info',
            'context' => 'nullable|array',
        ]);

        // Get user context
        $user = $request->user();
        $userContext = $user ? [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'user_email' => $user->email,
            'business_unit_id' => session('current_business_unit_id'),
            'business_unit_name' => session('current_business_unit_name'),
        ] : [
            'user_id' => null,
            'user_name' => 'Guest',
            'user_email' => null,
        ];

        // Build log context
        $logContext = [
            'source' => 'frontend',
            'url' => $validated['url'] ?? $request->header('Referer'),
            'user_agent' => $validated['userAgent'] ?? $request->header('User-Agent'),
            'ip_address' => $request->ip(),
            'timestamp' => $validated['timestamp'] ?? now()->toIso8601String(),
            'user' => $userContext,
            'stack' => $validated['stack'] ?? null,
            'additional_context' => $validated['context'] ?? [],
        ];

        // Determine log level
        $level = $validated['level'] ?? 'error';
        $message = $validated['message'];

        // Log based on level
        match ($level) {
            'warning' => Log::warning("[Frontend] {$message}", $logContext),
            'info' => Log::info("[Frontend] {$message}", $logContext),
            default => Log::error("[Frontend] {$message}", $logContext),
        };

        // In production, you might want to send critical errors to external monitoring
        // services like Sentry, Bugsnag, or Rollbar
        if (app()->environment('production') && $level === 'error') {
            // Example: Send to external monitoring service
            // Sentry::captureException(new \Exception($message));
        }

        return response()->json([
            'success' => true,
            'message' => 'Error logged successfully',
        ], 200);
    }

    /**
     * Log multiple frontend errors in batch.
     * 
     * Useful for logging multiple errors that occurred during a session.
     */
    public function storeBatch(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'errors' => 'required|array|max:50',
            'errors.*.message' => 'required|string|max:1000',
            'errors.*.stack' => 'nullable|string|max:5000',
            'errors.*.url' => 'nullable|string|max:500',
            'errors.*.timestamp' => 'nullable|string',
            'errors.*.level' => 'nullable|string|in:error,warning,info',
            'errors.*.context' => 'nullable|array',
        ]);

        $user = $request->user();
        $userContext = $user ? [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'user_email' => $user->email,
            'business_unit_id' => session('current_business_unit_id'),
            'business_unit_name' => session('current_business_unit_name'),
        ] : [
            'user_id' => null,
            'user_name' => 'Guest',
            'user_email' => null,
        ];

        $loggedCount = 0;

        foreach ($validated['errors'] as $error) {
            $logContext = [
                'source' => 'frontend_batch',
                'url' => $error['url'] ?? $request->header('Referer'),
                'user_agent' => $request->header('User-Agent'),
                'ip_address' => $request->ip(),
                'timestamp' => $error['timestamp'] ?? now()->toIso8601String(),
                'user' => $userContext,
                'stack' => $error['stack'] ?? null,
                'additional_context' => $error['context'] ?? [],
            ];

            $level = $error['level'] ?? 'error';
            $message = $error['message'];

            match ($level) {
                'warning' => Log::warning("[Frontend Batch] {$message}", $logContext),
                'info' => Log::info("[Frontend Batch] {$message}", $logContext),
                default => Log::error("[Frontend Batch] {$message}", $logContext),
            };

            $loggedCount++;
        }

        return response()->json([
            'success' => true,
            'message' => "Logged {$loggedCount} errors successfully",
            'count' => $loggedCount,
        ], 200);
    }
}

