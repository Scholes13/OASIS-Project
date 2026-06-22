import { Popover, Transition } from '@headlessui/react';
import { CalendarDays, ChevronDown } from 'lucide-react';

type ViewMode = 'day' | 'week' | 'month';
type ChartDisplayMode = 'balance' | 'volume';

type DayPill = {
    key: string;
    label: string;
};

type ProjectionChartControlsProps = {
    chartDisplayMode: ChartDisplayMode;
    onChartDisplayModeChange: (mode: ChartDisplayMode) => void;
    viewModes: ViewMode[];
    viewMode: ViewMode;
    onViewModeChange: (mode: ViewMode) => void;
    dayPills: DayPill[];
    selectedDayKey: string;
    selectedDayLabel: string;
    onDayFilterChange: (value: string) => void;
};

export default function ProjectionChartControls({
    chartDisplayMode,
    onChartDisplayModeChange,
    viewModes,
    viewMode,
    onViewModeChange,
    dayPills,
    selectedDayKey,
    selectedDayLabel,
    onDayFilterChange,
}: ProjectionChartControlsProps) {
    const chartDisplayModes: Array<{ label: string; value: ChartDisplayMode }> = [
        { label: 'Saldo Proyeksi', value: 'balance' },
        { label: 'Inflow / Outflow', value: 'volume' },
    ];

    return (
        <div className="flex flex-col items-stretch gap-3 sm:items-end">
            <div className="flex flex-wrap justify-end gap-2">
                <div
                    className="inline-flex rounded-xl border border-slate-200/70 bg-slate-100/80 p-1 shadow-inner shadow-slate-200/50"
                    role="group"
                    aria-label="Chart display mode"
                >
                    {chartDisplayModes.map((mode) => (
                        <button
                            key={mode.value}
                            type="button"
                            aria-pressed={chartDisplayMode === mode.value}
                            onClick={() => onChartDisplayModeChange(mode.value)}
                            className={`rounded-lg px-3.5 py-1.5 text-sm font-semibold transition-all duration-200 ${
                                chartDisplayMode === mode.value
                                    ? 'bg-white text-slate-950 shadow-sm ring-1 ring-slate-900/5'
                                    : 'text-slate-500 hover:bg-white/70 hover:text-slate-700'
                            }`}
                        >
                            {mode.label}
                        </button>
                    ))}
                </div>

                <div
                    className="inline-flex rounded-xl border border-slate-200/70 bg-slate-100/80 p-1 shadow-inner shadow-slate-200/50"
                    role="group"
                    aria-label="Chart granularity"
                >
                    {viewModes.map((mode) => (
                        <button
                            key={mode}
                            type="button"
                            onClick={() => onViewModeChange(mode)}
                            className={`rounded-lg px-3.5 py-1.5 text-sm font-medium capitalize transition-all duration-200 ${
                                viewMode === mode
                                    ? 'bg-white text-slate-900 shadow-sm ring-1 ring-slate-900/5'
                                    : 'text-slate-500 hover:bg-white/70 hover:text-slate-700'
                            }`}
                        >
                            {mode}
                        </button>
                    ))}
                </div>
            </div>

            {viewMode === 'day' && dayPills.length > 0 && (
                <div className="flex justify-end">
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
                                    <Popover.Panel className="absolute right-0 top-full z-20 mt-2 w-[min(92vw,280px)] rounded-xl border border-slate-200 bg-white p-3 shadow-xl">
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
        </div>
    );
}
