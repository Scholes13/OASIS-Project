import type { TaskStats } from '@/types';

interface TaskDashboardSummaryProps {
    stats: TaskStats;
}

export function TaskDashboardSummary({ stats }: TaskDashboardSummaryProps) {
    return (
        <div className="flex flex-col gap-1.5">
            <h1 className="text-2xl font-bold text-slate-900 tracking-tight">My Tasks</h1>
            <div className="flex flex-wrap items-center gap-2 mt-1">
                <span className="inline-flex items-center rounded-md bg-white px-2 py-1 text-[11px] font-semibold text-slate-600 border border-slate-200 shadow-sm">
                    {stats.total} Total
                </span>
                {stats.in_progress > 0 && (
                    <span className="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-[11px] font-semibold text-blue-700 border border-blue-100 shadow-sm">
                        {stats.in_progress} Active
                    </span>
                )}
                {stats.overdue > 0 && (
                    <span className="inline-flex items-center rounded-md bg-rose-50 px-2 py-1 text-[11px] font-semibold text-rose-700 border border-rose-100 shadow-sm">
                        {stats.overdue} Overdue
                    </span>
                )}
            </div>
        </div>
    );
}
