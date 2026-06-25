import { Link } from '@inertiajs/react';
import { Ban, Check, Download, Edit, Loader2, RotateCcw, Send, Shield, X } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { PR_STATUS_CONFIG } from '@/lib/purchasingConstants';
import type { PRShowProps } from '@/types/purchasing';

type PurchaseRequestPermissions = NonNullable<PRShowProps['can']>;

interface PurchaseRequestHeaderProps {
    purchaseRequest: PRShowProps['purchaseRequest'];
    permissions: Partial<PurchaseRequestPermissions>;
    isResendingEmail: boolean;
    onApprove: () => void;
    onReject: () => void;
    onResubmit: () => void;
    onResendApprovalEmail: () => void;
    onMarkOfflineApproved: () => void;
    onVoid: () => void;
}

export function PurchaseRequestHeader({
    purchaseRequest,
    permissions,
    isResendingEmail,
    onApprove,
    onReject,
    onResubmit,
    onResendApprovalEmail,
    onMarkOfflineApproved,
    onVoid,
}: PurchaseRequestHeaderProps) {
    const currentStatus = PR_STATUS_CONFIG[purchaseRequest.status as keyof typeof PR_STATUS_CONFIG] || PR_STATUS_CONFIG.draft;
    const StatusIcon = currentStatus.icon || Edit;

    return (
        <header className="px-9 pb-2 pt-3">
            <div className="flex items-start justify-between gap-6">
                <div className="min-w-0">
                    <nav className="mb-3 flex items-center gap-2 text-xs font-medium text-slate-500" aria-label="Breadcrumb">
                        <Link href={route('purchase-requests.index')} className="hover:text-slate-800">Purchase Request</Link>
                        <span className="text-slate-300">/</span>
                        <span className="truncate text-slate-800">{purchaseRequest.pr_number}</span>
                    </nav>
                    <div className="flex flex-wrap items-center gap-2.5">
                        <h1 className="text-2xl font-semibold tracking-tight text-slate-950">Purchase Request Approval</h1>
                        <span className={`inline-flex items-center gap-1 rounded-md px-2.5 py-0.5 text-xs font-medium ${currentStatus.bg} ${currentStatus.text}`}>
                            <StatusIcon className="h-3 w-3" />
                            {currentStatus.label}
                        </span>
                        {purchaseRequest.offline_approved_at && (
                            <span className="inline-flex items-center gap-1 rounded-md bg-violet-50 px-2.5 py-0.5 text-xs font-medium text-violet-700">
                                <Shield className="h-3 w-3" />
                                Offline approved
                            </span>
                        )}
                    </div>
                    <p className="mt-2 text-sm text-slate-500">
                        Multi-tier approval process for purchase request {purchaseRequest.pr_number}.
                    </p>
                </div>

                <div className="flex flex-wrap items-center justify-end gap-1 pt-14">
                    {permissions?.approve && (
                        <Button variant="default" size="sm" onClick={onApprove} className="bg-emerald-600 text-white hover:bg-emerald-700">
                            <Check className="w-4 h-4 mr-1.5" />
                            Approve
                        </Button>
                    )}

                    {permissions?.reject && (
                        <Button variant="ghost" size="sm" onClick={onReject} className="text-red-600 hover:text-red-900 hover:bg-red-50">
                            <X className="w-4 h-4 mr-1.5" />
                            Reject
                        </Button>
                    )}

                    {permissions?.edit && (
                        <Link href={route('purchase-requests.edit', { purchaseRequest: purchaseRequest.id })} className="inline-flex items-center gap-1.5 rounded-md px-2.5 py-1.5 text-sm font-medium text-slate-600 transition-colors hover:bg-slate-100 hover:text-slate-950">
                            <Edit className="w-4 h-4 mr-1.5" />
                            Edit
                        </Link>
                    )}

                    {permissions?.resubmit && (
                        <Button variant="ghost" size="sm" onClick={onResubmit} className="text-primary hover:text-primary hover:bg-blue-600">
                            <RotateCcw className="w-4 h-4 mr-1.5" />
                            Resubmit
                        </Button>
                    )}

                    {permissions?.resendApprovalEmail && (
                        <Button variant="ghost" size="sm" onClick={onResendApprovalEmail} disabled={isResendingEmail} className="text-sky-600 hover:text-sky-900 hover:bg-sky-50 disabled:opacity-50 disabled:cursor-not-allowed">
                            {isResendingEmail ? <Loader2 className="w-4 h-4 mr-1.5 animate-spin" /> : <Send className="w-4 h-4 mr-1.5" />}
                            Resend Email
                        </Button>
                    )}

                    {permissions?.downloadPdf && (
                        <a href={route('purchase-requests.pdf-public', { purchaseRequest: purchaseRequest.id })} target="_blank" rel="noopener noreferrer" className="inline-flex items-center gap-1.5 rounded-md px-2.5 py-1.5 text-sm font-medium text-slate-600 transition-colors hover:bg-slate-100 hover:text-slate-950">
                            <Download className="w-4 h-4 mr-1.5" />
                            Download PDF
                        </a>
                    )}

                    {permissions?.markOfflineApproved && (
                        <Button variant="ghost" size="sm" onClick={onMarkOfflineApproved} className="text-purple-600 hover:text-purple-900 hover:bg-purple-50">
                            <Shield className="w-4 h-4 mr-1.5" />
                            Mark Offline Approved
                        </Button>
                    )}

                    {permissions?.void && (
                        <Button variant="ghost" size="sm" onClick={onVoid} className="text-red-600 hover:text-red-900 hover:bg-red-50">
                            <Ban className="w-4 h-4 mr-1.5" />
                            Void
                        </Button>
                    )}
                </div>
            </div>
        </header>
    );
}
