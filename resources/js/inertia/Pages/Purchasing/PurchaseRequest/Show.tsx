import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { motion } from 'framer-motion';
import { AlertTriangle } from 'lucide-react';
import { PRShowProps } from '@/types/purchasing';
import { toast } from 'sonner';
import { SupportingDocumentLink } from '@/components/purchasing/SupportingDocumentLink';
import { PurchaseRequestActionModals } from '@/components/purchasing/show/PurchaseRequestActionModals';
import { PurchaseRequestApprovalsTimeline } from '@/components/purchasing/show/PurchaseRequestApprovalsTimeline';
import { PurchaseRequestHeader } from '@/components/purchasing/show/PurchaseRequestHeader';
import { PurchaseRequestItemsTable } from '@/components/purchasing/show/PurchaseRequestItemsTable';
import { PurchaseRequestSummaryPanel } from '@/components/purchasing/show/PurchaseRequestSummaryPanel';

export default function Show({ purchaseRequest, can }: PRShowProps) {
    const [showVoidModal, setShowVoidModal] = useState(false);
    const [showOfflineModal, setShowOfflineModal] = useState(false);
    const [showApproveModal, setShowApproveModal] = useState(false);
    const [showRejectModal, setShowRejectModal] = useState(false);
    const [voidReason, setVoidReason] = useState('');
    const [offlineNotes, setOfflineNotes] = useState('');
    const [offlineDocument, setOfflineDocument] = useState<File | null>(null);
    const [approvalNotes, setApprovalNotes] = useState('');
    const [rejectionNotes, setRejectionNotes] = useState('');
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [isResendingEmail, setIsResendingEmail] = useState(false);
    const supportingDocumentRouteName = 'purchase-requests.supporting-document';
    const supportingDocumentDownloadRouteName = 'purchase-requests.supporting-document.download';

    // Permissions
    const permissions = can || purchaseRequest.can || {} as {
        approve?: boolean;
        reject?: boolean;
        edit?: boolean;
        resubmit?: boolean;
        resendApprovalEmail?: boolean;
        downloadPdf?: boolean;
        markOfflineApproved?: boolean;
        void?: boolean;
        supportingDocument?: boolean;
    };


    // Handle void action
    const handleVoid = () => {
        if (!voidReason.trim()) {
            toast.error('Please provide a reason for voiding');
            return;
        }

        setIsSubmitting(true);
        router.post(
            route('purchase-requests.void', { purchaseRequest: purchaseRequest.id }),
            { reason: voidReason },
            {
                onSuccess: () => {
                    toast.success('Purchase request voided successfully');
                    setShowVoidModal(false);
                },
                onError: () => {
                    toast.error('Failed to void purchase request');
                },
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
        if (offlineNotes.trim()) {
            formData.append('notes', offlineNotes);
        }

        router.post(
            route('purchase-requests.mark-offline-approved', { purchaseRequest: purchaseRequest.id }),
            formData as any,
            {
                onSuccess: () => {
                    toast.success('Purchase request marked as offline approved');
                    setShowOfflineModal(false);
                },
                onError: () => {
                    toast.error('Failed to mark as offline approved');
                },
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
            route('purchase-requests.resubmit', { purchaseRequest: purchaseRequest.id }),
            {},
            {
                onSuccess: () => {
                    toast.success('Purchase request resubmitted successfully');
                },
                onError: () => {
                    toast.error('Failed to resubmit purchase request');
                },
            }
        );
    };

    // Handle resend approval email
    const handleResendApprovalEmail = () => {
        setIsResendingEmail(true);

        router.post(
            route('purchase-requests.resend-approval-email', { purchaseRequest: purchaseRequest.id }),
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

    // Handle approve
    const handleApprove = () => {
        setIsSubmitting(true);
        router.post(
            route('purchase-requests.approve', { purchaseRequest: purchaseRequest.id }),
            { notes: approvalNotes },
            {
                onSuccess: () => {
                    toast.success('Purchase request approved successfully');
                    setShowApproveModal(false);
                },
                onError: () => {
                    toast.error('Failed to approve purchase request');
                },
                onFinish: () => setIsSubmitting(false),
            }
        );
    };

    // Handle reject
    const handleReject = () => {
        if (!rejectionNotes.trim()) {
            toast.error('Please provide a reason for rejection');
            return;
        }
        setIsSubmitting(true);
        router.post(
            route('purchase-requests.reject', { purchaseRequest: purchaseRequest.id }),
            { notes: rejectionNotes },
            {
                onSuccess: () => {
                    toast.success('Purchase request rejected successfully');
                    setShowRejectModal(false);
                },
                onError: () => {
                    toast.error('Failed to reject purchase request');
                },
                onFinish: () => setIsSubmitting(false),
            }
        );
    };

    const getSupportingDocumentUrl = (download: boolean = false) => {
        const ziggy = route as unknown as {
            (...args: any[]): any;
        };
        const routeName = download ? supportingDocumentDownloadRouteName : supportingDocumentRouteName;
        const fallbackPath = download
            ? `/purchase-requests/${purchaseRequest.id}/supporting-document/download`
            : `/purchase-requests/${purchaseRequest.id}/supporting-document`;

        try {
            const routeList = ziggy();
            if (routeList?.has?.(routeName)) {
                return ziggy(routeName, {
                    purchaseRequest: purchaseRequest.id,
                });
            }
        } catch {
            // Fall back to the authenticated application path if Ziggy route metadata is unavailable.
        }

        return fallbackPath;
    };


    return (
        <>
            <Head title={`PR ${purchaseRequest.pr_number}`} />

            <div className="bg-white">
                <PurchaseRequestHeader
                    purchaseRequest={purchaseRequest}
                    permissions={permissions}
                    isResendingEmail={isResendingEmail}
                    onApprove={() => setShowApproveModal(true)}
                    onReject={() => setShowRejectModal(true)}
                    onResubmit={handleResubmit}
                    onResendApprovalEmail={handleResendApprovalEmail}
                    onMarkOfflineApproved={() => setShowOfflineModal(true)}
                    onVoid={() => setShowVoidModal(true)}
                />


                {/* Alert for Rejected PRs */}
                {purchaseRequest.status === 'rejected' && (
                    <motion.div
                        initial={{ opacity: 0, y: -10 }}
                        animate={{ opacity: 1, y: 0 }}
                        className="mx-6 mt-4 p-4 bg-red-50 border border-red-200 rounded-lg"
                    >
                        <div className="flex items-start">
                            <AlertTriangle className="w-5 h-5 text-red-500 mt-0.5" />
                            <div className="ml-3">
                                <h3 className="text-sm font-medium text-red-800">
                                    This Purchase Request was Rejected
                                </h3>
                                <p className="text-sm text-red-700 mt-1">
                                    You can edit this PR and resubmit it for approval using the "Resubmit" button above.
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
                            <PurchaseRequestSummaryPanel purchaseRequest={purchaseRequest} />


                            {/* Supporting Document Card */}
                            {purchaseRequest.supporting_document_path && permissions?.supportingDocument && (
                                <motion.div
                                    initial={{ opacity: 0, y: 20 }}
                                    animate={{ opacity: 1, y: 0 }}
                                    transition={{ delay: 0.2 }}
                                    className="bg-white rounded-xl border border-gray-100 overflow-hidden"
                                >
                                    <div className="px-5 py-4 border-b border-gray-100">
                                        <h3 className="text-base font-semibold text-gray-900">Supporting Document</h3>
                                    </div>
                                    <div className="p-5">
                                        <SupportingDocumentLink
                                            filename={purchaseRequest.supporting_document_name}
                                            url={getSupportingDocumentUrl()}
                                            downloadUrl={getSupportingDocumentUrl(true)}
                                        />
                                    </div>
                                </motion.div>
                            )}


                            <PurchaseRequestItemsTable purchaseRequest={purchaseRequest} />
                        </div>


                        {/* Sidebar (1/3) */}
                        <div className="space-y-6">
                            <PurchaseRequestApprovalsTimeline purchaseRequest={purchaseRequest} />
                        </div>
                    </div>
                </div>
            </div>


            <PurchaseRequestActionModals
                itemNumber={purchaseRequest.pr_number}
                showVoidModal={showVoidModal}
                showOfflineModal={showOfflineModal}
                showApproveModal={showApproveModal}
                showRejectModal={showRejectModal}
                isSubmitting={isSubmitting}
                voidReason={voidReason}
                offlineNotes={offlineNotes}
                offlineDocument={offlineDocument}
                approvalNotes={approvalNotes}
                rejectionNotes={rejectionNotes}
                onVoidClose={() => setShowVoidModal(false)}
                onOfflineClose={() => setShowOfflineModal(false)}
                onApproveClose={() => setShowApproveModal(false)}
                onRejectClose={() => setShowRejectModal(false)}
                onVoidReasonChange={setVoidReason}
                onOfflineNotesChange={setOfflineNotes}
                onOfflineDocumentChange={setOfflineDocument}
                onApprovalNotesChange={setApprovalNotes}
                onRejectionNotesChange={setRejectionNotes}
                onVoidSubmit={handleVoid}
                onOfflineSubmit={handleOfflineApproval}
                onApproveSubmit={handleApprove}
                onRejectSubmit={handleReject}
            />
        </>
    );
}
