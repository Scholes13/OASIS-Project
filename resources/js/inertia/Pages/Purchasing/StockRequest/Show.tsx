import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { motion } from 'framer-motion';
import { AlertTriangle, CheckCircle2, Circle, Download, Eye, Loader2 } from 'lucide-react';
import { Button } from '@/components/ui/button';

import { toast } from 'sonner';
import { SupportingDocumentLink } from '@/components/purchasing/SupportingDocumentLink';
import { formatCurrency, formatDateTime } from '@/lib/formatters';
import { StockRequestActionModals } from '@/components/purchasing/show/StockRequestActionModals';
import { StockRequestHeader } from '@/components/purchasing/show/StockRequestHeader';
import { StockRequestItemsTable, type StockRequestGaReviewItem } from '@/components/purchasing/show/StockRequestItemsTable';
import { StockRequestSummaryPanel } from '@/components/purchasing/show/StockRequestSummaryPanel';
import type { STPermissions, STShowProps } from '@/types/purchasing';

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

function StockRequestSidebarSummary({ stockRequest }: { stockRequest: STShowProps['stockRequest'] }) {
    const itemCount = stockRequest.items?.length || 0;
    const initialTotalAmount = stockRequest.items?.reduce((sum, item) => {
        const quantity = Number(item.quantity || 0);
        const price = Number(item.price || 0);
        const total = Number(item.total || 0);

        return sum + (price > 0 ? quantity * price : total);
    }, 0) || 0;
    const totalAmount = stockRequest.status === 'in_approval' ? initialTotalAmount : Number(stockRequest.total_amount || initialTotalAmount);
    const approvedSteps = stockRequest.approvals?.filter((approval) => approval.status === 'approved').length || 0;
    const totalSteps = stockRequest.approvals?.length || 0;
    const progress = totalSteps > 0 ? Math.round((approvedSteps / totalSteps) * 100) : 100;

    return (
        <SidebarCard>
            <SectionHeading>Request Summary</SectionHeading>
            <dl className="mt-4 space-y-3 text-sm">
                <div className="flex items-center justify-between gap-3">
                    <dt className="text-slate-500">Request ID</dt>
                    <dd className="font-medium text-slate-950">{stockRequest.st_number}</dd>
                </div>
                <div className="flex items-center justify-between gap-3">
                    <dt className="text-slate-500">Items</dt>
                    <dd className="font-medium text-slate-950">{itemCount}</dd>
                </div>
                <div className="flex items-center justify-between gap-3">
                    <dt className="text-slate-500">Total Amount</dt>
                    <dd className="font-medium text-slate-950">{formatCurrency(totalAmount)}</dd>
                </div>
                <div className="flex items-center justify-between gap-3">
                    <dt className="text-slate-500">Current Step</dt>
                    <dd className="font-medium capitalize text-blue-700">{stockRequest.status.replace(/_/g, ' ')}</dd>
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

function getStockRequestSteps(stockRequest: STShowProps['stockRequest']) {
    const approvalDone = Boolean(stockRequest.approved_at || ['ga_review', 'ready_for_purchasing', 'done'].includes(stockRequest.status));
    const gaDone = ['ready_for_purchasing', 'done'].includes(stockRequest.status);
    const purchasingActive = ['ready_for_purchasing', 'done'].includes(stockRequest.status);
    const approvedApprovals = stockRequest.approvals?.filter((approval) => approval.status === 'approved') || [];
    const lastApproval = approvedApprovals[approvedApprovals.length - 1];
    const totalSteps = stockRequest.approvals?.length || 0;
    const requester = stockRequest.user?.name || 'Requester';
    const requestedAt = formatDateTime(stockRequest.submitted_at || stockRequest.created_at);
    const approvalActor = approvalDone && lastApproval ? lastApproval.approver?.name || 'Approver' : 'Department approval';
    const approvalTime = totalSteps === 0
        ? 'Pending'
        : approvalDone && lastApproval
            ? formatDateTime(lastApproval.responded_at)
            : 'In progress';
    const gaActor = gaDone ? stockRequest.ga_reviewer?.name || 'GA reviewer' : 'General Affairs';
    const gaTime = gaDone
        ? formatDateTime(stockRequest.ga_reviewed_at)
        : stockRequest.status === 'ga_review'
            ? 'In progress'
            : 'Pending';
    const purchasingTask = stockRequest.admin_task;
    const purchasingActor = purchasingTask?.assigned_admin?.name || 'Purchasing team';
    const purchasingTime = purchasingTask?.status === 'done'
        ? formatDateTime(purchasingTask.completed_at)
        : purchasingTask?.status === 'in_progress'
            ? `In progress${purchasingTask.started_at ? ` since ${formatDateTime(purchasingTask.started_at)}` : ''}`
            : purchasingTask?.assigned_admin
                ? 'Claimed'
                : purchasingActive
                    ? 'In progress'
                    : 'Pending';
    const purchasingState = purchasingTask?.status === 'done'
        ? 'done'
        : purchasingActive || Boolean(purchasingTask)
            ? 'active'
            : 'pending';
    const doneState = stockRequest.status === 'done' || purchasingTask?.status === 'done' ? 'done' : 'pending';
    const doneTime = purchasingTask?.status === 'done' && purchasingTask.completed_at
        ? formatDateTime(purchasingTask.completed_at)
        : 'Pending';

    return [
        {
            title: 'Request Initiated',
            actor: requester,
            time: requestedAt,
            state: 'done',
        },
        {
            title: 'Department Approval',
            actor: approvalActor,
            time: approvalTime,
            state: approvalDone ? 'done' : 'active',
        },
        {
            title: 'General Affairs Review',
            actor: gaActor,
            time: gaTime,
            state: gaDone ? 'done' : stockRequest.status === 'ga_review' ? 'active' : 'pending',
        },
        {
            title: 'Purchasing Follow-up',
            actor: purchasingActor,
            time: purchasingTime,
            state: purchasingState,
        },
        {
            title: 'Done',
            actor: 'Completed',
            time: doneTime,
            state: doneState,
        },
    ];
}

function StockRequestProcessBar({ stockRequest }: { stockRequest: STShowProps['stockRequest'] }) {
    const steps = getStockRequestSteps(stockRequest);

    return (
        <section className="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm shadow-slate-200/50">
            <SectionHeading hint="Workflow">Process Overview</SectionHeading>
            <ol className="mt-4 grid gap-3 lg:grid-cols-5">
                {steps.map((step, index) => {
                    const isDone = step.state === 'done';
                    const isActive = step.state === 'active';
                    const Icon = isDone ? CheckCircle2 : Circle;

                    return (
                        <li key={step.title} className="relative min-w-0">
                            {index < steps.length - 1 && <div className="absolute left-9 right-[-1rem] top-3.5 hidden h-px border-t border-dashed border-slate-200 lg:block" />}
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

function StockRequestPipeline({ stockRequest }: { stockRequest: STShowProps['stockRequest'] }) {
    const steps = getStockRequestSteps(stockRequest);

    return (
        <div className="space-y-4">
            <SectionHeading hint="Pipeline">Approval Progress</SectionHeading>
            <ol className="relative space-y-4">
                {steps.map((step, index) => {
                    const isDone = step.state === 'done';
                    const isActive = step.state === 'active';
                    const Icon = isDone ? CheckCircle2 : Circle;

                    return (
                        <li key={step.title} className="relative grid grid-cols-[1.75rem_minmax(0,1fr)] gap-3">
                            {index < steps.length - 1 && <div className="absolute left-3.5 top-7 h-[calc(100%_+_0.5rem)] w-px bg-slate-200" />}
                            <div className={`relative z-10 flex h-7 w-7 items-center justify-center rounded-full ${isDone ? 'bg-emerald-50' : isActive ? 'bg-blue-50' : 'bg-slate-100'}`}>
                                <Icon className={`h-4 w-4 ${isDone ? 'text-emerald-600' : isActive ? 'text-blue-600' : 'text-slate-300'}`} />
                            </div>
                            <div className="min-w-0 rounded-xl bg-white/70 px-3 py-2.5 shadow-sm shadow-slate-200/40 ring-1 ring-slate-200/70">
                                <div className="flex items-start justify-between gap-3">
                                    <p className={`text-sm font-medium ${isDone || isActive ? 'text-slate-950' : 'text-slate-400'}`}>{step.title}</p>
                                    <span className={`rounded-full px-2 py-0.5 text-xs font-medium ${isDone ? 'bg-emerald-50 text-emerald-700' : isActive ? 'bg-blue-50 text-blue-700' : 'bg-slate-100 text-slate-400'}`}>
                                        {isDone ? 'Done' : isActive ? 'In progress' : 'Pending'}
                                    </span>
                                </div>
                                <p className="mt-1 truncate text-xs text-slate-500" title={`${step.actor} · ${step.time}`}>{step.actor} · {step.time}</p>
                            </div>
                        </li>
                    );
                })}
            </ol>
        </div>
    );
}

export default function Show({ stockRequest, can, approvalContext }: STShowProps) {
    const [showVoidModal, setShowVoidModal] = useState(false);
    const [showOfflineModal, setShowOfflineModal] = useState(false);
    const [showApproveModal, setShowApproveModal] = useState(false);
    const [showRejectModal, setShowRejectModal] = useState(false);
    const [voidReason, setVoidReason] = useState('');
    const [offlineNotes, setOfflineNotes] = useState('');
    const [offlineDocument, setOfflineDocument] = useState<File | null>(null);
    const [approvalNotes, setApprovalNotes] = useState('');
    const [gaReviewNotes, setGaReviewNotes] = useState('');
    const [gaRejectReason, setGaRejectReason] = useState('');
    const [gaReviewItems, setGaReviewItems] = useState<StockRequestGaReviewItem[]>(() =>
        (stockRequest.items || []).map((item) => ({
            id: item.id,
            ga_review_result: item.ga_review_result || 'pending_review',
            ga_review_note: item.ga_review_note || '',
            warehouse_available_qty: item.warehouse_available_qty?.toString() || '',
            procurement_quantity: item.ga_review_result === 'need_procurement' ? item.quantity.toString() : '',
        }))
    );
    const [isDecisionSubmitting, setIsDecisionSubmitting] = useState(false);
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [isResendingEmail, setIsResendingEmail] = useState(false);

    const permissions: Partial<STPermissions> = can || stockRequest.can || {};
    const isApprovalView = Boolean(approvalContext?.approvalId);
    const canReviewGa = stockRequest.status === 'ga_review' && Boolean(permissions.gaReviewApprove || permissions.gaReviewReject);

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
            formData,
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
                onSuccess: () => toast.success('Approval email resent to current approver'),
                onError: () => toast.error('Failed to resend approval email'),
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
            { action, notes: approvalNotes },
            {
                onSuccess: () => {
                    toast.success(action === 'approve' ? 'Stock request approved successfully' : 'Stock request rejected');
                    setShowApproveModal(false);
                    setShowRejectModal(false);
                },
                onError: () => {
                    toast.error(action === 'approve' ? 'Failed to approve stock request' : 'Failed to reject stock request');
                },
                onFinish: () => setIsDecisionSubmitting(false),
            }
        );
    };

    const handleGaReviewItemChange = (id: number, field: keyof Omit<StockRequestGaReviewItem, 'id'>, value: string) => {
        const stockItem = stockRequest.items?.find((item) => item.id === id);

        setGaReviewItems((items) => items.map((item) => {
            if (item.id !== id) {
                return item;
            }

            const nextItem = { ...item, [field]: value };

            if (stockItem && field === 'warehouse_available_qty') {
                const warehouseQty = Number(value || 0);
                nextItem.procurement_quantity = value === '' ? '' : Math.max(stockItem.quantity - warehouseQty, 0).toString();
            }

            if (stockItem && field === 'procurement_quantity') {
                const procurementQty = Number(value || 0);
                nextItem.warehouse_available_qty = value === '' ? '' : Math.max(stockItem.quantity - procurementQty, 0).toString();
            }

            if (field === 'ga_review_result' && value === 'warehouse_stock') {
                nextItem.warehouse_available_qty = stockItem?.quantity.toString() || '';
                nextItem.procurement_quantity = '0';
            }

            if (field === 'ga_review_result' && value === 'need_procurement') {
                nextItem.procurement_quantity = stockItem?.quantity.toString() || '';
                nextItem.warehouse_available_qty = '0';
            }

            return nextItem;
        }));
    };

    const handleGaReviewApprove = () => {
        if (gaReviewItems.some((item) => item.ga_review_result === 'pending_review')) {
            toast.error('All items must be reviewed');
            return;
        }
        setIsDecisionSubmitting(true);
        router.post(
            route('stock-requests.ga-review.approve', { stockRequest: stockRequest.id }),
            {
                ga_review_notes: gaReviewNotes,
                items: gaReviewItems.map((item) => ({
                    id: item.id,
                    ga_review_result: item.ga_review_result,
                    ga_review_note: item.ga_review_note,
                    warehouse_available_qty: item.warehouse_available_qty === '' ? null : item.warehouse_available_qty,
                    procurement_quantity: item.procurement_quantity === '' ? null : item.procurement_quantity,
                })),
            },
            {
                onSuccess: () => toast.success('GA review approved'),
                onError: () => toast.error('Failed to approve GA review'),
                onFinish: () => setIsDecisionSubmitting(false),
            }
        );
    };

    const handleGaReviewReject = () => {
        if (!gaRejectReason.trim()) {
            toast.error('Please provide GA rejection reason');
            return;
        }
        setIsDecisionSubmitting(true);
        router.post(
            route('stock-requests.ga-review.reject', { stockRequest: stockRequest.id }),
            { reason: gaRejectReason },
            {
                onSuccess: () => toast.success('GA review rejected'),
                onError: () => toast.error('Failed to reject GA review'),
                onFinish: () => setIsDecisionSubmitting(false),
            }
        );
    };

    const getOfflineApprovalDocumentUrl = () => {
        type RouteHelper = {
            (): { has?: (name: string) => boolean } | undefined;
            (name: string, params?: Record<string, number | string>): string;
        };
        const ziggy = route as unknown as RouteHelper;
        const routeName = 'stock-requests.offline-approval-document';
        const fallbackPath = `/stock-requests/${stockRequest.id}/offline-approval-document`;

        try {
            const routeList = ziggy();
            if (routeList?.has?.(routeName)) {
                return ziggy(routeName, { stockRequest: stockRequest.id });
            }
        } catch {
            // Fall back to authenticated application path when Ziggy metadata unavailable.
        }

        return fallbackPath;
    };

    return (
        <>
            <Head title={`ST ${stockRequest.st_number}`} />

            <div className="min-h-screen">
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

                {stockRequest.status === 'rejected' && (
                    <motion.div
                        initial={{ opacity: 0, y: -8 }}
                        animate={{ opacity: 1, y: 0 }}
                        className="mx-7 mb-2 flex items-start gap-3 rounded-lg border border-red-100 bg-red-50/70 px-4 py-3"
                    >
                        <AlertTriangle className="mt-0.5 h-4 w-4 flex-shrink-0 text-red-500" />
                        <div className="text-sm">
                            <p className="font-medium text-red-800">This stock request was rejected</p>
                            <p className="mt-0.5 text-red-700">Edit and resubmit using the Resubmit action above.</p>
                        </div>
                    </motion.div>
                )}

                <div className="px-7 pb-12 pt-1">
                    <div className="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1fr)_24rem]">
                        <div className="space-y-6">
                            <StockRequestProcessBar stockRequest={stockRequest} />
                            <section className="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm shadow-slate-200/50">
                                <StockRequestSummaryPanel stockRequest={stockRequest} />
                            </section>
                            <section className="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm shadow-slate-200/50">
                                <StockRequestItemsTable
                                    stockRequest={stockRequest}
                                    canReviewGa={canReviewGa}
                                    gaReviewItems={gaReviewItems}
                                    onGaReviewItemChange={handleGaReviewItemChange}
                                />
                            </section>
                        </div>

                        <aside className="space-y-5">
                            <StockRequestSidebarSummary stockRequest={stockRequest} />

                            {canReviewGa && (
                                <motion.section
                                    initial={{ opacity: 0, y: 12 }}
                                    animate={{ opacity: 1, y: 0 }}
                                    transition={{ delay: 0.1 }}
                                    className="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm shadow-slate-200/50"
                                >
                                    <SectionHeading hint="GA only">Stock Review</SectionHeading>
                                    <div className="space-y-3">
                                        <div>
                                            <label className="mb-1.5 block text-xs font-medium text-slate-700">Notes (optional)</label>
                                            <textarea
                                                value={gaReviewNotes}
                                                onChange={(event) => setGaReviewNotes(event.target.value)}
                                                rows={3}
                                                placeholder="Add GA review notes"
                                                className="w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                                            />
                                        </div>
                                        <div>
                                            <label className="mb-1.5 block text-xs font-medium text-slate-700">Reject reason</label>
                                            <textarea
                                                value={gaRejectReason}
                                                onChange={(event) => setGaRejectReason(event.target.value)}
                                                rows={3}
                                                placeholder="Required when rejecting"
                                                className="w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                                            />
                                        </div>
                                        <div className="grid grid-cols-2 gap-2 pt-1">
                                            {permissions.gaReviewApprove && (
                                                <Button type="button" onClick={handleGaReviewApprove} disabled={isDecisionSubmitting} className="bg-emerald-600 hover:bg-emerald-700 text-white">
                                                    {isDecisionSubmitting ? <Loader2 className="mr-2 h-3.5 w-3.5 animate-spin" /> : null}
                                                    Approve
                                                </Button>
                                            )}
                                            {permissions.gaReviewReject && (
                                                <Button type="button" onClick={handleGaReviewReject} disabled={isDecisionSubmitting} className="bg-red-600 hover:bg-red-700 text-white">
                                                    {isDecisionSubmitting ? <Loader2 className="mr-2 h-3.5 w-3.5 animate-spin" /> : null}
                                                    Reject
                                                </Button>
                                            )}
                                        </div>
                                    </div>
                                </motion.section>
                            )}

                            {approvalContext && (
                                <motion.section
                                    initial={{ opacity: 0, y: 12 }}
                                    animate={{ opacity: 1, y: 0 }}
                                    transition={{ delay: 0.15 }}
                                    className="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm shadow-slate-200/50"
                                >
                                    <SectionHeading hint="Your step">Your Approval</SectionHeading>
                                    {approvalContext.approvalStatus !== 'pending' ? (
                                        <p className="text-sm text-slate-500">This approval has been processed.</p>
                                    ) : !approvalContext.canApprove ? (
                                        <p className="text-sm text-amber-700">This is not the active approval step yet.</p>
                                    ) : (
                                        <div className="space-y-3">
                                            <div>
                                                <label className="mb-1.5 block text-xs font-medium text-slate-700">Notes (optional)</label>
                                                <textarea
                                                    value={approvalNotes}
                                                    onChange={(event) => setApprovalNotes(event.target.value)}
                                                    rows={3}
                                                    placeholder="Add approval notes"
                                                    className="w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                                                />
                                            </div>
                                            <div className="grid grid-cols-2 gap-2 pt-1">
                                                <Button type="button" onClick={() => setShowApproveModal(true)} disabled={isDecisionSubmitting} className="bg-emerald-600 hover:bg-emerald-700 text-white">
                                                    {isDecisionSubmitting ? <Loader2 className="mr-2 h-3.5 w-3.5 animate-spin" /> : null}
                                                    Approve
                                                </Button>
                                                <Button type="button" onClick={() => setShowRejectModal(true)} disabled={isDecisionSubmitting} className="bg-red-600 hover:bg-red-700 text-white">
                                                    {isDecisionSubmitting ? <Loader2 className="mr-2 h-3.5 w-3.5 animate-spin" /> : null}
                                                    Reject
                                                </Button>
                                            </div>
                                        </div>
                                    )}
                                </motion.section>
                            )}

                            <motion.section
                                initial={{ opacity: 0, y: 12 }}
                                animate={{ opacity: 1, y: 0 }}
                                transition={{ delay: 0.2 }}
                                className="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm shadow-slate-200/50"
                            >
                                <StockRequestPipeline stockRequest={stockRequest} />
                            </motion.section>

                            <SidebarCard>
                                <SectionHeading>Request Actions</SectionHeading>
                                <div className="mt-4 divide-y divide-slate-100 text-sm">
                                    <a
                                        href={route('stock-requests.pdf-public', { stockRequest: stockRequest.id })}
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
                                    <a
                                        href={isApprovalView ? route('stock-approvals.index') : route('stock-requests.index')}
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

                            {stockRequest.offline_approval_document_path && permissions?.offlineApprovalDocument && (
                                <motion.section
                                    initial={{ opacity: 0, y: 12 }}
                                    animate={{ opacity: 1, y: 0 }}
                                    transition={{ delay: 0.25 }}
                                    className="space-y-4"
                                >
                                    <SectionHeading>Offline Approval Document</SectionHeading>
                                    <SupportingDocumentLink
                                        filename={stockRequest.offline_approval_document_name}
                                        url={getOfflineApprovalDocumentUrl()}
                                        className="rounded-lg border border-violet-100 bg-violet-50/60"
                                    />
                                </motion.section>
                            )}
                        </aside>
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
