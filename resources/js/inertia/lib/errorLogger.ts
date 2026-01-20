import { router } from '@inertiajs/react';

/**
 * Error log entry structure
 */
interface ErrorLogEntry {
    message: string;
    stack?: string;
    url?: string;
    userAgent?: string;
    timestamp?: string;
    level?: 'error' | 'warning' | 'info';
    context?: Record<string, any>;
}

/**
 * Queue for batching error logs
 */
let errorQueue: ErrorLogEntry[] = [];
let batchTimeout: NodeJS.Timeout | null = null;

/**
 * Configuration
 */
const CONFIG = {
    // Maximum number of errors to batch before sending
    maxBatchSize: 10,
    
    // Time to wait before sending batched errors (ms)
    batchDelay: 5000,
    
    // Whether to log errors in development
    logInDevelopment: true,
    
    // Whether to log errors in production
    logInProduction: true,
};

/**
 * Check if error logging is enabled for current environment
 */
function isLoggingEnabled(): boolean {
    const isDevelopment = import.meta.env.DEV;
    return isDevelopment ? CONFIG.logInDevelopment : CONFIG.logInProduction;
}

/**
 * Send a single error log to the backend
 */
async function sendErrorLog(error: ErrorLogEntry): Promise<void> {
    if (!isLoggingEnabled()) {
        return;
    }

    try {
        await fetch('/api/error-logs', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                'Accept': 'application/json',
            },
            body: JSON.stringify(error),
        });
    } catch (err) {
        // Silently fail - don't want to create infinite error loops
        console.error('Failed to send error log:', err);
    }
}

/**
 * Send batched error logs to the backend
 */
async function sendBatchedErrorLogs(errors: ErrorLogEntry[]): Promise<void> {
    if (!isLoggingEnabled() || errors.length === 0) {
        return;
    }

    try {
        await fetch('/api/error-logs/batch', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ errors }),
        });
    } catch (err) {
        // Silently fail
        console.error('Failed to send batched error logs:', err);
    }
}

/**
 * Flush the error queue immediately
 */
function flushErrorQueue(): void {
    if (errorQueue.length === 0) {
        return;
    }

    const errors = [...errorQueue];
    errorQueue = [];

    if (batchTimeout) {
        clearTimeout(batchTimeout);
        batchTimeout = null;
    }

    sendBatchedErrorLogs(errors);
}

/**
 * Add error to queue and schedule batch send
 */
function queueError(error: ErrorLogEntry): void {
    errorQueue.push(error);

    // Send immediately if queue is full
    if (errorQueue.length >= CONFIG.maxBatchSize) {
        flushErrorQueue();
        return;
    }

    // Schedule batch send if not already scheduled
    if (!batchTimeout) {
        batchTimeout = setTimeout(() => {
            flushErrorQueue();
        }, CONFIG.batchDelay);
    }
}

/**
 * Log an error to the backend
 */
export function logError(
    message: string,
    options?: {
        stack?: string;
        level?: 'error' | 'warning' | 'info';
        context?: Record<string, any>;
        immediate?: boolean;
    }
): void {
    const errorLog: ErrorLogEntry = {
        message,
        stack: options?.stack,
        url: window.location.href,
        userAgent: navigator.userAgent,
        timestamp: new Date().toISOString(),
        level: options?.level || 'error',
        context: options?.context,
    };

    // Log to console in development
    if (import.meta.env.DEV) {
        console.error('[Error Logger]', errorLog);
    }

    // Send immediately or queue for batching
    if (options?.immediate) {
        sendErrorLog(errorLog);
    } else {
        queueError(errorLog);
    }
}

/**
 * Log an Error object to the backend
 */
export function logErrorObject(
    error: Error,
    options?: {
        level?: 'error' | 'warning' | 'info';
        context?: Record<string, any>;
        immediate?: boolean;
    }
): void {
    logError(error.message, {
        stack: error.stack,
        level: options?.level,
        context: {
            ...options?.context,
            errorName: error.name,
        },
        immediate: options?.immediate,
    });
}

/**
 * Log a warning to the backend
 */
export function logWarning(
    message: string,
    options?: {
        context?: Record<string, any>;
        immediate?: boolean;
    }
): void {
    logError(message, {
        level: 'warning',
        context: options?.context,
        immediate: options?.immediate,
    });
}

/**
 * Log info to the backend
 */
export function logInfo(
    message: string,
    options?: {
        context?: Record<string, any>;
        immediate?: boolean;
    }
): void {
    logError(message, {
        level: 'info',
        context: options?.context,
        immediate: options?.immediate,
    });
}

/**
 * Initialize error logger
 * Sets up global error handlers and cleanup on page unload
 */
export function initializeErrorLogger(): void {
    // Flush queue before page unload
    window.addEventListener('beforeunload', () => {
        flushErrorQueue();
    });

    // Flush queue when visibility changes (user switches tabs)
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            flushErrorQueue();
        }
    });

    // Flush queue periodically (every 30 seconds)
    setInterval(() => {
        flushErrorQueue();
    }, 30000);
}

/**
 * Manually flush the error queue
 * Useful for testing or before critical operations
 */
export function flushErrors(): void {
    flushErrorQueue();
}

