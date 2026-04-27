import { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { usePage } from '@inertiajs/react';
import { motion } from 'framer-motion';
import {
    FileText, Calendar, Download, BarChart3, PieChart,
    TrendingUp, Users, Clock, CheckCircle2,
} from 'lucide-react';
import {
    PieChart as PieChartRecharts, Pie, Cell,
    BarChart, Bar, LineChart, Line,
    XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer, Legend,
} from 'recharts';
import { format, subDays, startOfMonth } from 'date-fns';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/Card';
import { Button } from '@/components/ui/button';
import { EmptyState } from '@/components/ui/empty-state';
import { cn } from '@/lib/utils';
import type { PageProps } from '@/types';

// ── Types ──────────────────────────────────────────────────────────────
interface ReportData {
    total_tickets: number;
    resolved_tickets: number;
    avg_resolution_hours: number;
    by_status: { name: string; value: number; color: string }[];
    by_priority: { name: string; count: number; color: string }[];
    by_category: { name: string; count: number; color: string }[];
    by_staff: { name: string; count: number; color: string }[];
    daily_trend: { date: string; total: number; resolved: number }[];
}

interface ReportingProps extends PageProps {
    reportData: ReportData;
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
    const total = payload.reduce((sum: number, p: any) => sum + (p.value || 0), 0);
    return (
        <div className="rounded-lg border border-gray-200 bg-white px-3.5 py-2.5 shadow-xl">
            <p className="text-xs font-semibold text-gray-700 mb-1.5 border-b border-gray-100 pb-1.5">{label}</p>
            {payload.map((p: any, i: number) => (
                <div key={i} className="flex items-center justify-between gap-6 py-0.5">
                    <div className="flex items-center gap-2">
                        <span className="w-2.5 h-2.5 rounded-full flex-shrink-0" style={{ backgroundColor: p.color }} />
                        <span className="text-xs text-gray-600">{p.name}</span>
                    </div>
                    <div className="flex items-center gap-2">
                        <span className="text-xs font-bold text-gray-900 tabular-nums">{p.value}</span>
                        {total > 0 && (
                            <span className="text-[10px] text-gray-400 tabular-nums">
                                ({Math.round((p.value / total) * 100)}%)
                            </span>
                        )}
                    </div>
                </div>
            ))}
        </div>
    );
}

// ── Animation variants ──────────────────────────────────────────────
const fadeUp = { hidden: { opacity: 0, y: 16 }, show: { opacity: 1, y: 0, transition: { duration: 0.4 } } };

export default function TicketReporting({ reportData, filters }: ReportingProps) {
    const { flash } = usePage<PageProps>().props;
    const [dateFrom, setDateFrom] = useState(filters.date_from);
    const [dateTo, setDateTo] = useState(filters.date_to);
    const [isFiltering, setIsFiltering] = useState(false);

    const applyFilters = () => {
        setIsFiltering(true);
        router.get(route('it-support.admin.reporting'), {
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

    const handleExport = (format: 'excel' | 'pdf') => {
        const url = route('it-support.admin.export') + `?date_from=${dateFrom}&date_to=${dateTo}&format=${format}`;
        window.location.href = url;
    };

    // Ensure data arrays exist
    const statusPieData = reportData.by_status || [];
    const priorityBarData = reportData.by_priority || [];
    const categoryBarData = reportData.by_category || [];
    const staffBarData = reportData.by_staff || [];
    const dailyTrendData = reportData.daily_trend || [];

    return (
        <>
            <Head title="IT Support Reporting" />

            <div className="w-full px-6 py-6 lg:px-8 space-y-6">
                {/* ── Header ──────────────────────────────────────────────── */}
                <div className="flex flex-col xl:flex-row xl:items-end justify-between gap-4">
                    <div className="flex flex-col gap-1.5">
                        <h1 className="text-2xl font-bold text-gray-900 tracking-tight">IT Support Reporting</h1>
                        <p className="text-sm text-gray-500">Laporan dan statistik ticket dukungan IT</p>
                    </div>

                    <div className="flex items-center gap-2">
                        <Button variant="outline" size="sm" onClick={() => handleExport('excel')}>
                            <FileText className="w-4 h-4 mr-2" />
                            Export Excel
                        </Button>
                        <Button variant="outline" size="sm" onClick={() => handleExport('pdf')}>
                            <FileText className="w-4 h-4 mr-2" />
                            Export PDF
                        </Button>
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

                {/* ── Summary Cards ───────────────────────────────────────── */}
                <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-3 gap-4">
                    <motion.div variants={fadeUp} initial="hidden" animate="show">
                        <div className="border border-gray-200/80 rounded-lg p-5 bg-slate-50/80">
                            <div className="flex items-center gap-3">
                                <div className="w-10 h-10 rounded-lg bg-slate-200 flex items-center justify-center">
                                    <FileText className="w-5 h-5 text-slate-600" />
                                </div>
                                <div>
                                    <p className="text-[13px] font-medium text-gray-500">Total Tickets</p>
                                    <p className="text-2xl font-bold tabular-nums text-gray-900">{reportData.total_tickets}</p>
                                </div>
                            </div>
                        </div>
                    </motion.div>
                    <motion.div variants={fadeUp} initial="hidden" animate="show">
                        <div className="border border-gray-200/80 rounded-lg p-5 bg-emerald-50/60">
                            <div className="flex items-center gap-3">
                                <div className="w-10 h-10 rounded-lg bg-emerald-100 flex items-center justify-center">
                                    <CheckCircle2 className="w-5 h-5 text-emerald-600" />
                                </div>
                                <div>
                                    <p className="text-[13px] font-medium text-gray-500">Resolved</p>
                                    <p className="text-2xl font-bold tabular-nums text-emerald-600">{reportData.resolved_tickets}</p>
                                </div>
                            </div>
                        </div>
                    </motion.div>
                    <motion.div variants={fadeUp} initial="hidden" animate="show">
                        <div className="border border-gray-200/80 rounded-lg p-5 bg-blue-50/60">
                            <div className="flex items-center gap-3">
                                <div className="w-10 h-10 rounded-lg bg-blue-100 flex items-center justify-center">
                                    <Clock className="w-5 h-5 text-blue-600" />
                                </div>
                                <div>
                                    <p className="text-[13px] font-medium text-gray-500">Avg Resolution</p>
                                    <p className="text-2xl font-bold tabular-nums text-blue-600">{reportData.avg_resolution_hours}h</p>
                                </div>
                            </div>
                        </div>
                    </motion.div>
                </div>

                {/* ── Charts Row 1: Trend + Status ────────────────────── */}
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Daily Trend (Line) */}
                    <Card className="lg:col-span-2 border border-gray-200 rounded-lg">
                        <CardHeader className="pb-4">
                            <CardTitle className="text-base font-semibold text-gray-900">Tickets Over Time</CardTitle>
                        </CardHeader>
                        <CardContent className="flex-1">
                            {dailyTrendData.length === 0 ? (
                                <EmptyState
                                    icon={<TrendingUp className="w-10 h-10" />}
                                    title="No trend data"
                                    description="Data trend akan muncul setelah ticket dibuat."
                                    variant="compact"
                                />
                            ) : (
                                <ResponsiveContainer width="100%" height={300}>
                                    <LineChart data={dailyTrendData}>
                                        <CartesianGrid strokeDasharray="3 3" stroke="#f1f5f9" vertical={false} />
                                        <XAxis dataKey="date" tick={{ fontSize: 11 }} stroke="#94a3b8" axisLine={false} tickLine={false} dy={10} />
                                        <YAxis tick={{ fontSize: 11 }} stroke="#94a3b8" allowDecimals={false} axisLine={false} tickLine={false} dx={-10} />
                                        <Tooltip content={<ChartTooltip />} />
                                        <Legend iconType="circle" iconSize={8} wrapperStyle={{ fontSize: 12, paddingTop: 8 }} />
                                        <Line type="monotone" dataKey="total" name="Total" stroke="#6366f1" strokeWidth={2} dot={false} />
                                        <Line type="monotone" dataKey="resolved" name="Resolved" stroke="#10b981" strokeWidth={2} dot={false} />
                                    </LineChart>
                                </ResponsiveContainer>
                            )}
                        </CardContent>
                    </Card>

                    {/* By Status (Pie) */}
                    <Card className="border border-gray-200 rounded-lg">
                        <CardHeader className="pb-0">
                            <CardTitle className="text-base font-semibold text-gray-900">By Status</CardTitle>
                        </CardHeader>
                        <CardContent className="flex-1 flex flex-col justify-center">
                            {statusPieData.length === 0 ? (
                                <EmptyState
                                    icon={<PieChart className="w-10 h-10" />}
                                    title="No status data"
                                    description="Data status akan muncul setelah ticket dibuat."
                                    variant="compact"
                                />
                            ) : (
                                <div className="flex items-center justify-center gap-4">
                                    <div className="w-[180px] h-[180px]">
                                        <ResponsiveContainer width="100%" height="100%">
                                            <PieChartRecharts>
                                                <Pie data={statusPieData} dataKey="value" nameKey="name"
                                                    cx="50%" cy="50%" innerRadius={50} outerRadius={80}
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
                </div>

                {/* ── Charts Row 2: Priority + Category ──────────────── */}
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {/* By Priority (Bar) */}
                    <Card className="border border-gray-200 rounded-lg">
                        <CardHeader className="pb-4">
                            <CardTitle className="text-base font-semibold text-gray-900">By Priority</CardTitle>
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
                                <ResponsiveContainer width="100%" height={280}>
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

                    {/* By Category (Bar) */}
                    <Card className="border border-gray-200 rounded-lg">
                        <CardHeader className="pb-4">
                            <CardTitle className="text-base font-semibold text-gray-900">By Category</CardTitle>
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
                </div>

                {/* ── By Staff (Horizontal Bar) ───────────────────── */}
                <Card className="border border-gray-200 rounded-lg">
                    <CardHeader className="pb-4">
                        <CardTitle className="text-base font-semibold text-gray-900">By Staff</CardTitle>
                    </CardHeader>
                    <CardContent className="flex-1">
                        {staffBarData.length === 0 ? (
                            <EmptyState
                                icon={<Users className="w-10 h-10" />}
                                title="No staff data"
                                description="Belum ada ticket yang ditugaskan ke staff."
                                variant="compact"
                            />
                        ) : (
                            <ResponsiveContainer width="100%" height={300}>
                                <BarChart data={staffBarData} layout="vertical" barSize={24} margin={{ top: 10, right: 10, left: 80, bottom: 0 }}>
                                    <CartesianGrid strokeDasharray="3 3" stroke="#f1f5f9" horizontal={false} />
                                    <XAxis type="number" tick={{ fontSize: 11 }} stroke="#94a3b8" allowDecimals={false} axisLine={false} tickLine={false} />
                                    <YAxis type="category" dataKey="name" tick={{ fontSize: 11, fill: '#64748b' }} stroke="#cbd5e1" axisLine={false} tickLine={false} width={70} />
                                    <Tooltip content={<ChartTooltip />} />
                                    <Bar dataKey="count" name="Tickets" radius={[0, 4, 4, 0]} fill="#6366f1" />
                                </BarChart>
                            </ResponsiveContainer>
                        )}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}