import { Calendar, CheckCircle2, Clock, DollarSign, TrendingUp } from 'lucide-react';
import type { HistoryStats } from './historyUtils';
import { formatHistoryCurrency, formatHistoryTime } from './historyUtils';

interface HistoryStatsCardsProps {
    statistics: HistoryStats;
}

export function HistoryStatsCards({ statistics }: HistoryStatsCardsProps) {
    return (
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
            <div className="bg-white p-6 rounded-xl border border-gray-100 shadow-sm">
                <div className="flex items-center gap-3">
                    <div className="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center">
                        <CheckCircle2 className="w-5 h-5 text-primary" />
                    </div>
                    <div>
                        <p className="text-sm text-gray-500">Total Completed</p>
                        <p className="text-2xl font-bold text-gray-900">{statistics.total_completed}</p>
                    </div>
                </div>
            </div>

            <div className="bg-white p-6 rounded-xl border border-gray-100 shadow-sm">
                <div className="flex items-center gap-3">
                    <div className="w-10 h-10 rounded-lg bg-amber-100 flex items-center justify-center">
                        <Clock className="w-5 h-5 text-amber-600" />
                    </div>
                    <div>
                        <p className="text-sm text-gray-500">Avg Follow-up Time</p>
                        <p className="text-2xl font-bold text-gray-900">
                            {formatHistoryTime(statistics.avg_followup_time)}
                        </p>
                    </div>
                </div>
            </div>

            <div className="bg-white p-6 rounded-xl border border-gray-100 shadow-sm">
                <div className="flex items-center gap-3">
                    <div className="w-10 h-10 rounded-lg bg-blue-100 flex items-center justify-center">
                        <Calendar className="w-5 h-5 text-blue-600" />
                    </div>
                    <div>
                        <p className="text-sm text-gray-500">Avg Completion Time</p>
                        <p className="text-2xl font-bold text-gray-900">
                            {formatHistoryTime(statistics.avg_completion_time)}
                        </p>
                    </div>
                </div>
            </div>

            <div className="bg-white p-6 rounded-xl border border-gray-100 shadow-sm">
                <div className="flex items-center gap-3">
                    <div className="w-10 h-10 rounded-lg bg-emerald-100 flex items-center justify-center">
                        <DollarSign className="w-5 h-5 text-emerald-600" />
                    </div>
                    <div>
                        <p className="text-sm text-gray-500">Total Savings</p>
                        <p className="text-2xl font-bold text-emerald-600">
                            {formatHistoryCurrency(statistics.total_savings)}
                        </p>
                    </div>
                </div>
            </div>

            <div className="bg-white p-6 rounded-xl border border-gray-100 shadow-sm">
                <div className="flex items-center gap-3">
                    <div className="w-10 h-10 rounded-lg bg-emerald-100 flex items-center justify-center">
                        <TrendingUp className="w-5 h-5 text-emerald-600" />
                    </div>
                    <div>
                        <p className="text-sm text-gray-500">Avg Savings %</p>
                        <p className="text-2xl font-bold text-emerald-600">
                            {statistics.avg_savings_percentage ? `${Number(statistics.avg_savings_percentage).toFixed(1)}%` : '0%'}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    );
}
