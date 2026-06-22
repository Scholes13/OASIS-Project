import { Activity, AlertTriangle, BarChart3, Building2, ChevronRight, TrendingUp } from 'lucide-react';
import { motion } from 'framer-motion';

import { cn } from '@/lib/utils';

interface ExecutiveViewProps {
    executiveStats: any;
    onNavigateToAdminDashboard: (businessUnitId: number, departmentId?: number) => void;
}

export function ExecutiveView({ executiveStats, onNavigateToAdminDashboard }: ExecutiveViewProps) {
    return (
        <>
            <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                <div className="rounded-xl border border-slate-200/60 bg-white p-6 shadow-sm flex flex-col">
                    <div className="flex items-center gap-3 mb-4">
                        <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                            <BarChart3 className="h-5 w-5" strokeWidth={2} />
                        </div>
                        <h3 className="text-sm font-medium text-slate-500">Total Tasks</h3>
                    </div>
                    <div className="text-3xl font-bold tracking-tight text-slate-900 mb-2">
                        {executiveStats.aggregate.total.toLocaleString('id-ID')}
                    </div>
                    <div className="mt-auto text-sm text-slate-500">
                        Across <span className="font-medium text-slate-700">{executiveStats.aggregate.total_business_units}</span> business units
                    </div>
                </div>

                <div className="rounded-xl border border-slate-200/60 bg-white p-6 shadow-sm flex flex-col">
                    <div className="flex items-center gap-3 mb-4">
                        <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">
                            <TrendingUp className="h-5 w-5" strokeWidth={2} />
                        </div>
                        <h3 className="text-sm font-medium text-slate-500">Completion Rate</h3>
                    </div>
                    <div className="text-3xl font-bold tracking-tight text-slate-900 mb-2">
                        {executiveStats.aggregate.total > 0
                            ? Math.round((executiveStats.aggregate.completed / executiveStats.aggregate.total) * 100)
                            : 0}%
                    </div>
                    <div className="mt-auto text-sm text-slate-500">
                        <span className="font-medium text-emerald-600">{executiveStats.aggregate.completed.toLocaleString('id-ID')}</span> of {executiveStats.aggregate.total.toLocaleString('id-ID')} completed
                    </div>
                </div>

                <div
                    className={cn(
                        "rounded-xl border p-6 shadow-sm flex flex-col",
                        executiveStats.aggregate.overdue > 0
                            ? "border-red-200/60 bg-red-50/30"
                            : "border-slate-200/60 bg-white"
                    )}
                >
                    <div className="flex items-center gap-3 mb-4">
                        <div
                            className={cn(
                                "flex h-10 w-10 items-center justify-center rounded-lg",
                                executiveStats.aggregate.overdue > 0
                                    ? "bg-red-100 text-red-600"
                                    : "bg-slate-50 text-slate-400"
                            )}
                        >
                            <AlertTriangle className="h-5 w-5" strokeWidth={2} />
                        </div>
                        <h3 className="text-sm font-medium text-slate-500">Overdue Tasks</h3>
                    </div>
                    <div
                        className={cn(
                            "text-3xl font-bold tracking-tight mb-2",
                            executiveStats.aggregate.overdue > 0 ? "text-red-600" : "text-slate-900"
                        )}
                    >
                        {executiveStats.aggregate.overdue.toLocaleString('id-ID')}
                    </div>
                    <div className="mt-auto text-sm text-slate-500">
                        {executiveStats.aggregate.overdue > 0 ? 'Requires immediate attention' : 'All tasks are on schedule'}
                    </div>
                </div>

                <div className="rounded-xl border border-slate-200/60 bg-white p-6 shadow-sm flex flex-col">
                    <div className="flex items-center gap-3 mb-4">
                        <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-50 text-amber-600">
                            <Activity className="h-5 w-5" strokeWidth={2} />
                        </div>
                        <h3 className="text-sm font-medium text-slate-500">In Progress</h3>
                    </div>
                    <div className="text-3xl font-bold tracking-tight text-slate-900 mb-2">
                        {executiveStats.aggregate.in_progress.toLocaleString('id-ID')}
                    </div>
                    <div className="mt-auto text-sm text-slate-500">
                        <span className="font-medium text-slate-700">{executiveStats.aggregate.planned}</span> planned tasks pending
                    </div>
                </div>
            </div>

            <div className="rounded-xl border border-slate-200/60 bg-white shadow-sm overflow-hidden">
                <div className="flex items-center justify-between border-b border-slate-100 px-6 py-5 bg-white">
                    <div className="flex items-center gap-3">
                        <Building2 className="h-5 w-5 text-slate-400" />
                        <h3 className="text-base font-semibold text-slate-900">Business Unit Overview</h3>
                    </div>
                    <span className="text-xs font-medium text-slate-500 bg-slate-100 px-2 py-1 rounded-md">
                        {executiveStats.businessUnits.length} units
                    </span>
                </div>

                <div className="divide-y divide-slate-100">
                    {executiveStats.businessUnits.length > 0 ? executiveStats.businessUnits.map((bu: any) => (
                        <div
                            key={bu.id}
                            onClick={() => onNavigateToAdminDashboard(bu.id)}
                            className="group flex flex-col gap-4 px-6 py-5 transition-colors hover:bg-blue-50/50 cursor-pointer sm:flex-row sm:items-center"
                            title={`View ${bu.name} activity details`}
                        >
                            <div className="flex items-center gap-4 sm:w-[200px] shrink-0">
                                {bu.logo ? (
                                    <img
                                        src={bu.logo}
                                        alt={bu.code}
                                        className="h-10 w-10 shrink-0 rounded-lg object-contain bg-white border border-slate-200 p-1"
                                    />
                                ) : (
                                    <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-blue-600 text-white text-xs font-bold">
                                        {bu.code}
                                    </div>
                                )}
                                <div className="min-w-0">
                                    <p className="text-sm font-semibold text-slate-900 truncate">{bu.name}</p>
                                    <p className="text-xs text-slate-500 mt-0.5">{bu.total.toLocaleString('id-ID')} total tasks</p>
                                </div>
                            </div>

                            <div className="flex-1 flex items-center gap-4">
                                <div className="flex-1">
                                    <div className="h-2.5 w-full overflow-hidden rounded-full bg-slate-100">
                                        <motion.div
                                            initial={{ width: 0 }}
                                            animate={{ width: `${bu.completion_rate}%` }}
                                            transition={{ duration: 0.8, ease: "easeOut" }}
                                            className={cn(
                                                "h-full rounded-full",
                                                bu.completion_rate >= 80
                                                    ? "bg-emerald-500"
                                                    : bu.completion_rate >= 50
                                                        ? "bg-amber-500"
                                                        : "bg-red-500"
                                            )}
                                        />
                                    </div>
                                </div>
                                <span className="text-sm font-semibold text-slate-900 w-12 text-right tabular-nums">
                                    {bu.completion_rate}%
                                </span>
                            </div>

                            <div className="flex items-center gap-2 sm:w-auto shrink-0">
                                <span className="inline-flex items-center rounded-md bg-emerald-50 px-2 py-1 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-600/10">
                                    ✓ {bu.completed}
                                </span>
                                <span className="inline-flex items-center rounded-md bg-amber-50 px-2 py-1 text-xs font-medium text-amber-700 ring-1 ring-inset ring-amber-600/10">
                                    ⏳ {bu.in_progress}
                                </span>
                                {bu.overdue > 0 && (
                                    <span className="inline-flex items-center rounded-md bg-red-50 px-2 py-1 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-600/10">
                                        ⚠ {bu.overdue}
                                    </span>
                                )}
                            </div>

                            <ChevronRight className="h-4 w-4 text-slate-300 group-hover:text-blue-500 transition-colors shrink-0" />
                        </div>
                    )) : (
                        <div className="flex flex-col items-center justify-center py-16 text-center">
                            <div className="rounded-full bg-slate-50 p-4 border border-slate-100 mb-4">
                                <Building2 className="h-6 w-6 text-slate-400" />
                            </div>
                            <h4 className="text-sm font-semibold text-slate-900">No business unit data</h4>
                            <p className="mt-1 text-sm text-slate-500">Activity data will appear here once tasks are logged</p>
                        </div>
                    )}
                </div>
            </div>

            {executiveStats.topOverdueDepartments.length > 0 && (
                <div className="rounded-xl border border-red-200/60 bg-red-50/20 shadow-sm overflow-hidden">
                    <div className="flex items-center gap-3 border-b border-red-100 px-6 py-4 bg-red-50/40">
                        <AlertTriangle className="h-4 w-4 text-red-500" />
                        <h3 className="text-sm font-semibold text-red-900">Departments Requiring Attention</h3>
                    </div>
                    <div className="divide-y divide-red-100/60">
                        {executiveStats.topOverdueDepartments.map((dept: any, index: number) => (
                            <div
                                key={`${dept.departmentId}-${dept.businessUnitId}`}
                                onClick={() => onNavigateToAdminDashboard(dept.businessUnitId, dept.departmentId)}
                                className="group flex items-center justify-between px-6 py-3.5 hover:bg-red-50/60 cursor-pointer transition-colors"
                                title={`View ${dept.department} details in ${dept.businessUnit}`}
                            >
                                <div className="flex items-center gap-3">
                                    <span className="flex h-6 w-6 items-center justify-center rounded-full bg-red-100 text-xs font-bold text-red-700">
                                        {index + 1}
                                    </span>
                                    <div>
                                        <p className="text-sm font-medium text-slate-900">{dept.department}</p>
                                        <p className="text-xs text-slate-500">{dept.businessUnit}</p>
                                    </div>
                                </div>
                                <div className="flex items-center gap-2">
                                    <span className="inline-flex items-center rounded-md bg-red-100 px-2.5 py-1 text-xs font-semibold text-red-700">
                                        {dept.overdueCount} overdue
                                    </span>
                                    <ChevronRight className="h-4 w-4 text-red-300 group-hover:text-red-500 transition-colors" />
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            )}
        </>
    );
}

export default ExecutiveView;
