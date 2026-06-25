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
    CheckCircle,
    Tag,
    Mail,
    Clock,
    BookOpen,
    PieChart,
    PenSquare,
    Settings,
    ShieldCheck,
} from 'lucide-react';
import { PageProps } from '../../types';
import { cn } from '../../lib/utils';
import { useState, useEffect } from 'react';
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
    'tag': Tag,
    'mail': Mail,
    'clock': Clock,
    'book-open': BookOpen,
    'chart-pie': PieChart,
    'pencil-square': PenSquare,
    'cog-6-tooth': Settings,
    'shield-check': ShieldCheck,
};

export default function Sidebar({ minimized, onToggle }: SidebarProps) {
    const { navigation } = usePage<PageProps>().props;
    const { url } = usePage();
    const currentPath = url.split('?')[0]; // Remove query string

    // Determine active state from current URL (not from cached backend state)
    const isItemActive = (href: string): boolean => {
        const itemPath = new URL(href, window.location.origin).pathname;
        if (itemPath === '/dashboard') return currentPath === '/dashboard';
        if (itemPath === '/stock-requests' && currentPath.startsWith('/stock-requests/ga-review')) return false;
        return currentPath === itemPath || currentPath.startsWith(itemPath + '/');
    };

    const isParentActive = (item: any): boolean => {
        if (item.children?.length > 0) {
            return item.children.some((child: any) => isItemActive(child.href));
        }
        return isItemActive(item.href);
    };

    // Auto-expand menus that have an active child
    const getDefaultExpanded = (): Record<string, boolean> => {
        const expanded: Record<string, boolean> = {};
        navigation.sections.forEach((section) => {
            section.items.forEach((item) => {
                if (item.children?.length && isParentActive(item)) {
                    expanded[item.name] = true;
                }
            });
        });
        return expanded;
    };

    const [expandedMenus, setExpandedMenus] = useState<Record<string, boolean>>(getDefaultExpanded);

    // Update expanded state when URL changes (Inertia navigation)
    useEffect(() => {
        const newExpanded = getDefaultExpanded();
        setExpandedMenus(prev => {
            // Merge: keep user-toggled state, but auto-expand active parents
            const merged = { ...prev };
            Object.keys(newExpanded).forEach(key => {
                if (newExpanded[key]) merged[key] = true;
            });
            return merged;
        });
    }, [currentPath]);

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
                'fixed left-0 top-14 h-[calc(100vh-3.5rem)] bg-white border-r border-gray-200/80 transition-all duration-300 z-30 font-sans',
                minimized ? 'w-16' : 'w-60'
            )}
            aria-label="Main navigation"
        >
            {/* Toggle Button */}
            <button
                onClick={onToggle}
                className="absolute -right-3 top-6 w-6 h-6 bg-white border border-gray-200 rounded-full flex items-center justify-center hover:bg-gray-50 shadow-sm transition-colors"
            >
                {minimized ? (
                    <ChevronRight className="w-3 h-3 text-gray-400" />
                ) : (
                    <ChevronLeft className="w-3 h-3 text-gray-400" />
                )}
            </button>

            {/* Navigation */}
            <nav className="flex-1 overflow-y-auto pt-5 pb-4" aria-label="Sidebar navigation">
                {navigation.sections.map((section, sectionIndex) => (
                    <div key={sectionIndex} className="mb-5">
                        {!minimized && section.name !== 'Dashboard' && (
                            <div className="px-4 mb-1.5">
                                <h3 className="text-[11px] font-semibold text-gray-400 uppercase tracking-widest">
                                    {section.name}
                                </h3>
                            </div>
                        )}
                        <div className="space-y-0.5 px-2.5">
                            {section.items.map((item, itemIndex) => {
                                const Icon = iconMap[item.icon] || Home;
                                const isActive = isParentActive(item);
                                const hasChildren = item.children && item.children.length > 0;
                                const isExpanded = expandedMenus[item.name] ?? false;

                                return (
                                    <div key={itemIndex}>
                                        {/* Parent Menu Item */}
                                        {hasChildren && !minimized ? (
                                            <button
                                                onClick={() => toggleMenu(item.name)}
                                                className={cn(
                                                    'w-full flex items-center px-2.5 py-[7px] rounded-md text-[13px] font-medium transition-all duration-150',
                                                    isActive
                                                        ? 'bg-primary/8 text-primary font-semibold'
                                                        : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'
                                                )}
                                            >
                                                <Icon className="w-[18px] h-[18px] mr-2.5 flex-shrink-0" />
                                                <span className="flex-1 text-left truncate">{item.name}</span>
                                                {isExpanded ? (
                                                    <ChevronUp className="w-3.5 h-3.5 text-gray-400" />
                                                ) : (
                                                    <ChevronDown className="w-3.5 h-3.5 text-gray-400" />
                                                )}
                                            </button>
                                        ) : (
                                            <Link
                                                href={item.href}
                                                className={cn(
                                                    'flex items-center px-2.5 py-[7px] rounded-md text-[13px] font-medium transition-all duration-150',
                                                    isActive
                                                        ? 'bg-primary/8 text-primary font-semibold'
                                                        : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900',
                                                    minimized && 'justify-center px-0'
                                                )}
                                                title={minimized ? item.name : undefined}
                                                onMouseEnter={prefetchOnHover}
                                                onMouseLeave={cancelPrefetch}
                                            >
                                                <Icon className={cn('w-[18px] h-[18px] flex-shrink-0', !minimized && 'mr-2.5')} />
                                                {!minimized && <span className="truncate">{item.name}</span>}
                                                {!minimized && item.badge && (
                                                    <span className="ml-auto inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-semibold bg-red-50 text-red-600">
                                                        {item.badge}
                                                    </span>
                                                )}
                                            </Link>
                                        )}

                                        {/* Children Menu Items (Dropdown) */}
                                        {hasChildren && !minimized && isExpanded && (
                                            <div className="ml-[26px] mt-0.5 space-y-0.5 border-l border-gray-200 pl-2.5">
                                                {item.children!.map((child, childIndex) => {
                                                    const ChildIcon = iconMap[child.icon] || FileText;
                                                    const isChildActive = isItemActive(child.href);

                                                    return (
                                                        <Link
                                                            key={childIndex}
                                                            href={child.href}
                                                            className={cn(
                                                                'flex items-center px-2 py-[6px] rounded-md text-[13px] transition-all duration-150',
                                                                isChildActive
                                                                    ? 'text-primary font-semibold bg-primary/5'
                                                                    : 'text-gray-500 hover:text-gray-900 hover:bg-gray-50'
                                                            )}
                                                            onMouseEnter={prefetchOnHover}
                                                            onMouseLeave={cancelPrefetch}
                                                        >
                                                            <ChildIcon className="w-4 h-4 mr-2 flex-shrink-0" />
                                                            <span className="truncate">{child.name}</span>
                                                            {child.badge && (
                                                                <span className="ml-auto inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-semibold bg-red-50 text-red-600">
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
