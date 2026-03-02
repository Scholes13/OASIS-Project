import * as React from "react"
import { Slot } from "@radix-ui/react-slot"
import { cva, type VariantProps } from "class-variance-authority"
import { cn } from "@/lib/utils"
import { Loader2 } from "lucide-react"

const buttonVariants = cva(
  "inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0",
  {
    variants: {
      variant: {
        default:
          "bg-primary text-white shadow hover:bg-blue-600",
        primary:
          "bg-primary text-white shadow hover:bg-blue-600",
        destructive:
          "bg-red-600 text-white shadow-sm hover:bg-red-700",
        danger:
          "bg-red-600 text-white shadow-sm hover:bg-red-700",
        outline:
          "border border-gray-300 bg-white shadow-sm hover:bg-gray-50 text-gray-700",
        secondary:
          "bg-gray-100 text-gray-900 shadow-sm hover:bg-gray-200",
        ghost: "hover:bg-gray-100 text-gray-600 hover:text-gray-900",
        link: "text-primary underline-offset-4 hover:underline",
        success:
          "bg-green-600 text-white shadow-sm hover:bg-green-700",
        warning:
          "bg-amber-500 text-white shadow-sm hover:bg-amber-600",
        info:
          "bg-blue-500 text-white shadow-sm hover:bg-blue-600",
      },
      size: {
        default: "h-9 px-4 py-2",
        sm: "h-8 rounded-md px-3 text-xs",
        md: "h-9 px-4 py-2",
        lg: "h-10 rounded-md px-6 py-3",
        xl: "h-12 rounded-md px-10 text-base",
        icon: "h-9 w-9",
        "icon-sm": "h-8 w-8",
      },
    },
    defaultVariants: {
      variant: "default",
      size: "default",
    },
  }
)

export interface ButtonProps
  extends React.ButtonHTMLAttributes<HTMLButtonElement>,
    VariantProps<typeof buttonVariants> {
  asChild?: boolean
  loading?: boolean
  isLoading?: boolean // backward compatibility
  leftIcon?: React.ReactNode
  rightIcon?: React.ReactNode
}

const Button = React.forwardRef<HTMLButtonElement, ButtonProps>(
  ({ className, variant, size, asChild = false, loading = false, isLoading = false, leftIcon, rightIcon, children, disabled, ...props }, ref) => {
    const isButtonLoading = loading || isLoading
    const Comp = asChild ? Slot : "button"
    return (
      <Comp
        className={cn(buttonVariants({ variant, size, className }))}
        ref={ref}
        disabled={disabled || isButtonLoading}
        {...props}
      >
        {isButtonLoading && <Loader2 className="animate-spin" />}
        {!isButtonLoading && leftIcon && <span className="-ml-1">{leftIcon}</span>}
        {children}
        {!isButtonLoading && rightIcon && <span className="-mr-1">{rightIcon}</span>}
      </Comp>
    )
  }
)
Button.displayName = "Button"

export { Button, buttonVariants }
export default Button
