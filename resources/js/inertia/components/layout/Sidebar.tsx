import { Link, usePage } from '@inertiajs/react';
import { 
    Home, 
    ShoppingCart, 
    Package, 
    ClipboardList, 
    Calendar, 
    Users, 
    User, 
    Building, 
    Briefcase,
    ChevronLeft,
    ChevronRight,
    ChevronDown,
    ChevronUp,
    FileText,
    List,
    CheckCircle
} from 'lucide-react';
import { PageProps } from '../../types';
import { cn } from '../../lib/utils';
import { useState } from 'react';
import { usePrefetch } from '../../hooks/usePrefetch';

interface SidebarProps {
    minimized: boolean;
    onToggle: () => void;
}

const iconMap: Record<string, any> = {
    'home': Home,
    'shopping-cart': ShoppingCart,
    'cube': Package,
    'package': Package,
    'clipboard-list': ClipboardList,
    'calendar': Calendar,
    'users': Users,
    'user': User,
    'office-building': Building,
    'briefcase': Briefcase,
    'file-text': FileText,
    'list': List,
    'check-circle': CheckCircle,
};

export default function Sidebar({ minimized, onToggle }: SidebarProps) {
    const { navigation } = usePage<PageProps>().props;
    const [expandedMenus, setExpandedMenus] = useState<Record<string, boolean>>({});

    // Initialize prefetch hook for navigation links
    // Use a slightly longer delay (150ms) for sidebar to avoid excessive prefetching
    const { onMouseEnter: prefetchOnHover, onMouseLeave: cancelPrefetch } = usePrefetch({ 
        delay: 150 
    });

    const toggleMenu = (menuName: string) => {
        setExpandedMenus(prev => ({
            ...prev,
            [menuName]: !prev[menuName]
        }));
    };

    return (
        <aside
            className={cn(
                'fixed left-0 top-0 h-full bg-white border-r border-gray-200 transition-all duration-300 z-30',
                minimized ? 'w-16' : 'w-64'
            )}
        >
            {/* Logo Section */}
            <div className="h-16 flex items-center justify-between px-4 border-b border-gray-200">
                {!minimized && (
                    <Link href="/dashboard" className="flex items-center space-x-2">
                        <div className="w-8 h-8 bg-indigo-600 rounded-lg flex items-center justify-center">
                            <span className="text-white font-bold text-sm">O</span>
                        </div>
                        <span className="font-semibold text-gray-900">Oasis</span>
                    </Link>
                )}
                {minimized && (
                    <Link href="/dashboard" className="flex items-center justify-center w-full">
                        <div className="w-8 h-8 bg-indigo-600 rounded-lg flex items-center justify-center">
                            <span className="text-white font-bold text-sm">O</span>
                        </div>
                    </Link>
                )}
            </div>

            {/* Toggle Button */}
            <button
                onClick={onToggle}
                className="absolute -right-3 top-20 w-6 h-6 bg-white border border-gray-200 rounded-full flex items-center justify-center hover:bg-gray-50 transition-colors"
            >
                {minimized ? (
                    <ChevronRight className="w-3 h-3 text-gray-600" />
                ) : (
                    <ChevronLeft className="w-3 h-3 text-gray-600" />
                )}
            </button>

            {/* Navigation */}
            <nav className="flex-1 overflow-y-auto py-4">
                {navigation.sections.map((section, sectionIndex) => (
                    <div key={sectionIndex} className="mb-6">
                        {!minimized && (
                            <div className="px-4 mb-2">
                                <h3 className="text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    {section.name}
                                </h3>
                            </div>
                        )}
                        <div className="space-y-1 px-2">
                            {section.items.map((item, itemIndex) => {
                                const Icon = iconMap[item.icon] || Home;
                                const isActive = item.active;
                                const hasChildren = item.children && item.children.length > 0;
                                const isExpanded = expandedMenus[item.name] ?? false;

                                return (
                                    <div key={itemIndex}>
                                        {/* Parent Menu Item */}
                                        {hasChildren && !minimized ? (
                                            <button
                                                onClick={() => toggleMenu(item.name)}
                                                className={cn(
                                                    'w-full flex items-center px-3 py-2 rounded-lg text-sm font-medium transition-colors',
                                                    isActive
                                                        ? 'bg-indigo-50 text-indigo-600'
                                                        : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900'
                                                )}
                                            >
                                                <Icon className="w-5 h-5 mr-3" />
                                                <span className="flex-1 text-left">{item.name}</span>
                                                {isExpanded ? (
                                                    <ChevronUp className="w-4 h-4" />
                                                ) : (
                                                    <ChevronDown className="w-4 h-4" />
                                                )}
                                            </button>
                                        ) : (
                                            <Link
                                                href={item.href}
                                                className={cn(
                                                    'flex items-center px-3 py-2 rounded-lg text-sm font-medium transition-colors',
                                                    isActive
                                                        ? 'bg-indigo-50 text-indigo-600'
                                                        : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900',
                                                    minimized && 'justify-center'
                                                )}
                                                title={minimized ? item.name : undefined}
                                                onMouseEnter={prefetchOnHover}
                                                onMouseLeave={cancelPrefetch}
                                            >
                                                <Icon className={cn('w-5 h-5', !minimized && 'mr-3')} />
                                                {!minimized && <span>{item.name}</span>}
                                                {!minimized && item.badge && (
                                                    <span className="ml-auto inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                        {item.badge}
                                                    </span>
                                                )}
                                            </Link>
                                        )}

                                        {/* Children Menu Items (Dropdown) */}
                                        {hasChildren && !minimized && isExpanded && (
                                            <div className="ml-4 mt-1 space-y-1">
                                                {item.children!.map((child, childIndex) => {
                                                    const ChildIcon = iconMap[child.icon] || FileText;
                                                    const isChildActive = child.active;

                                                    return (
                                                        <Link
                                                            key={childIndex}
                                                            href={child.href}
                                                            className={cn(
                                                                'flex items-center px-3 py-2 rounded-lg text-sm transition-colors',
                                                                isChildActive
                                                                    ? 'bg-indigo-50 text-indigo-600 font-medium'
                                                                    : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'
                                                            )}
                                                            onMouseEnter={prefetchOnHover}
                                                            onMouseLeave={cancelPrefetch}
                                                        >
                                                            <ChildIcon className="w-4 h-4 mr-3" />
                                                            <span>{child.name}</span>
                                                            {child.badge && (
                                                                <span className="ml-auto inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                                    {child.badge}
                                                                </span>
                                                            )}
                                                        </Link>
                                                    );
                                                })}
                                            </div>
                                        )}
                                    </div>
                                );
                            })}
                        </div>
                    </div>
                ))}
            </nav>
        </aside>
    );
}
