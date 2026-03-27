import { Popover, Transition } from '@headlessui/react';
import { useId } from 'react';
import { motion } from 'framer-motion';
import { CalendarDays, ChevronDown } from 'lucide-react';
import {
    Bar,
    BarChart,
    CartesianGrid,
    Cell,
    ComposedChart,
    Legend,
    Line,
    LineChart,
    ReferenceLine,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';
import {
    buildLineSeries,
    resolveAggregateGradientOffset,
    resolveLineChartDomain,
    type CashflowChartRow,
} from '../chart-utils';
import { formatCurrency } from '../utils';

type ViewMode = 'day' | 'week' | 'month';

type ChartRow = {
    key?: string;
    label: string;
    inflow: number;
    outflow: number;
    closingBalance?: number;
};

type DayPill = {
    key: string;
    label: string;
};

interface ProjectionChartCardProps {
    title: string;
    subtitle: string;
    chartData: ChartRow[];
    viewMode: ViewMode;
    onViewModeChange: (mode: ViewMode) => void;
    dayPills: DayPill[];
    selectedDayKey: string;
    onDayFilterChange: (value: string) => void;
    minimumBalanceThreshold?: number;
}

const viewModes: ViewMode[] = ['day', 'week', 'month'];

const INFLOW_COLOR = '#3b82f6';
const OUTFLOW_COLOR = '#f43f5e';
const BALANCE_COLOR = '#059669';

export default function ProjectionChartCard({
    title,
    subtitle,
    chartData,
    viewMode,
    onViewModeChange,
    dayPills,
    selectedDayKey,
    onDayFilterChange,
    minimumBalanceThreshold,
}: ProjectionChartCardProps) {
    const rawLineSeries = buildLineSeries(chartData as CashflowChartRow[], viewMode);
    const lineSeries = rawLineSeries.map(row => ({
        ...row,
        dominantMovement: Math.abs(row.outflow ?? 0) > Math.abs(row.inflow ?? 0) ? row.outflow : row.inflow,
    }));
    const chartDomain = resolveLineChartDomain(chartData as CashflowChartRow[], viewMode);
    const gradientId = useId().replace(/:/g, '-');
    const aggregateGradientOffset = resolveAggregateGradientOffset(chartDomain);

    // Day single → full opacity bar, All others → composed chart
    const isSingleDay = viewMode === 'day' && selectedDayKey !== 'all';
    const useMultiPeriodChart = !isSingleDay;
    const hasBalanceData = lineSeries.some((row) => row.closingBalance != null && row.closingBalance !== 0);

    // Right Y-axis domain for balance line (month view)
    const balanceDomain: [number, number] = (() => {
        if (!hasBalanceData) return [0, 1];
        const balances = lineSeries.map((row) => row.closingBalance ?? 0);
        if (minimumBalanceThreshold) balances.push(minimumBalanceThreshold);
        const minBal = Math.min(0, ...balances);
        const maxBal = Math.max(...balances);
        const padding = Math.max((maxBal - minBal) * 0.1, 1);
        return [Math.floor(minBal - padding), Math.ceil(maxBal + padding)];
    })();

    const selectedDayLabel = selectedDayKey === 'all'
        ? 'All Days'
        : dayPills.find((day) => day.key === selectedDayKey)?.label ?? 'All Days';

    const formatAxisCurrency = (value: number): string => {
        return formatCurrency(Math.abs(value));
    };

    const formatAxisCurrencyShort = (value: number): string => {
        const abs = Math.abs(value);
        if (abs >= 1_000_000_000) return `${(abs / 1_000_000_000).toFixed(1)}B`;
        if (abs >= 1_000_000) return `${(abs / 1_000_000).toFixed(0)}M`;
        if (abs >= 1_000) return `${(abs / 1_000).toFixed(0)}K`;
        return String(abs);
    };

    const renderTooltip = ({ active, payload, label }: any) => {
        if (!active || !payload?.length) return null;

        const balancePayload = payload.find((e: any) => e.name === 'Balance');
        const inflowPayload = payload.find((e: any) => e.name === 'Inflow');
        const outflowPayload = payload.find((e: any) => e.name === 'Outflow');
        const netPayload = payload.find((e: any) => e.name === 'Net Cashflow');

        return (
            <div className="rounded-xl border border-slate-200/80 bg-white/95 p-4 shadow-xl backdrop-blur-md min-w-[240px]">
                <p className="mb-3 text-[11px] font-bold text-slate-500 uppercase tracking-widest">{label}</p>

                {balancePayload && (
                    <div className="mb-4 rounded-lg bg-emerald-50/50 p-3 ring-1 ring-emerald-100/80">
                        <p className="text-[11px] font-semibold text-emerald-600 mb-1 uppercase tracking-wider">Projected Balance</p>
                        <p className="text-xl font-bold tracking-tight text-slate-900">
                            {formatCurrency(Math.abs(Number(balancePayload.value ?? 0)))}
                        </p>
                        {minimumBalanceThreshold !== undefined && (
                            <div className="mt-2.5 pt-2.5 border-t border-emerald-100 flex flex-col gap-1 text-[11px] font-medium">
                                <div className="flex justify-between items-center text-slate-600">
                                    <span className="flex items-center gap-1.5">
                                        <span className="h-1 w-1 rounded-full bg-slate-400" />
                                        Lower Limit
                                    </span>
                                    <span>{formatCurrency(minimumBalanceThreshold)}</span>
                                </div>
                                <div className="flex justify-between items-center">
                                    <span className="flex items-center gap-1.5 text-slate-500">
                                        <span className={`h-1 w-1 rounded-full ${Number(balancePayload.value) < minimumBalanceThreshold ? 'bg-rose-500' : 'bg-emerald-500'}`} />
                                        Status
                                    </span>
                                    <span className={Number(balancePayload.value) < minimumBalanceThreshold ? 'text-rose-600 font-semibold' : 'text-emerald-600 font-semibold'}>
                                        {Number(balancePayload.value) < minimumBalanceThreshold ? 'Below Threshold' : 'Safe'}
                                    </span>
                                </div>
                            </div>
                        )}
                    </div>
                )}

                {netPayload && !balancePayload && (
                    <div className="mb-4 rounded-lg bg-blue-50/50 p-3 ring-1 ring-blue-100/80">
                        <p className="text-[11px] font-semibold text-blue-600 mb-1 uppercase tracking-wider">Net Cashflow</p>
                        <p className="text-xl font-bold tracking-tight text-slate-900">
                            {formatCurrency(Math.abs(Number(netPayload.value ?? 0)))}
                        </p>
                    </div>
                )}

                {(inflowPayload || outflowPayload) && (
                    <div className={`${balancePayload || netPayload ? 'mt-3 pt-3 border-t border-slate-100' : ''} space-y-1`}>
                        <div className="flex justify-between items-center text-[11px]">
                            <span className="text-slate-500 font-medium">In / Out Volume</span>
                            <div className="flex items-center gap-1.5 font-semibold">
                                <span className="text-blue-600">{formatCurrency(Math.abs(Number(inflowPayload?.value ?? 0)))}</span>
                                <span className="text-slate-400 font-normal">/</span>
                                <span className="text-rose-600">{formatCurrency(Math.abs(Number(outflowPayload?.value ?? 0)))}</span>
                            </div>
                        </div>
                    </div>
                )}
            </div>
        );
    };

    const sharedXAxis = (
        <XAxis
            dataKey="label"
            tick={{ fontSize: 11, fill: '#64748b' }}
            axisLine={false}
            tickLine={false}
            dy={8}
        />
    );

    const sharedYAxis = (
        <YAxis
            domain={chartDomain}
            tick={{ fontSize: 11, fill: '#64748b' }}
            axisLine={false}
            tickLine={false}
            width={72}
            tickFormatter={formatAxisCurrency}
        />
    );

    return (
        <motion.section
            className="rounded-2xl border border-slate-200/80 bg-white shadow-sm overflow-hidden"
            initial={{ opacity: 0, y: 12 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.25, ease: 'easeOut' }}
        >
            <div className="flex items-start justify-between gap-4 border-b border-slate-100 px-6 py-5 bg-white">
                <div>
                    <h2 className="text-2xl font-bold tracking-tight text-slate-900">{title}</h2>
                    <p className="mt-1 text-sm text-slate-500">{subtitle}</p>
                </div>
                <div className="flex bg-slate-100/80 p-1 rounded-lg border border-slate-200/50">
                    {viewModes.map((mode) => (
                        <button
                            key={mode}
                            type="button"
                            onClick={() => onViewModeChange(mode)}
                            className={`rounded-md px-4 py-1.5 text-sm font-medium capitalize transition-all duration-200 ${
                                viewMode === mode
                                    ? 'bg-white text-slate-900 shadow-sm ring-1 ring-slate-900/5'
                                    : 'text-slate-500 hover:text-slate-700 hover:bg-slate-50'
                            }`}
                        >
                            {mode}
                        </button>
                    ))}
                </div>
            </div>

            {viewMode === 'day' && dayPills.length > 0 && (
                <div className="px-6 pt-4 pb-1">
                    <Popover className="relative inline-flex">
                        {({ close }) => (
                            <>
                                <Popover.Button className="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3.5 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50">
                                    <CalendarDays className="h-3.5 w-3.5 text-slate-400" />
                                    <span>{selectedDayLabel}</span>
                                    <ChevronDown className="h-3.5 w-3.5 text-slate-400" />
                                </Popover.Button>

                                <Transition
                                    enter="transition duration-150 ease-out"
                                    enterFrom="translate-y-1 opacity-0"
                                    enterTo="translate-y-0 opacity-100"
                                    leave="transition duration-100 ease-in"
                                    leaveFrom="translate-y-0 opacity-100"
                                    leaveTo="translate-y-1 opacity-0"
                                >
                                    <Popover.Panel className="absolute left-0 top-full z-20 mt-2 w-[min(92vw,280px)] rounded-xl border border-slate-200 bg-white p-3 shadow-xl">
                                        <div className="space-y-3">
                                            <div className="space-y-1">
                                                <p className="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">Day Focus</p>
                                                <p className="text-sm text-slate-500">Pilih hari spesifik untuk menyorot movement harian.</p>
                                            </div>

                                            <div className="grid max-h-64 gap-2 overflow-y-auto pr-1">
                                                <button
                                                    type="button"
                                                    onClick={() => {
                                                        onDayFilterChange('all');
                                                        close();
                                                    }}
                                                    className={`rounded-lg px-3 py-2 text-left text-sm font-medium transition-colors ${
                                                        selectedDayKey === 'all'
                                                            ? 'bg-slate-900 text-white'
                                                            : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
                                                    }`}
                                                >
                                                    All Days
                                                </button>
                                                {dayPills.map((day) => (
                                                    <button
                                                        key={day.key}
                                                        type="button"
                                                        onClick={() => {
                                                            onDayFilterChange(day.key);
                                                            close();
                                                        }}
                                                        className={`rounded-lg px-3 py-2 text-left text-sm font-medium transition-colors ${
                                                            selectedDayKey === day.key
                                                                ? 'bg-slate-900 text-white'
                                                                : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
                                                        }`}
                                                    >
                                                        {day.label}
                                                    </button>
                                                ))}
                                            </div>
                                        </div>
                                    </Popover.Panel>
                                </Transition>
                            </>
                        )}
                    </Popover>
                </div>
            )}

            {chartData.length === 0 && (
                <div className="m-6 rounded-lg border border-dashed border-slate-200 bg-slate-50 py-10 text-center">
                    <p className="text-sm font-medium text-slate-700">Belum ada pergerakan cashflow pada periode ini.</p>
                    <p className="mt-1 text-sm text-muted-foreground">Coba ubah filter atau tambahkan entry baru untuk melihat proyeksi.</p>
                </div>
            )}



            {/* Day single → bar chart */}
            {chartData.length > 0 && isSingleDay && (
                <div className="relative overflow-x-auto px-6 py-5 show-scrollbar">
                    <div className="min-w-[320px]">
                        <ResponsiveContainer width="100%" height={360}>
                            <ComposedChart
                                data={lineSeries}
                                margin={{ top: 8, right: 8, left: 8, bottom: 8 }}
                                barSize={64}
                                barGap={-64}
                                barCategoryGap="20%"
                            >
                                <CartesianGrid strokeDasharray="3 3" stroke="#e2e8f0" vertical={false} />
                                {sharedXAxis}
                                {sharedYAxis}
                                <Tooltip cursor={{ fill: 'rgba(148,163,184,0.08)' }} content={renderTooltip} />
                                <ReferenceLine y={0} stroke="#cbd5e1" strokeWidth={1} />
                                <Bar dataKey="inflow" name="Inflow" fill={INFLOW_COLOR} radius={[4, 4, 0, 0]}>
                                    {lineSeries.map((entry) => (
                                        <Cell key={entry.key} fill={INFLOW_COLOR} fillOpacity={(entry.inflow ?? 0) > 0 ? 0.85 : 0} />
                                    ))}
                                </Bar>
                                <Bar dataKey="outflow" name="Outflow" fill={OUTFLOW_COLOR} radius={[0, 0, 4, 4]}>
                                    {lineSeries.map((entry) => (
                                        <Cell key={entry.key} fill={OUTFLOW_COLOR} fillOpacity={(entry.outflow ?? 0) < 0 ? 0.85 : 0} />
                                    ))}
                                </Bar>
                                <Legend iconType="square" iconSize={10} wrapperStyle={{ fontSize: 12, paddingTop: 16 }} />
                            </ComposedChart>
                        </ResponsiveContainer>
                    </div>
                </div>
            )}

            {/* Multi-period → composed chart with balance line, dominant trend, & threshold */}
            {chartData.length > 0 && useMultiPeriodChart && (
                <div className="relative overflow-x-auto px-6 py-5 show-scrollbar">
                    <div className="min-w-[680px]" style={lineSeries.length > 18 ? { width: `${lineSeries.length * 48}px` } : undefined}>
                        <ResponsiveContainer width="100%" height={380}>
                            <ComposedChart
                                data={lineSeries}
                                margin={{ top: 8, right: hasBalanceData ? 16 : 8, left: 8, bottom: 8 }}
                                barSize={22}
                                barGap={-22}
                                barCategoryGap="20%"
                            >
                                <CartesianGrid strokeDasharray="3 3" stroke="#e2e8f0" vertical={false} />
                                {sharedXAxis}
                                {sharedYAxis}
                                {hasBalanceData && (
                                    <YAxis
                                        yAxisId="balance"
                                        orientation="right"
                                        domain={balanceDomain}
                                        tick={{ fontSize: 10, fill: BALANCE_COLOR }}
                                        axisLine={false}
                                        tickLine={false}
                                        width={56}
                                        tickFormatter={formatAxisCurrencyShort}
                                    />
                                )}
                                <Tooltip cursor={{ fill: 'rgba(148,163,184,0.08)' }} content={renderTooltip} />
                                <ReferenceLine y={0} stroke="#cbd5e1" strokeWidth={1} />
                                {hasBalanceData && minimumBalanceThreshold && (
                                    <ReferenceLine
                                        yAxisId="balance"
                                        y={minimumBalanceThreshold}
                                        stroke={OUTFLOW_COLOR}
                                        strokeWidth={1}
                                        strokeDasharray="6 4"
                                        label={{
                                            value: `Min ${formatAxisCurrencyShort(minimumBalanceThreshold)}`,
                                            position: 'right',
                                            fontSize: 10,
                                            fontWeight: 600,
                                            fill: OUTFLOW_COLOR,
                                        }}
                                    />
                                )}
                                <Bar dataKey="inflow" name="Inflow" fill={INFLOW_COLOR} radius={[4, 4, 0, 0]}>
                                    {lineSeries.map((entry) => (
                                        <Cell key={entry.key} fill={INFLOW_COLOR} fillOpacity={(entry.inflow ?? 0) > 0 ? 0.35 : 0} />
                                    ))}
                                </Bar>
                                <Bar dataKey="outflow" name="Outflow" fill={OUTFLOW_COLOR} radius={[0, 0, 4, 4]}>
                                    {lineSeries.map((entry) => (
                                        <Cell key={entry.key} fill={OUTFLOW_COLOR} fillOpacity={(entry.outflow ?? 0) < 0 ? 0.35 : 0} />
                                    ))}
                                </Bar>
                                <defs>
                                    <linearGradient id={`${gradientId}-trend`} x1="0" y1="0" x2="0" y2="1">
                                        <stop offset="0%" stopColor={INFLOW_COLOR} />
                                        <stop offset={`${aggregateGradientOffset * 100}%`} stopColor={INFLOW_COLOR} />
                                        <stop offset={`${aggregateGradientOffset * 100}%`} stopColor={OUTFLOW_COLOR} />
                                        <stop offset="100%" stopColor={OUTFLOW_COLOR} />
                                    </linearGradient>
                                </defs>
                                {/* Single Dominant Trend Line */}
                                <Line
                                    type="monotone"
                                    dataKey="dominantMovement"
                                    name="Volume"
                                    stroke="#94a3b8"
                                    strokeWidth={1.5}
                                    strokeDasharray="4 4"
                                    dot={({ cx, cy, payload }) => (
                                        <circle cx={cx} cy={cy} r={3} fill={Number(payload?.dominantMovement ?? 0) < 0 ? OUTFLOW_COLOR : INFLOW_COLOR} stroke="#ffffff" strokeWidth={1.5} />
                                    )}
                                    activeDot={false}
                                    legendType="none"
                                />
                                {/* Balance line on right axis */}
                                {hasBalanceData && (
                                    <Line
                                        yAxisId="balance"
                                        type="monotone"
                                        dataKey="closingBalance"
                                        name="Balance"
                                        stroke={BALANCE_COLOR}
                                        strokeWidth={3.5}
                                        dot={({ cx, cy, payload }) => {
                                            const val = payload?.closingBalance ?? 0;
                                            const isBelowThreshold = minimumBalanceThreshold ? val < minimumBalanceThreshold : false;
                                            return (
                                                <circle
                                                    cx={cx}
                                                    cy={cy}
                                                    r={4}
                                                    fill={isBelowThreshold ? OUTFLOW_COLOR : BALANCE_COLOR}
                                                    stroke="#fff"
                                                    strokeWidth={2}
                                                />
                                            );
                                        }}
                                        activeDot={({ cx, cy, payload }) => (
                                            <circle
                                                cx={cx}
                                                cy={cy}
                                                r={6}
                                                fill={minimumBalanceThreshold && (payload?.closingBalance ?? 0) < minimumBalanceThreshold ? OUTFLOW_COLOR : BALANCE_COLOR}
                                                stroke="#fff"
                                                strokeWidth={2}
                                            />
                                        )}
                                    />
                                )}
                                <Legend
                                    iconSize={10}
                                    wrapperStyle={{ fontSize: 12, paddingTop: 16 }}
                                    {...({
                                        payload: [
                                            { value: 'Inflow', type: 'square', color: INFLOW_COLOR },
                                            { value: 'Outflow', type: 'square', color: OUTFLOW_COLOR },
                                            ...(hasBalanceData ? [{ value: 'Balance', type: 'line' as const, color: BALANCE_COLOR }] : []),
                                        ],
                                    } as any)}
                                />
                            </ComposedChart>
                        </ResponsiveContainer>
                    </div>
                </div>
            )}


        </motion.section>
    );
}
