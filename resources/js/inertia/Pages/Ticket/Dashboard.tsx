import { useMemo, useState, type ReactNode } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { Activity, AlertTriangle, Calendar, CheckCircle2, Clock3, MoreHorizontal, Ticket as TicketIcon } from 'lucide-react';
import { format, startOfMonth, subDays } from 'date-fns';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/Card';
import { TicketPriorityBadge } from '@/components/Ticket/TicketPriorityBadge';
import { TicketStatusBadge } from '@/components/Ticket/TicketStatusBadge';
import { cn } from '@/lib/utils';
import type { PageProps, Ticket, TicketDashboardMetrics } from '@/types';

interface DashboardProps extends PageProps {
    metrics: TicketDashboardMetrics;
    filters: { date_from: string; date_to: string };
}

const periodPresets = [
    { label: 'Today', getRange: () => ({ from: format(new Date(), 'yyyy-MM-dd'), to: format(new Date(), 'yyyy-MM-dd') }) },
    { label: 'This Week', getRange: () => ({ from: format(subDays(new Date(), 7), 'yyyy-MM-dd'), to: format(new Date(), 'yyyy-MM-dd') }) },
    { label: 'This Month', getRange: () => ({ from: format(startOfMonth(new Date()), 'yyyy-MM-dd'), to: format(new Date(), 'yyyy-MM-dd') }) },
    { label: '30 Days', getRange: () => ({ from: format(subDays(new Date(), 30), 'yyyy-MM-dd'), to: format(new Date(), 'yyyy-MM-dd') }) },
    { label: '90 Days', getRange: () => ({ from: format(subDays(new Date(), 90), 'yyyy-MM-dd'), to: format(new Date(), 'yyyy-MM-dd') }) },
];

const weekdayLabels = ['S', 'M', 'T', 'W', 'T', 'F', 'S'];

export default function TicketDashboard({ metrics, filters }: DashboardProps) {
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

    const openTickets = (metrics.by_status.waiting || 0) + (metrics.by_status.in_progress || 0);
    const responseTime = Number(metrics.avg_resolution_hours || 0).toFixed(1);
    const withinSla = Math.max(metrics.total - metrics.sla_breach_count, 0);
    const dueSoonCount = metrics.recent_tickets.filter((ticket) => getSlaState(ticket).state === 'soon').length;

    const weeklyVolume = useMemo(() => buildVolumeData(metrics), [metrics]);
    const maxVolume = Math.max(...weeklyVolume.map((item) => item.value), 1);
    const volumeDelta = metrics.total > 0 ? Math.round((openTickets / metrics.total) * 100) : 0;

    const statusGroups = useMemo(() => ({
        open: metrics.recent_tickets.filter((ticket) => ticket.status === 'waiting').slice(0, 2),
        resolving: metrics.recent_tickets.filter((ticket) => ticket.status === 'in_progress').slice(0, 2),
        archived: metrics.recent_tickets.filter((ticket) => ticket.status === 'done' || ticket.status === 'cancelled').slice(0, 2),
    }), [metrics.recent_tickets]);

    return (
        <>
            <Head title="IT Support Dashboard" />

            <div className="w-full space-y-5 bg-gray-50/60 px-6 py-6 lg:px-8">
                <div className="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight text-gray-900">IT Support Dashboard</h1>
                        <p className="mt-1 text-sm text-gray-500">Monitor ticket flow, response time, and support workload.</p>
                    </div>

                    <Card className="border-gray-200 bg-white p-2 shadow-none">
                        <div className="flex flex-col gap-2 lg:flex-row lg:items-center">
                            <div className="flex flex-wrap items-center gap-1.5">
                                {periodPresets.map((preset) => {
                                    const range = preset.getRange();
                                    const active = dateFrom === range.from && dateTo === range.to;

                                    return (
                                        <button
                                            key={preset.label}
                                            onClick={() => handlePreset(preset)}
                                            className={cn(
                                                'h-8 rounded-md border px-3 text-xs font-medium transition-colors',
                                                active
                                                    ? 'border-primary/20 bg-primary/10 text-primary'
                                                    : 'border-gray-200 bg-white text-gray-600 hover:border-gray-300 hover:bg-gray-50'
                                            )}
                                        >
                                            {preset.label}
                                        </button>
                                    );
                                })}
                            </div>
                            <div className="flex items-center gap-2 border-t border-gray-100 pt-2 lg:border-l lg:border-t-0 lg:pl-2 lg:pt-0">
                                <Calendar className="h-4 w-4 text-gray-400" />
                                <input
                                    type="date"
                                    value={dateFrom}
                                    onChange={(event) => setDateFrom(event.target.value)}
                                    className="h-8 w-32 rounded-md border border-gray-200 bg-white px-2 text-xs focus:border-gray-400 focus:outline-none focus:ring-0"
                                />
                                <span className="text-xs text-gray-300">—</span>
                                <input
                                    type="date"
                                    value={dateTo}
                                    onChange={(event) => setDateTo(event.target.value)}
                                    className="h-8 w-32 rounded-md border border-gray-200 bg-white px-2 text-xs focus:border-gray-400 focus:outline-none focus:ring-0"
                                />
                                <Button size="sm" variant="outline" onClick={applyFilters} className="h-8 text-xs">
                                    Apply
                                </Button>
                            </div>
                        </div>
                    </Card>
                </div>

                <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-6">
                    <MetricCard title="Total Tickets" value={metrics.total} subLabel="Current period" tone="neutral" icon={<TicketIcon className="h-4 w-4" />} />
                    <MetricCard title="SLA Target" value="48 hrs" subLabel="2 × 24h per case" tone="neutral" icon={<Clock3 className="h-4 w-4" />} />
                    <MetricCard title="Within SLA" value={withinSla} subLabel={`${Math.round((withinSla / Math.max(metrics.total, 1)) * 100)}% compliant`} tone="success" icon={<CheckCircle2 className="h-4 w-4" />} />
                    <MetricCard title="Due Soon" value={dueSoonCount} subLabel="Under 6 hours left" tone="warning" icon={<Clock3 className="h-4 w-4" />} />
                    <MetricCard title="Breached SLA" value={metrics.sla_breach_count} subLabel="Past 48h target" tone={metrics.sla_breach_count > 0 ? 'danger' : 'neutral'} icon={<AlertTriangle className="h-4 w-4" />} />
                    <MetricCard title="Response Time" value={`${responseTime} hrs`} subLabel="Average resolution" tone="neutral" icon={<Activity className="h-4 w-4" />} />
                </div>

                <div className="grid grid-cols-1 gap-5 xl:grid-cols-5">
                    <Card className="border-gray-200 bg-white p-5 shadow-none xl:col-span-3">
                        <div className="flex items-start justify-between">
                            <div>
                                <div className="flex items-center gap-2">
                                    <span className="flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-gray-50 text-gray-700">
                                        <Activity className="h-4 w-4" />
                                    </span>
                                    <div>
                                        <h2 className="text-sm font-semibold text-gray-900">Ticket Volume Tracker</h2>
                                        <p className="text-xs text-gray-500">Daily ticket flow and workload trend.</p>
                                    </div>
                                </div>
                            </div>
                            <span className="rounded-full border border-gray-200 px-3 py-1 text-xs font-medium text-gray-600">Weekly</span>
                        </div>

                        <div className="mt-8 grid grid-cols-1 gap-8 lg:grid-cols-5 lg:items-end">
                            <div className="lg:col-span-1">
                                <p className="text-4xl font-semibold tracking-tight text-gray-900">+{volumeDelta}%</p>
                                <p className="mt-2 text-xs leading-5 text-gray-500">Open tickets compared with total period volume.</p>
                            </div>
                            <div className="flex min-h-48 items-end justify-between gap-3 rounded-2xl bg-gradient-to-b from-white to-gray-50 px-3 pb-2 pt-6 lg:col-span-4">
                                {weeklyVolume.map((item, index) => {
                                    const heightRem = Math.max((item.value / maxVolume) * 8.25, 1.25);
                                    const active = index === 3;

                                    return (
                                        <div key={`${item.label}-${index}`} className="flex flex-1 flex-col items-center gap-2">
                                            <div className="flex h-36 items-end">
                                                <div className="relative flex flex-col items-center justify-end" style={{ height: `${heightRem}rem` }}>
                                                    {active && (
                                                        <span className="absolute -top-8 whitespace-nowrap rounded-md bg-primary/90 px-2 py-1 text-[0.625rem] font-medium text-white">
                                                            {item.value} tickets
                                                        </span>
                                                    )}
                                                    <span className={cn('mb-1 h-2 w-2 rounded-full', active ? 'bg-primary' : 'bg-primary/50')} />
                                                    <div className="w-px flex-1 bg-gray-200" />
                                                </div>
                                            </div>
                                            <span className={cn('flex h-7 w-7 items-center justify-center rounded-full text-xs font-medium', active ? 'bg-primary/10 text-primary ring-1 ring-primary/20' : 'bg-gray-100 text-gray-500')}>
                                                {item.label}
                                            </span>
                                        </div>
                                    );
                                })}
                            </div>
                        </div>
                    </Card>

                    <Card className="border-gray-200 bg-white p-5 shadow-none xl:col-span-2">
                        <div className="mb-4 flex items-start justify-between">
                            <div>
                                <h2 className="text-sm font-semibold text-gray-900">Recent Support Activity</h2>
                                <p className="text-xs text-gray-500">Latest support requests from users.</p>
                            </div>
                            <Link href={route('it-support.admin.tickets.index')} className="text-xs font-medium text-gray-700 underline-offset-4 hover:underline">
                                See all
                            </Link>
                        </div>
                        <div className="space-y-3">
                            {metrics.recent_tickets.slice(0, 4).map((ticket) => (
                                <ActivityItem key={ticket.id} ticket={ticket} />
                            ))}
                            {metrics.recent_tickets.length === 0 && (
                                <div className="rounded-xl border border-dashed border-gray-200 py-8 text-center text-sm text-gray-500">No recent activity</div>
                            )}
                        </div>
                    </Card>
                </div>

                <div className="grid grid-cols-1 gap-5 xl:grid-cols-4">
                    <Card className="border-gray-200 bg-white p-5 shadow-none xl:col-span-3">
                        <div className="mb-5 flex items-start justify-between">
                            <div>
                                <h2 className="text-sm font-semibold text-gray-900">Ticket Status Board</h2>
                                <p className="text-xs text-gray-500">Current work grouped by lifecycle state.</p>
                            </div>
                            {metrics.sla_breach_count > 0 && (
                                <span className="inline-flex items-center gap-1 rounded-full border border-red-200 bg-red-50 px-2.5 py-1 text-xs font-medium text-red-700">
                                    <AlertTriangle className="h-3.5 w-3.5" />
                                    {metrics.sla_breach_count} SLA breach
                                </span>
                            )}
                        </div>
                        <div className="grid grid-cols-1 gap-4 lg:grid-cols-3">
                            <StatusColumn title="Open Tickets" tickets={statusGroups.open} />
                            <StatusColumn title="Resolving Tickets" tickets={statusGroups.resolving} />
                            <StatusColumn title="Archived" tickets={statusGroups.archived} />
                        </div>
                    </Card>

                    <TeamWorkloadPanel staff={metrics.by_staff} total={openTickets} />
                </div>

                {isFiltering && <span className="sr-only">Filtering dashboard data</span>}
            </div>
        </>
    );
}

function MetricCard({ title, value, subLabel, tone, icon }: { title: string; value: number | string; subLabel: string; tone: 'neutral' | 'success' | 'warning' | 'danger'; icon: ReactNode }) {
    const toneClass = {
        neutral: 'text-gray-600 bg-gray-50 border-gray-200',
        success: 'text-emerald-700 bg-emerald-50 border-emerald-100',
        warning: 'text-amber-700 bg-amber-50 border-amber-100',
        danger: 'text-red-700 bg-red-50 border-red-100',
    }[tone];

    return (
        <Card className="border-gray-200 bg-white p-4 shadow-none">
            <div className="flex items-start justify-between gap-3">
                <div>
                    <p className="text-xs font-semibold uppercase tracking-wide text-gray-500">{title}</p>
                    <p className="mt-3 text-2xl font-semibold tracking-tight text-gray-900">{value}</p>
                    <p className="mt-1 text-xs text-gray-500">{subLabel}</p>
                </div>
                <span className={cn('flex h-8 w-8 items-center justify-center rounded-lg border', toneClass)}>{icon}</span>
            </div>
        </Card>
    );
}

function ActivityItem({ ticket }: { ticket: Ticket }) {
    const sla = getSlaState(ticket);

    return (
        <Link href={route('it-support.admin.tickets.show', { ticket: ticket.id })} className="block rounded-xl border border-gray-100 p-3 transition-colors hover:border-gray-200 hover:bg-gray-50/70">
            <div className="flex items-start gap-3">
                <span className="mt-0.5 flex h-9 w-9 items-center justify-center rounded-lg bg-gray-50 text-gray-500">
                    <TicketIcon className="h-4 w-4" />
                </span>
                <div className="min-w-0 flex-1">
                    <div className="flex items-start justify-between gap-2">
                        <div className="min-w-0">
                            <p className="break-words text-sm font-semibold text-gray-900">{ticket.ticket_number}</p>
                            <p className="break-words text-xs text-gray-500">{ticket.requester?.name || 'Unknown requester'}</p>
                        </div>
                        <TicketStatusBadge status={ticket.status} />
                    </div>
                    <p className="mt-2 break-words text-xs leading-5 text-gray-600">{ticket.title}</p>
                    <div className="mt-3 flex flex-wrap items-center gap-2">
                        <TicketPriorityBadge priority={ticket.priority} />
                        <SlaPill label={sla.label} state={sla.state} />
                        {ticket.category && <span className="rounded-md bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600">{ticket.category.name}</span>}
                    </div>
                </div>
            </div>
        </Link>
    );
}

function StatusColumn({ title, tickets }: { title: string; tickets: Ticket[] }) {
    return (
        <div>
            <div className="mb-3 flex items-center justify-between">
                <h3 className="text-xs font-semibold uppercase tracking-wide text-gray-500">{title} ({tickets.length})</h3>
                <MoreHorizontal className="h-4 w-4 text-gray-400" />
            </div>
            <div className="space-y-3">
                {tickets.map((ticket) => {
                    const sla = getSlaState(ticket);

                    return (
                        <Link key={ticket.id} href={route('it-support.admin.tickets.show', { ticket: ticket.id })} className="block rounded-xl border border-dashed border-gray-300 bg-white p-4 transition-colors hover:border-gray-400 hover:bg-gray-50/70">
                            <div className="mb-3 flex items-start justify-between gap-3">
                                <p className="break-words text-sm font-semibold leading-5 text-gray-900">{ticket.title}</p>
                                <TicketPriorityBadge priority={ticket.priority} />
                            </div>
                            <p className="break-words text-xs leading-5 text-gray-500">{ticket.description}</p>
                            <div className="mt-4 flex flex-wrap items-center justify-between gap-3">
                                <span className="text-xs text-gray-500">{format(new Date(ticket.updated_at), 'dd MMM yyyy')}</span>
                                <div className="flex flex-wrap items-center gap-2">
                                    <SlaPill label={sla.label} state={sla.state} />
                                    {ticket.category && <span className="rounded-md bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600">{ticket.category.name}</span>}
                                </div>
                            </div>
                        </Link>
                    );
                })}
                {tickets.length === 0 && (
                    <div className="rounded-xl border border-dashed border-gray-200 py-8 text-center text-xs text-gray-400">No tickets</div>
                )}
            </div>
        </div>
    );
}

function TeamWorkloadPanel({ staff, total }: { staff: { name: string; count: number }[]; total: number }) {
    const maxCount = Math.max(...staff.map((item) => item.count), 1);

    return (
        <Card className="border-gray-200 bg-white p-5 shadow-none">
            <div className="mb-5">
                <h2 className="text-sm font-semibold text-gray-900">Team Workload</h2>
                <p className="text-xs text-gray-500">Open ticket distribution per user/team.</p>
            </div>
            <div className="space-y-4">
                {staff.slice(0, 6).map((member) => {
                    const percent = Math.round((member.count / Math.max(total, 1)) * 100);
                    const width = Math.max((member.count / maxCount) * 100, 8);

                    return (
                        <div key={member.name}>
                            <div className="mb-1.5 flex items-center justify-between gap-3">
                                <div className="flex min-w-0 items-center gap-2">
                                    <span className="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-primary/10 text-xs font-semibold text-primary">
                                        {member.name.charAt(0).toUpperCase()}
                                    </span>
                                    <span className="break-words text-sm font-medium text-gray-800">{member.name}</span>
                                </div>
                                <span className="shrink-0 text-xs font-medium text-gray-500">{member.count} ({percent}%)</span>
                            </div>
                            <div className="h-2 rounded-full bg-gray-100">
                                <div className="h-2 rounded-full bg-primary/30" style={{ width: `${width}%` }} />
                            </div>
                        </div>
                    );
                })}
                {staff.length === 0 && (
                    <div className="rounded-xl border border-dashed border-gray-200 py-8 text-center text-xs text-gray-400">No assigned tickets</div>
                )}
            </div>
        </Card>
    );
}

function SlaPill({ label, state }: { label: string; state: 'ok' | 'soon' | 'breach' | 'none' }) {
    const className = {
        ok: 'border-emerald-100 bg-emerald-50 text-emerald-700',
        soon: 'border-amber-100 bg-amber-50 text-amber-700',
        breach: 'border-red-100 bg-red-50 text-red-700',
        none: 'border-gray-200 bg-gray-50 text-gray-500',
    }[state];

    return <span className={cn('rounded-md border px-2 py-0.5 text-xs font-medium', className)}>{label}</span>;
}

function getSlaState(ticket: Ticket): { label: string; state: 'ok' | 'soon' | 'breach' | 'none' } {
    if (ticket.is_sla_breach) {
        return { label: 'SLA breached', state: 'breach' };
    }

    if (! ticket.sla_deadline) {
        return { label: 'SLA 48h', state: 'none' };
    }

    const hoursLeft = (new Date(ticket.sla_deadline).getTime() - Date.now()) / 3600000;

    if (hoursLeft < 0) {
        return { label: 'SLA breached', state: 'breach' };
    }

    if (hoursLeft <= 6) {
        return { label: `Due ${Math.ceil(hoursLeft)}h`, state: 'soon' };
    }

    return { label: `Due ${Math.ceil(hoursLeft)}h`, state: 'ok' };
}

function buildVolumeData(metrics: TicketDashboardMetrics) {
    const values = [
        metrics.by_status.waiting || 0,
        metrics.by_priority.low || 0,
        metrics.by_priority.medium || 0,
        metrics.by_status.in_progress || 0,
        metrics.by_priority.high || 0,
        metrics.by_priority.critical || 0,
        metrics.by_status.done || 0,
    ];

    return weekdayLabels.map((label, index) => ({
        label,
        value: values[index] || 0,
    }));
}
