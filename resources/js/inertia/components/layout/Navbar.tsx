import { Menu } from 'lucide-react';
import { format } from 'date-fns';
import BusinessUnitSwitcher from './BusinessUnitSwitcher';
import UserMenu from './UserMenu';

interface NavbarProps {
    onMenuClick: () => void;
    sidebarMinimized: boolean;
}

export default function Navbar({ onMenuClick, sidebarMinimized }: NavbarProps) {
    // Format current date: "Wednesday, January 28, 2026"
    const today = format(new Date(), 'EEEE, MMMM d, yyyy');

    return (
        <header className={`fixed top-0 right-0 h-16 bg-white border-b border-gray-200 z-20 transition-all duration-300 ${sidebarMinimized ? 'left-16' : 'left-64'}`}
        >
            <div className="h-full px-6 flex items-center justify-between">
                {/* Left side - Mobile menu button */}
                <div className="flex items-center">
                    <button
                        onClick={onMenuClick}
                        className="lg:hidden p-2 rounded-lg text-gray-600 hover:bg-gray-100"
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
                    <UserMenu />
                </div>
            </div>
        </header>
    );
}
