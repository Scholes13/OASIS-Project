import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { motion } from 'framer-motion';
import { AlertTriangle, Check, Loader2, X } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { formatDate } from '@/lib/formatters';
import { APPROVAL_BADGE_COLORS } from '@/lib/purchasingConstants';
import { toast } from 'sonner';
import { SupportingDocumentLink } from '@/components/purchasing/SupportingDocumentLink';
import { StockRequestActionModals } from '@/components/purchasing/show/StockRequestActionModals';
import { StockRequestHeader } from '@/components/purchasing/show/StockRequestHeader';
import { StockRequestItemsTable } from '@/components/purchasing/show/StockRequestItemsTable';
import { StockRequestSummaryPanel } from '@/components/purchasing/show/StockRequestSummaryPanel';
import type { STPermissions, STShowProps } from '@/types/purchasing';

export default function Show({ stockRequest, can, approvalContext }: STShowProps) {
    const [showVoidModal, setShowVoidModal] = useState(false);
    const [showOfflineModal, setShowOfflineModal] = useState(false);
    const [showApproveModal, setShowApproveModal] = useState(false);
    const [showRejectModal, setShowRejectModal] = useState(false);
    const [voidReason, setVoidReason] = useState('');
    const [offlineNotes, setOfflineNotes] = useState('');
    const [offlineDocument, setOfflineDocument] = useState<File | null>(null);
    const [approvalNotes, setApprovalNotes] = useState('');
    const [isDecisionSubmitting, setIsDecisionSubmitting] = useState(false);
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [isResendingEmail, setIsResendingEmail] = useState(false);

    const permissions: Partial<STPermissions> = can || stockRequest.can || {};
    const isApprovalView = Boolean(approvalContext?.approvalId);

    // Handle void action
    const handleVoid = () => {
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
    const handleOfflineApproval = (e?: React.FormEvent) => {
        e?.preventDefault();
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
                    setShowApproveModal(false);
                    setShowRejectModal(false);
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
                <StockRequestHeader
                    stockRequest={stockRequest}
                    permissions={permissions}
                    isApprovalView={isApprovalView}
                    isResendingEmail={isResendingEmail}
                    onResubmit={handleResubmit}
                    onResendApprovalEmail={handleResendApprovalEmail}
                    onMarkOfflineApproved={() => setShowOfflineModal(true)}
                    onVoid={() => setShowVoidModal(true)}
                />


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
                            <StockRequestSummaryPanel stockRequest={stockRequest} />
                            <StockRequestItemsTable stockRequest={stockRequest} />
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
                                                        onClick={() => setShowApproveModal(true)}
                                                        disabled={isDecisionSubmitting}
                                                        className="bg-emerald-600 hover:bg-emerald-700 text-white"
                                                    >
                                                        {isDecisionSubmitting ? <Loader2 className="w-4 h-4 animate-spin mr-2" /> : null}
                                                        Approve
                                                    </Button>
                                                    <Button
                                                        type="button"
                                                        onClick={() => setShowRejectModal(true)}
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
                                        <SupportingDocumentLink
                                            filename={stockRequest.offline_approval_document_name}
                                            url={getOfflineApprovalDocumentUrl()}
                                            className="bg-purple-50 rounded-lg border border-purple-200"
                                        />
                                    </div>
                                </motion.div>
                            )}
                        </div>
                    </div>
                </div>


                <StockRequestActionModals
                    itemNumber={stockRequest.st_number}
                    showVoidModal={showVoidModal}
                    showOfflineModal={showOfflineModal}
                    showApproveModal={showApproveModal}
                    showRejectModal={showRejectModal}
                    isSubmitting={isSubmitting}
                    isDecisionSubmitting={isDecisionSubmitting}
                    voidReason={voidReason}
                    offlineNotes={offlineNotes}
                    offlineDocument={offlineDocument}
                    approvalNotes={approvalNotes}
                    onVoidClose={() => setShowVoidModal(false)}
                    onOfflineClose={() => setShowOfflineModal(false)}
                    onApproveClose={() => setShowApproveModal(false)}
                    onRejectClose={() => setShowRejectModal(false)}
                    onVoidReasonChange={setVoidReason}
                    onOfflineNotesChange={setOfflineNotes}
                    onOfflineDocumentChange={setOfflineDocument}
                    onApprovalNotesChange={setApprovalNotes}
                    onVoidSubmit={handleVoid}
                    onOfflineSubmit={handleOfflineApproval}
                    onApproveSubmit={() => handleApprovalDecision('approve')}
                    onRejectSubmit={() => handleApprovalDecision('reject')}
                />
            </div>
        </>
    );
}
