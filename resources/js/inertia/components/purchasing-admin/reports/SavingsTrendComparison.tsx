import { BarChart3 } from 'lucide-react';
import type { ComparativeTrendData } from './reportTypes';
import { formatReportCurrency } from './reportTypes';

interface SavingsTrendComparisonProps {
    trendData: ComparativeTrendData;
}

export function SavingsTrendComparison({ trendData }: SavingsTrendComparisonProps) {
    return (
        <div className="bg-white rounded-xl border border-gray-100 overflow-hidden shadow-sm">
            <div className="px-5 py-4 border-b border-gray-100">
                <h3 className="text-base font-semibold text-gray-900">Savings Trend Comparison</h3>
                <p className="text-sm text-gray-500">Monthly savings by business unit (last 12 months)</p>
            </div>
            <div className="p-6">
                {trendData.labels.length > 0 && trendData.datasets.length > 0 ? (
                    <div className="space-y-4">
                        <div className="flex flex-wrap gap-4 mb-4">
                            {trendData.datasets.map((dataset) => (
                                <div key={dataset.label} className="flex items-center gap-2">
                                    <div
                                        className="w-3 h-3 rounded-full"
                                        style={{ backgroundColor: dataset.borderColor }}
                                    />
                                    <span className="text-sm text-gray-600">{dataset.label}</span>
                                </div>
                            ))}
                        </div>

                        <div className="overflow-x-auto">
                            <table className="min-w-full text-sm">
                                <thead>
                                    <tr className="border-b border-gray-200">
                                        <th className="py-2 px-3 text-left font-medium text-gray-500">Month</th>
                                        {trendData.datasets.map((dataset) => (
                                            <th key={dataset.label} className="py-2 px-3 text-right font-medium text-gray-500">
                                                {dataset.label}
                                            </th>
                                        ))}
                                    </tr>
                                </thead>
                                <tbody>
                                    {trendData.labels.map((label, index) => (
                                        <tr key={label} className="border-b border-gray-100">
                                            <td className="py-2 px-3 text-gray-600">{label}</td>
                                            {trendData.datasets.map((dataset) => (
                                                <td key={dataset.label} className="py-2 px-3 text-right text-emerald-600 font-medium">
                                                    {formatReportCurrency(dataset.data[index])}
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
    );
}
