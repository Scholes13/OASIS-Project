import { Menu } from 'lucide-react';
import { format } from 'date-fns';
import BusinessUnitSwitcher from './BusinessUnitSwitcher';
import DepartmentSwitcher from './DepartmentSwitcher';
import NotificationBell from './NotificationBell';
import UserMenu from './UserMenu';
import type { NotificationListItem } from '@/types/notifications';

interface NavbarProps {
    onMenuClick: () => void;
    sidebarMinimized?: boolean;
    unreadCount: number;
    notificationItems: NotificationListItem[];
    hasNewNotification: boolean;
    notificationDropdownOpen: boolean;
    onNotificationToggle: () => void;
    onNotificationOpen: () => void;
}

export default function Navbar({
    onMenuClick,
    unreadCount,
    notificationItems,
    hasNewNotification,
    notificationDropdownOpen,
    onNotificationToggle,
    onNotificationOpen,
}: NavbarProps) {
    // Format current date: "Wednesday, January 28, 2026"
    const today = format(new Date(), 'EEEE, MMMM d, yyyy');

    return (
        <header
            className="sticky top-0 h-14 flex-shrink-0 bg-white border-b border-gray-200/80 z-20"
            role="banner"
        >
            <div className="h-full px-5 flex items-center justify-between">
                {/* Left side - Mobile menu button */}
                <div className="flex items-center">
                    <button
                        onClick={onMenuClick}
                        className="lg:hidden p-2 rounded-lg text-gray-600 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-primary"
                        aria-label="Toggle navigation menu"
                    >
                        <Menu className="w-5 h-5" />
                    </button>
                </div>

                {/* Right side - Date, Business Unit Switcher & User Menu */}
                <div className="flex items-center gap-4">
                    {/* Date display - right next to BU switcher */}
                    <span className="hidden md:block text-sm text-gray-500 font-medium">
                        {today}
                    </span>
                    <BusinessUnitSwitcher />
                    <DepartmentSwitcher />
                    <NotificationBell
                        unreadCount={unreadCount}
                        items={notificationItems}
                        hasNewNotification={hasNewNotification}
                        open={notificationDropdownOpen}
                        onToggle={onNotificationToggle}
                        onOpen={onNotificationOpen}
                    />
                    <UserMenu />
                </div>
            </div>
        </header>
    );
}
