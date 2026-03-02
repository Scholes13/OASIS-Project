import { motion } from 'framer-motion';
import { formatCurrency } from '../utils';

type ViewMode = 'day' | 'week' | 'month';

type ChartRow = {
    label: string;
    inflow: number;
    outflow: number;
};

type DayPill = {
    key: string;
    label: string;
};

interface ProjectionChartCardProps {
    chartData: ChartRow[];
    chartMaxValue: number;
    viewMode: ViewMode;
    onViewModeChange: (mode: ViewMode) => void;
    dayPills: DayPill[];
    selectedDayKey: string;
    onDayFilterChange: (value: string) => void;
}

function formatAxisValue(value: number): string {
    if (value >= 1000000000) return `${(value / 1000000000).toFixed(1).replace('.0', '')}B`;
    if (value >= 1000000) return `${Math.round(value / 1000000)}M`;
    if (value >= 1000) return `${Math.round(value / 1000)}k`;
    return `${Math.round(value)}`;
}

function barHeightPercent(value: number, maxValue: number): number {
    if (value <= 0) return 0;
    return Math.max((value / maxValue) * 100, 2);
}

const viewModes: ViewMode[] = ['day', 'week', 'month'];

export default function ProjectionChartCard({
    chartData,
    chartMaxValue,
    viewMode,
    onViewModeChange,
    dayPills,
    selectedDayKey,
    onDayFilterChange,
}: ProjectionChartCardProps) {
    const axisValues = [chartMaxValue, chartMaxValue * 0.75, chartMaxValue * 0.5, chartMaxValue * 0.25, 0];
    const barsAnimationKey = `${viewMode}-${selectedDayKey}-${chartData.length}`;

    return (
        <motion.section
            className="rounded-2xl border border-border bg-card p-6 shadow-[0_1px_2px_rgba(15,23,42,0.04)]"
            initial={{ opacity: 0, y: 12 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.25, ease: 'easeOut' }}
        >
            {/* Header */}
            <div className="mb-4 flex items-center justify-between">
                <h2 className="text-xl font-semibold text-foreground">Cashflow Projection</h2>
                <div className="flex items-center rounded-lg border border-slate-200 bg-slate-50/80 p-1">
                    {viewModes.map((mode) => (
                        <button
                            key={mode}
                            type="button"
                            onClick={() => onViewModeChange(mode)}
                            className={`rounded-md px-3 py-1.5 text-[13px] font-medium capitalize transition-all duration-200 ${
                                viewMode === mode
                                    ? 'bg-white text-primary shadow-sm font-semibold ring-1 ring-slate-200/50'
                                    : 'text-slate-500 hover:text-slate-800 hover:bg-slate-100/50'
                            }`}
                        >
                            {mode}
                        </button>
                    ))}
                </div>
            </div>

            {/* Day pills */}
            {viewMode === 'day' && (
                <div className="mb-4 flex gap-2 overflow-x-auto pb-1 show-scrollbar">
                    <button
                        type="button"
                        onClick={() => onDayFilterChange('all')}
                        className={`shrink-0 rounded-full px-3 py-1 text-xs font-semibold transition-colors ${
                            selectedDayKey === 'all'
                                ? 'bg-primary text-primary-foreground'
                                : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
                        }`}
                    >
                        All Days
                    </button>
                    {dayPills.map((day) => (
                        <button
                            key={day.key}
                            type="button"
                            onClick={() => onDayFilterChange(day.key)}
                            className={`shrink-0 rounded-full px-3 py-1 text-xs font-semibold transition-colors ${
                                selectedDayKey === day.key
                                    ? 'bg-primary text-primary-foreground'
                                    : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
                            }`}
                        >
                            {day.label}
                        </button>
                    ))}
                </div>
            )}

            {/* Chart area */}
            <div className="relative overflow-x-auto show-scrollbar">
                <div className="relative min-h-[280px]" style={chartData.length > 16 ? { minWidth: `${chartData.length * 2.25}rem` } : undefined}>
                    {/* Y-axis grid lines */}
                    <div className="absolute inset-0 flex flex-col justify-between pointer-events-none">
                        {axisValues.map((value) => (
                            <div key={value} className="flex items-center border-b border-slate-100">
                                <span className="w-10 shrink-0 text-right pr-2 text-[10px] text-muted-foreground">{formatAxisValue(value)}</span>
                            </div>
                        ))}
                    </div>

                    {/* Bars */}
                    <div className="relative flex items-end justify-around gap-1 pl-12" style={{ height: '280px' }} key={barsAnimationKey}>
                        {chartData.map((row, index) => (
                            <div key={`${row.label}-${row.inflow}-${row.outflow}`} className="flex flex-col items-center gap-1" style={{ flex: '1 1 0' }}>
                                <div className="flex items-end gap-[3px]" style={{ height: '250px' }}>
                                    <motion.div
                                        className="cfp-bar inflow"
                                        initial={{ height: 0 }}
                                        animate={{ height: `${barHeightPercent(row.inflow, chartMaxValue)}%` }}
                                        transition={{ duration: 0.45, ease: 'easeOut', delay: index * 0.02 }}
                                        data-value={formatCurrency(row.inflow)}
                                    />
                                    <motion.div
                                        className="cfp-bar outflow"
                                        initial={{ height: 0 }}
                                        animate={{ height: `${barHeightPercent(row.outflow, chartMaxValue)}%` }}
                                        transition={{ duration: 0.45, ease: 'easeOut', delay: index * 0.02 + 0.04 }}
                                        data-value={formatCurrency(row.outflow)}
                                    />
                                </div>
                                <span className="text-[10px] text-muted-foreground">{row.label}</span>
                            </div>
                        ))}
                    </div>
                </div>
            </div>

            {/* Legend */}
            <div className="mt-4 flex items-center justify-center gap-6">
                <div className="flex items-center gap-2 text-xs text-muted-foreground">
                    <div className="h-2.5 w-2.5 rounded-full bg-primary" />
                    Inflow
                </div>
                <div className="flex items-center gap-2 text-xs text-muted-foreground">
                    <div className="h-2.5 w-2.5 rounded-full bg-red-500" />
                    Outflow
                </div>
            </div>
        </motion.section>
    );
}
