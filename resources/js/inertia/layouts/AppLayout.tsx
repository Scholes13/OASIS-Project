import { ReactNode, useState, useEffect } from 'react';
import { Head } from '@inertiajs/react';
import Sidebar from '../components/layout/Sidebar';
import Navbar from '../components/layout/Navbar';
import BuTransitionOverlay from '../components/layout/BuTransitionOverlay';
import LogoutOverlay from '../components/layout/LogoutOverlay';
import { Toaster } from '../components/ui/toast';
import { cn } from '../lib/utils';

interface AppLayoutProps {
    children: ReactNode;
    title?: string;
}

export default function AppLayout({ children, title }: AppLayoutProps) {
    const [sidebarMinimized, setSidebarMinimized] = useState(false);
    const [mobileSidebarOpen, setMobileSidebarOpen] = useState(false);

    // Load sidebar state from localStorage
    useEffect(() => {
        const stored = localStorage.getItem('sidebar-minimized');
        if (stored) {
            setSidebarMinimized(stored === 'true');
        }
    }, []);

    // Save sidebar state to localStorage
    const toggleSidebar = () => {
        const newState = !sidebarMinimized;
        setSidebarMinimized(newState);
        localStorage.setItem('sidebar-minimized', String(newState));
    };

    return (
        <>
            {title && <Head title={title} />}
            
            {/* Toast Notifications */}
            <Toaster position="top-right" richColors closeButton duration={5000} />
            
            {/* BU Transition Overlay - renders above everything */}
            <BuTransitionOverlay />
            
            {/* Logout Overlay - renders above everything */}
            <LogoutOverlay />
            
            <div className="min-h-screen bg-gray-50">
                {/* Sidebar */}
                <Sidebar minimized={sidebarMinimized} onToggle={toggleSidebar} />

                {/* Mobile Sidebar Overlay */}
                {mobileSidebarOpen && (
                    <div
                        className="fixed inset-0 bg-black bg-opacity-50 z-20 lg:hidden"
                        onClick={() => setMobileSidebarOpen(false)}
                    />
                )}

                {/* Navbar */}
                <Navbar
                    onMenuClick={() => setMobileSidebarOpen(!mobileSidebarOpen)}
                    sidebarMinimized={sidebarMinimized}
                />

                {/* Main Content */}
                <main
                    className={cn(
                        'pt-16 transition-all duration-300',
                        sidebarMinimized ? 'ml-16' : 'ml-64'
                    )}
                >
                    {children}
                </main>
            </div>
        </>
    );
}
