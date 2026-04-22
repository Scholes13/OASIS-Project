import React, { useState, useEffect } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { motion, AnimatePresence } from 'framer-motion';
import { Search, Plus, Calendar, Loader2, FileText } from 'lucide-react';
import PurchaseRequestTable from '@/components/purchasing/PurchaseRequestTable';
import { PageProps, PaginatedData } from '@/types';
import { PurchaseRequest } from '@/types/purchasing';
import { Input } from '@/components/ui/input';
import { Select } from '@/components/ui/select';
import { Button } from '@/components/ui/button';
import { TableSkeleton } from '@/components/ui/skeleton';
import { cn } from '@/lib/utils';

/**
 * Purchase Request Index Page
 * 
 * Displays a list of purchase requests with filtering and pagination.
 * Matches the Livewire UI design with improved React state management.
 * 
 * Features:
 * - Real-time search filtering without page reload
 * - Status filtering
 * - Date range filtering
 * - Pagination controls
 * - Loading states with smooth transitions
 * - Business unit context awareness
 * 
 * Requirements: 6.1, 6.3, 6.4, 6.6
 */

interface PRIndexPageProps extends PageProps {
    purchaseRequests: PaginatedData<PurchaseRequest>;
    filters: {
        status?: string;
        search?: string;
        date_from?: string;
        date_to?: string;
    };
    statuses: Array<{ value: string; label: string }>;
}

export default function Index({
    purchaseRequests,
    filters,
    statuses,
    currentBusinessUnit
}: PRIndexPageProps) {
    // Defensive defaults for purchaseRequests to prevent undefined errors
    // Inertia v2 serializes Laravel paginator at root level (not nested under meta)
    const safeData = purchaseRequests?.data ?? [];
    const safeMeta = purchaseRequests?.meta ?? {
        from: (purchaseRequests as any)?.from ?? 0,
        to: (purchaseRequests as any)?.to ?? 0,
        total: (purchaseRequests as any)?.total ?? 0,
        last_page: (purchaseRequests as any)?.last_page ?? 1,
        links: (purchaseRequests as any)?.links ?? [],
    };
    const safeLinks = purchaseRequests?.links ?? {
        prev: (purchaseRequests as any)?.prev_page_url ?? null,
        next: (purchaseRequests as any)?.next_page_url ?? null,
    };

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
        // Skip filter on initial load
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

        router.get(route('purchase-requests.index'), params, {
            preserveState: true,
            preserveScroll: true,
            only: ['purchaseRequests'],
            onStart: () => setIsLoading(true),
            onFinish: () => setIsLoading(false),
        });
    };

    const handlePageChange = (url: string) => {
        router.get(url, {}, {
            preserveState: true,
            preserveScroll: true,
            only: ['purchaseRequests'],
            onStart: () => setIsLoading(true),
            onFinish: () => setIsLoading(false),
        });
    };

    const handleRowClick = (pr: PurchaseRequest) => {
        router.visit(route('purchase-requests.show', { purchaseRequest: pr.id }));
    };

    return (
        <>
            <Head title="My Purchase Requests" />

            <div className="w-full px-6 py-6 lg:px-8">
                    {/* Header */}
                    <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <h1 className="text-xl font-semibold text-gray-900">
                                    My Purchase Requests
                                </h1>
                                <p className="text-sm text-gray-500 mt-0.5">
                                    {currentBusinessUnit?.name || 'Current Business Unit'}
                                </p>
                            </div>
                            <div className="flex items-center space-x-3">
                                <Link href={route('purchase-requests.create')}>
                                    <Button className="bg-primary hover:bg-blue-600">
                                        <Plus className="w-4 h-4 mr-2" />
                                        Create New PR
                                    </Button>
                                </Link>
                            </div>
                        </div>
                    </div>

                    {/* Loading Overlay with Framer Motion */}
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
                                        placeholder="Search PR number or description..."
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

                            {/* Date Range - Placeholder for future implementation */}
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
                            /* Skeleton Loader for Initial Load */
                            <div className="p-6">
                                <TableSkeleton rows={10} columns={7} />
                            </div>
                        ) : safeData.length > 0 ? (
                            <>
                                <PurchaseRequestTable
                                    purchaseRequests={safeData}
                                    onRowClick={handleRowClick}
                                />

                                {/* Pagination */}
                                <div className="p-6 border-t border-gray-100">
                                    <div className="flex items-center justify-between">
                                        <p className="text-sm text-gray-400">
                                            Showing {safeMeta.from || 0} to {safeMeta.to || 0} of {safeMeta.total} results
                                        </p>

                                        {safeMeta.last_page > 1 && (
                                            <nav className="flex items-center gap-1">
                                                {/* Previous Button */}
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

                                                {/* Page Numbers */}
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
                                                                        ? "font-medium text-white bg-primary"
                                                                        : "text-gray-500 hover:bg-gray-100"
                                                                )}
                                                            >
                                                                {link.label}
                                                            </button>
                                                        ))}
                                                </div>

                                                {/* Next Button */}
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
                            /* Empty State with Framer Motion */
                            <motion.div
                                initial={{ opacity: 0, y: 20 }}
                                animate={{ opacity: 1, y: 0 }}
                            >
                                <div className="text-center py-16">
                                    <div className="w-16 h-16 mx-auto bg-gray-50 rounded-full flex items-center justify-center mb-4">
                                        <FileText className="w-8 h-8 text-gray-300" />
                                    </div>
                                    <h3 className="text-base font-medium text-gray-600 mb-2">
                                        No Purchase Request History
                                    </h3>
                                    <p className="text-sm text-gray-400 mb-6">
                                        You haven't created any purchase requests yet.
                                    </p>
                                    <Link href={route('purchase-requests.create')}>
                                        <Button>
                                            <Plus className="w-4 h-4 mr-2" />
                                            Create Your First PR
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
