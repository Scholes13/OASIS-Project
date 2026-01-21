import React, { useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { ApprovalsPageProps, ApprovalItem } from '@/types/purchasing';
import { ShieldCheck, Clock, CheckCircle, XCircle, ChevronRight, Search, Filter, FileText } from 'lucide-react';
import { formatCurrency, formatDate } from '@/lib/formatters';
import { useBusinessUnit } from '@/hooks/useBusinessUnit';

export default function Approvals({
    pendingApprovals,
    recentApprovals,
    stats,
    can
}: ApprovalsPageProps) {
    const { currentBusinessUnit } = useBusinessUnit();
    const [activeTab, setActiveTab] = useState<'pending' | 'history'>('pending');

    return (
        <div className="min-h-screen bg-gray-50">
            <Head title="Purchase Request Approvals" />

            <div className="py-6 px-4 sm:px-6 lg:px-8">
                {/* Header */}
                <div className="mb-6">
                    <h1 className="text-2xl font-bold text-gray-900">Approvals</h1>
                    <p className="text-sm text-gray-600 mt-1">
                        Manage purchase request approvals for {currentBusinessUnit?.name}
                    </p>
                </div>

                {/* Stats Cards */}
                <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
                    <div className="bg-white rounded-xl shadow-sm p-4 border border-gray-100 flex items-center space-x-4">
                        <div className="p-3 bg-blue-50 text-blue-600 rounded-lg">
                            <Clock className="w-6 h-6" />
                        </div>
                        <div>
                            <p className="text-sm font-medium text-gray-500">Pending</p>
                            <p className="text-2xl font-bold text-gray-900">{stats.pending}</p>
                        </div>
                    </div>
                    <div className="bg-white rounded-xl shadow-sm p-4 border border-gray-100 flex items-center space-x-4">
                        <div className="p-3 bg-green-50 text-green-600 rounded-lg">
                            <CheckCircle className="w-6 h-6" />
                        </div>
                        <div>
                            <p className="text-sm font-medium text-gray-500">Approved</p>
                            <p className="text-2xl font-bold text-gray-900">{stats.approved}</p>
                        </div>
                    </div>
                    <div className="bg-white rounded-xl shadow-sm p-4 border border-gray-100 flex items-center space-x-4">
                        <div className="p-3 bg-red-50 text-red-600 rounded-lg">
                            <XCircle className="w-6 h-6" />
                        </div>
                        <div>
                            <p className="text-sm font-medium text-gray-500">Rejected</p>
                            <p className="text-2xl font-bold text-gray-900">{stats.rejected}</p>
                        </div>
                    </div>
                    <div className="bg-white rounded-xl shadow-sm p-4 border border-gray-100 flex items-center space-x-4">
                        <div className="p-3 bg-indigo-50 text-indigo-600 rounded-lg">
                            <FileText className="w-6 h-6" />
                        </div>
                        <div>
                            <p className="text-sm font-medium text-gray-500">Total Processed</p>
                            <p className="text-2xl font-bold text-gray-900">{stats.total}</p>
                        </div>
                    </div>
                </div>

                {/* Tabs */}
                <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div className="border-b border-gray-200 px-4 sm:px-6">
                        <nav className="-mb-px flex space-x-8" aria-label="Tabs">
                            <button
                                onClick={() => setActiveTab('pending')}
                                className={`${activeTab === 'pending'
                                    ? 'border-indigo-500 text-indigo-600'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                    } whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center`}
                            >
                                <Clock className="w-4 h-4 mr-2" />
                                Pending ({stats.pending})
                            </button>
                            <button
                                onClick={() => setActiveTab('history')}
                                className={`${activeTab === 'history'
                                    ? 'border-indigo-500 text-indigo-600'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                    } whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center`}
                            >
                                <ShieldCheck className="w-4 h-4 mr-2" />
                                Approval History
                            </button>
                        </nav>
                    </div>

                    {/* Content */}
                    <div className="min-w-full divide-y divide-gray-200">
                        {activeTab === 'pending' ? (
                            <div>
                                {pendingApprovals.data.length === 0 ? (
                                    <div className="p-12 text-center">
                                        <div className="mx-auto h-12 w-12 text-gray-400">
                                            <CheckCircle className="h-12 w-12" />
                                        </div>
                                        <h3 className="mt-2 text-sm font-medium text-gray-900">All caught up!</h3>
                                        <p className="mt-1 text-sm text-gray-500">You have no pending approvals at the moment.</p>
                                    </div>
                                ) : (
                                    <ul className="divide-y divide-gray-200">
                                        {pendingApprovals.data.map((approval) => (
                                            <li key={approval.id} className="relative hover:bg-gray-50 transition-colors duration-150">
                                                <Link
                                                    href={route('approvals.show', { prApproval: approval.id })}
                                                    className="block p-4 sm:px-6"
                                                >
                                                    <div className="flex items-center justify-between">
                                                        <div className="flex-1 min-w-0">
                                                            <div className="flex items-center space-x-3 mb-1">
                                                                <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 capitalize">
                                                                    {approval.approval_type}
                                                                </span>
                                                                <span className="text-sm text-gray-500">
                                                                    Running for {formatDate(approval.waiting_since)}
                                                                </span>
                                                            </div>
                                                            <div className="mt-2">
                                                                <p className="text-lg font-medium text-indigo-600 truncate">
                                                                    {approval.purchase_request.pr_number}
                                                                </p>
                                                                <p className="text-sm text-gray-900 font-medium">
                                                                    {approval.purchase_request.user.name} • {approval.purchase_request.department.name}
                                                                </p>
                                                                <p className="text-sm text-gray-500 truncate mt-1">
                                                                    {approval.purchase_request.used_for}
                                                                </p>
                                                            </div>
                                                        </div>
                                                        <div className="flex items-center gap-4">
                                                            <div className="text-right hidden sm:block">
                                                                <p className="text-lg font-bold text-gray-900">
                                                                    {formatCurrency(approval.purchase_request.total_amount)}
                                                                </p>
                                                                <p className="text-sm text-gray-500">Total Amount</p>
                                                            </div>
                                                            <ChevronRight className="w-5 h-5 text-gray-400" />
                                                        </div>
                                                    </div>
                                                </Link>
                                            </li>
                                        ))}
                                    </ul>
                                )}
                            </div>
                        ) : (
                            <div>
                                {recentApprovals.length === 0 ? (
                                    <div className="p-12 text-center">
                                        <div className="mx-auto h-12 w-12 text-gray-400">
                                            <Clock className="h-12 w-12" />
                                        </div>
                                        <h3 className="mt-2 text-sm font-medium text-gray-900">No history found</h3>
                                        <p className="mt-1 text-sm text-gray-500">You haven't processed any approvals recently.</p>
                                    </div>
                                ) : (
                                    <ul className="divide-y divide-gray-200">
                                        {recentApprovals.map((approval) => (
                                            <li key={approval.id} className="p-4 sm:px-6 hover:bg-gray-50">
                                                <div className="flex items-center justify-between">
                                                    <div className="flex-1 min-w-0">
                                                        <div className="flex items-center space-x-3 mb-1">
                                                            <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize ${approval.status === 'approved'
                                                                ? 'bg-green-100 text-green-800'
                                                                : 'bg-red-100 text-red-800'
                                                                }`}>
                                                                {approval.status}
                                                            </span>
                                                            <span className="text-sm text-gray-500">
                                                                Processed {formatDate(approval.waiting_since)}
                                                            </span>
                                                        </div>
                                                        <div className="mt-2">
                                                            <Link
                                                                href={route('purchase-requests.show', { purchaseRequest: approval.purchase_request.id })}
                                                                className="text-base font-medium text-gray-900 hover:text-indigo-600"
                                                            >
                                                                {approval.purchase_request.pr_number}
                                                            </Link>
                                                            <p className="text-sm text-gray-500 mt-1">
                                                                Applicant: {approval.purchase_request.user.name}
                                                            </p>
                                                        </div>
                                                    </div>
                                                    <div className="text-right">
                                                        <span className="text-sm font-medium text-gray-900">
                                                            {formatCurrency(approval.purchase_request.total_amount)}
                                                        </span>
                                                        <ChevronRight className="w-5 h-5 text-gray-400 inline ml-2" />
                                                    </div>
                                                </div>
                                            </li>
                                        ))}
                                    </ul>
                                )}
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </div>
    );
}
