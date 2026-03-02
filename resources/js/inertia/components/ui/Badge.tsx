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
        'indigo': 'bg-blue-100 text-blue-800',
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

// Purchase Request Status Badge
interface PRStatusBadgeProps {
    status: 'draft' | 'submitted' | 'in_approval' | 'approved' | 'rejected' | 'voided';
}

function PRStatusBadge({ status }: PRStatusBadgeProps) {
    const config: Record<string, { label: string; bg: string; text: string; icon: React.ReactNode }> = {
        draft: {
            label: 'Draft',
            bg: 'bg-gray-100',
            text: 'text-gray-700',
            icon: (
                <svg className="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                </svg>
            ),
        },
        submitted: {
            label: 'Submitted',
            bg: 'bg-blue-100',
            text: 'text-blue-700',
            icon: (
                <svg className="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            ),
        },
        in_approval: {
            label: 'In Approval',
            bg: 'bg-amber-100',
            text: 'text-amber-700',
            icon: (
                <svg className="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            ),
        },
        approved: {
            label: 'Approved',
            bg: 'bg-emerald-100',
            text: 'text-emerald-700',
            icon: (
                <svg className="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.5" d="M5 13l4 4L19 7"></path>
                </svg>
            ),
        },
        rejected: {
            label: 'Rejected',
            bg: 'bg-red-100',
            text: 'text-red-700',
            icon: (
                <svg className="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.5" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            ),
        },
        voided: {
            label: 'Voided',
            bg: 'bg-gray-100',
            text: 'text-gray-500',
            icon: (
                <svg className="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.5" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path>
                </svg>
            ),
        },
    };

    const { label, bg, text, icon } = config[status] || config.draft;

    return (
        <span className={cn(
            "inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium",
            bg, text
        )}>
            {icon}
            {label}
        </span>
    );
}

// Offline Approved Badge
function OfflineApprovedBadge() {
    return (
        <span className="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-700">
            <svg className="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
            </svg>
            Offline Approved
        </span>
    );
}

export {
    Badge,
    StatusBadge,
    ActivityTypeBadge,
    PriorityBadge,
    PRStatusBadge,
    OfflineApprovedBadge,
}
export type { BadgeProps, StatusBadgeProps, ActivityTypeBadgeProps, PriorityBadgeProps, PRStatusBadgeProps }
