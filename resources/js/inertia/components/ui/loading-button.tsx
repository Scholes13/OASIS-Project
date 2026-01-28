/**
 * LoadingButton Component
 * 
 * Button component with built-in loading state and spinner.
 * 
 * Requirements:
 * - 11.2: Disable submit buttons during submission and show spinner
 * 
 * Features:
 * - Automatic disabling during loading
 * - Built-in spinner animation
 * - Customizable loading text
 * - Supports all Button variants
 * 
 * Usage:
 * ```tsx
 * <LoadingButton
 *   isLoading={isSubmitting}
 *   loadingText="Creating..."
 *   type="submit"
 * >
 *   Create User
 * </LoadingButton>
 * ```
 */

import * as React from 'react';
import { Loader2 } from 'lucide-react';
import { Button, ButtonProps } from './button';
import { cn } from '@/lib/utils';

export interface LoadingButtonProps extends ButtonProps {
  // Whether the button is in loading state
  isLoading?: boolean;
  
  // Text to display when loading (optional)
  loadingText?: string;
  
  // Icon to show when loading (defaults to spinner)
  loadingIcon?: React.ReactNode;
  
  // Whether to show the loading icon
  showLoadingIcon?: boolean;
}

export const LoadingButton = React.forwardRef<HTMLButtonElement, LoadingButtonProps>(
  (
    {
      children,
      isLoading = false,
      loadingText,
      loadingIcon,
      showLoadingIcon = true,
      disabled,
      className,
      ...props
    },
    ref
  ) => {
    return (
      <Button
        ref={ref}
        disabled={disabled || isLoading}
        className={cn(className)}
        {...props}
      >
        {isLoading && showLoadingIcon && (
          <span className="mr-2">
            {loadingIcon || <Loader2 className="h-4 w-4 animate-spin" />}
          </span>
        )}
        {isLoading && loadingText ? loadingText : children}
      </Button>
    );
  }
);

LoadingButton.displayName = 'LoadingButton';

export default LoadingButton;
