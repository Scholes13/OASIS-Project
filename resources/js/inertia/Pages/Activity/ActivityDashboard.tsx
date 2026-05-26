import { useEffect, useMemo, useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { motion, AnimatePresence } from 'framer-motion';
import { FocusBreakdownPanel } from '@/components/activity/dashboard';
import ExecutiveView from '@/components/activity/dashboard/ExecutiveView';
import TeamActivitySection from '@/components/activity/dashboard/TeamActivitySection';
import DashboardMetricCards from '@/components/activity/dashboard/DashboardMetricCards';
import DashboardFilterBar from '@/components/activity/dashboard/DashboardFilterBar';
import { generateInsight } from '@/lib/insightGenerator';
import { PERIOD_LABELS } from '@/lib/activityConstants';
import type { PageProps } from '@/types';

type PeriodFilter = 'today' | 'week' | 'month' | 'year' | 'all';

interface TaskBasic {
    id: number;
    title?: string;
    task_title?: string;
    due_date?: string | null;
    is_critical?: boolean;
    status?: string;
    task_description?: string;
    activity_type?: { name: string; color: string };
    duration_minutes?: number;
    started_at?: string | null;
    task_date?: string | null;
    created_at?: string | null;
    participants?: Array<{ id?: number; user_id?: number; name?: string; user?: { name?: string }; primary_position?: { name?: string } }>;
}

interface Stats {
    total: number;
    completed: number;
    in_progress: number;
    overdue: number;
    planned?: number;
    completed_this_month?: number;
}

interface PaginatedData<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
    prev_page_url: string | null;
    next_page_url: string | null;
}

interface FocusBreakdownItem {
    category: string;
    subcategory: string;
    count: number;
    percentage_of_report: number;
    color?: string;
}

interface FocusBreakdown {
    total_activities: number;
    top_category: {
        name: string;
        count: number;
        percentage_of_report: number;
    };
    top_subcategory: {
        name: string;
        count: number;
        percentage_of_report: number;
    };
    items: FocusBreakdownItem[];
}

interface PersonalVisuals {
    roadmap: PaginatedData<TaskBasic>;
    upcoming: TaskBasic[];
    distribution: { name: string; color: string; value: number }[];
    focus_breakdown: FocusBreakdown;
}

interface DepartmentVisuals {
    roadmap: PaginatedData<TaskBasic>;
    upcoming: TaskBasic[];
    distribution: { name: string; color: string; value: number }[];
    focus_breakdown: FocusBreakdown;
    bottleneck: number;
    top_category: string;
}

interface ExecutiveBusinessUnit {
    id: number;
    code: string;
    name: string;
    logo: string | null;
    total: number;
    completed: number;
    in_progress: number;
    planned: number;
    overdue: number;
    completed_this_month: number;
    completion_rate: number;
}

interface ExecutiveStats {
    aggregate: Stats & { total_business_units: number };
    businessUnits: ExecutiveBusinessUnit[];
    topOverdueDepartments: Array<{
        departmentId: number;
        department: string;
        businessUnitId: number;
        businessUnit: string;
        overdueCount: number;
    }>;
}

interface DashboardProps extends PageProps {
    personalStats: Stats;
    personalVisuals: PersonalVisuals;
    departmentStats: Stats | null;
    departmentVisuals: DepartmentVisuals | null;
    departmentMembers?: Array<{ id: number; name: string; department_id?: number }>;
    subDepartments?: Array<{ id: number; code: string; name: string }>;
    canViewReports?: boolean;
    executiveStats?: ExecutiveStats | null;
    queryParams?: {
        tab?: string;
        page?: string;
        dept_tab?: string;
        dept_page?: string;
        distribution_period?: PeriodFilter;
        dept_distribution_period?: PeriodFilter;
        member_user_id?: string | null;
        dept_filter?: string | null;
    };
}

const smoothTransition = { duration: 0.35, ease: "easeInOut" as const };

export default function ActivityDashboard({
    personalStats,
    personalVisuals,
    departmentStats,
    departmentVisuals,
    departmentMembers = [],
    subDepartments = [],
    canViewReports,
    executiveStats,
    queryParams
}: DashboardProps) {
    const hasExecutive = !!(canViewReports && executiveStats);
    const [viewMode, setViewMode] = useState<'personal' | 'department' | 'executive'>(
        hasExecutive ? 'executive' : departmentStats ? 'department' : 'personal'
    );
    const [distributionPeriod, setDistributionPeriod] = useState<PeriodFilter>(
        (queryParams?.distribution_period as PeriodFilter) || 'all'
    );
    const [deptDistributionPeriod, setDeptDistributionPeriod] = useState<PeriodFilter>(
        (queryParams?.dept_distribution_period as PeriodFilter) || 'all'
    );
    const [selectedDepartmentMember, setSelectedDepartmentMember] = useState<string>(queryParams?.member_user_id ?? '');
    const [selectedSubDepartment, setSelectedSubDepartment] = useState<string>(queryParams?.dept_filter ?? '');
    const [isDistributionLoading, setIsDistributionLoading] = useState(false);
    const [isDeptDistributionLoading, setIsDeptDistributionLoading] = useState(false);

    useEffect(() => {
        setSelectedDepartmentMember(queryParams?.member_user_id ?? '');
    }, [queryParams?.member_user_id]);

    useEffect(() => {
        setSelectedSubDepartment(queryParams?.dept_filter ?? '');
    }, [queryParams?.dept_filter]);

    /** Navigate to admin dashboard for a specific BU, switching context first */
    const navigateToAdminDashboard = (businessUnitId: number, departmentId?: number) => {
        // Switch BU context then redirect to admin dashboard
        router.post(route('api.business-unit.switch'), { business_unit_id: businessUnitId }, {
            preserveState: false,
            onSuccess: () => {
                const params: Record<string, string> = {};
                if (departmentId) {
                    params.department_id = String(departmentId);
                }
                router.visit(route('activity.admin.dashboard', params));
            },
        });
    };

    const periodQuickFilters: Array<{ key: PeriodFilter; label: string }> = [
        { key: 'today', label: PERIOD_LABELS.day },
        { key: 'week', label: PERIOD_LABELS.week },
        { key: 'month', label: PERIOD_LABELS.month },
    ];

    const activeStats = viewMode === 'department' && departmentStats ? departmentStats : personalStats;
    const activeVisuals = viewMode === 'department' && departmentVisuals ? departmentVisuals : personalVisuals;
    const roadmapData = activeVisuals?.roadmap?.data || [];

    const completionRate = activeStats.total > 0
        ? Math.round((activeStats.completed / activeStats.total) * 100)
        : 0;

    const trackedDurationMinutes = roadmapData.reduce((sum, task) => {
        const duration = Number(task.duration_minutes || 0);
        return sum + (Number.isFinite(duration) ? duration : 0);
    }, 0);

    const fallbackDurationMinutes =
        (activeStats.completed * 95) +
        (activeStats.in_progress * 60) +
        (Math.max(activeStats.total - activeStats.completed - activeStats.in_progress, 0) * 30);

    const totalDurationMinutes = trackedDurationMinutes > 0 ? trackedDurationMinutes : fallbackDurationMinutes;
    const totalHours = Math.floor(totalDurationMinutes / 60);
    const totalMinutes = totalDurationMinutes % 60;
    const activeProjects = activeVisuals?.distribution?.length || 0;
    const activeFocusBreakdown = activeVisuals?.focus_breakdown;

    // Filter only in_progress tasks for real-time Team Activity display
    const inProgressTasks = useMemo(() =>
        roadmapData.filter((t) => t.status === 'in_progress'),
    [roadmapData]);

    const taskActivityRows = useMemo(() => {
        const rows: {
            id: string;
            memberName: string;
            memberRole: string;
            activity: string;
            totalMinutes: number;
        }[] = [];

        const now = new Date();
        inProgressTasks.forEach((task, taskIndex) => {
            const participants = Array.isArray(task.participants) && task.participants.length > 0
                ? task.participants
                : [{ id: `self-${task.id}`, name: 'You' }];
            const title = task.task_title || task.title || 'Task';
            const activityLabel = `Working on: ${title}`;

            // Calculate real elapsed time from started_at (when task was set to in_progress)
            const startedAt = task.started_at || task.created_at;
            const elapsed = startedAt ? Math.max(0, Math.floor((now.getTime() - new Date(startedAt).getTime()) / 60000)) : 0;

            const memberName = participants.map((m: any) => m.name || m.user?.name || 'Member').join(', ');
            const memberRole = (participants[0] as any)?.primary_position?.name || 'Team Member';

            rows.push({
                id: `${task.id}-${taskIndex}`,
                memberName,
                memberRole,
                activity: activityLabel,
                totalMinutes: elapsed,
            });
        });

        return rows;
    }, [inProgressTasks]);

    const TASKS_PER_PAGE = 10;
    const [taskPage, setTaskPage] = useState(0);
    const totalTaskPages = Math.ceil(taskActivityRows.length / TASKS_PER_PAGE);
    const pagedTasks = taskActivityRows.slice(
        taskPage * TASKS_PER_PAGE,
        (taskPage + 1) * TASKS_PER_PAGE
    );


    const focusInsight = generateInsight(activeFocusBreakdown?.top_category, viewMode);
    const isFocusLoading = viewMode === 'personal' ? isDistributionLoading : isDeptDistributionLoading;

    // Handlers for period changes
    const handleDistributionPeriodChange = (period: PeriodFilter) => {
        setDistributionPeriod(period);
        setIsDistributionLoading(true);
        router.get(
            route('activity.dashboard'),
            { distribution_period: period },
            {
                preserveState: true,
                preserveScroll: true,
                only: ['personalVisuals', 'personalStats', 'queryParams'],
                onFinish: () => setIsDistributionLoading(false)
            }
        );
    };

    const handleDeptDistributionPeriodChange = (period: PeriodFilter) => {
        setDeptDistributionPeriod(period);
        setIsDeptDistributionLoading(true);
        router.get(
            route('activity.dashboard'),
            {
                dept_distribution_period: period,
                ...(selectedDepartmentMember ? { member_user_id: selectedDepartmentMember } : {}),
            },
            {
                preserveState: true,
                preserveScroll: true,
                only: ['departmentVisuals', 'departmentStats', 'queryParams', 'departmentMembers'],
                onFinish: () => setIsDeptDistributionLoading(false)
            }
        );
    };

    const handleDepartmentMemberChange = (memberUserId: string) => {
        setSelectedDepartmentMember(memberUserId);
        setIsDeptDistributionLoading(true);
        router.get(
            route('activity.dashboard'),
            {
                dept_distribution_period: deptDistributionPeriod,
                ...(selectedSubDepartment ? { dept_filter: selectedSubDepartment } : {}),
                ...(memberUserId ? { member_user_id: memberUserId } : {}),
            },
            {
                preserveState: true,
                preserveScroll: true,
                only: ['departmentVisuals', 'departmentStats', 'queryParams', 'departmentMembers', 'subDepartments'],
                onFinish: () => setIsDeptDistributionLoading(false),
            }
        );
    };

    const handleSubDepartmentChange = (deptId: string) => {
        // Switching sub-department resets the member filter, since the
        // member list is scoped to the chosen sub-dept.
        setSelectedSubDepartment(deptId);
        setSelectedDepartmentMember('');
        setIsDeptDistributionLoading(true);
        router.get(
            route('activity.dashboard'),
            {
                dept_distribution_period: deptDistributionPeriod,
                ...(deptId ? { dept_filter: deptId } : {}),
            },
            {
                preserveState: true,
                preserveScroll: true,
                only: ['departmentVisuals', 'departmentStats', 'queryParams', 'departmentMembers', 'subDepartments'],
                onFinish: () => setIsDeptDistributionLoading(false),
            }
        );
    };

    const handleViewModeChange = (nextViewMode: 'personal' | 'department' | 'executive') => {
        setViewMode(nextViewMode);

        if (nextViewMode === 'department' || !selectedDepartmentMember) {
            return;
        }

        setSelectedDepartmentMember('');
        setIsDeptDistributionLoading(true);
        router.get(
            route('activity.dashboard'),
            {
                distribution_period: distributionPeriod,
                dept_distribution_period: deptDistributionPeriod,
            },
            {
                preserveState: true,
                preserveScroll: true,
                only: ['departmentVisuals', 'departmentStats', 'queryParams', 'departmentMembers'],
                onFinish: () => setIsDeptDistributionLoading(false),
            }
        );
    };

    const focusedDepartmentMember = departmentMembers.find((member) => String(member.id) === selectedDepartmentMember) ?? null;

    return (
        <>
            <Head title="Activity Dashboard" />
            <div className="w-full font-sans text-slate-900 pb-12">
                <main className="w-full px-6 py-6 lg:px-8">

                    <DashboardFilterBar
                        hasExecutive={hasExecutive}
                        hasDepartmentStats={!!departmentStats}
                        viewMode={viewMode}
                        periodQuickFilters={periodQuickFilters}
                        distributionPeriod={distributionPeriod}
                        deptDistributionPeriod={deptDistributionPeriod}
                        selectedDepartmentMember={selectedDepartmentMember}
                        selectedSubDepartment={selectedSubDepartment}
                        departmentMembers={departmentMembers}
                        subDepartments={subDepartments}
                        onViewModeChange={handleViewModeChange}
                        onDistributionPeriodChange={handleDistributionPeriodChange}
                        onDeptDistributionPeriodChange={handleDeptDistributionPeriodChange}
                        onDepartmentMemberChange={handleDepartmentMemberChange}
                        onSubDepartmentChange={handleSubDepartmentChange}
                    />

                    {/* Dashboard Content */}
                    <AnimatePresence mode="wait" initial={false}>
                        <motion.div
                            key={viewMode}
                            initial={{ opacity: 0, y: 10 }}
                            animate={{ opacity: 1, y: 0 }}
                            exit={{ opacity: 0, y: -10 }}
                            transition={smoothTransition}
                            className="space-y-6"
                        >
                        {viewMode === 'executive' && executiveStats && (
                            <ExecutiveView
                                executiveStats={executiveStats}
                                onNavigateToAdminDashboard={navigateToAdminDashboard}
                            />
                        )}

                        {/* ═══════ PERSONAL / DEPARTMENT VIEW ═══════ */}
                        {viewMode !== 'executive' && (
                            <>
                            <DashboardMetricCards
                                viewMode={viewMode}
                                totalHours={totalHours}
                                totalMinutes={totalMinutes}
                                activeProjects={activeProjects}
                                completionRate={completionRate}
                                activeStats={activeStats}
                            />

                            {/* Detailed Sections Grid */}
                            <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                                <TeamActivitySection
                                    viewMode={viewMode}
                                    inProgressCount={inProgressTasks.length}
                                    rows={taskActivityRows}
                                    pagedRows={pagedTasks}
                                    taskPage={taskPage}
                                    totalTaskPages={totalTaskPages}
                                    tasksPerPage={TASKS_PER_PAGE}
                                    focusedMemberName={focusedDepartmentMember?.name ?? null}
                                    onPageChange={setTaskPage}
                                />

                                <FocusBreakdownPanel
                                    title={viewMode === 'department' ? 'Department Focus' : 'My Focus'}
                                    distribution={activeVisuals?.distribution || []}
                                    focusBreakdown={activeFocusBreakdown}
                                    insight={focusInsight}
                                    isLoading={isFocusLoading}
                                />
                            </div>
                            </>
                        )}
                        </motion.div>
                    </AnimatePresence>
                </main>
            </div>
        </>
    );
}
