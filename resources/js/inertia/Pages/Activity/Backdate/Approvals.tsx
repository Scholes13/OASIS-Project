import { useState, useEffect } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import { useForm } from '@inertiajs/react';
import { Clock, CheckCircle, XCircle, AlertCircle, Check, X } from 'lucide-react';
import { DataTable, type PaginationData } from '@/components/admin/DataTable';
import { StatCard } from '@/components/admin/StatCard';
import { Badge } from '@/components/ui/Badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogHeader,
    DialogTitle,
    DialogDescription,
    DialogContent,
    DialogFooter,
} from '@/components/ui/dialog';
import { toast } from '@/components/ui/toast';
import { cn } from '@/lib/utils';
import type { ColumnDef } from '@tanstack/react-table';
import type { PageProps, User, Department } from '@/types';

// Types
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
    updated_at: string;
    user?: User;
    approver?: User;
    rejector?: User;
    department?: Department;
}

interface BackdateApprovalsProps extends PageProps {
    requests: {
        data: BackdatePermission[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number;
        to: number;
    };
    pendingCount: number;
    statusFilter: string;
}

// Status Badge Component
function BackdateStatusBadge({ status }: { status: BackdatePermission['status'] }) {
    const config = {
        pending: { variant: 'warning' as const, icon: Clock, label: 'Pending' },
        approved: { variant: 'success' as const, icon: CheckCircle, label: 'Approved' },
        rejected: { variant: 'danger' as const, icon: XCircle, label: 'Rejected' },
        expired: { variant: 'default' as const, icon: AlertCircle, label: 'Expired' },
    };

    const { variant, icon: Icon, label } = config[status] || config.pending;

    return (
        <Badge variant={variant} className="inline-flex items-center gap-1">
            <Icon className="w-3.5 h-3.5" />
            {label}
        </Badge>
    );
}

// User Avatar Component
function UserAvatar({ name, email }: { name: string; email?: string }) {
    const initials = name
        .split(' ')
        .map((n) => n[0])
        .join('')
        .substring(0, 2)
        .toUpperCase();

    return (
        <div className="flex items-center">
            <div className="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center text-xs font-medium text-white">
                {initials}
            </div>
            <div className="ml-3">
                <p className="text-sm font-medium text-gray-900">{name}</p>
                {email && <p className="text-xs text-gray-500">{email}</p>}
            </div>
        </div>
    );
}

export default function Approvals({ requests, pendingCount, statusFilter: initialStatusFilter }: BackdateApprovalsProps) {
    const { flash } = usePage<PageProps>().props;
    const [statusFilter, setStatusFilter] = useState(initialStatusFilter || 'pending');
    const [showRejectModal, setShowRejectModal] = useState(false);
    const [selectedRequest, setSelectedRequest] = useState<BackdatePermission | null>(null);
    const [processingId, setProcessingId] = useState<number | null>(null);

    // Form for rejection
    const { data, setData, post, processing, errors, reset } = useForm({
        rejection_reason: '',
    });

    // Handle flash messages
    useEffect(() => {
        if (flash.success) toast.success(flash.success);
        if (flash.error) toast.error(flash.error);
    }, [flash]);

    // Format date helper
    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString('en-GB', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
        });
    };

    const formatDateTime = (dateString: string) => {
        return new Date(dateString).toLocaleString('en-GB', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    // Filter change handler
    const handleFilterChange = (filter: string) => {
        setStatusFilter(filter);
        router.get(
            route('activity.backdate.approvals'),
            { status: filter },
            { preserveState: true, preserveScroll: true }
        );
    };

    // Page change handler
    const handlePageChange = (page: number) => {
        router.get(
            route('activity.backdate.approvals'),
            { page, status: statusFilter },
            { preserveState: true, preserveScroll: true }
        );
    };

    // Approve handler
    const handleApprove = (requestId: number) => {
        setProcessingId(requestId);
        router.post(
            route('activity.backdate.approve', { id: requestId }),
            {},
            {
                preserveScroll: true,
                onFinish: () => setProcessingId(null),
            }
        );
    };

    // Open reject modal
    const handleOpenRejectModal = (request: BackdatePermission) => {
        setSelectedRequest(request);
        reset();
        setShowRejectModal(true);
    };

    // Submit rejection
    const handleReject = (e: React.FormEvent) => {
        e.preventDefault();
        if (!selectedRequest) return;

        post(route('activity.backdate.reject', { id: selectedRequest.id }), {
            onSuccess: () => {
                setShowRejectModal(false);
                setSelectedRequest(null);
                reset();
            },
        });
    };

    // Table columns
    const columns: ColumnDef<BackdatePermission>[] = [
        {
            accessorKey: 'user',
            header: 'Requester',
            cell: ({ row }) => (
                <UserAvatar
                    name={row.original.user?.name || 'Unknown'}
                    email={row.original.user?.email}
                />
            ),
        },
        {
            accessorKey: 'department',
            header: 'Department',
            cell: ({ row }) => (
                <span className="text-sm text-gray-900">
                    {row.original.department?.name || 'N/A'}
                </span>
            ),
        },
        {
            accessorKey: 'requested_date',
            header: 'Requested Date',
            cell: ({ row }) => (
                <span className="font-medium text-gray-900">
                    {formatDate(row.original.requested_date)}
                </span>
            ),
        },
        {
            accessorKey: 'reason',
            header: 'Reason',
            cell: ({ row }) => (
                <p
                    className="text-sm text-gray-900 max-w-xs truncate"
                    title={row.original.reason}
                >
                    {row.original.reason}
                </p>
            ),
        },
        {
            accessorKey: 'status',
            header: 'Status',
            cell: ({ row }) => <BackdateStatusBadge status={row.original.status} />,
        },
        {
            accessorKey: 'created_at',
            header: 'Submitted',
            cell: ({ row }) => (
                <span className="text-sm text-gray-500">
                    {formatDateTime(row.original.created_at)}
                </span>
            ),
        },
        {
            id: 'actions',
            header: 'Actions',
            cell: ({ row }) => {
                const request = row.original;
                const isProcessing = processingId === request.id;

                if (request.status === 'pending') {
                    return (
                        <div className="flex items-center gap-2">
                            <Button
                                size="sm"
                                onClick={() => handleApprove(request.id)}
                                disabled={isProcessing}
                                className="bg-emerald-600 hover:bg-emerald-700 text-white"
                            >
                                <Check className="w-4 h-4 mr-1" />
                                Approve
                            </Button>
                            <Button
                                size="sm"
                                variant="destructive"
                                onClick={() => handleOpenRejectModal(request)}
                                disabled={isProcessing}
                            >
                                <X className="w-4 h-4 mr-1" />
                                Reject
                            </Button>
                        </div>
                    );
                }

                if (request.status === 'approved') {
                    return (
                        <div className="text-sm text-gray-500">
                            <p>Approved by {request.approver?.name}</p>
                            <p className="text-xs">{formatDateTime(request.approved_at!)}</p>
                            {request.granted_until && (
                                <p className="text-xs text-emerald-600 font-medium">
                                    Valid until: {formatDateTime(request.granted_until)}
                                </p>
                            )}
                        </div>
                    );
                }

                if (request.status === 'rejected') {
                    return (
                        <div className="text-sm text-gray-500">
                            <p>Rejected by {request.rejector?.name}</p>
                            <p className="text-xs">{formatDateTime(request.rejected_at!)}</p>
                            {request.rejection_reason && (
                                <p
                                    className="text-xs text-red-600 mt-1 max-w-xs truncate"
                                    title={request.rejection_reason}
                                >
                                    Reason: {request.rejection_reason}
                                </p>
                            )}
                        </div>
                    );
                }

                return null;
            },
        },
    ];

    // Pagination data
    const pagination: PaginationData = {
        current_page: requests.current_page,
        last_page: requests.last_page,
        per_page: requests.per_page,
        total: requests.total,
        from: requests.from,
        to: requests.to,
    };

    // Filter buttons config
    const filterButtons = [
        { value: 'pending', label: 'Pending', activeClass: 'bg-amber-100 text-amber-700' },
        { value: 'approved', label: 'Approved', activeClass: 'bg-emerald-100 text-emerald-700' },
        { value: 'rejected', label: 'Rejected', activeClass: 'bg-red-100 text-red-700' },
        { value: 'all', label: 'All', activeClass: 'bg-blue-50 text-blue-700' },
    ];

    return (
        <>
            <Head title="Backdate Approvals" />

            <div className="w-full px-6 py-6 lg:px-8">
                {/* Header */}
                <div className="mb-6">
                    <h1 className="text-2xl font-bold text-gray-900">Backdate Approvals</h1>
                    <p className="mt-1 text-sm text-gray-600">
                        Review and manage backdate permission requests from your team
                    </p>
                </div>

                {/* Stats Card */}
                <div className="mb-6">
                    <StatCard
                        title="Pending Requests"
                        value={pendingCount}
                        icon={Clock}
                        color="indigo"
                    />
                </div>

                {/* Filters */}
                <div className="bg-white rounded-xl border border-gray-100 p-4 mb-6">
                    <div className="flex items-center gap-4">
                        <label className="text-sm font-medium text-gray-700">
                            Filter by Status:
                        </label>
                        <div className="flex gap-2">
                            {filterButtons.map((filter) => (
                                <button
                                    key={filter.value}
                                    onClick={() => handleFilterChange(filter.value)}
                                    className={cn(
                                        'px-3 py-1.5 rounded-lg text-sm font-medium transition-colors',
                                        statusFilter === filter.value
                                            ? filter.activeClass
                                            : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                                    )}
                                >
                                    {filter.label}
                                </button>
                            ))}
                        </div>
                    </div>
                </div>

                {/* Requests Table */}
                <DataTable
                    data={requests.data}
                    columns={columns}
                    pagination={pagination}
                    onPageChange={handlePageChange}
                    emptyMessage={
                        statusFilter === 'pending'
                            ? 'There are no pending backdate requests at the moment.'
                            : 'No requests match the selected filter.'
                    }
                />
            </div>

            {/* Reject Modal */}
            <Dialog open={showRejectModal} onClose={() => setShowRejectModal(false)}>
                <DialogHeader onClose={() => setShowRejectModal(false)}>
                    <div className="flex items-start">
                        <div className="flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                            <X className="h-6 w-6 text-red-600" />
                        </div>
                        <div className="ml-4">
                            <DialogTitle>Reject Backdate Request</DialogTitle>
                        </div>
                    </div>
                </DialogHeader>
                <DialogContent>
                    {selectedRequest && (
                        <form onSubmit={handleReject}>
                            <div className="space-y-4">
                                <p className="text-sm text-gray-500">
                                    You are about to reject the backdate request from{' '}
                                    <strong>{selectedRequest.user?.name}</strong> for date{' '}
                                    <strong>{formatDate(selectedRequest.requested_date)}</strong>.
                                </p>
                                <p className="text-sm text-gray-500">
                                    <strong>Reason:</strong> {selectedRequest.reason}
                                </p>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Rejection Reason <span className="text-red-600">*</span>
                                    </label>
                                    <textarea
                                        value={data.rejection_reason}
                                        onChange={(e) => setData('rejection_reason', e.target.value)}
                                        rows={4}
                                        className={cn(
                                            'w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500',
                                            errors.rejection_reason
                                                ? 'border-red-300'
                                                : 'border-gray-300'
                                        )}
                                        placeholder="Please provide a clear reason for rejecting this request..."
                                    />
                                    {errors.rejection_reason && (
                                        <p className="mt-1 text-sm text-red-600">
                                            {errors.rejection_reason}
                                        </p>
                                    )}
                                </div>
                            </div>
                        </form>
                    )}
                </DialogContent>
                <DialogFooter>
                    <Button
                        variant="outline"
                        onClick={() => {
                            setShowRejectModal(false);
                            setSelectedRequest(null);
                            reset();
                        }}
                    >
                        Cancel
                    </Button>
                    <Button
                        variant="destructive"
                        onClick={handleReject}
                        loading={processing}
                    >
                        <X className="w-4 h-4 mr-2" />
                        Reject Request
                    </Button>
                </DialogFooter>
            </Dialog>
        </>
    );
}
