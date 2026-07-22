import type { PageProps } from '@/types';

export type PeriodFilter = 'today' | 'week' | 'month' | 'year' | 'all';

export interface TaskBasic {
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
    participants?: Array<{
        id?: number;
        user_id?: number;
        name?: string;
        user?: { name?: string };
        primary_position?: { name?: string };
    }>;
}

export interface Stats {
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
    top_category: { name: string; count: number; percentage_of_report: number };
    top_subcategory: { name: string; count: number; percentage_of_report: number };
    items: FocusBreakdownItem[];
}

interface PersonalVisuals {
    roadmap: PaginatedData<TaskBasic>;
    upcoming: TaskBasic[];
    distribution: { name: string; color: string; value: number }[];
    focus_breakdown: FocusBreakdown;
}

interface DepartmentVisuals extends PersonalVisuals {
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

export interface ActivityDashboardProps extends PageProps {
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
