// Types for Purchasing Admin Tasks

export interface AdminTask {
    id: number;
    taskable_type: string;
    taskable_id: number;
    business_unit_id: number;
    department_id: number;
    assigned_admin_id: number | null;
    status: 'pending_followup' | 'in_progress' | 'done';
    estimated_total_price: number;
    realized_total_price: number | null;
    savings_amount: number | null;
    savings_percentage: number | null;
    followup_time_minutes: number | null;
    completion_time_minutes: number | null;
    entered_at: string;
    started_at: string | null;
    completed_at: string | null;
    notes: string | null;

    // Relations
    taskable?: {
        id: number;
        pr_number?: string;
        st_number?: string;
        total_amount?: number;
        status?: string;
        used_for?: string;
    };
    assigned_admin?: {
        id: number;
        name: string;
    };
    department?: {
        id: number;
        name: string;
    };
    business_unit?: {
        id: number;
        name: string;
        code: string;
    };
}

export interface TaskFilters {
    status: string;
    type: string;
    date: string;
    search: string;
}

export interface TaskCounts {
    pending: number;
    in_progress: number;
    completed: number;
}

export type ViewMode = 'list' | 'board' | 'calendar' | 'timeline';
