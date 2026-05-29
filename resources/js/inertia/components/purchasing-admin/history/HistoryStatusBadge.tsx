import { cn } from '@/lib/utils';

interface HistoryStatusBadgeProps {
    status: string;
}

export function HistoryStatusBadge({ status }: HistoryStatusBadgeProps) {
    const styles: Record<string, { bg: string; text: string; label: string }> = {
        pending_followup: { bg: 'bg-amber-100', text: 'text-amber-700', label: 'Pending' },
        in_progress: { bg: 'bg-blue-100', text: 'text-blue-700', label: 'In Progress' },
        done: { bg: 'bg-emerald-100', text: 'text-emerald-700', label: 'Completed' },
    };
    const style = styles[status] || styles.pending_followup;

    return (
        <span
            className={cn(
                'inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium',
                style.bg,
                style.text
            )}
        >
            {style.label}
        </span>
    );
}
