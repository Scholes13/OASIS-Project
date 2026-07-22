import { CheckCircle2, Circle } from 'lucide-react';
import { formatDateTime } from '@/lib/formatters';
import type { STShowProps } from '@/types/purchasing';

type StockRequest = STShowProps['stockRequest'];

function getSteps(stockRequest: StockRequest) {
    const approvalDone = Boolean(stockRequest.approved_at || ['ga_review', 'ready_for_purchasing', 'done'].includes(stockRequest.status));
    const gaDone = ['ready_for_purchasing', 'done'].includes(stockRequest.status);
    const purchasingActive = ['ready_for_purchasing', 'done'].includes(stockRequest.status);
    const approvedApprovals = stockRequest.approvals?.filter((approval) => approval.status === 'approved') || [];
    const lastApproval = approvedApprovals[approvedApprovals.length - 1];
    const totalSteps = stockRequest.approvals?.length || 0;
    const purchasingTask = stockRequest.admin_task;
    const routesDirectlyToPurchasing = stockRequest.routes_directly_to_purchasing;
    const approvalTime = totalSteps === 0
        ? 'Pending'
        : approvalDone && lastApproval
            ? formatDateTime(lastApproval.responded_at)
            : 'In progress';
    const purchasingTime = purchasingTask?.status === 'done'
        ? formatDateTime(purchasingTask.completed_at)
        : purchasingTask?.status === 'in_progress'
            ? `In progress${purchasingTask.started_at ? ` since ${formatDateTime(purchasingTask.started_at)}` : ''}`
            : purchasingTask?.assigned_admin ? 'Claimed' : purchasingActive ? 'Awaiting claim' : 'Pending';
    const purchasingState = purchasingTask?.status === 'done'
        ? 'done'
        : purchasingTask?.status === 'in_progress' || purchasingActive
            ? 'active'
            : 'pending';

    return [
        {
            title: 'Request Initiated',
            actor: stockRequest.user?.name || 'Requester',
            time: formatDateTime(stockRequest.submitted_at || stockRequest.created_at),
            state: 'done',
        },
        ...(!routesDirectlyToPurchasing ? [{
            title: 'Department Approval',
            actor: approvalDone && lastApproval
                ? lastApproval.metadata?.approver_snapshot?.name || lastApproval.approver?.name || 'Approver'
                : 'Department approval',
            time: approvalTime,
            state: approvalDone ? 'done' : 'active',
        }] : []),
        ...(!stockRequest.skips_ga_review ? [{
            title: 'General Affairs Review',
            actor: gaDone ? stockRequest.ga_reviewer?.name || 'GA reviewer' : 'General Affairs',
            time: gaDone
                ? formatDateTime(stockRequest.ga_reviewed_at)
                : stockRequest.status === 'ga_review' ? 'In progress' : 'Pending',
            state: gaDone ? 'done' : stockRequest.status === 'ga_review' ? 'active' : 'pending',
        }] : []),
        {
            title: 'Purchasing Follow-up',
            actor: purchasingTask?.assigned_admin?.name || 'Purchasing team',
            time: purchasingTime,
            state: purchasingState,
        },
        {
            title: 'Done',
            actor: 'Completed',
            time: purchasingTask?.status === 'done' && purchasingTask.completed_at
                ? formatDateTime(purchasingTask.completed_at)
                : 'Pending',
            state: stockRequest.status === 'done' || purchasingTask?.status === 'done' ? 'done' : 'pending',
        },
    ];
}

export function StockRequestSidebarSummary({ stockRequest }: { stockRequest: StockRequest }) {
    const itemCount = stockRequest.items?.length || 0;
    const approvedSteps = stockRequest.approvals?.filter((approval) => approval.status === 'approved').length || 0;
    const totalSteps = stockRequest.approvals?.length || 0;
    const approvalProgress = totalSteps > 0 ? Math.round((approvedSteps / totalSteps) * 40) : 40;
    const progress = stockRequest.status === 'done' || stockRequest.admin_task?.status === 'done'
        ? 100
        : stockRequest.status === 'ready_for_purchasing' ? 80
            : stockRequest.status === 'ga_review' ? 60
                : ['approved', 'ga_rejected'].includes(stockRequest.status) ? 40
                    : stockRequest.status === 'in_approval' ? approvalProgress
                        : stockRequest.status === 'submitted' ? 20 : 0;

    return (
        <section className="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm shadow-slate-200/50">
            <h2 className="text-xs font-semibold uppercase tracking-wider text-slate-500">Request Summary</h2>
            <dl className="mt-4 space-y-3 text-sm">
                <div className="flex items-center justify-between gap-3"><dt className="text-slate-500">Request ID</dt><dd className="font-medium text-slate-950">{stockRequest.st_number}</dd></div>
                <div className="flex items-center justify-between gap-3"><dt className="text-slate-500">Items</dt><dd className="font-medium text-slate-950">{itemCount}</dd></div>
                <div className="flex items-center justify-between gap-3"><dt className="text-slate-500">Current Step</dt><dd className="font-medium capitalize text-blue-700">{stockRequest.status.replace(/_/g, ' ')}</dd></div>
                <div className="flex items-center justify-between gap-3"><dt className="text-slate-500">Progress</dt><dd className="font-medium text-slate-950">{progress}%</dd></div>
            </dl>
            <div className="mt-4 h-2 rounded-full bg-slate-100"><div className="h-2 rounded-full bg-blue-600" style={{ width: `${progress}%` }} /></div>
        </section>
    );
}

export function StockRequestProcessBar({ stockRequest }: { stockRequest: StockRequest }) {
    const steps = getSteps(stockRequest);

    return (
        <section className="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm shadow-slate-200/50">
            <div className="flex items-baseline justify-between"><h2 className="text-xs font-semibold uppercase tracking-wider text-slate-500">Process Overview</h2><span className="text-xs text-slate-400">Workflow</span></div>
            <ol className={`mt-4 grid gap-3 ${steps.length === 3 ? 'lg:grid-cols-3' : steps.length === 4 ? 'lg:grid-cols-4' : 'lg:grid-cols-5'}`}>
                {steps.map((step, index) => {
                    const isDone = step.state === 'done';
                    const isActive = step.state === 'active';
                    const Icon = isDone ? CheckCircle2 : Circle;

                    return (
                        <li key={step.title} className="relative min-w-0">
                            {index < steps.length - 1 && <div className="absolute left-9 right-[-1rem] top-3.5 hidden h-px border-t border-dashed border-slate-200 lg:block" />}
                            <div className="relative flex items-start gap-3">
                                <div className={`flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-full ${isDone ? 'bg-emerald-50' : isActive ? 'bg-blue-600' : 'bg-slate-100'}`}><Icon className={`h-4 w-4 ${isDone ? 'text-emerald-600' : isActive ? 'text-white' : 'text-slate-300'}`} /></div>
                                <div className="min-w-0"><p className={`text-sm font-semibold ${isDone || isActive ? 'text-slate-950' : 'text-slate-400'}`}>{step.title}</p><div className="mt-1 space-y-0.5 text-xs leading-5 text-slate-500"><p>{step.actor}</p><p className={isActive ? 'text-blue-700' : ''}>{step.time}</p></div></div>
                            </div>
                        </li>
                    );
                })}
            </ol>
        </section>
    );
}

export function StockRequestPipeline({ stockRequest }: { stockRequest: StockRequest }) {
    const steps = getSteps(stockRequest);

    return (
        <div className="space-y-4">
            <div className="flex items-baseline justify-between"><h2 className="text-xs font-semibold uppercase tracking-wider text-slate-500">Approval Progress</h2><span className="text-xs text-slate-400">Pipeline</span></div>
            <ol className="relative space-y-4">
                {steps.map((step, index) => {
                    const isDone = step.state === 'done';
                    const isActive = step.state === 'active';
                    const Icon = isDone ? CheckCircle2 : Circle;

                    return (
                        <li key={step.title} className="relative grid grid-cols-[1.75rem_minmax(0,1fr)] gap-3">
                            {index < steps.length - 1 && <div className="absolute left-3.5 top-7 h-[calc(100%_+_0.5rem)] w-px bg-slate-200" />}
                            <div className={`relative z-10 flex h-7 w-7 items-center justify-center rounded-full ${isDone ? 'bg-emerald-50' : isActive ? 'bg-blue-50' : 'bg-slate-100'}`}><Icon className={`h-4 w-4 ${isDone ? 'text-emerald-600' : isActive ? 'text-blue-600' : 'text-slate-300'}`} /></div>
                            <div className="min-w-0 rounded-xl bg-white/70 px-3 py-2.5 shadow-sm shadow-slate-200/40 ring-1 ring-slate-200/70"><div className="flex items-start justify-between gap-3"><p className={`text-sm font-medium ${isDone || isActive ? 'text-slate-950' : 'text-slate-400'}`}>{step.title}</p><span className={`rounded-full px-2 py-0.5 text-xs font-medium ${isDone ? 'bg-emerald-50 text-emerald-700' : isActive ? 'bg-blue-50 text-blue-700' : 'bg-slate-100 text-slate-400'}`}>{isDone ? 'Done' : isActive ? 'In progress' : 'Pending'}</span></div><p className="mt-1 truncate text-xs text-slate-500" title={`${step.actor} · ${step.time}`}>{step.actor} · {step.time}</p></div>
                        </li>
                    );
                })}
            </ol>
        </div>
    );
}
