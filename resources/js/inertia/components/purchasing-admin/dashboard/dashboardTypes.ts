export interface DashboardStats {
    pending: number;
    in_progress: number;
    done: number;
}

export interface DashboardMetricsData {
    total_tasks_completed: number;
    avg_followup_time: number;
    avg_completion_time: number;
    total_savings: number;
    avg_savings_percentage: number;
}

export interface RecentAdminTask {
    id: number;
    taskable: {
        pr_number?: string;
        st_number?: string;
    };
    department: {
        name: string;
    };
    assigned_admin: {
        name: string;
    } | null;
    status: string;
    entered_at: string;
}

export interface DepartmentBreakdownItem {
    department: string;
    count: number;
    percentage: number;
}

export const formatDashboardCurrency = (amount: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(amount);
};

export const formatDashboardTime = (minutes: number) => {
    if (minutes === 0) return '0m';
    if (minutes > 0 && minutes < 1) return '1m';
    if (minutes < 60) return `${Math.round(minutes)}m`;

    const hours = Math.floor(minutes / 60);
    const mins = Math.round(minutes % 60);

    return mins > 0 ? `${hours}h ${mins}m` : `${hours}h`;
};
