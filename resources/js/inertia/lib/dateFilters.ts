import { differenceInCalendarDays, endOfDay, endOfMonth, endOfWeek, isThisMonth, isThisWeek, isToday, isWithinInterval, parseISO, startOfDay, startOfMonth, startOfWeek } from 'date-fns';

export type DateFilter = 'all' | 'today' | 'week' | 'month' | { from: string; to: string };

function toDate(date: string | Date | null | undefined): Date | null {
    if (!date) return null;
    const parsed = typeof date === 'string' ? parseISO(date) : date;
    return Number.isNaN(parsed.getTime()) ? null : parsed;
}

export function isOverdue(dueDate: string | Date | null, completedAt?: string | Date | null): boolean {
    const due = toDate(dueDate);
    if (!due || completedAt) return false;

    return differenceInCalendarDays(due, new Date()) < 0;
}

export function formatDueDate(dueDate: string | Date | null): string {
    const due = toDate(dueDate);
    if (!due) return '-';

    const days = differenceInCalendarDays(due, new Date());
    if (days === 0) return 'Due today';
    if (days < 0) return `Overdue ${Math.abs(days)} day${Math.abs(days) === 1 ? '' : 's'}`;

    return `Due in ${days} day${days === 1 ? '' : 's'}`;
}

export function isWithinDateFilter(date: string | Date | null, filter: DateFilter): boolean {
    if (filter === 'all') return true;

    const parsed = toDate(date);
    if (!parsed) return false;

    if (filter === 'today') return isToday(parsed);
    if (filter === 'week') return isThisWeek(parsed, { weekStartsOn: 1 });
    if (filter === 'month') return isThisMonth(parsed);

    const from = toDate(filter.from);
    const to = toDate(filter.to);
    if (!from || !to) return true;

    return isWithinInterval(parsed, { start: startOfDay(from), end: endOfDay(to) });
}

export function makeDateRangeFilter(from: Date | null, to: Date | null): DateFilter {
    if (!from || !to) return 'all';

    return {
        from: startOfDay(from).toISOString(),
        to: endOfDay(to).toISOString(),
    };
}

export const DATE_FILTER_RANGES = {
    week: () => ({ from: startOfWeek(new Date(), { weekStartsOn: 1 }), to: endOfWeek(new Date(), { weekStartsOn: 1 }) }),
    month: () => ({ from: startOfMonth(new Date()), to: endOfMonth(new Date()) }),
};
