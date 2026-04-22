import { describe, it, expect, vi } from 'vitest';
import { render, screen, fireEvent } from '@testing-library/react';
import Navbar from '@/components/layout/Navbar';
import type { NotificationListItem } from '@/types/notifications';

vi.mock('@inertiajs/react', () => ({
    Link: ({ children, href, ...props }: { children: React.ReactNode; href: string }) => <a href={href} {...props}>{children}</a>,
    usePage: () => ({
        props: {
            auth: { user: { id: 1, name: 'Test User', email: 'test@example.com', role: 'user' } },
            currentBusinessUnit: { id: 1, code: 'WNS', name: 'WNS Business Unit', logo: null },
            availableBusinessUnits: [
                { id: 1, code: 'WNS', name: 'WNS Business Unit', logo: null },
            ],
            navigation: { sections: [] },
            flash: {},
            notifications: { unread_count: 0 },
            appName: 'Test App',
        },
    }),
}));

describe('Navbar Component', () => {
    const defaultProps = {
        onMenuClick: vi.fn(),
        sidebarMinimized: false,
        unreadCount: 0,
        notificationItems: [] as NotificationListItem[],
        hasNewNotification: false,
        notificationDropdownOpen: false,
        onNotificationToggle: vi.fn(),
        onNotificationOpen: vi.fn(),
    };

    it('renders the mobile menu button', () => {
        render(<Navbar {...defaultProps} />);

        expect(screen.getByLabelText('Toggle navigation menu')).toBeInTheDocument();
    });

    it('calls onMenuClick when hamburger button is clicked', () => {
        const onMenuClick = vi.fn();
        render(<Navbar {...defaultProps} onMenuClick={onMenuClick} />);

        const hamburgerButton = screen.getByLabelText('Toggle navigation menu');
        fireEvent.click(hamburgerButton);

        expect(onMenuClick).toHaveBeenCalledOnce();
    });

    it('renders the notification bell', () => {
        render(<Navbar {...defaultProps} />);

        expect(screen.getByLabelText('Open notifications')).toBeInTheDocument();
    });

    it('passes unread count to notification bell', () => {
        render(<Navbar {...defaultProps} unreadCount={5} />);

        expect(screen.getByText('5')).toBeInTheDocument();
    });

    it('renders the current date', () => {
        render(<Navbar {...defaultProps} />);

        // The date is formatted as "EEEE, MMMM d, yyyy"
        // We can check for the year at minimum
        expect(screen.getByText(/2026/)).toBeInTheDocument();
    });

    it('renders business unit name', () => {
        render(<Navbar {...defaultProps} />);

        expect(screen.getByText('WNS Business Unit')).toBeInTheDocument();
    });

    it('passes notification dropdown state to NotificationBell', () => {
        render(<Navbar {...defaultProps} notificationDropdownOpen={true} />);

        // When open, the dropdown header should be visible
        expect(screen.getByText('Notifications')).toBeInTheDocument();
    });

    it('calls onNotificationToggle when bell is clicked', () => {
        const onNotificationToggle = vi.fn();
        const onNotificationOpen = vi.fn();

        render(
            <Navbar
                {...defaultProps}
                onNotificationToggle={onNotificationToggle}
                onNotificationOpen={onNotificationOpen}
            />
        );

        fireEvent.click(screen.getByLabelText('Open notifications'));

        expect(onNotificationToggle).toHaveBeenCalledOnce();
        expect(onNotificationOpen).toHaveBeenCalledOnce();
    });
});
