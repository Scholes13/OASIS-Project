import { Head, Link } from '@inertiajs/react';
import { motion } from 'framer-motion';
import { useState, useEffect } from 'react';
import {
    ShoppingCart,
    Package,
    ClipboardCheck,
    Calendar,
    ClipboardList,
    TrendingUp,
    Clock,
    CheckCircle,
    AlertCircle,
} from 'lucide-react';
import { StatsCardSkeleton, CardSkeleton } from '@/components/ui/skeleton';

interface QuickAction {
    title: string;
    description: string;
    icon: string;
    url: string;
    color: string;
    badge?: number;
}

interface Activity {
    type: string;
    title: string;
    status: string;
    date: string;
    url: string;
}

interface DashboardProps {
    stats: {
        my_purchase_requests: number;
        my_stock_requests: number;
        pending_approvals: number;
        my_tasks: number;
    };
    recentActivities: Activity[];
    pendingApprovalsCount: number;
    quickActions: QuickAction[];
}

const iconMap: Record<string, any> = {
    'shopping-cart': ShoppingCart,
    'package': Package,
    'clipboard-check': ClipboardCheck,
    'calendar': Calendar,
    'clipboard-list': ClipboardList,
};

const colorMap: Record<string, string> = {
    indigo: 'bg-indigo-100 text-indigo-600',
    blue: 'bg-blue-100 text-blue-600',
    amber: 'bg-amber-100 text-amber-600',
    emerald: 'bg-emerald-100 text-emerald-600',
    purple: 'bg-purple-100 text-purple-600',
};

const statusColorMap: Record<string, string> = {
    draft: 'bg-gray-100 text-gray-700',
    submitted: 'bg-blue-100 text-blue-700',
    in_approval: 'bg-amber-100 text-amber-700',
    approved: 'bg-emerald-100 text-emerald-700',
    rejected: 'bg-red-100 text-red-700',
    voided: 'bg-gray-100 text-gray-500',
};

export default function Dashboard({ stats, recentActivities, pendingApprovalsCount, quickActions }: DashboardProps) {
    const [isInitialLoad, setIsInitialLoad] = useState(true);

    // Mark initial load as complete after component mounts
    useEffect(() => {
        const timer = setTimeout(() => {
            setIsInitialLoad(false);
        }, 100);
        return () => clearTimeout(timer);
    }, []);

    return (
        <>
            <Head title="Dashboard" />
            <div className="w-full h-full bg-gray-50 p-6">
                    {/* Header */}
                    <div className="mb-6">
                        <h1 className="text-2xl font-bold text-gray-900">Dashboard</h1>
                        <p className="mt-1 text-sm text-gray-600">
                            Welcome back! Here's what's happening today.
                        </p>
                    </div>

                    {isInitialLoad ? (
                        /* Skeleton Loaders for Initial Load */
                        <>
                            {/* Stats Grid Skeleton */}
                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                                {Array.from({ length: 4 }).map((_, i) => (
                                    <StatsCardSkeleton key={i} />
                                ))}
                            </div>

                            {/* Quick Actions Skeleton */}
                            <div className="mb-6">
                                <div className="h-6 w-32 bg-gray-200 rounded mb-4 animate-pulse" />
                                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                    {Array.from({ length: 6 }).map((_, i) => (
                                        <CardSkeleton key={i} />
                                    ))}
                                </div>
                            </div>

                            {/* Recent Activities Skeleton */}
                            <CardSkeleton className="h-96" />
                        </>
                    ) : (
                        /* Actual Content */
                        <>
                            {/* Stats Grid */}
                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                        {/* My Purchase Requests */}
                        <motion.div
                            initial={{ opacity: 0, y: 20 }}
                            animate={{ opacity: 1, y: 0 }}
                            transition={{ duration: 0.3 }}
                            className="bg-white rounded-xl border border-gray-100 p-6"
                        >
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-medium text-gray-600">My PRs</p>
                                    <p className="text-3xl font-bold text-gray-900 mt-2">{stats.my_purchase_requests}</p>
                                </div>
                                <div className="p-3 bg-indigo-100 rounded-lg">
                                    <ShoppingCart className="w-6 h-6 text-indigo-600" />
                                </div>
                            </div>
                        </motion.div>

                        {/* My Stock Requests */}
                        <motion.div
                            initial={{ opacity: 0, y: 20 }}
                            animate={{ opacity: 1, y: 0 }}
                            transition={{ duration: 0.3, delay: 0.1 }}
                            className="bg-white rounded-xl border border-gray-100 p-6"
                        >
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-medium text-gray-600">My STs</p>
                                    <p className="text-3xl font-bold text-gray-900 mt-2">{stats.my_stock_requests}</p>
                                </div>
                                <div className="p-3 bg-blue-100 rounded-lg">
                                    <Package className="w-6 h-6 text-blue-600" />
                                </div>
                            </div>
                        </motion.div>

                        {/* Pending Approvals */}
                        <motion.div
                            initial={{ opacity: 0, y: 20 }}
                            animate={{ opacity: 1, y: 0 }}
                            transition={{ duration: 0.3, delay: 0.2 }}
                            className="bg-white rounded-xl border border-gray-100 p-6"
                        >
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-medium text-gray-600">Pending Approvals</p>
                                    <p className="text-3xl font-bold text-gray-900 mt-2">{stats.pending_approvals}</p>
                                </div>
                                <div className="p-3 bg-amber-100 rounded-lg">
                                    <ClipboardCheck className="w-6 h-6 text-amber-600" />
                                </div>
                            </div>
                        </motion.div>

                        {/* My Tasks */}
                        <motion.div
                            initial={{ opacity: 0, y: 20 }}
                            animate={{ opacity: 1, y: 0 }}
                            transition={{ duration: 0.3, delay: 0.3 }}
                            className="bg-white rounded-xl border border-gray-100 p-6"
                        >
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-medium text-gray-600">My Tasks</p>
                                    <p className="text-3xl font-bold text-gray-900 mt-2">{stats.my_tasks}</p>
                                </div>
                                <div className="p-3 bg-emerald-100 rounded-lg">
                                    <Calendar className="w-6 h-6 text-emerald-600" />
                                </div>
                            </div>
                        </motion.div>
                    </div>

                    {/* Quick Actions */}
                    <div className="mb-6">
                        <h2 className="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h2>
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            {quickActions.map((action, index) => {
                                const Icon = iconMap[action.icon] || ShoppingCart;
                                const colorClass = colorMap[action.color] || colorMap.indigo;

                                return (
                                    <motion.div
                                        key={action.title}
                                        initial={{ opacity: 0, y: 20 }}
                                        animate={{ opacity: 1, y: 0 }}
                                        transition={{ duration: 0.3, delay: 0.4 + index * 0.1 }}
                                    >
                                        <Link
                                            href={action.url}
                                            className="block bg-white rounded-xl border border-gray-100 p-6 hover:shadow-md transition-shadow"
                                        >
                                            <div className="flex items-start justify-between">
                                                <div className="flex-1">
                                                    <div className="flex items-center">
                                                        <div className={`p-2 rounded-lg ${colorClass}`}>
                                                            <Icon className="w-5 h-5" />
                                                        </div>
                                                        {action.badge && (
                                                            <span className="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                                {action.badge}
                                                            </span>
                                                        )}
                                                    </div>
                                                    <h3 className="mt-3 text-base font-semibold text-gray-900">
                                                        {action.title}
                                                    </h3>
                                                    <p className="mt-1 text-sm text-gray-600">{action.description}</p>
                                                </div>
                                            </div>
                                        </Link>
                                    </motion.div>
                                );
                            })}
                        </div>
                    </div>

                    {/* Recent Activities */}
                    <motion.div
                        initial={{ opacity: 0, y: 20 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{ duration: 0.3, delay: 0.8 }}
                        className="bg-white rounded-xl border border-gray-100 overflow-hidden"
                    >
                        <div className="px-6 py-4 border-b border-gray-100">
                            <h2 className="text-lg font-semibold text-gray-900">Recent Activities</h2>
                        </div>
                        <div className="divide-y divide-gray-100">
                            {recentActivities.length === 0 ? (
                                <div className="px-6 py-8 text-center text-gray-500">
                                    No recent activities
                                </div>
                            ) : (
                                recentActivities.map((activity, index) => (
                                    <Link
                                        key={index}
                                        href={activity.url}
                                        className="block px-6 py-4 hover:bg-gray-50 transition-colors"
                                    >
                                        <div className="flex items-center justify-between">
                                            <div className="flex-1">
                                                <div className="flex items-center">
                                                    <span className="text-sm font-medium text-gray-900">
                                                        {activity.title}
                                                    </span>
                                                    <span
                                                        className={`ml-3 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${
                                                            statusColorMap[activity.status] || statusColorMap.draft
                                                        }`}
                                                    >
                                                        {activity.status.replace('_', ' ')}
                                                    </span>
                                                </div>
                                                <p className="mt-1 text-xs text-gray-500">
                                                    {activity.type === 'purchase_request' ? 'Purchase Request' : 'Stock Request'} • {activity.date}
                                                </p>
                                            </div>
                                        </div>
                                    </Link>
                                ))
                            )}
                        </div>
                    </motion.div>
                        </>
                    )}
            </div>
        </>
    );
}
