# ErrorBoundary Component

## Overview

The ErrorBoundary component is a React Error Boundary that catches JavaScript errors anywhere in the component tree, logs those errors, and displays a fallback UI instead of crashing the entire application.

## Requirements Fulfilled

- **Requirement 15.1**: Display user-friendly error page when React component errors occur
- **Requirement 15.4**: Show detailed stack traces in development mode
- **Requirement 15.5**: Log errors to server in production without exposing sensitive information

## Features

### 1. Error Catching
- Catches all React component errors in the tree below it
- Prevents the entire application from crashing
- Provides graceful degradation

### 2. User-Friendly Error Display
- Shows a clean, professional error message
- Provides "Refresh Page" and "Try Again" buttons
- Displays error ID for support reference (production only)

### 3. Development Mode
- Shows detailed error stack traces
- Displays component stack for debugging
- Logs errors to browser console

### 4. Production Mode
- Hides sensitive error details from users
- Logs errors to server via `/api/log-error` endpoint
- Includes context: URL, user agent, timestamp, user ID, business unit

### 5. Server-Side Error Logging
- Endpoint: `POST /api/log-error`
- Controller: `App\Http\Controllers\ErrorLogController`
- Logs to Laravel log file with full context
- Validates and sanitizes error data

## Usage

### Basic Usage (Already Integrated)

The ErrorBoundary is already integrated at the app level in `resources/js/inertia/app.tsx`:

```tsx
import { ErrorBoundary } from '@/components/ErrorBoundary';

createRoot(el).render(
  <ErrorBoundary>
    <App {...props} />
  </ErrorBoundary>
);
```

### Custom Fallback UI

```tsx
import { ErrorBoundary } from '@/components/ErrorBoundary';

<ErrorBoundary fallback={<CustomErrorPage />}>
  <MyComponent />
</ErrorBoundary>
```

### With Error Handler Callback

```tsx
<ErrorBoundary onError={(error, errorInfo) => {
  // Custom error handling logic
  analytics.trackError(error);
}}>
  <MyComponent />
</ErrorBoundary>
```

### Using the Error Handler Hook

For programmatic error handling in functional components:

```tsx
import { useErrorHandler } from '@/components/ErrorBoundary';

function MyComponent() {
  const { handleError } = useErrorHandler();
  
  const handleClick = async () => {
    try {
      await riskyOperation();
    } catch (error) {
      handleError(error as Error);
    }
  };
  
  return <button onClick={handleClick}>Click me</button>;
}
```

### Using AsyncBoundary

For handling errors in lazy-loaded components:

```tsx
import { AsyncBoundary } from '@/components/ErrorBoundary';

<AsyncBoundary
  loadingFallback={<LoadingSpinner />}
  errorFallback={<ErrorMessage />}
>
  <LazyComponent />
</AsyncBoundary>
```

## Error Logging Flow

### Development Mode
1. Error occurs in React component
2. ErrorBoundary catches the error
3. Error is logged to browser console
4. Detailed stack trace is displayed to developer
5. User sees error message with stack trace

### Production Mode
1. Error occurs in React component
2. ErrorBoundary catches the error
3. Error is sent to `/api/log-error` endpoint
4. Server logs error with context to Laravel log
5. User sees friendly error message (no stack trace)
6. Error ID is displayed for support reference

## Server-Side Implementation

### Controller: `app/Http/Controllers/ErrorLogController.php`

```php
public function store(Request $request): JsonResponse
{
    $validated = $request->validate([
        'message' => 'required|string|max:1000',
        'stack' => 'nullable|string|max:5000',
        'componentStack' => 'nullable|string|max:5000',
        'url' => 'required|string|max:500',
        'userAgent' => 'required|string|max:500',
        'timestamp' => 'required|date',
    ]);

    Log::error('Frontend Error', [
        'message' => $validated['message'],
        'stack' => $validated['stack'] ?? null,
        'component_stack' => $validated['componentStack'] ?? null,
        'url' => $validated['url'],
        'user_agent' => $validated['userAgent'],
        'timestamp' => $validated['timestamp'],
        'user_id' => auth()->id(),
        'business_unit_id' => session('current_business_unit_id'),
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Error logged successfully',
    ]);
}
```

### Route: `routes/web.php`

```php
Route::prefix('api')->middleware(['auth'])->group(function () {
    Route::post('/log-error', [ErrorLogController::class, 'store'])
        ->name('api.log-error');
});
```

## Error Log Format

Errors are logged to `storage/logs/laravel.log` with the following context:

```
[2025-01-19 10:30:45] production.ERROR: Frontend Error
{
  "message": "Cannot read property 'foo' of undefined",
  "stack": "TypeError: Cannot read property 'foo' of undefined\n    at MyComponent...",
  "component_stack": "    in MyComponent\n    in ErrorBoundary\n    in App",
  "url": "https://example.com/dashboard",
  "user_agent": "Mozilla/5.0...",
  "timestamp": "2025-01-19T10:30:45.123Z",
  "user_id": 1,
  "business_unit_id": 2
}
```

## Testing

### Manual Testing

To test the ErrorBoundary, you can temporarily add a component that throws an error:

```tsx
function BrokenComponent() {
  throw new Error('Test error for ErrorBoundary');
  return <div>This will never render</div>;
}

// In your page
<BrokenComponent />
```

### Expected Behavior

**Development Mode:**
- Error message displayed
- Full stack trace visible
- Error logged to console
- "Refresh Page" and "Try Again" buttons available

**Production Mode:**
- Error message displayed
- No stack trace visible
- Error ID shown
- Error logged to server
- "Refresh Page" and "Try Again" buttons available

## Security Considerations

1. **No Sensitive Data Exposure**: Stack traces are only shown in development
2. **Input Validation**: Server validates all error data before logging
3. **Rate Limiting**: Consider adding rate limiting to prevent log spam
4. **Authentication**: Error logging endpoint requires authentication
5. **Data Sanitization**: Error messages are truncated to prevent log injection

## Monitoring

To monitor frontend errors in production:

1. Check Laravel logs: `storage/logs/laravel.log`
2. Search for "Frontend Error" entries
3. Use log aggregation tools (e.g., Sentry, LogRocket)
4. Set up alerts for error spikes

## Future Enhancements

- [ ] Integrate with error tracking service (Sentry, Bugsnag)
- [ ] Add error rate limiting on client side
- [ ] Implement error grouping and deduplication
- [ ] Add user feedback form in error UI
- [ ] Create admin dashboard for error monitoring
- [ ] Add automatic error recovery strategies

## Related Files

- `resources/js/inertia/components/ErrorBoundary.tsx` - Main component
- `resources/js/inertia/app.tsx` - Integration point
- `app/Http/Controllers/ErrorLogController.php` - Server-side logging
- `routes/web.php` - API route definition

## References

- [React Error Boundaries](https://react.dev/reference/react/Component#catching-rendering-errors-with-an-error-boundary)
- [Laravel Logging](https://laravel.com/docs/12.x/logging)
- [Inertia.js Error Handling](https://inertiajs.com/error-handling)
