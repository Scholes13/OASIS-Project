import { useState, useMemo } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { motion, AnimatePresence } from 'framer-motion';
import {
    Clock, CheckCircle2, AlertTriangle,
    Calendar, BarChart3, PieChart, TrendingUp,
    Users, ArrowRight,
} from 'lucide-react';
import {
    PieChart as PieChartRecharts, Pie, Cell,
    BarChart, Bar, LineChart, Line,
    XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer, Legend,
} from 'recharts';
import { format, subDays, startOfMonth } from 'date-fns';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/Card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/Badge';
import { EmptyState } from '@/components/ui/empty-state';
import { TicketStatusBadge } from '@/components/Ticket/TicketStatusBadge';
import { TicketPriorityBadge } from '@/components/Ticket/TicketPriorityBadge';
import { SlaBadge } from '@/components/Ticket/SlaBadge';
import { cn } from '@/lib/utils';
import type { PageProps, Ticket, TicketDashboardMetrics } from '@/types';

// ── Types ──────────────────────────────────────────────────────────────
interface DashboardProps extends PageProps {
    metrics: TicketDashboardMetrics;
    filters: { date_from: string; date_to: string };
}

// ── Chart Constants ──────────────────────────────────────────────────
const COLORS = ['#6366f1', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899'];

const statusColors: Record<string, string> = {
    waiting: '#f59e0b',
    in_progress: '#3b82f6',
    done: '#10b981',
    cancelled: '#6b7280',
};

const priorityColors: Record<string, string> = {
    low: '#94a3b8',
    medium: '#3b82f6',
    high: '#f59e0b',
    critical: '#ef4444',
};

// ── Period presets ─────────────────────────────────────────────────────
const periodPresets = [
    { label: 'Today', getRange: () => ({ from: format(new Date(), 'yyyy-MM-dd'), to: format(new Date(), 'yyyy-MM-dd') }) },
    { label: 'This Week', getRange: () => ({ from: format(subDays(new Date(), 7), 'yyyy-MM-dd'), to: format(new Date(), 'yyyy-MM-dd') }) },
    { label: 'This Month', getRange: () => ({ from: format(startOfMonth(new Date()), 'yyyy-MM-dd'), to: format(new Date(), 'yyyy-MM-dd') }) },
    { label: '30 Days', getRange: () => ({ from: format(subDays(new Date(), 30), 'yyyy-MM-dd'), to: format(new Date(), 'yyyy-MM-dd') }) },
    { label: '90 Days', getRange: () => ({ from: format(subDays(new Date(), 90), 'yyyy-MM-dd'), to: format(new Date(), 'yyyy-MM-dd') }) },
];

// ── Chart Tooltip ───────────────────────────────────────────────────
function ChartTooltip({ active, payload, label }: any) {
    if (!active || !payload?.length) return null;
    return (
        <div className="rounded-lg border border-gray-200 bg-white px-3.5 py-2.5 shadow-xl">
            <p className="text-xs font-semibold text-gray-700 mb-1.5 border-b border-gray-100 pb-1.5">{label}</p>
            {payload.map((p: any, i: number) => (
                <div key={i} className="flex items-center justify-between gap-6 py-0.5">
                    <div className="flex items-center gap-2">
                        <span className="w-2.5 h-2.5 rounded-full flex-shrink-0" style={{ backgroundColor: p.color }} />
                        <span className="text-xs text-gray-600">{p.name}</span>
                    </div>
                    <span className="text-xs font-bold text-gray-900 tabular-nums">{p.value}</span>
                </div>
            ))}
        </div>
    );
}

// ── Animation variants ──────────────────────────────────────────────
const fadeUp = { hidden: { opacity: 0, y: 16 }, show: { opacity: 1, y: 0, transition: { duration: 0.4 } } };
const stagger = { show: { transition: { staggerChildren: 0.06 } } };

export default function TicketDashboard({ metrics, filters }: DashboardProps) {
    const { flash } = usePage<PageProps>().props;
    const [dateFrom, setDateFrom] = useState(filters.date_from);
    const [dateTo, setDateTo] = useState(filters.date_to);
    const [isFiltering, setIsFiltering] = useState(false);

    const applyFilters = () => {
        setIsFiltering(true);
        router.get(route('it-support.admin.dashboard'), {
            date_from: dateFrom,
            date_to: dateTo,
        }, {
            preserveState: true,
            preserveScroll: true,
            onFinish: () => setIsFiltering(false),
        });
    };

    const handlePreset = (preset: typeof periodPresets[number]) => {
        const range = preset.getRange();
        setDateFrom(range.from);
        setDateTo(range.to);
    };

    // Calculate status counts
    const statusCounts = useMemo(() => ({
        total: metrics.total,
        waiting: metrics.by_status.waiting || 0,
        in_progress: metrics.by_status.in_progress || 0,
        done: metrics.by_status.done || 0,
        cancelled: metrics.by_status.cancelled || 0,
        sla_breach: metrics.sla_breach_count || 0,
    }), [metrics]);

    // Data for Pie Chart (by status)
    const statusPieData = useMemo(() => [
        { name: 'Menunggu', value: metrics.by_status.waiting || 0, color: statusColors.waiting },
        { name: 'Dalam Proses', value: metrics.by_status.in_progress || 0, color: statusColors.in_progress },
        { name: 'Selesai', value: metrics.by_status.done || 0, color: statusColors.done },
        { name: 'Dibatalkan', value: metrics.by_status.cancelled || 0, color: statusColors.cancelled },
    ].filter(d => d.value > 0), [metrics.by_status]);

    // Data for Priority Bar Chart
    const priorityBarData = useMemo(() => [
        { name: 'Rendah', count: metrics.by_priority.low || 0, color: priorityColors.low },
        { name: 'Sedang', count: metrics.by_priority.medium || 0, color: priorityColors.medium },
        { name: 'Tinggi', count: metrics.by_priority.high || 0, color: priorityColors.high },
        { name: 'Kritis', count: metrics.by_priority.critical || 0, color: priorityColors.critical },
    ], [metrics.by_priority]);

    // Data for Category Bar Chart
    const categoryBarData = useMemo(() =>
        metrics.by_category.map(c => ({ name: c.name, count: c.count, color: c.color })),
        [metrics.by_category]
    );

    // Data for Staff workload
    const staffWorkloadData = useMemo(() =>
        [...metrics.by_staff].sort((a, b) => b.count - a.count).slice(0, 5),
        [metrics.by_staff]
    );

    return (
        <>
            <Head title="IT Support Dashboard" />

            <div className="w-full px-6 py-6 lg:px-8 space-y-6">
                {/* ── Header ──────────────────────────────────────────────── */}
                <div className="flex flex-col xl:flex-row xl:items-end justify-between gap-4">
                    <div className="flex flex-col gap-1.5">
                        <h1 className="text-2xl font-bold text-gray-900 tracking-tight">IT Support Dashboard</h1>
                        <p className="text-sm text-gray-500">Ringkasan semua ticket dukungan IT</p>
                    </div>
                </div>

                {/* ── Filter Bar ─────────────────────────────────────── */}
                <Card className="p-3 shadow-sm border-gray-200/80">
                    <div className="flex flex-col lg:flex-row lg:items-center gap-3">
                        {/* Period Presets */}
                        <div className="flex items-center gap-1.5 flex-wrap">
                            {periodPresets.map((preset) => (
                                <button
                                    key={preset.label}
                                    onClick={() => handlePreset(preset)}
                                    className={cn(
                                        'h-7 px-3 text-xs font-medium rounded-md border transition-all cursor-pointer',
                                        dateFrom === preset.getRange().from && dateTo === preset.getRange().to
                                            ? 'bg-primary text-white border-primary shadow-sm'
                                            : 'bg-white text-gray-600 border-gray-200 hover:border-gray-300 hover:bg-gray-50'
                                    )}
                                >
                                    {preset.label}
                                </button>
                            ))}
                        </div>

                        <div className="w-px h-7 bg-gray-200 hidden lg:block" />

                        {/* Date Range */}
                        <div className="flex items-center gap-2">
                            <Calendar className="w-4 h-4 text-gray-400 flex-shrink-0" strokeWidth={1.5} />
                            <input
                                type="date"
                                value={dateFrom}
                                onChange={(e) => setDateFrom(e.target.value)}
                                className="w-[130px] text-xs h-8 rounded-md border border-gray-200 bg-white px-2 focus:border-primary focus:ring-1 focus:ring-primary/20 focus:outline-none"
                            />
                            <span className="text-xs text-gray-300 font-medium">—</span>
                            <input
                                type="date"
                                value={dateTo}
                                onChange={(e) => setDateTo(e.target.value)}
                                className="w-[130px] text-xs h-8 rounded-md border border-gray-200 bg-white px-2 focus:border-primary focus:ring-1 focus:ring-primary/20 focus:outline-none"
                            />
                            <Button size="sm" variant="outline" onClick={applyFilters} className="h-8 text-xs">
                                Apply
                            </Button>
                        </div>
                    </div>
                </Card>

                {/* ── Metric Cards ───────────────────────────────────────── */}
                <AnimatePresence mode="wait">
                    {isFiltering ? (
                        <motion.div
                            initial={{ opacity: 0 }}
                            animate={{ opacity: 1 }}
                            exit={{ opacity: 0 }}
                            className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3"
                        >
                            {Array.from({ length: 5 }).map((_, i) => (
                                <div key={i} className="h-24 bg-gray-100 animate-pulse rounded-lg" />
                            ))}
                        </motion.div>
                    ) : (
                        <motion.div variants={stagger} initial="hidden" animate="show"
                            className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
                            <motion.div variants={fadeUp}>
                                <div className="border border-gray-200/80 rounded-lg p-4 bg-slate-50/80">
                                    <p className="text-[13px] font-medium text-gray-500">Total Tickets</p>
                                    <p className="mt-1 text-3xl font-bold tabular-nums text-gray-900">{metrics.total}</p>
                                </div>
                            </motion.div>
                            <motion.div variants={fadeUp}>
                                <div className="border border-gray-200/80 rounded-lg p-4 bg-amber-50/60">
                                    <p className="text-[13px] font-medium text-gray-500">Menunggu</p>
                                    <p className="mt-1 text-3xl font-bold tabular-nums text-amber-600">{statusCounts.waiting}</p>
                                </div>
                            </motion.div>
                            <motion.div variants={fadeUp}>
                                <div className="border border-gray-200/80 rounded-lg p-4 bg-blue-50/60">
                                    <p className="text-[13px] font-medium text-gray-500">Dalam Proses</p>
                                    <p className="mt-1 text-3xl font-bold tabular-nums text-blue-600">{statusCounts.in_progress}</p>
                                </div>
                            </motion.div>
                            <motion.div variants={fadeUp}>
                                <div className="border border-gray-200/80 rounded-lg p-4 bg-emerald-50/60">
                                    <p className="text-[13px] font-medium text-gray-500">Selesai</p>
                                    <p className="mt-1 text-3xl font-bold tabular-nums text-emerald-600">{statusCounts.done}</p>
                                </div>
                            </motion.div>
                            <motion.div variants={fadeUp}>
                                <div className="border border-gray-200/80 rounded-lg p-4 bg-red-50/60">
                                    <p className="text-[13px] font-medium text-gray-500">SLA Breach</p>
                                    <p className="mt-1 text-3xl font-bold tabular-nums text-red-600">{metrics.sla_breach_count}</p>
                                </div>
                            </motion.div>
                        </motion.div>
                    )}
                </AnimatePresence>

                {/* ── Charts Row 1: Status + Priority ────────────────────── */}
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {/* Tickets by Status (Pie/Donut) */}
                    <Card className="border border-gray-200 rounded-lg">
                        <CardHeader className="pb-0">
                            <CardTitle className="text-base font-semibold text-gray-900">Tickets by Status</CardTitle>
                        </CardHeader>
                        <CardContent className="flex-1 flex flex-col justify-center">
                            {statusPieData.length === 0 ? (
                                <EmptyState
                                    icon={<PieChart className="w-10 h-10" />}
                                    title="No ticket data"
                                    description="Data ticket akan muncul setelah ticket dibuat."
                                    variant="compact"
                                />
                            ) : (
                                <div className="flex items-center justify-center gap-6">
                                    <div className="w-[200px] h-[200px]">
                                        <ResponsiveContainer width="100%" height="100%">
                                            <PieChartRecharts>
                                                <Pie data={statusPieData} dataKey="value" nameKey="name"
                                                    cx="50%" cy="50%" innerRadius={60} outerRadius={90}
                                                    paddingAngle={2} stroke="none">
                                                    {statusPieData.map((entry, index) => (
                                                        <Cell key={index} fill={entry.color} />
                                                    ))}
                                                </Pie>
                                                <Tooltip content={<ChartTooltip />} />
                                            </PieChartRecharts>
                                        </ResponsiveContainer>
                                    </div>
                                    <div className="space-y-2">
                                        {statusPieData.map((item, i) => (
                                            <div key={i} className="flex items-center justify-between gap-3">
                                                <div className="flex items-center gap-2">
                                                    <span className="w-2.5 h-2.5 rounded-full" style={{ backgroundColor: item.color }} />
                                                    <span className="text-sm text-gray-600">{item.name}</span>
                                                </div>
                                                <span className="text-sm font-semibold text-gray-900 tabular-nums">{item.value}</span>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* Tickets by Priority (Bar) */}
                    <Card className="border border-gray-200 rounded-lg">
                        <CardHeader className="pb-4">
                            <CardTitle className="text-base font-semibold text-gray-900">Tickets by Priority</CardTitle>
                        </CardHeader>
                        <CardContent className="flex-1">
                            {priorityBarData.every(d => d.count === 0) ? (
                                <EmptyState
                                    icon={<BarChart3 className="w-10 h-10" />}
                                    title="No priority data"
                                    description="Data priority akan muncul setelah ticket dibuat."
                                    variant="compact"
                                />
                            ) : (
                                <ResponsiveContainer width="100%" height={220}>
                                    <BarChart data={priorityBarData} barSize={40} margin={{ top: 10, right: 10, left: -15, bottom: 0 }}>
                                        <CartesianGrid strokeDasharray="3 3" stroke="#f1f5f9" vertical={false} />
                                        <XAxis dataKey="name" tick={{ fontSize: 11, fill: '#64748b', fontWeight: 500 }} stroke="#cbd5e1" axisLine={false} tickLine={false} dy={10} />
                                        <YAxis tick={{ fontSize: 11, fill: '#64748b' }} stroke="#cbd5e1" allowDecimals={false} axisLine={false} tickLine={false} dx={-10} />
                                        <Tooltip content={<ChartTooltip />} />
                                        <Bar dataKey="count" name="Tickets" radius={[4, 4, 0, 0]}>
                                            {priorityBarData.map((entry, index) => (
                                                <Cell key={index} fill={entry.color} />
                                            ))}
                                        </Bar>
                                    </BarChart>
                                </ResponsiveContainer>
                            )}
                        </CardContent>
                    </Card>
                </div>

                {/* ── Charts Row 2: Category + Recent ───────────────────── */}
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Tickets by Category */}
                    <Card className="lg:col-span-2 border border-gray-200 rounded-lg">
                        <CardHeader className="pb-4">
                            <CardTitle className="text-base font-semibold text-gray-900">Tickets by Category</CardTitle>
                        </CardHeader>
                        <CardContent className="flex-1">
                            {categoryBarData.length === 0 ? (
                                <EmptyState
                                    icon={<BarChart3 className="w-10 h-10" />}
                                    title="No category data"
                                    description="Data kategori akan muncul setelah ticket dibuat."
                                    variant="compact"
                                />
                            ) : (
                                <ResponsiveContainer width="100%" height={280}>
                                    <BarChart data={categoryBarData} barSize={32} margin={{ top: 10, right: 10, left: -15, bottom: 0 }}>
                                        <CartesianGrid strokeDasharray="3 3" stroke="#f1f5f9" vertical={false} />
                                        <XAxis dataKey="name" tick={{ fontSize: 11, fill: '#64748b', fontWeight: 500 }} stroke="#cbd5e1" axisLine={false} tickLine={false} dy={10} />
                                        <YAxis tick={{ fontSize: 11, fill: '#64748b' }} stroke="#cbd5e1" allowDecimals={false} axisLine={false} tickLine={false} dx={-10} />
                                        <Tooltip content={<ChartTooltip />} />
                                        <Bar dataKey="count" name="Tickets" radius={[4, 4, 0, 0]} fill="#6366f1" />
                                    </BarChart>
                                </ResponsiveContainer>
                            )}
                        </CardContent>
                    </Card>

                    {/* Staff Workload */}
                    <Card className="border border-gray-200 rounded-lg">
                        <CardHeader className="pb-4 border-b border-gray-100">
                            <CardTitle className="text-base font-semibold text-gray-900">Staff Workload</CardTitle>
                        </CardHeader>
                        <CardContent className="pt-4">
                            {staffWorkloadData.length === 0 ? (
                                <EmptyState
                                    icon={<Users className="w-10 h-10" />}
                                    title="No staff data"
                                    description="Belum ada ticket yang ditugaskan ke staff."
                                    variant="compact"
                                />
                            ) : (
                                <div className="space-y-3">
                                    {staffWorkloadData.map((staff, i) => (
                                        <div key={i} className="flex items-center gap-3 group">
                                            <div className="w-9 h-9 rounded-full bg-primary/90 flex items-center justify-center text-sm font-bold text-white shadow-sm">
                                                {staff.name.charAt(0).toUpperCase()}
                                            </div>
                                            <div className="flex-1 min-w-0">
                                                <p className="text-sm font-medium text-gray-800 truncate">{staff.name}</p>
                                            </div>
                                            <span className="text-sm font-semibold text-gray-900 tabular-nums">{staff.count}</span>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>

                {/* ── Recent Tickets Table ────────────────────────────────────── */}
                <Card className="border border-gray-200 rounded-lg">
                    <CardHeader className="pb-4 border-b border-gray-100">
                        <div className="flex items-center justify-between">
                            <CardTitle className="text-base font-semibold text-gray-900">Recent Tickets</CardTitle>
                            <Link href={route('it-support.admin.tickets.index')}>
                                <Button variant="ghost" size="sm" className="text-primary">
                                    View All <ArrowRight className="w-4 h-4 ml-1" />
                                </Button>
                            </Link>
                        </div>
                    </CardHeader>
                    <CardContent className="p-0">
                        <div className="overflow-x-auto">
                            <table className="w-full">
                                <thead>
                                    <tr className="bg-gray-100 border-b border-gray-200">
                                        <th className="h-12 px-5 text-left text-sm font-semibold text-gray-700">Ticket</th>
                                        <th className="h-12 px-5 text-left text-sm font-semibold text-gray-700">Title</th>
                                        <th className="h-12 px-5 text-left text-sm font-semibold text-gray-700">Requester</th>
                                        <th className="h-12 px-5 text-left text-sm font-semibold text-gray-700">Status</th>
                                        <th className="h-12 px-5 text-left text-sm font-semibold text-gray-700">Priority</th>
                                        <th className="h-12 px-5 text-left text-sm font-semibold text-gray-700">SLA</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {metrics.recent_tickets.length === 0 ? (
                                        <tr>
                                            <td colSpan={6} className="px-5 py-8 text-center text-gray-500">
                                                No recent tickets
                                            </td>
                                        </tr>
                                    ) : (
                                        metrics.recent_tickets.slice(0, 10).map((ticket) => (
                                            <tr key={ticket.id} className="border-b border-gray-100 hover:bg-gray-50/80">
                                                <td className="px-5 py-4 text-sm font-medium text-primary">
                                                    <Link href={route('it-support.admin.tickets.show', { ticket: ticket.id })}>
                                                        {ticket.ticket_number}
                                                    </Link>
                                                </td>
                                                <td className="px-5 py-4 text-sm text-gray-900 max-w-xs truncate">
                                                    {ticket.title}
                                                </td>
                                                <td className="px-5 py-4 text-sm text-gray-600">
                                                    {ticket.requester?.name || '-'}
                                                </td>
                                                <td className="px-5 py-4">
                                                    <TicketStatusBadge status={ticket.status} />
                                                </td>
                                                <td className="px-5 py-4">
                                                    <TicketPriorityBadge priority={ticket.priority} />
                                                </td>
                                                <td className="px-5 py-4">
                                                    <SlaBadge
                                                        slaDeadline={ticket.sla_deadline}
                                                        isBreached={ticket.is_sla_breach}
                                                    />
                                                </td>
                                            </tr>
                                        ))
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}