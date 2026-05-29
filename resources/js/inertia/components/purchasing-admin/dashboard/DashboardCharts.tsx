import { motion } from 'framer-motion';
import { Bar, BarChart, CartesianGrid, Cell, Line, LineChart, Pie, PieChart, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts';
import type { DepartmentBreakdownItem, DashboardStats } from './dashboardTypes';
import { formatDashboardCurrency } from './dashboardTypes';

interface DashboardChartsProps {
    stats: DashboardStats;
    savingsTrend: {
        labels: string[];
        data: number[];
    };
    departmentBreakdown: DepartmentBreakdownItem[];
}

export function DashboardCharts({ stats, savingsTrend, departmentBreakdown }: DashboardChartsProps) {
    const totalTasks = stats.pending + stats.in_progress + stats.done;
    const taskDistributionData = [
        { name: 'Pending', value: stats.pending, color: '#f59e0b' },
        { name: 'In Progress', value: stats.in_progress, color: '#3b82f6' },
        { name: 'Completed', value: stats.done, color: '#10b981' },
    ];
    const savingsTrendData = savingsTrend.labels.map((label, index) => ({
        month: label,
        savings: savingsTrend.data[index],
    }));
    const departmentChartData = departmentBreakdown.map((dept) => ({
        name: dept.department,
        count: dept.count,
        percentage: dept.percentage,
    }));

    return (
        <>
            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
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
                                <Pie data={taskDistributionData} cx="50%" cy="50%" innerRadius={60} outerRadius={80} paddingAngle={5} dataKey="value">
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
                        <div className="absolute inset-0 flex items-center justify-center pointer-events-none">
                            <div className="text-center">
                                <span className="block text-2xl font-bold text-gray-900">{totalTasks}</span>
                                <span className="text-xs text-gray-500">Total</span>
                            </div>
                        </div>
                    </div>

                    <div className="mt-4 space-y-3">
                        {taskDistributionData.slice().reverse().map((item) => (
                            <div key={item.name} className="flex items-center justify-between p-2 bg-gray-50 rounded-lg">
                                <div className="flex items-center">
                                    <div className="w-3 h-3 rounded-full mr-2" style={{ backgroundColor: item.color }} />
                                    <span className="text-sm text-gray-600">{item.name === 'Completed' ? 'Completed' : item.name}</span>
                                </div>
                                <span className="text-sm font-semibold text-gray-900">{item.value}</span>
                            </div>
                        ))}
                    </div>
                </motion.div>

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
                            <span className="w-3 h-3 rounded-full bg-emerald-500 mr-1" />
                            Savings
                        </div>
                    </div>

                    <div className="flex-1 w-full h-[300px]">
                        <ResponsiveContainer width="100%" height="100%">
                            <LineChart data={savingsTrendData} margin={{ top: 10, right: 10, bottom: 0, left: 0 }}>
                                <CartesianGrid strokeDasharray="3 3" stroke="#f3f4f6" vertical={false} />
                                <XAxis dataKey="month" tick={{ fontSize: 12, fill: '#6b7280' }} tickLine={false} axisLine={false} dy={10} />
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
                                    formatter={(value?: number) => [formatDashboardCurrency(value ?? 0), 'Savings']}
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
                                <XAxis dataKey="name" tick={{ fontSize: 11, fill: '#6b7280' }} tickLine={false} axisLine={false} dy={10} />
                                <YAxis tick={{ fontSize: 11, fill: '#6b7280' }} tickLine={false} axisLine={false} />
                                <Tooltip cursor={{ fill: '#f9fafb' }} contentStyle={{ borderRadius: '8px', border: 'none', boxShadow: '0 4px 6px -1px rgb(0 0 0 / 0.1)' }} />
                                <Bar dataKey="count" radius={[6, 6, 0, 0]} maxBarSize={60}>
                                    {departmentChartData.map((entry, index) => (
                                        <Cell key={`cell-${entry.name}`} fill={`url(#colorGradient${index})`} />
                                    ))}
                                </Bar>
                                <defs>
                                    {departmentChartData.map((entry, index) => (
                                        <linearGradient key={`colorGradient${entry.name}`} id={`colorGradient${index}`} x1="0" y1="0" x2="0" y2="1">
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
        </>
    );
}
