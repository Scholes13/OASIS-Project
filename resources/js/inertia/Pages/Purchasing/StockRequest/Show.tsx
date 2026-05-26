import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { motion, AnimatePresence } from 'framer-motion';
import { 
    ArrowLeft, 
    Edit,
    Download, 
    Ban,
    RotateCcw,
    Shield,
    Check,
    X,
    FileText,
    Eye,
    AlertTriangle,
    Loader2,
    Send
} from 'lucide-react';
import { PageProps } from '@/types';
import { Button } from '@/components/ui/button';
import { formatDate } from '@/lib/formatters';
import { APPROVAL_BADGE_COLORS, ST_STATUS_CONFIG } from '@/lib/purchasingConstants';
import { toast } from 'sonner';

interface StockItem {
    id: number;
    item_name: string;
    item_description: string | null;
    quantity: number;
    unit: string;
    image_path: string | null;
}

interface StockApproval {
    id: number;
    approver_id: number;
    step_order: number;
    status: 'pending' | 'approved' | 'rejected';
    notes: string | null;
    responded_at: string | null;
    approver: {
        id: number;
        name: string;
        email: string;
    };
}

interface StockRequest {
    id: number;
    st_number: string;
    business_unit_id: number;
    department_id: number;
    user_id: number;
    purpose: string;
    date_of_request: string;
    expected_date: string | null;
    status: string;
    submitted_at: string | null;
    approved_at: string | null;
    rejected_at: string | null;
    voided_at: string | null;
    offline_approved_at: string | null;
    offline_approval_document_path: string | null;
    offline_approval_document_name: string | null;
    created_at: string;
    updated_at: string;
    user: { id: number; name: string; email: string };
    department: { id: number; name: string; code: string };
    business_unit: { id: number; name: string; code: string };
    items?: StockItem[];
    approvals?: StockApproval[];
    approval_progress?: { approved: number; total: number };
    can?: STPermissions;
}


interface STPermissions {
    approve?: boolean;
    reject?: boolean;
    edit: boolean;
    delete: boolean;
    void: boolean;
    resubmit: boolean;
    resendApprovalEmail?: boolean;
    downloadPdf: boolean;
    markOfflineApproved: boolean;
    offlineApprovalDocument?: boolean;
}

interface ApprovalContext {
    approvalId: number;
    canApprove: boolean;
    approvalStatus: 'pending' | 'approved' | 'rejected' | 'skipped';
}

interface ShowPageProps extends PageProps {
    stockRequest: StockRequest;
    can?: STPermissions;
    approvalContext?: ApprovalContext;
}

export default function Show({ stockRequest, can, approvalContext }: ShowPageProps) {
    const [showVoidModal, setShowVoidModal] = useState(false);
    const [showOfflineModal, setShowOfflineModal] = useState(false);
    const [voidReason, setVoidReason] = useState('');
    const [offlineNotes, setOfflineNotes] = useState('');
    const [offlineDocument, setOfflineDocument] = useState<File | null>(null);
    const [approvalNotes, setApprovalNotes] = useState('');
    const [isDecisionSubmitting, setIsDecisionSubmitting] = useState(false);
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [isResendingEmail, setIsResendingEmail] = useState(false);

    const currentStatus = ST_STATUS_CONFIG[stockRequest.status as keyof typeof ST_STATUS_CONFIG] || ST_STATUS_CONFIG.draft;
    const StatusIcon = currentStatus.icon || Edit;
    const permissions = can || stockRequest.can || {} as STPermissions;
    const isApprovalView = Boolean(approvalContext?.approvalId);

    // Handle void action
    const handleVoid = (e: React.FormEvent) => {
        e.preventDefault();
        if (!voidReason.trim()) {
            toast.error('Please provide a reason for voiding');
            return;
        }
        setIsSubmitting(true);
        router.post(
            route('stock-requests.void', { stockRequest: stockRequest.id }),
            { void_reason: voidReason },
            {
                onSuccess: () => {
                    toast.success('Stock request voided successfully');
                    setShowVoidModal(false);
                },
                onError: () => toast.error('Failed to void stock request'),
                onFinish: () => setIsSubmitting(false),
            }
        );
    };

    // Handle offline approval
    const handleOfflineApproval = (e: React.FormEvent) => {
        e.preventDefault();
        if (!offlineDocument) {
            toast.error('Please upload approval document');
            return;
        }
        setIsSubmitting(true);
        const formData = new FormData();
        formData.append('offline_approval_document', offlineDocument);
        if (offlineNotes.trim()) formData.append('notes', offlineNotes);

        router.post(
            route('stock-requests.mark-offline-approved', { stockRequest: stockRequest.id }),
            formData as any,
            {
                onSuccess: () => {
                    toast.success('Stock request marked as offline approved');
                    setShowOfflineModal(false);
                },
                onError: () => toast.error('Failed to mark as offline approved'),
                onFinish: () => setIsSubmitting(false),
            }
        );
    };

    // Handle resubmit
    const handleResubmit = () => {
        if (!window.confirm('Resubmitting will reset the approval workflow. All previous approval decisions will be cleared. Continue?')) {
            return;
        }
        router.post(
            route('stock-requests.resubmit', { stockRequest: stockRequest.id }),
            {},
            {
                onSuccess: () => toast.success('Stock request resubmitted successfully'),
                onError: () => toast.error('Failed to resubmit stock request'),
            }
        );
    };

    const handleResendApprovalEmail = () => {
        setIsResendingEmail(true);

        router.post(
            route('stock-requests.resend-approval-email', { stockRequest: stockRequest.id }),
            {},
            {
                onSuccess: () => {
                    toast.success('Approval email resent to current approver');
                },
                onError: () => {
                    toast.error('Failed to resend approval email');
                },
                onFinish: () => setIsResendingEmail(false),
            }
        );
    };

    const handleApprovalDecision = (action: 'approve' | 'reject') => {
        if (!approvalContext?.approvalId) {
            return;
        }

        setIsDecisionSubmitting(true);

        router.post(
            route('stock-approvals.process', { approval: approvalContext.approvalId }),
            {
                action,
                notes: approvalNotes,
            },
            {
                onSuccess: () => {
                    toast.success(action === 'approve' ? 'Stock request approved successfully' : 'Stock request rejected');
                },
                onError: () => {
                    toast.error(action === 'approve' ? 'Failed to approve stock request' : 'Failed to reject stock request');
                },
                onFinish: () => {
                    setIsDecisionSubmitting(false);
                },
            }
        );
    };

    const getOfflineApprovalDocumentUrl = () => {
        const ziggy = route as unknown as {
            (...args: any[]): any;
        };
        const routeName = 'stock-requests.offline-approval-document';
        const fallbackPath = `/stock-requests/${stockRequest.id}/offline-approval-document`;

        try {
            const routeList = ziggy();
            if (routeList?.has?.(routeName)) {
                return ziggy(routeName, {
                    stockRequest: stockRequest.id,
                });
            }
        } catch {
            // Fall back to the authenticated application path if Ziggy route metadata is unavailable.
        }

        return fallbackPath;
    };


    return (
        <>
            <Head title={`ST ${stockRequest.st_number}`} />

            <div className="bg-white">
                {/* Header */}
                <div className="border-b border-gray-200 px-6 py-4">
                    <div className="flex items-center justify-between">
                        <div className="flex items-center space-x-4">
                            <Link
                                href={isApprovalView ? route('stock-approvals.index') : route('stock-requests.index')}
                                className="inline-flex items-center text-gray-500 hover:text-gray-700 transition-colors"
                            >
                                <ArrowLeft className="w-5 h-5" />
                            </Link>
                            <div>
                                <div className="flex items-center space-x-3">
                                    <h1 className="text-xl font-semibold text-gray-900">
                                        {stockRequest.st_number}
                                    </h1>
                                    <span className={`inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium ${currentStatus.bg} ${currentStatus.text}`}>
                                        <StatusIcon className="w-3.5 h-3.5 mr-1" />
                                        {currentStatus.label}
                                    </span>
                                    {stockRequest.offline_approved_at && (
                                        <span className="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-700">
                                            <Shield className="w-3.5 h-3.5 mr-1" />
                                            Offline Approved
                                        </span>
                                    )}
                                </div>
                                <p className="text-sm text-gray-500 mt-0.5">
                                    {stockRequest.business_unit?.name || 'N/A'} • {stockRequest.department?.name || 'N/A'}
                                </p>
                            </div>
                        </div>

                        {/* Action Buttons */}
                        <div className="flex items-center space-x-2">
                            {permissions?.edit && (
                                <Link
                                    href={route('stock-requests.edit', { stockRequest: stockRequest.id })}
                                    className="inline-flex items-center px-3 py-1.5 text-sm text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-md transition-colors"
                                >
                                    <Edit className="w-4 h-4 mr-1.5" />
                                    Edit
                                </Link>
                            )}

                            {permissions?.resubmit && (
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    onClick={handleResubmit}
                                    className="text-primary hover:text-primary hover:bg-blue-600"
                                >
                                    <RotateCcw className="w-4 h-4 mr-1.5" />
                                    Resubmit
                                </Button>
                            )}

                            {permissions?.resendApprovalEmail && (
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    onClick={handleResendApprovalEmail}
                                    disabled={isResendingEmail}
                                    className="text-sky-600 hover:text-sky-900 hover:bg-sky-50 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    {isResendingEmail ? (
                                        <Loader2 className="w-4 h-4 mr-1.5 animate-spin" />
                                    ) : (
                                        <Send className="w-4 h-4 mr-1.5" />
                                    )}
                                    Resend Email
                                </Button>
                            )}

                            {permissions?.downloadPdf && (
                                <a
                                    href={route('stock-requests.pdf-public', { stockRequest: stockRequest.id })}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="inline-flex items-center px-3 py-1.5 text-sm text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-md transition-colors"
                                >
                                    <Download className="w-4 h-4 mr-1.5" />
                                    Download PDF
                                </a>
                            )}

                            {permissions?.markOfflineApproved && (
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    onClick={() => setShowOfflineModal(true)}
                                    className="text-purple-600 hover:text-purple-900 hover:bg-purple-50"
                                >
                                    <Shield className="w-4 h-4 mr-1.5" />
                                    Mark Offline Approved
                                </Button>
                            )}

                            {permissions?.void && (
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    onClick={() => setShowVoidModal(true)}
                                    className="text-red-600 hover:text-red-900 hover:bg-red-50"
                                >
                                    <Ban className="w-4 h-4 mr-1.5" />
                                    Void
                                </Button>
                            )}
                        </div>
                    </div>
                </div>


                {/* Alert for Rejected STs */}
                {stockRequest.status === 'rejected' && (
                    <motion.div
                        initial={{ opacity: 0, y: -10 }}
                        animate={{ opacity: 1, y: 0 }}
                        className="mx-6 mt-4 p-4 bg-red-50 border border-red-200 rounded-lg"
                    >
                        <div className="flex items-start">
                            <AlertTriangle className="w-5 h-5 text-red-500 mt-0.5" />
                            <div className="ml-3">
                                <h3 className="text-sm font-medium text-red-800">
                                    This Stock Request was Rejected
                                </h3>
                                <p className="text-sm text-red-700 mt-1">
                                    You can edit this ST and resubmit it for approval using the "Resubmit" button above.
                                </p>
                            </div>
                        </div>
                    </motion.div>
                )}

                {/* Content Grid */}
                <div className="px-6 py-6">
                    <div className="grid grid-cols-1 xl:grid-cols-3 gap-6">
                        {/* Main Content (2/3) */}
                        <div className="xl:col-span-2 space-y-6">
                            {/* Request Details Card */}
                            <motion.div
                                initial={{ opacity: 0, y: 20 }}
                                animate={{ opacity: 1, y: 0 }}
                                transition={{ delay: 0.1 }}
                                className="bg-white rounded-xl border border-gray-100 overflow-hidden"
                            >
                                <div className="px-5 py-4 border-b border-gray-100">
                                    <h3 className="text-base font-semibold text-gray-900">Request Details</h3>
                                </div>
                                <div className="p-6">
                                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-x-8">
                                        <div className="mb-6">
                                            <p className="text-sm font-medium text-gray-500">Requested By</p>
                                            <p className="mt-1 text-sm text-gray-900">{stockRequest.user?.name || 'N/A'}</p>
                                        </div>
                                        <div className="mb-6">
                                            <p className="text-sm font-medium text-gray-500">Department</p>
                                            <p className="mt-1 text-sm text-gray-900">
                                                {stockRequest.department?.name || 'N/A'} ({stockRequest.department?.code || 'N/A'})
                                            </p>
                                        </div>
                                        <div className="mb-6">
                                            <p className="text-sm font-medium text-gray-500">Date of Request</p>
                                            <p className="mt-1 text-sm text-gray-900">{formatDate(stockRequest.date_of_request)}</p>
                                        </div>
                                        <div className="mb-6">
                                            <p className="text-sm font-medium text-gray-500">Expected Date</p>
                                            <p className="mt-1 text-sm text-gray-900">{formatDate(stockRequest.expected_date) || 'Not specified'}</p>
                                        </div>
                                        <div className="sm:col-span-2">
                                            <p className="text-sm font-medium text-gray-500">Purpose</p>
                                            <p className="mt-1 text-sm text-gray-900">{stockRequest.purpose || 'Not specified'}</p>
                                        </div>
                                    </div>
                                </div>
                            </motion.div>


                            {/* Items Table Card */}
                            <motion.div
                                initial={{ opacity: 0, y: 20 }}
                                animate={{ opacity: 1, y: 0 }}
                                transition={{ delay: 0.2 }}
                                className="bg-white rounded-xl border border-gray-100 overflow-hidden"
                            >
                                <div className="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                                    <h3 className="text-base font-semibold text-gray-900">Items</h3>
                                    <span className="text-sm text-gray-500">
                                        {stockRequest.items?.length || 0} {stockRequest.items?.length === 1 ? 'item' : 'items'}
                                    </span>
                                </div>
                                <div className="overflow-x-auto">
                                    <table className="min-w-full divide-y divide-gray-200">
                                        <thead className="bg-gray-50">
                                            <tr>
                                                <th className="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No</th>
                                                <th className="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item</th>
                                                <th className="px-5 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Qty</th>
                                                <th className="px-5 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Image</th>
                                            </tr>
                                        </thead>
                                        <tbody className="bg-white divide-y divide-gray-100">
                                            {stockRequest.items && stockRequest.items.length > 0 ? (
                                                stockRequest.items.map((item, index) => (
                                                    <motion.tr
                                                        key={item.id}
                                                        initial={{ opacity: 0, y: 10 }}
                                                        animate={{ opacity: 1, y: 0 }}
                                                        transition={{ delay: 0.3 + index * 0.05 }}
                                                        className="hover:bg-gray-50 transition-colors"
                                                    >
                                                        <td className="px-5 py-4 whitespace-nowrap text-sm text-gray-500">{index + 1}</td>
                                                        <td className="px-5 py-4">
                                                            <div className="text-sm text-gray-900">{item.item_name}</div>
                                                            {item.item_description && (
                                                                <div className="text-sm text-gray-400 mt-1">{item.item_description}</div>
                                                            )}
                                                        </td>
                                                        <td className="px-5 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                                            {item.quantity} {item.unit}
                                                        </td>
                                                        <td className="px-5 py-4 whitespace-nowrap text-center">
                                                            {item.image_path ? (
                                                                <a
                                                                    href={`/storage/${item.image_path}`}
                                                                    target="_blank"
                                                                    rel="noopener noreferrer"
                                                                    className="text-blue-600 hover:text-blue-800"
                                                                >
                                                                    <Eye className="w-4 h-4 inline" />
                                                                </a>
                                                            ) : (
                                                                <span className="text-gray-400">-</span>
                                                            )}
                                                        </td>
                                                    </motion.tr>
                                                ))
                                            ) : (
                                                <tr>
                                                    <td colSpan={4} className="px-5 py-8 text-center text-sm text-gray-500">
                                                        No items found
                                                    </td>
                                                </tr>
                                            )}
                                        </tbody>
                                    </table>
                                </div>
                            </motion.div>
                        </div>


                        {/* Sidebar (1/3) */}
                        <div className="space-y-6">
                            {approvalContext && (
                                <motion.div
                                    initial={{ opacity: 0, y: 20 }}
                                    animate={{ opacity: 1, y: 0 }}
                                    transition={{ delay: 0.25 }}
                                    className="bg-white rounded-xl border border-gray-100 overflow-hidden"
                                >
                                    <div className="px-5 py-4 border-b border-gray-100">
                                        <h3 className="text-base font-semibold text-gray-900">Your Approval</h3>
                                    </div>
                                    <div className="p-5 space-y-4">
                                        {approvalContext.approvalStatus !== 'pending' ? (
                                            <div className="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-700">
                                                This approval has been processed.
                                            </div>
                                        ) : !approvalContext.canApprove ? (
                                            <div className="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800">
                                                This is not the active approval step yet.
                                            </div>
                                        ) : (
                                            <>
                                                <div>
                                                    <label className="block text-sm font-medium text-gray-700 mb-2">Notes (Optional)</label>
                                                    <textarea
                                                        value={approvalNotes}
                                                        onChange={(event) => setApprovalNotes(event.target.value)}
                                                        rows={3}
                                                        placeholder="Add approval notes"
                                                        className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary focus:ring-2 focus:ring-primary"
                                                    />
                                                </div>
                                                <div className="grid grid-cols-2 gap-3">
                                                    <Button
                                                        type="button"
                                                        onClick={() => handleApprovalDecision('approve')}
                                                        disabled={isDecisionSubmitting}
                                                        className="bg-emerald-600 hover:bg-emerald-700 text-white"
                                                    >
                                                        {isDecisionSubmitting ? <Loader2 className="w-4 h-4 animate-spin mr-2" /> : null}
                                                        Approve
                                                    </Button>
                                                    <Button
                                                        type="button"
                                                        onClick={() => handleApprovalDecision('reject')}
                                                        disabled={isDecisionSubmitting}
                                                        className="bg-red-600 hover:bg-red-700 text-white"
                                                    >
                                                        {isDecisionSubmitting ? <Loader2 className="w-4 h-4 animate-spin mr-2" /> : null}
                                                        Reject
                                                    </Button>
                                                </div>
                                            </>
                                        )}
                                    </div>
                                </motion.div>
                            )}

                            {/* Approval Progress Card */}
                            <motion.div
                                initial={{ opacity: 0, y: 20 }}
                                animate={{ opacity: 1, y: 0 }}
                                transition={{ delay: 0.3 }}
                                className="bg-white rounded-xl border border-gray-100 overflow-hidden"
                            >
                                <div className="px-5 py-4 border-b border-gray-100">
                                    <h3 className="text-base font-semibold text-gray-900">Approval Progress</h3>
                                </div>
                                <div className="p-5">
                                    {stockRequest.approvals && stockRequest.approvals.length > 0 ? (
                                        <div className="space-y-4">
                                            {stockRequest.approvals.map((approval, index) => (
                                                <div key={approval.id} className="flex items-start space-x-3">
                                                    <div className={`flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium ${
                                                        APPROVAL_BADGE_COLORS[approval.status]?.bg || APPROVAL_BADGE_COLORS.pending.bg
                                                    } ${
                                                        APPROVAL_BADGE_COLORS[approval.status]?.text || APPROVAL_BADGE_COLORS.pending.text
                                                    }`}>
                                                        {approval.status === 'approved' ? <Check className="w-4 h-4" /> :
                                                         approval.status === 'rejected' ? <X className="w-4 h-4" /> :
                                                         index + 1}
                                                    </div>
                                                    <div className="flex-1 min-w-0">
                                                        <p className="text-sm font-medium text-gray-900">{approval.approver?.name || 'N/A'}</p>
                                                        <p className="text-xs text-gray-500 capitalize">{approval.status}</p>
                                                        {approval.responded_at && (
                                                            <p className="text-xs text-gray-400 mt-1">
                                                                {formatDate(approval.responded_at)}
                                                            </p>
                                                        )}
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    ) : (
                                        <p className="text-sm text-gray-500 text-center py-4">No approval workflow</p>
                                    )}
                                </div>
                            </motion.div>

                            {/* Offline Approval Document */}
                            {stockRequest.offline_approval_document_path && permissions?.offlineApprovalDocument && (
                                <motion.div
                                    initial={{ opacity: 0, y: 20 }}
                                    animate={{ opacity: 1, y: 0 }}
                                    transition={{ delay: 0.4 }}
                                    className="bg-white rounded-xl border border-gray-100 overflow-hidden"
                                >
                                    <div className="px-5 py-4 border-b border-gray-100">
                                        <h3 className="text-base font-semibold text-gray-900">Offline Approval Document</h3>
                                    </div>
                                    <div className="p-5">
                                        <div className="flex items-center justify-between p-3 bg-purple-50 rounded-lg border border-purple-200">
                                            <div className="flex items-center space-x-3">
                                                <FileText className="w-5 h-5 text-purple-500" />
                                                <span className="text-sm text-purple-700 truncate">
                                                    {stockRequest.offline_approval_document_name || 'Document'}
                                                </span>
                                            </div>
                                            <a
                                                href={getOfflineApprovalDocumentUrl()}
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                className="text-purple-600 hover:text-purple-800"
                                                title="View offline approval document"
                                            >
                                                <Eye className="w-4 h-4" />
                                            </a>
                                        </div>
                                    </div>
                                </motion.div>
                            )}
                        </div>
                    </div>
                </div>


                {/* Void Modal */}
                <AnimatePresence>
                    {showVoidModal && (
                        <motion.div
                            initial={{ opacity: 0 }}
                            animate={{ opacity: 1 }}
                            exit={{ opacity: 0 }}
                            className="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
                            onClick={() => setShowVoidModal(false)}
                        >
                            <motion.div
                                initial={{ scale: 0.95, opacity: 0 }}
                                animate={{ scale: 1, opacity: 1 }}
                                exit={{ scale: 0.95, opacity: 0 }}
                                className="bg-white rounded-xl p-6 max-w-md w-full mx-4"
                                onClick={(e) => e.stopPropagation()}
                            >
                                <h3 className="text-lg font-semibold text-gray-900 mb-4">Void Stock Request</h3>
                                <form onSubmit={handleVoid}>
                                    <textarea
                                        value={voidReason}
                                        onChange={(e) => setVoidReason(e.target.value)}
                                        placeholder="Please provide a reason for voiding this request..."
                                        rows={4}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 text-sm"
                                        required
                                    />
                                    <div className="flex justify-end space-x-3 mt-4">
                                        <Button type="button" variant="ghost" onClick={() => setShowVoidModal(false)}>
                                            Cancel
                                        </Button>
                                        <Button type="submit" disabled={isSubmitting} className="bg-red-600 hover:bg-red-700 text-white">
                                            {isSubmitting ? <Loader2 className="w-4 h-4 animate-spin mr-2" /> : null}
                                            Void Request
                                        </Button>
                                    </div>
                                </form>
                            </motion.div>
                        </motion.div>
                    )}
                </AnimatePresence>

                {/* Offline Approval Modal */}
                <AnimatePresence>
                    {showOfflineModal && (
                        <motion.div
                            initial={{ opacity: 0 }}
                            animate={{ opacity: 1 }}
                            exit={{ opacity: 0 }}
                            className="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
                            onClick={() => setShowOfflineModal(false)}
                        >
                            <motion.div
                                initial={{ scale: 0.95, opacity: 0 }}
                                animate={{ scale: 1, opacity: 1 }}
                                exit={{ scale: 0.95, opacity: 0 }}
                                className="bg-white rounded-xl p-6 max-w-md w-full mx-4"
                                onClick={(e) => e.stopPropagation()}
                            >
                                <h3 className="text-lg font-semibold text-gray-900 mb-4">Mark as Offline Approved</h3>
                                <form onSubmit={handleOfflineApproval}>
                                    <div className="mb-4">
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Upload Approval Document <span className="text-red-500">*</span>
                                        </label>
                                        <input
                                            type="file"
                                            accept=".pdf,.jpg,.jpeg,.png"
                                            onChange={(e) => setOfflineDocument(e.target.files?.[0] || null)}
                                            className="w-full text-sm"
                                            required
                                        />
                                    </div>
                                    <div className="mb-4">
                                        <label className="block text-sm font-medium text-gray-700 mb-1">Notes (Optional)</label>
                                        <textarea
                                            value={offlineNotes}
                                            onChange={(e) => setOfflineNotes(e.target.value)}
                                            placeholder="Add any notes..."
                                            rows={3}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 text-sm"
                                        />
                                    </div>
                                    <div className="flex justify-end space-x-3">
                                        <Button type="button" variant="ghost" onClick={() => setShowOfflineModal(false)}>
                                            Cancel
                                        </Button>
                                        <Button type="submit" disabled={isSubmitting} className="bg-purple-600 hover:bg-purple-700 text-white">
                                            {isSubmitting ? <Loader2 className="w-4 h-4 animate-spin mr-2" /> : null}
                                            Mark Approved
                                        </Button>
                                    </div>
                                </form>
                            </motion.div>
                        </motion.div>
                    )}
                </AnimatePresence>
            </div>
        </>
    );
}
