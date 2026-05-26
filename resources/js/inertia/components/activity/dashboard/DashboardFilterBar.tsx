import { Fragment } from 'react';
import { Popover, Transition } from '@headlessui/react';
import { Building2, Download, Filter } from 'lucide-react';

import { cn } from '@/lib/utils';
import { openDownloadInSameTab } from '@/lib/download';

type ViewMode = 'personal' | 'department' | 'executive';
type PeriodFilter = 'today' | 'week' | 'month' | 'year' | 'all';

interface DepartmentMember {
    id: number;
    name: string;
    department_id?: number;
}

interface SubDepartment {
    id: number;
    code: string;
    name: string;
}

interface DashboardFilterBarProps {
    hasExecutive: boolean;
    hasDepartmentStats: boolean;
    viewMode: ViewMode;
    periodQuickFilters: Array<{ key: PeriodFilter; label: string }>;
    distributionPeriod: PeriodFilter;
    deptDistributionPeriod: PeriodFilter;
    selectedDepartmentMember: string;
    selectedSubDepartment: string;
    departmentMembers: DepartmentMember[];
    subDepartments: SubDepartment[];
    onViewModeChange: (viewMode: ViewMode) => void;
    onDistributionPeriodChange: (period: PeriodFilter) => void;
    onDeptDistributionPeriodChange: (period: PeriodFilter) => void;
    onDepartmentMemberChange: (memberUserId: string) => void;
    onSubDepartmentChange: (deptId: string) => void;
}

export function DashboardFilterBar({
    hasExecutive,
    hasDepartmentStats,
    viewMode,
    periodQuickFilters,
    distributionPeriod,
    deptDistributionPeriod,
    selectedDepartmentMember,
    selectedSubDepartment,
    departmentMembers,
    subDepartments,
    onViewModeChange,
    onDistributionPeriodChange,
    onDeptDistributionPeriodChange,
    onDepartmentMemberChange,
    onSubDepartmentChange,
}: DashboardFilterBarProps) {
    return (
        <div className="mb-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between bg-white border border-slate-200/60 shadow-sm rounded-xl px-4 py-3">
            <div className="flex flex-wrap items-center gap-4">
                <div className="flex bg-slate-100/80 p-1 rounded-lg border border-slate-200/50">
                    {hasExecutive && (
                        <button
                            onClick={() => onViewModeChange('executive')}
                            className={cn(
                                'rounded-md px-4 py-1.5 text-sm font-medium transition-all duration-200',
                                viewMode === 'executive'
                                    ? 'bg-white text-slate-900 shadow-sm ring-1 ring-slate-900/5'
                                    : 'text-slate-500 hover:text-slate-700 hover:bg-slate-50'
                            )}
                        >
                            <span className="flex items-center gap-1.5">
                                <Building2 className="h-3.5 w-3.5" />
                                Executive
                            </span>
                        </button>
                    )}
                    <button
                        onClick={() => onViewModeChange('personal')}
                        className={cn(
                            'rounded-md px-4 py-1.5 text-sm font-medium transition-all duration-200',
                            viewMode === 'personal'
                                ? 'bg-white text-slate-900 shadow-sm ring-1 ring-slate-900/5'
                                : 'text-slate-500 hover:text-slate-700 hover:bg-slate-50'
                        )}
                    >
                        Personal
                    </button>
                    {hasDepartmentStats && (
                        <button
                            onClick={() => onViewModeChange('department')}
                            className={cn(
                                'rounded-md px-4 py-1.5 text-sm font-medium transition-all duration-200',
                                viewMode === 'department'
                                    ? 'bg-white text-slate-900 shadow-sm ring-1 ring-slate-900/5'
                                    : 'text-slate-500 hover:text-slate-700 hover:bg-slate-50'
                            )}
                        >
                            Department
                        </button>
                    )}
                </div>

                {viewMode !== 'executive' && (
                    <div className="hidden md:block w-px h-6 bg-slate-200" />
                )}

                {viewMode !== 'executive' && (
                    <div className="flex bg-slate-100/80 p-1 rounded-lg border border-slate-200/50">
                        {periodQuickFilters.map((period) => {
                            const activePeriod = viewMode === 'personal'
                                ? distributionPeriod
                                : deptDistributionPeriod;

                            return (
                                <button
                                    key={period.key}
                                    onClick={() => viewMode === 'personal'
                                        ? onDistributionPeriodChange(period.key)
                                        : onDeptDistributionPeriodChange(period.key)}
                                    className={cn(
                                        'rounded-md px-4 py-1.5 text-sm font-medium transition-all duration-200',
                                        activePeriod === period.key
                                            ? 'bg-white text-slate-900 shadow-sm ring-1 ring-slate-900/5'
                                            : 'text-slate-500 hover:text-slate-700 hover:bg-slate-50'
                                    )}
                                >
                                    {period.label}
                                </button>
                            );
                        })}
                    </div>
                )}
            </div>

            {viewMode !== 'executive' && (
                <div className="flex items-center gap-3">
                    {viewMode === 'department' ? (
                        <Popover className="relative">
                            <Popover.Button className="flex items-center justify-center rounded-lg bg-white border border-slate-200 px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50">
                                <Filter className="mr-2 h-4 w-4 text-slate-500" />
                                Filter
                                {(selectedDepartmentMember || selectedSubDepartment) ? (
                                    <span className="ml-2 inline-flex h-2 w-2 rounded-full bg-blue-500" />
                                ) : null}
                            </Popover.Button>
                            <Transition
                                as={Fragment}
                                enter="transition ease-out duration-200"
                                enterFrom="opacity-0 translate-y-1"
                                enterTo="opacity-100 translate-y-0"
                                leave="transition ease-in duration-150"
                                leaveFrom="opacity-100 translate-y-0"
                                leaveTo="opacity-0 translate-y-1"
                            >
                                <Popover.Panel className="absolute right-0 z-20 mt-2 w-72 rounded-xl border border-slate-200 bg-white p-4 shadow-lg">
                                    <div className="space-y-3">
                                        {subDepartments.length > 0 && (
                                            <div>
                                                <label
                                                    htmlFor="activity-dashboard-subdept-filter"
                                                    className="mb-1.5 block text-xs font-medium text-slate-500"
                                                >
                                                    Sub-department
                                                </label>
                                                <select
                                                    id="activity-dashboard-subdept-filter"
                                                    value={selectedSubDepartment}
                                                    onChange={(event) => onSubDepartmentChange(event.target.value)}
                                                    className="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 outline-none transition focus:border-[#16599c] focus:ring-2 focus:ring-[#16599c]/20"
                                                >
                                                    <option value="">All sub-departments</option>
                                                    {subDepartments.map((dept) => (
                                                        <option key={dept.id} value={String(dept.id)}>
                                                            {dept.name}
                                                        </option>
                                                    ))}
                                                </select>
                                            </div>
                                        )}
                                        <div>
                                            <label
                                                htmlFor="activity-dashboard-member-filter"
                                                className="mb-1.5 block text-xs font-medium text-slate-500"
                                            >
                                                Member
                                            </label>
                                            <select
                                                id="activity-dashboard-member-filter"
                                                value={selectedDepartmentMember}
                                                onChange={(event) => onDepartmentMemberChange(event.target.value)}
                                                className="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 outline-none transition focus:border-[#16599c] focus:ring-2 focus:ring-[#16599c]/20"
                                            >
                                                <option value="">All members</option>
                                                {departmentMembers.map((member) => (
                                                    <option key={member.id} value={String(member.id)}>
                                                        {member.name}
                                                    </option>
                                                ))}
                                            </select>
                                        </div>
                                    </div>
                                </Popover.Panel>
                            </Transition>
                        </Popover>
                    ) : null}
                    <button
                        type="button"
                        onClick={() => openDownloadInSameTab(route('activity.task.export', {
                            scope: viewMode === 'personal' ? 'my' : 'department',
                            ...(viewMode === 'department' && selectedDepartmentMember
                                ? { member_user_id: selectedDepartmentMember }
                                : {}),
                        }))}
                        className="flex items-center justify-center rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-blue-600"
                    >
                        <Download className="mr-2 h-4 w-4" />
                        Export Report
                    </button>
                </div>
            )}
        </div>
    );
}

export default DashboardFilterBar;
