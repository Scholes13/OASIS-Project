import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { ShieldCheck, Clock, CheckCircle, XCircle, ChevronRight, FileText, Package, ShoppingCart } from 'lucide-react';
import { formatCurrency, formatDate } from '@/lib/formatters';
import { useBusinessUnit } from '@/hooks/useBusinessUnit';

interface User {
    id: number;
    name: string;
    email: string;
}

interface Department {
    id: number;
    name: string;
    code: string;
}

interface BusinessUnit {
    id: number;
    name: string;
    code: string;
}

interface ApprovalItem {
    id: number;
    type: 'PR' | 'ST';
    request_number: string;
    request_id: number;
    used_for: string;
    total_amount: number;
    currency: string;
    user: User;
    department: Department;
    business_unit: BusinessUnit;
    step_order: number;
    approval_type: string;
    status: string;
    waiting_since: string;
    created_at?: string;
    responded_at?: string;
    can: {
        approve: boolean;
        reject: boolean;
    };
}

interface Stats {
    pending: number;
    approved: number;
    rejected: number;
    total: number;
    pr_pending: number;
    st_pending: number;
}

interface ApprovalsPageProps {
    pendingApprovals: ApprovalItem[];
    recentApprovals: ApprovalItem[];
    stats: Stats;
    can: {
        processApprovals: boolean;
    };
}

export default function Approvals({
    pendingApprovals,
    recentApprovals,
    stats,
    can
}: ApprovalsPageProps) {
    const { currentBusinessUnit } = useBusinessUnit();
    const [activeTab, setActiveTab] = useState<'pending' | 'history'>('pending');

    const handleApprovalClick = (approval: ApprovalItem) => {
        if (approval.type === 'PR') {
            router.visit(route('approvals.show', { prApproval: approval.id }));
        } else {
            router.visit(route('stock-approvals.show', { approval: approval.id }));
        }
    };

    const handleViewRequest = (approval: ApprovalItem) => {
        if (approval.type === 'PR') {
            router.visit(route('purchase-requests.show', { purchaseRequest: approval.request_id }));
        } else {
            router.visit(route('stock-requests.show', { stockRequest: approval.request_id }));
        }
    };

    const getTypeIcon = (type: 'PR' | 'ST') => {
        return type === 'PR' 
            ? <ShoppingCart className="w-4 h-4" />
            : <Package className="w-4 h-4" />;
    };

    const getTypeBadgeColor = (type: 'PR' | 'ST') => {
        return type === 'PR'
            ? 'bg-blue-50 text-blue-700'
            : 'bg-purple-100 text-purple-700';
    };

    return (
        <div className="w-full">
            <Head title="My Approvals" />

            <div className="w-full px-6 py-6 lg:px-8">
                {/* Header */}
                <div className="mb-6">
                    <h1 className="text-2xl font-bold text-gray-900">My Approvals</h1>
                    <p className="text-sm text-gray-600 mt-1">
                        Manage purchase and stock request approvals for {currentBusinessUnit?.name}
                    </p>
                </div>

                {/* Stats Cards */}
                <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
                    <div className="bg-white rounded-xl shadow-sm p-4 border border-gray-100 flex items-center space-x-4">
                        <div className="p-3 bg-amber-50 text-amber-600 rounded-lg">
                            <Clock className="w-6 h-6" />
                        </div>
                        <div>
                            <p className="text-sm font-medium text-gray-500">Pending</p>
                            <p className="text-2xl font-bold text-gray-900">{stats.pending}</p>
                            <p className="text-xs text-gray-400">
                                {stats.pr_pending} PR • {stats.st_pending} ST
                            </p>
                        </div>
                    </div>
                    <div className="bg-white rounded-xl shadow-sm p-4 border border-gray-100 flex items-center space-x-4">
                        <div className="p-3 bg-emerald-50 text-emerald-600 rounded-lg">
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
                        <div className="p-3 bg-blue-50 text-blue-600 rounded-lg">
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
                                    ? 'border-primary text-primary'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                    } whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center`}
                            >
                                <Clock className="w-4 h-4 mr-2" />
                                Pending ({stats.pending})
                            </button>
                            <button
                                onClick={() => setActiveTab('history')}
                                className={`${activeTab === 'history'
                                    ? 'border-primary text-primary'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                    } whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center`}
                            >
                                <ShieldCheck className="w-4 h-4 mr-2" />
                                Recent History
                            </button>
                        </nav>
                    </div>

                    {/* Content */}
                    <div className="min-w-full divide-y divide-gray-200">
                        {activeTab === 'pending' ? (
                            <div>
                                {pendingApprovals.length === 0 ? (
                                    <div className="p-12 text-center">
                                        <div className="mx-auto h-12 w-12 text-gray-400">
                                            <CheckCircle className="h-12 w-12" />
                                        </div>
                                        <h3 className="mt-2 text-sm font-medium text-gray-900">All caught up!</h3>
                                        <p className="mt-1 text-sm text-gray-500">You have no pending approvals at the moment.</p>
                                    </div>
                                ) : (
                                    <ul className="divide-y divide-gray-200">
                                        {pendingApprovals.map((approval) => (
                                            <li 
                                                key={`${approval.type}-${approval.id}`} 
                                                className="relative hover:bg-gray-50 transition-colors duration-150 cursor-pointer"
                                                onClick={() => handleApprovalClick(approval)}
                                            >
                                                <div className="block p-4 sm:px-6">
                                                    <div className="flex items-center justify-between">
                                                        <div className="flex-1 min-w-0">
                                                            <div className="flex items-center space-x-3 mb-1">
                                                                <span className={`inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium ${getTypeBadgeColor(approval.type)}`}>
                                                                    {getTypeIcon(approval.type)}
                                                                    {approval.type}
                                                                </span>
                                                                <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 capitalize">
                                                                    {approval.approval_type}
                                                                </span>
                                                                <span className="text-sm text-gray-500">
                                                                    Waiting since {formatDate(approval.waiting_since)}
                                                                </span>
                                                            </div>
                                                            <div className="mt-2">
                                                                <p className="text-lg font-medium text-primary truncate">
                                                                    {approval.request_number}
                                                                </p>
                                                                <p className="text-sm text-gray-900 font-medium">
                                                                    {approval.user?.name ?? 'Unknown User'} • {approval.department?.name ?? 'Unknown Dept'}
                                                                </p>
                                                                <p className="text-sm text-gray-500 truncate mt-1">
                                                                    {approval.used_for}
                                                                </p>
                                                            </div>
                                                        </div>
                                                        <div className="flex items-center gap-4">
                                                            <div className="text-right hidden sm:block">
                                                                <p className="text-lg font-bold text-gray-900">
                                                                    {formatCurrency(approval.total_amount, approval.currency)}
                                                                </p>
                                                                <p className="text-sm text-gray-500">Total Amount</p>
                                                            </div>
                                                            <ChevronRight className="w-5 h-5 text-gray-400" />
                                                        </div>
                                                    </div>
                                                </div>
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
                                            <li 
                                                key={`${approval.type}-${approval.id}`} 
                                                className="p-4 sm:px-6 hover:bg-gray-50 cursor-pointer"
                                                onClick={() => handleViewRequest(approval)}
                                            >
                                                <div className="flex items-center justify-between">
                                                    <div className="flex-1 min-w-0">
                                                        <div className="flex items-center space-x-3 mb-1">
                                                            <span className={`inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium ${getTypeBadgeColor(approval.type)}`}>
                                                                {getTypeIcon(approval.type)}
                                                                {approval.type}
                                                            </span>
                                                            <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize ${
                                                                approval.status === 'approved'
                                                                    ? 'bg-emerald-100 text-emerald-700'
                                                                    : 'bg-red-100 text-red-700'
                                                            }`}>
                                                                {approval.status}
                                                            </span>
                                                            <span className="text-sm text-gray-500">
                                                                {approval.responded_at ? formatDate(approval.responded_at) : ''}
                                                            </span>
                                                        </div>
                                                        <div className="mt-2">
                                                            <p className="text-base font-medium text-gray-900 hover:text-primary">
                                                                {approval.request_number}
                                                            </p>
                                                            <p className="text-sm text-gray-500 mt-1">
                                                                Requestor: {approval.user?.name ?? 'Unknown User'}
                                                            </p>
                                                        </div>
                                                    </div>
                                                    <div className="flex items-center gap-2">
                                                        <span className="text-sm font-medium text-gray-900">
                                                            {formatCurrency(approval.total_amount, approval.currency)}
                                                        </span>
                                                        <ChevronRight className="w-5 h-5 text-gray-400" />
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
