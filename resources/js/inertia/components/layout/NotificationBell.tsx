import { useMemo, useState } from 'react';
import { Bell, ChevronRight } from 'lucide-react';
import { Link, usePage } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import type { NotificationListItem, PageProps } from '@/types';

interface NotificationBellProps {
    recentNotifications: NotificationListItem[];
}

export default function NotificationBell({ recentNotifications }: NotificationBellProps) {
    const { notifications } = usePage<PageProps>().props;
    const [open, setOpen] = useState(false);
    const [items, setItems] = useState<NotificationListItem[]>(recentNotifications);
    const unreadCount = notifications?.unread_count ?? 0;

    const loadRecentNotifications = async (): Promise<void> => {
        try {
            const response = await fetch(route('notifications.recent'), {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });

            if (! response.ok) {
                setItems([]);

                return;
            }

            const payload = (await response.json()) as { data?: NotificationListItem[] };

            setItems(Array.isArray(payload.data) ? payload.data : []);
        } catch {
            setItems([]);
        }
    };

    const unreadLabel = useMemo(() => {
        if (unreadCount > 99) {
            return '99+';
        }

        return String(unreadCount);
    }, [unreadCount]);

    return (
        <div className="relative">
            <button
                type="button"
                aria-label="Open notifications"
                onClick={() => {
                    setOpen((value) => {
                        const next = !value;

                        if (next) {
                            void loadRecentNotifications();
                        }

                        return next;
                    });
                }}
                className="relative inline-flex h-10 w-10 items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-600 transition hover:border-gray-300 hover:text-gray-900"
            >
                <Bell className="h-4 w-4" />
                {unreadCount > 0 ? (
                    <span className="absolute -right-1 -top-1 inline-flex min-w-[1.25rem] items-center justify-center rounded-full bg-blue-600 px-1.5 py-0.5 text-[10px] font-semibold text-white">
                        {unreadLabel}
                    </span>
                ) : null}
            </button>

            {open ? (
                <div className="absolute right-0 z-30 mt-3 w-[22rem] overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xl">
                    <div className="border-b border-gray-100 px-4 py-3">
                        <div className="text-sm font-semibold text-gray-900">Notifications</div>
                        <div className="text-xs text-gray-500">High-signal updates across your workspace.</div>
                    </div>

                    <div className="max-h-[26rem] overflow-y-auto">
                        {items.length === 0 ? (
                            <div className="px-4 py-8 text-center text-sm text-gray-500">
                                No notifications yet.
                            </div>
                        ) : (
                            items.map((notification) => (
                                <Link
                                    key={notification.id}
                                    href={route('notifications.open', { notification: notification.id })}
                                    className={cn(
                                        'block border-b border-gray-100 px-4 py-3 transition hover:bg-gray-50',
                                        notification.read_at ? 'bg-white' : 'bg-blue-50/50'
                                    )}
                                >
                                    <div className="flex items-start justify-between gap-3">
                                        <div className="min-w-0 flex-1">
                                            <div className="truncate text-sm font-medium text-gray-900">{notification.title}</div>
                                            <div className="mt-1 line-clamp-2 text-xs leading-5 text-gray-500">{notification.message}</div>
                                        </div>
                                        <ChevronRight className="mt-0.5 h-4 w-4 flex-shrink-0 text-gray-400" />
                                    </div>
                                </Link>
                            ))
                        )}
                    </div>

                    <div className="border-t border-gray-100 px-4 py-3">
                        <Link href={route('notifications.index')} className="text-sm font-medium text-blue-600 hover:text-blue-700">
                            View all notifications
                        </Link>
                    </div>
                </div>
            ) : null}
        </div>
    );
}
