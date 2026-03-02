import { useState, useEffect } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import { useForm } from '@inertiajs/react';
import { Clock, Plus, CheckCircle, XCircle, AlertCircle, Eye } from 'lucide-react';
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
    approver?: User;
    rejector?: User;
    department?: Department;
}

interface BackdateRequestsProps extends PageProps {
    requests: {
        data: BackdatePermission[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number;
        to: number;
    };
    activePermission: BackdatePermission | null;
    hasPendingRequest: boolean;
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

// Countdown Timer Component
function CountdownTimer({ grantedUntil }: { grantedUntil: string }) {
    const [countdown, setCountdown] = useState('');

    useEffect(() => {
        const updateCountdown = () => {
            const now = new Date();
            const end = new Date(grantedUntil);
            const diff = end.getTime() - now.getTime();

            if (diff <= 0) {
                setCountdown('Expired');
                return;
            }

            const hours = Math.floor(diff / (1000 * 60 * 60));
            const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((diff % (1000 * 60)) / 1000);

            setCountdown(`${hours}h ${minutes}m ${seconds}s`);
        };

        updateCountdown();
        const interval = setInterval(updateCountdown, 1000);
        return () => clearInterval(interval);
    }, [grantedUntil]);

    return <strong>{countdown}</strong>;
}

export default function Requests({ requests, activePermission, hasPendingRequest }: BackdateRequestsProps) {
    const { flash } = usePage<PageProps>().props;
    const [showRequestModal, setShowRequestModal] = useState(false);
    const [showDetailModal, setShowDetailModal] = useState(false);
    const [selectedRequest, setSelectedRequest] = useState<BackdatePermission | null>(null);

    // Form for submitting new request
    const { data, setData, post, processing, errors, reset } = useForm({
        reason: '',
    });

    // Handle flash messages
    useEffect(() => {
        if (flash.success) toast.success(flash.success);
        if (flash.error) toast.error(flash.error);
    }, [flash]);

    // Submit request handler
    const handleSubmitRequest = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('activity.backdate.request.submit'), {
            onSuccess: () => {
                setShowRequestModal(false);
                reset();
            },
        });
    };

    // View detail handler
    const handleViewDetail = (request: BackdatePermission) => {
        setSelectedRequest(request);
        setShowDetailModal(true);
    };

    // Page change handler
    const handlePageChange = (page: number) => {
        router.get(route('activity.backdate.requests'), { page }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

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

    // Table columns
    const columns: ColumnDef<BackdatePermission>[] = [
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
                <div className="max-w-xs truncate" title={row.original.reason}>
                    {row.original.reason}
                </div>
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
                <span className="text-gray-500">
                    {formatDateTime(row.original.created_at)}
                </span>
            ),
        },
        {
            id: 'actions',
            header: 'Actions',
            cell: ({ row }) => (
                <Button
                    variant="ghost"
                    size="sm"
                    onClick={() => handleViewDetail(row.original)}
                    className="text-primary hover:text-primary"
                >
                    <Eye className="w-4 h-4 mr-1" />
                    View Details
                </Button>
            ),
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

    // Check if active permission is still valid
    const isActivePermissionValid = activePermission && 
        activePermission.status === 'approved' && 
        activePermission.granted_until && 
        new Date(activePermission.granted_until) > new Date();

    return (
        <>
            <Head title="Backdate Requests" />

            <div className="w-full px-6 py-6 lg:px-8">
                {/* Page Header */}
                <div className="mb-6">
                    <h1 className="text-2xl font-bold text-gray-900">Backdate Requests</h1>
                    <p className="mt-1 text-sm text-gray-600">
                        Request permission to enter tasks with older dates
                    </p>
                </div>

                {/* Active Permission Alert */}
                {isActivePermissionValid && activePermission && (
                    <div className="mb-6 bg-emerald-50 border border-emerald-200 rounded-lg p-4">
                        <div className="flex items-start">
                            <div className="flex-shrink-0">
                                <CheckCircle className="w-5 h-5 text-emerald-600" />
                            </div>
                            <div className="ml-3 flex-1">
                                <h3 className="text-sm font-medium text-emerald-800">
                                    Active Backdate Permission
                                </h3>
                                    <div className="mt-2 text-sm text-emerald-700">
                                        <p>
                                            You can backdate tasks up to{' '}
                                            <strong>{formatDate(activePermission.requested_date)}</strong>
                                        </p>
                                        <p className="mt-1">
                                            Time remaining:{' '}
                                            <CountdownTimer grantedUntil={activePermission.granted_until!} />
                                        </p>
                                        <p className="mt-1 text-xs">
                                            Expires at: {formatDateTime(activePermission.granted_until!)}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    )}

                    {/* Request Button */}
                    <div className="mb-6">
                        <Button
                            onClick={() => setShowRequestModal(true)}
                            disabled={hasPendingRequest}
                            className="bg-primary hover:bg-blue-600 text-white"
                        >
                            <Plus className="w-4 h-4 mr-2" />
                            Request Backdate Access
                        </Button>
                        {hasPendingRequest && (
                            <p className="mt-2 text-sm text-amber-600">
                                You have a pending request. Please wait for approval.
                            </p>
                        )}
                    </div>

                    {/* Requests Table */}
                    <div className="bg-white rounded-xl border border-gray-100 overflow-hidden">
                        <div className="px-5 py-4 border-b border-gray-100">
                            <h3 className="text-base font-semibold text-gray-900">Request History</h3>
                        </div>
                        <DataTable
                            data={requests.data}
                            columns={columns}
                            pagination={pagination}
                            onPageChange={handlePageChange}
                            emptyMessage="No requests yet. Get started by requesting backdate access."
                        />
                    </div>
                </div>

            {/* Request Form Modal */}
            <Dialog open={showRequestModal} onClose={() => setShowRequestModal(false)}>
                <DialogHeader onClose={() => setShowRequestModal(false)}>
                    <DialogTitle>Request Backdate Access</DialogTitle>
                    <DialogDescription>
                        Request permission to enter tasks with older dates. Your department head will
                        review and approve your request.
                    </DialogDescription>
                </DialogHeader>
                <DialogContent>
                    <form onSubmit={handleSubmitRequest}>
                        <div className="space-y-4">
                            {/* Reason */}
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Reason <span className="text-red-500">*</span>
                                </label>
                                <textarea
                                    value={data.reason}
                                    onChange={(e) => setData('reason', e.target.value)}
                                    rows={4}
                                    placeholder="Explain why you need backdate access..."
                                    className={cn(
                                        'w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-primary',
                                        errors.reason ? 'border-red-300' : 'border-gray-300'
                                    )}
                                />
                                {errors.reason && (
                                    <p className="mt-1 text-sm text-red-600">{errors.reason}</p>
                                )}
                                <div className="mt-1 flex justify-between text-xs text-gray-500">
                                    <span>Minimum 10 characters</span>
                                    <span>{data.reason.trim().length} characters</span>
                                </div>
                            </div>

                            {/* Info Box */}
                            <div className="bg-blue-50 border border-blue-200 rounded-lg p-3">
                                <div className="flex">
                                    <div className="flex-shrink-0">
                                        <AlertCircle className="h-5 w-5 text-blue-400" />
                                    </div>
                                    <div className="ml-3">
                                        <p className="text-sm text-blue-700">
                                            Once approved, you'll be able to enter tasks with dates older
                                            than yesterday. The permission will be valid until the end of
                                            the approval day.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </DialogContent>
                <DialogFooter>
                    <Button
                        variant="outline"
                        onClick={() => {
                            setShowRequestModal(false);
                            reset();
                        }}
                    >
                        Cancel
                    </Button>
                    <Button
                        onClick={handleSubmitRequest}
                        loading={processing}
                        disabled={data.reason.trim().length < 10}
                        className="bg-primary hover:bg-blue-600 text-white"
                    >
                        Submit Request
                    </Button>
                </DialogFooter>
            </Dialog>

            {/* Request Detail Modal */}
            <Dialog
                open={showDetailModal}
                onClose={() => {
                    setShowDetailModal(false);
                    setSelectedRequest(null);
                }}
                className="max-w-2xl"
            >
                <DialogHeader
                    onClose={() => {
                        setShowDetailModal(false);
                        setSelectedRequest(null);
                    }}
                >
                    <DialogTitle>Request Details</DialogTitle>
                </DialogHeader>
                <DialogContent>
                    {selectedRequest && (
                        <div className="space-y-4">
                            {/* Status */}
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Status
                                </label>
                                <BackdateStatusBadge status={selectedRequest.status} />
                            </div>

                            {/* Requested Date */}
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Requested Date
                                </label>
                                <p className="text-sm text-gray-900">
                                    {formatDate(selectedRequest.requested_date)}
                                </p>
                            </div>

                            {/* Reason */}
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Reason
                                </label>
                                <p className="text-sm text-gray-900">{selectedRequest.reason}</p>
                            </div>

                            {/* Submitted */}
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Submitted
                                </label>
                                <p className="text-sm text-gray-900">
                                    {formatDateTime(selectedRequest.created_at)}
                                </p>
                            </div>

                            {/* Approved Info */}
                            {selectedRequest.status === 'approved' && (
                                <>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Approved By
                                        </label>
                                        <p className="text-sm text-gray-900">
                                            {selectedRequest.approver?.name ?? 'N/A'}
                                        </p>
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Approved At
                                        </label>
                                        <p className="text-sm text-gray-900">
                                            {selectedRequest.approved_at
                                                ? formatDateTime(selectedRequest.approved_at)
                                                : 'N/A'}
                                        </p>
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Granted Until
                                        </label>
                                        <p className="text-sm text-gray-900">
                                            {selectedRequest.granted_until
                                                ? formatDateTime(selectedRequest.granted_until)
                                                : 'N/A'}
                                        </p>
                                    </div>
                                </>
                            )}

                            {/* Rejected Info */}
                            {selectedRequest.status === 'rejected' && (
                                <>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Rejected By
                                        </label>
                                        <p className="text-sm text-gray-900">
                                            {selectedRequest.rejector?.name ?? 'N/A'}
                                        </p>
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Rejected At
                                        </label>
                                        <p className="text-sm text-gray-900">
                                            {selectedRequest.rejected_at
                                                ? formatDateTime(selectedRequest.rejected_at)
                                                : 'N/A'}
                                        </p>
                                    </div>
                                    {selectedRequest.rejection_reason && (
                                        <div className="bg-red-50 border border-red-200 rounded-lg p-4">
                                            <label className="block text-sm font-medium text-red-800 mb-1">
                                                Rejection Reason
                                            </label>
                                            <p className="text-sm text-red-700">
                                                {selectedRequest.rejection_reason}
                                            </p>
                                        </div>
                                    )}
                                </>
                            )}
                        </div>
                    )}
                </DialogContent>
                <DialogFooter>
                    <Button
                        variant="outline"
                        onClick={() => {
                            setShowDetailModal(false);
                            setSelectedRequest(null);
                        }}
                    >
                        Close
                    </Button>
                </DialogFooter>
            </Dialog>
        </>
    );
}
