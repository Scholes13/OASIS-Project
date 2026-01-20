import React from 'react';
import { PRApproval } from '@/types/purchasing';
import { Check, X, Clock, User } from 'lucide-react';
import { format, parseISO } from 'date-fns';

/**
 * ApprovalTimeline Component
 * 
 * Displays the approval workflow timeline for a Purchase Request with visual indicators
 * for each approval step's status (pending, approved, rejected).
 * 
 * Features:
 * - Visual timeline with connecting lines between steps
 * - Status icons (check, x, clock) with color-coded backgrounds
 * - Approver name and step order
 * - Timestamps for completed approvals
 * - Notes/comments from approvers
 * - Due dates for pending approvals
 * 
 * @component
 * @example
 * ```tsx
 * <ApprovalTimeline approvals={purchaseRequest.approvals} />
 * ```
 */

interface ApprovalTimelineProps {
    approvals: PRApproval[];
}

export function ApprovalTimeline({ approvals }: ApprovalTimelineProps) {
    if (!approvals || approvals.length === 0) {
        return (
            <div className="text-center py-8">
                <Clock className="w-12 h-12 text-gray-300 mx-auto mb-3" />
                <p className="text-sm text-gray-500">No approval workflow configured</p>
            </div>
        );
    }

    const getStatusIcon = (status: string) => {
        switch (status) {
            case 'approved':
                return (
                    <div className="w-8 h-8 rounded-full bg-emerald-100 flex items-center justify-center">
                        <Check className="w-4 h-4 text-emerald-600" />
                    </div>
                );
            case 'rejected':
                return (
                    <div className="w-8 h-8 rounded-full bg-red-100 flex items-center justify-center">
                        <X className="w-4 h-4 text-red-600" />
                    </div>
                );
            case 'pending':
                return (
                    <div className="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center">
                        <Clock className="w-4 h-4 text-gray-400" />
                    </div>
                );
            default:
                return (
                    <div className="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center">
                        <User className="w-4 h-4 text-gray-400" />
                    </div>
                );
        }
    };

    const getStatusText = (status: string) => {
        switch (status) {
            case 'approved':
                return 'Approved';
            case 'rejected':
                return 'Rejected';
            case 'pending':
                return 'Pending';
            default:
                return 'Unknown';
        }
    };

    const getStatusColor = (status: string) => {
        switch (status) {
            case 'approved':
                return 'text-emerald-600';
            case 'rejected':
                return 'text-red-600';
            case 'pending':
                return 'text-gray-500';
            default:
                return 'text-gray-500';
        }
    };

    return (
        <div className="space-y-0">
            {approvals.map((approval, index) => (
                <div key={approval.id} className="flex items-start gap-3 pb-6 last:pb-0">
                    {/* Step Indicator */}
                    <div className="flex-shrink-0 relative">
                        {getStatusIcon(approval.status)}
                        
                        {/* Connecting Line */}
                        {index < approvals.length - 1 && (
                            <div className="absolute top-8 left-4 w-0.5 h-full bg-gray-200 -ml-px" />
                        )}
                    </div>

                    {/* Content */}
                    <div className="flex-1 min-w-0 pt-0.5">
                        <div className="flex items-start justify-between gap-2">
                            <div className="flex-1 min-w-0">
                                <p className="text-sm font-medium text-gray-900 truncate">
                                    {approval.approver?.name || 'Unknown Approver'}
                                </p>
                                <p className="text-xs text-gray-500 mt-0.5">
                                    Step {approval.step_order}
                                </p>
                            </div>
                            <span className={`text-xs font-medium ${getStatusColor(approval.status)}`}>
                                {getStatusText(approval.status)}
                            </span>
                        </div>

                        {/* Timestamp */}
                        {approval.responded_at && (
                            <p className="text-xs text-gray-400 mt-1">
                                {format(parseISO(approval.responded_at), 'MMM dd, yyyy HH:mm')}
                            </p>
                        )}

                        {/* Notes */}
                        {approval.notes && (
                            <div className="mt-2 p-2 bg-gray-50 rounded text-xs text-gray-600">
                                {approval.notes}
                            </div>
                        )}

                        {/* Due Date (for pending approvals) */}
                        {approval.status === 'pending' && approval.due_date && (
                            <p className="text-xs text-amber-600 mt-1">
                                Due: {format(parseISO(approval.due_date), 'MMM dd, yyyy')}
                            </p>
                        )}
                    </div>
                </div>
            ))}
        </div>
    );
}
