import { Head, Link } from '@inertiajs/react';

type ApprovalRow = {
    id: number;
    step_order: number;
    approval_type: string | null;
    task_type: string | null;
    status: string;
    assigned_at: string | null;
    responded_at: string | null;
    notes: string | null;
    stock_request: {
        id: number;
        st_number: string;
        purpose: string;
        status: string;
        requester: string;
        department: string;
        department_code: string | null;
    };
};

type Props = {
    pendingApprovals: {
        data: ApprovalRow[];
    };
    recentApprovals: ApprovalRow[];
    stats: {
        pending: number;
        approved: number;
        rejected: number;
        total: number;
    };
};

function fmtDate(value: string | null) {
    if (!value) return '-';
    return new Date(value).toLocaleString();
}

export default function StockApprovalIndex({ pendingApprovals, recentApprovals, stats }: Props) {
    return (
        <>
            <Head title="Stock Request Approvals" />

            <div className="p-6 space-y-6">
                <div>
                    <h1 className="text-2xl font-bold text-gray-900">Stock Request Approvals</h1>
                    <p className="text-sm text-gray-600 mt-1">Review and process stock request approval tasks assigned to you.</p>
                </div>

                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div className="rounded-xl border border-gray-200 bg-white p-4">
                        <p className="text-sm text-gray-500">Pending</p>
                        <p className="text-2xl font-bold text-gray-900 mt-1">{stats.pending}</p>
                    </div>
                    <div className="rounded-xl border border-gray-200 bg-white p-4">
                        <p className="text-sm text-gray-500">Approved</p>
                        <p className="text-2xl font-bold text-gray-900 mt-1">{stats.approved}</p>
                    </div>
                    <div className="rounded-xl border border-gray-200 bg-white p-4">
                        <p className="text-sm text-gray-500">Rejected</p>
                        <p className="text-2xl font-bold text-gray-900 mt-1">{stats.rejected}</p>
                    </div>
                    <div className="rounded-xl border border-gray-200 bg-white p-4">
                        <p className="text-sm text-gray-500">Total</p>
                        <p className="text-2xl font-bold text-gray-900 mt-1">{stats.total}</p>
                    </div>
                </div>

                <section className="rounded-xl border border-gray-200 bg-white overflow-hidden">
                    <div className="px-5 py-4 border-b border-gray-200">
                        <h2 className="text-base font-semibold text-gray-900">Pending Approvals</h2>
                    </div>
                    {pendingApprovals.data.length === 0 ? (
                        <p className="px-5 py-6 text-sm text-gray-600">No pending approvals.</p>
                    ) : (
                        <div className="divide-y divide-gray-200">
                            {pendingApprovals.data.map((row) => (
                                <div key={row.id} className="px-5 py-4">
                                    <div className="flex items-start justify-between gap-4">
                                        <div>
                                            <p className="text-sm font-semibold text-gray-900">{row.stock_request.st_number}</p>
                                            <p className="text-sm text-gray-600 mt-1">{row.stock_request.purpose}</p>
                                            <p className="text-xs text-gray-500 mt-2">
                                                Requester: {row.stock_request.requester} - {row.stock_request.department}
                                            </p>
                                            <p className="text-xs text-gray-500">Assigned: {fmtDate(row.assigned_at)}</p>
                                        </div>
                                        <Link
                                            href={route('stock-approvals.show', { approval: row.id })}
                                            className="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-700"
                                        >
                                            Review
                                        </Link>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </section>

                <section className="rounded-xl border border-gray-200 bg-white overflow-hidden">
                    <div className="px-5 py-4 border-b border-gray-200">
                        <h2 className="text-base font-semibold text-gray-900">Recent History</h2>
                    </div>
                    {recentApprovals.length === 0 ? (
                        <p className="px-5 py-6 text-sm text-gray-600">No approval history yet.</p>
                    ) : (
                        <div className="divide-y divide-gray-200">
                            {recentApprovals.map((row) => (
                                <div key={row.id} className="px-5 py-4">
                                    <div className="flex items-start justify-between gap-4">
                                        <div>
                                            <p className="text-sm font-semibold text-gray-900">{row.stock_request.st_number}</p>
                                            <p className="text-xs text-gray-500 mt-1">Status: {row.status}</p>
                                            <p className="text-xs text-gray-500">Processed: {fmtDate(row.responded_at)}</p>
                                            {row.notes && <p className="text-xs text-gray-600 mt-1">Notes: {row.notes}</p>}
                                        </div>
                                        <Link
                                            href={route('stock-approvals.show', { approval: row.id })}
                                            className="text-sm font-medium text-indigo-600 hover:text-indigo-700"
                                        >
                                            Details
                                        </Link>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </section>
            </div>
        </>
    );
}
