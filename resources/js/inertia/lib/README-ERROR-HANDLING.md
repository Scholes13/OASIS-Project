# Error Handling System

## Overview

The application includes a comprehensive global error handling system that automatically handles Inertia errors, validation errors, network errors, and displays user-friendly toast notifications.

## Features

### Automatic Error Handling

The error handling system is automatically initialized when the app starts and handles:

1. **HTTP Error Codes** (400-599)
   - 400: Bad Request
   - 401: Unauthorized (redirects to login)
   - 403: Forbidden
   - 404: Not Found
   - 419: Session Expired
   - 422: Validation Errors
   - 429: Too Many Requests
   - 500+: Server Errors

2. **Validation Errors** (422 status)
   - Displays field-level validation errors
   - Shows up to 3 errors with a count of remaining errors
   - Automatically formats error messages

3. **Network Errors**
   - Detects when the server is unreachable
   - Shows appropriate "Network Error" message

4. **Flash Messages**
   - Automatically displays Laravel flash messages
   - Supports: success, error, warning, info

5. **Unhandled Errors**
   - Catches unhandled promise rejections
   - Catches global JavaScript errors
   - Logs errors to console for debugging

## Usage

### Automatic Handling

Most errors are handled automatically. You don't need to do anything special:

```typescript
import { router } from '@inertiajs/react';

// This will automatically show error toasts if it fails
router.post('/purchase-requests', data);
```

### Manual Error Handling

For try-catch blocks or custom error handling:

```typescript
import { handleError, handleFormError } from '@/lib/errorHandlers';

try {
    // Some code that might throw
    await someAsyncOperation();
} catch (error) {
    handleError(error, 'Failed to perform operation');
}

// For form validation errors
if (errors) {
    handleFormError(errors);
}
```

### Using Toast Directly

For custom notifications:

```typescript
import { showToast } from '@/components/ui/toast';

// Success
showToast.success('Success!', 'Operation completed successfully');

// Error
showToast.error('Error!', 'Something went wrong');

// Warning
showToast.warning('Warning!', 'Please be careful');

// Info
showToast.info('Info', 'Here is some information');

// Loading (returns toast ID for dismissal)
const toastId = showToast.loading('Processing...');
// Later...
showToast.dismiss(toastId);

// Promise-based (shows loading, then success/error)
showToast.promise(
    fetchData(),
    {
        loading: 'Loading data...',
        success: 'Data loaded successfully!',
        error: 'Failed to load data',
    }
);
```

## Error Event Listeners

The system listens to the following Inertia events:

- `inertia:error` - HTTP errors from server responses
- `inertia:success` - Successful requests (for flash messages)
- `inertia:invalid` - Validation errors
- `inertia:exception` - Unhandled exceptions

## Flash Messages from Laravel

To show flash messages from Laravel controllers:

```php
// In your controller
return redirect()->back()->with('success', 'Purchase request created successfully!');
return redirect()->back()->with('error', 'Failed to create purchase request.');
return redirect()->back()->with('warning', 'Please review your input.');
return redirect()->back()->with('info', 'Your request is being processed.');
```

These will automatically be displayed as toast notifications.

## Validation Errors from Laravel

Laravel validation errors are automatically handled:

```php
// In your controller
$request->validate([
    'name' => 'required|string|max:255',
    'email' => 'required|email',
]);
```

If validation fails, the errors will be displayed as a toast notification with field names and messages.

## Configuration

### Toast Position

You can configure the toast position in `AppLayout.tsx`:

```typescript
<Toaster 
    position="top-right"  // top-left, top-right, bottom-left, bottom-right, top-center, bottom-center
    richColors={true}     // Use colored backgrounds
    closeButton={true}    // Show close button
    duration={5000}       // Auto-dismiss after 5 seconds
/>
```

### Custom Error Messages

To customize error messages for specific HTTP status codes, edit `errorHandlers.ts`:

```typescript
const ERROR_MESSAGES: Record<number, string> = {
    400: 'Your custom message for 400 errors',
    // ... other status codes
};
```

## Testing Error Handling

To test error handling in development:

1. **Test 404 Error**: Navigate to a non-existent route
2. **Test Validation**: Submit a form with invalid data
3. **Test Network Error**: Disconnect from internet and try an action
4. **Test Flash Messages**: Use Laravel's `with()` method in controllers

## Debugging

All errors are logged to the browser console with context:

```
Inertia Exception: Error details...
Global Error: Error details...
Unhandled Promise Rejection: Error details...
```

Check the console for detailed error information during development.

## Best Practices

1. **Let the system handle errors automatically** - Don't wrap every Inertia call in try-catch
2. **Use flash messages for user feedback** - Return flash messages from Laravel controllers
3. **Use manual error handling only when needed** - For custom error logic or non-Inertia operations
4. **Provide context in error messages** - Use descriptive messages that help users understand what went wrong
5. **Log errors for debugging** - The system automatically logs errors to console

## Requirements Validation

This implementation satisfies:

- **Requirement 15.2**: Handle Inertia error events ✅
- **Requirement 15.3**: Display appropriate toast messages for different error codes ✅
- Handles validation errors (422) ✅
- Handles network errors ✅
- Handles authentication errors (401, 419) ✅
- Handles permission errors (403) ✅
- Handles server errors (500+) ✅
- Displays flash messages automatically ✅
