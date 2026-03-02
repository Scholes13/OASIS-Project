import React from 'react';
import { Head, Link } from '@inertiajs/react';
import {
    Clock,
    DollarSign,
    TrendingUp,
    CheckCircle2,
    ArrowLeft,
    Building2,
    BarChart3,
    Globe,
} from 'lucide-react';
import { cn } from '@/lib/utils';
import type { PageProps } from '@/types';

interface BusinessUnitMetric {
    id: number;
    code: string;
    name: string;
    total_tasks: number;
    total_savings: number;
    avg_savings_percentage: number;
    avg_followup_time: number;
    avg_completion_time: number;
}

interface ConsolidatedReportProps extends PageProps {
    childBusinessUnits: { id: number; code: string; name: string }[];
    businessUnitMetrics: BusinessUnitMetric[];
    overallMetrics: {
        total_tasks: number;
        total_savings: number;
        avg_savings_percentage: number;
        avg_followup_time: number;
        avg_completion_time: number;
    };
    comparativeTrendData: {
        labels: string[];
        datasets: {
            label: string;
            data: number[];
            borderColor: string;
            backgroundColor: string;
        }[];
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

export default function ConsolidatedReport({
    childBusinessUnits = [],
    businessUnitMetrics = [],
    overallMetrics = {
        total_tasks: 0,
        total_savings: 0,
        avg_savings_percentage: 0,
        avg_followup_time: 0,
        avg_completion_time: 0,
    },
    comparativeTrendData = { labels: [], datasets: [] },
}: ConsolidatedReportProps) {
    const hasChildBUs = childBusinessUnits.length > 0;

    return (
        <>
            <Head title="Consolidated Report" />
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
                            <div className="w-10 h-10 rounded-lg bg-purple-100 flex items-center justify-center">
                                <Globe className="w-5 h-5 text-purple-600" />
                            </div>
                            <div>
                                <h1 className="text-2xl font-bold text-gray-900">Consolidated Report</h1>
                                <p className="text-gray-500">Cross-business unit performance overview</p>
                            </div>
                        </div>
                    </div>

                    {!hasChildBUs ? (
                        <div className="bg-white rounded-xl border border-gray-100 p-8 text-center">
                            <Building2 className="w-12 h-12 mx-auto mb-4 text-gray-400" />
                            <h3 className="text-lg font-medium text-gray-900 mb-2">No Child Business Units</h3>
                            <p className="text-gray-500">
                                Consolidated reports are only available for parent business units with child units.
                            </p>
                        </div>
                    ) : (
                        <>
                            {/* Overall Statistics Cards */}
                            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
                                {/* Total Tasks */}
                                <div className="bg-white p-6 rounded-xl border border-gray-100 shadow-sm">
                                    <div className="flex items-center gap-3">
                                        <div className="w-10 h-10 rounded-lg bg-primary flex items-center justify-center">
                                            <CheckCircle2 className="w-5 h-5 text-primary" />
                                        </div>
                                        <div>
                                            <p className="text-sm text-gray-500">Total Tasks</p>
                                            <p className="text-2xl font-bold text-gray-900">{overallMetrics.total_tasks}</p>
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
                                            <p className="text-2xl font-bold text-emerald-600">{formatCurrency(overallMetrics.total_savings)}</p>
                                        </div>
                                    </div>
                                </div>

                                {/* Avg Savings % */}
                                <div className="bg-white p-6 rounded-xl border border-gray-100 shadow-sm">
                                    <div className="flex items-center gap-3">
                                        <div className="w-10 h-10 rounded-lg bg-emerald-100 flex items-center justify-center">
                                            <TrendingUp className="w-5 h-5 text-emerald-600" />
                                        </div>
                                        <div>
                                            <p className="text-sm text-gray-500">Avg Savings %</p>
                                            <p className="text-2xl font-bold text-emerald-600">
                                                {overallMetrics.avg_savings_percentage?.toFixed(1) || '0'}%
                                            </p>
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
                                            <p className="text-sm text-gray-500">Avg Follow-up</p>
                                            <p className="text-2xl font-bold text-gray-900">{formatTime(overallMetrics.avg_followup_time)}</p>
                                        </div>
                                    </div>
                                </div>

                                {/* Avg Completion Time */}
                                <div className="bg-white p-6 rounded-xl border border-gray-100 shadow-sm">
                                    <div className="flex items-center gap-3">
                                        <div className="w-10 h-10 rounded-lg bg-blue-100 flex items-center justify-center">
                                            <BarChart3 className="w-5 h-5 text-blue-600" />
                                        </div>
                                        <div>
                                            <p className="text-sm text-gray-500">Avg Completion</p>
                                            <p className="text-2xl font-bold text-gray-900">{formatTime(overallMetrics.avg_completion_time)}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {/* Business Unit Comparison Table */}
                            <div className="bg-white rounded-xl border border-gray-100 overflow-hidden shadow-sm mb-6">
                                <div className="px-5 py-4 border-b border-gray-100">
                                    <div className="flex items-center gap-2">
                                        <Building2 className="w-5 h-5 text-gray-500" />
                                        <h3 className="text-base font-semibold text-gray-900">Business Unit Comparison</h3>
                                    </div>
                                    <p className="text-sm text-gray-500">Performance metrics by business unit</p>
                                </div>
                                <div className="overflow-x-auto">
                                    <table className="min-w-full divide-y divide-gray-200">
                                        <thead className="bg-gray-50">
                                            <tr>
                                                <th className="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Business Unit</th>
                                                <th className="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tasks Completed</th>
                                                <th className="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Savings</th>
                                                <th className="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Avg Savings %</th>
                                                <th className="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Avg Follow-up</th>
                                                <th className="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Avg Completion</th>
                                            </tr>
                                        </thead>
                                        <tbody className="bg-white divide-y divide-gray-100">
                                            {businessUnitMetrics.length === 0 ? (
                                                <tr>
                                                    <td colSpan={6} className="px-5 py-8 text-center text-sm text-gray-500">
                                                        No business unit data available.
                                                    </td>
                                                </tr>
                                            ) : (
                                                businessUnitMetrics.map((bu) => (
                                                    <tr key={bu.id} className="hover:bg-gray-50 transition-colors">
                                                        <td className="px-5 py-4 whitespace-nowrap">
                                                            <div className="flex items-center gap-2">
                                                                <span className="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                                                                    {bu.code}
                                                                </span>
                                                                <span className="text-sm font-medium text-gray-900">{bu.name}</span>
                                                            </div>
                                                        </td>
                                                        <td className="px-5 py-4 whitespace-nowrap text-sm text-gray-900">
                                                            {bu.total_tasks}
                                                        </td>
                                                        <td className="px-5 py-4 whitespace-nowrap text-sm font-medium text-emerald-600">
                                                            {formatCurrency(bu.total_savings)}
                                                        </td>
                                                        <td className="px-5 py-4 whitespace-nowrap text-sm text-gray-900">
                                                            {bu.avg_savings_percentage?.toFixed(1) || '0'}%
                                                        </td>
                                                        <td className="px-5 py-4 whitespace-nowrap text-sm text-gray-900">
                                                            {formatTime(bu.avg_followup_time)}
                                                        </td>
                                                        <td className="px-5 py-4 whitespace-nowrap text-sm text-gray-900">
                                                            {formatTime(bu.avg_completion_time)}
                                                        </td>
                                                    </tr>
                                                ))
                                            )}
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            {/* Comparative Trend */}
                            <div className="bg-white rounded-xl border border-gray-100 overflow-hidden shadow-sm">
                                <div className="px-5 py-4 border-b border-gray-100">
                                    <h3 className="text-base font-semibold text-gray-900">Savings Trend Comparison</h3>
                                    <p className="text-sm text-gray-500">Monthly savings by business unit (last 12 months)</p>
                                </div>
                                <div className="p-6">
                                    {comparativeTrendData.labels.length > 0 && comparativeTrendData.datasets.length > 0 ? (
                                        <div className="space-y-4">
                                            {/* Legend */}
                                            <div className="flex flex-wrap gap-4 mb-4">
                                                {comparativeTrendData.datasets.map((dataset) => (
                                                    <div key={dataset.label} className="flex items-center gap-2">
                                                        <div
                                                            className="w-3 h-3 rounded-full"
                                                            style={{ backgroundColor: dataset.borderColor }}
                                                        />
                                                        <span className="text-sm text-gray-600">{dataset.label}</span>
                                                    </div>
                                                ))}
                                            </div>

                                            {/* Simple table view of trend data */}
                                            <div className="overflow-x-auto">
                                                <table className="min-w-full text-sm">
                                                    <thead>
                                                        <tr className="border-b border-gray-200">
                                                            <th className="py-2 px-3 text-left font-medium text-gray-500">Month</th>
                                                            {comparativeTrendData.datasets.map((dataset) => (
                                                                <th key={dataset.label} className="py-2 px-3 text-right font-medium text-gray-500">
                                                                    {dataset.label}
                                                                </th>
                                                            ))}
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        {comparativeTrendData.labels.map((label, index) => (
                                                            <tr key={label} className="border-b border-gray-100">
                                                                <td className="py-2 px-3 text-gray-600">{label}</td>
                                                                {comparativeTrendData.datasets.map((dataset) => (
                                                                    <td key={dataset.label} className="py-2 px-3 text-right text-emerald-600 font-medium">
                                                                        {formatCurrency(dataset.data[index])}
                                                                    </td>
                                                                ))}
                                                            </tr>
                                                        ))}
                                                    </tbody>
                                                </table>
                                            </div>
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
                        </>
                    )}
                </div>
            </div>
        </>
    );
}
