import { Head, Link } from '@inertiajs/react';
import { BellRing } from 'lucide-react';
import { Card } from '@/components/ui/Card';
import { cn } from '@/lib/utils';
import type { NotificationsPageProps, NotificationsPaginationShape } from '@/types/notifications';

const FILTERS = ['All', 'Unread', 'Activity', 'Purchasing', 'Backdate', 'System'] as const;

export default function NotificationsIndex({ notifications, filters }: NotificationsPageProps) {
    const pagination = notifications as NotificationsPageProps['notifications'] & NotificationsPaginationShape;
    const currentPage = notifications.meta?.current_page ?? pagination.current_page ?? 1;
    const lastPage = notifications.meta?.last_page ?? pagination.last_page ?? 1;
    const previousUrl = notifications.links?.prev ?? pagination.prev_page_url ?? null;
    const nextUrl = notifications.links?.next ?? pagination.next_page_url ?? null;

    return (
        <>
            <Head title="Notifications" />

            <div className="mx-auto flex w-full max-w-5xl flex-col gap-6 px-4 py-6 sm:px-6 lg:px-8">
                <div className="flex flex-col gap-3 rounded-3xl border border-gray-200 bg-white px-6 py-6 shadow-sm">
                    <div className="flex items-center gap-3">
                        <div className="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-blue-50 text-blue-600">
                            <BellRing className="h-5 w-5" />
                        </div>
                        <div>
                            <h1 className="text-xl font-semibold text-gray-900">Notifications</h1>
                            <p className="text-sm text-gray-500">A clean view of the updates that need your attention.</p>
                        </div>
                    </div>

                    <div className="flex flex-wrap gap-2">
                        {FILTERS.map((filter) => {
                            const value = filter.toLowerCase();

                            return (
                                <Link
                                    key={filter}
                                    href={route('notifications.index', { filter: value })}
                                    className={cn(
                                        'inline-flex rounded-full px-3 py-1.5 text-xs font-medium',
                                        filters.active === value
                                            ? 'bg-blue-600 text-white'
                                            : 'bg-gray-100 text-gray-600'
                                    )}
                                >
                                    {filter}
                                </Link>
                            );
                        })}
                    </div>

                    <div>
                        <Link
                            as="button"
                            method="post"
                            href={route('notifications.mark-all-read', { filter: filters.active })}
                            className="text-sm font-medium text-blue-600 hover:text-blue-700"
                        >
                            Mark all as read
                        </Link>
                    </div>
                </div>

                {notifications.data.length === 0 ? (
                    <Card className="rounded-3xl border-dashed px-6 py-12 text-center text-sm text-gray-500">
                        No notifications yet.
                    </Card>
                ) : (
                    <div className="space-y-6">
                        <div className="space-y-3">
                            {notifications.data.map((notification) => (
                                <Link
                                    key={notification.id}
                                    href={route('notifications.open', { notification: notification.id })}
                                    className="block"
                                >
                                    <Card
                                        className={cn(
                                            'rounded-3xl px-5 py-4 transition hover:border-gray-300 hover:shadow-md',
                                            notification.read_at ? 'bg-white' : 'border-blue-200 bg-blue-50/40'
                                        )}
                                    >
                                        <div className="flex items-start justify-between gap-4">
                                            <div className="min-w-0 flex-1">
                                                <div className="text-sm font-semibold text-gray-900">{notification.title}</div>
                                                <div className="mt-1 text-sm leading-6 text-gray-500">{notification.message}</div>
                                            </div>
                                            <span className="rounded-full bg-gray-100 px-2.5 py-1 text-[11px] font-medium uppercase tracking-wide text-gray-500">
                                                {notification.category}
                                            </span>
                                        </div>
                                    </Card>
                                </Link>
                            ))}
                        </div>

                        {lastPage > 1 ? (
                            <div className="flex items-center justify-between rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-600 shadow-sm">
                                <span>
                                    Page {currentPage} of {lastPage}
                                </span>
                                <div className="flex items-center gap-2">
                                    {previousUrl ? (
                                        <Link
                                            href={previousUrl}
                                            className="inline-flex rounded-xl border border-gray-200 px-3 py-1.5 font-medium text-gray-700 transition hover:border-gray-300 hover:bg-gray-50"
                                        >
                                            Previous
                                        </Link>
                                    ) : null}
                                    {nextUrl ? (
                                        <Link
                                            href={nextUrl}
                                            className="inline-flex rounded-xl border border-gray-200 px-3 py-1.5 font-medium text-gray-700 transition hover:border-gray-300 hover:bg-gray-50"
                                        >
                                            Next
                                        </Link>
                                    ) : null}
                                </div>
                            </div>
                        ) : null}
                    </div>
                )}
            </div>
        </>
    );
}
