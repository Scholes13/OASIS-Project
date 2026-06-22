/**
 * Hooks Index
 * 
 * Central export point for all custom React hooks.
 */

// Form Submission Hooks
export { useFormSubmission, useAsyncAction } from './useFormSubmission';

// Optimistic Update Hooks
export { useOptimisticUpdate, useOptimisticList } from './useOptimisticUpdate';

// Real-time Notification Hooks
export { useNotifications } from './useNotifications';

// Re-export types
export type { default as useFormSubmissionType } from './useFormSubmission';
export type { default as useOptimisticUpdateType } from './useOptimisticUpdate';
