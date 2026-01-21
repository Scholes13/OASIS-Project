import * as React from 'react';
import { Link, router } from '@inertiajs/react';
import { motion, AnimatePresence } from 'framer-motion';
import {
    CheckCircle,
    Search,
    Filter,
    ChevronLeft,
    ChevronRight,
} from 'lucide-react';
import { cn } from '@/lib/utils';
import { PurchasingTaskCard } from './PurchasingTaskCard';
import type { AdminTask, TaskFilters, TaskCounts } from './types';

interface PurchasingTaskListProps {
    tasks: {
        data: AdminTask[];
        links: any[];
        current_page: number;
        last_page: number;
        total: number;
    };
    filters: TaskFilters;
    counts: TaskCounts;
    onClaim?: (taskId: number) => void;
    onStart?: (taskId: number) => void;
}

const tabs = [
    { id: 'pending', label: 'Pending', countKey: 'pending' as keyof TaskCounts },
    { id: 'in_progress', label: 'In Progress', countKey: 'in_progress' as keyof TaskCounts },
    { id: 'completed', label: 'Completed', countKey: 'completed' as keyof TaskCounts },
];

const typeOptions = [
    { value: '', label: 'All Types' },
    { value: 'purchase_request', label: 'Purchase Request' },
    { value: 'stock_request', label: 'Stock Request' },
];

const dateOptions = [
    { value: 'all', label: 'All Dates' },
    { value: 'today', label: 'Today' },
    { value: 'last_30_days', label: 'Last 30 Days' },
];

export function PurchasingTaskList({
    tasks,
    filters,
    counts,
    onClaim,
    onStart,
}: PurchasingTaskListProps) {
    const [activeTab, setActiveTab] = React.useState(filters.status || 'pending');
    const [searchQuery, setSearchQuery] = React.useState(filters.search || '');
    const [typeFilter, setTypeFilter] = React.useState(filters.type || '');
    const [dateFilter, setDateFilter] = React.useState(filters.date || 'all');

    // Handle tab change
    const handleTabChange = (tab: string) => {
        setActiveTab(tab);
        router.get(route('purchasing.admin.tasks'), {
            status: tab,
            type: typeFilter,
            date: dateFilter,
            search: searchQuery,
        }, { preserveState: true, preserveScroll: true });
    };

    // Handle filter changes
    const handleFilterChange = (newFilters: Partial<TaskFilters>) => {
        router.get(route('purchasing.admin.tasks'), {
            status: activeTab,
            type: newFilters.type ?? typeFilter,
            date: newFilters.date ?? dateFilter,
            search: newFilters.search ?? searchQuery,
        }, { preserveState: true, preserveScroll: true });
    };

    // Handle search
    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        handleFilterChange({ search: searchQuery });
    };

    return (
        <div>
            {/* Tabs */}
            <div className="border-b border-gray-200 mb-6">
                <nav className="flex space-x-8">
                    {tabs.map((tab) => (
                        <button
                            key={tab.id}
                            onClick={() => handleTabChange(tab.id)}
                            className={cn(
                                "py-4 px-1 border-b-2 font-medium text-sm whitespace-nowrap transition-colors",
                                activeTab === tab.id
                                    ? "border-indigo-500 text-indigo-600"
                                    : "border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300"
                            )}
                        >
                            {tab.label}
                            <span className={cn(
                                "ml-2 py-0.5 px-2 rounded-full text-xs",
                                activeTab === tab.id
                                    ? "bg-indigo-100 text-indigo-600"
                                    : "bg-gray-100 text-gray-600"
                            )}>
                                {counts[tab.countKey]}
                            </span>
                        </button>
                    ))}
                </nav>
            </div>

            {/* Filters */}
            <div className="flex flex-col sm:flex-row gap-4 mb-6">
                <div className="flex gap-3">
                    <select
                        value={typeFilter}
                        onChange={(e) => {
                            setTypeFilter(e.target.value);
                            handleFilterChange({ type: e.target.value });
                        }}
                        className="px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                    >
                        {typeOptions.map((opt) => (
                            <option key={opt.value} value={opt.value}>{opt.label}</option>
                        ))}
                    </select>
                    <select
                        value={dateFilter}
                        onChange={(e) => {
                            setDateFilter(e.target.value);
                            handleFilterChange({ date: e.target.value });
                        }}
                        className="px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                    >
                        {dateOptions.map((opt) => (
                            <option key={opt.value} value={opt.value}>{opt.label}</option>
                        ))}
                    </select>
                </div>
                <div className="flex-1 max-w-md">
                    <form onSubmit={handleSearch} className="relative">
                        <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
                        <input
                            type="text"
                            placeholder="Search number..."
                            value={searchQuery}
                            onChange={(e) => setSearchQuery(e.target.value)}
                            className="w-full pl-9 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        />
                    </form>
                </div>
            </div>

            {/* Task Grid */}
            <AnimatePresence mode="wait">
                {tasks.data.length === 0 ? (
                    <motion.div
                        initial={{ opacity: 0 }}
                        animate={{ opacity: 1 }}
                        className="text-center py-12 bg-white rounded-lg border border-gray-200"
                    >
                        <CheckCircle className="mx-auto h-12 w-12 text-gray-300 mb-4" />
                        <h3 className="text-lg font-medium text-gray-900 mb-2">No tasks found</h3>
                        <p className="text-gray-500">
                            Great job! You have no {activeTab.replace('_', ' ')} tasks requiring attention.
                        </p>
                    </motion.div>
                ) : (
                    <motion.div
                        initial={{ opacity: 0 }}
                        animate={{ opacity: 1 }}
                        className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4"
                    >
                        {tasks.data.map((task) => (
                            <PurchasingTaskCard
                                key={task.id}
                                task={task}
                                onClaim={onClaim}
                                onStart={onStart}
                            />
                        ))}
                    </motion.div>
                )}
            </AnimatePresence>

            {/* Pagination */}
            {tasks.last_page > 1 && (
                <div className="mt-6 flex items-center justify-between">
                    <p className="text-sm text-gray-600">
                        Page {tasks.current_page} of {tasks.last_page} ({tasks.total} total)
                    </p>
                    <div className="flex gap-2">
                        {tasks.links.map((link, index) => {
                            if (link.url === null) return null;

                            // Skip prev/next labels, use icons instead
                            if (link.label.includes('Previous') || link.label.includes('Next')) {
                                return null;
                            }

                            return (
                                <Link
                                    key={index}
                                    href={link.url}
                                    className={cn(
                                        "px-3 py-1.5 text-sm rounded border transition-colors",
                                        link.active
                                            ? "bg-indigo-600 text-white border-indigo-600"
                                            : "bg-white text-gray-600 border-gray-300 hover:bg-gray-50"
                                    )}
                                    preserveScroll
                                    preserveState
                                >
                                    {link.label.replace(/&laquo;|&raquo;/g, '').trim()}
                                </Link>
                            );
                        })}
                    </div>
                </div>
            )}
        </div>
    );
}

export default PurchasingTaskList;
