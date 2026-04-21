import { describe, expect, it, vi } from 'vitest';
import { act, fireEvent, render, screen, waitFor } from '@testing-library/react';
import NotificationBell from '@/components/layout/NotificationBell';
import type { NotificationListItem } from '@/types/notifications';

const { mockFetch } = vi.hoisted(() => ({
    mockFetch: vi.fn(),
}));

vi.mock('@inertiajs/react', () => ({
    Link: ({ children, href, ...props }: { children: React.ReactNode; href: string }) => <a href={href} {...props}>{children}</a>,
    usePage: () => ({
        props: {
            notifications: {
                unread_count: 3,
            },
        },
    }),
}));

Object.defineProperty(globalThis, 'fetch', {
    value: mockFetch,
    writable: true,
});

describe('NotificationBell', () => {
    it('renders unread count badge from shared props', () => {
        mockFetch.mockResolvedValue({
            ok: true,
            json: async () => ({ data: [] }),
        });

        render(<NotificationBell recentNotifications={[]} />);

        expect(screen.getByText('3')).toBeInTheDocument();
    });

    it('shows empty state when there are no recent notifications', async () => {
        mockFetch.mockResolvedValue({
            ok: true,
            json: async () => ({ data: [] }),
        });

        render(<NotificationBell recentNotifications={[]} />);

        fireEvent.click(screen.getByRole('button', { name: /open notifications/i }));

        await waitFor(() => {
            expect(screen.getByText('No notifications yet.')).toBeInTheDocument();
        });
    });

    it('renders recent notification title and message when opened', async () => {
        mockFetch.mockResolvedValue({
            ok: true,
            json: async () => ({
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
            }),
        });

        const notifications: NotificationListItem[] = [
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
        ];

        render(<NotificationBell recentNotifications={notifications} />);

        fireEvent.click(screen.getByRole('button', { name: /open notifications/i }));

        await waitFor(() => {
            expect(screen.getByText('You were tagged by Amanda in Activity Budget Review')).toBeInTheDocument();
            expect(screen.getByText('Amanda tagged you in Budget Review.')).toBeInTheDocument();
        });
    });

    it('requests recent notifications when the panel is opened', async () => {
        mockFetch.mockResolvedValue({
            ok: true,
            json: async () => ({ data: [] }),
        });

        render(<NotificationBell recentNotifications={[]} />);

        await act(async () => {
            fireEvent.click(screen.getByRole('button', { name: /open notifications/i }));
        });

        expect(mockFetch).toHaveBeenCalled();
    });
});
