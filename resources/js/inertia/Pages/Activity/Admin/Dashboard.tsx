import { useState, useEffect, useMemo } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { motion, AnimatePresence } from 'framer-motion';
import {
    BarChart3, Building2, CheckCircle, Clock, Download, ListTodo,
    TrendingUp, Timer, ArrowRight, Trophy, Filter, Calendar as CalendarIcon,
    Users, Flame, Activity, Zap,
} from 'lucide-react';
import {
    AreaChart, Area, BarChart, Bar, PieChart, Pie, Cell,
    XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer, Legend,
} from 'recharts';
import { format, subDays, startOfMonth, startOfWeek, endOfWeek } from 'date-fns';
import { useBusinessUnit } from '@/hooks/useBusinessUnit';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/Card';
import { Button } from '@/components/ui/button';
import { Select } from '@/components/ui/select';
import { Badge } from '@/components/ui/Badge';
import { EmptyState, NoDataEmpty } from '@/components/ui/empty-state';
import { StatsCardSkeleton, ChartSkeleton, CardSkeleton } from '@/components/ui/skeleton';
import { toast } from '@/components/ui/toast';
import { cn } from '@/lib/utils';
import type { PageProps, Department } from '@/types';

// ── Types ──────────────────────────────────────────────────────────────
interface DepartmentStat {
    department: Department;
    total: number; completed: number; in_progress: number;
    planned: number; cancelled: number; completion_rate: number; total_hours: number;
}
interface BuSummary {
    total: number; completed: number; in_progress: number;
    planned: number; cancelled: number; completion_rate: number; total_hours: number;
}
interface ActivityTypeData {
    name: string; color: string; count: number; completed: number;
    hours: number; percentage: number;
}
interface DailyTrend { date: string; total: number; completed: number; }
interface Contributor {
    created_by: number; name: string; dept_name: string;
    total: number; completed: number;
}
interface DeptOption { id: number; name: string; code: string; }
interface DashboardProps extends PageProps {
    departmentStats: DepartmentStat[];
    buSummary: BuSummary;
    buActivityTypes: ActivityTypeData[];
    dailyTrend: DailyTrend[];
    topContributors: Contributor[];
    pendingBackdateCount: number;
    departments: DeptOption[];
    filters: { date_from: string; date_to: string; department_id: number | null };
}

// ── Static Tailwind-safe color maps ────────────────────────────────────
const metricStyles: Record<string, { bg: string; text: string; ring: string }> = {
    indigo:  { bg: 'bg-indigo-50',  text: 'text-indigo-600',  ring: 'ring-indigo-500/20' },
    emerald: { bg: 'bg-emerald-50', text: 'text-emerald-600', ring: 'ring-emerald-500/20' },
    blue:    { bg: 'bg-blue-50',    text: 'text-blue-600',    ring: 'ring-blue-500/20' },
    amber:   { bg: 'bg-amber-50',   text: 'text-amber-600',   ring: 'ring-amber-500/20' },
    red:     { bg: 'bg-red-50',     text: 'text-red-600',     ring: 'ring-red-500/20' },
    purple:  { bg: 'bg-purple-50',  text: 'text-purple-600',  ring: 'ring-purple-500/20' },
};

const fadeUp = { hidden: { opacity: 0, y: 16 }, show: { opacity: 1, y: 0, transition: { duration: 0.4 } } };
const stagger = { show: { transition: { staggerChildren: 0.06 } } };
const COLORS = ['#6366f1', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#14b8a6', '#f97316'];

// ── Period presets ─────────────────────────────────────────────────────
const periodPresets = [
    { label: 'Hari Ini', getRange: () => ({ from: format(new Date(), 'yyyy-MM-dd'), to: format(new Date(), 'yyyy-MM-dd') }) },
    { label: 'Minggu Ini', getRange: () => ({ from: format(startOfWeek(new Date(), { weekStartsOn: 1 }), 'yyyy-MM-dd'), to: format(endOfWeek(new Date(), { weekStartsOn: 1 }), 'yyyy-MM-dd') }) },
    { label: 'Bulan Ini', getRange: () => ({ from: format(startOfMonth(new Date()), 'yyyy-MM-dd'), to: format(new Date(), 'yyyy-MM-dd') }) },
    { label: '30 Hari', getRange: () => ({ from: format(subDays(new Date(), 30), 'yyyy-MM-dd'), to: format(new Date(), 'yyyy-MM-dd') }) },
    { label: '90 Hari', getRange: () => ({ from: format(subDays(new Date(), 90), 'yyyy-MM-dd'), to: format(new Date(), 'yyyy-MM-dd') }) },
];

// ── Busyness level helper ──────────────────────────────────────────────
function getBusynessLevel(total: number, completionRate: number): { label: string; color: string; icon: typeof Flame; bgClass: string; textClass: string; dotClass: string } {
    if (total >= 20 && completionRate < 50) {
        return { label: 'Overloaded', color: '#ef4444', icon: Flame, bgClass: 'bg-red-50', textClass: 'text-red-700', dotClass: 'bg-red-500' };
    }
    if (total >= 10) {
        return { label: 'Sibuk', color: '#f59e0b', icon: Zap, bgClass: 'bg-amber-50', textClass: 'text-amber-700', dotClass: 'bg-amber-500' };
    }
    if (total >= 3) {
        return { label: 'Normal', color: '#10b981', icon: Activity, bgClass: 'bg-emerald-50', textClass: 'text-emerald-700', dotClass: 'bg-emerald-500' };
    }
    return { label: 'Rendah', color: '#94a3b8', icon: Clock, bgClass: 'bg-slate-50', textClass: 'text-slate-600', dotClass: 'bg-slate-400' };
}

// ── Enhanced chart tooltip ─────────────────────────────────────────────
function ChartTooltip({ active, payload, label }: any) {
    if (!active || !payload?.length) return null;
    const total = payload.reduce((sum: number, p: any) => sum + (p.value || 0), 0);
    return (
        <div className="rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 shadow-xl">
            <p className="text-xs font-semibold text-slate-700 mb-1.5 border-b border-slate-100 pb-1.5">{label}</p>
            {payload.map((p: any, i: number) => (
                <div key={i} className="flex items-center justify-between gap-6 py-0.5">
                    <div className="flex items-center gap-2">
                        <span className="w-2.5 h-2.5 rounded-full flex-shrink-0" style={{ backgroundColor: p.color }} />
                        <span className="text-xs text-slate-600">{p.name}</span>
                    </div>
                    <div className="flex items-center gap-2">
                        <span className="text-xs font-bold text-slate-900 tabular-nums">{p.value}</span>
                        {total > 0 && (
                            <span className="text-[10px] text-slate-400 tabular-nums">
                                ({Math.round((p.value / total) * 100)}%)
                            </span>
                        )}
                    </div>
                </div>
            ))}
        </div>
    );
}

// ── Loading skeleton ───────────────────────────────────────────────────
function DashboardLoadingSkeleton() {
    return (
        <div className="w-full px-6 py-6 lg:px-8 space-y-6 animate-in fade-in duration-300">
            <div className="flex items-end justify-between gap-4">
                <div className="space-y-2">
                    <div className="h-8 w-48 bg-slate-200 rounded-md animate-pulse" />
                    <div className="h-4 w-72 bg-slate-100 rounded-md animate-pulse" />
                </div>
                <div className="h-10 w-80 bg-slate-100 rounded-xl animate-pulse" />
            </div>
            <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                {Array.from({ length: 6 }).map((_, i) => <StatsCardSkeleton key={i} />)}
            </div>
            <div className="grid grid-cols-1 lg:grid-cols-5 gap-6">
                <ChartSkeleton type="pie" className="lg:col-span-2 border border-slate-200 rounded-xl" />
                <ChartSkeleton type="line" className="lg:col-span-3 border border-slate-200 rounded-xl" />
            </div>
            <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                {Array.from({ length: 6 }).map((_, i) => <CardSkeleton key={i} />)}
            </div>
        </div>
    );
}

export default function Dashboard({
    departmentStats, buSummary, buActivityTypes, dailyTrend,
    topContributors, pendingBackdateCount, departments, filters,
}: DashboardProps) {
    const { flash, currentBusinessUnit } = usePage<PageProps>().props;
    const { isSwitching } = useBusinessUnit(['departmentStats', 'buSummary']);
    const [dateFrom, setDateFrom] = useState(filters.date_from);
    const [dateTo, setDateTo] = useState(filters.date_to);
    const [departmentId, setDepartmentId] = useState<string | number>(filters.department_id?.toString() || '');
    const [isFiltering, setIsFiltering] = useState(false);
    const [activePreset, setActivePreset] = useState<string | null>(null);

    useEffect(() => {
        if (flash.success) toast.success(flash.success);
        if (flash.error) toast.error(flash.error);
    }, [flash]);

    // Reset filtering state when data arrives
    useEffect(() => {
        setIsFiltering(false);
    }, [departmentStats, buSummary]);

    // Detect active preset from current date filters
    useEffect(() => {
        const match = periodPresets.find(p => {
            const r = p.getRange();
            return r.from === dateFrom && r.to === dateTo;
        });
        setActivePreset(match?.label ?? null);
    }, [dateFrom, dateTo]);

    const applyFilters = (overrides: { date_from?: string; date_to?: string; department_id?: string } = {}) => {
        setIsFiltering(true);
        const params: Record<string, string> = {
            date_from: overrides.date_from ?? dateFrom,
            date_to: overrides.date_to ?? dateTo,
        };
        const deptVal = overrides.department_id ?? departmentId;
        if (deptVal) params.department_id = String(deptVal);
        router.get(route('activity.admin.dashboard'), params, {
            preserveState: true, preserveScroll: true,
        });
    };

    const handlePreset = (preset: typeof periodPresets[number]) => {
        const range = preset.getRange();
        setDateFrom(range.from);
        setDateTo(range.to);
        setActivePreset(preset.label);
        applyFilters({ date_from: range.from, date_to: range.to });
    };

    const handleDepartmentChange = (value: string | number) => {
        const val = String(value);
        setDepartmentId(val);
        applyFilters({ department_id: val });
    };

    const handleDateApply = () => {
        setActivePreset(null);
        applyFilters();
    };

    const handleExport = () => {
        let url = route('activity.admin.export') + `?date_from=${dateFrom}&date_to=${dateTo}`;
        if (departmentId) url += `&department_id=${departmentId}`;
        window.location.href = url;
    };

    // Memoized dept chart data
    const deptChartData = useMemo(() =>
        [...departmentStats]
            .filter(d => d.total > 0)
            .sort((a, b) => b.total - a.total)
            .map(d => ({ name: d.department.code, completed: d.completed, in_progress: d.in_progress, planned: d.planned })),
        [departmentStats]
    );

    // Department select options
    const deptOptions = useMemo(() => [
        { value: '', label: 'Semua Department' },
        ...departments.map(d => ({ value: String(d.id), label: `${d.code} — ${d.name}` })),
    ], [departments]);

    // Metric cards config — static Tailwind classes only
    const metricCards = useMemo(() => {
        const completionColor = buSummary.completion_rate >= 70 ? 'emerald' : buSummary.completion_rate >= 40 ? 'amber' : 'red';
        return [
            { label: 'Total Aktivitas', value: buSummary.total, icon: ListTodo, color: 'indigo' as const },
            { label: 'Selesai', value: buSummary.completed, icon: CheckCircle, color: 'emerald' as const },
            { label: 'Berjalan', value: buSummary.in_progress, icon: Clock, color: 'blue' as const },
            { label: 'Direncanakan', value: buSummary.planned, icon: BarChart3, color: 'amber' as const },
            { label: 'Completion', value: `${buSummary.completion_rate}%`, icon: TrendingUp, color: completionColor as keyof typeof metricStyles },
            { label: 'Total Jam', value: buSummary.total_hours, icon: Timer, color: 'purple' as const },
        ];
    }, [buSummary]);

    // Show skeleton while switching BU
    if (isSwitching) {
        return <DashboardLoadingSkeleton />;
    }

    return (
        <>
            <Head title="Activity Admin" />

            <div className="w-full px-6 py-6 lg:px-8 space-y-6">

                {/* ── Header ────────────────────────────────────────── */}
                <div className="flex flex-col xl:flex-row xl:items-end justify-between gap-4">
                    <div className="flex flex-col gap-1.5">
                        <h1 className="text-2xl font-bold text-slate-900 tracking-tight">Activity Admin</h1>
                        <div className="flex flex-wrap items-center gap-2 mt-1">
                            <Badge variant="default" size="sm" className="bg-white border border-slate-200 shadow-sm text-slate-600">
                                <Building2 className="w-3 h-3 mr-1" strokeWidth={2} />
                                {currentBusinessUnit?.name || 'Business Unit'}
                            </Badge>
                            <span className="text-sm text-slate-500">Ringkasan kesibukan semua department</span>
                        </div>
                    </div>

                    {/* Action buttons */}
                    <div className="flex items-center gap-2">
                        {pendingBackdateCount > 0 && (
                            <Link href={route('activity.admin.backdate.approvals')}>
                                <Button variant="outline" size="sm" className="relative text-rose-600 border-rose-200 hover:bg-rose-50 hover:text-rose-700">
                                    <Clock className="w-3.5 h-3.5 mr-1.5" strokeWidth={1.5} />
                                    HOD Backdate
                                    <span className="absolute -top-1.5 -right-1.5 flex h-4.5 w-4.5 items-center justify-center rounded-full bg-rose-500 text-[9px] font-bold text-white ring-2 ring-white">
                                        {pendingBackdateCount}
                                    </span>
                                </Button>
                            </Link>
                        )}
                        <Button size="sm" onClick={handleExport} className="bg-primary hover:bg-primary/90 text-white shadow-sm">
                            <Download className="w-3.5 h-3.5 mr-1.5" strokeWidth={2} />
                            Export
                        </Button>
                    </div>
                </div>

                {/* ── Filter Bar ─────────────────────────────────────── */}
                <Card className="p-3 shadow-sm border-slate-200/80">
                    <div className="flex flex-col lg:flex-row lg:items-center gap-3">
                        {/* Period Presets */}
                        <div className="flex items-center gap-1.5 flex-wrap">
                            {periodPresets.map((preset) => (
                                <button
                                    key={preset.label}
                                    onClick={() => handlePreset(preset)}
                                    className={cn(
                                        'h-7 px-3 text-xs font-medium rounded-md border transition-all cursor-pointer',
                                        activePreset === preset.label
                                            ? 'bg-primary text-white border-primary shadow-sm'
                                            : 'bg-white text-slate-600 border-slate-200 hover:border-slate-300 hover:bg-slate-50'
                                    )}
                                >
                                    {preset.label}
                                </button>
                            ))}
                        </div>

                        <div className="w-px h-7 bg-slate-200 hidden lg:block" />

                        {/* Date Range */}
                        <div className="flex items-center gap-2">
                            <CalendarIcon className="w-4 h-4 text-slate-400 flex-shrink-0" strokeWidth={1.5} />
                            <input
                                type="date"
                                value={dateFrom}
                                onChange={e => { setDateFrom(e.target.value); setActivePreset(null); }}
                                className="w-[130px] text-xs h-8 rounded-md border border-slate-200 bg-white px-2 focus:border-primary focus:ring-1 focus:ring-primary/20 focus:outline-none"
                            />
                            <span className="text-xs text-slate-300 font-medium">—</span>
                            <input
                                type="date"
                                value={dateTo}
                                onChange={e => { setDateTo(e.target.value); setActivePreset(null); }}
                                className="w-[130px] text-xs h-8 rounded-md border border-slate-200 bg-white px-2 focus:border-primary focus:ring-1 focus:ring-primary/20 focus:outline-none"
                            />
                            <Button size="sm" variant="outline" onClick={handleDateApply} className="h-8 text-xs">
                                Terapkan
                            </Button>
                        </div>

                        <div className="w-px h-7 bg-slate-200 hidden lg:block" />

                        {/* Department Filter */}
                        <div className="w-56">
                            <Select
                                value={departmentId}
                                onChange={handleDepartmentChange}
                                options={deptOptions}
                                placeholder="Semua Department"
                                className="text-xs"
                            />
                        </div>
                    </div>
                </Card>

                {/* ── Metric Cards ───────────────────────────────── */}
                <AnimatePresence mode="wait">
                    {isFiltering ? (
                        <motion.div key="skeleton" initial={{ opacity: 0 }} animate={{ opacity: 1 }} exit={{ opacity: 0 }}
                            className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                            {Array.from({ length: 6 }).map((_, i) => <StatsCardSkeleton key={i} />)}
                        </motion.div>
                    ) : (
                        <motion.div key="data" variants={stagger} initial="hidden" animate="show"
                            className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                            {metricCards.map((m) => {
                                const Icon = m.icon;
                                const style = metricStyles[m.color] || metricStyles.indigo;
                                return (
                                    <motion.div key={m.label} variants={fadeUp}>
                                        <Card className="p-4 hover:shadow-md transition-all group">
                                            <div className="flex items-start justify-between">
                                                <div>
                                                    <p className="text-[11px] font-medium text-slate-500 uppercase tracking-wider">{m.label}</p>
                                                    <p className="text-2xl font-bold text-slate-900 mt-1.5 tabular-nums">{m.value}</p>
                                                </div>
                                                <div className={cn('p-2 rounded-lg ring-1 transition-transform group-hover:scale-110', style.bg, style.ring)}>
                                                    <Icon className={cn('w-5 h-5', style.text)} strokeWidth={1.5} />
                                                </div>
                                            </div>
                                        </Card>
                                    </motion.div>
                                );
                            })}
                        </motion.div>
                    )}
                </AnimatePresence>

                {/* ── Charts Row 1: Distribution + Daily Trend ────── */}
                <div className="grid grid-cols-1 lg:grid-cols-5 gap-6">
                    {/* Donut: Distribusi Aktivitas */}
                    <Card className="lg:col-span-2 flex flex-col shadow-sm border-slate-200/60">
                        <CardHeader className="pb-0">
                            <CardTitle className="text-base font-semibold text-slate-800">Distribusi Aktivitas</CardTitle>
                        </CardHeader>
                        <CardContent className="flex-1 flex flex-col justify-center mt-4">
                            {buActivityTypes.length === 0 ? (
                                <EmptyState
                                    icon={<BarChart3 className="w-10 h-10" />}
                                    title="Belum ada data aktivitas"
                                    description="Data akan muncul setelah ada aktivitas di periode yang dipilih."
                                    variant="compact"
                                />
                            ) : (
                                <div className="flex flex-col sm:flex-row items-center justify-between gap-6">
                                    <div className="w-full sm:w-1/2 h-[200px]">
                                        <ResponsiveContainer width="100%" height="100%">
                                            <PieChart>
                                                <Pie data={buActivityTypes as any[]} dataKey="count" nameKey="name"
                                                    cx="50%" cy="50%" innerRadius={60} outerRadius={90} paddingAngle={2} stroke="none">
                                                    {buActivityTypes.map((e, i) => (
                                                        <Cell key={i} fill={e.color || COLORS[i % COLORS.length]} />
                                                    ))}
                                                </Pie>
                                                <Tooltip content={<ChartTooltip />} />
                                            </PieChart>
                                        </ResponsiveContainer>
                                    </div>
                                    <div className="w-full sm:w-1/2 space-y-2.5">
                                        {buActivityTypes.slice(0, 6).map((at, i) => (
                                            <div key={i} className="flex items-center justify-between gap-3 group">
                                                <div className="flex items-center gap-2.5 min-w-0">
                                                    <span className="w-2.5 h-2.5 rounded-full flex-shrink-0"
                                                        style={{ backgroundColor: at.color || COLORS[i % COLORS.length] }} />
                                                    <span className="text-sm text-slate-600 font-medium truncate group-hover:text-slate-900 transition-colors">{at.name}</span>
                                                </div>
                                                <div className="flex items-center gap-2 flex-shrink-0">
                                                    <span className="text-sm font-semibold text-slate-900 tabular-nums">{at.count}</span>
                                                    <span className="text-[10px] font-medium text-slate-400 bg-slate-50 px-1.5 py-0.5 rounded tabular-nums">{at.percentage}%</span>
                                                </div>
                                            </div>
                                        ))}
                                        {buActivityTypes.length > 6 && (
                                            <p className="text-xs text-slate-400 text-center pt-1 font-medium">
                                                +{buActivityTypes.length - 6} lainnya
                                            </p>
                                        )}
                                    </div>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* Area: Daily Trend */}
                    <Card className="lg:col-span-3 flex flex-col shadow-sm border-slate-200/60">
                        <CardHeader className="pb-4">
                            <CardTitle className="text-base font-semibold text-slate-800">Tren Harian</CardTitle>
                        </CardHeader>
                        <CardContent className="flex-1 flex items-center">
                            {dailyTrend.length === 0 ? (
                                <EmptyState
                                    icon={<TrendingUp className="w-10 h-10" />}
                                    title="Belum ada data tren"
                                    description="Data tren akan muncul saat ada aktivitas harian."
                                    variant="compact"
                                    className="w-full"
                                />
                            ) : (
                                <ResponsiveContainer width="100%" height={300}>
                                    <AreaChart data={dailyTrend}>
                                        <defs>
                                            <linearGradient id="gT" x1="0" y1="0" x2="0" y2="1">
                                                <stop offset="0%" stopColor="#6366f1" stopOpacity={0.15} />
                                                <stop offset="100%" stopColor="#6366f1" stopOpacity={0} />
                                            </linearGradient>
                                            <linearGradient id="gD" x1="0" y1="0" x2="0" y2="1">
                                                <stop offset="0%" stopColor="#10b981" stopOpacity={0.15} />
                                                <stop offset="100%" stopColor="#10b981" stopOpacity={0} />
                                            </linearGradient>
                                        </defs>
                                        <CartesianGrid strokeDasharray="3 3" stroke="#f1f5f9" vertical={false} />
                                        <XAxis dataKey="date" tick={{ fontSize: 11 }} stroke="#94a3b8" axisLine={false} tickLine={false} dy={10} />
                                        <YAxis tick={{ fontSize: 11 }} stroke="#94a3b8" allowDecimals={false} axisLine={false} tickLine={false} dx={-10} />
                                        <Tooltip content={<ChartTooltip />} />
                                        <Area type="monotone" dataKey="total" name="Total" stroke="#6366f1" fill="url(#gT)" strokeWidth={2} />
                                        <Area type="monotone" dataKey="completed" name="Selesai" stroke="#10b981" fill="url(#gD)" strokeWidth={2} />
                                        <Legend iconType="circle" iconSize={8} wrapperStyle={{ fontSize: 12, paddingTop: 8 }} />
                                    </AreaChart>
                                </ResponsiveContainer>
                            )}
                        </CardContent>
                    </Card>
                </div>

                {/* ── Charts Row 2: Dept Bar + Top Contributors ──── */}
                <div className="grid grid-cols-1 lg:grid-cols-5 gap-6">
                    <Card className="lg:col-span-3 shadow-sm border-slate-200/60 flex flex-col">
                        <CardHeader className="pb-4">
                            <CardTitle className="text-base font-semibold text-slate-800">Aktivitas per Department</CardTitle>
                        </CardHeader>
                        <CardContent className="flex-1">
                            {deptChartData.length === 0 ? (
                                <EmptyState
                                    icon={<BarChart3 className="w-10 h-10" />}
                                    title="Belum ada data department"
                                    description="Data akan muncul setelah department memiliki aktivitas."
                                    variant="compact"
                                />
                            ) : (
                                <ResponsiveContainer width="100%" height={320}>
                                    <BarChart data={deptChartData} barSize={36} margin={{ top: 10, right: 10, left: -15, bottom: 0 }}>
                                        <CartesianGrid strokeDasharray="3 3" stroke="#f1f5f9" vertical={false} />
                                        <XAxis dataKey="name" tick={{ fontSize: 11, fill: '#64748b', fontWeight: 500 }} stroke="#cbd5e1" axisLine={false} tickLine={false} dy={10} />
                                        <YAxis tick={{ fontSize: 11, fill: '#64748b' }} stroke="#cbd5e1" allowDecimals={false} axisLine={false} tickLine={false} dx={-10} />
                                        <Tooltip cursor={{ fill: '#f8fafc' }} content={<ChartTooltip />} />
                                        <Legend iconType="circle" iconSize={8} wrapperStyle={{ fontSize: 12, paddingTop: 16, fontWeight: 500, color: '#475569' }} />
                                        <Bar dataKey="completed" name="Selesai" stackId="a" fill="#10b981" />
                                        <Bar dataKey="in_progress" name="Berjalan" stackId="a" fill="#3b82f6" />
                                        <Bar dataKey="planned" name="Direncanakan" stackId="a" fill="#f59e0b" radius={[4, 4, 0, 0]} />
                                    </BarChart>
                                </ResponsiveContainer>
                            )}
                        </CardContent>
                    </Card>

                    <Card className="lg:col-span-2 shadow-sm border-slate-200/60 flex flex-col">
                        <CardHeader className="pb-4 border-b border-slate-100">
                            <CardTitle className="text-base font-semibold text-slate-800 flex items-center gap-2">
                                <Trophy className="w-5 h-5 text-amber-500" strokeWidth={2} />
                                Top Contributors
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="flex-1 overflow-y-auto pt-4" style={{ maxHeight: '340px' }}>
                            {topContributors.length === 0 ? (
                                <EmptyState
                                    icon={<Users className="w-10 h-10" />}
                                    title="Belum ada kontributor"
                                    description="Data akan muncul saat ada aktivitas karyawan."
                                    variant="compact"
                                />
                            ) : (
                                <div className="space-y-3">
                                    {topContributors.map((c, i) => {
                                        const rate = c.total > 0 ? Math.round((c.completed / c.total) * 100) : 0;
                                        return (
                                            <div key={c.created_by} className="flex items-center gap-3.5 group py-1 hover:bg-slate-50/50 -mx-2 px-2 rounded-lg transition-colors">
                                                <div className="relative flex-shrink-0">
                                                    <div className="w-9 h-9 rounded-full bg-primary/90 flex items-center justify-center text-sm font-bold text-white shadow-sm">
                                                        {c.name.charAt(0).toUpperCase()}
                                                    </div>
                                                    {i < 3 && (
                                                        <div className={cn(
                                                            'absolute -top-1 -right-1 w-4 h-4 rounded-full flex items-center justify-center text-[10px] font-bold text-white shadow-sm ring-2 ring-white',
                                                            i === 0 ? 'bg-amber-400' : i === 1 ? 'bg-slate-400' : 'bg-orange-400'
                                                        )}>
                                                            {i + 1}
                                                        </div>
                                                    )}
                                                </div>
                                                <div className="flex-1 min-w-0">
                                                    <p className="text-sm font-semibold text-slate-800 truncate group-hover:text-primary transition-colors">{c.name}</p>
                                                    <p className="text-[11px] text-slate-500 truncate">{c.dept_name}</p>
                                                </div>
                                                <div className="text-right flex-shrink-0">
                                                    <p className="text-sm font-bold text-slate-900 tabular-nums">
                                                        {c.completed}<span className="text-[11px] font-medium text-slate-400">/{c.total}</span>
                                                    </p>
                                                    <div className="flex items-center justify-end gap-1.5 mt-1">
                                                        <span className="text-[10px] font-medium text-slate-500 tabular-nums">{rate}%</span>
                                                        <div className="w-12 h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                                            <div
                                                                className={cn(
                                                                    'h-full rounded-full transition-all',
                                                                    rate >= 70 ? 'bg-emerald-500' : rate >= 40 ? 'bg-amber-500' : 'bg-red-400'
                                                                )}
                                                                style={{ width: `${rate}%` }}
                                                            />
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        );
                                    })}
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>

                {/* ── Department Cards with Busyness Indicator ────── */}
                <div>
                    <h2 className="text-base font-semibold text-slate-900 flex items-center gap-2 mb-4">
                        <Building2 className="w-5 h-5 text-primary" strokeWidth={1.5} />
                        Detail per Department
                        {departmentStats.length > 0 && (
                            <Badge variant="default" size="sm">{departmentStats.length} dept</Badge>
                        )}
                    </h2>

                    {departmentStats.length === 0 ? (
                        <EmptyState
                            icon={<Building2 className="w-12 h-12" />}
                            title="Tidak ada department aktif"
                            description="Belum ada department aktif di business unit ini."
                        />
                    ) : (
                        <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                            {departmentStats.map((ds) => {
                                const busyness = getBusynessLevel(ds.total, ds.completion_rate);
                                const BusynessIcon = busyness.icon;
                                const avgHoursPerTask = ds.total > 0 ? (ds.total_hours / ds.total).toFixed(1) : '0';

                                return (
                                    <Link key={ds.department.id}
                                        href={route('activity.admin.department', { department: ds.department.id }) + `?date_from=${dateFrom}&date_to=${dateTo}`}
                                        className="block group">
                                        <motion.div whileHover={{ y: -2 }} transition={{ type: 'spring', stiffness: 400, damping: 25 }}>
                                            <Card className="p-5 hover:border-primary/50 hover:shadow-md transition-all">
                                                {/* Header: Name + Busyness badge */}
                                                <div className="flex items-start justify-between mb-3">
                                                    <div className="min-w-0 flex-1">
                                                        <h3 className="text-sm font-semibold text-slate-900 group-hover:text-primary transition-colors truncate">
                                                            {ds.department.name}
                                                        </h3>
                                                        <p className="text-[11px] text-slate-400 mt-0.5">{ds.department.code}</p>
                                                    </div>
                                                    <span className={cn(
                                                        'inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold flex-shrink-0',
                                                        busyness.bgClass, busyness.textClass
                                                    )}>
                                                        <BusynessIcon className="w-3 h-3" strokeWidth={2} />
                                                        {busyness.label}
                                                    </span>
                                                </div>

                                                {/* Completion bar */}
                                                <div className="flex items-center gap-2 mb-3">
                                                    <div className="flex-1 h-2 bg-slate-100 rounded-full overflow-hidden">
                                                        <motion.div
                                                            initial={{ width: 0 }}
                                                            animate={{ width: `${Math.min(ds.completion_rate, 100)}%` }}
                                                            transition={{ duration: 0.8, ease: 'easeOut' }}
                                                            className={cn(
                                                                'h-full rounded-full',
                                                                ds.completion_rate >= 70 ? 'bg-emerald-500' :
                                                                    ds.completion_rate >= 40 ? 'bg-amber-500' : 'bg-red-400'
                                                            )}
                                                        />
                                                    </div>
                                                    <span className="text-xs font-bold text-slate-700 tabular-nums w-10 text-right">{ds.completion_rate}%</span>
                                                </div>

                                                {/* Stats grid */}
                                                <div className="grid grid-cols-4 gap-1 text-center mb-3">
                                                    <div>
                                                        <p className="text-base font-bold tabular-nums text-emerald-600">{ds.completed}</p>
                                                        <p className="text-[10px] text-slate-400">Done</p>
                                                    </div>
                                                    <div>
                                                        <p className="text-base font-bold tabular-nums text-blue-600">{ds.in_progress}</p>
                                                        <p className="text-[10px] text-slate-400">Progress</p>
                                                    </div>
                                                    <div>
                                                        <p className="text-base font-bold tabular-nums text-amber-600">{ds.planned}</p>
                                                        <p className="text-[10px] text-slate-400">Planned</p>
                                                    </div>
                                                    <div>
                                                        <p className="text-base font-bold tabular-nums text-red-400">{ds.cancelled}</p>
                                                        <p className="text-[10px] text-slate-400">Cancel</p>
                                                    </div>
                                                </div>

                                                {/* Footer: hours + avg */}
                                                <div className="flex items-center justify-between pt-2 border-t border-slate-100">
                                                    <div className="flex items-center gap-3 text-[11px] text-slate-500">
                                                        <span className="flex items-center gap-1">
                                                            <Timer className="w-3 h-3" strokeWidth={1.5} />
                                                            {ds.total_hours}h logged
                                                        </span>
                                                        <span className="flex items-center gap-1">
                                                            <Activity className="w-3 h-3" strokeWidth={1.5} />
                                                            ~{avgHoursPerTask}h/task
                                                        </span>
                                                    </div>
                                                    <ArrowRight className="w-4 h-4 text-slate-300 group-hover:text-primary group-hover:translate-x-0.5 transition-all" strokeWidth={1.5} />
                                                </div>
                                            </Card>
                                        </motion.div>
                                    </Link>
                                );
                            })}
                        </div>
                    )}
                </div>
            </div>
        </>
    );
}
