import { Fragment, useEffect, useRef, useState } from 'react';
import { Listbox, Popover, Transition } from '@headlessui/react';
import { CalendarDays, Check, ChevronDown, Loader2, SlidersHorizontal } from 'lucide-react';
import { format, isValid, parseISO } from 'date-fns';
import { cn } from '@/lib/utils';

export interface DashboardPeriodPreset {
    label: string;
    getRange: () => { from: string; to: string };
}

interface TicketDashboardDateFilterProps {
    presets: DashboardPeriodPreset[];
    dateFrom: string;
    dateTo: string;
    isFiltering: boolean;
    onPresetSelect: (preset: DashboardPeriodPreset) => void;
    onApply: (from: string, to: string) => void;
}

function displayDate(value: string): string {
    const date = parseISO(value);

    return isValid(date) ? format(date, 'dd MMM yyyy') : 'Select date';
}

export function TicketDashboardDateFilter({
    presets,
    dateFrom,
    dateTo,
    isFiltering,
    onPresetSelect,
    onApply,
}: TicketDashboardDateFilterProps) {
    const [draftFrom, setDraftFrom] = useState(dateFrom);
    const [draftTo, setDraftTo] = useState(dateTo);
    const pendingApplyRange = useRef<{ from: string; to: string } | null>(null);
    const activePreset = presets.find((preset) => {
        const range = preset.getRange();

        return dateFrom === range.from && dateTo === range.to;
    });
    const validRange = Boolean(draftFrom && draftTo && draftFrom <= draftTo);
    const displayedRange = `${displayDate(dateFrom)} to ${displayDate(dateTo)}`;

    useEffect(() => {
        setDraftFrom(dateFrom);
        setDraftTo(dateTo);
    }, [dateFrom, dateTo]);

    const resetDraft = () => {
        setDraftFrom(dateFrom);
        setDraftTo(dateTo);
    };

    return (
        <div className="flex w-full flex-wrap items-center gap-1.5 rounded-xl border border-gray-200 bg-white p-1.5 shadow-sm xl:w-auto xl:flex-nowrap">
            <Listbox value={activePreset?.label ?? 'Custom range'} onChange={(label) => {
                const preset = presets.find((item) => item.label === label);

                if (preset) {
                    onPresetSelect(preset);
                }
            }}>
                <div className="relative min-w-36 flex-1 xl:flex-none">
                    <Listbox.Button
                        aria-label={`Select date period, current: ${activePreset?.label ?? 'Custom range'}`}
                        className="flex h-10 w-full items-center justify-between gap-3 rounded-lg px-3 text-sm font-medium text-gray-700 outline-none transition hover:bg-gray-50 focus-visible:ring-2 focus-visible:ring-primary/20"
                    >
                        <span className="truncate">{activePreset?.label ?? 'Custom range'}</span>
                        <ChevronDown className="h-4 w-4 shrink-0 text-gray-400" />
                    </Listbox.Button>

                    <Transition
                        as={Fragment}
                        enter="transition ease-out duration-150"
                        enterFrom="translate-y-1 opacity-0"
                        enterTo="translate-y-0 opacity-100"
                        leave="transition ease-in duration-100"
                        leaveFrom="translate-y-0 opacity-100"
                        leaveTo="translate-y-1 opacity-0"
                    >
                        <Listbox.Options className="absolute left-0 z-50 mt-2 w-48 overflow-hidden rounded-xl border border-gray-200 bg-white p-1.5 shadow-xl focus:outline-none">
                            {presets.map((preset) => (
                                <Listbox.Option
                                    key={preset.label}
                                    value={preset.label}
                                    className={({ active }) => cn(
                                        'flex cursor-pointer select-none items-center justify-between rounded-lg px-3 py-2 text-sm text-gray-700',
                                        active && 'bg-gray-50',
                                    )}
                                >
                                    {({ selected }) => (
                                        <>
                                            <span className={cn(selected && 'font-semibold text-primary')}>{preset.label}</span>
                                            {selected && <Check className="h-4 w-4 text-primary" />}
                                        </>
                                    )}
                                </Listbox.Option>
                            ))}
                        </Listbox.Options>
                    </Transition>
                </div>
            </Listbox>

            <div className="hidden h-7 w-px bg-gray-200 sm:block" />

            <Popover className="relative flex min-w-64 flex-[2] items-center gap-1.5 xl:flex-none">
                {({ open, close }) => (
                <>
                <PopoverCloseSync open={open} onClose={resetDraft} />
                <Popover.Button
                    aria-label={`Choose custom date range, current: ${displayedRange}`}
                    className="flex h-10 w-full items-center gap-2.5 rounded-lg px-3 text-left text-sm text-gray-700 outline-none transition hover:bg-gray-50 focus-visible:ring-2 focus-visible:ring-primary/20 xl:w-72"
                >
                    <CalendarDays className="h-4 w-4 shrink-0 text-gray-400" />
                    <span className="min-w-0 flex-1 truncate font-medium">
                        {displayDate(dateFrom)} <span className="px-1 text-gray-300">–</span> {displayDate(dateTo)}
                    </span>
                    <ChevronDown className="h-4 w-4 shrink-0 text-gray-400" />
                </Popover.Button>

                <Transition
                    as={Fragment}
                    enter="transition ease-out duration-150"
                    enterFrom="translate-y-1 opacity-0 scale-95"
                    enterTo="translate-y-0 opacity-100 scale-100"
                    leave="transition ease-in duration-100"
                    leaveFrom="translate-y-0 opacity-100 scale-100"
                    leaveTo="translate-y-1 opacity-0 scale-95"
                >
                    <Popover.Panel className="absolute right-0 z-50 mt-2 w-[min(22rem,calc(100vw-3rem))] rounded-2xl border border-gray-200 bg-white p-4 shadow-xl">
                        <div className="mb-4">
                            <p className="text-sm font-semibold text-gray-900">Custom date range</p>
                            <p className="mt-1 text-xs text-gray-500">Choose the reporting period, then apply the filter.</p>
                        </div>

                        <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <label className="space-y-1.5">
                                <span className="text-xs font-medium text-gray-600">Start date</span>
                                <input
                                    aria-label="Start date"
                                    aria-invalid={!validRange}
                                    aria-describedby={!validRange ? 'ticket-dashboard-date-range-error' : undefined}
                                    type="date"
                                    value={draftFrom}
                                    max={draftTo || undefined}
                                    onChange={(event) => setDraftFrom(event.target.value)}
                                    className="h-10 w-full rounded-lg border border-gray-200 bg-gray-50/60 px-3 text-sm text-gray-700 outline-none transition focus:border-primary focus:bg-white focus:ring-2 focus:ring-primary/10"
                                />
                            </label>
                            <label className="space-y-1.5">
                                <span className="text-xs font-medium text-gray-600">End date</span>
                                <input
                                    aria-label="End date"
                                    aria-invalid={!validRange}
                                    aria-describedby={!validRange ? 'ticket-dashboard-date-range-error' : undefined}
                                    type="date"
                                    value={draftTo}
                                    min={draftFrom || undefined}
                                    onChange={(event) => setDraftTo(event.target.value)}
                                    className="h-10 w-full rounded-lg border border-gray-200 bg-gray-50/60 px-3 text-sm text-gray-700 outline-none transition focus:border-primary focus:bg-white focus:ring-2 focus:ring-primary/10"
                                />
                            </label>
                        </div>

                        {!validRange && (
                            <p id="ticket-dashboard-date-range-error" className="mt-3 text-xs font-medium text-red-600">
                                Start date must be before the end date.
                            </p>
                        )}
                    </Popover.Panel>
                </Transition>

                <button
                    type="button"
                    aria-label="Apply date filter"
                    onPointerDown={() => {
                        pendingApplyRange.current = { from: draftFrom, to: draftTo };
                    }}
                    onClick={() => {
                        const range = pendingApplyRange.current ?? { from: draftFrom, to: draftTo };
                        pendingApplyRange.current = null;
                        onApply(range.from, range.to);
                        close();
                    }}
                    disabled={!validRange || isFiltering}
                    className="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-primary px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/30 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    {isFiltering ? <Loader2 className="h-4 w-4 animate-spin" /> : <SlidersHorizontal className="h-4 w-4" />}
                    <span className="hidden sm:inline">Apply</span>
                </button>
                </>
                )}
            </Popover>
        </div>
    );
}

function PopoverCloseSync({ open, onClose }: { open: boolean; onClose: () => void }) {
    const wasOpen = useRef(open);
    const onCloseRef = useRef(onClose);
    onCloseRef.current = onClose;

    useEffect(() => {
        if (wasOpen.current && !open) {
            onCloseRef.current();
        }

        wasOpen.current = open;
    }, [open]);

    return null;
}
