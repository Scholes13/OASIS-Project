import type React from 'react';
import { motion } from 'framer-motion';
import { CheckCircle2, Circle } from 'lucide-react';
import { formatDateTime } from '@/lib/formatters';
import type { PurchaseRequest } from '@/types/purchasing';

interface PurchaseRequestApprovalsTimelineProps {
    purchaseRequest: PurchaseRequest;
}

export function PurchaseRequestApprovalsTimeline({ purchaseRequest }: PurchaseRequestApprovalsTimelineProps) {
    const steps = getPurchaseRequestApprovalSteps(purchaseRequest);

    return (
        <motion.div initial={{ opacity: 0, x: 20 }} animate={{ opacity: 1, x: 0 }} transition={{ delay: 0.2 }} className="space-y-4">
            <SectionHeading hint="Pipeline">Approval Progress</SectionHeading>
            <ol className="relative space-y-4">
                {steps.map((step, index) => {
                    const isDone = step.state === 'done';
                    const isActive = step.state === 'active';
                    const Icon = isDone ? CheckCircle2 : Circle;

                    return (
                        <li key={step.title} className="relative grid grid-cols-[1.75rem_minmax(0,1fr)] gap-3">
                            {index < steps.length - 1 && <div className="absolute left-3.5 top-7 h-[calc(100%_+_0.5rem)] w-px bg-slate-200" />}
                            <div className={`relative z-10 flex h-7 w-7 items-center justify-center rounded-full ${isDone ? 'bg-emerald-50' : isActive ? 'bg-blue-50' : 'bg-slate-100'}`}>
                                <Icon className={`h-4 w-4 ${isDone ? 'text-emerald-600' : isActive ? 'text-blue-600' : 'text-slate-300'}`} />
                            </div>
                            <div className="min-w-0 rounded-xl bg-white/70 px-3 py-2.5 shadow-sm shadow-slate-200/40 ring-1 ring-slate-200/70">
                                <div className="flex items-start justify-between gap-3">
                                    <p className={`text-sm font-medium ${isDone || isActive ? 'text-slate-950' : 'text-slate-400'}`}>{step.title}</p>
                                    <span className={`rounded-full px-2 py-0.5 text-xs font-medium ${isDone ? 'bg-emerald-50 text-emerald-700' : isActive ? 'bg-blue-50 text-blue-700' : 'bg-slate-100 text-slate-400'}`}>
                                        {isDone ? 'Done' : isActive ? 'In progress' : 'Pending'}
                                    </span>
                                </div>
                                <p className="mt-1 truncate text-xs text-slate-500" title={`${step.actor} · ${step.time}`}>{step.actor} · {step.time}</p>
                            </div>
                        </li>
                    );
                })}
            </ol>
        </motion.div>
    );
}

function SectionHeading({ children, hint }: { children: React.ReactNode; hint?: string }) {
    return (
        <div className="flex items-baseline justify-between">
            <h2 className="text-xs font-semibold uppercase tracking-wider text-slate-500">{children}</h2>
            {hint && <span className="text-xs text-slate-400">{hint}</span>}
        </div>
    );
}

function getPurchaseRequestApprovalSteps(purchaseRequest: PurchaseRequest) {
    const approvals = purchaseRequest.approvals || [];
    const allApproved = approvals.length > 0 && approvals.every((approval) => approval.status === 'approved');
    const requestedAt = formatDateTime(purchaseRequest.submitted_at || purchaseRequest.created_at);
    const adminTask = purchaseRequest.admin_task;
    const purchasingFollowUp = adminTask
        ? {
            title: 'Purchasing Follow-up',
            actor: adminTask.assigned_admin?.name || 'Purchasing team',
            time: adminTask.completed_at ? formatDateTime(adminTask.completed_at) : adminTask.started_at ? 'In progress' : 'Pending',
            state: adminTask.status === 'done' ? 'done' : adminTask.status === 'in_progress' ? 'active' : 'pending',
        }
        : {
            title: 'Purchasing Follow-up',
            actor: 'Purchasing team',
            time: allApproved ? 'In progress' : 'Pending',
            state: allApproved ? 'active' : 'pending',
        };
    const doneStep = adminTask?.status === 'done'
        ? {
            title: 'Done',
            actor: 'Goods received',
            time: adminTask.completed_at ? formatDateTime(adminTask.completed_at) : 'Done',
            state: 'done',
        }
        : {
            title: 'Done',
            actor: 'Goods received',
            time: 'Pending',
            state: 'pending',
        };
    const approvalStage = (index: number, pendingLabel: string) => {
        const approval = approvals[index];

        if (!approval) {
            return {
                title: pendingLabel,
                actor: pendingLabel,
                time: 'Pending',
                state: 'pending',
            };
        }

        return {
            title: pendingLabel,
            actor: approval.approver?.name || pendingLabel,
            time: approval.status === 'approved' || approval.status === 'rejected'
                ? formatDateTime(approval.responded_at)
                : 'In progress',
            state: approval.status === 'approved' ? 'done' : 'active',
        };
    };

    return [
        {
            title: 'Request Initiated',
            actor: purchaseRequest.user?.name || 'Requester',
            time: requestedAt,
            state: 'done',
        },
        approvalStage(0, 'Internal Department'),
        approvalStage(1, 'Purchasing Approval'),
        approvalStage(2, 'Management / BOD'),
        purchasingFollowUp,
        doneStep,
    ];
}
