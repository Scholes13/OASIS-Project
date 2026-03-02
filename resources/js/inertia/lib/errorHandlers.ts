import { router } from '@inertiajs/react';
import { showToast, toast } from '@/components/ui/toast';
import { logError, logErrorObject, logWarning } from './errorLogger';

/**
 * Error code to user-friendly message mapping
 */
const ERROR_MESSAGES: Record<number, string> = {
    400: 'Bad request. Please check your input.',
    401: 'You are not authenticated. Please log in.',
    403: 'You do not have permission to perform this action.',
    404: 'The requested resource was not found.',
    405: 'This action is not allowed.',
    408: 'Request timeout. Please try again.',
    409: 'A conflict occurred. Please refresh and try again.',
    419: 'Your session has expired. Please refresh the page.',
    422: 'Validation failed. Please check your input.',
    429: 'Too many requests. Please slow down.',
    500: 'A server error occurred. Please try again later.',
    502: 'Bad gateway. The server is temporarily unavailable.',
    503: 'Service unavailable. Please try again later.',
    504: 'Gateway timeout. The server took too long to respond.',
};

/**
 * Get user-friendly error message for HTTP status code
 */
function getErrorMessage(status: number, defaultMessage?: string): string {
    return ERROR_MESSAGES[status] || defaultMessage || 'An unexpected error occurred.';
}

/**
 * Handle validation errors (422 status)
 */
function handleValidationErrors(errors: Record<string, string | string[]>): void {
    const errorMessages = Object.entries(errors)
        .map(([field, messages]) => {
            const messageArray = Array.isArray(messages) ? messages : [messages];
            return `${field}: ${messageArray.join(', ')}`;
        })
        .slice(0, 3); // Show max 3 validation errors

    const errorCount = Object.keys(errors).length;
    const moreErrors = errorCount > 3 ? ` (+${errorCount - 3} more)` : '';

    showToast.error(
        'Validation Failed',
        errorMessages.join('\n') + moreErrors
    );
}

/**
 * Handle network errors (no response from server)
 */
function handleNetworkError(): void {
    const errorMessage = 'Unable to connect to the server. Please check your internet connection.';

    showToast.error(
        'Network Error',
        errorMessage
    );

    // Log network error to backend (will be queued and sent when connection is restored)
    logError(errorMessage, {
        level: 'error',
        context: {
            errorType: 'network',
        },
    });
}

/**
 * Handle Inertia error events
 * 
 * Note: Inertia fires this event when a non-2xx response is received.
 * For 422 validation errors, Inertia handles them internally via onError callback,
 * so we should NOT show duplicate toasts for those.
 */
function handleInertiaError(event: CustomEvent): void {
    const { detail } = event;
    const response = detail?.response;

    // If there's no response object, check if this is truly a network error
    // or just Inertia's internal error handling (e.g., validation errors)
    if (!response) {
        // Only show network error if we're actually offline or the request truly failed
        if (!navigator.onLine) {
            handleNetworkError();
        }
        // Otherwise, silently ignore — the component's onError callback handles it
        return;
    }

    const status = response.status;
    const data = response.data;

    // Skip 422 validation errors — these are handled by component onError callbacks
    if (status === 422) {
        return;
    }

    // Log error to backend
    logError(`HTTP ${status} Error`, {
        level: status >= 500 ? 'error' : 'warning',
        context: {
            errorType: 'http',
            statusCode: status,
            responseData: data,
            url: window.location.href,
        },
    });

    // Handle authentication errors (401)
    if (status === 401) {
        showToast.error(
            'Authentication Required',
            'Please log in to continue.'
        );
        setTimeout(() => {
            router.visit('/login');
        }, 2000);
        return;
    }

    // Handle session expired (419)
    if (status === 419) {
        showToast.error(
            'Session Expired',
            'Your session has expired. Refreshing page...'
        );
        setTimeout(() => {
            window.location.reload();
        }, 1500);
        return;
    }

    // Handle permission errors (403)
    if (status === 403) {
        showToast.error(
            'Access Denied',
            getErrorMessage(status)
        );
        return;
    }

    // Handle not found errors (404)
    if (status === 404) {
        showToast.error(
            'Not Found',
            getErrorMessage(status)
        );
        return;
    }

    // Handle server errors (500+)
    if (status >= 500) {
        showToast.error(
            'Server Error',
            getErrorMessage(status)
        );
        return;
    }

    // Handle other client errors (400-499)
    if (status >= 400 && status < 500) {
        showToast.error(
            'Error',
            getErrorMessage(status, data?.message)
        );
        return;
    }

    // Fallback for unknown errors
    showToast.error(
        'Error',
        data?.message || 'An unexpected error occurred.'
    );
}

/**
 * Handle Inertia success events (for flash messages)
 * 
 * Uses toast IDs to prevent duplicate toasts when components
 * already show their own success/error feedback.
 */
function handleInertiaSuccess(event: CustomEvent): void {
    const { detail } = event;
    const page = detail?.page;

    if (!page?.props?.flash) {
        return;
    }

    const flash = page.props.flash;

    // Show error flash — these are important and usually not handled by components
    if (flash.error) {
        toast.error('Error', {
            description: flash.error,
            id: 'flash-error',
        });
    }

    // Show warning message
    if (flash.warning) {
        toast.warning('Warning', {
            description: flash.warning,
            id: 'flash-warning',
        });
    }

    // Show info message
    if (flash.info) {
        toast.info('Info', {
            description: flash.info,
            id: 'flash-info',
        });
    }

    // NOTE: flash.success is intentionally NOT shown here.
    // Components handle their own success toasts via onSuccess callbacks
    // (e.g., KanbanBoard, ActivityDataTable, TaskDetailModal).
    // Showing it here would cause duplicate toasts.
}

/**
 * Handle Inertia invalid events (non-Inertia responses)
 * 
 * This fires when the server returns a non-Inertia response (e.g., plain HTML).
 * Validation errors (422) are already handled by component onError callbacks,
 * so we skip those here.
 */
function handleInertiaInvalid(event: CustomEvent): void {
    // Don't show anything — validation errors are handled by components
    // and other invalid responses are edge cases (e.g., server returning HTML)
    const { detail } = event;
    const response = detail?.response;
    
    if (response?.status && response.status !== 422) {
        logWarning(`Received non-Inertia response (${response.status})`, {
            context: {
                errorType: 'inertia_invalid',
                statusCode: response.status,
                url: window.location.href,
            },
        });
    }
}

/**
 * Handle Inertia exception events (unhandled errors)
 */
function handleInertiaException(event: CustomEvent): void {
    const { detail } = event;
    const error = detail?.error;

    console.error('Inertia Exception:', error);

    // Log exception to backend
    if (error instanceof Error) {
        logErrorObject(error, {
            level: 'error',
            context: {
                errorType: 'inertia_exception',
            },
            immediate: true, // Send immediately for critical errors
        });
    } else {
        logError('Inertia Exception: Unknown error', {
            level: 'error',
            context: {
                errorType: 'inertia_exception',
                errorDetails: String(error),
            },
            immediate: true,
        });
    }

    showToast.error(
        'Unexpected Error',
        'An unexpected error occurred. Please try again.'
    );
}

/**
 * Initialize global error handlers
 * Should be called once when the app starts
 */
export function initializeErrorHandlers(): void {
    // Prevent duplicate listeners by cleaning up first
    cleanupErrorHandlers();

    // Handle Inertia error events
    document.addEventListener('inertia:error', handleInertiaError as EventListener);

    // Handle Inertia success events (for flash messages)
    document.addEventListener('inertia:success', handleInertiaSuccess as EventListener);

    // Handle Inertia invalid events (validation errors)
    document.addEventListener('inertia:invalid', handleInertiaInvalid as EventListener);

    // Handle Inertia exception events (unhandled errors)
    document.addEventListener('inertia:exception', handleInertiaException as EventListener);

    // Handle global unhandled promise rejections
    window.addEventListener('unhandledrejection', (event) => {
        console.error('Unhandled Promise Rejection:', event.reason);

        // Log to backend
        if (event.reason instanceof Error) {
            logErrorObject(event.reason, {
                level: 'error',
                context: {
                    errorType: 'unhandled_promise_rejection',
                },
            });
        } else {
            logError('Unhandled Promise Rejection', {
                level: 'error',
                context: {
                    errorType: 'unhandled_promise_rejection',
                    reason: String(event.reason),
                },
            });
        }

        showToast.error(
            'Unexpected Error',
            'An unexpected error occurred. Please try again.'
        );
    });

    // Handle global errors
    window.addEventListener('error', (event) => {
        console.error('Global Error:', event.error);

        // Don't show toast for script loading errors
        if (event.message.includes('Script error')) {
            return;
        }

        // Log to backend
        if (event.error instanceof Error) {
            logErrorObject(event.error, {
                level: 'error',
                context: {
                    errorType: 'global_error',
                    filename: event.filename,
                    lineno: event.lineno,
                    colno: event.colno,
                },
            });
        } else {
            logError(event.message, {
                level: 'error',
                context: {
                    errorType: 'global_error',
                    filename: event.filename,
                    lineno: event.lineno,
                    colno: event.colno,
                },
            });
        }

        showToast.error(
            'Unexpected Error',
            'An unexpected error occurred. Please try again.'
        );
    });
}

/**
 * Cleanup error handlers (useful for testing)
 */
export function cleanupErrorHandlers(): void {
    document.removeEventListener('inertia:error', handleInertiaError as EventListener);
    document.removeEventListener('inertia:success', handleInertiaSuccess as EventListener);
    document.removeEventListener('inertia:invalid', handleInertiaInvalid as EventListener);
    document.removeEventListener('inertia:exception', handleInertiaException as EventListener);
}

/**
 * Manually handle an error (useful for try-catch blocks)
 */
export function handleError(error: unknown, context?: string): void {
    console.error(context ? `${context}:` : 'Error:', error);

    // Log to backend
    if (error instanceof Error) {
        logErrorObject(error, {
            level: 'error',
            context: {
                errorType: 'manual_error',
                errorContext: context,
            },
        });

        showToast.error(
            context || 'Error',
            error.message
        );
    } else {
        logError(String(error), {
            level: 'error',
            context: {
                errorType: 'manual_error',
                errorContext: context,
            },
        });

        showToast.error(
            context || 'Error',
            'An unexpected error occurred.'
        );
    }
}

/**
 * Handle form submission errors
 */
export function handleFormError(errors: Record<string, string | string[]>): void {
    handleValidationErrors(errors);
}
