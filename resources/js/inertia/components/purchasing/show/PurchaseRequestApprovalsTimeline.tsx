import { motion } from 'framer-motion';
import { Check, Clock, X } from 'lucide-react';
import { Badge } from '@/components/ui/Badge';
import { formatDateTime } from '@/lib/formatters';
import type { PurchaseRequest } from '@/types/purchasing';

interface PurchaseRequestApprovalsTimelineProps {
    purchaseRequest: PurchaseRequest;
}

export function PurchaseRequestApprovalsTimeline({ purchaseRequest }: PurchaseRequestApprovalsTimelineProps) {
    return (
        <>
            <motion.div initial={{ opacity: 0, x: 20 }} animate={{ opacity: 1, x: 0 }} transition={{ delay: 0.2 }} className="bg-white rounded-xl border border-gray-100 overflow-hidden">
                <div className="px-5 py-4 border-b border-gray-100">
                    <h3 className="text-base font-semibold text-gray-900">Approval Progress</h3>
                </div>
                <div className="p-5">
                    {purchaseRequest.approvals && purchaseRequest.approvals.length > 0 ? (
                        <div className="space-y-0">
                            {purchaseRequest.approvals.map((approval, index) => (
                                <motion.div key={approval.id} initial={{ opacity: 0, x: -10 }} animate={{ opacity: 1, x: 0 }} transition={{ delay: 0.3 + index * 0.1 }} className="flex items-start gap-3 pb-6 last:pb-0">
                                    <div className="flex-shrink-0 relative">
                                        {approval.status === 'approved' ? (
                                            <div className="w-8 h-8 rounded-full bg-emerald-100 flex items-center justify-center">
                                                <Check className="w-4 h-4 text-emerald-600" />
                                            </div>
                                        ) : approval.status === 'rejected' ? (
                                            <div className="w-8 h-8 rounded-full bg-red-100 flex items-center justify-center">
                                                <X className="w-4 h-4 text-red-600" />
                                            </div>
                                        ) : (
                                            <div className="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center">
                                                <Clock className="w-4 h-4 text-gray-400" />
                                            </div>
                                        )}
                                        {index < purchaseRequest.approvals!.length - 1 && <div className="absolute top-8 left-4 w-0.5 h-6 bg-gray-200" />}
                                    </div>

                                    <div className="flex-1 min-w-0">
                                        <div className="flex items-start justify-between">
                                            <div className="flex-1">
                                                <p className="text-sm font-medium text-gray-900">{approval.approver?.name || 'Unknown'}</p>
                                                <p className="text-xs text-gray-500 mt-0.5">{approval.approver?.email || ''}</p>
                                            </div>
                                            <Badge variant={approval.status === 'approved' ? 'success' : approval.status === 'rejected' ? 'danger' : 'default'} className="ml-2">
                                                {approval.status === 'approved' ? 'Approved' : approval.status === 'rejected' ? 'Rejected' : 'Pending'}
                                            </Badge>
                                        </div>
                                        {approval.responded_at && <p className="text-xs text-gray-400 mt-1">{formatDateTime(approval.responded_at)}</p>}
                                        {approval.notes && <p className="text-xs text-gray-600 mt-2 p-2 bg-gray-50 rounded">{approval.notes}</p>}
                                    </div>
                                </motion.div>
                            ))}
                        </div>
                    ) : (
                        <div className="text-center py-6">
                            <div className="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                                <Check className="w-6 h-6 text-gray-400" />
                            </div>
                            <p className="text-sm text-gray-500">No approval workflow</p>
                            <p className="text-xs text-gray-400 mt-1">Submit this PR to start approval</p>
                        </div>
                    )}
                </div>
            </motion.div>

            <motion.div initial={{ opacity: 0, x: 20 }} animate={{ opacity: 1, x: 0 }} transition={{ delay: 0.3 }} className="bg-white rounded-xl border border-gray-100 overflow-hidden">
                <div className="px-5 py-4 border-b border-gray-100">
                    <h3 className="text-base font-semibold text-gray-900">Timeline</h3>
                </div>
                <div className="p-5 space-y-3">
                    <TimelineRow label="Created" value={formatDateTime(purchaseRequest.created_at)} />
                    {purchaseRequest.submitted_at && <TimelineRow label="Submitted" value={formatDateTime(purchaseRequest.submitted_at)} />}
                    {purchaseRequest.approved_at && <TimelineRow label="Approved" value={formatDateTime(purchaseRequest.approved_at)} labelClassName="text-emerald-600" />}
                    {purchaseRequest.rejected_at && <TimelineRow label="Rejected" value={formatDateTime(purchaseRequest.rejected_at)} labelClassName="text-red-600" />}
                    {purchaseRequest.voided_at && <TimelineRow label="Voided" value={formatDateTime(purchaseRequest.voided_at)} />}
                    {purchaseRequest.offline_approved_at && (
                        <>
                            <TimelineRow label="Offline Approved" value={formatDateTime(purchaseRequest.offline_approved_at)} labelClassName="text-purple-600" />
                            {purchaseRequest.offline_approval_notes && (
                                <div className="text-sm">
                                    <span className="text-gray-500">Notes:</span>
                                    <p className="text-gray-700 mt-1 p-2 bg-gray-50 rounded text-xs">{purchaseRequest.offline_approval_notes}</p>
                                </div>
                            )}
                        </>
                    )}
                </div>
            </motion.div>
        </>
    );
}

function TimelineRow({ label, value, labelClassName = 'text-gray-500' }: { label: string; value: string; labelClassName?: string }) {
    return (
        <div className="flex items-center justify-between text-sm">
            <span className={labelClassName}>{label}</span>
            <span className="text-gray-900">{value}</span>
        </div>
    );
}
