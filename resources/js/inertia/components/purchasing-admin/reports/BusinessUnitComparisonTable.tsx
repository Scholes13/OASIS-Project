import { Building2 } from 'lucide-react';
import type { BusinessUnitMetric } from './reportTypes';
import { formatReportCurrency, formatReportTime } from './reportTypes';

interface BusinessUnitComparisonTableProps {
    metrics: BusinessUnitMetric[];
}

export function BusinessUnitComparisonTable({ metrics }: BusinessUnitComparisonTableProps) {
    return (
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
                        {metrics.length === 0 ? (
                            <tr>
                                <td colSpan={6} className="px-5 py-8 text-center text-sm text-gray-500">
                                    No business unit data available.
                                </td>
                            </tr>
                        ) : (
                            metrics.map((bu) => (
                                <tr key={bu.id} className="hover:bg-gray-50 transition-colors">
                                    <td className="px-5 py-4 whitespace-nowrap">
                                        <div className="flex items-center gap-2">
                                            <span className="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                                                {bu.code}
                                            </span>
                                            <span className="text-sm font-medium text-gray-900">{bu.name}</span>
                                        </div>
                                    </td>
                                    <td className="px-5 py-4 whitespace-nowrap text-sm text-gray-900">{bu.total_tasks}</td>
                                    <td className="px-5 py-4 whitespace-nowrap text-sm font-medium text-emerald-600">
                                        {formatReportCurrency(bu.total_savings)}
                                    </td>
                                    <td className="px-5 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {bu.avg_savings_percentage?.toFixed(1) || '0'}%
                                    </td>
                                    <td className="px-5 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {formatReportTime(bu.avg_followup_time)}
                                    </td>
                                    <td className="px-5 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {formatReportTime(bu.avg_completion_time)}
                                    </td>
                                </tr>
                            ))
                        )}
                    </tbody>
                </table>
            </div>
        </div>
    );
}
