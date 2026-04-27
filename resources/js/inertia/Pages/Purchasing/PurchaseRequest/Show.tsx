import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { motion, AnimatePresence } from 'framer-motion';
import { 
    ArrowLeft, 
    Edit, 
    Download, 
    Ban, 
    RotateCcw,
    Shield,
    Check,
    X,
    Clock,
    FileText,
    Eye,
    Upload,
    AlertTriangle,
    Loader2,
    Send
} from 'lucide-react';
import { PRShowProps } from '@/types/purchasing';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/Badge';
import { toast } from 'sonner';

export default function Show({ purchaseRequest, can }: PRShowProps) {
    const [showVoidModal, setShowVoidModal] = useState(false);
    const [showOfflineModal, setShowOfflineModal] = useState(false);
    const [showApproveModal, setShowApproveModal] = useState(false);
    const [showRejectModal, setShowRejectModal] = useState(false);
    const [voidReason, setVoidReason] = useState('');
    const [offlineNotes, setOfflineNotes] = useState('');
    const [offlineDocument, setOfflineDocument] = useState<File | null>(null);
    const [approvalNotes, setApprovalNotes] = useState('');
    const [rejectionNotes, setRejectionNotes] = useState('');
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [isResendingEmail, setIsResendingEmail] = useState(false);
    const supportingDocumentRouteName = 'purchase-requests.supporting-document';
    const supportingDocumentDownloadRouteName = 'purchase-requests.supporting-document.download';

    // Status styling configuration
    const statusConfig = {
        draft: { bg: 'bg-gray-100', text: 'text-gray-700', icon: Edit, label: 'Draft' },
        submitted: { bg: 'bg-blue-100', text: 'text-blue-700', icon: Clock, label: 'Submitted' },
        in_approval: { bg: 'bg-amber-100', text: 'text-amber-700', icon: Clock, label: 'In Approval' },
        approved: { bg: 'bg-emerald-100', text: 'text-emerald-700', icon: Check, label: 'Approved' },
        rejected: { bg: 'bg-red-100', text: 'text-red-700', icon: X, label: 'Rejected' },
        voided: { bg: 'bg-gray-100', text: 'text-gray-500', icon: Ban, label: 'Voided' },
    };

    const currentStatus = statusConfig[purchaseRequest.status];
    const StatusIcon = currentStatus.icon;

    // Permissions
    const permissions = can || purchaseRequest.can || {} as {
        approve?: boolean;
        reject?: boolean;
        edit?: boolean;
        resubmit?: boolean;
        resendApprovalEmail?: boolean;
        downloadPdf?: boolean;
        markOfflineApproved?: boolean;
        void?: boolean;
        supportingDocument?: boolean;
    };


    // Handle void action
    const handleVoid = (e: React.FormEvent) => {
        e.preventDefault();
        if (!voidReason.trim()) {
            toast.error('Please provide a reason for voiding');
            return;
        }

        setIsSubmitting(true);
        router.post(
            route('purchase-requests.void', { purchaseRequest: purchaseRequest.id }),
            { reason: voidReason },
            {
                onSuccess: () => {
                    toast.success('Purchase request voided successfully');
                    setShowVoidModal(false);
                },
                onError: () => {
                    toast.error('Failed to void purchase request');
                },
                onFinish: () => setIsSubmitting(false),
            }
        );
    };

    // Handle offline approval
    const handleOfflineApproval = (e: React.FormEvent) => {
        e.preventDefault();
        if (!offlineDocument) {
            toast.error('Please upload approval document');
            return;
        }

        setIsSubmitting(true);
        const formData = new FormData();
        formData.append('offline_approval_document', offlineDocument);
        if (offlineNotes.trim()) {
            formData.append('notes', offlineNotes);
        }

        router.post(
            route('purchase-requests.mark-offline-approved', { purchaseRequest: purchaseRequest.id }),
            formData as any,
            {
                onSuccess: () => {
                    toast.success('Purchase request marked as offline approved');
                    setShowOfflineModal(false);
                },
                onError: () => {
                    toast.error('Failed to mark as offline approved');
                },
                onFinish: () => setIsSubmitting(false),
            }
        );
    };

    // Handle resubmit
    const handleResubmit = () => {
        if (!window.confirm('Resubmitting will reset the approval workflow. All previous approval decisions will be cleared. Continue?')) {
            return;
        }
        router.post(
            route('purchase-requests.resubmit', { purchaseRequest: purchaseRequest.id }),
            {},
            {
                onSuccess: () => {
                    toast.success('Purchase request resubmitted successfully');
                },
                onError: () => {
                    toast.error('Failed to resubmit purchase request');
                },
            }
        );
    };

    // Handle resend approval email
    const handleResendApprovalEmail = () => {
        setIsResendingEmail(true);

        router.post(
            route('purchase-requests.resend-approval-email', { purchaseRequest: purchaseRequest.id }),
            {},
            {
                onSuccess: () => {
                    toast.success('Approval email resent to current approver');
                },
                onError: () => {
                    toast.error('Failed to resend approval email');
                },
                onFinish: () => setIsResendingEmail(false),
            }
        );
    };

    // Handle approve
    const handleApprove = (e: React.FormEvent) => {
        e.preventDefault();
        setIsSubmitting(true);
        router.post(
            route('purchase-requests.approve', { purchaseRequest: purchaseRequest.id }),
            { notes: approvalNotes },
            {
                onSuccess: () => {
                    toast.success('Purchase request approved successfully');
                    setShowApproveModal(false);
                },
                onError: () => {
                    toast.error('Failed to approve purchase request');
                },
                onFinish: () => setIsSubmitting(false),
            }
        );
    };

    // Handle reject
    const handleReject = (e: React.FormEvent) => {
        e.preventDefault();
        if (!rejectionNotes.trim()) {
            toast.error('Please provide a reason for rejection');
            return;
        }
        setIsSubmitting(true);
        router.post(
            route('purchase-requests.reject', { purchaseRequest: purchaseRequest.id }),
            { notes: rejectionNotes },
            {
                onSuccess: () => {
                    toast.success('Purchase request rejected successfully');
                    setShowRejectModal(false);
                },
                onError: () => {
                    toast.error('Failed to reject purchase request');
                },
                onFinish: () => setIsSubmitting(false),
            }
        );
    };

    // Format currency
    const formatCurrency = (amount: number, currency: string = 'IDR') => {
        return new Intl.NumberFormat('id-ID', {
            style: 'decimal',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0,
        }).format(amount);
    };

    // Format date
    const formatDate = (dateString: string | null) => {
        if (!dateString) return 'N/A';
        return new Date(dateString).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
        });
    };

    // Format datetime
    const formatDateTime = (dateString: string | null) => {
        if (!dateString) return 'N/A';
        return new Date(dateString).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    const getSupportingDocumentUrl = (download: boolean = false) => {
        const ziggy = route as unknown as {
            (...args: any[]): any;
        };
        const routeName = download ? supportingDocumentDownloadRouteName : supportingDocumentRouteName;
        const fallbackPath = download
            ? `/purchase-requests/${purchaseRequest.id}/supporting-document/download`
            : `/purchase-requests/${purchaseRequest.id}/supporting-document`;

        try {
            const routeList = ziggy();
            if (routeList?.has?.(routeName)) {
                return ziggy(routeName, {
                    purchaseRequest: purchaseRequest.id,
                });
            }
        } catch {
            // Fall back to the authenticated application path if Ziggy route metadata is unavailable.
        }

        return fallbackPath;
    };


    return (
        <>
            <Head title={`PR ${purchaseRequest.pr_number}`} />

            <div className="bg-white">
                {/* Header */}
                <div className="border-b border-gray-200 px-6 py-4">
                    <div className="flex items-center justify-between">
                        <div className="flex items-center space-x-4">
                            <Link
                                href={route('purchase-requests.index')}
                                className="inline-flex items-center text-gray-500 hover:text-gray-700 transition-colors"
                            >
                                <ArrowLeft className="w-5 h-5" />
                            </Link>
                            <div>
                                <div className="flex items-center space-x-3">
                                    <h1 className="text-xl font-semibold text-gray-900">
                                        {purchaseRequest.pr_number}
                                    </h1>
                                    <span className={`inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium ${currentStatus.bg} ${currentStatus.text}`}>
                                        <StatusIcon className="w-3.5 h-3.5 mr-1" />
                                        {currentStatus.label}
                                    </span>
                                    {purchaseRequest.offline_approved_at && (
                                        <span className="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-700">
                                            <Shield className="w-3.5 h-3.5 mr-1" />
                                            Offline Approved
                                        </span>
                                    )}
                                </div>
                                <p className="text-sm text-gray-500 mt-0.5">
                                    {purchaseRequest.business_unit?.name || 'N/A'} • {purchaseRequest.department?.name || 'N/A'}
                                </p>
                            </div>
                        </div>

                        {/* Action Buttons */}
                        <div className="flex items-center space-x-2">
                            {permissions?.approve && (
                                <Button
                                    variant="default"
                                    size="sm"
                                    onClick={() => setShowApproveModal(true)}
                                    className="bg-emerald-600 hover:bg-emerald-700 text-white"
                                >
                                    <Check className="w-4 h-4 mr-1.5" />
                                    Approve
                                </Button>
                            )}

                            {permissions?.reject && (
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    onClick={() => setShowRejectModal(true)}
                                    className="text-red-600 hover:text-red-900 hover:bg-red-50"
                                >
                                    <X className="w-4 h-4 mr-1.5" />
                                    Reject
                                </Button>
                            )}

                            {permissions?.edit && (
                                <Link
                                    href={route('purchase-requests.edit', { purchaseRequest: purchaseRequest.id })}
                                    className="inline-flex items-center px-3 py-1.5 text-sm text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-md transition-colors"
                                >
                                    <Edit className="w-4 h-4 mr-1.5" />
                                    Edit
                                </Link>
                            )}

                            {permissions?.resubmit && (
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    onClick={handleResubmit}
                                    className="text-primary hover:text-primary hover:bg-blue-600"
                                >
                                    <RotateCcw className="w-4 h-4 mr-1.5" />
                                    Resubmit
                                </Button>
                            )}

                            {permissions?.resendApprovalEmail && (
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    onClick={handleResendApprovalEmail}
                                    disabled={isResendingEmail}
                                    className="text-sky-600 hover:text-sky-900 hover:bg-sky-50 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    {isResendingEmail ? (
                                        <Loader2 className="w-4 h-4 mr-1.5 animate-spin" />
                                    ) : (
                                        <Send className="w-4 h-4 mr-1.5" />
                                    )}
                                    Resend Email
                                </Button>
                            )}

                            {permissions?.downloadPdf && (
                                <a
                                    href={route('purchase-requests.pdf-public', { purchaseRequest: purchaseRequest.id })}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="inline-flex items-center px-3 py-1.5 text-sm text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-md transition-colors"
                                >
                                    <Download className="w-4 h-4 mr-1.5" />
                                    Download PDF
                                </a>
                            )}

                            {permissions?.markOfflineApproved && (
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    onClick={() => setShowOfflineModal(true)}
                                    className="text-purple-600 hover:text-purple-900 hover:bg-purple-50"
                                >
                                    <Shield className="w-4 h-4 mr-1.5" />
                                    Mark Offline Approved
                                </Button>
                            )}

                            {permissions?.void && (
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    onClick={() => setShowVoidModal(true)}
                                    className="text-red-600 hover:text-red-900 hover:bg-red-50"
                                >
                                    <Ban className="w-4 h-4 mr-1.5" />
                                    Void
                                </Button>
                            )}
                        </div>
                    </div>
                </div>


                {/* Alert for Rejected PRs */}
                {purchaseRequest.status === 'rejected' && (
                    <motion.div
                        initial={{ opacity: 0, y: -10 }}
                        animate={{ opacity: 1, y: 0 }}
                        className="mx-6 mt-4 p-4 bg-red-50 border border-red-200 rounded-lg"
                    >
                        <div className="flex items-start">
                            <AlertTriangle className="w-5 h-5 text-red-500 mt-0.5" />
                            <div className="ml-3">
                                <h3 className="text-sm font-medium text-red-800">
                                    This Purchase Request was Rejected
                                </h3>
                                <p className="text-sm text-red-700 mt-1">
                                    You can edit this PR and resubmit it for approval using the "Resubmit" button above.
                                </p>
                            </div>
                        </div>
                    </motion.div>
                )}

                {/* Content Grid */}
                <div className="px-6 py-6">
                    <div className="grid grid-cols-1 xl:grid-cols-3 gap-6">
                        {/* Main Content (2/3) */}
                        <div className="xl:col-span-2 space-y-6">
                            {/* Request Details Card */}
                            <motion.div
                                initial={{ opacity: 0, y: 20 }}
                                animate={{ opacity: 1, y: 0 }}
                                transition={{ delay: 0.1 }}
                                className="bg-white rounded-xl border border-gray-100 overflow-hidden"
                            >
                                <div className="px-5 py-4 border-b border-gray-100">
                                    <h3 className="text-base font-semibold text-gray-900">Request Details</h3>
                                </div>
                                <div className="p-6">
                                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-x-8">
                                        <div className="mb-6">
                                            <p className="text-sm font-medium text-gray-500">Requested By</p>
                                            <p className="mt-1 text-sm text-gray-900">
                                                {purchaseRequest.user?.name || 'N/A'}
                                            </p>
                                        </div>
                                        <div className="mb-6">
                                            <p className="text-sm font-medium text-gray-500">Department</p>
                                            <p className="mt-1 text-sm text-gray-900">
                                                {purchaseRequest.department?.name || 'N/A'} ({purchaseRequest.department?.code || 'N/A'})
                                            </p>
                                        </div>
                                        <div className="mb-6">
                                            <p className="text-sm font-medium text-gray-500">Date of Request</p>
                                            <p className="mt-1 text-sm text-gray-900">
                                                {formatDate(purchaseRequest.date_of_request)}
                                            </p>
                                        </div>
                                        <div className="mb-6">
                                            <p className="text-sm font-medium text-gray-500">Expected Date</p>
                                            <p className="mt-1 text-sm text-gray-900">
                                                {formatDate(purchaseRequest.expected_date) || 'Not specified'}
                                            </p>
                                        </div>
                                        <div className="sm:col-span-2">
                                            <p className="text-sm font-medium text-gray-500">Purpose / Used For</p>
                                            <p className="mt-1 text-sm text-gray-900">
                                                {purchaseRequest.used_for || 'Not specified'}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </motion.div>


                            {/* Supporting Document Card */}
                            {purchaseRequest.supporting_document_path && permissions?.supportingDocument && (
                                <motion.div
                                    initial={{ opacity: 0, y: 20 }}
                                    animate={{ opacity: 1, y: 0 }}
                                    transition={{ delay: 0.2 }}
                                    className="bg-white rounded-xl border border-gray-100 overflow-hidden"
                                >
                                    <div className="px-5 py-4 border-b border-gray-100">
                                        <h3 className="text-base font-semibold text-gray-900">Supporting Document</h3>
                                    </div>
                                    <div className="p-5">
                                        <div className="flex items-center justify-between p-3 bg-gray-50 rounded-lg border border-gray-200">
                                            <div className="flex items-center space-x-3">
                                                <div className="flex-shrink-0 w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center">
                                                    <FileText className="w-5 h-5 text-gray-500" />
                                                </div>
                                                <div className="min-w-0 flex-1">
                                                    <p className="text-sm font-medium text-gray-900 truncate">
                                                        {purchaseRequest.supporting_document_name || 'Document'}
                                                    </p>
                                                    <p className="text-xs text-gray-500">Document</p>
                                                </div>
                                            </div>
                                            <div className="flex items-center space-x-2">
                                                <a
                                                    href={getSupportingDocumentUrl()}
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    className="inline-flex items-center px-3 py-1.5 text-sm text-blue-600 hover:text-blue-800 hover:bg-blue-50 rounded-md transition-colors"
                                                    title="View document"
                                                >
                                                    <Eye className="w-4 h-4 mr-1.5" />
                                                    View
                                                </a>
                                                <a
                                                    href={getSupportingDocumentUrl(true)}
                                                    download={purchaseRequest.supporting_document_name}
                                                    className="inline-flex items-center px-3 py-1.5 text-sm text-gray-600 hover:text-gray-800 hover:bg-gray-100 rounded-md transition-colors"
                                                    title="Download document"
                                                >
                                                    <Download className="w-4 h-4 mr-1.5" />
                                                    Download
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </motion.div>
                            )}


                            {/* Items Table Card */}
                            <motion.div
                                initial={{ opacity: 0, y: 20 }}
                                animate={{ opacity: 1, y: 0 }}
                                transition={{ delay: 0.3 }}
                                className="bg-white rounded-xl border border-gray-100 overflow-hidden"
                            >
                                <div className="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                                    <h3 className="text-base font-semibold text-gray-900">Items</h3>
                                    <span className="text-sm text-gray-500">
                                        {purchaseRequest.items?.length || 0} {purchaseRequest.items?.length === 1 ? 'item' : 'items'}
                                    </span>
                                </div>
                                <div className="overflow-x-auto">
                                    <table className="min-w-full divide-y divide-gray-200">
                                        <thead className="bg-gray-50">
                                            <tr>
                                                <th className="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    No
                                                </th>
                                                <th className="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Item
                                                </th>
                                                <th className="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Expense Dept
                                                </th>
                                                <th className="px-5 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Qty
                                                </th>
                                                <th className="px-5 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Unit Price
                                                </th>
                                                <th className="px-5 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Total
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody className="bg-white divide-y divide-gray-100">
                                            {purchaseRequest.items && purchaseRequest.items.length > 0 ? (
                                                purchaseRequest.items.map((item, index) => (
                                                    <motion.tr
                                                        key={item.id}
                                                        initial={{ opacity: 0, y: 10 }}
                                                        animate={{ opacity: 1, y: 0 }}
                                                        transition={{ delay: 0.4 + index * 0.05 }}
                                                        className="hover:bg-gray-50 transition-colors"
                                                    >
                                                        <td className="px-5 py-4 whitespace-nowrap text-sm text-gray-500">
                                                            {index + 1}
                                                        </td>
                                                        <td className="px-5 py-4">
                                                            <div className="text-sm text-gray-900">{item.item_name}</div>
                                                            {item.brand_name && (
                                                                <div className="text-sm text-gray-500">Brand: {item.brand_name}</div>
                                                            )}
                                                            {item.item_description && (
                                                                <div className="text-sm text-gray-400 mt-1">{item.item_description}</div>
                                                            )}
                                                            {item.supplier_name && (
                                                                <div className="text-xs text-gray-400 mt-1">Supplier: {item.supplier_name}</div>
                                                            )}
                                                        </td>
                                                        <td className="px-5 py-4 whitespace-nowrap">
                                                            <div className="text-sm text-gray-900">
                                                                {item.expense_department?.name || 'N/A'}
                                                            </div>
                                                            <div className="text-xs text-gray-500">
                                                                {item.expense_department?.code || ''}
                                                            </div>
                                                        </td>
                                                        <td className="px-5 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                                            {formatCurrency(item.quantity)} {item.unit}
                                                        </td>
                                                        <td className="px-5 py-4 whitespace-nowrap text-right">
                                                            <span className="text-sm text-gray-500">{item.currency || 'IDR'} </span>
                                                            <span className="text-sm text-gray-900">
                                                                {formatCurrency(item.unit_price, item.currency)}
                                                            </span>
                                                        </td>
                                                        <td className="px-5 py-4 whitespace-nowrap text-right">
                                                            <span className="text-sm text-gray-500">{item.currency || 'IDR'} </span>
                                                            <span className="text-sm text-gray-900">
                                                                {formatCurrency(item.total_price, item.currency)}
                                                            </span>
                                                        </td>
                                                    </motion.tr>
                                                ))
                                            ) : (
                                                <tr>
                                                    <td colSpan={6} className="px-5 py-8 text-center text-sm text-gray-500">
                                                        No items found
                                                    </td>
                                                </tr>
                                            )}
                                        </tbody>
                                        <tfoot className="bg-gray-50">
                                            <tr>
                                                <td colSpan={5} className="px-5 py-4 text-right text-sm font-semibold text-gray-900">
                                                    Total Amount
                                                </td>
                                                <td className="px-5 py-4 whitespace-nowrap text-right">
                                                    <span className="text-sm text-gray-900">{purchaseRequest.currency || 'IDR'} </span>
                                                    <span className="text-base font-semibold text-gray-900">
                                                        {formatCurrency(purchaseRequest.total_amount, purchaseRequest.currency)}
                                                    </span>
                                                </td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </motion.div>
                        </div>


                        {/* Sidebar (1/3) */}
                        <div className="space-y-6">
                            {/* Approval Progress Card */}
                            <motion.div
                                initial={{ opacity: 0, x: 20 }}
                                animate={{ opacity: 1, x: 0 }}
                                transition={{ delay: 0.2 }}
                                className="bg-white rounded-xl border border-gray-100 overflow-hidden"
                            >
                                <div className="px-5 py-4 border-b border-gray-100">
                                    <h3 className="text-base font-semibold text-gray-900">Approval Progress</h3>
                                </div>
                                <div className="p-5">
                                    {purchaseRequest.approvals && purchaseRequest.approvals.length > 0 ? (
                                        <div className="space-y-0">
                                            {purchaseRequest.approvals.map((approval, index) => (
                                                <motion.div
                                                    key={approval.id}
                                                    initial={{ opacity: 0, x: -10 }}
                                                    animate={{ opacity: 1, x: 0 }}
                                                    transition={{ delay: 0.3 + index * 0.1 }}
                                                    className="flex items-start gap-3 pb-6 last:pb-0"
                                                >
                                                    {/* Step Indicator */}
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
                                                        {/* Connector Line */}
                                                        {index < purchaseRequest.approvals!.length - 1 && (
                                                            <div className="absolute top-8 left-4 w-0.5 h-6 bg-gray-200" />
                                                        )}
                                                    </div>

                                                    {/* Approval Details */}
                                                    <div className="flex-1 min-w-0">
                                                        <div className="flex items-start justify-between">
                                                            <div className="flex-1">
                                                                <p className="text-sm font-medium text-gray-900">
                                                                    {approval.approver?.name || 'Unknown'}
                                                                </p>
                                                                <p className="text-xs text-gray-500 mt-0.5">
                                                                    {approval.approver?.email || ''}
                                                                </p>
                                                            </div>
                                                            <Badge
                                                                variant={
                                                                    approval.status === 'approved'
                                                                        ? 'success'
                                                                        : approval.status === 'rejected'
                                                                        ? 'danger'
                                                                        : 'default'
                                                                }
                                                                className="ml-2"
                                                            >
                                                                {approval.status === 'approved'
                                                                    ? 'Approved'
                                                                    : approval.status === 'rejected'
                                                                    ? 'Rejected'
                                                                    : 'Pending'}
                                                            </Badge>
                                                        </div>
                                                        {approval.responded_at && (
                                                            <p className="text-xs text-gray-400 mt-1">
                                                                {formatDateTime(approval.responded_at)}
                                                            </p>
                                                        )}
                                                        {approval.notes && (
                                                            <p className="text-xs text-gray-600 mt-2 p-2 bg-gray-50 rounded">
                                                                {approval.notes}
                                                            </p>
                                                        )}
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


                            {/* Timeline Card */}
                            <motion.div
                                initial={{ opacity: 0, x: 20 }}
                                animate={{ opacity: 1, x: 0 }}
                                transition={{ delay: 0.3 }}
                                className="bg-white rounded-xl border border-gray-100 overflow-hidden"
                            >
                                <div className="px-5 py-4 border-b border-gray-100">
                                    <h3 className="text-base font-semibold text-gray-900">Timeline</h3>
                                </div>
                                <div className="p-5 space-y-3">
                                    <div className="flex items-center justify-between text-sm">
                                        <span className="text-gray-500">Created</span>
                                        <span className="text-gray-900">{formatDateTime(purchaseRequest.created_at)}</span>
                                    </div>
                                    {purchaseRequest.submitted_at && (
                                        <div className="flex items-center justify-between text-sm">
                                            <span className="text-gray-500">Submitted</span>
                                            <span className="text-gray-900">{formatDateTime(purchaseRequest.submitted_at)}</span>
                                        </div>
                                    )}
                                    {purchaseRequest.approved_at && (
                                        <div className="flex items-center justify-between text-sm">
                                            <span className="text-emerald-600">Approved</span>
                                            <span className="text-gray-900">{formatDateTime(purchaseRequest.approved_at)}</span>
                                        </div>
                                    )}
                                    {purchaseRequest.rejected_at && (
                                        <div className="flex items-center justify-between text-sm">
                                            <span className="text-red-600">Rejected</span>
                                            <span className="text-gray-900">{formatDateTime(purchaseRequest.rejected_at)}</span>
                                        </div>
                                    )}
                                    {purchaseRequest.voided_at && (
                                        <div className="flex items-center justify-between text-sm">
                                            <span className="text-gray-500">Voided</span>
                                            <span className="text-gray-900">{formatDateTime(purchaseRequest.voided_at)}</span>
                                        </div>
                                    )}
                                    {purchaseRequest.offline_approved_at && (
                                        <>
                                            <div className="flex items-center justify-between text-sm">
                                                <span className="text-purple-600">Offline Approved</span>
                                                <span className="text-gray-900">{formatDateTime(purchaseRequest.offline_approved_at)}</span>
                                            </div>
                                            {purchaseRequest.offline_approval_notes && (
                                                <div className="text-sm">
                                                    <span className="text-gray-500">Notes:</span>
                                                    <p className="text-gray-700 mt-1 p-2 bg-gray-50 rounded text-xs">
                                                        {purchaseRequest.offline_approval_notes}
                                                    </p>
                                                </div>
                                            )}
                                        </>
                                    )}
                                </div>
                            </motion.div>
                        </div>
                    </div>
                </div>
            </div>


            {/* Void Modal */}
            <AnimatePresence>
                {showVoidModal && (
                    <>
                        {/* Backdrop */}
                        <motion.div
                            initial={{ opacity: 0 }}
                            animate={{ opacity: 1 }}
                            exit={{ opacity: 0 }}
                            className="fixed inset-0 bg-black bg-opacity-50 z-[9998]"
                            onClick={() => setShowVoidModal(false)}
                        />

                        {/* Modal */}
                        <div className="fixed inset-0 z-[9999] flex items-center justify-center p-4">
                            <motion.div
                                initial={{ opacity: 0, scale: 0.95, y: 20 }}
                                animate={{ opacity: 1, scale: 1, y: 0 }}
                                exit={{ opacity: 0, scale: 0.95, y: 20 }}
                                className="relative w-full max-w-md transform overflow-hidden rounded-xl bg-white shadow-xl transition-all"
                            >
                                <form onSubmit={handleVoid}>
                                    {/* Body */}
                                    <div className="bg-white px-5 py-4">
                                        <div className="flex items-start">
                                            <div className="flex-shrink-0 w-10 h-10 flex items-center justify-center rounded-full bg-red-100">
                                                <AlertTriangle className="w-5 h-5 text-red-600" />
                                            </div>
                                            <div className="ml-3">
                                                <h3 className="text-base font-semibold text-gray-900">
                                                    Void Purchase Request
                                                </h3>
                                                <p className="mt-1 text-sm text-gray-500">
                                                    Are you sure you want to void <strong>{purchaseRequest.pr_number}</strong>? This action cannot be undone.
                                                </p>
                                            </div>
                                        </div>
                                        <div className="mt-3">
                                            <label htmlFor="reason" className="block text-sm font-medium text-gray-700">
                                                Reason for voiding <span className="text-red-500">*</span>
                                            </label>
                                            <textarea
                                                id="reason"
                                                rows={2}
                                                required
                                                value={voidReason}
                                                onChange={(e) => setVoidReason(e.target.value)}
                                                className="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 text-sm"
                                                placeholder="Please provide a reason for voiding this purchase request..."
                                            />
                                        </div>
                                    </div>

                                    {/* Footer */}
                                    <div className="bg-gray-50 px-5 py-3 flex justify-end gap-2">
                                        <Button
                                            type="button"
                                            variant="outline"
                                            onClick={() => setShowVoidModal(false)}
                                            disabled={isSubmitting}
                                            className="disabled:opacity-50 disabled:cursor-not-allowed"
                                        >
                                            Cancel
                                        </Button>
                                        <Button
                                            type="submit"
                                            variant="destructive"
                                            disabled={isSubmitting}
                                            className="disabled:opacity-50 disabled:cursor-not-allowed"
                                        >
                                            {isSubmitting ? (
                                                <>
                                                    <Loader2 className="w-4 h-4 mr-2 animate-spin" />
                                                    Voiding...
                                                </>
                                            ) : (
                                                'Void Purchase Request'
                                            )}
                                        </Button>
                                    </div>
                                </form>
                            </motion.div>
                        </div>
                    </>
                )}
            </AnimatePresence>


            {/* Offline Approval Modal */}
            <AnimatePresence>
                {showOfflineModal && (
                    <>
                        {/* Backdrop */}
                        <motion.div
                            initial={{ opacity: 0 }}
                            animate={{ opacity: 1 }}
                            exit={{ opacity: 0 }}
                            className="fixed inset-0 bg-black bg-opacity-50 z-[9998]"
                            onClick={() => setShowOfflineModal(false)}
                        />

                        {/* Modal */}
                        <div className="fixed inset-0 z-[9999] flex items-center justify-center p-4">
                            <motion.div
                                initial={{ opacity: 0, scale: 0.95, y: 20 }}
                                animate={{ opacity: 1, scale: 1, y: 0 }}
                                exit={{ opacity: 0, scale: 0.95, y: 20 }}
                                className="relative w-full max-w-md transform overflow-hidden rounded-xl bg-white shadow-xl transition-all"
                            >
                                <form onSubmit={handleOfflineApproval}>
                                    {/* Body */}
                                    <div className="bg-white px-5 py-4">
                                        <div className="flex items-start">
                                            <div className="flex-shrink-0 w-10 h-10 flex items-center justify-center rounded-full bg-purple-100">
                                                <Shield className="w-5 h-5 text-purple-600" />
                                            </div>
                                            <div className="ml-3">
                                                <h3 className="text-base font-semibold text-gray-900">
                                                    Mark as Offline Approved
                                                </h3>
                                                <p className="mt-1 text-sm text-gray-500">
                                                    Use this when the PR has been approved manually/offline (e.g., signed paper copy).
                                                </p>
                                            </div>
                                        </div>

                                        <div className="mt-3 rounded-lg bg-amber-50 border border-amber-200 p-2.5">
                                            <div className="flex">
                                                <AlertTriangle className="w-4 h-4 flex-shrink-0 text-amber-500 mt-0.5" />
                                                <p className="ml-2 text-xs text-amber-700">
                                                    <strong>Note:</strong> This will skip the digital approval workflow. The PR status will show as "Approved".
                                                </p>
                                            </div>
                                        </div>

                                        {/* File Upload */}
                                        <div className="mt-3">
                                            <label htmlFor="offline_approval_document" className="block text-sm font-medium text-gray-700">
                                                Bukti Approval <span className="text-red-500">*</span>
                                            </label>
                                            <p className="text-xs text-gray-500 mb-2">
                                                Upload foto/scan dokumen yang sudah ditandatangani (JPG, PNG, PDF - max 10MB)
                                            </p>
                                            <div className="mt-1">
                                                <label className="cursor-pointer block">
                                                    <input
                                                        type="file"
                                                        id="offline_approval_document"
                                                        accept=".jpg,.jpeg,.png,.pdf"
                                                        required
                                                        className="hidden"
                                                        onChange={(e) => setOfflineDocument(e.target.files?.[0] || null)}
                                                    />
                                                    <div
                                                        className={`border-2 border-dashed rounded-lg p-4 text-center transition-colors ${
                                                            offlineDocument
                                                                ? 'border-purple-400 bg-purple-50'
                                                                : 'border-gray-300 hover:border-purple-400 hover:bg-purple-50'
                                                        }`}
                                                    >
                                                        {!offlineDocument ? (
                                                            <div>
                                                                <Upload className="mx-auto h-8 w-8 text-gray-400" />
                                                                <p className="mt-1 text-sm text-gray-600">
                                                                    <span className="font-medium text-purple-600">Klik untuk upload</span> atau drag & drop
                                                                </p>
                                                            </div>
                                                        ) : (
                                                            <div className="flex items-center justify-center space-x-2">
                                                                <Check className="h-6 w-6 text-purple-600" />
                                                                <span className="text-sm text-purple-700 font-medium">
                                                                    {offlineDocument.name}
                                                                </span>
                                                            </div>
                                                        )}
                                                    </div>
                                                </label>
                                            </div>
                                        </div>

                                        <div className="mt-3">
                                            <label htmlFor="offline_notes" className="block text-sm font-medium text-gray-700">
                                                Notes (optional)
                                            </label>
                                            <textarea
                                                id="offline_notes"
                                                rows={2}
                                                value={offlineNotes}
                                                onChange={(e) => setOfflineNotes(e.target.value)}
                                                className="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 text-sm"
                                                placeholder="Add any notes about the offline approval..."
                                            />
                                        </div>
                                    </div>

                                    {/* Footer */}
                                    <div className="bg-gray-50 px-5 py-3 flex justify-end gap-2">
                                        <Button
                                            type="button"
                                            variant="outline"
                                            onClick={() => setShowOfflineModal(false)}
                                            disabled={isSubmitting}
                                            className="disabled:opacity-50 disabled:cursor-not-allowed"
                                        >
                                            Cancel
                                        </Button>
                                        <Button
                                            type="submit"
                                            className="bg-purple-600 hover:bg-purple-700 disabled:opacity-50 disabled:cursor-not-allowed"
                                            disabled={isSubmitting}
                                        >
                                            {isSubmitting ? (
                                                <>
                                                    <Loader2 className="w-4 h-4 mr-2 animate-spin" />
                                                    Submitting...
                                                </>
                                            ) : (
                                                'Mark as Offline Approved'
                                            )}
                                        </Button>
                                    </div>
                                </form>
                            </motion.div>
                        </div>
                    </>
                )}
            </AnimatePresence>

            {/* Approve Modal */}
            <AnimatePresence>
                {showApproveModal && (
                    <>
                        {/* Backdrop */}
                        <motion.div
                            initial={{ opacity: 0 }}
                            animate={{ opacity: 1 }}
                            exit={{ opacity: 0 }}
                            className="fixed inset-0 bg-black bg-opacity-50 z-[9998]"
                            onClick={() => setShowApproveModal(false)}
                        />

                        {/* Modal */}
                        <div className="fixed inset-0 z-[9999] flex items-center justify-center p-4">
                            <motion.div
                                initial={{ opacity: 0, scale: 0.95, y: 20 }}
                                animate={{ opacity: 1, scale: 1, y: 0 }}
                                exit={{ opacity: 0, scale: 0.95, y: 20 }}
                                className="relative w-full max-w-md transform overflow-hidden rounded-xl bg-white shadow-xl transition-all"
                            >
                                <form onSubmit={handleApprove}>
                                    {/* Body */}
                                    <div className="bg-white px-5 py-4">
                                        <div className="flex items-start">
                                            <div className="flex-shrink-0 w-10 h-10 flex items-center justify-center rounded-full bg-emerald-100">
                                                <Check className="w-5 h-5 text-emerald-600" />
                                            </div>
                                            <div className="ml-3">
                                                <h3 className="text-base font-semibold text-gray-900">
                                                    Approve Purchase Request
                                                </h3>
                                                <p className="mt-1 text-sm text-gray-500">
                                                    Are you sure you want to approve <strong>{purchaseRequest.pr_number}</strong>?
                                                </p>
                                            </div>
                                        </div>
                                        <div className="mt-3">
                                            <label htmlFor="approval_notes" className="block text-sm font-medium text-gray-700">
                                                Notes (optional)
                                            </label>
                                            <textarea
                                                id="approval_notes"
                                                rows={2}
                                                value={approvalNotes}
                                                onChange={(e) => setApprovalNotes(e.target.value)}
                                                className="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 text-sm"
                                                placeholder="Add any notes about your approval..."
                                            />
                                        </div>
                                    </div>

                                    {/* Footer */}
                                    <div className="bg-gray-50 px-5 py-3 flex justify-end gap-2">
                                        <Button
                                            type="button"
                                            variant="outline"
                                            onClick={() => setShowApproveModal(false)}
                                            disabled={isSubmitting}
                                            className="disabled:opacity-50 disabled:cursor-not-allowed"
                                        >
                                            Cancel
                                        </Button>
                                        <Button
                                            type="submit"
                                            className="bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 disabled:cursor-not-allowed"
                                            disabled={isSubmitting}
                                        >
                                            {isSubmitting ? (
                                                <>
                                                    <Loader2 className="w-4 h-4 mr-2 animate-spin" />
                                                    Approving...
                                                </>
                                            ) : (
                                                'Approve Purchase Request'
                                            )}
                                        </Button>
                                    </div>
                                </form>
                            </motion.div>
                        </div>
                    </>
                )}
            </AnimatePresence>

            {/* Reject Modal */}
            <AnimatePresence>
                {showRejectModal && (
                    <>
                        {/* Backdrop */}
                        <motion.div
                            initial={{ opacity: 0 }}
                            animate={{ opacity: 1 }}
                            exit={{ opacity: 0 }}
                            className="fixed inset-0 bg-black bg-opacity-50 z-[9998]"
                            onClick={() => setShowRejectModal(false)}
                        />

                        {/* Modal */}
                        <div className="fixed inset-0 z-[9999] flex items-center justify-center p-4">
                            <motion.div
                                initial={{ opacity: 0, scale: 0.95, y: 20 }}
                                animate={{ opacity: 1, scale: 1, y: 0 }}
                                exit={{ opacity: 0, scale: 0.95, y: 20 }}
                                className="relative w-full max-w-md transform overflow-hidden rounded-xl bg-white shadow-xl transition-all"
                            >
                                <form onSubmit={handleReject}>
                                    {/* Body */}
                                    <div className="bg-white px-5 py-4">
                                        <div className="flex items-start">
                                            <div className="flex-shrink-0 w-10 h-10 flex items-center justify-center rounded-full bg-red-100">
                                                <X className="w-5 h-5 text-red-600" />
                                            </div>
                                            <div className="ml-3">
                                                <h3 className="text-base font-semibold text-gray-900">
                                                    Reject Purchase Request
                                                </h3>
                                                <p className="mt-1 text-sm text-gray-500">
                                                    Are you sure you want to reject <strong>{purchaseRequest.pr_number}</strong>?
                                                </p>
                                            </div>
                                        </div>
                                        <div className="mt-3">
                                            <label htmlFor="rejection_notes" className="block text-sm font-medium text-gray-700">
                                                Reason for rejection <span className="text-red-500">*</span>
                                            </label>
                                            <textarea
                                                id="rejection_notes"
                                                rows={3}
                                                required
                                                value={rejectionNotes}
                                                onChange={(e) => setRejectionNotes(e.target.value)}
                                                className="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 text-sm"
                                                placeholder="Please provide a reason for rejecting this purchase request..."
                                            />
                                        </div>
                                    </div>

                                    {/* Footer */}
                                    <div className="bg-gray-50 px-5 py-3 flex justify-end gap-2">
                                        <Button
                                            type="button"
                                            variant="outline"
                                            onClick={() => setShowRejectModal(false)}
                                            disabled={isSubmitting}
                                            className="disabled:opacity-50 disabled:cursor-not-allowed"
                                        >
                                            Cancel
                                        </Button>
                                        <Button
                                            type="submit"
                                            variant="destructive"
                                            disabled={isSubmitting}
                                            className="disabled:opacity-50 disabled:cursor-not-allowed"
                                        >
                                            {isSubmitting ? (
                                                <>
                                                    <Loader2 className="w-4 h-4 mr-2 animate-spin" />
                                                    Rejecting...
                                                </>
                                            ) : (
                                                'Reject Purchase Request'
                                            )}
                                        </Button>
                                    </div>
                                </form>
                            </motion.div>
                        </div>
                    </>
                )}
            </AnimatePresence>
        </>
    );
}
