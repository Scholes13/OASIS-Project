import * as React from "react"
import { format, isValid, parse } from "date-fns"
import { id as idLocale } from "date-fns/locale"
import { Calendar as CalendarIcon, X } from "lucide-react"
import { Popover, Transition } from "@headlessui/react"
import { cn } from "@/lib/utils"
import { Button } from "./button"

// Simple Calendar Component
interface CalendarProps {
  selected?: Date
  onSelect?: (date: Date | undefined) => void
  disabled?: (date: Date) => boolean
  minDate?: Date
  maxDate?: Date
  className?: string
}

function SimpleCalendar({
  selected,
  onSelect,
  disabled,
  minDate,
  maxDate,
  className,
}: CalendarProps) {
  const [currentMonth, setCurrentMonth] = React.useState(selected || new Date())

  const daysInMonth = new Date(
    currentMonth.getFullYear(),
    currentMonth.getMonth() + 1,
    0
  ).getDate()

  const firstDayOfMonth = new Date(
    currentMonth.getFullYear(),
    currentMonth.getMonth(),
    1
  ).getDay()

  const days = Array.from({ length: daysInMonth }, (_, i) => i + 1)
  const emptyDays = Array.from({ length: firstDayOfMonth }, (_, i) => i)

  const monthNames = [
    "January", "February", "March", "April", "May", "June",
    "July", "August", "September", "October", "November", "December"
  ]

  const prevMonth = () => {
    setCurrentMonth(new Date(currentMonth.getFullYear(), currentMonth.getMonth() - 1))
  }

  const nextMonth = () => {
    setCurrentMonth(new Date(currentMonth.getFullYear(), currentMonth.getMonth() + 1))
  }

  const isDateDisabled = (day: number) => {
    const date = new Date(currentMonth.getFullYear(), currentMonth.getMonth(), day)
    if (disabled?.(date)) return true
    if (minDate && date < minDate) return true
    if (maxDate && date > maxDate) return true
    return false
  }

  const isDateSelected = (day: number) => {
    if (!selected) return false
    return (
      selected.getDate() === day &&
      selected.getMonth() === currentMonth.getMonth() &&
      selected.getFullYear() === currentMonth.getFullYear()
    )
  }

  const isToday = (day: number) => {
    const today = new Date()
    return (
      today.getDate() === day &&
      today.getMonth() === currentMonth.getMonth() &&
      today.getFullYear() === currentMonth.getFullYear()
    )
  }

  const handleSelect = (day: number) => {
    if (isDateDisabled(day)) return
    const date = new Date(currentMonth.getFullYear(), currentMonth.getMonth(), day)
    onSelect?.(date)
  }

  return (
    <div className={cn("p-3", className)}>
      {/* Header */}
      <div className="flex items-center justify-between mb-4">
        <button
          type="button"
          onClick={prevMonth}
          className="p-1.5 hover:bg-gray-100 rounded-md"
        >
          <ChevronLeftIcon className="h-4 w-4 text-gray-600" />
        </button>
        <span className="text-sm font-medium text-gray-900">
          {monthNames[currentMonth.getMonth()]} {currentMonth.getFullYear()}
        </span>
        <button
          type="button"
          onClick={nextMonth}
          className="p-1.5 hover:bg-gray-100 rounded-md"
        >
          <ChevronRightIcon className="h-4 w-4 text-gray-600" />
        </button>
      </div>

      {/* Day names */}
      <div className="grid grid-cols-7 gap-1 mb-2">
        {["Su", "Mo", "Tu", "We", "Th", "Fr", "Sa"].map((day) => (
          <div key={day} className="text-center text-xs font-medium text-gray-500 py-1">
            {day}
          </div>
        ))}
      </div>

      {/* Days */}
      <div className="grid grid-cols-7 gap-1">
        {emptyDays.map((i) => (
          <div key={`empty-${i}`} className="h-8" />
        ))}
        {days.map((day) => (
          <button
            key={day}
            type="button"
            onClick={() => handleSelect(day)}
            disabled={isDateDisabled(day)}
            className={cn(
              "h-8 w-8 text-sm rounded-md transition-colors",
              "hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1",
              isDateSelected(day) && "bg-indigo-600 text-white hover:bg-indigo-700",
              isToday(day) && !isDateSelected(day) && "border border-indigo-300",
              isDateDisabled(day) && "text-gray-300 cursor-not-allowed hover:bg-transparent"
            )}
          >
            {day}
          </button>
        ))}
      </div>
    </div>
  )
}

// Simple icons for calendar navigation
function ChevronLeftIcon({ className }: { className?: string }) {
  return (
    <svg className={className} fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
    </svg>
  )
}

function ChevronRightIcon({ className }: { className?: string }) {
  return (
    <svg className={className} fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
    </svg>
  )
}

// DatePicker Component
interface DatePickerProps {
  value?: Date | string | null
  onChange?: (date: Date | undefined) => void
  placeholder?: string
  disabled?: boolean
  error?: string
  className?: string
  label?: string
  required?: boolean
  format?: string
  clearable?: boolean
  minDate?: Date
  maxDate?: Date
}

export function DatePicker({
  value,
  onChange,
  placeholder = "Select date...",
  disabled = false,
  error,
  className,
  label,
  required,
  format: dateFormat = "dd MMM yyyy",
  clearable = true,
  minDate,
  maxDate,
}: DatePickerProps) {
  // Parse value to Date object
  const dateValue = React.useMemo(() => {
    if (!value) return undefined
    if (value instanceof Date) return isValid(value) ? value : undefined
    const parsed = parse(value, "yyyy-MM-dd", new Date())
    return isValid(parsed) ? parsed : undefined
  }, [value])

  const handleSelect = (date: Date | undefined) => {
    onChange?.(date)
  }

  const handleClear = (e: React.MouseEvent) => {
    e.stopPropagation()
    onChange?.(undefined)
  }

  return (
    <div className={cn("w-full", className)}>
      {label && (
        <label className="mb-1.5 block text-sm font-medium text-gray-700">
          {label}
          {required && <span className="ml-1 text-red-500">*</span>}
        </label>
      )}
      <Popover className="relative">
        {({ open }) => (
          <>
            <Popover.Button
              disabled={disabled}
              className={cn(
                "relative w-full cursor-pointer rounded-md border bg-white py-2 pl-3 pr-10 text-left text-sm shadow-sm transition-colors",
                "focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500",
                disabled && "cursor-not-allowed opacity-50 bg-gray-50",
                error ? "border-red-500" : "border-gray-300"
              )}
            >
              <span className={cn("block truncate", !dateValue && "text-gray-400")}>
                {dateValue ? format(dateValue, dateFormat, { locale: idLocale }) : placeholder}
              </span>
              <span className="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2">
                {clearable && dateValue ? (
                  <button
                    type="button"
                    onClick={handleClear}
                    className="pointer-events-auto p-1 hover:bg-gray-100 rounded"
                  >
                    <X className="h-4 w-4 text-gray-400" />
                  </button>
                ) : (
                  <CalendarIcon className="h-4 w-4 text-gray-400" />
                )}
              </span>
            </Popover.Button>

            <Transition
              as={React.Fragment}
              leave="transition ease-in duration-100"
              leaveFrom="opacity-100"
              leaveTo="opacity-0"
            >
              <Popover.Panel className="absolute z-50 mt-1 w-auto rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none">
                <SimpleCalendar
                  selected={dateValue}
                  onSelect={handleSelect}
                  minDate={minDate}
                  maxDate={maxDate}
                />
              </Popover.Panel>
            </Transition>
          </>
        )}
      </Popover>
      {error && <p className="mt-1 text-xs text-red-500">{error}</p>}
    </div>
  )
}

// DateRangePicker Component
interface DateRangePickerProps {
  startDate?: Date | string | null
  endDate?: Date | string | null
  onStartDateChange?: (date: Date | undefined) => void
  onEndDateChange?: (date: Date | undefined) => void
  startLabel?: string
  endLabel?: string
  disabled?: boolean
  error?: string
  className?: string
}

export function DateRangePicker({
  startDate,
  endDate,
  onStartDateChange,
  onEndDateChange,
  startLabel = "Start Date",
  endLabel = "End Date",
  disabled = false,
  error,
  className,
}: DateRangePickerProps) {
  const parseDate = (value: Date | string | null | undefined) => {
    if (!value) return undefined
    if (value instanceof Date) return isValid(value) ? value : undefined
    const parsed = parse(value, "yyyy-MM-dd", new Date())
    return isValid(parsed) ? parsed : undefined
  }

  const parsedStartDate = parseDate(startDate)
  const parsedEndDate = parseDate(endDate)

  return (
    <div className={cn("flex gap-4", className)}>
      <DatePicker
        label={startLabel}
        value={parsedStartDate}
        onChange={onStartDateChange}
        disabled={disabled}
        maxDate={parsedEndDate}
        error={error}
      />
      <DatePicker
        label={endLabel}
        value={parsedEndDate}
        onChange={onEndDateChange}
        disabled={disabled}
        minDate={parsedStartDate}
      />
    </div>
  )
}

export { SimpleCalendar as Calendar }
