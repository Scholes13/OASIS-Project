import { AlertTriangle, Clock, TrendingUp, Users, Activity } from 'lucide-react';
import { cn } from '@/lib/utils';

interface Stats {
    total: number;
    completed: number;
    in_progress: number;
    overdue: number;
    planned?: number;
}

interface PersonalStatsCardsProps {
    stats: Stats;
}

interface DepartmentStatsCardsProps {
    stats: Stats | null;
    bottleneck?: number;
    topCategory?: string;
}

export function PersonalStatsCards({ stats }: PersonalStatsCardsProps) {
    const completionRate = stats.total > 0 ? Math.round((stats.completed / stats.total) * 100) : 0;

    return (
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
            {/* Overdue Tasks */}
            <div className={cn(
                "rounded-xl p-5 border shadow-sm flex items-center justify-between",
                stats.overdue > 0 ? "bg-red-50 border-red-200" : "bg-white border-gray-200"
            )}>
                <div>
                    <p className={cn("text-base font-semibold", stats.overdue > 0 ? "text-red-700" : "text-gray-500")}>
                        Overdue Tasks
                    </p>
                    <p className={cn("text-3xl font-bold mt-1", stats.overdue > 0 ? "text-red-700" : "text-gray-900")}>
                        {stats.overdue}
                    </p>
                    {stats.overdue > 0 && (
                        <p className="text-sm text-red-600 mt-1 font-medium bg-red-100 px-2 py-0.5 rounded-full inline-block">
                            Action Required
                        </p>
                    )}
                </div>
                <div className={cn("p-3 rounded-lg border", stats.overdue > 0 ? "bg-red-100 border-red-200" : "bg-gray-50 border-gray-100")}>
                    <AlertTriangle className={cn("h-6 w-6", stats.overdue > 0 ? "text-red-600" : "text-gray-400")} />
                </div>
            </div>

            {/* Due This Week */}
            <div className="bg-white rounded-xl p-5 border border-gray-200 shadow-sm flex items-center justify-between">
                <div>
                    <p className="text-base font-semibold text-gray-500">Due This Week</p>
                    <p className="text-3xl font-bold text-gray-900 mt-1">
                        {stats.in_progress + (stats.planned || 0)}
                    </p>
                    <p className="text-sm text-amber-700 mt-1 font-medium bg-amber-50 px-2 py-0.5 rounded-full inline-block border border-amber-100">
                        Active Tasks
                    </p>
                </div>
                <div className="p-3 bg-amber-50 rounded-lg border border-amber-100">
                    <Clock className="h-6 w-6 text-amber-600" />
                </div>
            </div>

            {/* Completion Rate */}
            <div className="bg-white rounded-xl p-5 border border-gray-200 shadow-sm flex items-center justify-between">
                <div>
                    <p className="text-base font-semibold text-gray-500">Completion Rate</p>
                    <div className="flex items-baseline gap-2 mt-1">
                        <p className="text-3xl font-bold text-emerald-600">{completionRate}%</p>
                        <span className="text-sm text-gray-400">this month</span>
                    </div>
                    <p className="text-sm text-emerald-700 mt-1 font-medium flex items-center">
                        <TrendingUp className="h-3 w-3 mr-1" />
                        {stats.completed} tasks done
                    </p>
                </div>
                <div className="h-14 w-14 relative flex items-center justify-center">
                    <svg className="h-full w-full transform -rotate-90">
                        <circle cx="28" cy="28" r="22" stroke="#f3f4f6" strokeWidth="4" fill="none" />
                        <circle 
                            cx="28" cy="28" r="22" 
                            stroke="#10b981" strokeWidth="4" fill="none" 
                            strokeDasharray={138} 
                            strokeDashoffset={138 - (138 * (completionRate / 100))} 
                            className="transition-all duration-1000 ease-out" 
                            strokeLinecap="round" 
                        />
                    </svg>
                </div>
            </div>
        </div>
    );
}

export function DepartmentStatsCards({ stats, bottleneck = 0, topCategory = '-' }: DepartmentStatsCardsProps) {
    return (
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
            {/* Team Overdue */}
            <div className="bg-white rounded-xl p-5 border border-gray-200 shadow-sm flex items-center justify-between">
                <div>
                    <p className="text-base font-semibold text-gray-500">Team Overdue</p>
                    <p className={cn("text-3xl font-bold mt-1", bottleneck > 0 ? "text-red-600" : "text-gray-900")}>
                        {bottleneck}
                    </p>
                    {bottleneck > 0 ? (
                        <p className="text-sm text-red-600 mt-1 font-medium bg-red-50 px-2 py-0.5 rounded-full inline-block border border-red-100">
                            Needs attention
                        </p>
                    ) : (
                        <p className="text-sm text-emerald-700 mt-1 font-medium bg-emerald-50 px-2 py-0.5 rounded-full inline-block border border-emerald-100">
                            All on track
                        </p>
                    )}
                </div>
                <div className={cn("p-3 rounded-lg border", bottleneck > 0 ? "bg-red-100 border-red-200" : "bg-gray-50 border-gray-100")}>
                    <AlertTriangle className={cn("h-6 w-6", bottleneck > 0 ? "text-red-600" : "text-gray-400")} />
                </div>
            </div>

            {/* Total Active */}
            <div className="bg-white rounded-xl p-5 border border-gray-200 shadow-sm flex items-center justify-between">
                <div>
                    <p className="text-base font-semibold text-gray-500">Total Active</p>
                    <p className="text-3xl font-bold text-gray-900 mt-1">{stats?.total || 0}</p>
                    <p className="text-sm text-indigo-700 mt-1 font-medium bg-indigo-50 px-2 py-0.5 rounded-full inline-block border border-indigo-100">
                        Department Tasks
                    </p>
                </div>
                <div className="p-3 bg-indigo-50 rounded-lg border border-indigo-100">
                    <Users className="h-6 w-6 text-indigo-600" />
                </div>
            </div>

            {/* Top Category */}
            <div className="bg-white rounded-xl p-5 border border-gray-200 shadow-sm flex items-center justify-between">
                <div>
                    <p className="text-base font-semibold text-gray-500">Top Category</p>
                    <p className="text-3xl font-bold text-gray-900 mt-1 truncate max-w-[180px]" title={topCategory}>
                        {topCategory}
                    </p>
                    <p className="text-sm text-gray-500 mt-1">Most active work type</p>
                </div>
                <div className="p-3 bg-amber-50 rounded-lg border border-amber-100">
                    <Activity className="h-6 w-6 text-amber-600" />
                </div>
            </div>
        </div>
    );
}
