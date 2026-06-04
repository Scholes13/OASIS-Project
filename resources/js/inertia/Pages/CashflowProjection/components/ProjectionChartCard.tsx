import { motion } from 'framer-motion';
import { useState } from 'react';
import {
    Bar,
    CartesianGrid,
    Cell,
    ComposedChart,
    Legend,
    Line,
    ReferenceLine,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';
import type { TooltipContentProps } from 'recharts';
import ProjectionChartControls from './ProjectionChartControls';
import ProjectionChartTooltip from './ProjectionChartTooltip';
import {
    buildLineSeries,
    resolveLineChartDomain,
} from '../chart-utils';
import { formatCurrency } from '../utils';

type ViewMode = 'day' | 'week' | 'month';
type ChartDisplayMode = 'balance' | 'volume';

type ChartRow = {
    key: string;
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
const OUTFLOW_COLOR = '#ef4444';
const BALANCE_COLOR = '#059669';
const WARNING_COLOR = '#f59e0b';
const ZERO_BALANCE_COLOR = '#ef4444';

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
    const [chartDisplayMode, setChartDisplayMode] = useState<ChartDisplayMode>('balance');
    const lineSeries = buildLineSeries(chartData, viewMode);
    const chartDomain = resolveLineChartDomain(chartData, viewMode);

    // Day single → full opacity bar, All others → composed chart
    const isSingleDay = viewMode === 'day' && selectedDayKey !== 'all';
    const useMultiPeriodChart = !isSingleDay;
    const hasBalanceData = lineSeries.some((row) => row.closingBalance != null && row.closingBalance !== 0);
    const showBalanceLine = chartDisplayMode === 'balance' && useMultiPeriodChart && hasBalanceData;
    const showMovementBars = chartDisplayMode === 'volume' || !useMultiPeriodChart || !hasBalanceData;
    const hideMovementAxis = showBalanceLine;
    const closingBalances = lineSeries
        .map((row) => row.closingBalance)
        .filter((value): value is number => typeof value === 'number');
    const minimumLineThreshold = minimumBalanceThreshold && minimumBalanceThreshold > 0 ? minimumBalanceThreshold : undefined;
    // Right Y-axis domain for balance line (month view)
    const balanceDomain: [number, number] = (() => {
        if (!hasBalanceData) return [0, 1];
        const balances = [...closingBalances];
        balances.push(0);
        if (minimumLineThreshold !== undefined) {
            balances.push(minimumLineThreshold);
        }
        const minBal = Math.min(...balances);
        const maxBal = Math.max(...balances);
        const padding = Math.max((maxBal - minBal) * 0.12, maxBal * 0.04, 1);
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

    const renderTooltip = (props: TooltipContentProps<number | string | ReadonlyArray<number | string>, number | string>) => (
        <ProjectionChartTooltip
            {...props}
            chartDisplayMode={chartDisplayMode}
            minimumBalanceThreshold={minimumBalanceThreshold}
        />
    );

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
            tick={hideMovementAxis ? false : { fontSize: 11, fill: '#64748b' }}
            axisLine={false}
            tickLine={false}
            width={72}
            tickFormatter={formatAxisCurrency}
            hide={hideMovementAxis}
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
                <ProjectionChartControls
                    chartDisplayMode={chartDisplayMode}
                    onChartDisplayModeChange={setChartDisplayMode}
                    viewModes={viewModes}
                    viewMode={viewMode}
                    onViewModeChange={onViewModeChange}
                    dayPills={dayPills}
                    selectedDayKey={selectedDayKey}
                    selectedDayLabel={selectedDayLabel}
                    onDayFilterChange={onDayFilterChange}
                />
            </div>

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
                                <ReferenceLine y={0} stroke={ZERO_BALANCE_COLOR} strokeWidth={1.5} strokeDasharray="4 4" />
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
                                {showBalanceLine && (
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
                                <ReferenceLine y={0} stroke={ZERO_BALANCE_COLOR} strokeWidth={1.5} strokeDasharray="4 4" />
                                {showBalanceLine && (
                                    <ReferenceLine
                                        yAxisId="balance"
                                        y={0}
                                        stroke={ZERO_BALANCE_COLOR}
                                        strokeWidth={1.5}
                                        strokeDasharray="4 4"
                                        label={{
                                            value: 'Saldo 0',
                                            position: 'right',
                                            fontSize: 10,
                                            fontWeight: 700,
                                            fill: ZERO_BALANCE_COLOR,
                                        }}
                                    />
                                )}
                                {showBalanceLine && minimumLineThreshold && (
                                    <ReferenceLine
                                        yAxisId="balance"
                                        y={minimumLineThreshold}
                                        stroke={WARNING_COLOR}
                                        strokeWidth={1}
                                        strokeDasharray="6 4"
                                        label={{
                                            value: `Minimum ${formatAxisCurrencyShort(minimumLineThreshold)}`,
                                            position: 'right',
                                            fontSize: 10,
                                            fontWeight: 600,
                                            fill: WARNING_COLOR,
                                        }}
                                    />
                                )}
                                {showMovementBars && (
                                    <Bar dataKey="inflow" name="Inflow" fill={INFLOW_COLOR} radius={[4, 4, 0, 0]}>
                                        {lineSeries.map((entry) => (
                                            <Cell key={entry.key} fill={INFLOW_COLOR} fillOpacity={(entry.inflow ?? 0) > 0 ? 0.35 : 0} />
                                        ))}
                                    </Bar>
                                )}
                                {showMovementBars && (
                                    <Bar dataKey="outflow" name="Outflow" fill={OUTFLOW_COLOR} radius={[0, 0, 4, 4]}>
                                        {lineSeries.map((entry) => (
                                            <Cell key={entry.key} fill={OUTFLOW_COLOR} fillOpacity={(entry.outflow ?? 0) < 0 ? 0.35 : 0} />
                                        ))}
                                    </Bar>
                                )}
                                {/* Balance line on right axis */}
                                {showBalanceLine && (
                                    <Line
                                        yAxisId="balance"
                                        type="monotone"
                                        dataKey="closingBalance"
                                        name="Saldo Proyeksi"
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
                                                    stroke="#f8fafc"
                                                    strokeWidth={2}
                                                />
                                            );
                                        }}
                                        activeDot={({ cx, cy, payload }) => (
                                            <circle
                                                cx={cx}
                                                cy={cy}
                                                r={6}
                                                fill={
                                                    minimumBalanceThreshold && (payload?.closingBalance ?? 0) < minimumBalanceThreshold
                                                        ? OUTFLOW_COLOR
                                                        : BALANCE_COLOR
                                                }
                                                stroke="#f8fafc"
                                                strokeWidth={2}
                                            />
                                        )}
                                    />
                                )}
                                <Legend
                                    iconSize={10}
                                    wrapperStyle={{ fontSize: 12, paddingTop: 16 }}
                                />
                            </ComposedChart>
                        </ResponsiveContainer>
                    </div>
                </div>
            )}


        </motion.section>
    );
}
