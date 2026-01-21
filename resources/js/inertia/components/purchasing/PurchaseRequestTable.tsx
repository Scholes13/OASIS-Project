import React from 'react';
import { Link } from '@inertiajs/react';
import { motion } from 'framer-motion';
import { Eye } from 'lucide-react';
import { PurchaseRequest } from '@/types/purchasing';
import { formatCurrency, formatDate, formatTime } from '@/lib/formatters';
import { usePrefetch } from '@/hooks/usePrefetch';

/**
 * PurchaseRequestTable Component
 * 
 * Displays a table of purchase requests with status badges, amounts, and actions.
 * Matches the Livewire UI design with improved React state management.
 * 
 * Features:
 * - Status badges with color coding (draft: gray, submitted: blue, approved: green, rejected: red, voided: gray)
 * - Clickable rows for navigation
 * - Responsive table with horizontal scroll on mobile
 * - Approval progress display for submitted/in_approval/approved/rejected PRs
 * - Currency formatting with thousand separators
 * - Date and time formatting
 * 
 * @example
 * ```tsx
 * <PurchaseRequestTable 
 *   purchaseRequests={purchaseRequests.data}
 *   onRowClick={(pr) => router.visit(`/purchasing/purchase-requests/${pr.id}`)}
 * />
 * ```
 */

interface PurchaseRequestTableProps {
    purchaseRequests: PurchaseRequest[];
    onRowClick?: (pr: PurchaseRequest) => void;
}

// Status badge configuration matching Livewire UI
const getStatusConfig = (status: string) => {
    const configs: Record<string, { label: string; className: string }> = {
        draft: {
            label: 'Draft',
            className: 'text-gray-600',
        },
        submitted: {
            label: 'Submitted',
            className: 'text-blue-600',
        },
        in_approval: {
            label: 'In Approval',
            className: 'text-blue-600',
        },
        approved: {
            label: 'Approved',
            className: 'text-emerald-600',
        },
        rejected: {
            label: 'Rejected',
            className: 'text-red-600',
        },
        voided: {
            label: 'Voided',
            className: 'text-gray-500',
        },
    };

    return configs[status] || configs.draft;
};

export default function PurchaseRequestTable({
    purchaseRequests,
    onRowClick
}: PurchaseRequestTableProps) {
    // Initialize prefetch hook with 100ms delay
    // This will prefetch PR detail pages when user hovers over links
    const { onMouseEnter: prefetchOnHover, onMouseLeave: cancelPrefetch } = usePrefetch({
        delay: 100,
        // Only prefetch the purchase request data, not the entire page
        only: ['purchaseRequest', 'items', 'approvals']
    });

    const handleRowClick = (pr: PurchaseRequest) => {
        if (onRowClick) {
            onRowClick(pr);
        }
    };

    return (
        <div className="bg-white overflow-hidden">
            <div className="overflow-x-auto">
                <table className="min-w-full">
                    <thead>
                        <tr className="border-b border-gray-100">
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                                DEPT
                            </th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                                NO. PR
                            </th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                                USED FOR
                            </th>
                            <th className="px-6 py-3 text-right text-xs font-medium text-gray-400 uppercase tracking-wider">
                                AMOUNT
                            </th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                                DATE
                            </th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                                STATUS
                            </th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                                ACTIONS
                            </th>
                        </tr>
                    </thead>
                    <tbody className="bg-white divide-y divide-gray-50">
                        {purchaseRequests.map((pr, index) => {
                            const statusConfig = getStatusConfig(pr.status);
                            const showProgress = ['submitted', 'in_approval', 'approved', 'rejected'].includes(pr.status);

                            return (
                                <motion.tr
                                    key={pr.id}
                                    initial={{ opacity: 0, y: 20 }}
                                    animate={{ opacity: 1, y: 0 }}
                                    transition={{ delay: index * 0.05 }}
                                    className="hover:bg-gray-50/50 transition-colors cursor-pointer"
                                    onClick={() => handleRowClick(pr)}
                                >
                                    <td className="px-6 py-4">
                                        <span className="text-sm text-gray-600">
                                            {pr.department?.code || 'N/A'}
                                        </span>
                                    </td>
                                    <td className="px-6 py-4">
                                        <div className="text-sm font-mono font-medium text-gray-700">
                                            {pr.pr_number}
                                        </div>
                                        <div className="text-xs text-gray-400 mt-0.5">
                                            {pr.items?.length || 0} items
                                        </div>
                                    </td>
                                    <td className="px-6 py-4">
                                        <span className="text-sm text-gray-600 max-w-md">
                                            {pr.used_for.length > 60
                                                ? `${pr.used_for.substring(0, 60)}...`
                                                : pr.used_for
                                            }
                                        </span>
                                    </td>
                                    <td className="px-6 py-4 text-right">
                                        <span className="text-sm text-gray-700">
                                            {pr.currency} {formatCurrency(pr.total_amount)}
                                        </span>
                                    </td>
                                    <td className="px-6 py-4">
                                        <div className="text-sm text-gray-600">
                                            {formatDate(pr.date_of_request)}
                                        </div>
                                        <div className="text-xs text-gray-400 mt-0.5">
                                            {formatTime(pr.created_at)}
                                        </div>
                                    </td>
                                    <td className="px-6 py-4">
                                        <span className={`text-sm ${statusConfig.className}`}>
                                            {statusConfig.label}
                                        </span>
                                        {showProgress && pr.approval_progress && (
                                            <div className="text-xs text-gray-400 mt-0.5">
                                                {pr.approval_progress.approved}/{pr.approval_progress.total}
                                            </div>
                                        )}
                                    </td>
                                    <td className="px-6 py-4">
                                        <Link
                                            href={`/purchase-requests/${pr.id}`}
                                            onMouseEnter={prefetchOnHover}
                                            onMouseLeave={cancelPrefetch}
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
        </div>
    );
}
