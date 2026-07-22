import { CheckCircle2, Circle } from 'lucide-react';
import { formatDateTime } from '@/lib/formatters';
import type { PRShowProps } from '@/types/purchasing';

type PurchaseRequest = PRShowProps['purchaseRequest'];

function getSteps(purchaseRequest: PurchaseRequest) {
    const approvals = purchaseRequest.approvals || [];
    const allApproved = approvals.length > 0 && approvals.every((approval) => approval.status === 'approved');
    const requestedAt = formatDateTime(purchaseRequest.submitted_at || purchaseRequest.created_at);
    const requester = purchaseRequest.user?.name || 'Requester';
    const approvalStage = (index: number, pendingLabel: string) => {
        const approval = approvals[index];

        if (!approval) {
            return {
                actor: pendingLabel,
                time: 'Pending',
                state: 'pending',
            };
        }

        if (approval.status === 'approved') {
            return {
                actor: approval.approver?.name || 'Approver',
                time: formatDateTime(approval.responded_at),
                state: 'done',
            };
        }

        if (approval.status === 'rejected') {
            return {
                actor: approval.approver?.name || 'Approver',
                time: formatDateTime(approval.responded_at),
                state: 'active',
            };
        }

        return {
            actor: approval.approver?.name || pendingLabel,
            time: 'In progress',
            state: 'active',
        };
    };
    const internalApproval = approvalStage(0, 'Internal Department');
    const purchasingApproval = approvalStage(1, 'Purchasing Approval');
    const managementApproval = approvalStage(2, 'Management / BOD');
    const adminTask = purchaseRequest.admin_task;
    const purchasingFollowUp = adminTask
        ? {
            actor: adminTask.status === 'pending_followup' ? 'Purchasing team' : adminTask.assigned_admin?.name || 'Purchasing team',
            time: adminTask.completed_at ? formatDateTime(adminTask.completed_at) : adminTask.started_at ? 'In progress' : 'Pending',
            state: adminTask.status === 'done' ? 'done' : adminTask.status === 'in_progress' ? 'active' : 'pending',
        }
        : {
            actor: 'Purchasing team',
            time: allApproved ? 'In progress' : 'Pending',
            state: allApproved ? 'active' : 'pending',
        };
    const doneStep = adminTask?.status === 'done'
        ? {
            actor: 'Goods received',
            time: adminTask.completed_at ? formatDateTime(adminTask.completed_at) : 'Done',
            state: 'done',
        }
        : {
            actor: 'Goods received',
            time: 'Pending',
            state: 'pending',
        };

    return [
        { title: 'Request Initiated', actor: requester, time: requestedAt, state: 'done' },
        { title: 'Internal Department', ...internalApproval },
        { title: 'Purchasing Approval', ...purchasingApproval },
        { title: 'Management / BOD', ...managementApproval },
        { title: 'Purchasing Follow-up', ...purchasingFollowUp },
        { title: 'Done', ...doneStep },
    ];
}

export function PurchaseRequestProcessBar({ purchaseRequest }: { purchaseRequest: PurchaseRequest }) {
    const steps = getSteps(purchaseRequest);

    return (
        <section className="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm shadow-slate-200/50">
            <div className="flex items-baseline justify-between">
                <h2 className="text-xs font-semibold uppercase tracking-wider text-slate-500">Process Overview</h2>
                <span className="text-xs text-slate-400">Workflow</span>
            </div>
            <ol className="mt-4 grid gap-3 lg:grid-cols-6">
                {steps.map((step) => {
                    const isDone = step.state === 'done';
                    const isActive = step.state === 'active';
                    const Icon = isDone ? CheckCircle2 : Circle;

                    return (
                        <li key={step.title} className="relative min-w-0">
                            <div className="relative flex items-start gap-3">
                                <div className={`flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-full ${isDone ? 'bg-emerald-50' : isActive ? 'bg-blue-600' : 'bg-slate-100'}`}>
                                    <Icon className={`h-4 w-4 ${isDone ? 'text-emerald-600' : isActive ? 'text-white' : 'text-slate-300'}`} />
                                </div>
                                <div className="min-w-0">
                                    <p className={`text-sm font-semibold ${isDone || isActive ? 'text-slate-950' : 'text-slate-400'}`}>{step.title}</p>
                                    <div className="mt-1 space-y-0.5 text-xs leading-5 text-slate-500">
                                        <p>{step.actor}</p>
                                        <p className={isActive ? 'text-blue-700' : ''}>{step.time}</p>
                                    </div>
                                </div>
                            </div>
                        </li>
                    );
                })}
            </ol>
        </section>
    );
}
