/**
 * ErrorBoundary Component
 * 
 * React Error Boundary for catching and handling component errors gracefully.
 * 
 * Requirements:
 * - 15.1: Display user-friendly error page when React component errors occur
 * - 15.4: Show detailed stack traces in development mode
 * - 15.5: Log errors to server in production without exposing sensitive information
 * 
 * Features:
 * - Catches React component errors and prevents app crash
 * - Displays user-friendly error message with recovery options
 * - Shows detailed error stack in development mode only
 * - Logs errors to server in production mode for monitoring
 * - Provides custom fallback UI support
 * - Includes useErrorHandler hook for programmatic error handling
 * - Includes AsyncBoundary for handling async component errors
 * 
 * Usage:
 * 
 * Basic usage (already integrated in app.tsx):
 * ```tsx
 * <ErrorBoundary>
 *   <App />
 * </ErrorBoundary>
 * ```
 * 
 * With custom fallback:
 * ```tsx
 * <ErrorBoundary fallback={<CustomErrorPage />}>
 *   <MyComponent />
 * </ErrorBoundary>
 * ```
 * 
 * With error handler callback:
 * ```tsx
 * <ErrorBoundary onError={(error, errorInfo) => {
 *   // Custom error handling logic
 *   console.log('Error occurred:', error);
 * }}>
 *   <MyComponent />
 * </ErrorBoundary>
 * ```
 * 
 * Using the error handler hook:
 * ```tsx
 * function MyComponent() {
 *   const { handleError } = useErrorHandler();
 *   
 *   const handleClick = async () => {
 *     try {
 *       await riskyOperation();
 *     } catch (error) {
 *       handleError(error as Error);
 *     }
 *   };
 * }
 * ```
 * 
 * Using AsyncBoundary for async components:
 * ```tsx
 * <AsyncBoundary
 *   loadingFallback={<LoadingSpinner />}
 *   errorFallback={<ErrorMessage />}
 * >
 *   <LazyComponent />
 * </AsyncBoundary>
 * ```
 */

import * as React from "react"
import { AlertTriangle, RefreshCw } from "lucide-react"
import { Button } from "../components/ui/button"
import { router } from "@inertiajs/react"
import { logErrorObject } from "../lib/errorLogger"

interface ErrorBoundaryProps {
  children: React.ReactNode
  fallback?: React.ReactNode
  onError?: (error: Error, errorInfo: React.ErrorInfo) => void
}

interface ErrorBoundaryState {
  hasError: boolean
  error: Error | null
}

/**
 * Log error to server in production mode
 * Requirement 15.5: Log errors to server without exposing sensitive information
 */
async function logErrorToServer(error: Error, errorInfo: React.ErrorInfo): Promise<void> {
  // Log to backend using the error logger
  logErrorObject(error, {
    level: 'error',
    context: {
      errorType: 'react_error_boundary',
      componentStack: errorInfo.componentStack,
    },
    immediate: true, // Send immediately for critical React errors
  })
}

export class ErrorBoundary extends React.Component<ErrorBoundaryProps, ErrorBoundaryState> {
  constructor(props: ErrorBoundaryProps) {
    super(props)
    this.state = { hasError: false, error: null }
  }

  static getDerivedStateFromError(error: Error): ErrorBoundaryState {
    return { hasError: true, error }
  }

  componentDidCatch(error: Error, errorInfo: React.ErrorInfo) {
    // Log to console in development (Requirement 15.4)
    if (import.meta.env.DEV) {
      console.error("ErrorBoundary caught an error:", error, errorInfo)
    }
    
    // Log to server in production (Requirement 15.5)
    logErrorToServer(error, errorInfo)
    
    // Call custom error handler if provided
    this.props.onError?.(error, errorInfo)
  }

  handleReset = () => {
    this.setState({ hasError: false, error: null })
  }

  render() {
    if (this.state.hasError) {
      if (this.props.fallback) {
        return this.props.fallback
      }

      return (
        <DefaultErrorFallback
          error={this.state.error}
          onReset={this.handleReset}
        />
      )
    }

    return this.props.children
  }
}

// Default error fallback component
interface DefaultErrorFallbackProps {
  error: Error | null
  onReset?: () => void
}

function DefaultErrorFallback({ error, onReset }: DefaultErrorFallbackProps) {
  const isDevelopment = import.meta.env.DEV
  
  return (
    <div className="min-h-[400px] flex flex-col items-center justify-center p-8 text-center">
      <div className="rounded-full bg-red-100 p-4 mb-4">
        <AlertTriangle className="h-8 w-8 text-red-600" />
      </div>
      <h2 className="text-xl font-semibold text-gray-900 mb-2">
        Something went wrong
      </h2>
      <p className="text-gray-500 max-w-md mb-4">
        An unexpected error occurred. Please try refreshing the page or contact support if the problem persists.
      </p>
      {/* Requirement 15.4: Show detailed stack trace in development */}
      {isDevelopment && error && (
        <div className="w-full max-w-2xl mb-4">
          <div className="text-left text-xs bg-gray-900 text-gray-100 p-4 rounded-lg overflow-auto max-h-64">
            <div className="font-semibold text-red-400 mb-2">Error Details (Development Only):</div>
            <div className="text-red-300 mb-2">{error.message}</div>
            {error.stack && (
              <pre className="text-gray-400 whitespace-pre-wrap break-words">
                {error.stack}
              </pre>
            )}
          </div>
        </div>
      )}
      {/* Requirement 15.5: Don't expose sensitive information in production */}
      {!isDevelopment && (
        <p className="text-xs text-gray-400 mb-4">
          Error ID: {Date.now().toString(36)}
        </p>
      )}
      <div className="flex gap-3">
        <Button variant="outline" onClick={() => window.location.reload()}>
          <RefreshCw className="h-4 w-4 mr-2" />
          Refresh Page
        </Button>
        {onReset && (
          <Button onClick={onReset}>
            Try Again
          </Button>
        )}
      </div>
    </div>
  )
}

// Hook for error handling
export function useErrorHandler() {
  const [error, setError] = React.useState<Error | null>(null)

  const handleError = React.useCallback((error: Error) => {
    console.error("Error handled:", error)
    setError(error)
  }, [])

  const clearError = React.useCallback(() => {
    setError(null)
  }, [])

  // Throw error to be caught by error boundary
  if (error) {
    throw error
  }

  return { handleError, clearError }
}

// Async error boundary wrapper
interface AsyncBoundaryProps {
  children: React.ReactNode
  loadingFallback?: React.ReactNode
  errorFallback?: React.ReactNode
}

export function AsyncBoundary({
  children,
  loadingFallback,
  errorFallback,
}: AsyncBoundaryProps) {
  return (
    <ErrorBoundary fallback={errorFallback}>
      <React.Suspense fallback={loadingFallback || <DefaultLoadingFallback />}>
        {children}
      </React.Suspense>
    </ErrorBoundary>
  )
}

function DefaultLoadingFallback() {
  return (
    <div className="min-h-[200px] flex items-center justify-center">
      <div className="flex items-center gap-2 text-gray-500">
        <div className="h-5 w-5 animate-spin rounded-full border-2 border-gray-300 border-t-indigo-600" />
        <span>Loading...</span>
      </div>
    </div>
  )
}

export default ErrorBoundary
