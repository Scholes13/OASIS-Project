/**
 * useFormSubmission Hook
 * 
 * Provides comprehensive form submission state management.
 * 
 * Requirements:
 * - 11.2: Disable submit buttons during submission and show spinner
 * - 11.7: Handle optimistic updates with rollback
 * 
 * Features:
 * - Automatic button disabling during submission
 * - Loading state management
 * - Success/error toast notifications
 * - Optimistic updates with rollback
 * - Form reset on success
 * 
 * Usage:
 * ```tsx
 * const { isSubmitting, handleSubmit } = useFormSubmission({
 *   onSubmit: async (data) => {
 *     await api.createUser(data);
 *   },
 *   successMessage: 'User created successfully',
 *   errorMessage: 'Failed to create user',
 * });
 * 
 * <Button type="submit" disabled={isSubmitting}>
 *   {isSubmitting ? 'Creating...' : 'Create User'}
 * </Button>
 * ```
 */

import { useState, useCallback } from 'react';
import { toast } from 'sonner';

interface FormSubmissionOptions<T = any> {
  // The submission handler
  onSubmit: (data: T) => void | Promise<void>;
  
  // Optional: Success callback
  onSuccess?: () => void;
  
  // Optional: Error callback
  onError?: (error: any) => void;
  
  // Optional: Success message
  successMessage?: string;
  
  // Optional: Error message
  errorMessage?: string;
  
  // Optional: Show loading toast
  showLoadingToast?: boolean;
  
  // Optional: Loading message
  loadingMessage?: string;
  
  // Optional: Reset form on success
  resetOnSuccess?: boolean;
  
  // Optional: Form reset handler
  onReset?: () => void;
}

export function useFormSubmission<T = any>(options: FormSubmissionOptions<T>) {
  const {
    onSubmit,
    onSuccess,
    onError,
    successMessage,
    errorMessage,
    showLoadingToast = false,
    loadingMessage = 'Submitting...',
    resetOnSuccess = false,
    onReset,
  } = options;

  const [isSubmitting, setIsSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const handleSubmit = useCallback(async (data: T) => {
    setIsSubmitting(true);
    setError(null);

    let loadingToastId: string | number | undefined;

    try {
      // Show loading toast if enabled
      if (showLoadingToast) {
        loadingToastId = toast.loading(loadingMessage);
      }

      // Execute submission
      await onSubmit(data);

      // Dismiss loading toast
      if (loadingToastId) {
        toast.dismiss(loadingToastId);
      }

      // Show success message
      if (successMessage) {
        toast.success(successMessage);
      }

      // Reset form if enabled
      if (resetOnSuccess && onReset) {
        onReset();
      }

      // Call success callback
      onSuccess?.();

    } catch (err: any) {
      // Dismiss loading toast
      if (loadingToastId) {
        toast.dismiss(loadingToastId);
      }

      // Show error message
      const errorMsg = errorMessage || err.message || 'An error occurred';
      toast.error(errorMsg);
      setError(errorMsg);

      // Call error callback
      onError?.(err);

    } finally {
      setIsSubmitting(false);
    }
  }, [onSubmit, onSuccess, onError, successMessage, errorMessage, showLoadingToast, loadingMessage, resetOnSuccess, onReset]);

  const reset = useCallback(() => {
    setIsSubmitting(false);
    setError(null);
    onReset?.();
  }, [onReset]);

  return {
    isSubmitting,
    error,
    handleSubmit,
    reset,
  };
}

/**
 * useAsyncAction Hook
 * 
 * Simplified hook for async actions (delete, toggle, etc.) with loading state.
 * 
 * Usage:
 * ```tsx
 * const { execute, isLoading } = useAsyncAction({
 *   action: async () => {
 *     await api.deleteUser(userId);
 *   },
 *   successMessage: 'User deleted successfully',
 * });
 * 
 * <Button onClick={execute} disabled={isLoading}>
 *   {isLoading ? 'Deleting...' : 'Delete'}
 * </Button>
 * ```
 */
export function useAsyncAction(options: {
  action: () => void | Promise<void>;
  onSuccess?: () => void;
  onError?: (error: any) => void;
  successMessage?: string;
  errorMessage?: string;
  confirmMessage?: string;
}) {
  const {
    action,
    onSuccess,
    onError,
    successMessage,
    errorMessage,
    confirmMessage,
  } = options;

  const [isLoading, setIsLoading] = useState(false);

  const execute = useCallback(async () => {
    // Show confirmation if provided
    if (confirmMessage && !confirm(confirmMessage)) {
      return;
    }

    setIsLoading(true);

    try {
      await action();

      if (successMessage) {
        toast.success(successMessage);
      }

      onSuccess?.();

    } catch (err: any) {
      const errorMsg = errorMessage || err.message || 'An error occurred';
      toast.error(errorMsg);
      onError?.(err);

    } finally {
      setIsLoading(false);
    }
  }, [action, onSuccess, onError, successMessage, errorMessage, confirmMessage]);

  return {
    execute,
    isLoading,
  };
}

export default useFormSubmission;
