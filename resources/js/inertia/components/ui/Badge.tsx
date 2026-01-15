import * as React from "react"
import { cn } from "@/lib/utils"

// Simple Badge Component
interface BadgeProps extends React.HTMLAttributes<HTMLSpanElement> {
    variant?: 'default' | 'success' | 'warning' | 'danger' | 'info'
    size?: 'sm' | 'md' | 'lg'
}

const Badge = React.forwardRef<HTMLSpanElement, BadgeProps>(
    ({ className, variant = 'default', size = 'md', children, ...props }, ref) => {
        const variantStyles = {
            default: 'bg-slate-100 text-slate-600',
            success: 'bg-emerald-100 text-emerald-700',
            warning: 'bg-amber-100 text-amber-700',
            danger: 'bg-rose-100 text-rose-700',
            info: 'bg-blue-100 text-blue-700',
        }

        const sizeStyles = {
            sm: 'px-1.5 py-0.5 text-[10px]',
            md: 'px-2 py-0.5 text-xs',
            lg: 'px-2.5 py-1 text-xs',
        }

        return (
            <span
                ref={ref}
                className={cn(
                    "inline-flex items-center rounded-md font-medium",
                    variantStyles[variant],
                    sizeStyles[size],
                    className
                )}
                {...props}
            >
                {children}
            </span>
        )
    }
)
Badge.displayName = "Badge"

// Status Badge - Pill style with chevron (like reference design)
interface StatusBadgeProps {
    status: string
    showChevron?: boolean
}

function StatusBadge({ status, showChevron = true }: StatusBadgeProps) {
    const config: Record<string, { label: string; bg: string; text: string; border: string }> = {
        planned: {
            label: 'Planned',
            bg: 'bg-amber-50',
            text: 'text-amber-700',
            border: 'border-amber-200',
        },
        in_progress: {
            label: 'In Progress',
            bg: 'bg-blue-50',
            text: 'text-blue-700',
            border: 'border-blue-200',
        },
        completed: {
            label: 'Achieved',
            bg: 'bg-emerald-50',
            text: 'text-emerald-700',
            border: 'border-emerald-200',
        },
        cancelled: {
            label: 'Cancelled',
            bg: 'bg-gray-50',
            text: 'text-gray-500',
            border: 'border-gray-200',
        },
    }

    const { label, bg, text, border } = config[status] || config.planned

    return (
        <span className={cn(
            "inline-flex items-center gap-1 px-3 py-1 text-xs font-medium rounded-full border",
            bg, text, border
        )}>
            {label}
            {showChevron && (
                <svg className="w-3 h-3 opacity-60" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                    <path strokeLinecap="round" strokeLinejoin="round" d="M19 9l-7 7-7-7" />
                </svg>
            )}
        </span>
    )
}

// Activity Type - Plain colored text
interface ActivityTypeBadgeProps {
    name: string
    color?: string
}

function ActivityTypeBadge({ name, color }: ActivityTypeBadgeProps) {
    const colorMap: Record<string, string> = {
        'blue': 'bg-blue-100 text-blue-700',
        'indigo': 'bg-indigo-100 text-indigo-700',
        'purple': 'bg-purple-100 text-purple-700',
        'gray': 'bg-gray-100 text-gray-700',
        'yellow': 'bg-yellow-100 text-yellow-700',
        'green': 'bg-green-100 text-green-700',
        'red': 'bg-red-100 text-red-700',
        'pink': 'bg-pink-100 text-pink-700',
        'orange': 'bg-orange-100 text-orange-700',
        'teal': 'bg-teal-100 text-teal-700',
        'cyan': 'bg-cyan-100 text-cyan-700',
    }

    // Default to gray if color not found or name doesn't match a specific style
    const badgeClass = colorMap[color || ''] || 'bg-gray-100 text-gray-700'

    return (
        <span className={cn("inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-medium", badgeClass)}>
            {name}
        </span>
    )
}

// Priority Badge
interface PriorityBadgeProps {
    priority: string
}

function PriorityBadge({ priority }: PriorityBadgeProps) {
    const config: Record<string, { label: string; className: string }> = {
        low: { label: 'Low', className: 'bg-slate-100 text-slate-600' },
        medium: { label: 'Medium', className: 'bg-amber-100 text-amber-700' },
        high: { label: 'High', className: 'bg-rose-100 text-rose-700' },
    }

    const { label, className } = config[priority?.toLowerCase()] || config.low

    return (
        <span className={cn("inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-md", className)}>
            {label}
        </span>
    )
}

export {
    Badge,
    StatusBadge,
    ActivityTypeBadge,
    PriorityBadge,
}
export type { BadgeProps, StatusBadgeProps, ActivityTypeBadgeProps, PriorityBadgeProps }
