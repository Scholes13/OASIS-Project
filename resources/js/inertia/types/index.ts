// User types
export interface User {
    id: number;
    name: string;
    email: string;
    role: string;
    avatar_url?: string;
    primary_department_id?: number;
    is_purchasing_admin?: boolean;
    is_purchasing_readonly?: boolean;
}

// Business Unit types
export interface BusinessUnit {
    id: number;
    code: string;
    name: string;
    logo: string | null;
}

// Navigation Menu types
export interface MenuItem {
    name: string;        // Changed from 'label' to match NavigationService
    icon: string;
    href: string;
    routePattern?: string | string[];  // Pattern(s) to match for active state
    excludePattern?: string;           // Pattern to exclude from active matching
    badge?: number;      // Added for notification badges
    children?: MenuItem[];
}

export interface MenuSection {
    name: string;        // Changed from 'title' to match NavigationService
    items: MenuItem[];
}

export interface NavigationMenu {
    sections: MenuSection[];
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
    business_unit_id?: number;
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
    email?: string;
    user?: User;
}

export interface TaskComment {
    id: number;
    user: { id: number; name: string } | null;
    body: string;
    edited_at: string | null;
    created_at: string;
    can_edit: boolean;
    can_delete: boolean;
}

export interface Task {
    id: number;
    task_title: string;
    task_description: string | null;
    status: TaskStatus;
    priority: TaskPriority;
    due_date: string | null;
    task_date?: string;
    start_date?: string;
    started_at?: string;
    completed_at?: string;
    start_time?: string;
    end_time?: string;
    completed_date?: string;
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
    comments_data?: TaskComment[];
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
    member_user_id: string;
    scope?: 'my' | 'department';
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
export interface CashflowImportFlashError {
    row: number | null;
    column: string;
    message: string;
    value?: string | number | boolean | null;
}

export interface CashflowImportFlash {
    status: 'success' | 'failed';
    summary: string;
    file_name: string;
    total_rows: number;
    processed_rows: number;
    created_rows: number;
    updated_rows: number;
    failed_rows: number;
    truncated: boolean;
    errors: CashflowImportFlashError[];
}

export interface FlashMessages {
    success?: string;
    error?: string;
    warning?: string;
    info?: string;
    cashflow_import?: CashflowImportFlash;
    just_logged_in?: boolean;
    created_task_id?: number | string | null;
}

// Shared page props (from Inertia middleware)
export interface PageProps {
    auth: {
        user: User | null;
    };
    currentBusinessUnit: BusinessUnit | null;
    availableBusinessUnits: BusinessUnit[];
    navigation: NavigationMenu;
    flash: FlashMessages;
    notifications: {
        unread_count: number;
    };
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

// Re-export types from other modules
export * from './purchasing';
export * from './admin';
export * from './notifications';
export * from './ticket';
