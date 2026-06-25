import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { motion } from 'framer-motion';
import { AlertTriangle, CheckCircle2, Circle, Download, Eye } from 'lucide-react';
import { PRShowProps } from '@/types/purchasing';
import { toast } from 'sonner';
import { SupportingDocumentLink } from '@/components/purchasing/SupportingDocumentLink';
import { formatCurrency, formatDateTime } from '@/lib/formatters';
import { PurchaseRequestActionModals } from '@/components/purchasing/show/PurchaseRequestActionModals';
import { PurchaseRequestApprovalsTimeline } from '@/components/purchasing/show/PurchaseRequestApprovalsTimeline';
import { PurchaseRequestHeader } from '@/components/purchasing/show/PurchaseRequestHeader';
import { PurchaseRequestItemsTable } from '@/components/purchasing/show/PurchaseRequestItemsTable';
import { PurchaseRequestSummaryPanel } from '@/components/purchasing/show/PurchaseRequestSummaryPanel';

function SectionHeading({ children, hint }: { children: React.ReactNode; hint?: string }) {
    return (
        <div className="flex items-baseline justify-between">
            <h2 className="text-xs font-semibold uppercase tracking-wider text-slate-500">{children}</h2>
            {hint && <span className="text-xs text-slate-400">{hint}</span>}
        </div>
    );
}

function SidebarCard({ children }: { children: React.ReactNode }) {
    return (
        <section className="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm shadow-slate-200/50">
            {children}
        </section>
    );
}

function PurchaseRequestSidebarSummary({ purchaseRequest }: { purchaseRequest: PRShowProps['purchaseRequest'] }) {
    const itemCount = purchaseRequest.items?.length || 0;
    const approvedSteps = purchaseRequest.approvals?.filter((approval) => approval.status === 'approved').length || 0;
    const totalSteps = purchaseRequest.approvals?.length || 0;
    const progress = totalSteps > 0 ? Math.round((approvedSteps / totalSteps) * 100) : 100;

    return (
        <SidebarCard>
            <SectionHeading>Request Summary</SectionHeading>
            <dl className="mt-4 space-y-3 text-sm">
                <div className="flex items-center justify-between gap-3">
                    <dt className="text-slate-500">Request ID</dt>
                    <dd className="font-medium text-slate-950">{purchaseRequest.pr_number}</dd>
                </div>
                <div className="flex items-center justify-between gap-3">
                    <dt className="text-slate-500">Items</dt>
                    <dd className="font-medium text-slate-950">{itemCount}</dd>
                </div>
                <div className="flex items-center justify-between gap-3">
                    <dt className="text-slate-500">Total Amount</dt>
                    <dd className="font-medium text-slate-950">{formatCurrency(purchaseRequest.total_amount, purchaseRequest.currency)}</dd>
                </div>
                <div className="flex items-center justify-between gap-3">
                    <dt className="text-slate-500">Current Step</dt>
                    <dd className="font-medium capitalize text-blue-700">{purchaseRequest.status.replace(/_/g, ' ')}</dd>
                </div>
                <div className="flex items-center justify-between gap-3">
                    <dt className="text-slate-500">Progress</dt>
                    <dd className="font-medium text-slate-950">{progress}%</dd>
                </div>
            </dl>
            <div className="mt-4 h-2 rounded-full bg-slate-100">
                <div className="h-2 rounded-full bg-blue-600" style={{ width: `${progress}%` }} />
            </div>
        </SidebarCard>
    );
}

function getPurchaseRequestSteps(purchaseRequest: PRShowProps['purchaseRequest']) {
    const approvals = purchaseRequest.approvals || [];
    const allApproved = approvals.length > 0 && approvals.every((approval) => approval.status === 'approved');
    const requestedAt = formatDateTime(purchaseRequest.submitted_at || purchaseRequest.created_at);
    const requester = purchaseRequest.user?.name || 'Requester';
    const approvalStage = (index: number, pendingLabel: string) => {
        const approval = approvals[index];

        if (!approval) {
            return {
                actor: pendingLabel,
                time: 'Pending',
                state: 'pending',
            };
        }

        if (approval.status === 'approved') {
            return {
                actor: approval.approver?.name || 'Approver',
                time: formatDateTime(approval.responded_at),
                state: 'done',
            };
        }

        if (approval.status === 'rejected') {
            return {
                actor: approval.approver?.name || 'Approver',
                time: formatDateTime(approval.responded_at),
                state: 'active',
            };
        }

        return {
            actor: approval.approver?.name || pendingLabel,
            time: 'In progress',
            state: 'active',
        };
    };
    const internalApproval = approvalStage(0, 'Internal Department');
    const purchasingApproval = approvalStage(1, 'Purchasing Approval');
    const managementApproval = approvalStage(2, 'Management / BOD');
    const adminTask = purchaseRequest.admin_task;
    const purchasingFollowUp = adminTask
        ? {
            actor: adminTask.assigned_admin?.name || 'Purchasing team',
            time: adminTask.completed_at ? formatDateTime(adminTask.completed_at) : adminTask.started_at ? 'In progress' : 'Pending',
            state: adminTask.status === 'done' ? 'done' : adminTask.status === 'in_progress' ? 'active' : 'pending',
        }
        : {
            actor: 'Purchasing team',
            time: allApproved ? 'In progress' : 'Pending',
            state: allApproved ? 'active' : 'pending',
        };
    const doneStep = adminTask?.status === 'done'
        ? {
            actor: 'Goods received',
            time: adminTask.completed_at ? formatDateTime(adminTask.completed_at) : 'Done',
            state: 'done',
        }
        : {
            actor: 'Goods received',
            time: 'Pending',
            state: 'pending',
        };

    return [
        {
            title: 'Request Initiated',
            actor: requester,
            time: requestedAt,
            state: 'done',
        },
        {
            title: 'Internal Department',
            ...internalApproval,
        },
        {
            title: 'Purchasing Approval',
            ...purchasingApproval,
        },
        {
            title: 'Management / BOD',
            ...managementApproval,
        },
        {
            title: 'Purchasing Follow-up',
            ...purchasingFollowUp,
        },
        {
            title: 'Done',
            ...doneStep,
        },
    ];
}

function PurchaseRequestProcessBar({ purchaseRequest }: { purchaseRequest: PRShowProps['purchaseRequest'] }) {
    const steps = getPurchaseRequestSteps(purchaseRequest);

    return (
        <section className="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm shadow-slate-200/50">
            <SectionHeading hint="Workflow">Process Overview</SectionHeading>
            <ol className="mt-4 grid gap-3 lg:grid-cols-6">
                {steps.map((step, index) => {
                    const isDone = step.state === 'done';
                    const isActive = step.state === 'active';
                    const Icon = isDone ? CheckCircle2 : Circle;

                    return (
                        <li key={step.title} className="relative min-w-0">
                            <div className="relative flex items-start gap-3">
                                <div className={`flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-full ${isDone ? 'bg-emerald-50' : isActive ? 'bg-blue-600' : 'bg-slate-100'}`}>
                                    <Icon className={`h-4 w-4 ${isDone ? 'text-emerald-600' : isActive ? 'text-white' : 'text-slate-300'}`} />
                                </div>
                                <div className="min-w-0">
                                    <p className={`text-sm font-semibold ${isDone || isActive ? 'text-slate-950' : 'text-slate-400'}`}>{step.title}</p>
                                    <div className="mt-1 space-y-0.5 text-xs leading-5 text-slate-500">
                                        <p>{step.actor}</p>
                                        <p className={isActive ? 'text-blue-700' : ''}>{step.time}</p>
                                    </div>
                                </div>
                            </div>
                        </li>
                    );
                })}
            </ol>
        </section>
    );
}

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

            <div className="min-h-screen">
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

                {purchaseRequest.status === 'rejected' && (
                    <motion.div
                        initial={{ opacity: 0, y: -8 }}
                        animate={{ opacity: 1, y: 0 }}
                        className="mx-7 mb-2 flex items-start gap-3 rounded-lg border border-red-100 bg-red-50/70 px-4 py-3"
                    >
                        <AlertTriangle className="mt-0.5 h-4 w-4 flex-shrink-0 text-red-500" />
                        <div className="text-sm">
                            <p className="font-medium text-red-800">This purchase request was rejected</p>
                            <p className="mt-0.5 text-red-700">Edit and resubmit using the Resubmit action above.</p>
                        </div>
                    </motion.div>
                )}

                <div className="px-7 pb-12 pt-1">
                    <div className="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1fr)_24rem]">
                        <div className="space-y-6">
                            <PurchaseRequestProcessBar purchaseRequest={purchaseRequest} />
                            <section className="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm shadow-slate-200/50">
                                <PurchaseRequestSummaryPanel purchaseRequest={purchaseRequest} />
                            </section>
                            <section className="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm shadow-slate-200/50">
                                <PurchaseRequestItemsTable purchaseRequest={purchaseRequest} />
                            </section>
                        </div>

                        <aside className="space-y-5">
                            <PurchaseRequestSidebarSummary purchaseRequest={purchaseRequest} />
                            <motion.section
                                initial={{ opacity: 0, y: 12 }}
                                animate={{ opacity: 1, y: 0 }}
                                transition={{ delay: 0.2 }}
                                className="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm shadow-slate-200/50"
                            >
                                <PurchaseRequestApprovalsTimeline purchaseRequest={purchaseRequest} />
                            </motion.section>
                            <SidebarCard>
                                <SectionHeading>Request Actions</SectionHeading>
                                <div className="mt-4 divide-y divide-slate-100 text-sm">
                                    {permissions?.downloadPdf && (
                                        <a
                                            href={route('purchase-requests.pdf-public', { purchaseRequest: purchaseRequest.id })}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            className="flex items-center justify-between py-3 text-slate-600 transition-colors hover:text-slate-950"
                                        >
                                            <span className="inline-flex items-center gap-2">
                                                <Download className="h-4 w-4" />
                                                Download request
                                            </span>
                                            <span className="text-slate-300">›</span>
                                        </a>
                                    )}
                                    <a
                                        href={route('purchase-requests.index')}
                                        className="flex items-center justify-between py-3 text-slate-600 transition-colors hover:text-slate-950"
                                    >
                                        <span className="inline-flex items-center gap-2">
                                            <Eye className="h-4 w-4" />
                                            View list
                                        </span>
                                        <span className="text-slate-300">›</span>
                                    </a>
                                </div>
                            </SidebarCard>
                            {purchaseRequest.supporting_document_path && permissions?.supportingDocument && (
                                <motion.section
                                    initial={{ opacity: 0, y: 12 }}
                                    animate={{ opacity: 1, y: 0 }}
                                    transition={{ delay: 0.25 }}
                                    className="space-y-4"
                                >
                                    <SectionHeading>Supporting Document</SectionHeading>
                                    <SupportingDocumentLink
                                        filename={purchaseRequest.supporting_document_name}
                                        url={getSupportingDocumentUrl()}
                                        downloadUrl={getSupportingDocumentUrl(true)}
                                    />
                                </motion.section>
                            )}
                        </aside>
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
