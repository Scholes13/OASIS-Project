import { useState, useEffect } from 'react';
import { Head, Link, router, usePage, useForm } from '@inertiajs/react';
import { ArrowLeft, Clock, CheckCircle, XCircle, AlertCircle, Check, X } from 'lucide-react';
import { DataTable, type PaginationData } from '@/components/admin/DataTable';
import { Badge } from '@/components/ui/Badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogHeader,
    DialogTitle,
    DialogContent,
    DialogFooter,
} from '@/components/ui/dialog';
import { toast } from '@/components/ui/toast';
import { cn } from '@/lib/utils';
import type { ColumnDef } from '@tanstack/react-table';
import type { PageProps, User, Department } from '@/types';

interface BackdatePermission {
    id: number;
    user_id: number;
    department_id: number;
    business_unit_id: number;
    requested_date: string;
    reason: string;
    status: 'pending' | 'approved' | 'rejected' | 'expired';
    approved_by: number | null;
    approved_at: string | null;
    rejected_by: number | null;
    rejected_at: string | null;
    rejection_reason: string | null;
    granted_until: string | null;
    created_at: string;
    requester?: User;
    approver?: User;
    rejector?: User;
    department?: Department;
}

interface Props extends PageProps {
    requests: {
        data: BackdatePermission[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number;
        to: number;
    };
    filters: { status: string };
}

function StatusBadge({ status }: { status: string }) {
    const cfg: Record<string, { variant: 'warning' | 'success' | 'danger' | 'default'; icon: React.ElementType; label: string }> = {
        pending: { variant: 'warning', icon: Clock, label: 'Pending' },
        approved: { variant: 'success', icon: CheckCircle, label: 'Approved' },
        rejected: { variant: 'danger', icon: XCircle, label: 'Rejected' },
        expired: { variant: 'default', icon: AlertCircle, label: 'Expired' },
    };
    const { variant, icon: Icon, label } = cfg[status] || cfg.pending;
    return (
        <Badge variant={variant} className="inline-flex items-center gap-1">
            <Icon className="w-3.5 h-3.5" />
            {label}
        </Badge>
    );
}

const formatDate = (d: string) => new Date(d).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
const formatDateTime = (d: string) => new Date(d).toLocaleString('en-GB', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });

export default function BackdateApprovals({ requests, filters }: Props) {
    const { flash } = usePage<PageProps>().props;
    const [statusFilter, setStatusFilter] = useState(filters.status || 'pending');
    const [showRejectModal, setShowRejectModal] = useState(false);
    const [selectedRequest, setSelectedRequest] = useState<BackdatePermission | null>(null);
    const [processingId, setProcessingId] = useState<number | null>(null);

    const { data, setData, post, processing, errors, reset } = useForm({ reason: '' });

    useEffect(() => {
        if (flash.success) toast.success(flash.success);
        if (flash.error) toast.error(flash.error);
    }, [flash]);

    const handleFilterChange = (filter: string) => {
        setStatusFilter(filter);
        router.get(route('activity.admin.backdate.approvals'), { status: filter }, { preserveState: true, preserveScroll: true });
    };

    const handlePageChange = (page: number) => {
        router.get(route('activity.admin.backdate.approvals'), { page, status: statusFilter }, { preserveState: true, preserveScroll: true });
    };

    const handleApprove = (id: number) => {
        setProcessingId(id);
        router.post(route('activity.admin.backdate.approve', { id }), {}, {
            preserveScroll: true,
            onFinish: () => setProcessingId(null),
        });
    };

    const handleReject = (e: React.FormEvent) => {
        e.preventDefault();
        if (!selectedRequest) return;
        post(route('activity.admin.backdate.reject', { id: selectedRequest.id }), {
            onSuccess: () => { setShowRejectModal(false); setSelectedRequest(null); reset(); },
        });
    };

    const columns: ColumnDef<BackdatePermission>[] = [
        {
            accessorKey: 'requester',
            header: 'HOD / Requester',
            cell: ({ row }) => {
                const name = row.original.requester?.name || 'Unknown';
                return (
                    <div className="flex items-center gap-2">
                        <div className="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center text-xs font-medium text-white">
                            {name.charAt(0).toUpperCase()}
                        </div>
                        <div>
                            <p className="text-sm font-medium text-gray-900">{name}</p>
                            <p className="text-xs text-gray-500">{row.original.requester?.email}</p>
                        </div>
                    </div>
                );
            },
        },
        {
            accessorKey: 'department',
            header: 'Department',
            cell: ({ row }) => <span className="text-sm text-gray-900">{row.original.department?.name || '-'}</span>,
        },
        {
            accessorKey: 'requested_date',
            header: 'Requested Date',
            cell: ({ row }) => <span className="font-medium text-gray-900 text-sm">{formatDate(row.original.requested_date)}</span>,
        },
        {
            accessorKey: 'reason',
            header: 'Reason',
            cell: ({ row }) => <p className="text-sm text-gray-900 max-w-xs truncate" title={row.original.reason}>{row.original.reason}</p>,
        },
        {
            accessorKey: 'status',
            header: 'Status',
            cell: ({ row }) => <StatusBadge status={row.original.status} />,
        },
        {
            accessorKey: 'created_at',
            header: 'Submitted',
            cell: ({ row }) => <span className="text-sm text-gray-500">{formatDateTime(row.original.created_at)}</span>,
        },
        {
            id: 'actions',
            header: 'Actions',
            cell: ({ row }) => {
                const req = row.original;
                if (req.status !== 'pending') return null;
                return (
                    <div className="flex items-center gap-2">
                        <Button size="sm" onClick={() => handleApprove(req.id)} disabled={processingId === req.id} className="bg-emerald-600 hover:bg-emerald-700 text-white">
                            <Check className="w-4 h-4 mr-1" /> Approve
                        </Button>
                        <Button size="sm" variant="destructive" onClick={() => { setSelectedRequest(req); reset(); setShowRejectModal(true); }} disabled={processingId === req.id}>
                            <X className="w-4 h-4 mr-1" /> Reject
                        </Button>
                    </div>
                );
            },
        },
    ];

    const pagination: PaginationData = {
        current_page: requests.current_page,
        last_page: requests.last_page,
        per_page: requests.per_page,
        total: requests.total,
        from: requests.from,
        to: requests.to,
    };

    const filterButtons = [
        { value: 'pending', label: 'Pending', activeClass: 'bg-amber-100 text-amber-700' },
        { value: 'approved', label: 'Approved', activeClass: 'bg-emerald-100 text-emerald-700' },
        { value: 'rejected', label: 'Rejected', activeClass: 'bg-red-100 text-red-700' },
        { value: 'all', label: 'All', activeClass: 'bg-blue-50 text-blue-700' },
    ];

    return (
        <>
            <Head title="HOD Backdate Approvals" />
            <div className="w-full px-6 py-6 lg:px-8">
                <div className="flex items-center gap-4 mb-6">
                    <Link href={route('activity.admin.dashboard')}>
                        <Button variant="ghost" size="sm"><ArrowLeft className="w-4 h-4 mr-1" strokeWidth={1.5} /> Back</Button>
                    </Link>
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900">HOD Backdate Approvals</h1>
                        <p className="mt-1 text-sm text-gray-600">Review backdate requests dari HOD / Team Leader</p>
                    </div>
                </div>

                <div className="bg-white rounded-xl border border-gray-100 p-4 mb-6">
                    <div className="flex items-center gap-4">
                        <label className="text-sm font-medium text-gray-700">Filter:</label>
                        <div className="flex gap-2">
                            {filterButtons.map((f) => (
                                <button key={f.value} onClick={() => handleFilterChange(f.value)}
                                    className={cn('px-3 py-1.5 rounded-lg text-sm font-medium transition-colors',
                                        statusFilter === f.value ? f.activeClass : 'bg-gray-100 text-gray-600 hover:bg-gray-200')}>
                                    {f.label}
                                </button>
                            ))}
                        </div>
                    </div>
                </div>

                <DataTable data={requests.data} columns={columns} pagination={pagination} onPageChange={handlePageChange}
                    emptyMessage={statusFilter === 'pending' ? 'Tidak ada pending backdate request dari HOD.' : 'Tidak ada request yang cocok.'} />
            </div>

            <Dialog open={showRejectModal} onClose={() => setShowRejectModal(false)}>
                <DialogHeader onClose={() => setShowRejectModal(false)}>
                    <div className="flex items-start">
                        <div className="flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                            <X className="h-6 w-6 text-red-600" />
                        </div>
                        <div className="ml-4"><DialogTitle>Reject Backdate Request</DialogTitle></div>
                    </div>
                </DialogHeader>
                <DialogContent>
                    {selectedRequest && (
                        <form onSubmit={handleReject}>
                            <div className="space-y-4">
                                <p className="text-sm text-gray-500">
                                    Reject request dari <strong>{selectedRequest.requester?.name}</strong> untuk tanggal <strong>{formatDate(selectedRequest.requested_date)}</strong>.
                                </p>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Alasan Penolakan <span className="text-red-600">*</span></label>
                                    <textarea value={data.reason} onChange={(e) => setData('reason', e.target.value)} rows={4}
                                        className={cn('w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500',
                                            errors.reason ? 'border-red-300' : 'border-gray-300')}
                                        placeholder="Berikan alasan penolakan..." />
                                    {errors.reason && <p className="mt-1 text-sm text-red-600">{errors.reason}</p>}
                                </div>
                            </div>
                        </form>
                    )}
                </DialogContent>
                <DialogFooter>
                    <Button variant="outline" onClick={() => { setShowRejectModal(false); setSelectedRequest(null); reset(); }}>Cancel</Button>
                    <Button variant="destructive" onClick={handleReject} loading={processing}><X className="w-4 h-4 mr-2" /> Reject</Button>
                </DialogFooter>
            </Dialog>
        </>
    );
}
