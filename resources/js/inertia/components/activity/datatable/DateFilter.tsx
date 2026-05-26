import * as React from "react"
import { format } from "date-fns"
import { id as idLocale } from "date-fns/locale"
import { Calendar, CheckCircle2, ChevronDown, X } from "lucide-react"
import { Popover, Transition } from "@headlessui/react"

import { cn } from "@/lib/utils"

export type DateFilterType = "all" | "today" | "week" | "month" | "custom"

export interface DateRange {
    start: Date | null
    end: Date | null
}

interface DateFilterProps {
    value: DateFilterType
    onChange: (value: DateFilterType) => void
    customRange: DateRange
    onCustomRangeChange: (range: DateRange) => void
}

const dateFilterOptions: { value: DateFilterType; label: string }[] = [
    { value: "all", label: "Semua" },
    { value: "today", label: "Hari Ini" },
    { value: "week", label: "Minggu Ini" },
    { value: "month", label: "Bulan Ini" },
    { value: "custom", label: "Custom" },
]

export function DateFilter({
    value,
    onChange,
    customRange,
    onCustomRangeChange,
}: DateFilterProps) {
    const [showCustom, setShowCustom] = React.useState(false)

    const handleFilterChange = (newValue: DateFilterType) => {
        onChange(newValue)
        setShowCustom(newValue === "custom")
    }

    const getDisplayLabel = () => {
        if (value === "custom" && customRange.start && customRange.end) {
            return `${format(customRange.start, "dd MMM", { locale: idLocale })} - ${format(customRange.end, "dd MMM", { locale: idLocale })}`
        }

        return dateFilterOptions.find((option) => option.value === value)?.label || "Semua"
    }

    return (
        <Popover className="relative">
            {({ close }) => (
                <>
                    <Popover.Button
                        className={cn(
                            "inline-flex items-center gap-2 px-3 py-2 text-sm border rounded-lg transition-colors",
                            value !== "all"
                                ? "border-blue-200 bg-blue-50 text-blue-700"
                                : "border-gray-300 bg-white text-gray-700 hover:bg-gray-50"
                        )}
                    >
                        <Calendar className="h-4 w-4" strokeWidth={1.5} />
                        <span className="font-medium">{getDisplayLabel()}</span>
                        <ChevronDown className="h-3.5 w-3.5 opacity-50" />
                    </Popover.Button>

                    <Transition
                        as={React.Fragment}
                        enter="transition ease-out duration-100"
                        enterFrom="opacity-0 scale-95"
                        enterTo="opacity-100 scale-100"
                        leave="transition ease-in duration-75"
                        leaveFrom="opacity-100 scale-100"
                        leaveTo="opacity-0 scale-95"
                    >
                        <Popover.Panel className="absolute right-0 z-50 mt-2 w-64 bg-white rounded-xl shadow-lg ring-1 ring-gray-200 overflow-hidden">
                            <div className="p-2 border-b border-gray-100">
                                <p className="px-2 py-1 text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Filter Tanggal
                                </p>
                                {dateFilterOptions
                                    .filter((option) => option.value !== "custom")
                                    .map((option) => (
                                        <button
                                            key={option.value}
                                            onClick={() => {
                                                handleFilterChange(option.value)
                                                close()
                                            }}
                                            className={cn(
                                                "flex w-full items-center gap-2 px-3 py-2 text-sm rounded-lg transition-colors",
                                                value === option.value
                                                    ? "bg-blue-50 text-blue-700 font-medium"
                                                    : "text-gray-700 hover:bg-gray-50"
                                            )}
                                        >
                                            {option.label}
                                            {value === option.value && (
                                                <CheckCircle2 className="h-4 w-4 ml-auto text-primary" />
                                            )}
                                        </button>
                                    ))}
                            </div>

                            <div className="p-3">
                                <p className="text-xs font-medium text-gray-500 uppercase tracking-wider mb-2">
                                    Custom Range
                                </p>
                                <div className="space-y-2">
                                    <div>
                                        <label className="block text-xs text-gray-500 mb-1">Dari</label>
                                        <input
                                            type="date"
                                            value={customRange.start ? format(customRange.start, "yyyy-MM-dd") : ""}
                                            onChange={(event) => {
                                                const date = event.target.value ? new Date(event.target.value) : null
                                                onCustomRangeChange({ ...customRange, start: date })
                                            }}
                                            className="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-xs text-gray-500 mb-1">Sampai</label>
                                        <input
                                            type="date"
                                            value={customRange.end ? format(customRange.end, "yyyy-MM-dd") : ""}
                                            onChange={(event) => {
                                                const date = event.target.value ? new Date(event.target.value) : null
                                                onCustomRangeChange({ ...customRange, end: date })
                                            }}
                                            className="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                        />
                                    </div>
                                    <button
                                        onClick={() => {
                                            if (customRange.start && customRange.end) {
                                                handleFilterChange("custom")
                                                close()
                                            }
                                        }}
                                        disabled={!customRange.start || !customRange.end}
                                        className={cn(
                                            "w-full py-2 text-sm font-medium rounded-lg transition-colors",
                                            customRange.start && customRange.end
                                                ? "bg-primary text-white hover:bg-blue-600"
                                                : "bg-gray-100 text-gray-400 cursor-not-allowed"
                                        )}
                                    >
                                        Terapkan
                                    </button>
                                </div>
                            </div>

                            {value !== "all" && (
                                <div className="p-2 border-t border-gray-100">
                                    <button
                                        onClick={() => {
                                            onChange("all")
                                            onCustomRangeChange({ start: null, end: null })
                                            close()
                                        }}
                                        className="flex w-full items-center justify-center gap-1.5 px-3 py-2 text-sm text-gray-500 hover:text-gray-700 hover:bg-gray-50 rounded-lg transition-colors"
                                    >
                                        <X className="h-3.5 w-3.5" />
                                        Hapus Filter
                                    </button>
                                </div>
                            )}
                        </Popover.Panel>
                    </Transition>
                </>
            )}
        </Popover>
    )
}

export default DateFilter
