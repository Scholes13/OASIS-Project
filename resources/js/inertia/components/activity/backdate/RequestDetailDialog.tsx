import { CheckCircle, Clock, AlertCircle, XCircle } from 'lucide-react';

import { Badge } from '@/components/ui/Badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import type { Department, User } from '@/types';

export interface BackdatePermission {
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

export function BackdateStatusBadge({ status }: { status: BackdatePermission['status'] }) {
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

interface RequestDetailDialogProps {
    request: BackdatePermission | null;
    open: boolean;
    onClose: () => void;
    formatDate: (date: string) => string;
    formatDateTime: (date: string) => string;
}

export function RequestDetailDialog({
    request,
    open,
    onClose,
    formatDate,
    formatDateTime,
}: RequestDetailDialogProps) {
    return (
        <Dialog
            open={open}
            onClose={onClose}
            className="max-w-2xl"
        >
            <DialogHeader onClose={onClose}>
                <DialogTitle>Request Details</DialogTitle>
            </DialogHeader>
            <DialogContent>
                {request && (
                    <div className="space-y-4">
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <BackdateStatusBadge status={request.status} />
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Requested Date</label>
                            <p className="text-sm text-gray-900">{formatDate(request.requested_date)}</p>
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Reason</label>
                            <p className="text-sm text-gray-900">{request.reason}</p>
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Submitted</label>
                            <p className="text-sm text-gray-900">{formatDateTime(request.created_at)}</p>
                        </div>

                        {request.status === 'approved' && (
                            <>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Approved By</label>
                                    <p className="text-sm text-gray-900">{request.approver?.name ?? 'N/A'}</p>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Approved At</label>
                                    <p className="text-sm text-gray-900">
                                        {request.approved_at ? formatDateTime(request.approved_at) : 'N/A'}
                                    </p>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Granted Until</label>
                                    <p className="text-sm text-gray-900">
                                        {request.granted_until ? formatDateTime(request.granted_until) : 'N/A'}
                                    </p>
                                </div>
                            </>
                        )}

                        {request.status === 'rejected' && (
                            <>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Rejected By</label>
                                    <p className="text-sm text-gray-900">{request.rejector?.name ?? 'N/A'}</p>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Rejected At</label>
                                    <p className="text-sm text-gray-900">
                                        {request.rejected_at ? formatDateTime(request.rejected_at) : 'N/A'}
                                    </p>
                                </div>
                                {request.rejection_reason && (
                                    <div className="bg-red-50 border border-red-200 rounded-lg p-4">
                                        <label className="block text-sm font-medium text-red-800 mb-1">Rejection Reason</label>
                                        <p className="text-sm text-red-700">{request.rejection_reason}</p>
                                    </div>
                                )}
                            </>
                        )}
                    </div>
                )}
            </DialogContent>
            <DialogFooter>
                <Button variant="outline" onClick={onClose}>Close</Button>
            </DialogFooter>
        </Dialog>
    );
}
