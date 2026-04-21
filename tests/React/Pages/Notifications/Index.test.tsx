import { describe, expect, it } from 'vitest';
import { render, screen } from '@testing-library/react';
import NotificationsIndex from '@/Pages/Notifications/Index';
import type { NotificationsPageProps } from '@/types/notifications';

describe('Notifications archive page', () => {
    it('renders filter tabs and notification rows', () => {
        const props: NotificationsPageProps = {
            notifications: {
                data: [
                    {
                        id: 'notif-1',
                        type: 'database',
                        category: 'activity',
                        event: 'task_tagged',
                        title: 'You were tagged by Amanda in Activity Budget Review',
                        message: 'Amanda tagged you in Budget Review.',
                        action_url: '/activity/task/12',
                        priority: 'normal',
                        read_at: null,
                        created_at: '2026-04-20T20:00:00.000000Z',
                        occurred_at: '2026-04-20T20:00:00.000000Z',
                    },
                ],
                links: {
                    first: '/notifications?page=1',
                    last: '/notifications?page=1',
                    prev: null,
                    next: null,
                },
                meta: {
                    current_page: 1,
                    from: 1,
                    last_page: 1,
                    links: [],
                    path: '/notifications',
                    per_page: 15,
                    to: 1,
                    total: 1,
                },
            },
            filters: {
                active: 'all',
            },
        };

        render(<NotificationsIndex {...props} />);

        expect(screen.getByText('All')).toBeInTheDocument();
        expect(screen.getByText('Unread')).toBeInTheDocument();
        expect(screen.getByText('Activity')).toBeInTheDocument();
        expect(screen.getByText('You were tagged by Amanda in Activity Budget Review')).toBeInTheDocument();
        expect(screen.getByText('Mark all as read')).toBeInTheDocument();
        expect(screen.getByRole('link', { name: 'Unread' })).toHaveAttribute('href', expect.stringContaining('filter=unread'));
    });

    it('renders pagination controls when there are multiple pages', () => {
        const props: NotificationsPageProps = {
            notifications: {
                data: [
                    {
                        id: 'notif-1',
                        type: 'database',
                        category: 'activity',
                        event: 'task_tagged',
                        title: 'Paged notification',
                        message: 'Paged message.',
                        action_url: '/activity/task/12',
                        priority: 'normal',
                        read_at: null,
                        created_at: '2026-04-20T20:00:00.000000Z',
                        occurred_at: '2026-04-20T20:00:00.000000Z',
                    },
                ],
                links: {
                    first: '/notifications?page=1',
                    last: '/notifications?page=2',
                    prev: null,
                    next: '/notifications?page=2',
                },
                meta: {
                    current_page: 1,
                    from: 1,
                    last_page: 2,
                    links: [],
                    path: '/notifications',
                    per_page: 15,
                    to: 15,
                    total: 16,
                },
            },
            filters: {
                active: 'all',
            },
        };

        render(<NotificationsIndex {...props} />);

        expect(screen.getByText('Page 1 of 2')).toBeInTheDocument();
        expect(screen.getByRole('link', { name: 'Next' })).toHaveAttribute('href', '/notifications?page=2');
    });

    it('renders empty state when the archive has no notifications', () => {
        const props: NotificationsPageProps = {
            notifications: {
                data: [],
                links: {
                    first: '/notifications?page=1',
                    last: '/notifications?page=1',
                    prev: null,
                    next: null,
                },
                meta: {
                    current_page: 1,
                    from: null,
                    last_page: 1,
                    links: [],
                    path: '/notifications',
                    per_page: 15,
                    to: null,
                    total: 0,
                },
            },
            filters: {
                active: 'all',
            },
        };

        render(<NotificationsIndex {...props} />);

        expect(screen.getByText('No notifications yet.')).toBeInTheDocument();
    });
});
