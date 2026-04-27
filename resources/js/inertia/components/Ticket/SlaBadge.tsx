import { cn } from '@/lib/utils';

interface SlaBadgeProps {
    slaDeadline: string | null;
    isBreached: boolean;
    className?: string;
}

/**
 * Calculate hours remaining until SLA deadline
 */
function getHoursRemaining(deadline: string): number {
    const now = new Date();
    const deadlineDate = new Date(deadline);
    const diffMs = deadlineDate.getTime() - now.getTime();
    return diffMs / (1000 * 60 * 60); // Convert to hours
}

export function SlaBadge({ slaDeadline, isBreached, className }: SlaBadgeProps) {
    if (!slaDeadline) {
        return null;
    }

    const hoursRemaining = getHoursRemaining(slaDeadline);

    let config: { label: string; className: string };
    
    if (isBreached || hoursRemaining < 0) {
        // SLA Breached
        config = {
            label: 'SLA Breach',
            className: 'bg-red-100 text-red-700 border-red-200',
        };
    } else if (hoursRemaining < 2) {
        // Approaching deadline (< 2 hours)
        config = {
            label: 'SLA Warning',
            className: 'bg-amber-100 text-amber-700 border-amber-200',
        };
    } else {
        // On time
        config = {
            label: 'SLA OK',
            className: 'bg-emerald-100 text-emerald-700 border-emerald-200',
        };
    }

    return (
        <span className={cn(
            'inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-md border',
            config.className,
            className
        )}>
            <svg className="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            {config.label}
        </span>
    );
}