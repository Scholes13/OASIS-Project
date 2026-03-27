import { Head, usePage, Link, router } from '@inertiajs/react';
import type { PageProps } from '@/types';
import { BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer, PieChart, Pie, Cell, LineChart, Line } from 'recharts';
import { motion } from 'framer-motion';
import { TrendingUp, TrendingDown, Clock, CheckCircle, AlertCircle, Users } from 'lucide-react';

interface DashboardProps extends PageProps {
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
    const totalTasks = stats.pending + stats.in_progress + stats.done;

    const handleFilterChange = (e: React.ChangeEvent<HTMLSelectElement>) => {
        router.get(
            route('purchasing.admin.dashboard'),
            { date_preset: e.target.value },
            { preserveState: true, preserveScroll: true }
        );
    };

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
    // Format time in minutes to hours/minutes
    // Dashboard: avg followup & completion min 1 if data exists
    const formatTime = (minutes: number) => {
        if (minutes === 0) return '0m';
        if (minutes > 0 && minutes < 1) {
            return `1m`; // Minimal display as requested
        }
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
                <div className="w-full px-4 sm:px-6 lg:px-8">
                    {/* Header */}
                    <div className="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div>
                            <h1 className="text-2xl font-bold text-gray-900">Purchasing Admin Dashboard</h1>
                            <p className="mt-1 text-sm text-gray-600">
                                {userRole.is_management ? 'Management Overview' : 'Your Tasks & Performance'}
                            </p>
                        </div>

                        {/* Filter */}
                        <div className="flex items-center space-x-2">
                            <select
                                value={datePreset}
                                onChange={handleFilterChange}
                                className="block w-full sm:w-auto rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary sm:text-sm pl-3 pr-10 py-2"
                            >
                                <option value="this_month">This Month</option>
                                <option value="last_month">Last Month</option>
                                <option value="this_year">This Year</option>
                                <option value="all_time">All Time</option>
                            </select>
                        </div>
                    </div>

                    {/* Row 1: Performance Summary (4 Columns) */}
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                        {/* 1. Pending Tasks (Focus) */}
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

                        {/* 2. Avg Process Time */}
                        <motion.div
                            initial={{ opacity: 0, y: 20 }}
                            animate={{ opacity: 1, y: 0 }}
                            transition={{ duration: 0.3, delay: 0.1 }}
                            className="bg-white rounded-xl border border-gray-100 p-6 shadow-sm"
                        >
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-medium text-gray-500">Avg Process Time</p>
                                    <p className="text-3xl font-bold text-gray-900 mt-2">{formatTime(metrics.avg_completion_time)}</p>
                                    <p className="text-xs text-gray-400 mt-1">Speed</p>
                                </div>
                                <div className="p-3 bg-blue-50 rounded-lg">
                                    <TrendingUp className="w-6 h-6 text-blue-600" />
                                </div>
                            </div>
                        </motion.div>

                        {/* 3. Avg Follow Up */}
                        <motion.div
                            initial={{ opacity: 0, y: 20 }}
                            animate={{ opacity: 1, y: 0 }}
                            transition={{ duration: 0.3, delay: 0.2 }}
                            className="bg-white rounded-xl border border-gray-100 p-6 shadow-sm"
                        >
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-medium text-gray-500">Avg Follow Up</p>
                                    <p className="text-3xl font-bold text-gray-900 mt-2">{formatTime(metrics.avg_followup_time)}</p>
                                    <p className="text-xs text-gray-400 mt-1">Response</p>
                                </div>
                                <div className="p-3 bg-primary/10 rounded-lg">
                                    <Users className="w-6 h-6 text-primary" />
                                </div>
                            </div>
                        </motion.div>

                        {/* 4. Total Savings (Green Highlight) */}
                        <motion.div
                            initial={{ opacity: 0, y: 20 }}
                            animate={{ opacity: 1, y: 0 }}
                            transition={{ duration: 0.3, delay: 0.3 }}
                            className="bg-white rounded-xl border border-emerald-100 p-6 shadow-sm ring-1 ring-emerald-50"
                        >
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-medium text-gray-500">Total Savings</p>
                                    <p className="text-2xl font-bold text-emerald-600 mt-2">{formatCurrency(metrics.total_savings)}</p>
                                    <div className="flex items-center mt-1">
                                        <TrendingUp className="w-3 h-3 text-emerald-500 mr-1" />
                                        <p className="text-xs text-emerald-600 font-medium">{metrics.avg_savings_percentage.toFixed(1)}% avg</p>
                                    </div>
                                </div>
                                <div className="p-3 bg-emerald-50 rounded-lg">
                                    <CheckCircle className="w-6 h-6 text-emerald-600" />
                                </div>
                            </div>
                        </motion.div>
                    </div>

                    {/* Row 2: Main Content (1:2 Ratio) */}
                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                        {/* Col Kiri (35% approx - 1 col): Task Distribution */}
                        <motion.div
                            initial={{ opacity: 0, y: 20 }}
                            animate={{ opacity: 1, y: 0 }}
                            transition={{ duration: 0.3, delay: 0.4 }}
                            className="lg:col-span-1 bg-white rounded-xl border border-gray-100 p-6 flex flex-col shadow-sm"
                        >
                            <h3 className="text-base font-semibold text-gray-900 mb-4">Task Distribution</h3>
                            <div className="flex-1 min-h-[200px] relative">
                                <ResponsiveContainer width="100%" height={220}>
                                    <PieChart>
                                        <Pie
                                            data={taskDistributionData}
                                            cx="50%"
                                            cy="50%"
                                            innerRadius={60}
                                            outerRadius={80}
                                            paddingAngle={5}
                                            dataKey="value"
                                        >
                                            {taskDistributionData.map((entry, index) => (
                                                <Cell key={`cell-${index}`} fill={entry.color} strokeWidth={0} />
                                            ))}
                                        </Pie>
                                        <Tooltip
                                            contentStyle={{ borderRadius: '8px', border: 'none', boxShadow: '0 4px 6px -1px rgb(0 0 0 / 0.1)' }}
                                            itemStyle={{ fontSize: '12px', fontWeight: 500 }}
                                        />
                                    </PieChart>
                                </ResponsiveContainer>
                                {/* Center Text Overlay */}
                                <div className="absolute inset-0 flex items-center justify-center pointer-events-none">
                                    <div className="text-center">
                                        <span className="block text-2xl font-bold text-gray-900">{totalTasks}</span>
                                        <span className="text-xs text-gray-500">Total</span>
                                    </div>
                                </div>
                            </div>

                            {/* List Summary Below Chart */}
                            <div className="mt-4 space-y-3">
                                <div className="flex items-center justify-between p-2 bg-gray-50 rounded-lg">
                                    <div className="flex items-center">
                                        <div className="w-3 h-3 rounded-full bg-emerald-500 mr-2"></div>
                                        <span className="text-sm text-gray-600">Completed</span>
                                    </div>
                                    <span className="text-sm font-semibold text-gray-900">{stats.done}</span>
                                </div>
                                <div className="flex items-center justify-between p-2 bg-gray-50 rounded-lg">
                                    <div className="flex items-center">
                                        <div className="w-3 h-3 rounded-full bg-blue-500 mr-2"></div>
                                        <span className="text-sm text-gray-600">In Progress</span>
                                    </div>
                                    <span className="text-sm font-semibold text-gray-900">{stats.in_progress}</span>
                                </div>
                                <div className="flex items-center justify-between p-2 bg-gray-50 rounded-lg">
                                    <div className="flex items-center">
                                        <div className="w-3 h-3 rounded-full bg-amber-500 mr-2"></div>
                                        <span className="text-sm text-gray-600">Pending</span>
                                    </div>
                                    <span className="text-sm font-semibold text-gray-900">{stats.pending}</span>
                                </div>
                            </div>
                        </motion.div>

                        {/* Col Kanan (65% approx - 2 cols): Financial & Efficiency Trend */}
                        <motion.div
                            initial={{ opacity: 0, y: 20 }}
                            animate={{ opacity: 1, y: 0 }}
                            transition={{ duration: 0.3, delay: 0.5 }}
                            className="lg:col-span-2 bg-white rounded-xl border border-gray-100 p-6 flex flex-col shadow-sm"
                        >
                            <div className="flex items-center justify-between mb-6">
                                <div>
                                    <h3 className="text-base font-semibold text-gray-900">Savings Trend</h3>
                                    <p className="text-sm text-gray-500">Financial performance over time</p>
                                </div>
                                <div className="flex items-center space-x-2 text-sm text-gray-500">
                                    <div className="flex items-center">
                                        <span className="w-3 h-3 rounded-full bg-emerald-500 mr-1"></span>
                                        Savings
                                    </div>
                                </div>
                            </div>

                            <div className="flex-1 w-full h-[300px]">
                                <ResponsiveContainer width="100%" height="100%">
                                    <LineChart data={savingsTrendData} margin={{ top: 10, right: 10, bottom: 0, left: 0 }}>
                                        <CartesianGrid strokeDasharray="3 3" stroke="#f3f4f6" vertical={false} />
                                        <XAxis
                                            dataKey="month"
                                            tick={{ fontSize: 12, fill: '#6b7280' }}
                                            tickLine={false}
                                            axisLine={false}
                                            dy={10}
                                        />
                                        <YAxis
                                            tick={{ fontSize: 12, fill: '#6b7280' }}
                                            tickLine={false}
                                            axisLine={false}
                                            tickFormatter={(value) => {
                                                if (value >= 1000000) return `${(value / 1000000).toFixed(0)}M`;
                                                if (value >= 1000) return `${(value / 1000).toFixed(0)}k`;
                                                return value;
                                            }}
                                        />
                                        <Tooltip
                                            formatter={(value?: number) => [formatCurrency(value ?? 0), 'Savings']}
                                            contentStyle={{ borderRadius: '8px', border: 'none', boxShadow: '0 10px 15px -3px rgb(0 0 0 / 0.1)' }}
                                            labelStyle={{ color: '#6b7280', fontSize: '12px', marginBottom: '8px' }}
                                        />
                                        <Line
                                            type="monotone"
                                            dataKey="savings"
                                            stroke="#10b981"
                                            strokeWidth={3}
                                            dot={{ fill: '#fff', stroke: '#10b981', strokeWidth: 2, r: 4 }}
                                            activeDot={{ r: 6, fill: '#10b981', stroke: '#fff', strokeWidth: 2 }}
                                        />
                                    </LineChart>
                                </ResponsiveContainer>
                            </div>
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
                            <div className="h-[300px]">
                                <ResponsiveContainer width="100%" height="100%">
                                    <BarChart data={departmentChartData} margin={{ top: 0, right: 0, bottom: 0, left: 0 }}>
                                        <CartesianGrid strokeDasharray="3 3" stroke="#f3f4f6" vertical={false} />
                                        <XAxis
                                            dataKey="name"
                                            tick={{ fontSize: 11, fill: '#6b7280' }}
                                            tickLine={false}
                                            axisLine={false}
                                            dy={10}
                                        />
                                        <YAxis
                                            tick={{ fontSize: 11, fill: '#6b7280' }}
                                            tickLine={false}
                                            axisLine={false}
                                        />
                                        <Tooltip
                                            cursor={{ fill: '#f9fafb' }}
                                            contentStyle={{ borderRadius: '8px', border: 'none', boxShadow: '0 4px 6px -1px rgb(0 0 0 / 0.1)' }}
                                        />
                                        <Bar dataKey="count" radius={[6, 6, 0, 0]} maxBarSize={60}>
                                            {departmentChartData.map((entry, index) => (
                                                <Cell key={`cell-${index}`} fill={`url(#colorGradient${index})`} />
                                            ))}
                                        </Bar>
                                        <defs>
                                            {departmentChartData.map((entry, index) => (
                                                <linearGradient key={`colorGradient${index}`} id={`colorGradient${index}`} x1="0" y1="0" x2="0" y2="1">
                                                    <stop offset="0%" stopColor="#6366f1" stopOpacity={0.8} />
                                                    <stop offset="95%" stopColor="#818cf8" stopOpacity={0.4} />
                                                </linearGradient>
                                            ))}
                                        </defs>
                                    </BarChart>
                                </ResponsiveContainer>
                            </div>
                        </motion.div>
                    )}

                    {/* Recent Tasks */}
                    <motion.div
                        initial={{ opacity: 0, y: 20 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{ duration: 0.3, delay: 1.0 }}
                        className="bg-white rounded-xl border border-gray-100 overflow-hidden"
                    >
                        <div className="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                            <h3 className="text-base font-semibold text-gray-900">Recent Tasks</h3>
                            <Link href={route('purchasing.admin.tasks')} className="text-sm text-primary hover:text-primary font-medium">
                                View All →
                            </Link>
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
                                                    className={`inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium ${task.status === 'pending_followup'
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
