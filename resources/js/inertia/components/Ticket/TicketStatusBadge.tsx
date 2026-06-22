import { cn } from '@/lib/utils';
import type { TicketStatus } from '@/types';

interface TicketStatusBadgeProps {
    status: TicketStatus;
    className?: string;
}

const statusConfig: Record<TicketStatus, { label: string; bg: string; text: string; icon: React.ReactNode }> = {
    waiting: {
        label: 'Menunggu',
        bg: 'bg-amber-50',
        text: 'text-amber-700',
        icon: (
            <svg className="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        ),
    },
    in_progress: {
        label: 'Dalam Proses',
        bg: 'bg-blue-50',
        text: 'text-blue-700',
        icon: (
            <svg className="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
        ),
    },
    done: {
        label: 'Selesai',
        bg: 'bg-emerald-50',
        text: 'text-emerald-700',
        icon: (
            <svg className="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.5" d="M5 13l4 4L19 7" />
            </svg>
        ),
    },
    cancelled: {
        label: 'Dibatalkan',
        bg: 'bg-gray-50',
        text: 'text-gray-500',
        icon: (
            <svg className="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.5" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
            </svg>
        ),
    },
};

export function TicketStatusBadge({ status, className }: TicketStatusBadgeProps) {
    const config = statusConfig[status] || statusConfig.waiting;

    return (
        <span className={cn(
            'inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium',
            config.bg,
            config.text,
            className
        )}>
            {config.icon}
            {config.label}
        </span>
    );
}