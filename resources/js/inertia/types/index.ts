// User types
export interface User {
    id: number;
    name: string;
    email: string;
    role: string;
    avatar_url?: string;
    primary_department_id?: number;
}

// Business Unit types
export interface BusinessUnit {
    id: number;
    code: string;
    name: string;
}

// Activity Module types
export interface ActivityType {
    id: number;
    name: string;
    code: string;
    color: string;
    sub_activities?: SubActivity[];
}

export interface SubActivity {
    id: number;
    name: string;
    activity_type_id: number;
}

export interface Department {
    id: number;
    name: string;
    code: string;
}

export interface TaskParticipant {
    id: number;
    user_id: number;
    employee_task_id: number;
    user: User;
}

export interface TaskAttachment {
    id: number;
    filename: string;
    filepath: string;
    filesize: number;
    mime_type: string;
    original_name?: string;
    url?: string;
    created_at: string;
}

export type TaskStatus = 'planned' | 'in_progress' | 'completed' | 'cancelled';
export type TaskPriority = 'low' | 'medium' | 'high';

export interface TaskParticipantUser {
    id: number;
    user_id: number;
    employee_task_id: number;
    name: string;
    user?: User;
}

export interface Task {
    id: number;
    task_title: string;
    task_description: string | null;
    status: TaskStatus;
    priority: TaskPriority;
    due_date: string;
    start_date?: string;
    completed_at?: string;
    business_unit_id: number;
    department_id: number;
    activity_type_id: number;
    sub_activity_id?: number;
    created_by: number;
    activity_type: ActivityType;
    sub_activity?: SubActivity;
    creator: User;
    participants: TaskParticipantUser[];
    department: Department;
    attachments?: TaskAttachment[];
    created_at: string;
    updated_at: string;
}

export interface TaskStats {
    total: number;
    planned: number;
    in_progress: number;
    completed: number;
    overdue: number;
}

export interface TaskFilters {
    search: string;
    activity_type_id: string;
    status: string;
    date_from: string;
    date_to: string;
}

// Pagination types
export interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

export interface PaginationMeta {
    current_page: number;
    from: number | null;
    last_page: number;
    links: PaginationLink[];
    path: string;
    per_page: number;
    to: number | null;
    total: number;
}

export interface PaginatedData<T> {
    data: T[];
    links: {
        first: string;
        last: string;
        prev: string | null;
        next: string | null;
    };
    meta: PaginationMeta;
}

// Flash messages
export interface FlashMessages {
    success?: string;
    error?: string;
    warning?: string;
    info?: string;
}

// Shared page props (from Inertia middleware)
export interface PageProps {
    auth: {
        user: User | null;
    };
    currentBusinessUnit: BusinessUnit | null;
    flash: FlashMessages;
    appName: string;
    [key: string]: unknown; // Index signature for Inertia compatibility
}

// Activity-specific by-type data
export interface ActivityByType {
    name: string;
    count: number;
    color: string;
}

// Stats for analytics
export interface ActivityStat {
    label: string;
    value: number | string;
    icon?: string;
    color?: string;
}
