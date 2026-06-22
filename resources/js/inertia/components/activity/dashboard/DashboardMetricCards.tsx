import { Briefcase, Clock, TrendingUp } from 'lucide-react';

import { cn } from '@/lib/utils';

interface Stats {
    total: number;
    completed: number;
    in_progress: number;
    completed_this_month?: number;
}

interface DashboardMetricCardsProps {
    viewMode: 'personal' | 'department' | 'executive';
    totalHours: number;
    totalMinutes: number;
    activeProjects: number;
    completionRate: number;
    activeStats: Stats;
}

export function DashboardMetricCards({
    viewMode,
    totalHours,
    totalMinutes,
    activeProjects,
    completionRate,
    activeStats,
}: DashboardMetricCardsProps) {
    return (
        <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
            <div className="rounded-xl border border-slate-200/60 bg-white p-6 shadow-sm flex flex-col">
                <div className="flex items-center gap-3 mb-4">
                    <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                        <Clock className="h-5 w-5" strokeWidth={2} />
                    </div>
                    <h3 className="text-sm font-medium text-slate-500">
                        {viewMode === 'department' ? 'Team Total Hours' : 'My Total Hours'}
                    </h3>
                </div>
                <div className="text-3xl font-bold tracking-tight text-slate-900 mb-2">
                    {totalHours}h <span className="text-2xl text-slate-500 font-semibold">{String(totalMinutes).padStart(2, '0')}m</span>
                </div>
                <div className="mt-auto flex items-center text-sm">
                    <span className="font-medium text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded mr-2">
                        +{Math.max(activeStats.completed_this_month || 0, 0)} tasks
                    </span>
                    <span className="text-slate-500">completed this period</span>
                </div>
            </div>

            <div className="rounded-xl border border-slate-200/60 bg-white p-6 shadow-sm flex flex-col">
                <div className="flex items-center gap-3 mb-4">
                    <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-purple-50 text-purple-600">
                        <Briefcase className="h-5 w-5" strokeWidth={2} />
                    </div>
                    <h3 className="text-sm font-medium text-slate-500">Active Projects</h3>
                </div>
                <div className="text-3xl font-bold tracking-tight text-slate-900 mb-2">
                    {activeProjects}
                </div>
                <div className="mt-auto flex items-center text-sm text-slate-500">
                    <span className="font-medium text-slate-700 mr-1">{activeStats.in_progress}</span> task(s) currently in progress
                </div>
            </div>

            <div className="rounded-xl border border-slate-200/60 bg-white p-6 shadow-sm flex flex-col relative overflow-hidden">
                <div className="flex items-center gap-3 mb-4 relative z-10">
                    <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-50 text-amber-600">
                        <TrendingUp className="h-5 w-5" strokeWidth={2} />
                    </div>
                    <h3 className="text-sm font-medium text-slate-500">Avg. Efficiency</h3>
                </div>
                <div className="text-3xl font-bold tracking-tight text-slate-900 mb-2 relative z-10">
                    {completionRate}%
                </div>
                <div className="mt-auto flex items-center text-sm text-slate-500 relative z-10">
                    <span className="font-medium text-slate-700 mr-1">{activeStats.completed}</span> of {activeStats.total} tasks completed
                </div>

                <div className="absolute right-6 bottom-6 flex items-end gap-1.5 opacity-40">
                    {[45, 60, 75, 50, completionRate, 80, 65].map((val, index) => (
                        <div
                            key={index}
                            className={cn(
                                "w-2 rounded-t-sm transition-all",
                                index === 4 ? "bg-blue-500" : "bg-slate-200"
                            )}
                            style={{ height: `${Math.max(val / 2, 10)}px` }}
                        />
                    ))}
                </div>
            </div>
        </div>
    );
}

export default DashboardMetricCards;
