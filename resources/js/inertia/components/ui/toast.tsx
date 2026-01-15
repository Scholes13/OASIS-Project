import { Toaster as SonnerToaster, toast } from "sonner"

interface ToasterProps {
  position?: "top-left" | "top-right" | "bottom-left" | "bottom-right" | "top-center" | "bottom-center"
  richColors?: boolean
  closeButton?: boolean
  duration?: number
}

export function Toaster({
  position = "top-right",
  richColors = true,
  closeButton = true,
  duration = 4000,
}: ToasterProps = {}) {
  return (
    <SonnerToaster
      position={position}
      richColors={richColors}
      closeButton={closeButton}
      duration={duration}
      toastOptions={{
        classNames: {
          toast: "group border-gray-200 bg-white text-gray-900 shadow-lg",
          description: "text-gray-500",
          actionButton: "bg-indigo-600 text-white",
          cancelButton: "bg-gray-100 text-gray-500",
          closeButton: "bg-white border-gray-200 hover:bg-gray-100",
        },
      }}
    />
  )
}

// Re-export toast functions for convenience
export { toast }

// Helper functions with consistent styling
export const showToast = {
  success: (message: string, description?: string) => {
    toast.success(message, { description })
  },
  error: (message: string, description?: string) => {
    toast.error(message, { description })
  },
  warning: (message: string, description?: string) => {
    toast.warning(message, { description })
  },
  info: (message: string, description?: string) => {
    toast.info(message, { description })
  },
  loading: (message: string) => {
    return toast.loading(message)
  },
  dismiss: (toastId?: string | number) => {
    toast.dismiss(toastId)
  },
  promise: <T,>(
    promise: Promise<T>,
    messages: {
      loading: string
      success: string | ((data: T) => string)
      error: string | ((error: Error) => string)
    }
  ) => {
    return toast.promise(promise, messages)
  },
}

// Custom action toast
export const actionToast = (
  message: string,
  actionLabel: string,
  onAction: () => void,
  options?: { description?: string; duration?: number }
) => {
  toast(message, {
    description: options?.description,
    duration: options?.duration,
    action: {
      label: actionLabel,
      onClick: onAction,
    },
  })
}
