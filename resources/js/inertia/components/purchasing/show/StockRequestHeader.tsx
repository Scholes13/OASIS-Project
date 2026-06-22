import { Link } from '@inertiajs/react';
import { ArrowLeft, Ban, Download, Edit, Loader2, RotateCcw, Send, Shield } from 'lucide-react';
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
        <div className="border-b border-gray-200 px-6 py-4">
            <div className="flex items-center justify-between">
                <div className="flex items-center space-x-4">
                    <Link href={isApprovalView ? route('stock-approvals.index') : route('stock-requests.index')} className="inline-flex items-center text-gray-500 hover:text-gray-700 transition-colors">
                        <ArrowLeft className="w-5 h-5" />
                    </Link>
                    <div>
                        <div className="flex items-center space-x-3">
                            <h1 className="text-xl font-semibold text-gray-900">{stockRequest.st_number}</h1>
                            <span className={`inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium ${currentStatus.bg} ${currentStatus.text}`}>
                                <StatusIcon className="w-3.5 h-3.5 mr-1" />
                                {currentStatus.label}
                            </span>
                            {stockRequest.offline_approved_at && (
                                <span className="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-700">
                                    <Shield className="w-3.5 h-3.5 mr-1" />
                                    Offline Approved
                                </span>
                            )}
                        </div>
                        <p className="text-sm text-gray-500 mt-0.5">{stockRequest.business_unit?.name || 'N/A'} • {stockRequest.department?.name || 'N/A'}</p>
                    </div>
                </div>

                <div className="flex items-center space-x-2">
                    {permissions?.edit && <Link href={route('stock-requests.edit', { stockRequest: stockRequest.id })} className="inline-flex items-center px-3 py-1.5 text-sm text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-md transition-colors"><Edit className="w-4 h-4 mr-1.5" />Edit</Link>}
                    {permissions?.resubmit && <Button variant="ghost" size="sm" onClick={onResubmit} className="text-primary hover:text-primary hover:bg-blue-600"><RotateCcw className="w-4 h-4 mr-1.5" />Resubmit</Button>}
                    {permissions?.resendApprovalEmail && <Button variant="ghost" size="sm" onClick={onResendApprovalEmail} disabled={isResendingEmail} className="text-sky-600 hover:text-sky-900 hover:bg-sky-50 disabled:opacity-50 disabled:cursor-not-allowed">{isResendingEmail ? <Loader2 className="w-4 h-4 mr-1.5 animate-spin" /> : <Send className="w-4 h-4 mr-1.5" />}Resend Email</Button>}
                    {permissions?.downloadPdf && <a href={route('stock-requests.pdf-public', { stockRequest: stockRequest.id })} target="_blank" rel="noopener noreferrer" className="inline-flex items-center px-3 py-1.5 text-sm text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-md transition-colors"><Download className="w-4 h-4 mr-1.5" />Download PDF</a>}
                    {permissions?.markOfflineApproved && <Button variant="ghost" size="sm" onClick={onMarkOfflineApproved} className="text-purple-600 hover:text-purple-900 hover:bg-purple-50"><Shield className="w-4 h-4 mr-1.5" />Mark Offline Approved</Button>}
                    {permissions?.void && <Button variant="ghost" size="sm" onClick={onVoid} className="text-red-600 hover:text-red-900 hover:bg-red-50"><Ban className="w-4 h-4 mr-1.5" />Void</Button>}
                </div>
            </div>
        </div>
    );
}
