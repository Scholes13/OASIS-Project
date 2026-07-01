import { cn } from '@/lib/utils';
import type { TicketPriority } from '@/types';

interface TicketPriorityBadgeProps {
    priority: TicketPriority;
    className?: string;
}

const priorityConfig: Record<TicketPriority, { label: string; className: string; icon: React.ReactNode }> = {
    low: {
        label: 'Rendah',
        className: 'bg-slate-50 text-slate-600 ring-1 ring-slate-200/70',
        icon: (
            <svg className="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M20 12H4" />
            </svg>
        ),
    },
    medium: {
        label: 'Sedang',
        className: 'bg-blue-50 text-blue-600 ring-1 ring-blue-100',
        icon: (
            <svg className="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 12h14M12 5l7 7-7 7" />
            </svg>
        ),
    },
    high: {
        label: 'Tinggi',
        className: 'bg-amber-50 text-amber-700 ring-1 ring-amber-100',
        icon: (
            <svg className="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
            </svg>
        ),
    },
    critical: {
        label: 'Kritis',
        className: 'bg-red-50 text-red-600 ring-1 ring-red-100',
        icon: (
            <svg className="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
        ),
    },
};

export function TicketPriorityBadge({ priority, className }: TicketPriorityBadgeProps) {
    const config = priorityConfig[priority] || priorityConfig.low;

    return (
        <span className={cn(
            'inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-md',
            config.className,
            className
        )}>
            {config.icon}
            {config.label}
        </span>
    );
}