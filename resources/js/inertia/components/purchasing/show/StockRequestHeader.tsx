import { Link } from '@inertiajs/react';
import { Ban, Download, Edit, Loader2, RotateCcw, Send, Shield } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { ST_STATUS_CONFIG } from '@/lib/purchasingConstants';
import type { STPermissions, StockRequest } from '@/types/purchasing';

interface StockRequestHeaderProps {
    stockRequest: StockRequest;
    permissions: Partial<STPermissions>;
    isApprovalView: boolean;
    isResendingEmail: boolean;
    onResubmit: () => void;
    onResendApprovalEmail: () => void;
    onMarkOfflineApproved: () => void;
    onVoid: () => void;
}

export function StockRequestHeader({ stockRequest, permissions, isApprovalView, isResendingEmail, onResubmit, onResendApprovalEmail, onMarkOfflineApproved, onVoid }: StockRequestHeaderProps) {
    const currentStatus = ST_STATUS_CONFIG[stockRequest.status as keyof typeof ST_STATUS_CONFIG] || ST_STATUS_CONFIG.draft;
    const StatusIcon = currentStatus.icon || Edit;

    return (
        <header className="px-9 pb-2 pt-3">
            <div className="flex items-start justify-between gap-6">
                <div className="min-w-0">
                    <nav className="mb-3 flex items-center gap-2 text-xs font-medium text-slate-500" aria-label="Breadcrumb">
                        <Link href={route('stock-approvals.index')} className="hover:text-slate-800">Approvals</Link>
                        <span className="text-slate-300">/</span>
                        <Link href={route('stock-requests.index')} className="hover:text-slate-800">Stock Request</Link>
                        <span className="text-slate-300">/</span>
                        <span className="truncate text-slate-800">{stockRequest.st_number}</span>
                    </nav>
                    <div className="flex flex-wrap items-center gap-2.5">
                        <h1 className="text-2xl font-semibold tracking-tight text-slate-950">Stock Request Approval</h1>
                        <span className={`inline-flex items-center gap-1 rounded-md px-2.5 py-0.5 text-xs font-medium ${currentStatus.bg} ${currentStatus.text}`}>
                            <StatusIcon className="h-3 w-3" />
                            {currentStatus.label}
                        </span>
                        {stockRequest.offline_approved_at && (
                            <span className="inline-flex items-center gap-1 rounded-md bg-violet-50 px-2.5 py-0.5 text-xs font-medium text-violet-700">
                                <Shield className="h-3 w-3" />
                                Offline approved
                            </span>
                        )}
                    </div>
                    <p className="mt-2 text-sm text-slate-500">
                        Multi-tier approval process for stock request {stockRequest.st_number}.
                    </p>
                </div>

                <div className="flex flex-wrap items-center justify-end gap-1 pt-14">
                    {permissions?.edit && (
                        <Link
                            href={route('stock-requests.edit', { stockRequest: stockRequest.id })}
                            className="inline-flex items-center gap-1.5 rounded-md px-2.5 py-1.5 text-sm font-medium text-slate-600 transition-colors hover:bg-slate-100 hover:text-slate-950"
                        >
                            <Edit className="h-3.5 w-3.5" />
                            Edit
                        </Link>
                    )}
                    {permissions?.resubmit && (
                        <Button variant="ghost" size="sm" onClick={onResubmit} className="text-blue-700 hover:bg-blue-50 hover:text-blue-800">
                            <RotateCcw className="mr-1.5 h-3.5 w-3.5" />
                            Resubmit
                        </Button>
                    )}
                    {permissions?.resendApprovalEmail && (
                        <Button variant="ghost" size="sm" onClick={onResendApprovalEmail} disabled={isResendingEmail} className="text-slate-600 hover:bg-slate-100 hover:text-slate-950 disabled:cursor-not-allowed disabled:opacity-50">
                            {isResendingEmail ? <Loader2 className="mr-1.5 h-3.5 w-3.5 animate-spin" /> : <Send className="mr-1.5 h-3.5 w-3.5" />}
                            Resend email
                        </Button>
                    )}
                    {permissions?.downloadPdf && (
                        <a
                            href={route('stock-requests.pdf-public', { stockRequest: stockRequest.id })}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="inline-flex items-center gap-1.5 rounded-md px-2.5 py-1.5 text-sm font-medium text-slate-600 transition-colors hover:bg-slate-100 hover:text-slate-950"
                        >
                            <Download className="h-3.5 w-3.5" />
                            Download PDF
                        </a>
                    )}
                    {permissions?.markOfflineApproved && (
                        <Button variant="ghost" size="sm" onClick={onMarkOfflineApproved} className="text-violet-700 hover:bg-violet-50 hover:text-violet-800">
                            <Shield className="mr-1.5 h-3.5 w-3.5" />
                            Mark offline approved
                        </Button>
                    )}
                    {permissions?.void && (
                        <Button variant="ghost" size="sm" onClick={onVoid} className="text-red-700 hover:bg-red-50 hover:text-red-800">
                            <Ban className="mr-1.5 h-3.5 w-3.5" />
                            Void
                        </Button>
                    )}
                </div>
            </div>
        </header>
    );
}
