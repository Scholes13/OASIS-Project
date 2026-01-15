import * as React from "react"
import { AlertTriangle, RefreshCw } from "lucide-react"
import { Button } from "../components/ui/button"

interface ErrorBoundaryProps {
  children: React.ReactNode
  fallback?: React.ReactNode
  onError?: (error: Error, errorInfo: React.ErrorInfo) => void
}

interface ErrorBoundaryState {
  hasError: boolean
  error: Error | null
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
    console.error("ErrorBoundary caught an error:", error, errorInfo)
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
      {process.env.NODE_ENV === "development" && error && (
        <pre className="text-left text-xs bg-gray-100 p-4 rounded-lg max-w-lg overflow-auto mb-4 text-red-600">
          {error.message}
          {error.stack && (
            <>
              {"\n\n"}
              {error.stack}
            </>
          )}
        </pre>
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
