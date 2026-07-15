import { useState, useEffect } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import { useForm } from '@inertiajs/react';
import { Clock, Plus, CheckCircle, AlertCircle, Eye } from 'lucide-react';
import { DataTable, type PaginationData } from '@/components/admin/DataTable';
import { StatCard } from '@/components/admin/StatCard';
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
import {
    BackdateStatusBadge,
    RequestDetailDialog,
    type BackdatePermission,
} from '@/components/activity/backdate/RequestDetailDialog';
import { cn } from '@/lib/utils';
import { formatDateTimeWib, formatDateWib } from '@/lib/activityDateTime';
import type { ColumnDef } from '@tanstack/react-table';
import type { PageProps } from '@/types';

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
        requested_date: '',
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
        return formatDateWib(dateString, {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
        }, 'id-ID');
    };

    const formatDateTime = (dateString: string) => {
        return formatDateTimeWib(dateString, {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        }, 'id-ID');
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
                            <div>
                                <label htmlFor="requested_date" className="block text-sm font-medium text-gray-700 mb-1">
                                    Earliest task date <span className="text-red-500">*</span>
                                </label>
                                <input
                                    id="requested_date"
                                    type="date"
                                    value={data.requested_date}
                                    max={new Date(Date.now() - 86400000).toISOString().slice(0, 10)}
                                    onChange={(event) => setData('requested_date', event.target.value)}
                                    className={cn(
                                        'w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-primary',
                                        errors.requested_date ? 'border-red-300' : 'border-gray-300'
                                    )}
                                />
                                {errors.requested_date && (
                                    <p className="mt-1 text-sm text-red-600">{errors.requested_date}</p>
                                )}
                            </div>

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
                        disabled={!data.requested_date || data.reason.trim().length < 10}
                        className="bg-primary hover:bg-blue-600 text-white"
                    >
                        Submit Request
                    </Button>
                </DialogFooter>
            </Dialog>

            <RequestDetailDialog
                request={selectedRequest}
                open={showDetailModal}
                onClose={() => {
                    setShowDetailModal(false);
                    setSelectedRequest(null);
                }}
                formatDate={formatDate}
                formatDateTime={formatDateTime}
            />
        </>
    );
}
