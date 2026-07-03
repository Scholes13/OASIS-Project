import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/Card';
import { cn } from '@/lib/utils';
import type { SlaCompliance } from './types';

export default function ReportingSlaCompliance({ sla }: { sla: SlaCompliance }) {
    return (
        <div className="space-y-4">
            <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <div className="rounded-lg border border-gray-200 bg-white p-4 shadow-none">
                    <p className="mb-1 text-xs font-semibold uppercase tracking-wide text-gray-500">SLA Compliance Rate</p>
                    <p
                        className={cn(
                            'text-3xl font-bold tabular-nums',
                            sla.rate >= 90 ? 'text-emerald-600' :
                                sla.rate >= 70 ? 'text-amber-600' : 'text-red-600'
                        )}
                    >
                        {sla.rate}%
                    </p>
                </div>
                <div className="rounded-lg border border-gray-200 bg-white p-4 shadow-none">
                    <p className="mb-1 text-xs font-semibold uppercase tracking-wide text-gray-500">Total Resolved</p>
                    <p className="text-3xl font-bold tabular-nums text-gray-900">{sla.total_resolved}</p>
                </div>
                <div className="rounded-lg border border-gray-200 bg-white p-4 shadow-none">
                    <p className="mb-1 text-xs font-semibold uppercase tracking-wide text-gray-500">Within SLA</p>
                    <p className="text-3xl font-bold tabular-nums text-emerald-600">{sla.within_sla}</p>
                </div>
                <div className="rounded-lg border border-gray-200 bg-white p-4 shadow-none">
                    <p className="mb-1 text-xs font-semibold uppercase tracking-wide text-gray-500">SLA Breached</p>
                    <p className="text-3xl font-bold tabular-nums text-red-600">{sla.breached}</p>
                </div>
            </div>

            <Card className="border-gray-200 bg-white shadow-none">
                <CardHeader className="pb-3">
                    <CardTitle className="text-base font-semibold text-gray-900">SLA Performance by Priority</CardTitle>
                </CardHeader>
                <CardContent className="p-0">
                    <table className="min-w-full divide-y divide-gray-200">
                        <thead className="bg-gray-50">
                            <tr>
                                <th className="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Priority</th>
                                <th className="px-5 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">SLA Target</th>
                                <th className="px-5 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Avg Actual</th>
                                <th className="px-5 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Resolved</th>
                                <th className="px-5 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Within SLA</th>
                                <th className="px-5 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Breached</th>
                                <th className="px-5 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Compliance</th>
                            </tr>
                        </thead>
                        <tbody className="bg-white divide-y divide-gray-100">
                            {sla.by_priority.map((row) => (
                                <tr
                                    key={row.priority}
                                    className="hover:bg-gray-50/50"
                                >
                                    <td className="px-5 py-3 text-sm font-medium text-gray-900">{row.label}</td>
                                    <td className="px-5 py-3 text-sm text-center text-gray-600">{row.sla_hours > 0 ? `${row.sla_hours}h` : '-'}</td>
                                    <td
                                        className={cn(
                                            'px-5 py-3 text-sm text-center font-medium',
                                            row.total > 0 && row.avg_hours > row.sla_hours ? 'text-red-600' : 'text-gray-600'
                                        )}
                                    >
                                        {row.total > 0 ? `${row.avg_hours}h` : '-'}
                                    </td>
                                    <td className="px-5 py-3 text-sm text-center text-gray-900 font-medium">{row.total}</td>
                                    <td className="px-5 py-3 text-sm text-center text-emerald-600 font-medium">{row.within_sla}</td>
                                    <td className="px-5 py-3 text-sm text-center text-red-600 font-medium">{row.breached}</td>
                                    <td className="px-5 py-3 text-center">
                                        {row.total > 0 ? (
                                            <span
                                                className={cn(
                                                    'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                                                    row.compliance_rate >= 90 ? 'bg-emerald-100 text-emerald-700' :
                                                        row.compliance_rate >= 70 ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700'
                                                )}
                                            >
                                                {row.compliance_rate}%
                                            </span>
                                        ) : (
                                            <span className="text-sm text-gray-400">-</span>
                                        )}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </CardContent>
            </Card>
        </div>
    );
}
