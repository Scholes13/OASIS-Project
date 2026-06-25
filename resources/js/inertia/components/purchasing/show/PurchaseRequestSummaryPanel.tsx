import { formatDate } from '@/lib/formatters';
import type { PurchaseRequest } from '@/types/purchasing';

interface PurchaseRequestSummaryPanelProps {
    purchaseRequest: PurchaseRequest;
}

export function PurchaseRequestSummaryPanel({ purchaseRequest }: PurchaseRequestSummaryPanelProps) {
    const itemCount = purchaseRequest.items?.length || 0;
    const progress = purchaseRequest.approval_progress
        ? `${purchaseRequest.approval_progress.approved}/${purchaseRequest.approval_progress.total}`
        : `${purchaseRequest.approvals?.filter((approval) => approval.status === 'approved').length || 0}/${purchaseRequest.approvals?.length || 0}`;

    return (
        <section className="space-y-5">
            <div className="border-b border-slate-200 pb-3">
                <h2 className="text-sm font-semibold text-slate-950">Request Details</h2>
            </div>
            <dl className="grid gap-x-16 gap-y-4 lg:grid-cols-2">
                <div className="grid grid-cols-[10rem_minmax(0,1fr)] gap-x-6">
                    <dt className="text-sm text-slate-500">Request ID</dt>
                    <dd className="text-sm font-medium text-slate-950">{purchaseRequest.pr_number}</dd>
                </div>
                <div className="grid grid-cols-[10rem_minmax(0,1fr)] gap-x-6">
                    <dt className="text-sm text-slate-500">Business Unit</dt>
                    <dd className="text-sm font-medium text-slate-950">{purchaseRequest.business_unit?.name || 'N/A'}</dd>
                </div>
                <div className="grid grid-cols-[10rem_minmax(0,1fr)] gap-x-6">
                    <dt className="text-sm text-slate-500">Request Type</dt>
                    <dd className="text-sm font-medium text-slate-950">Purchase Request</dd>
                </div>
                <div className="grid grid-cols-[10rem_minmax(0,1fr)] gap-x-6">
                    <dt className="text-sm text-slate-500">Item Count</dt>
                    <dd className="text-sm font-medium text-slate-950">{itemCount}</dd>
                </div>
                <div className="grid grid-cols-[10rem_minmax(0,1fr)] gap-x-6">
                    <dt className="text-sm text-slate-500">Department</dt>
                    <dd className="text-sm font-medium text-slate-950">
                        {purchaseRequest.department?.name || 'N/A'}
                        {purchaseRequest.department?.code && (
                            <span className="ml-1.5 text-slate-400">({purchaseRequest.department.code})</span>
                        )}
                    </dd>
                </div>
                <div className="grid grid-cols-[10rem_minmax(0,1fr)] gap-x-6">
                    <dt className="text-sm text-slate-500">Current Step</dt>
                    <dd className="text-sm font-medium capitalize text-slate-950">{purchaseRequest.status.replace(/_/g, ' ')}</dd>
                </div>
                <div className="grid grid-cols-[10rem_minmax(0,1fr)] gap-x-6">
                    <dt className="text-sm text-slate-500">Requested By</dt>
                    <dd className="text-sm font-medium text-slate-950">{purchaseRequest.user?.name || 'N/A'}</dd>
                </div>
                <div className="grid grid-cols-[10rem_minmax(0,1fr)] gap-x-6">
                    <dt className="text-sm text-slate-500">Approval Progress</dt>
                    <dd className="text-sm font-medium text-slate-950">{progress}</dd>
                </div>
                <div className="grid grid-cols-[10rem_minmax(0,1fr)] gap-x-6">
                    <dt className="text-sm text-slate-500">Request Date</dt>
                    <dd className="text-sm font-medium text-slate-950">{formatDate(purchaseRequest.date_of_request)}</dd>
                </div>
                <div className="grid grid-cols-[10rem_minmax(0,1fr)] gap-x-6">
                    <dt className="text-sm text-slate-500">Purpose</dt>
                    <dd className="text-sm font-medium text-slate-950">{purchaseRequest.used_for || <span className="text-slate-400">Not specified</span>}</dd>
                </div>
                <div className="grid grid-cols-[10rem_minmax(0,1fr)] gap-x-6">
                    <dt className="text-sm text-slate-500">Expected Date</dt>
                    <dd className="text-sm font-medium text-slate-950">
                        {formatDate(purchaseRequest.expected_date) || <span className="text-slate-400">Not specified</span>}
                    </dd>
                </div>
            </dl>
        </section>
    );
}
