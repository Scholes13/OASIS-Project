// Admin Panel Types

import { Department as BaseDepartment, BusinessUnit as BaseBusinessUnit, PaginatedData, PageProps } from './index';

// Re-export for convenience
export type Department = BaseDepartment;
export type BusinessUnit = BaseBusinessUnit;

// Base User interface for admin (extends base User with admin-specific fields)
export interface User {
    id: number;
    name: string;
    email: string;
    phone_number?: string;
    global_role: 'super_admin' | 'user';
    supervisor_id?: number;
    is_active: boolean;
    is_super_admin: boolean;
    email_verified_at?: string;
    created_at: string;
    updated_at: string;
    
    // Relationships
    supervisor?: User;
    subordinates?: User[];
    business_units?: BusinessUnitAssignment[];
    primary_business_unit?: BusinessUnit;
    primary_department?: Department;
    primary_position?: Position;
}

// Admin User type alias (must be after User interface)
export type AdminUser = User;

// Business Unit Assignment
export interface BusinessUnitAssignment {
    business_unit: BusinessUnit;
    department: Department;
    position: Position;
    is_primary: boolean;
}

// Pagination Data
export interface PaginationData {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
}

// Select Option
export interface SelectOption {
    value: string | number;
    label: string;
}

// Position
export interface Position {
    id: number;
    name: string;
    code: string;
    department_id: number;
    access_level: 'staff' | 'supervisor' | 'manager' | 'head';
    is_active: boolean;
    department?: Department;
}

// User Business Unit Assignment
export interface UserBusinessUnit {
    id: number;
    user_id: number;
    business_unit_id: number;
    department_id: number;
    position_id: number;
    is_primary: boolean;
    is_purchasing_admin: boolean;
    business_unit: BusinessUnit;
    department: Department;
    position: Position;
}

// Admin Dashboard Stats
export interface AdminDashboardStats {
    total_users: number;
    active_users: number;
    super_admins: number;
    total_business_units: number;
    active_business_units: number;
    total_departments: number;
    total_assignments: number;
    total_purchase_requests: number;
    pending_approvals: number;
    active_sequences: number;
}

// Business Unit with Stats
export interface BusinessUnitWithStats extends Omit<BusinessUnit, 'logo'> {
    parent_id?: number;
    manager_id?: number;
    description?: string;
    address?: string;
    phone?: string;
    email?: string;
    logo?: string | null;
    is_active: boolean;
    sort_order: number;
    created_at: string;
    updated_at: string;
    
    // Relationships
    parent?: BusinessUnit;
    children?: BusinessUnit[];
    manager?: User;
    
    // Stats
    departments_count?: number;
    users_count?: number;
    purchase_requests_count?: number;
}

// Department with Stats
export interface DepartmentWithStats extends Department {
    is_active: boolean;
    sort_order: number;
    head_id?: number;
    users_count?: number;
    created_at: string;
    updated_at: string;
    
    // Relationships
    business_unit?: BusinessUnit;
    head?: User;
    positions?: Position[];
    
    // Stats
    positions_count?: number;
    
    // Purchasing config
    is_purchasing_enabled?: boolean;
    purchasing_admin_id?: number;
    purchasing_admin?: User;
}

// Admin Dashboard Props
export interface AdminDashboardProps extends PageProps {
    stats: AdminDashboardStats;
    recentUsers: User[];
    businessUnitStats: BusinessUnitWithStats[];
    monthlyPRs: Record<string, number>;
}

// User Management - Index Props
export interface UserIndexProps extends PageProps {
    users: PaginatedData<User>;
    businessUnits: BusinessUnit[];
    departments: Department[];
    filters: {
        search?: string;
        business_unit?: number;
        department?: number;
        global_role?: string;
        is_active?: boolean;
    };
}

// User Management - Form Props
export interface UserFormProps extends PageProps {
    user?: User;
    businessUnits: BusinessUnitWithStats[];
    users: User[]; // For supervisor selection
    errors?: Record<string, string>;
}

// User Management - Show Props
export interface UserShowProps extends PageProps {
    user: User;
    can: {
        edit: boolean;
        delete: boolean;
        deactivate: boolean;
    };
}

// Business Unit Management - Index Props
export interface BusinessUnitIndexProps extends PageProps {
    businessUnits: PaginatedData<BusinessUnitWithStats>;
    filters: {
        search?: string;
        is_active?: boolean;
        parent_id?: number;
    };
    viewMode?: 'grid' | 'list';
}

// Business Unit Management - Form Props
export interface BusinessUnitFormProps extends PageProps {
    businessUnit?: BusinessUnitWithStats;
    businessUnits: BusinessUnit[]; // For parent selection
    users: User[]; // For manager selection
    errors?: Record<string, string>;
}

// Business Unit Management - Show Props
export interface BusinessUnitShowProps extends PageProps {
    businessUnit: BusinessUnitWithStats;
    departments: Department[];
    users: User[];
    stats: {
        total_departments: number;
        total_users: number;
        total_purchase_requests: number;
        active_sequences: number;
    };
    can: {
        edit: boolean;
        delete: boolean;
    };
}

// Department Management - Index Props
export interface DepartmentIndexProps extends PageProps {
    departments: PaginatedData<DepartmentWithStats>;
    businessUnits: BusinessUnit[];
    filters: {
        search?: string;
        business_unit_id?: number;
        is_active?: boolean;
    };
}

// Department Management - Form Props
export interface DepartmentFormProps extends PageProps {
    department?: DepartmentWithStats;
    businessUnits: BusinessUnit[];
    users: User[]; // For head selection
    errors?: Record<string, string>;
}

// Department Management - Show Props
export interface DepartmentShowProps extends PageProps {
    department: DepartmentWithStats;
    positions: Position[];
    users: User[];
    can: {
        edit: boolean;
        delete: boolean;
    };
}

// Position Management - Index Props
export interface PositionIndexProps extends PageProps {
    positions: PaginatedData<Position>;
    departments: Department[];
    filters: {
        search?: string;
        department_id?: number;
        access_level?: string;
        is_active?: boolean;
    };
}

// Position Management - Form Props
export interface PositionFormProps extends PageProps {
    position?: Position;
    departments: Department[];
    errors?: Record<string, string>;
}

// Form Data Types
export interface UserFormData {
    name: string;
    email: string;
    phone_number?: string;
    password?: string;
    password_confirmation?: string;
    global_role: 'super_admin' | 'user';
    supervisor_id?: number;
    is_active: boolean;
    business_units: Array<{
        business_unit_id: number;
        department_id: number;
        position_id: number;
    }>;
    primary_business_unit: number;
}

export interface BusinessUnitFormData {
    code: string;
    name: string;
    parent_id?: number;
    manager_id?: number;
    logo?: File;
    is_active: boolean;
    sort_order: number;
}

export interface DepartmentFormData {
    code: string;
    name: string;
    business_unit_id: number;
    head_id?: number;
    is_active: boolean;
    sort_order: number;
    is_purchasing_enabled: boolean;
    purchasing_admin_id?: number;
}

export interface PositionFormData {
    code: string;
    name: string;
    department_id: number;
    access_level: 'staff' | 'supervisor' | 'manager' | 'head';
    is_active: boolean;
}

// Filter Types
export interface UserFilters {
    search: string;
    business_unit: number | null;
    department: number | null;
    global_role: string | null;
    is_active: boolean | null;
}

export interface BusinessUnitFilters {
    search: string;
    is_active: boolean | null;
    parent_id: number | null;
}

export interface DepartmentFilters {
    search: string;
    business_unit_id: number | null;
    is_active: boolean | null;
}

export interface PositionFilters {
    search: string;
    department_id: number | null;
    access_level: string | null;
    is_active: boolean | null;
}

// PR Category
export interface PrCategory {
    id: number;
    name: string;
    code: string;
    description?: string;
    color: string;
    is_active: boolean;
    sort_order: number;
    usage_count?: number;
    created_at: string;
    updated_at: string;
}

// Activity Type
export interface ActivityType {
    id: number;
    name: string;
    color: string;
    code?: string;
    department_prefix?: string;
    sub_activities_count?: number;
    usage_count?: number;
    created_at: string;
    updated_at: string;
}

// Sub-Activity
export interface SubActivity {
    id: number;
    name: string;
    activity_type_id: number;
    activity_type?: ActivityType;
    usage_count?: number;
    created_at: string;
    updated_at: string;
}

// Notification Settings
export interface NotificationSettings {
    smtp_host: string;
    smtp_port: number;
    smtp_username: string;
    smtp_password: string;
    smtp_encryption: 'tls' | 'ssl' | null;
    from_address: string;
    from_name: string;
}

export interface EmailStatistics {
    total_sent: number;
    total_failed: number;
    last_sent_at: string | null;
}

// SLA Settings
export interface SlaSettings {
    id: number;
    business_unit_id: number;
    business_unit?: BusinessUnit;
    follow_up_hours: number;
    completion_hours: number;
    email_alerts_enabled: boolean;
}

export interface SlaStatistics {
    business_unit: BusinessUnit;
    compliance_rate: number;
    average_completion_time: number;
    overdue_count: number;
}

// Chart Data
export interface ChartDataPoint {
    month: string;
    count: number;
}

// Select Option
export interface SelectOption {
    value: string | number;
    label: string;
}

// Validation Error Types
export interface ValidationErrors {
    [key: string]: string | string[];
}

// API Response Types
export interface ApiResponse<T = any> {
    success: boolean;
    message?: string;
    data?: T;
    errors?: ValidationErrors;
}

// Department/Position Dynamic Loading
export interface DepartmentsByBusinessUnit {
    [businessUnitId: number]: Department[];
}

export interface PositionsByDepartment {
    [departmentId: number]: Position[];
}

// PR Category Management Props
export interface PrCategoryIndexProps extends PageProps {
    categories: PaginatedData<PrCategory>;
    filters: {
        search?: string;
    };
}

export interface PrCategoryFormData {
    name: string;
}

// Activity Type Management Props
export interface ActivityTypeIndexProps extends PageProps {
    activityTypes: PaginatedData<ActivityType>;
    filters: {
        search?: string;
    };
}

export interface ActivityTypeFormData {
    name: string;
    color: string;
}

// Sub-Activity Management Props
export interface SubActivityIndexProps extends PageProps {
    subActivities: PaginatedData<SubActivity>;
    activityTypes: ActivityType[];
    filters: {
        search?: string;
        activity_type_id?: number;
    };
}

export interface SubActivityFormData {
    name: string;
    activity_type_id: number;
}

// Notification Settings Props
export interface NotificationSettingsProps extends PageProps {
    settings: NotificationSettings;
    statistics: EmailStatistics;
}

export interface NotificationSettingsFormData {
    smtp_host: string;
    smtp_port: number;
    smtp_username: string;
    smtp_password?: string;
    smtp_encryption: 'tls' | 'ssl' | '';
    from_address: string;
    from_name: string;
}

// SLA Settings Props
export interface SlaSettingsProps extends PageProps {
    settings: SlaSettings[];
    statistics: SlaStatistics[];
}

export interface SlaSettingsFormData {
    settings: Array<{
        business_unit_id: number;
        follow_up_hours: number;
        completion_hours: number;
        email_alerts_enabled: boolean;
    }>;
}
