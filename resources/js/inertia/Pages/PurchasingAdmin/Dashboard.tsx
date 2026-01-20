import React from 'react';
import { Head, usePage } from '@inertiajs/react';
import { BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer, PieChart, Pie, Cell, LineChart, Line } from 'recharts';
import { motion } from 'framer-motion';
import { TrendingUp, TrendingDown, Clock, CheckCircle, AlertCircle } from 'lucide-react';

interface DashboardProps {
    stats: {
        pending: number;
        in_progress: number;
        done: number;
    };
    recentTasks: Array<{
        id: number;
        taskable: {
            pr_number?: string;
            st_number?: string;
        };
        department: {
            name: string;
        };
        assigned_admin: {
            name: string;
        } | null;
        status: string;
        entered_at: string;
    }>;
    metrics: {
        total_tasks_completed: number;
        avg_followup_time: number;
        avg_completion_time: number;
        total_savings: number;
        avg_savings_percentage: number;
    };
    savingsTrend: {
        labels: string[];
        data: number[];
    };
    departmentBreakdown: Array<{
        department: string;
        count: number;
        percentage: number;
    }>;
    datePreset: string;
    dateRange: {
        from: string | null;
        to: string | null;
    };
    userRole: {
        is_purchasing_admin: boolean;
        is_management: boolean;
    };
}

const Dashboard: React.FC = () => {
    const { stats, recentTasks, metrics, savingsTrend, departmentBreakdown, datePreset, userRole } = usePage<DashboardProps>().props;

    // Format currency
    const formatCurrency = (amount: number) => {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0,
        }).format(amount);
    };

    // Format time in minutes to hours/minutes
    const formatTime = (minutes: number) => {
        if (minutes < 60) {
            return `${Math.round(minutes)}m`;
        }
        const hours = Math.floor(minutes / 60);
        const mins = Math.round(minutes % 60);
        return mins > 0 ? `${hours}h ${mins}m` : `${hours}h`;
    };

    // Prepare chart data
    const taskDistributionData = [
        { name: 'Pending', value: stats.pending, color: '#f59e0b' },
        { name: 'In Progress', value: stats.in_progress, color: '#3b82f6' },
        { name: 'Completed', value: stats.done, color: '#10b981' },
    ];

    const savingsTrendData = savingsTrend.labels.map((label, index) => ({
        month: label,
        savings: savingsTrend.data[index],
    }));

    const departmentChartData = departmentBreakdown.map(dept => ({
        name: dept.department,
        count: dept.count,
        percentage: dept.percentage,
    }));

    return (
        <>
            <Head title="Purchasing Admin Dashboard" />

            <div className="py-6">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    {/* Header */}
                    <div className="mb-6">
                        <h1 className="text-2xl font-bold text-gray-900">Purchasing Admin Dashboard</h1>
                        <p className="mt-1 text-sm text-gray-600">
                            {userRole.is_management ? 'Management Overview' : 'Your Tasks & Performance'}
                        </p>
                    </div>

                    {/* Stats Grid */}
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                        {/* Pending Tasks */}
                        <motion.div
                            initial={{ opacity: 0, y: 20 }}
                            animate={{ opacity: 1, y: 0 }}
                            transition={{ duration: 0.3 }}
                            className="bg-white rounded-xl border border-gray-100 p-6"
                        >
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-medium text-gray-600">Pending Tasks</p>
                                    <p className="text-3xl font-bold text-gray-900 mt-2">{stats.pending}</p>
                                </div>
                                <div className="p-3 bg-amber-100 rounded-lg">
                                    <Clock className="w-6 h-6 text-amber-600" />
                                </div>
                            </div>
                        </motion.div>

                        {/* In Progress */}
                        <motion.div
                            initial={{ opacity: 0, y: 20 }}
                            animate={{ opacity: 1, y: 0 }}
                            transition={{ duration: 0.3, delay: 0.1 }}
                            className="bg-white rounded-xl border border-gray-100 p-6"
                        >
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-medium text-gray-600">In Progress</p>
                                    <p className="text-3xl font-bold text-gray-900 mt-2">{stats.in_progress}</p>
                                </div>
                                <div className="p-3 bg-blue-100 rounded-lg">
                                    <AlertCircle className="w-6 h-6 text-blue-600" />
                                </div>
                            </div>
                        </motion.div>

                        {/* Completed */}
                        <motion.div
                            initial={{ opacity: 0, y: 20 }}
                            animate={{ opacity: 1, y: 0 }}
                            transition={{ duration: 0.3, delay: 0.2 }}
                            className="bg-white rounded-xl border border-gray-100 p-6"
                        >
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-medium text-gray-600">Completed</p>
                                    <p className="text-3xl font-bold text-gray-900 mt-2">{stats.done}</p>
                                </div>
                                <div className="p-3 bg-emerald-100 rounded-lg">
                                    <CheckCircle className="w-6 h-6 text-emerald-600" />
                                </div>
                            </div>
                        </motion.div>

                        {/* Total Savings */}
                        <motion.div
                            initial={{ opacity: 0, y: 20 }}
                            animate={{ opacity: 1, y: 0 }}
                            transition={{ duration: 0.3, delay: 0.3 }}
                            className="bg-white rounded-xl border border-gray-100 p-6"
                        >
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-medium text-gray-600">Total Savings</p>
                                    <p className="text-2xl font-bold text-gray-900 mt-2">{formatCurrency(metrics.total_savings)}</p>
                                    <p className="text-xs text-emerald-600 mt-1 flex items-center">
                                        <TrendingUp className="w-3 h-3 mr-1" />
                                        {metrics.avg_savings_percentage.toFixed(1)}% avg
                                    </p>
                                </div>
                                <div className="p-3 bg-indigo-100 rounded-lg">
                                    <TrendingUp className="w-6 h-6 text-indigo-600" />
                                </div>
                            </div>
                        </motion.div>
                    </div>

                    {/* Performance Metrics */}
                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                        <motion.div
                            initial={{ opacity: 0, y: 20 }}
                            animate={{ opacity: 1, y: 0 }}
                            transition={{ duration: 0.3, delay: 0.4 }}
                            className="bg-white rounded-xl border border-gray-100 p-6"
                        >
                            <h3 className="text-sm font-medium text-gray-600 mb-2">Tasks Completed</h3>
                            <p className="text-3xl font-bold text-gray-900">{metrics.total_tasks_completed}</p>
                            <p className="text-xs text-gray-500 mt-1">In selected period</p>
                        </motion.div>

                        <motion.div
                            initial={{ opacity: 0, y: 20 }}
                            animate={{ opacity: 1, y: 0 }}
                            transition={{ duration: 0.3, delay: 0.5 }}
                            className="bg-white rounded-xl border border-gray-100 p-6"
                        >
                            <h3 className="text-sm font-medium text-gray-600 mb-2">Avg Follow-up Time</h3>
                            <p className="text-3xl font-bold text-gray-900">{formatTime(metrics.avg_followup_time)}</p>
                            <p className="text-xs text-gray-500 mt-1">Time to start task</p>
                        </motion.div>

                        <motion.div
                            initial={{ opacity: 0, y: 20 }}
                            animate={{ opacity: 1, y: 0 }}
                            transition={{ duration: 0.3, delay: 0.6 }}
                            className="bg-white rounded-xl border border-gray-100 p-6"
                        >
                            <h3 className="text-sm font-medium text-gray-600 mb-2">Avg Completion Time</h3>
                            <p className="text-3xl font-bold text-gray-900">{formatTime(metrics.avg_completion_time)}</p>
                            <p className="text-xs text-gray-500 mt-1">Time to complete task</p>
                        </motion.div>
                    </div>

                    {/* Charts Row */}
                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                        {/* Task Distribution Pie Chart */}
                        <motion.div
                            initial={{ opacity: 0, y: 20 }}
                            animate={{ opacity: 1, y: 0 }}
                            transition={{ duration: 0.3, delay: 0.7 }}
                            className="bg-white rounded-xl border border-gray-100 p-6"
                        >
                            <h3 className="text-base font-semibold text-gray-900 mb-4">Task Distribution</h3>
                            <ResponsiveContainer width="100%" height={300}>
                                <PieChart>
                                    <Pie
                                        data={taskDistributionData}
                                        cx="50%"
                                        cy="50%"
                                        labelLine={false}
                                        label={({ name, percent }) => `${name}: ${(percent * 100).toFixed(0)}%`}
                                        outerRadius={80}
                                        fill="#8884d8"
                                        dataKey="value"
                                    >
                                        {taskDistributionData.map((entry, index) => (
                                            <Cell key={`cell-${index}`} fill={entry.color} />
                                        ))}
                                    </Pie>
                                    <Tooltip />
                                </PieChart>
                            </ResponsiveContainer>
                        </motion.div>

                        {/* Savings Trend Line Chart */}
                        <motion.div
                            initial={{ opacity: 0, y: 20 }}
                            animate={{ opacity: 1, y: 0 }}
                            transition={{ duration: 0.3, delay: 0.8 }}
                            className="bg-white rounded-xl border border-gray-100 p-6"
                        >
                            <h3 className="text-base font-semibold text-gray-900 mb-4">Savings Trend</h3>
                            <ResponsiveContainer width="100%" height={300}>
                                <LineChart data={savingsTrendData}>
                                    <CartesianGrid strokeDasharray="3 3" stroke="#f0f0f0" />
                                    <XAxis dataKey="month" tick={{ fontSize: 12 }} />
                                    <YAxis tick={{ fontSize: 12 }} />
                                    <Tooltip
                                        formatter={(value: number) => `${value.toFixed(1)}%`}
                                        contentStyle={{ borderRadius: '8px', border: '1px solid #e5e7eb' }}
                                    />
                                    <Line
                                        type="monotone"
                                        dataKey="savings"
                                        stroke="#10b981"
                                        strokeWidth={2}
                                        dot={{ fill: '#10b981', r: 4 }}
                                        activeDot={{ r: 6 }}
                                    />
                                </LineChart>
                            </ResponsiveContainer>
                        </motion.div>
                    </div>

                    {/* Department Performance Bar Chart */}
                    {departmentChartData.length > 0 && (
                        <motion.div
                            initial={{ opacity: 0, y: 20 }}
                            animate={{ opacity: 1, y: 0 }}
                            transition={{ duration: 0.3, delay: 0.9 }}
                            className="bg-white rounded-xl border border-gray-100 p-6 mb-6"
                        >
                            <h3 className="text-base font-semibold text-gray-900 mb-4">Department Performance</h3>
                            <ResponsiveContainer width="100%" height={300}>
                                <BarChart data={departmentChartData}>
                                    <CartesianGrid strokeDasharray="3 3" stroke="#f0f0f0" />
                                    <XAxis dataKey="name" tick={{ fontSize: 12 }} />
                                    <YAxis tick={{ fontSize: 12 }} />
                                    <Tooltip
                                        contentStyle={{ borderRadius: '8px', border: '1px solid #e5e7eb' }}
                                    />
                                    <Bar dataKey="count" fill="#6366f1" radius={[8, 8, 0, 0]} />
                                </BarChart>
                            </ResponsiveContainer>
                        </motion.div>
                    )}

                    {/* Recent Tasks */}
                    <motion.div
                        initial={{ opacity: 0, y: 20 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{ duration: 0.3, delay: 1.0 }}
                        className="bg-white rounded-xl border border-gray-100 overflow-hidden"
                    >
                        <div className="px-6 py-4 border-b border-gray-100">
                            <h3 className="text-base font-semibold text-gray-900">Recent Tasks</h3>
                        </div>
                        <div className="divide-y divide-gray-100">
                            {recentTasks.length === 0 ? (
                                <div className="px-6 py-8 text-center text-gray-500">
                                    No recent tasks
                                </div>
                            ) : (
                                recentTasks.map((task) => (
                                    <div key={task.id} className="px-6 py-4 hover:bg-gray-50 transition-colors">
                                        <div className="flex items-center justify-between">
                                            <div className="flex-1">
                                                <p className="text-sm font-medium text-gray-900">
                                                    {task.taskable.pr_number || task.taskable.st_number}
                                                </p>
                                                <p className="text-xs text-gray-500 mt-1">
                                                    {task.department.name}
                                                    {task.assigned_admin && ` • ${task.assigned_admin.name}`}
                                                </p>
                                            </div>
                                            <div className="flex items-center space-x-3">
                                                <span
                                                    className={`inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium ${
                                                        task.status === 'pending_followup'
                                                            ? 'bg-amber-100 text-amber-700'
                                                            : task.status === 'in_progress'
                                                            ? 'bg-blue-100 text-blue-700'
                                                            : 'bg-emerald-100 text-emerald-700'
                                                    }`}
                                                >
                                                    {task.status === 'pending_followup' ? 'Pending' : task.status === 'in_progress' ? 'In Progress' : 'Done'}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                ))
                            )}
                        </div>
                    </motion.div>
                </div>
            </div>
        </>
    );
};

export default Dashboard;
