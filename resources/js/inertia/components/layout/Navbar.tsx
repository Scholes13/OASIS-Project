import { Menu } from 'lucide-react';
import BusinessUnitSwitcher from './BusinessUnitSwitcher';
import UserMenu from './UserMenu';

interface NavbarProps {
    onMenuClick: () => void;
    sidebarMinimized: boolean;
}

export default function Navbar({ onMenuClick, sidebarMinimized }: NavbarProps) {
    return (
        <header className={`fixed top-0 right-0 h-16 bg-white border-b border-gray-200 z-20 transition-all duration-300 ${sidebarMinimized ? 'left-16' : 'left-64'}`}
        >
            <div className="h-full px-4 flex items-center justify-between">
                {/* Left side - Mobile menu button */}
                <button
                    onClick={onMenuClick}
                    className="lg:hidden p-2 rounded-lg text-gray-600 hover:bg-gray-100"
                >
                    <Menu className="w-5 h-5" />
                </button>

                {/* Right side - Business Unit Switcher & User Menu */}
                <div className="ml-auto flex items-center space-x-4">
                    <BusinessUnitSwitcher />
                    <UserMenu />
                </div>
            </div>
        </header>
    );
}
