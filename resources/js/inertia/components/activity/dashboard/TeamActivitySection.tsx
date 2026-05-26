import { Activity } from 'lucide-react';

import { cn } from '@/lib/utils';

interface TaskActivityRow {
    id: string;
    memberName: string;
    memberRole: string;
    activity: string;
    totalMinutes: number;
}

interface TeamActivitySectionProps {
    viewMode: 'personal' | 'department' | 'executive';
    inProgressCount: number;
    rows: TaskActivityRow[];
    pagedRows: TaskActivityRow[];
    taskPage: number;
    totalTaskPages: number;
    tasksPerPage: number;
    focusedMemberName?: string | null;
    onPageChange: (page: number | ((page: number) => number)) => void;
}

export function TeamActivitySection({
    viewMode,
    inProgressCount,
    rows,
    pagedRows,
    taskPage,
    totalTaskPages,
    tasksPerPage,
    focusedMemberName,
    onPageChange,
}: TeamActivitySectionProps) {
    return (
        <div className="rounded-xl border border-slate-200/60 bg-white shadow-sm lg:col-span-2 overflow-hidden flex flex-col">
            <div className="flex items-center justify-between border-b border-slate-100 px-6 py-5 bg-white">
                <h3 className="text-base font-semibold text-slate-900">
                    {viewMode === 'department' ? 'Team Activity' : 'My Activity'}
                </h3>
                <span className="text-xs font-medium text-slate-500 bg-slate-100 px-2 py-1 rounded-md">
                    {inProgressCount} in progress
                </span>
            </div>

            <div className="flex-1 flex flex-col">
                {rows.length > 0 ? (
                    <>
                        <div className="flex flex-col divide-y divide-slate-100">
                            {pagedRows.map((row) => {
                                const days = Math.floor(row.totalMinutes / 1440);
                                const hours = Math.floor((row.totalMinutes % 1440) / 60);
                                const mins = row.totalMinutes % 60;
                                const durationText = days > 0
                                    ? `${days}d ${hours}h`
                                    : `${hours}h ${String(mins).padStart(2, '0')}m`;

                                return (
                                    <div
                                        key={row.id}
                                        className="group grid grid-cols-[auto_1fr_auto] items-center gap-x-4 px-6 py-4 transition-colors hover:bg-slate-50/70"
                                    >
                                        <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-blue-600 text-white text-sm font-semibold ring-2 ring-white">
                                            {row.memberName.charAt(0).toUpperCase()}
                                        </div>

                                        <div className="min-w-0">
                                            <div className="flex items-baseline gap-2">
                                                <p className="truncate text-sm font-semibold text-slate-900">
                                                    {row.memberName}
                                                </p>
                                                <p className="hidden sm:block truncate text-xs text-slate-400 shrink-0">
                                                    {row.memberRole}
                                                </p>
                                            </div>
                                            <p className="mt-0.5 text-sm text-slate-600 line-clamp-1">
                                                {row.activity}
                                            </p>
                                        </div>

                                        <div className="text-sm font-semibold text-slate-900 tabular-nums whitespace-nowrap pl-2">
                                            {durationText}
                                        </div>
                                    </div>
                                );
                            })}
                        </div>

                        {totalTaskPages > 1 && (
                            <div className="flex items-center justify-between border-t border-slate-100 px-6 py-3">
                                <span className="text-xs text-slate-500">
                                    {taskPage * tasksPerPage + 1}-{Math.min((taskPage + 1) * tasksPerPage, rows.length)} of {rows.length} tasks
                                </span>
                                <div className="flex items-center gap-1">
                                    <button
                                        onClick={() => onPageChange((page) => Math.max(0, page - 1))}
                                        disabled={taskPage === 0}
                                        className={cn(
                                            'px-3 py-1 text-xs font-medium rounded-md transition-colors',
                                            taskPage === 0
                                                ? 'text-slate-300 cursor-not-allowed'
                                                : 'text-[#16599c] hover:bg-blue-50'
                                        )}
                                    >
                                        Prev
                                    </button>
                                    {Array.from({ length: totalTaskPages }, (_, index) => (
                                        <button
                                            key={index}
                                            onClick={() => onPageChange(index)}
                                            className={cn(
                                                'h-7 w-7 text-xs font-medium rounded-md transition-colors',
                                                taskPage === index
                                                    ? 'bg-[#16599c] text-white'
                                                    : 'text-[#16599c] hover:bg-blue-50'
                                            )}
                                        >
                                            {index + 1}
                                        </button>
                                    ))}
                                    <button
                                        onClick={() => onPageChange((page) => Math.min(totalTaskPages - 1, page + 1))}
                                        disabled={taskPage === totalTaskPages - 1}
                                        className={cn(
                                            'px-3 py-1 text-xs font-medium rounded-md transition-colors',
                                            taskPage === totalTaskPages - 1
                                                ? 'text-slate-300 cursor-not-allowed'
                                                : 'text-[#16599c] hover:bg-blue-50'
                                        )}
                                    >
                                        Next
                                    </button>
                                </div>
                            </div>
                        )}
                    </>
                ) : (
                    <div className="flex flex-1 flex-col items-center justify-center py-16 text-center">
                        <div className="rounded-full bg-slate-50 p-4 border border-slate-100 mb-4">
                            <Activity className="h-6 w-6 text-slate-400" />
                        </div>
                        <h4 className="text-sm font-semibold text-slate-900">No tasks in progress</h4>
                        <p className="mt-1 text-sm text-slate-500">
                            {viewMode === 'department' && focusedMemberName
                                ? `Tidak ada aktivitas untuk ${focusedMemberName} pada filter yang aktif.`
                                : 'Tasks being worked on will appear here'}
                        </p>
                    </div>
                )}
            </div>
        </div>
    );
}

export default TeamActivitySection;
