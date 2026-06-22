import { Link } from '@inertiajs/react';
import { ExternalLink } from 'lucide-react';
import type { AdminTask } from '@/components/purchasing-admin/types';
import { cn } from '@/lib/utils';
import { HistoryStatusBadge } from './HistoryStatusBadge';
import { formatHistoryCurrency, formatHistoryDate, formatHistoryTime, getHistoryTaskNumber } from './historyUtils';

interface HistoryTableProps {
    tasks: AdminTask[];
    showAdmin?: boolean;
    showPrices?: boolean;
}

export function HistoryTable({ tasks, showAdmin = false, showPrices = false }: HistoryTableProps) {
    const colSpan = 8 + (showAdmin ? 1 : 0) + (showPrices ? 2 : 0);

    return (
        <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-gray-200">
                <thead className="bg-gray-50">
                    <tr>
                        <th className="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Document</th>
                        <th className="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Business Unit</th>
                        {showAdmin && (
                            <th className="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Admin</th>
                        )}
                        <th className="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th className="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Entered</th>
                        <th className="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Follow-up Time</th>
                        <th className="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Completion Time</th>
                        {showPrices && (
                            <>
                                <th className="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estimated Price</th>
                                <th className="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Realized Price</th>
                            </>
                        )}
                        <th className="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Savings</th>
                        <th className="px-5 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody className="bg-white divide-y divide-gray-100">
                    {tasks.length === 0 ? (
                        <tr>
                            <td colSpan={colSpan} className="px-5 py-8 text-center text-sm text-gray-500">
                                No task history found for the selected filters.
                            </td>
                        </tr>
                    ) : (
                        tasks.map((task) => (
                            <tr key={task.id} className="hover:bg-gray-50 transition-colors">
                                <td className="px-5 py-4 whitespace-nowrap text-sm font-medium text-gray-900 font-mono">
                                    {getHistoryTaskNumber(task) || 'N/A'}
                                </td>
                                <td className="px-5 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {task.business_unit?.name || 'N/A'}
                                </td>
                                {showAdmin && (
                                    <td className="px-5 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {task.assigned_admin?.name || 'N/A'}
                                    </td>
                                )}
                                <td className="px-5 py-4 whitespace-nowrap text-sm">
                                    <HistoryStatusBadge status={task.status} />
                                </td>
                                <td className="px-5 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {formatHistoryDate(task.entered_at)}
                                </td>
                                <td className="px-5 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {formatHistoryTime(task.followup_time_minutes)}
                                </td>
                                <td className="px-5 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {formatHistoryTime(task.completion_time_minutes)}
                                </td>
                                {showPrices && (
                                    <>
                                        <td className="px-5 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {formatHistoryCurrency(task.estimated_total_price)}
                                        </td>
                                        <td className="px-5 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {formatHistoryCurrency(task.realized_total_price)}
                                        </td>
                                    </>
                                )}
                                <td className="px-5 py-4 whitespace-nowrap text-sm">
                                    {task.savings_amount !== null ? (
                                        <div className="flex flex-col">
                                            <span
                                                className={cn(
                                                    'font-medium',
                                                    Number(task.savings_amount) >= 0 ? 'text-emerald-600' : 'text-red-600'
                                                )}
                                            >
                                                {formatHistoryCurrency(task.savings_amount)}
                                            </span>
                                            <span className="text-xs text-gray-500">
                                                ({task.savings_percentage ? Number(task.savings_percentage).toFixed(1) : '0'}%)
                                            </span>
                                        </div>
                                    ) : (
                                        <span className="text-gray-400">-</span>
                                    )}
                                </td>
                                <td className="px-5 py-4 whitespace-nowrap text-sm text-center">
                                    <Link
                                        href={route('purchasing.admin.tasks.show', { taskId: task.id })}
                                        className="inline-flex items-center px-3 py-1.5 text-sm text-primary hover:text-primary hover:bg-blue-600 rounded-md transition-colors font-medium"
                                    >
                                        <ExternalLink className="w-4 h-4 mr-1" />
                                        Detail
                                    </Link>
                                </td>
                            </tr>
                        ))
                    )}
                </tbody>
            </table>
        </div>
    );
}
