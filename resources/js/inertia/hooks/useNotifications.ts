import { useCallback, useEffect, useRef, useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import echo from '@echo';
import type { NotificationListItem, PageProps } from '@/types';

interface UseNotificationsReturn {
    unreadCount: number;
    recentItems: NotificationListItem[];
    refreshNotifications: () => Promise<void>;
    hasNewNotification: boolean;
    clearNewFlag: () => void;
}

export function useNotifications(initialItems: NotificationListItem[] = []): UseNotificationsReturn {
    const { auth, notifications } = usePage<PageProps>().props;
    const userId = auth.user?.id;

    const [unreadCount, setUnreadCount] = useState(notifications?.unread_count ?? 0);
    const [recentItems, setRecentItems] = useState<NotificationListItem[]>(initialItems);
    const [hasNewNotification, setHasNewNotification] = useState(false);
    const channelRef = useRef<ReturnType<typeof echo.private> | null>(null);

    // Sync unread count from Inertia shared props on page navigation
    useEffect(() => {
        setUnreadCount(notifications?.unread_count ?? 0);
    }, [notifications?.unread_count]);

    // Fetch recent notifications from server
    const refreshNotifications = useCallback(async (): Promise<void> => {
        try {
            const response = await fetch(route('notifications.recent'), {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                return;
            }

            const payload = (await response.json()) as { data?: NotificationListItem[] };
            setRecentItems(Array.isArray(payload.data) ? payload.data : []);
        } catch {
            // Silently fail - notifications are non-critical
        }
    }, []);

    // Subscribe to private broadcast channel
    useEffect(() => {
        if (!userId) {
            return;
        }

        const channel = echo.private(`App.Models.Core.User.${userId}`);
        channelRef.current = channel;

        channel.notification((notification: Record<string, unknown>) => {
            // Build a NotificationListItem from the broadcast payload
            const newItem: NotificationListItem = {
                id: (notification.id as string) ?? crypto.randomUUID(),
                type: (notification.type as string) ?? 'notification',
                category: (notification.category as string) ?? 'system',
                event: (notification.event as string) ?? 'notification',
                title: (notification.title as string) ?? 'New notification',
                message: (notification.message as string) ?? '',
                action_url: (notification.action_url as string | null) ?? null,
                priority: (notification.priority as string) ?? 'normal',
                read_at: null,
                created_at: new Date().toISOString(),
                occurred_at: (notification.occurred_at as string) ?? new Date().toISOString(),
            };

            // Prepend to recent items (max 8)
            setRecentItems((prev) => [newItem, ...prev].slice(0, 8));

            // Increment unread count
            setUnreadCount((prev) => prev + 1);

            // Flag new notification for UI effects (e.g., bell animation)
            setHasNewNotification(true);
        });

        return () => {
            channel.stopListening('.Illuminate\\Notifications\\Events\\BroadcastNotificationCreated');
            echo.leave(`App.Models.Core.User.${userId}`);
            channelRef.current = null;
        };
    }, [userId]);

    const clearNewFlag = useCallback(() => {
        setHasNewNotification(false);
    }, []);

    return {
        unreadCount,
        recentItems,
        refreshNotifications,
        hasNewNotification,
        clearNewFlag,
    };
}
