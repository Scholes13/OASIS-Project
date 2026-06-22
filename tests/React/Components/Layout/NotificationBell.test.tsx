import { describe, expect, it, vi } from 'vitest';
import { fireEvent, render, screen } from '@testing-library/react';
import NotificationBell from '@/components/layout/NotificationBell';
import type { NotificationListItem } from '@/types/notifications';

vi.mock('@inertiajs/react', () => ({
    Link: ({ children, href, ...props }: { children: React.ReactNode; href: string }) => <a href={href} {...props}>{children}</a>,
}));

describe('NotificationBell', () => {
    const defaultProps = {
        unreadCount: 0,
        items: [] as NotificationListItem[],
        hasNewNotification: false,
        open: false,
        onToggle: vi.fn(),
        onOpen: vi.fn(),
    };

    it('renders unread count badge when count is greater than zero', () => {
        render(<NotificationBell {...defaultProps} unreadCount={3} />);

        expect(screen.getByText('3')).toBeInTheDocument();
    });

    it('does not render badge when unread count is zero', () => {
        const { container } = render(<NotificationBell {...defaultProps} unreadCount={0} />);

        // Badge should not be present
        expect(container.querySelector('.bg-blue-600')).not.toBeInTheDocument();
    });

    it('renders 99+ when unread count exceeds 99', () => {
        render(<NotificationBell {...defaultProps} unreadCount={150} />);

        expect(screen.getByText('99+')).toBeInTheDocument();
    });

    it('shows empty state when open with no items', () => {
        render(<NotificationBell {...defaultProps} open={true} items={[]} />);

        expect(screen.getByText('No notifications yet.')).toBeInTheDocument();
    });

    it('renders notification items when open with items', () => {
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

        render(<NotificationBell {...defaultProps} open={true} items={notifications} />);

        expect(screen.getByText('You were tagged by Amanda in Activity Budget Review')).toBeInTheDocument();
        expect(screen.getByText('Amanda tagged you in Budget Review.')).toBeInTheDocument();
    });

    it('calls onOpen and onToggle when bell is clicked while closed', () => {
        const onToggle = vi.fn();
        const onOpen = vi.fn();

        render(<NotificationBell {...defaultProps} open={false} onToggle={onToggle} onOpen={onOpen} />);

        fireEvent.click(screen.getByRole('button', { name: /open notifications/i }));

        expect(onOpen).toHaveBeenCalledOnce();
        expect(onToggle).toHaveBeenCalledOnce();
    });

    it('calls only onToggle when bell is clicked while already open', () => {
        const onToggle = vi.fn();
        const onOpen = vi.fn();

        render(<NotificationBell {...defaultProps} open={true} onToggle={onToggle} onOpen={onOpen} />);

        fireEvent.click(screen.getByRole('button', { name: /open notifications/i }));

        expect(onOpen).not.toHaveBeenCalled();
        expect(onToggle).toHaveBeenCalledOnce();
    });

    it('applies ring highlight when hasNewNotification is true', () => {
        render(<NotificationBell {...defaultProps} hasNewNotification={true} />);

        const button = screen.getByRole('button', { name: /open notifications/i });
        expect(button.className).toContain('ring-2');
        expect(button.className).toContain('ring-blue-400');
    });

    it('does not show dropdown when open is false', () => {
        render(<NotificationBell {...defaultProps} open={false} />);

        expect(screen.queryByText('Notifications')).not.toBeInTheDocument();
    });

    it('shows "View all notifications" link when open', () => {
        render(<NotificationBell {...defaultProps} open={true} />);

        expect(screen.getByText('View all notifications')).toBeInTheDocument();
    });
});
