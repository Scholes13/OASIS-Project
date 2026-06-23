// Purchasing Module Types

// Base types (duplicated to avoid circular imports)
interface User {
    id: number;
    name: string;
    email: string;
    role: string;
    avatar_url?: string;
    primary_department_id?: number;
}

interface Department {
    id: number;
    name: string;
    code: string;
    business_unit_id?: number;
}

interface BusinessUnit {
    id: number;
    code: string;
    name: string;
    logo: string | null;
}

interface PaginationMeta {
    current_page: number;
    from: number | null;
    last_page: number;
    links: Array<{ url: string | null; label: string; active: boolean }>;
    path: string;
    per_page: number;
    to: number | null;
    total: number;
}

interface PaginatedData<T> {
    data: T[];
    links: {
        first: string;
        last: string;
        prev: string | null;
        next: string | null;
    };
    meta: PaginationMeta;
}

// Export base types for use in other files
export type { User, Department, BusinessUnit, PaginatedData };

// Purchase Request Status
export type PurchaseRequestStatus =
    | 'draft'
    | 'submitted'
    | 'in_approval'
    | 'approved'
    | 'rejected'
    | 'voided';

// PR Category
export interface PRCategory {
    id: number;
    name: string;
    code: string;
    description?: string;
}

// PR Item
export interface PRItem {
    id: number;
    purchase_request_id: number;
    category_id: number;
    item_name: string;
    brand_name?: string;
    item_description?: string;
    supplier_name?: string;
    specification: string | null;
    quantity: number;
    unit: string;
    unit_price: number;
    estimated_price: number;
    total_price: number;
    currency?: string;
    expense_department_id?: number;
    image_path: string | null;
    notes: string | null;
    item_order: number;
    category: PRCategory;
    expense_department?: Department;
}

// PR Approval
export interface PRApproval {
    id: number;
    purchase_request_id: number;
    approver_id: number;
    step_order: number;
    status: 'pending' | 'approved' | 'rejected';
    notes: string | null;
    assigned_at: string;
    responded_at: string | null;
    due_date: string | null;
    approver: User;
}

// Purchase Request
export interface PurchaseRequest {
    id: number;
    pr_number: string;
    business_unit_id: number;
    department_id: number;
    user_id: number;
    category_id?: number;
    used_for: string;
    date_of_request: string;
    expected_date: string | null;
    designated_date: string | null;
    status: PurchaseRequestStatus;
    submitted_at: string | null;
    approved_at: string | null;
    rejected_at: string | null;
    voided_at: string | null;
    offline_approved_at: string | null;
    offline_approved_by: number | null;
    offline_approval_notes: string | null;
    offline_approval_document_path: string | null;
    offline_approval_document_name: string | null;
    approval_workflow: any[] | null;
    is_sequential_approval: boolean;
    total_amount: number;
    currency: string;
    supporting_document_path: string | null;
    supporting_document_name: string | null;
    approval_notes: string | null;
    created_at: string;
    updated_at: string;

    // Relationships
    user: User;
    department: Department;
    business_unit: BusinessUnit;
    category?: PRCategory;
    items?: PRItem[];
    approvals?: PRApproval[];

    // Aggregates from withCount
    items_count?: number;

    // Computed properties
    approval_progress?: {
        approved: number;
        total: number;
    };
}

// PR Index Page Props
export interface PRIndexProps {
    purchaseRequests: PaginatedData<PurchaseRequest>;
    filters: {
        status?: string;
        search?: string;
        date_from?: string;
        date_to?: string;
    };
    statuses: Array<{ value: string; label: string }>;
}

// PR Item Form Data
export interface PRItemFormData {
    id?: number;
    item_name: string;
    brand_name?: string;
    item_description?: string;
    supplier_name?: string;
    quantity: number;
    unit: string;
    unit_price: number;
    currency: string;
    expense_department_id?: number;
    image_path?: string;
    image_file?: File;
}

// PR Form Data
export interface PRFormData {
    business_unit_id: string;
    department_id: string;
    category_id: string;
    used_for: string;
    request_date: string;
    expected_date?: string;
    currency: string;
    items: PRItemFormData[];
    supporting_document?: File;
    approval_notes?: string;
    approval_workflow?: CustomApprovalStep[];
}

// Approver for selection
export interface Approver {
    id: number;
    name: string;
    email: string;
    position?: string;
    department?: Department;
}

// Custom Approval Step
export interface CustomApprovalStep {
    approver_id: string;
    task_type: 'approval' | 'paraf';
}

export type ApprovalEntry = {
    user_id?: number;
    approver_id?: string;
    name?: string;
    level?: string;
    task_type?: 'approval' | 'paraf';
};

export type AvailableApprover = {
    id: number;
    name: string;
    position?: string;
    level?: string;
};

// PR Create Page Props
export interface PRCreateProps {
    categories: PRCategory[];
    departments: Department[];
    businessUnits: BusinessUnit[];
    availableApprovers: Approver[];
    errors?: Record<string, string>;
}

// PR Show Page Props
export interface PRShowProps {
    purchaseRequest: PurchaseRequest & {
        can?: {
            edit: boolean;
            delete: boolean;
            void: boolean;
            resubmit: boolean;
            resendApprovalEmail: boolean;
            approve: boolean;
            reject: boolean;
            downloadPdf: boolean;
            markOfflineApproved: boolean;
            supportingDocument?: boolean;
        };
    };
    can?: {
        edit: boolean;
        delete: boolean;
        void: boolean;
        resubmit: boolean;
        resendApprovalEmail: boolean;
        approve: boolean;
        reject: boolean;
        downloadPdf: boolean;
        markOfflineApproved: boolean;
        supportingDocument?: boolean;
    };
}

// Approval Item Interface
export interface ApprovalItem {
    id: number;
    purchase_request: PurchaseRequest;
    step_order: number;
    approval_type: 'approval' | 'paraf';
    status: 'pending' | 'approved' | 'rejected';
    waiting_since: string;
    can: {
        approve: boolean;
        reject: boolean;
    };
}

// Approvals Page Props
export interface ApprovalsPageProps {
    auth: {
        user: User | null;
    };
    pendingApprovals: PaginatedData<ApprovalItem>;
    recentApprovals: ApprovalItem[];
    stats: {
        pending: number;
        approved: number;
        rejected: number;
        total: number;
    };
    can: {
        processApprovals: boolean;
    };
    [key: string]: unknown;
}

// ==================== Stock Request Types ====================

// Stock Request Status
export type StockRequestStatus =
    | 'draft'
    | 'submitted'
    | 'in_approval'
    | 'approved'
    | 'ga_review'
    | 'ga_rejected'
    | 'ready_for_purchasing'
    | 'rejected'
    | 'voided';

// Stock Request Item
export interface StockItem {
    id: number;
    item_name: string;
    item_description: string | null;
    specifications?: string | null;
    quantity: number;
    unit: string;
    image_path: string | null;
    ga_review_result?: 'pending_review' | 'warehouse_stock' | 'need_procurement';
    ga_review_note?: string | null;
    warehouse_available_qty?: number | null;
}

// Stock Request Approval
export interface StockApproval {
    id: number;
    approver_id: number;
    step_order: number;
    status: 'pending' | 'approved' | 'rejected';
    notes: string | null;
    responded_at: string | null;
    approver: User;
}

// Stock Request
export interface StockRequest {
    id: number;
    st_number: string;
    business_unit_id: number;
    department_id: number;
    user_id: number;
    purpose: string;
    date_of_request: string;
    expected_date: string | null;
    status: StockRequestStatus;
    submitted_at: string | null;
    approved_at: string | null;
    rejected_at: string | null;
    ga_review_started_at?: string | null;
    ga_reviewed_at?: string | null;
    ga_reviewed_by?: number | null;
    ga_review_notes?: string | null;
    ga_rejected_reason?: string | null;
    voided_at: string | null;
    offline_approved_at: string | null;
    offline_approval_document_path: string | null;
    offline_approval_document_name: string | null;
    created_at: string;
    updated_at: string;
    user: User;
    department: Department;
    business_unit: BusinessUnit;
    items?: StockItem[];
    approvals?: StockApproval[];
    approval_progress?: { approved: number; total: number };
}

// ST Permissions (parity-grade with PR)
export interface STPermissions {
    edit: boolean;
    delete: boolean;
    void: boolean;
    resubmit: boolean;
    resendApprovalEmail?: boolean;
    approve?: boolean;
    reject?: boolean;
    downloadPdf: boolean;
    markOfflineApproved?: boolean;
    offlineApprovalDocument?: boolean;
    gaReviewApprove?: boolean;
    gaReviewReject?: boolean;
}

// Approval Context for ST
export interface STApprovalContext {
    approvalId: number;
    canApprove: boolean;
    approvalStatus: 'pending' | 'approved' | 'rejected' | 'skipped';
}

// ST Show Page Props
export interface STShowProps {
    stockRequest: StockRequest & {
        can?: STPermissions;
    };
    can?: STPermissions;
    approvalContext?: STApprovalContext;
}
