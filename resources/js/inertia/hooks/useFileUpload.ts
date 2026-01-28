import { useState, useCallback } from 'react';
import axios, { AxiosProgressEvent } from 'axios';
import { FileUploadProgress } from '@/components/admin/FileUpload';

interface UseFileUploadOptions {
  onSuccess?: (response: any) => void;
  onError?: (error: string) => void;
}

export function useFileUpload(options: UseFileUploadOptions = {}) {
  const [progress, setProgress] = useState<FileUploadProgress>({
    progress: 0,
    status: 'idle',
  });

  const uploadFile = useCallback(
    async (file: File, url: string, additionalData?: Record<string, any>) => {
      // Reset progress
      setProgress({
        progress: 0,
        status: 'uploading',
      });

      try {
        // Create FormData
        const formData = new FormData();
        formData.append('file', file);

        // Add additional data if provided
        if (additionalData) {
          Object.entries(additionalData).forEach(([key, value]) => {
            formData.append(key, value);
          });
        }

        // Upload with progress tracking
        const response = await axios.post(url, formData, {
          headers: {
            'Content-Type': 'multipart/form-data',
          },
          onUploadProgress: (progressEvent: AxiosProgressEvent) => {
            if (progressEvent.total) {
              const percentCompleted = Math.round(
                (progressEvent.loaded * 100) / progressEvent.total
              );
              setProgress({
                progress: percentCompleted,
                status: 'uploading',
              });
            }
          },
        });

        // Success
        setProgress({
          progress: 100,
          status: 'success',
        });

        if (options.onSuccess) {
          options.onSuccess(response.data);
        }

        return response.data;
      } catch (error: any) {
        // Error
        const errorMessage = getErrorMessage(error);
        
        setProgress({
          progress: 0,
          status: 'error',
          error: errorMessage,
        });

        if (options.onError) {
          options.onError(errorMessage);
        }

        throw error;
      }
    },
    [options]
  );

  const reset = useCallback(() => {
    setProgress({
      progress: 0,
      status: 'idle',
    });
  }, []);

  return {
    uploadFile,
    progress,
    reset,
    isUploading: progress.status === 'uploading',
    isSuccess: progress.status === 'success',
    isError: progress.status === 'error',
  };
}

/**
 * Extract error message from various error formats
 */
function getErrorMessage(error: any): string {
  // Network error
  if (!error.response) {
    return 'Network error. Please check your connection and try again.';
  }

  // Server error with message
  if (error.response?.data?.message) {
    return error.response.data.message;
  }

  // Validation errors
  if (error.response?.data?.errors) {
    const errors = error.response.data.errors;
    const firstError = Object.values(errors)[0];
    if (Array.isArray(firstError)) {
      return firstError[0] as string;
    }
    return firstError as string;
  }

  // HTTP status errors
  switch (error.response?.status) {
    case 400:
      return 'Invalid file. Please check the file and try again.';
    case 401:
      return 'Unauthorized. Please log in and try again.';
    case 403:
      return 'You do not have permission to upload files.';
    case 413:
      return 'File is too large. Please upload a smaller file.';
    case 415:
      return 'Unsupported file type. Please upload a different file.';
    case 500:
      return 'Server error. Please try again later.';
    default:
      return 'Upload failed. Please try again.';
  }
}
