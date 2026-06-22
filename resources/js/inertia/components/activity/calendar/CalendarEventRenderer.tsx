import { Users } from 'lucide-react';
import { cn } from '@/lib/utils';
import type { Task } from '@/types';

const activityTypeColors: Record<string, { bg: string; text: string; border: string }> = {
    blue: { bg: '#dbeafe', text: '#1e40af', border: '#3b82f6' },
    indigo: { bg: '#dbeafe', text: '#1e40af', border: '#3b82f6' },
    purple: { bg: '#ede9fe', text: '#5b21b6', border: '#8b5cf6' },
    pink: { bg: '#fce7f3', text: '#9d174d', border: '#ec4899' },
    red: { bg: '#fee2e2', text: '#991b1b', border: '#ef4444' },
    orange: { bg: '#ffedd5', text: '#9a3412', border: '#f97316' },
    amber: { bg: '#fef3c7', text: '#92400e', border: '#f59e0b' },
    yellow: { bg: '#fef9c3', text: '#854d0e', border: '#eab308' },
    lime: { bg: '#ecfccb', text: '#3f6212', border: '#84cc16' },
    green: { bg: '#dcfce7', text: '#166534', border: '#22c55e' },
    emerald: { bg: '#d1fae5', text: '#065f46', border: '#10b981' },
    teal: { bg: '#ccfbf1', text: '#115e59', border: '#14b8a6' },
    cyan: { bg: '#cffafe', text: '#155e75', border: '#06b6d4' },
    gray: { bg: '#f3f4f6', text: '#374151', border: '#6b7280' },
};

export const statusStyles: Record<string, { dot: string; label: string }> = {
    planned: { dot: 'bg-slate-400', label: 'Planned' },
    in_progress: { dot: 'bg-amber-500', label: 'In Progress' },
    completed: { dot: 'bg-emerald-500', label: 'Completed' },
    cancelled: { dot: 'bg-gray-400', label: 'Cancelled' },
};

function getActivityColors(type: { color?: string } | undefined) {
    if (!type?.color) return activityTypeColors.gray;
    return activityTypeColors[type.color] || activityTypeColors.gray;
}

type CalendarParticipant = Task['participants'][number] & {
    avatar_url?: string;
};

type CalendarOwner = {
    name: string;
    avatar_url?: string;
    participantCount: number;
};

function getCalendarOwner(task: Task): CalendarOwner {
    const participants = (task.participants || []) as CalendarParticipant[];
    const participant = participants[0];
    const participantUser = participant?.user;

    return {
        name:
            participant?.name ||
            participantUser?.name ||
            task.creator?.name ||
            'Owner',
        avatar_url:
            participant?.avatar_url ||
            participantUser?.avatar_url ||
            task.creator?.avatar_url,
        participantCount: participants.length,
    };
}

function getParticipantOwnerInitial(name: string) {
    return name.trim().charAt(0).toUpperCase() || '?';
}

function OwnerBadge({ owner, compact = false }: { owner: CalendarOwner; compact?: boolean }) {
    const ownerInitial = getParticipantOwnerInitial(owner.name);

    return (
        <div
            className={cn(
                'flex shrink-0 items-center gap-1',
                compact && 'ml-auto'
            )}
        >
            {owner.avatar_url ? (
                <img
                    src={owner.avatar_url}
                    alt={owner.name}
                    className={cn(
                        'rounded-full object-cover ring-1 ring-white shadow-sm',
                        compact ? 'h-5 w-5' : 'h-6 w-6'
                    )}
                />
            ) : (
                <div
                    className={cn(
                        'flex items-center justify-center rounded-full bg-white/70 font-semibold text-slate-700 ring-1 ring-white shadow-sm',
                        compact ? 'h-5 w-5 text-[10px]' : 'h-6 w-6 text-[11px]'
                    )}
                    aria-label={owner.name}
                >
                    {ownerInitial}
                </div>
            )}
        </div>
    );
}

function MonthOwnerBadge({ task }: { task: Task }) {
    const owner = getCalendarOwner(task);
    const remainingParticipants = Math.max(owner.participantCount - 1, 0);

    return (
        <div className="ml-auto flex shrink-0 items-center gap-1">
            {remainingParticipants > 0 && (
                <span className="inline-flex h-5 items-center justify-center rounded-full bg-white/70 px-1 text-[10px] font-semibold text-slate-600 ring-1 ring-white">
                    +{remainingParticipants}
                </span>
            )}
            <OwnerBadge
                owner={owner}
                compact
            />
        </div>
    );
}

function RichOwnerDetails({ task }: { task: Task }) {
    const owner = getCalendarOwner(task);
    const participantCount = owner.participantCount;

    return (
        <div className="mt-1 flex items-center justify-between gap-2">
            <div className="flex min-w-0 items-center gap-2">
                <OwnerBadge owner={owner} />
                <div className="min-w-0">
                    <p className="truncate text-[11px] font-semibold text-gray-700">
                        {owner.name}
                    </p>
                    <p className="truncate text-[10px] text-gray-500">
                        {participantCount > 1
                            ? `${participantCount} participants`
                            : 'Owner'}
                    </p>
                </div>
            </div>
            {participantCount > 0 && (
                <div className="flex items-center gap-1 text-[10px] font-medium text-gray-600">
                    <Users className="h-3 w-3 text-gray-500" />
                    <span>{participantCount}</span>
                </div>
            )}
        </div>
    );
}

export default function CalendarEventRenderer({ event, view }: { event: any; view: string }) {
    const task = event.extendedProps.task as Task;
    const colors = getActivityColors(task.activity_type);
    const isMonthView = view === 'dayGridMonth';
    const isOverdue = task.due_date
        ? new Date(task.due_date) < new Date() && !['completed', 'cancelled'].includes(task.status)
        : false;

    if (isMonthView) {
        return (
            <div
                className={cn(
                    'flex items-center gap-1.5 px-1.5 py-0.5 text-[11px] rounded cursor-pointer transition-all hover:shadow-sm',
                    'border-l-[3px]'
                )}
                style={{
                    backgroundColor: colors.bg,
                    borderLeftColor: colors.border,
                }}
            >
                <span
                    className={cn(
                        'w-1.5 h-1.5 rounded-full flex-shrink-0',
                        statusStyles[task.status]?.dot || 'bg-gray-400'
                    )}
                />
                <span
                    className="min-w-0 flex-1 font-semibold truncate"
                    style={{ color: colors.text }}
                >
                    {event.title}
                </span>
                {isOverdue && (
                    <span className="text-[9px] text-rose-600 font-bold flex-shrink-0">!</span>
                )}
                <MonthOwnerBadge task={task} />
            </div>
        );
    }

    return (
        <div
            className="h-full p-2 text-xs overflow-hidden rounded cursor-pointer border-l-[3px]"
            style={{
                backgroundColor: colors.bg,
                borderLeftColor: colors.border,
            }}
        >
            <div className="flex items-center gap-1 mb-0.5">
                <p
                    className="font-semibold truncate"
                    style={{ color: colors.text }}
                >
                    {event.title}
                </p>
            </div>
            {task.activity_type && (
                <p className="text-gray-600 truncate text-[10px]">
                    {task.activity_type.name}
                </p>
            )}
            <RichOwnerDetails task={task} />
        </div>
    );
}
