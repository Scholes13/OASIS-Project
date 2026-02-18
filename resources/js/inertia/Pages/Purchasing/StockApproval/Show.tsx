import { Head, Link, useForm } from '@inertiajs/react';

type ApprovalStep = {
    id: number;
    step_order: number;
    approval_type: string | null;
    task_type: string | null;
    status: string;
    notes: string | null;
    responded_at: string | null;
    approver: {
        id: number;
        name: string;
        email: string;
    };
};

type Item = {
    id: number;
    item_name: string;
    specifications: string | null;
    quantity: number;
    unit: string;
};

type Props = {
    approval: {
        id: number;
        step_order: number;
        approval_type: string | null;
        task_type: string | null;
        status: string;
        notes: string | null;
        assigned_at: string | null;
        responded_at: string | null;
        approver: {
            id: number;
            name: string;
            email: string;
        };
        stock_request: {
            id: number;
            st_number: string;
            purpose: string;
            status: string;
            date_of_request: string | null;
            expected_date: string | null;
            submitted_at: string | null;
            user: {
                id: number;
                name: string;
                email: string;
            };
            department: {
                id: number;
                name: string;
                code: string;
            };
            business_unit: {
                id: number;
                name: string;
                code: string;
                logo: string | null;
            };
            items: Item[];
            approvals: ApprovalStep[];
        };
    };
    canApprove: boolean;
};

function fmtDate(value: string | null) {
    if (!value) return '-';
    return new Date(value).toLocaleString();
}

export default function StockApprovalShow({ approval, canApprove }: Props) {
    const { data, setData, post, processing, transform } = useForm({
        action: 'approve',
        notes: '',
    });

    const submitAction = (action: 'approve' | 'reject') => {
        transform((values) => ({
            ...values,
            action,
        }));
        post(route('stock-approvals.process', { approval: approval.id }), {
            preserveScroll: true,
        });
    };

    return (
        <>
            <Head title={`Stock Approval ${approval.stock_request.st_number}`} />

            <div className="p-6 space-y-6">
                <div className="flex items-start justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900">Stock Approval Detail</h1>
                        <p className="text-sm text-gray-600 mt-1">{approval.stock_request.st_number} - {approval.stock_request.purpose}</p>
                    </div>
                    <Link
                        href={route('stock-approvals.index')}
                        className="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50"
                    >
                        Back to List
                    </Link>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <section className="lg:col-span-2 rounded-xl border border-gray-200 bg-white p-5 space-y-4">
                        <h2 className="text-base font-semibold text-gray-900">Request Information</h2>
                        <dl className="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                            <div>
                                <dt className="text-gray-500">Requester</dt>
                                <dd className="text-gray-900 font-medium">{approval.stock_request.user.name}</dd>
                            </div>
                            <div>
                                <dt className="text-gray-500">Department</dt>
                                <dd className="text-gray-900 font-medium">{approval.stock_request.department.name}</dd>
                            </div>
                            <div>
                                <dt className="text-gray-500">Business Unit</dt>
                                <dd className="text-gray-900 font-medium">{approval.stock_request.business_unit.name}</dd>
                            </div>
                            <div>
                                <dt className="text-gray-500">Status</dt>
                                <dd className="text-gray-900 font-medium">{approval.stock_request.status}</dd>
                            </div>
                            <div>
                                <dt className="text-gray-500">Request Date</dt>
                                <dd className="text-gray-900 font-medium">{approval.stock_request.date_of_request ?? '-'}</dd>
                            </div>
                            <div>
                                <dt className="text-gray-500">Expected Date</dt>
                                <dd className="text-gray-900 font-medium">{approval.stock_request.expected_date ?? '-'}</dd>
                            </div>
                        </dl>

                        <div>
                            <h3 className="text-sm font-semibold text-gray-900 mb-2">Items</h3>
                            <div className="rounded-lg border border-gray-200 overflow-hidden">
                                <table className="min-w-full divide-y divide-gray-200 text-sm">
                                    <thead className="bg-gray-50">
                                        <tr>
                                            <th className="px-3 py-2 text-left font-semibold text-gray-700">Item</th>
                                            <th className="px-3 py-2 text-left font-semibold text-gray-700">Specification</th>
                                            <th className="px-3 py-2 text-left font-semibold text-gray-700">Qty</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-200 bg-white">
                                        {approval.stock_request.items.map((item) => (
                                            <tr key={item.id}>
                                                <td className="px-3 py-2 text-gray-900">{item.item_name}</td>
                                                <td className="px-3 py-2 text-gray-600">{item.specifications || '-'}</td>
                                                <td className="px-3 py-2 text-gray-900">{item.quantity} {item.unit}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </section>

                    <section className="rounded-xl border border-gray-200 bg-white p-5 space-y-4">
                        <h2 className="text-base font-semibold text-gray-900">Your Approval</h2>
                        <dl className="space-y-2 text-sm">
                            <div>
                                <dt className="text-gray-500">Step</dt>
                                <dd className="text-gray-900 font-medium">#{approval.step_order}</dd>
                            </div>
                            <div>
                                <dt className="text-gray-500">Type</dt>
                                <dd className="text-gray-900 font-medium">{approval.approval_type || approval.task_type || '-'}</dd>
                            </div>
                            <div>
                                <dt className="text-gray-500">Assigned</dt>
                                <dd className="text-gray-900 font-medium">{fmtDate(approval.assigned_at)}</dd>
                            </div>
                            <div>
                                <dt className="text-gray-500">Status</dt>
                                <dd className="text-gray-900 font-medium">{approval.status}</dd>
                            </div>
                        </dl>

                        {canApprove && approval.status === 'pending' ? (
                            <div className="space-y-3">
                                <label className="block text-sm font-medium text-gray-700" htmlFor="notes">Notes</label>
                                <textarea
                                    id="notes"
                                    value={data.notes}
                                    onChange={(e) => setData('notes', e.target.value)}
                                    rows={4}
                                    className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm"
                                    placeholder="Optional notes"
                                />

                                <div className="grid grid-cols-2 gap-3">
                                    <button
                                        type="button"
                                        onClick={() => submitAction('approve')}
                                        disabled={processing}
                                        className="inline-flex justify-center rounded-md bg-emerald-600 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-60"
                                    >
                                        Approve
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => submitAction('reject')}
                                        disabled={processing}
                                        className="inline-flex justify-center rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white hover:bg-red-700 disabled:opacity-60"
                                    >
                                        Reject
                                    </button>
                                </div>
                            </div>
                        ) : (
                            <p className="text-sm text-gray-600">This approval is not currently actionable.</p>
                        )}
                    </section>
                </div>

                <section className="rounded-xl border border-gray-200 bg-white p-5">
                    <h2 className="text-base font-semibold text-gray-900 mb-3">Approval Timeline</h2>
                    <div className="space-y-3">
                        {approval.stock_request.approvals.map((step) => (
                            <div key={step.id} className="rounded-lg border border-gray-200 px-4 py-3">
                                <div className="flex items-center justify-between gap-4">
                                    <div>
                                        <p className="text-sm font-semibold text-gray-900">Step #{step.step_order} - {step.approver.name}</p>
                                        <p className="text-xs text-gray-500 mt-1">{step.approval_type || step.task_type || '-'} - {step.status}</p>
                                        {step.notes && <p className="text-xs text-gray-600 mt-1">Notes: {step.notes}</p>}
                                    </div>
                                    <p className="text-xs text-gray-500">{fmtDate(step.responded_at)}</p>
                                </div>
                            </div>
                        ))}
                    </div>
                </section>
            </div>
        </>
    );
}
