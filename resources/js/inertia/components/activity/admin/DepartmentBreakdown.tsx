import { Link } from '@inertiajs/react';
import { motion } from 'framer-motion';
import { Activity, ArrowRight, Building2, Clock, Flame, Timer, Zap } from 'lucide-react';
import { Badge } from '@/components/ui/Badge';
import { Card } from '@/components/ui/Card';
import { EmptyState } from '@/components/ui/empty-state';
import { cn } from '@/lib/utils';
import type { Department } from '@/types';

interface DepartmentStat {
    department: Department;
    total: number;
    completed: number;
    in_progress: number;
    planned: number;
    cancelled: number;
    completion_rate: number;
    total_hours: number;
}

interface DepartmentBreakdownProps {
    departmentStats: DepartmentStat[];
    dateFrom: string;
    dateTo: string;
}

function getBusynessLevel(total: number, completionRate: number) {
    if (total >= 20 && completionRate < 50) {
        return { label: 'Overloaded', icon: Flame, bgClass: 'bg-red-50', textClass: 'text-red-700' };
    }
    if (total >= 10) {
        return { label: 'Sibuk', icon: Zap, bgClass: 'bg-amber-50', textClass: 'text-amber-700' };
    }
    if (total >= 3) {
        return { label: 'Normal', icon: Activity, bgClass: 'bg-emerald-50', textClass: 'text-emerald-700' };
    }

    return { label: 'Rendah', icon: Clock, bgClass: 'bg-gray-50', textClass: 'text-gray-600' };
}

export default function DepartmentBreakdown({
    departmentStats,
    dateFrom,
    dateTo,
}: DepartmentBreakdownProps) {
    return (
        <div>
            <h2 className="text-base font-semibold text-gray-900 flex items-center gap-2 mb-4">
                <Building2 className="w-5 h-5 text-primary" strokeWidth={1.5} />
                Detail per Department
                {departmentStats.length > 0 && (
                    <Badge variant="default" size="sm">{departmentStats.length} dept</Badge>
                )}
            </h2>

            {departmentStats.length === 0 ? (
                <EmptyState
                    icon={<Building2 className="w-12 h-12" />}
                    title="No active departments"
                    description="There are no active departments in this business unit."
                />
            ) : (
                <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                    {departmentStats.map((stat) => {
                        const busyness = getBusynessLevel(stat.total, stat.completion_rate);
                        const BusynessIcon = busyness.icon;
                        const avgHoursPerTask = stat.total > 0 ? (stat.total_hours / stat.total).toFixed(1) : '0';

                        return (
                            <Link
                                key={stat.department.id}
                                href={route('activity.admin.department', { department: stat.department.id }) + `?date_from=${dateFrom}&date_to=${dateTo}`}
                                className="block group"
                            >
                                <motion.div
                                    whileHover={{ y: -2 }}
                                    transition={{ type: 'spring', stiffness: 400, damping: 25 }}
                                >
                                    <Card className="p-5 hover:border-primary/50 hover:shadow-md transition-all">
                                        <div className="flex items-start justify-between mb-3">
                                            <div className="min-w-0 flex-1">
                                                <h3 className="text-sm font-semibold text-gray-900 group-hover:text-primary transition-colors truncate">
                                                    {stat.department.name}
                                                </h3>
                                                <p className="text-[11px] text-gray-400 mt-0.5">{stat.department.code}</p>
                                            </div>
                                            <span className={cn('inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold flex-shrink-0', busyness.bgClass, busyness.textClass)}>
                                                <BusynessIcon className="w-3 h-3" strokeWidth={2} />
                                                {busyness.label}
                                            </span>
                                        </div>

                                        <div className="flex items-center gap-2 mb-3">
                                            <div className="flex-1 h-2 bg-gray-100 rounded-full overflow-hidden">
                                                <motion.div
                                                    initial={{ width: 0 }}
                                                    animate={{ width: `${Math.min(stat.completion_rate, 100)}%` }}
                                                    transition={{ duration: 0.8, ease: 'easeOut' }}
                                                    className={cn('h-full rounded-full', stat.completion_rate >= 70 ? 'bg-emerald-500' : stat.completion_rate >= 40 ? 'bg-amber-500' : 'bg-red-400')}
                                                />
                                            </div>
                                            <span className="text-xs font-bold text-gray-700 tabular-nums w-10 text-right">{stat.completion_rate}%</span>
                                        </div>

                                        <div className="grid grid-cols-4 gap-1 text-center mb-3">
                                            <StatCell label="Done" value={stat.completed} className="text-emerald-600" />
                                            <StatCell label="Progress" value={stat.in_progress} className="text-blue-600" />
                                            <StatCell label="Planned" value={stat.planned} className="text-amber-600" />
                                            <StatCell label="Cancel" value={stat.cancelled} className="text-red-400" />
                                        </div>

                                        <div className="flex items-center justify-between pt-2 border-t border-gray-100">
                                            <div className="flex items-center gap-3 text-[11px] text-gray-500">
                                                <span className="flex items-center gap-1">
                                                    <Timer className="w-3 h-3" strokeWidth={1.5} />
                                                    {stat.total_hours}h logged
                                                </span>
                                                <span className="flex items-center gap-1">
                                                    <Activity className="w-3 h-3" strokeWidth={1.5} />
                                                    ~{avgHoursPerTask}h/task
                                                </span>
                                            </div>
                                            <ArrowRight className="w-4 h-4 text-gray-300 group-hover:text-primary group-hover:translate-x-0.5 transition-all" strokeWidth={1.5} />
                                        </div>
                                    </Card>
                                </motion.div>
                            </Link>
                        );
                    })}
                </div>
            )}
        </div>
    );
}

function StatCell({ label, value, className }: { label: string; value: number; className: string }) {
    return (
        <div>
            <p className={cn('text-base font-bold tabular-nums', className)}>{value}</p>
            <p className="text-[10px] text-gray-400">{label}</p>
        </div>
    );
}
