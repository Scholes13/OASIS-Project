import { ReactNode, useState, useEffect, useCallback } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import Sidebar from '../components/layout/Sidebar';
import Navbar from '../components/layout/Navbar';
import BuTransitionOverlay from '../components/layout/BuTransitionOverlay';
import LogoutOverlay from '../components/layout/LogoutOverlay';
import { Toaster } from '../components/ui/toast';
import { cn } from '../lib/utils';
import { useNotifications } from '@/hooks/useNotifications';
import type { NotificationListItem, PageProps } from '@/types';

interface AppLayoutProps {
    children: ReactNode;
    title?: string;
}

export default function AppLayout({ children, title }: AppLayoutProps) {
    const page = usePage<PageProps>();
    const [sidebarMinimized, setSidebarMinimized] = useState(false);
    const [mobileSidebarOpen, setMobileSidebarOpen] = useState(false);
    const [notifOpen, setNotifOpen] = useState(false);

    const recentNotifications = (page.props.recentNotifications ?? []) as NotificationListItem[];
    const {
        unreadCount,
        recentItems,
        refreshNotifications,
        hasNewNotification,
        clearNewFlag,
    } = useNotifications(recentNotifications);

    // Load sidebar state from localStorage
    useEffect(() => {
        const stored = localStorage.getItem('sidebar-minimized');
        if (stored) {
            setSidebarMinimized(stored === 'true');
        }
    }, []);

    // Close mobile sidebar or notification dropdown on Escape key
    useEffect(() => {
        const handleKeyDown = (e: KeyboardEvent) => {
            if (e.key === 'Escape') {
                if (notifOpen) {
                    setNotifOpen(false);
                } else if (mobileSidebarOpen) {
                    setMobileSidebarOpen(false);
                }
            }
        };

        document.addEventListener('keydown', handleKeyDown);
        return () => document.removeEventListener('keydown', handleKeyDown);
    }, [mobileSidebarOpen, notifOpen]);

    // Close notification dropdown on route change
    useEffect(() => {
        return router.on('navigate', () => {
            setNotifOpen(false);
        });
    }, []);

    // Close notification dropdown on outside click
    useEffect(() => {
        if (!notifOpen) return;

        const handleClickOutside = (e: MouseEvent) => {
            const target = e.target as HTMLElement;
            // If click is outside the notification bell area, close it
            if (!target.closest('[aria-label="Open notifications"]') && !target.closest('.absolute.right-0.z-30')) {
                setNotifOpen(false);
            }
        };

        // Delay to avoid closing immediately on the same click that opened it
        const timer = setTimeout(() => {
            document.addEventListener('click', handleClickOutside);
        }, 0);

        return () => {
            clearTimeout(timer);
            document.removeEventListener('click', handleClickOutside);
        };
    }, [notifOpen]);

    // Lock body scroll when mobile sidebar is open
    useEffect(() => {
        if (mobileSidebarOpen) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = '';
        }
        return () => { document.body.style.overflow = ''; };
    }, [mobileSidebarOpen]);

    const toggleSidebar = useCallback(() => {
        const newState = !sidebarMinimized;
        setSidebarMinimized(newState);
        localStorage.setItem('sidebar-minimized', String(newState));
    }, [sidebarMinimized]);

    const closeMobileSidebar = useCallback(() => {
        setMobileSidebarOpen(false);
    }, []);

    const toggleMobileSidebar = useCallback(() => {
        setMobileSidebarOpen(prev => !prev);
    }, []);

    return (
        <>
            {title && <Head title={title} />}

            {/* Skip to Content — WCAG 2.1 keyboard navigation */}
            <a
                href="#main-content"
                className="sr-only focus:not-sr-only focus:fixed focus:top-4 focus:left-4 focus:z-[100] focus:px-4 focus:py-2 focus:bg-white focus:text-primary focus:font-semibold focus:rounded-lg focus:shadow-lg focus:ring-2 focus:ring-primary focus:outline-none"
            >
                Skip to content
            </a>

            {/* Toast Notifications */}
            <Toaster position="top-right" richColors closeButton duration={5000} />

            {/* BU Transition Overlay */}
            <BuTransitionOverlay />

            {/* Logout Overlay */}
            <LogoutOverlay />

            <div className="flex h-screen overflow-hidden bg-background">
                {/* Sidebar — hidden on mobile, shown on lg+ */}
                <div className="hidden lg:block">
                    <Sidebar minimized={sidebarMinimized} onToggle={toggleSidebar} />
                </div>

                {/* Mobile Sidebar Overlay + Drawer */}
                {mobileSidebarOpen && (
                    <div className="fixed inset-0 z-40 lg:hidden" role="dialog" aria-modal="true">
                        {/* Backdrop */}
                        <div
                            className="fixed inset-0 bg-black/50 transition-opacity"
                            onClick={closeMobileSidebar}
                            aria-hidden="true"
                        />
                        {/* Drawer */}
                        <div className="fixed inset-y-0 left-0 w-60 z-50">
                            <Sidebar minimized={false} onToggle={closeMobileSidebar} />
                        </div>
                    </div>
                )}

                {/* Main Wrapper — responsive margin: 0 on mobile, ml-16/ml-64 on lg+ */}
                <div
                    className={cn(
                        'flex flex-1 flex-col overflow-hidden transition-all duration-300',
                        'lg:ml-16',
                        !sidebarMinimized && 'lg:ml-60'
                    )}
                >
                    {/* Navbar */}
                    <Navbar
                        onMenuClick={toggleMobileSidebar}
                        sidebarMinimized={sidebarMinimized}
                        unreadCount={unreadCount}
                        notificationItems={recentItems}
                        hasNewNotification={hasNewNotification}
                        notificationDropdownOpen={notifOpen}
                        onNotificationToggle={() => {
                            setNotifOpen((v) => !v);
                            if (hasNewNotification) clearNewFlag();
                        }}
                        onNotificationOpen={() => {
                            void refreshNotifications();
                            if (hasNewNotification) clearNewFlag();
                        }}
                    />

                    {/* Main Content — scrollable area */}
                    <main id="main-content" className="flex-1 overflow-y-auto bg-[#f8fafc]">
                        {children}
                    </main>
                </div>
            </div>
        </>
    );
}
