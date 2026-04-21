import type { PaginatedData } from './index';

export interface NotificationListItem {
    id: string;
    type: string;
    category: string;
    event: string;
    title: string;
    message: string;
    action_url: string | null;
    priority: string;
    read_at: string | null;
    created_at: string | null;
    occurred_at: string | null;
}

export interface NotificationsSharedProps {
    unread_count: number;
}

export interface NotificationsPageProps {
    notifications: PaginatedData<NotificationListItem>;
    filters: {
        active: string;
    };
}

export interface NotificationsPaginationShape {
    current_page?: number;
    last_page?: number;
    prev_page_url?: string | null;
    next_page_url?: string | null;
}
