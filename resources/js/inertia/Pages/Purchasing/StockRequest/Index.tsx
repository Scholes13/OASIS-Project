import React, { useState, useEffect } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { motion, AnimatePresence } from 'framer-motion';
import { Search, Plus, Calendar, Loader2, FileText, Eye, Package } from 'lucide-react';
import { PageProps, PaginatedData } from '@/types';
import { Input } from '@/components/ui/input';
import { Select } from '@/components/ui/select';
import { Button } from '@/components/ui/button';
import { TableSkeleton } from '@/components/ui/skeleton';
import { cn } from '@/lib/utils';
import { formatCurrency, formatDate, formatTime } from '@/lib/formatters';

/**
 * Stock Request Index Page
 * 
 * Displays a list of stock requests with filtering and pagination.
 */

interface StockRequest {
    id: number;
    st_number: string;
    purpose: string;
    status: string;
    date_of_request: string;
    created_at: string;
    total_amount: number;
    currency: string;
    department?: { id: number; name: string; code: string };
    user?: { id: number; name: string; email: string };
    items_count?: number;
    approval_progress?: { approved: number; total: number };
}

interface STIndexPageProps extends PageProps {
    stockRequests: PaginatedData<StockRequest>;
    filters: {
        status?: string;
        search?: string;
        date_from?: string;
        date_to?: string;
    };
}

// Status badge configuration
const getStatusConfig = (status: string) => {
    const configs: Record<string, { label: string; className: string }> = {
        draft: { label: 'Draft', className: 'text-gray-600' },
        submitted: { label: 'Submitted', className: 'text-blue-600' },
        in_approval: { label: 'In Approval', className: 'text-blue-600' },
        approved: { label: 'Approved', className: 'text-emerald-600' },
        rejected: { label: 'Rejected', className: 'text-red-600' },
        voided: { label: 'Voided', className: 'text-gray-500' },
    };
    return configs[status] || configs.draft;
};

const statuses = [
    { value: 'draft', label: 'Draft' },
    { value: 'submitted', label: 'Submitted' },
    { value: 'in_approval', label: 'In Approval' },
    { value: 'approved', label: 'Approved' },
    { value: 'rejected', label: 'Rejected' },
    { value: 'voided', label: 'Voided' },
];

export default function Index({
    stockRequests,
    filters,
    currentBusinessUnit
}: STIndexPageProps) {
    // Defensive defaults for stockRequests to prevent undefined errors
    const safeData = stockRequests?.data ?? [];
    const safeMeta = stockRequests?.meta ?? { from: 0, to: 0, total: 0, last_page: 1, links: [] };
    const safeLinks = stockRequests?.links ?? { prev: null, next: null };

    const [search, setSearch] = useState(filters?.search || '');
    const [selectedStatus, setSelectedStatus] = useState(filters?.status || '');
    const [dateFrom, setDateFrom] = useState(filters?.date_from || '');
    const [dateTo, setDateTo] = useState(filters?.date_to || '');
    const [isLoading, setIsLoading] = useState(false);
    const [isInitialLoad, setIsInitialLoad] = useState(true);

    // Mark initial load as complete after component mounts
    useEffect(() => {
        setIsInitialLoad(false);
    }, []);

    // Debounce search to avoid too many requests
    useEffect(() => {
        if (isInitialLoad) return;

        const timer = setTimeout(() => {
            handleFilter();
        }, 300);

        return () => clearTimeout(timer);
    }, [search, selectedStatus, dateFrom, dateTo]);

    const handleFilter = () => {
        const params: Record<string, string> = {};

        if (search) params.search = search;
        if (selectedStatus) params.status = selectedStatus;
        if (dateFrom) params.date_from = dateFrom;
        if (dateTo) params.date_to = dateTo;

        router.get(route('stock-requests.index'), params, {
            preserveState: true,
            preserveScroll: true,
            only: ['stockRequests'],
            onStart: () => setIsLoading(true),
            onFinish: () => setIsLoading(false),
        });
    };

    const handlePageChange = (url: string) => {
        router.get(url, {}, {
            preserveState: true,
            preserveScroll: true,
            only: ['stockRequests'],
            onStart: () => setIsLoading(true),
            onFinish: () => setIsLoading(false),
        });
    };

    const handleRowClick = (st: StockRequest) => {
        router.visit(route('stock-requests.show', { stockRequest: st.id }));
    };

    return (
        <>
            <Head title="My Stock Requests" />

            <div className="w-full px-6 py-6 lg:px-8">
                    {/* Header */}
                    <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <h1 className="text-xl font-semibold text-gray-900">
                                    My Stock Requests
                                </h1>
                                <p className="text-sm text-gray-500 mt-0.5">
                                    {currentBusinessUnit?.name || 'Current Business Unit'}
                                </p>
                            </div>
                            <div className="flex items-center space-x-3">
                                <Link href={route('stock-requests.create')}>
                                    <Button className="bg-primary hover:bg-blue-600">
                                        <Plus className="w-4 h-4 mr-2" />
                                        Create New ST
                                    </Button>
                                </Link>
                            </div>
                        </div>
                    </div>

                    {/* Loading Overlay */}
                    <AnimatePresence>
                        {isLoading && (
                            <motion.div
                                initial={{ opacity: 0 }}
                                animate={{ opacity: 1 }}
                                exit={{ opacity: 0 }}
                                className="fixed inset-0 bg-white/80 backdrop-blur-sm z-50 flex items-center justify-center"
                            >
                                <motion.div
                                    initial={{ scale: 0.9, opacity: 0 }}
                                    animate={{ scale: 1, opacity: 1 }}
                                    exit={{ scale: 0.9, opacity: 0 }}
                                    className="flex flex-col items-center space-y-4"
                                >
                                    <Loader2 className="w-12 h-12 text-primary animate-spin" />
                                    <div className="text-center">
                                        <h3 className="text-lg font-semibold text-gray-700 mb-1">Loading Data</h3>
                                        <p className="text-sm text-gray-400">Please wait...</p>
                                    </div>
                                </motion.div>
                            </motion.div>
                        )}
                    </AnimatePresence>

                    {/* Filters */}
                    <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
                        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                            {/* Search */}
                            <div className="md:col-span-2">
                                <div className="relative">
                                    <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" />
                                    <Input
                                        type="text"
                                        value={search}
                                        onChange={(e) => setSearch(e.target.value)}
                                        placeholder="Search ST number or purpose..."
                                        className="pl-10"
                                    />
                                </div>
                            </div>

                            {/* Status Filter */}
                            <div>
                                <Select
                                    value={selectedStatus}
                                    onChange={(value) => setSelectedStatus(value as string)}
                                    options={[
                                        { value: '', label: 'All Status' },
                                        ...statuses
                                    ]}
                                    placeholder="Filter by status"
                                />
                            </div>

                            {/* Date Range Placeholder */}
                            <div className="flex items-center space-x-2">
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    disabled
                                    title="Date range filter (coming soon)"
                                >
                                    <Calendar className="w-4 h-4 mr-2" />
                                    Date Range
                                </Button>
                            </div>
                        </div>
                    </div>

                    {/* Table */}
                    <div className={cn("bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden transition-opacity duration-200", isLoading && "opacity-50")}>
                        {isInitialLoad ? (
                            <div className="p-6">
                                <TableSkeleton rows={10} columns={7} />
                            </div>
                        ) : safeData.length > 0 ? (
                            <>
                                <div className="overflow-x-auto">
                                    <table className="min-w-full">
                                        <thead>
                                            <tr className="border-b border-gray-100">
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">DEPT</th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">NO. ST</th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">PURPOSE</th>
                                                <th className="px-6 py-3 text-right text-xs font-medium text-gray-400 uppercase tracking-wider">AMOUNT</th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">DATE</th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">STATUS</th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">ACTIONS</th>
                                            </tr>
                                        </thead>
                                        <tbody className="bg-white divide-y divide-gray-50">
                                            {safeData.map((st, index) => {
                                                const statusConfig = getStatusConfig(st.status);
                                                const showProgress = ['submitted', 'in_approval', 'approved', 'rejected'].includes(st.status);

                                                return (
                                                    <motion.tr
                                                        key={st.id}
                                                        initial={{ opacity: 0, y: 20 }}
                                                        animate={{ opacity: 1, y: 0 }}
                                                        transition={{ delay: index * 0.05 }}
                                                        className="hover:bg-gray-50/50 transition-colors cursor-pointer"
                                                        onClick={() => handleRowClick(st)}
                                                    >
                                                        <td className="px-6 py-4">
                                                            <span className="text-sm text-gray-600">{st.department?.code || 'N/A'}</span>
                                                        </td>
                                                        <td className="px-6 py-4">
                                                            <div className="text-sm font-mono font-medium text-gray-700">{st.st_number}</div>
                                                            <div className="text-xs text-gray-400 mt-0.5">{st.items_count || 0} items</div>
                                                        </td>
                                                        <td className="px-6 py-4">
                                                            <span className="text-sm text-gray-600 max-w-md">
                                                                {st.purpose.length > 60 ? `${st.purpose.substring(0, 60)}...` : st.purpose}
                                                            </span>
                                                        </td>
                                                        <td className="px-6 py-4 text-right">
                                                            <span className="text-sm text-gray-700">
                                                                {st.currency} {formatCurrency(st.total_amount)}
                                                            </span>
                                                        </td>
                                                        <td className="px-6 py-4">
                                                            <div className="text-sm text-gray-600">{formatDate(st.date_of_request)}</div>
                                                            <div className="text-xs text-gray-400 mt-0.5">{formatTime(st.created_at)}</div>
                                                        </td>
                                                        <td className="px-6 py-4">
                                                            <span className={`text-sm ${statusConfig.className}`}>{statusConfig.label}</span>
                                                            {showProgress && st.approval_progress && (
                                                                <div className="text-xs text-gray-400 mt-0.5">
                                                                    {st.approval_progress.approved}/{st.approval_progress.total}
                                                                </div>
                                                            )}
                                                        </td>
                                                        <td className="px-6 py-4">
                                                            <Link
                                                                href={`/stock-requests/${st.id}`}
                                                                onClick={(e: React.MouseEvent) => e.stopPropagation()}
                                                                className="inline-flex items-center px-3 py-1.5 text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-md transition-colors"
                                                            >
                                                                <Eye className="w-4 h-4 mr-1" />
                                                                View
                                                            </Link>
                                                        </td>
                                                    </motion.tr>
                                                );
                                            })}
                                        </tbody>
                                    </table>
                                </div>

                                {/* Pagination */}
                                <div className="p-6 border-t border-gray-100">
                                    <div className="flex items-center justify-between">
                                        <p className="text-sm text-gray-400">
                                            Showing {safeMeta.from || 0} to {safeMeta.to || 0} of {safeMeta.total} results
                                        </p>

                                        {safeMeta.last_page > 1 && (
                                            <nav className="flex items-center gap-1">
                                                {safeLinks.prev ? (
                                                    <button
                                                        onClick={() => handlePageChange(safeLinks.prev!)}
                                                        className="px-3 py-2 text-sm text-gray-400 hover:text-gray-600 transition-colors"
                                                    >
                                                        ← Previous
                                                    </button>
                                                ) : (
                                                    <span className="px-3 py-2 text-sm text-gray-300 cursor-not-allowed select-none">
                                                        ← Previous
                                                    </span>
                                                )}

                                                <div className="flex items-center gap-1 mx-2">
                                                    {safeMeta.links
                                                        .filter(link => link.label !== '&laquo; Previous' && link.label !== 'Next &raquo;')
                                                        .map((link, index) => (
                                                            <button
                                                                key={index}
                                                                onClick={() => link.url && handlePageChange(link.url)}
                                                                disabled={!link.url}
                                                                className={cn(
                                                                    "w-8 h-8 flex items-center justify-center text-sm rounded-md transition-colors",
                                                                    link.active
                                                                        ? "font-medium text-primary bg-primary"
                                                                        : "text-gray-500 hover:bg-gray-100"
                                                                )}
                                                            >
                                                                {link.label}
                                                            </button>
                                                        ))}
                                                </div>

                                                {safeLinks.next ? (
                                                    <button
                                                        onClick={() => handlePageChange(safeLinks.next!)}
                                                        className="px-3 py-2 text-sm text-gray-400 hover:text-gray-600 transition-colors"
                                                    >
                                                        Next →
                                                    </button>
                                                ) : (
                                                    <span className="px-3 py-2 text-sm text-gray-300 cursor-not-allowed select-none">
                                                        Next →
                                                    </span>
                                                )}
                                            </nav>
                                        )}
                                    </div>
                                </div>
                            </>
                        ) : (
                            <motion.div
                                initial={{ opacity: 0, y: 20 }}
                                animate={{ opacity: 1, y: 0 }}
                            >
                                <div className="text-center py-16">
                                    <div className="w-16 h-16 mx-auto bg-gray-50 rounded-full flex items-center justify-center mb-4">
                                        <Package className="w-8 h-8 text-gray-300" />
                                    </div>
                                    <h3 className="text-base font-medium text-gray-600 mb-2">
                                        No Stock Request History
                                    </h3>
                                    <p className="text-sm text-gray-400 mb-6">
                                        You haven't created any stock requests yet.
                                    </p>
                                    <Link href={route('stock-requests.create')}>
                                        <Button>
                                            <Plus className="w-4 h-4 mr-2" />
                                            Create Your First ST
                                        </Button>
                                    </Link>
                                </div>
                            </motion.div>
                        )}
                    </div>
            </div>
        </>
    );
}
