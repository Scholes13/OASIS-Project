import { CheckCircle, Clock, TrendingUp, Users } from 'lucide-react';
import { motion } from 'framer-motion';
import type { DashboardMetricsData, DashboardStats } from './dashboardTypes';
import { formatDashboardCurrency, formatDashboardTime } from './dashboardTypes';

interface DashboardMetricsProps {
    stats: DashboardStats;
    metrics: DashboardMetricsData;
}

export function DashboardMetrics({ stats, metrics }: DashboardMetricsProps) {
    return (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
            <motion.div
                initial={{ opacity: 0, y: 20 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ duration: 0.3 }}
                className="bg-white rounded-xl border border-gray-100 p-6 shadow-sm"
            >
                <div className="flex items-center justify-between">
                    <div>
                        <p className="text-sm font-medium text-gray-500">Pending Tasks</p>
                        <p className="text-3xl font-bold text-gray-900 mt-2">{stats.pending}</p>
                        <p className="text-xs text-amber-600 mt-1 font-medium">Needs Attention</p>
                    </div>
                    <div className="p-3 bg-amber-50 rounded-lg">
                        <Clock className="w-6 h-6 text-amber-600" />
                    </div>
                </div>
            </motion.div>

            <motion.div
                initial={{ opacity: 0, y: 20 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ duration: 0.3, delay: 0.1 }}
                className="bg-white rounded-xl border border-gray-100 p-6 shadow-sm"
            >
                <div className="flex items-center justify-between">
                    <div>
                        <p className="text-sm font-medium text-gray-500">Avg Process Time</p>
                        <p className="text-3xl font-bold text-gray-900 mt-2">
                            {formatDashboardTime(metrics.avg_completion_time)}
                        </p>
                        <p className="text-xs text-gray-400 mt-1">Speed</p>
                    </div>
                    <div className="p-3 bg-blue-50 rounded-lg">
                        <TrendingUp className="w-6 h-6 text-blue-600" />
                    </div>
                </div>
            </motion.div>

            <motion.div
                initial={{ opacity: 0, y: 20 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ duration: 0.3, delay: 0.2 }}
                className="bg-white rounded-xl border border-gray-100 p-6 shadow-sm"
            >
                <div className="flex items-center justify-between">
                    <div>
                        <p className="text-sm font-medium text-gray-500">Avg Follow Up</p>
                        <p className="text-3xl font-bold text-gray-900 mt-2">
                            {formatDashboardTime(metrics.avg_followup_time)}
                        </p>
                        <p className="text-xs text-gray-400 mt-1">Response</p>
                    </div>
                    <div className="p-3 bg-primary/10 rounded-lg">
                        <Users className="w-6 h-6 text-primary" />
                    </div>
                </div>
            </motion.div>

            <motion.div
                initial={{ opacity: 0, y: 20 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ duration: 0.3, delay: 0.3 }}
                className="bg-white rounded-xl border border-emerald-100 p-6 shadow-sm ring-1 ring-emerald-50"
            >
                <div className="flex items-center justify-between">
                    <div>
                        <p className="text-sm font-medium text-gray-500">Total Savings</p>
                        <p className="text-2xl font-bold text-emerald-600 mt-2">
                            {formatDashboardCurrency(metrics.total_savings)}
                        </p>
                        <div className="flex items-center mt-1">
                            <TrendingUp className="w-3 h-3 text-emerald-500 mr-1" />
                            <p className="text-xs text-emerald-600 font-medium">
                                {metrics.avg_savings_percentage.toFixed(1)}% avg
                            </p>
                        </div>
                    </div>
                    <div className="p-3 bg-emerald-50 rounded-lg">
                        <CheckCircle className="w-6 h-6 text-emerald-600" />
                    </div>
                </div>
            </motion.div>
        </div>
    );
}
