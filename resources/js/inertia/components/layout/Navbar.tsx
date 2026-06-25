import { Link } from '@inertiajs/react';
import { Droplets, Menu } from 'lucide-react';
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
    return (
        <header
            className="sticky top-0 z-20 h-14 flex-shrink-0 border-b border-slate-300/70 bg-white/60 pt-2 backdrop-blur-sm"
            role="banner"
        >
            <div className="flex h-full items-center justify-between px-5">
                <div className="flex min-w-0 items-center gap-3">
                    <button
                        onClick={onMenuClick}
                        className="rounded-lg p-2 text-slate-600 hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-primary lg:hidden"
                        aria-label="Toggle navigation menu"
                    >
                        <Menu className="h-5 w-5" />
                    </button>
                    <Link href="/dashboard" className="flex items-center gap-2.5">
                        <div className="flex h-7 w-7 items-center justify-center rounded-md bg-[#16599c] shadow-sm">
                            <Droplets className="h-4 w-4 text-white" />
                        </div>
                        <div className="flex flex-col leading-none">
                            <span className="text-sm font-bold uppercase tracking-wide text-gray-900">OASIS</span>
                            <span className="text-xs tracking-wide text-gray-400">workspace</span>
                        </div>
                    </Link>
                </div>

                <div className="flex items-center gap-2">
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
