import { formatDate } from '@/lib/formatters';
import type { StockRequest } from '@/types/purchasing';

export function StockRequestSummaryPanel({ stockRequest }: { stockRequest: StockRequest }) {
    const itemCount = stockRequest.items?.length || 0;
    const progress = stockRequest.approval_progress
        ? `${stockRequest.approval_progress.approved}/${stockRequest.approval_progress.total}`
        : `${stockRequest.approvals?.filter((approval) => approval.status === 'approved').length || 0}/${stockRequest.approvals?.length || 0}`;

    return (
        <section className="space-y-5">
            <div className="border-b border-slate-200 pb-3">
                <h2 className="text-sm font-semibold text-slate-950">Request Details</h2>
            </div>
            <dl className="grid gap-x-16 gap-y-4 lg:grid-cols-2">
                <div className="grid grid-cols-[10rem_minmax(0,1fr)] gap-x-6">
                    <dt className="text-sm text-slate-500">Request ID</dt>
                    <dd className="text-sm font-medium text-slate-950">{stockRequest.st_number}</dd>
                </div>
                <div className="grid grid-cols-[10rem_minmax(0,1fr)] gap-x-6">
                    <dt className="text-sm text-slate-500">Business Unit</dt>
                    <dd className="text-sm font-medium text-slate-950">{stockRequest.business_unit?.name || 'N/A'}</dd>
                </div>
                <div className="grid grid-cols-[10rem_minmax(0,1fr)] gap-x-6">
                    <dt className="text-sm text-slate-500">Request Type</dt>
                    <dd className="text-sm font-medium text-slate-950">Stock Request</dd>
                </div>
                <div className="grid grid-cols-[10rem_minmax(0,1fr)] gap-x-6">
                    <dt className="text-sm text-slate-500">Item Count</dt>
                    <dd className="text-sm font-medium text-slate-950">{itemCount}</dd>
                </div>
                <div className="grid grid-cols-[10rem_minmax(0,1fr)] gap-x-6">
                    <dt className="text-sm text-slate-500">Department</dt>
                    <dd className="text-sm font-medium text-slate-950">
                        {stockRequest.department?.name || 'N/A'}
                        {stockRequest.department?.code && (
                            <span className="ml-1.5 text-slate-400">({stockRequest.department.code})</span>
                        )}
                    </dd>
                </div>
                <div className="grid grid-cols-[10rem_minmax(0,1fr)] gap-x-6">
                    <dt className="text-sm text-slate-500">Current Step</dt>
                    <dd className="text-sm font-medium capitalize text-slate-950">{stockRequest.status.replace(/_/g, ' ')}</dd>
                </div>
                <div className="grid grid-cols-[10rem_minmax(0,1fr)] gap-x-6">
                    <dt className="text-sm text-slate-500">Requested By</dt>
                    <dd className="text-sm font-medium text-slate-950">{stockRequest.user?.name || 'N/A'}</dd>
                </div>
                <div className="grid grid-cols-[10rem_minmax(0,1fr)] gap-x-6">
                    <dt className="text-sm text-slate-500">Approval Progress</dt>
                    <dd className="text-sm font-medium text-slate-950">{progress}</dd>
                </div>
                <div className="grid grid-cols-[10rem_minmax(0,1fr)] gap-x-6">
                    <dt className="text-sm text-slate-500">Request Date</dt>
                    <dd className="text-sm font-medium text-slate-950">{formatDate(stockRequest.date_of_request)}</dd>
                </div>
                <div className="grid grid-cols-[10rem_minmax(0,1fr)] gap-x-6">
                    <dt className="text-sm text-slate-500">Purpose</dt>
                    <dd className="text-sm font-medium text-slate-950">{stockRequest.purpose || <span className="text-slate-400">Not specified</span>}</dd>
                </div>
                <div className="grid grid-cols-[10rem_minmax(0,1fr)] gap-x-6">
                    <dt className="text-sm text-slate-500">Expected Date</dt>
                    <dd className="text-sm font-medium text-slate-950">
                        {formatDate(stockRequest.expected_date) || <span className="text-slate-400">Not specified</span>}
                    </dd>
                </div>
            </dl>
        </section>
    );
}
