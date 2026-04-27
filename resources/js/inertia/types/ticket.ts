// IT Support / Ticket System Types

import type { User, Department } from './index';

// Ticket Category
export interface TicketCategory {
    id: number;
    name: string;
    description: string | null;
    color: string;
    is_active: boolean;
    tickets_count?: number;
}

// Main Ticket interface
export interface Ticket {
    id: number;
    ticket_number: string;
    title: string;
    description: string;
    requester: User | null;
    department: Department | null;
    status: TicketStatus;
    priority: TicketPriority;
    category: TicketCategory | null;
    assigned_user: User | null;
    creator: User | null;
    comments: TicketComment[];
    attachments: TicketAttachment[];
    knowledge_articles?: KnowledgeArticle[];
    follow_up_at: string | null;
    resolved_at: string | null;
    processing_time: string | null;
    sla_deadline: string | null;
    is_sla_breach: boolean;
    created_at: string;
    updated_at: string;
}

// Ticket enums
export type TicketStatus = 'waiting' | 'in_progress' | 'done' | 'cancelled';
export type TicketPriority = 'low' | 'medium' | 'high' | 'critical';

// Ticket Comment
export interface TicketComment {
    id: number;
    content: string;
    is_private: boolean;
    user: User | null;
    created_at: string;
    updated_at: string;
    can_edit?: boolean;
    can_delete?: boolean;
}

// Ticket Attachment
export interface TicketAttachment {
    id: number;
    original_filename: string;
    file_type: string | null;
    file_size: number;
    download_url?: string;
    created_at: string;
}

// Ticket SLA Settings
export interface TicketSlaSettings {
    id: number;
    priority: TicketPriority;
    resolution_hours: number;
}

// Knowledge Base Types
export interface KnowledgeCategory {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    icon: string | null;
    order: number;
    parent: KnowledgeCategory | null;
    children: KnowledgeCategory[];
    articles_count?: number;
}

export interface KnowledgeArticle {
    id: number;
    title: string;
    slug: string;
    content: string;
    category: KnowledgeCategory | null;
    is_published: boolean;
    views_count: number;
    author: User | null;
    published_at: string | null;
    meta_description: string | null;
    tags: string[];
    created_at: string;
    updated_at: string;
}

// Ticket Dashboard Metrics
export interface TicketDashboardMetrics {
    total: number;
    by_status: Record<TicketStatus, number>;
    by_priority: Record<TicketPriority, number>;
    by_category: { name: string; count: number; color: string }[];
    by_staff: { name: string; count: number }[];
    avg_resolution_hours: number;
    sla_breach_count: number;
    recent_tickets: Ticket[];
}

// Ticket Form Data
export interface TicketFormData {
    title: string;
    description: string;
    priority: TicketPriority;
    category_id: number | null;
    department_id: number | null;
    assigned_user_id: number | null;
    attachments?: File[];
}

// Ticket Comment Form Data
export interface TicketCommentFormData {
    content: string;
    is_private: boolean;
}

// IT Support Admin Assignment Types (for Admin page)
export interface ITSupportAssignment {
    id: number;
    is_it_support_admin: boolean;
    is_it_support_report_access: boolean;
    is_primary: boolean;
    user: { id: number; name: string; email: string } | null;
    business_unit: { id: number; name: string; code: string } | null;
    department: { id: number; name: string } | null;
    position: { id: number; name: string; access_level: string } | null;
}

// Pagination support for tickets
export interface TicketFilters {
    search: string;
    status: TicketStatus | '';
    priority: TicketPriority | '';
    category_id: number | null;
    assigned_user_id: number | null;
    date_from: string;
    date_to: string;
}