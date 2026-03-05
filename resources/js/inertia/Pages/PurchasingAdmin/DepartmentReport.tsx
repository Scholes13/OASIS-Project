import React from 'react';
import { Head, Link } from '@inertiajs/react';
import {
    Clock,
    DollarSign,
    TrendingUp,
    CheckCircle2,
    ArrowLeft,
    Building2,
    Users,
    BarChart3,
} from 'lucide-react';
import { cn } from '@/lib/utils';
import type { PageProps } from '@/types';

interface AdminPerformance {
    name: string;
    tasks_completed: number;
    total_savings: number;
    avg_savings_percentage: number;
    avg_followup_time: number;
    avg_completion_time: number;
}

interface Department {
    id: number;
    name: string;
}

interface DepartmentReportProps extends PageProps {
    department: Department | null;
    totalSavings: number;
    averageFollowupTime: number;
    averageCompletionTime: number;
    totalTasksCompleted: number;
    savingsBreakdown: {
        purchase_request: number;
        stock_request: number;
    };
    adminPerformance: AdminPerformance[];
    departmentTrendData: {
        labels: string[];
        data: number[];
    };
}

// Format currency
const formatCurrency = (amount: number | string | null | undefined) => {
    if (amount === null || amount === undefined) return 'Rp 0';
    const val = typeof amount === 'string' ? parseFloat(amount) : amount;
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(val);
};

// Format time (minutes to human readable)
const formatTime = (minutes: number | string | null | undefined) => {
    if (minutes === null || minutes === undefined || minutes === 0) return '0 min';
    const val = typeof minutes === 'string' ? parseFloat(minutes) : minutes;
    if (val >= 1440) {
        const days = Math.floor(val / 1440);
        const hours = Math.floor((val % 1440) / 60);
        return hours > 0 ? `${days}d ${hours}h` : `${days}d`;
    }
    if (val >= 60) {
        const hours = Math.floor(val / 60);
        const mins = Math.round(val % 60);
        return mins > 0 ? `${hours}h ${mins}m` : `${hours}h`;
    }
    return `${Math.round(val)} min`;
};

export default function DepartmentReport({
    department,
    totalSavings = 0,
    averageFollowupTime = 0,
    averageCompletionTime = 0,
    totalTasksCompleted = 0,
    savingsBreakdown = { purchase_request: 0, stock_request: 0 },
    adminPerformance = [],
    departmentTrendData = { labels: [], data: [] },
}: DepartmentReportProps) {
    const prSavings = savingsBreakdown?.purchase_request || 0;
    const stSavings = savingsBreakdown?.stock_request || 0;
    const totalBreakdown = prSavings + stSavings;
    const prPercentage = totalBreakdown > 0 ? (prSavings / totalBreakdown) * 100 : 0;
    const stPercentage = totalBreakdown > 0 ? (stSavings / totalBreakdown) * 100 : 0;

    return (
        <>
            <Head title="Department Report" />
            <div className="py-6">
                <div className="w-full px-4 sm:px-6 lg:px-8">
                    {/* Header */}
                    <div className="mb-6">
                        <Link
                            href={route('purchasing.admin.dashboard')}
                            className="inline-flex items-center text-gray-600 hover:text-gray-900 mb-4"
                        >
                            <ArrowLeft className="w-4 h-4 mr-2" />
                            Back to Dashboard
                        </Link>
                        <div className="flex items-center gap-3">
                            <div className="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center">
                                <Building2 className="w-5 h-5 text-primary" />
                            </div>
                            <div>
                                <h1 className="text-2xl font-bold text-gray-900">Department Report</h1>
                                <p className="text-gray-500">{department?.name || 'Your Department'} - Performance Overview</p>
                            </div>
                        </div>
                    </div>

                    {/* Statistics Cards */}
                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                        {/* Total Tasks Completed */}
                        <div className="bg-white p-6 rounded-xl border border-gray-100 shadow-sm">
                            <div className="flex items-center gap-3">
                                <div className="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center">
                                    <CheckCircle2 className="w-5 h-5 text-primary" />
                                </div>
                                <div>
                                    <p className="text-sm text-gray-500">Tasks Completed</p>
                                    <p className="text-2xl font-bold text-gray-900">{totalTasksCompleted}</p>
                                </div>
                            </div>
                        </div>

                        {/* Total Savings */}
                        <div className="bg-white p-6 rounded-xl border border-gray-100 shadow-sm">
                            <div className="flex items-center gap-3">
                                <div className="w-10 h-10 rounded-lg bg-emerald-100 flex items-center justify-center">
                                    <DollarSign className="w-5 h-5 text-emerald-600" />
                                </div>
                                <div>
                                    <p className="text-sm text-gray-500">Total Savings</p>
                                    <p className="text-2xl font-bold text-emerald-600">{formatCurrency(totalSavings)}</p>
                                </div>
                            </div>
                        </div>

                        {/* Avg Follow-up Time */}
                        <div className="bg-white p-6 rounded-xl border border-gray-100 shadow-sm">
                            <div className="flex items-center gap-3">
                                <div className="w-10 h-10 rounded-lg bg-amber-100 flex items-center justify-center">
                                    <Clock className="w-5 h-5 text-amber-600" />
                                </div>
                                <div>
                                    <p className="text-sm text-gray-500">Avg Follow-up Time</p>
                                    <p className="text-2xl font-bold text-gray-900">{formatTime(averageFollowupTime)}</p>
                                </div>
                            </div>
                        </div>

                        {/* Avg Completion Time */}
                        <div className="bg-white p-6 rounded-xl border border-gray-100 shadow-sm">
                            <div className="flex items-center gap-3">
                                <div className="w-10 h-10 rounded-lg bg-blue-100 flex items-center justify-center">
                                    <TrendingUp className="w-5 h-5 text-blue-600" />
                                </div>
                                <div>
                                    <p className="text-sm text-gray-500">Avg Completion Time</p>
                                    <p className="text-2xl font-bold text-gray-900">{formatTime(averageCompletionTime)}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                        {/* Savings Breakdown */}
                        <div className="bg-white rounded-xl border border-gray-100 overflow-hidden shadow-sm">
                            <div className="px-5 py-4 border-b border-gray-100">
                                <h3 className="text-base font-semibold text-gray-900">Savings Breakdown</h3>
                                <p className="text-sm text-gray-500">By request type</p>
                            </div>
                            <div className="p-6">
                                <div className="space-y-4">
                                    {/* Purchase Request */}
                                    <div>
                                        <div className="flex items-center justify-between mb-2">
                                            <span className="text-sm font-medium text-gray-700">Purchase Request</span>
                                            <span className="text-sm font-semibold text-emerald-600">{formatCurrency(prSavings)}</span>
                                        </div>
                                        <div className="w-full bg-gray-200 rounded-full h-2">
                                            <div
                                                className="bg-primary h-2 rounded-full transition-all duration-300"
                                                style={{ width: `${prPercentage}%` }}
                                            />
                                        </div>
                                        <p className="text-xs text-gray-500 mt-1">{prPercentage.toFixed(1)}% of total</p>
                                    </div>

                                    {/* Stock Request */}
                                    <div>
                                        <div className="flex items-center justify-between mb-2">
                                            <span className="text-sm font-medium text-gray-700">Stock Request</span>
                                            <span className="text-sm font-semibold text-emerald-600">{formatCurrency(stSavings)}</span>
                                        </div>
                                        <div className="w-full bg-gray-200 rounded-full h-2">
                                            <div
                                                className="bg-emerald-600 h-2 rounded-full transition-all duration-300"
                                                style={{ width: `${stPercentage}%` }}
                                            />
                                        </div>
                                        <p className="text-xs text-gray-500 mt-1">{stPercentage.toFixed(1)}% of total</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Savings Trend */}
                        <div className="bg-white rounded-xl border border-gray-100 overflow-hidden shadow-sm">
                            <div className="px-5 py-4 border-b border-gray-100">
                                <h3 className="text-base font-semibold text-gray-900">Savings Trend</h3>
                                <p className="text-sm text-gray-500">Last 12 months</p>
                            </div>
                            <div className="p-6">
                                {departmentTrendData.labels.length > 0 ? (
                                    <div className="space-y-2">
                                        {departmentTrendData.labels.map((label, index) => (
                                            <div key={label} className="flex items-center justify-between">
                                                <span className="text-sm text-gray-600">{label}</span>
                                                <span className="text-sm font-medium text-emerald-600">
                                                    {formatCurrency(departmentTrendData.data[index])}
                                                </span>
                                            </div>
                                        ))}
                                    </div>
                                ) : (
                                    <div className="flex items-center justify-center h-32 text-gray-500">
                                        <div className="text-center">
                                            <BarChart3 className="w-8 h-8 mx-auto mb-2 text-gray-400" />
                                            <p className="text-sm">No trend data available</p>
                                        </div>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>

                    {/* Admin Performance Table */}
                    <div className="bg-white rounded-xl border border-gray-100 overflow-hidden shadow-sm">
                        <div className="px-5 py-4 border-b border-gray-100">
                            <div className="flex items-center gap-2">
                                <Users className="w-5 h-5 text-gray-500" />
                                <h3 className="text-base font-semibold text-gray-900">Admin Performance</h3>
                            </div>
                            <p className="text-sm text-gray-500">Individual performance metrics</p>
                        </div>
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Admin</th>
                                        <th className="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tasks Completed</th>
                                        <th className="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Savings</th>
                                        <th className="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Avg Savings %</th>
                                        <th className="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Avg Follow-up</th>
                                        <th className="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Avg Completion</th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-gray-100">
                                    {adminPerformance.length === 0 ? (
                                        <tr>
                                            <td colSpan={6} className="px-5 py-8 text-center text-sm text-gray-500">
                                                No admin performance data available.
                                            </td>
                                        </tr>
                                    ) : (
                                        adminPerformance.map((admin, index) => (
                                            <tr key={index} className="hover:bg-gray-50 transition-colors">
                                                <td className="px-5 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                    {admin.name}
                                                </td>
                                                <td className="px-5 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    {admin.tasks_completed}
                                                </td>
                                                <td className="px-5 py-4 whitespace-nowrap text-sm font-medium text-emerald-600">
                                                    {formatCurrency(admin.total_savings)}
                                                </td>
                                                <td className="px-5 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    {admin.avg_savings_percentage?.toFixed(1) || '0'}%
                                                </td>
                                                <td className="px-5 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    {formatTime(admin.avg_followup_time)}
                                                </td>
                                                <td className="px-5 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    {formatTime(admin.avg_completion_time)}
                                                </td>
                                            </tr>
                                        ))
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}
