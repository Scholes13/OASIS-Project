import * as React from "react"
import { Link, router, usePage } from "@inertiajs/react"
import { ColumnDef } from "@tanstack/react-table"
import { 
    format, 
    isPast, 
    isToday, 
    isThisWeek, 
    isThisMonth, 
    startOfDay, 
    endOfDay,
    startOfWeek,
    endOfWeek,
    startOfMonth,
    endOfMonth,
    isWithinInterval,
    parseISO
} from "date-fns"
import { id as idLocale } from "date-fns/locale"
import {
    MoreHorizontal,
    Eye,
    Edit,
    Trash2,
    AlertTriangle,
    User,
    Users,
    ChevronDown,
    Circle,
    PlayCircle,
    CheckCircle2,
    XCircle,
    ListTodo,
    Calendar,
    X,
} from "lucide-react"
import { DataTable, SortableHeader } from "../ui/data-table"
import { cn } from "@/lib/utils"
import { TaskDetailModal } from "./TaskDetailModal"
import { showToast } from "../ui/toast"
import type { Task, PaginatedData, PageProps, TaskStatus, TaskStats } from "@/types"
import { Popover, Transition } from "@headlessui/react"

type ViewMode = "my" | "department"
type DateFilterType = "all" | "today" | "week" | "month" | "custom"

interface DateRange {
    start: Date | null
    end: Date | null
}

interface ActivityDataTableProps {
    tasks: PaginatedData<Task>
    stats?: TaskStats
    onRowClick?: (task: Task) => void
    showActions?: boolean
    compact?: boolean
    showHeader?: boolean
}

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

function isOverdue(dueDate: string, status: string): boolean {
    if (status === "completed" || status === "cancelled") return false
    return isPast(new Date(dueDate)) && !isToday(new Date(dueDate))
}

function formatDueDate(dateString: string) {
    return format(new Date(dateString), "dd MMM yyyy", { locale: idLocale })
}

// Check if task due date is within date range
function isWithinDateFilter(dueDate: string, filterType: DateFilterType, customRange: DateRange): boolean {
    if (filterType === "all") return true
    
    const taskDate = parseISO(dueDate)
    const today = new Date()
    
    switch (filterType) {
        case "today":
            return isToday(taskDate)
        case "week":
            return isThisWeek(taskDate, { locale: idLocale, weekStartsOn: 1 })
        case "month":
            return isThisMonth(taskDate)
        case "custom":
            if (customRange.start && customRange.end) {
                return isWithinInterval(taskDate, {
                    start: startOfDay(customRange.start),
                    end: endOfDay(customRange.end)
                })
            }
            return true
        default:
            return true
    }
}

// ============================================================================
// DATE FILTER COMPONENT
// ============================================================================

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

function DateFilter({ value, onChange, customRange, onCustomRangeChange }: DateFilterProps) {
    const [showCustom, setShowCustom] = React.useState(false)
    
    const handleFilterChange = (newValue: DateFilterType) => {
        onChange(newValue)
        if (newValue === "custom") {
            setShowCustom(true)
        } else {
            setShowCustom(false)
        }
    }
    
    const getDisplayLabel = () => {
        if (value === "custom" && customRange.start && customRange.end) {
            return `${format(customRange.start, "dd MMM", { locale: idLocale })} - ${format(customRange.end, "dd MMM", { locale: idLocale })}`
        }
        return dateFilterOptions.find(opt => opt.value === value)?.label || "Semua"
    }
    
    return (
        <Popover className="relative">
            {({ close }) => (
                <>
                    <Popover.Button
                        className={cn(
                            "inline-flex items-center gap-2 px-3 py-2 text-sm border rounded-lg transition-colors",
                            value !== "all" 
                                ? "border-indigo-300 bg-indigo-50 text-indigo-700" 
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
                            {/* Quick Filters */}
                            <div className="p-2 border-b border-gray-100">
                                <p className="px-2 py-1 text-xs font-medium text-gray-500 uppercase tracking-wider">Filter Tanggal</p>
                                {dateFilterOptions.filter(opt => opt.value !== "custom").map((option) => (
                                    <button
                                        key={option.value}
                                        onClick={() => {
                                            handleFilterChange(option.value)
                                            close()
                                        }}
                                        className={cn(
                                            "flex w-full items-center gap-2 px-3 py-2 text-sm rounded-lg transition-colors",
                                            value === option.value 
                                                ? "bg-indigo-50 text-indigo-700 font-medium" 
                                                : "text-gray-700 hover:bg-gray-50"
                                        )}
                                    >
                                        {option.label}
                                        {value === option.value && (
                                            <CheckCircle2 className="h-4 w-4 ml-auto text-indigo-500" />
                                        )}
                                    </button>
                                ))}
                            </div>
                            
                            {/* Custom Range */}
                            <div className="p-3">
                                <p className="text-xs font-medium text-gray-500 uppercase tracking-wider mb-2">Custom Range</p>
                                <div className="space-y-2">
                                    <div>
                                        <label className="block text-xs text-gray-500 mb-1">Dari</label>
                                        <input
                                            type="date"
                                            value={customRange.start ? format(customRange.start, "yyyy-MM-dd") : ""}
                                            onChange={(e) => {
                                                const date = e.target.value ? new Date(e.target.value) : null
                                                onCustomRangeChange({ ...customRange, start: date })
                                            }}
                                            className="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-xs text-gray-500 mb-1">Sampai</label>
                                        <input
                                            type="date"
                                            value={customRange.end ? format(customRange.end, "yyyy-MM-dd") : ""}
                                            onChange={(e) => {
                                                const date = e.target.value ? new Date(e.target.value) : null
                                                onCustomRangeChange({ ...customRange, end: date })
                                            }}
                                            className="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
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
                                                ? "bg-indigo-600 text-white hover:bg-indigo-700"
                                                : "bg-gray-100 text-gray-400 cursor-not-allowed"
                                        )}
                                    >
                                        Terapkan
                                    </button>
                                </div>
                            </div>
                            
                            {/* Clear Filter */}
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

// ============================================================================
// INTERACTIVE STATS CARDS - Soft Colored with Click-to-Filter & Quiet Default
// ============================================================================

type StatusFilter = "all" | "in_progress" | "overdue" | "completed"

const colorThemes = {
    indigo: {
        bg: "bg-indigo-50",
        border: "border-indigo-200",
        hoverBorder: "hover:border-indigo-400",
        text: "text-indigo-700",
        icon: "text-indigo-600",
        ring: "ring-indigo-500",
    },
    amber: {
        bg: "bg-amber-50",
        border: "border-amber-200",
        hoverBorder: "hover:border-amber-400",
        text: "text-amber-700",
        icon: "text-amber-600",
        ring: "ring-amber-500",
    },
    rose: {
        bg: "bg-rose-50",
        border: "border-rose-200",
        hoverBorder: "hover:border-rose-400",
        text: "text-rose-700",
        icon: "text-rose-600",
        ring: "ring-rose-500",
    },
    emerald: {
        bg: "bg-emerald-50",
        border: "border-emerald-200",
        hoverBorder: "hover:border-emerald-400",
        text: "text-emerald-700",
        icon: "text-emerald-600",
        ring: "ring-emerald-500",
    },
    // Neutral theme for "quiet default" Total card
    neutral: {
        bg: "bg-gray-50",
        border: "border-gray-200",
        hoverBorder: "hover:border-gray-300",
        text: "text-gray-700",
        icon: "text-gray-500",
        ring: "ring-gray-400",
    },
}

interface StatsCardConfig {
    label: string
    status: StatusFilter
    color: keyof typeof colorThemes
    icon: React.ReactNode
}

interface InteractiveStatsCardProps {
    config: StatsCardConfig
    value: number
    isActive: boolean
    isQuietDefault: boolean // "Total" card when filter is "all" - no ring
    onClick: () => void
}

function InteractiveStatsCard({ config, value, isActive, isQuietDefault, onClick }: InteractiveStatsCardProps) {
    // Use neutral theme for Total when it's the quiet default
    const theme = isQuietDefault ? colorThemes.neutral : colorThemes[config.color]
    
    // Show ring only when actively filtered (not for quiet default)
    const showRing = isActive && !isQuietDefault
    
    return (
        <button
            onClick={onClick}
            className={cn(
                "flex items-center gap-3 px-4 py-3 rounded-xl border transition-all cursor-pointer text-left w-full",
                theme.bg,
                theme.border,
                theme.hoverBorder,
                "hover:shadow-md",
                showRing && "ring-2 ring-offset-2",
                showRing && colorThemes[config.color].ring
            )}
        >
            <div className={cn("flex-shrink-0", isQuietDefault ? colorThemes.neutral.icon : colorThemes[config.color].icon)}>
                {config.icon}
            </div>
            <div className="flex-1 min-w-0">
                <p className={cn("text-xs font-medium truncate opacity-80", isQuietDefault ? colorThemes.neutral.text : colorThemes[config.color].text)}>
                    {config.label}
                </p>
                <p className={cn("text-2xl font-bold tracking-tight", isQuietDefault ? colorThemes.neutral.text : colorThemes[config.color].text)}>
                    {value}
                </p>
            </div>
            {showRing && (
                <CheckCircle2 className={cn("h-4 w-4 flex-shrink-0", colorThemes[config.color].icon)} strokeWidth={2} />
            )}
        </button>
    )
}

interface MetricCardsProps {
    stats?: TaskStats
    filteredCount: number
    activeFilter: StatusFilter
    onFilterChange: (filter: StatusFilter) => void
}

function MetricCards({ stats, filteredCount, activeFilter, onFilterChange }: MetricCardsProps) {
    const safeStats = stats || { total: filteredCount, planned: 0, in_progress: 0, completed: 0, overdue: 0 }
    
    const statsConfig: StatsCardConfig[] = [
        {
            label: "Total Activities",
            status: "all",
            color: "indigo",
            icon: <ListTodo className="h-5 w-5" strokeWidth={1.5} />,
        },
        {
            label: "In Progress",
            status: "in_progress",
            color: "amber",
            icon: <PlayCircle className="h-5 w-5" strokeWidth={1.5} />,
        },
        {
            label: "Overdue",
            status: "overdue",
            color: "rose",
            icon: <AlertTriangle className="h-5 w-5" strokeWidth={1.5} />,
        },
        {
            label: "Completed",
            status: "completed",
            color: "emerald",
            icon: <CheckCircle2 className="h-5 w-5" strokeWidth={1.5} />,
        },
    ]

    const getValueForStatus = (status: StatusFilter): number => {
        switch (status) {
            case "all": return safeStats.total
            case "in_progress": return safeStats.in_progress
            case "overdue": return safeStats.overdue
            case "completed": return safeStats.completed
            default: return 0
        }
    }
    
    return (
        <div className="grid grid-cols-2 lg:grid-cols-4 gap-3 px-6 py-5">
            {statsConfig.map((config) => {
                const isActive = activeFilter === config.status
                // "Quiet Default": Total card is neutral when filter is "all"
                const isQuietDefault = config.status === "all" && activeFilter === "all"
                
                return (
                    <InteractiveStatsCard
                        key={config.status}
                        config={config}
                        value={getValueForStatus(config.status)}
                        isActive={isActive}
                        isQuietDefault={isQuietDefault}
                        onClick={() => onFilterChange(config.status)}
                    />
                )
            })}
        </div>
    )
}

// ============================================================================
// SUBTLE STATUS BADGE - Modern Enterprise Style
// ============================================================================

const statusConfig: Record<string, { 
    label: string
    icon: React.ReactNode
    bg: string
    text: string
    ring: string
}> = {
    planned: {
        label: "Planned",
        icon: <Circle className="h-3.5 w-3.5" />,
        bg: "bg-slate-50",
        text: "text-slate-600",
        ring: "ring-slate-200",
    },
    in_progress: {
        label: "In Progress",
        icon: <PlayCircle className="h-3.5 w-3.5" />,
        bg: "bg-blue-50",
        text: "text-blue-700",
        ring: "ring-blue-200",
    },
    completed: {
        label: "Completed",
        icon: <CheckCircle2 className="h-3.5 w-3.5" />,
        bg: "bg-emerald-50",
        text: "text-emerald-700",
        ring: "ring-emerald-200",
    },
    cancelled: {
        label: "Cancelled",
        icon: <XCircle className="h-3.5 w-3.5" />,
        bg: "bg-gray-50",
        text: "text-gray-500",
        ring: "ring-gray-200",
    },
}

function StatusDropdown({ task }: { task: Task }) {
    const [isUpdating, setIsUpdating] = React.useState(false)
    const current = statusConfig[task.status] || statusConfig.planned

    const handleStatusChange = (newStatus: TaskStatus, close: () => void) => {
        if (newStatus === task.status) {
            close()
            return
        }

        setIsUpdating(true)
        router.patch(
            route("activity.task.update", { task: task.id }),
            { status: newStatus },
            {
                preserveScroll: true,
                preserveState: false,
                only: ['tasks', 'stats', 'byActivityType'],
                onSuccess: () => {
                    showToast.success("Status updated", `${task.task_title} → ${statusConfig[newStatus]?.label}`)
                    close()
                },
                onError: () => showToast.error("Failed to update status"),
                onFinish: () => setIsUpdating(false),
            }
        )
    }

    return (
        <Popover className="relative">
            {({ close }) => (
                <>
                    <Popover.Button
                        className={cn(
                            "inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-md text-sm font-medium ring-1 ring-inset transition-all",
                            current.bg, current.text, current.ring,
                            "hover:ring-2",
                            isUpdating && "opacity-50 cursor-wait"
                        )}
                        onClick={(e) => e.stopPropagation()}
                        disabled={isUpdating}
                    >
                        {current.icon}
                        {current.label}
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
                        <Popover.Panel
                            className="absolute left-0 z-50 mt-1.5 w-40 bg-white rounded-lg shadow-lg ring-1 ring-gray-200 py-1"
                            onClick={(e) => e.stopPropagation()}
                        >
                            {Object.entries(statusConfig).map(([key, config]) => (
                                <button
                                    key={key}
                                    onClick={() => handleStatusChange(key as TaskStatus, close)}
                                    disabled={isUpdating}
                                    className={cn(
                                        "flex w-full items-center gap-2 px-3 py-2 text-sm transition-colors",
                                        "hover:bg-gray-50",
                                        task.status === key && "bg-gray-50 font-medium"
                                    )}
                                >
                                    <span className={config.text}>{config.icon}</span>
                                    <span className="text-gray-700">{config.label}</span>
                                    {task.status === key && (
                                        <CheckCircle2 className="h-3.5 w-3.5 ml-auto text-indigo-500" />
                                    )}
                                </button>
                            ))}
                        </Popover.Panel>
                    </Transition>
                </>
            )}
        </Popover>
    )
}

// ============================================================================
// AVATAR STACK - Overlapping Avatars
// ============================================================================

const avatarColors = [
    "bg-indigo-100 text-indigo-700",
    "bg-emerald-100 text-emerald-700",
    "bg-amber-100 text-amber-700",
    "bg-rose-100 text-rose-700",
    "bg-violet-100 text-violet-700",
]

function AvatarStack({ participants, max = 2 }: { participants: any[]; max?: number }) {
    if (!participants || participants.length === 0) {
        return <span className="text-sm text-gray-400">—</span>
    }

    const visible = participants.slice(0, max)
    const remaining = participants.length - max

    return (
        <div className="flex items-center">
            <div className="flex items-center" style={{ marginLeft: 0 }}>
                {visible.map((p, index) => {
                    const name = p.name || p.user?.name || "U"
                    return (
                        <div
                            key={p.id || p.user_id || index}
                            className={cn(
                                "w-9 h-9 rounded-full border-2 border-white flex items-center justify-center text-sm font-semibold shadow-sm",
                                avatarColors[index % avatarColors.length]
                            )}
                            style={{ marginLeft: index === 0 ? 0 : -10 }}
                            title={name}
                        >
                            {name.charAt(0).toUpperCase()}
                        </div>
                    )
                })}
                {remaining > 0 && (
                    <div 
                        className="w-9 h-9 rounded-full bg-gray-100 border-2 border-white flex items-center justify-center text-xs font-semibold text-gray-600 shadow-sm"
                        style={{ marginLeft: -10 }}
                    >
                        +{remaining}
                    </div>
                )}
            </div>
        </div>
    )
}

// ============================================================================
// ACTIVITY TYPE BADGE - Subtle Style
// ============================================================================

const typeColors: Record<string, string> = {
    blue: "bg-blue-50 text-blue-700 ring-blue-200",
    indigo: "bg-indigo-50 text-indigo-700 ring-indigo-200",
    purple: "bg-purple-50 text-purple-700 ring-purple-200",
    pink: "bg-pink-50 text-pink-700 ring-pink-200",
    red: "bg-red-50 text-red-700 ring-red-200",
    orange: "bg-orange-50 text-orange-700 ring-orange-200",
    amber: "bg-amber-50 text-amber-700 ring-amber-200",
    yellow: "bg-yellow-50 text-yellow-700 ring-yellow-200",
    lime: "bg-lime-50 text-lime-700 ring-lime-200",
    green: "bg-green-50 text-green-700 ring-green-200",
    emerald: "bg-emerald-50 text-emerald-700 ring-emerald-200",
    teal: "bg-teal-50 text-teal-700 ring-teal-200",
    cyan: "bg-cyan-50 text-cyan-700 ring-cyan-200",
    gray: "bg-gray-50 text-gray-600 ring-gray-200",
}

function TypeBadge({ name, color }: { name: string; color?: string }) {
    const colorClass = typeColors[color || "gray"] || typeColors.gray
    return (
        <span className={cn(
            "inline-flex items-center px-2.5 py-1 rounded-md text-sm font-medium ring-1 ring-inset",
            colorClass
        )}>
            {name}
        </span>
    )
}

// ============================================================================
// ROW ACTION MENU - Hidden by Default
// ============================================================================

function RowActions({ task, visible }: { task: Task; visible: boolean }) {
    return (
        <Popover className="relative">
            <Popover.Button
                className={cn(
                    "p-1.5 rounded-md hover:bg-gray-100 transition-all",
                    visible ? "opacity-100" : "opacity-0"
                )}
                onClick={(e) => e.stopPropagation()}
            >
                <MoreHorizontal className="h-4 w-4 text-gray-400" strokeWidth={1.5} />
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
                <Popover.Panel className="absolute right-0 z-50 mt-1 w-32 bg-white rounded-lg shadow-lg ring-1 ring-gray-200 py-1">
                    <Link
                        href={route("activity.task.show", { task: task.id })}
                        className="flex items-center px-3 py-1.5 text-xs text-gray-700 hover:bg-gray-50"
                    >
                        <Eye className="mr-2 h-3.5 w-3.5" strokeWidth={1.5} />
                        View
                    </Link>
                    <Link
                        href={route("activity.task.edit", { task: task.id })}
                        className="flex items-center px-3 py-1.5 text-xs text-gray-700 hover:bg-gray-50"
                    >
                        <Edit className="mr-2 h-3.5 w-3.5" strokeWidth={1.5} />
                        Edit
                    </Link>
                    <button
                        onClick={(e) => {
                            e.stopPropagation()
                            if (confirm("Delete this activity?")) {
                                router.delete(route("activity.task.destroy", { task: task.id }))
                            }
                        }}
                        className="flex w-full items-center px-3 py-1.5 text-xs text-rose-600 hover:bg-rose-50"
                    >
                        <Trash2 className="mr-2 h-3.5 w-3.5" strokeWidth={1.5} />
                        Delete
                    </button>
                </Popover.Panel>
            </Transition>
        </Popover>
    )
}

// ============================================================================
// TABLE COLUMNS - Modern Typography Hierarchy
// ============================================================================

const createColumns = (showActions: boolean): ColumnDef<Task>[] => {
    const columns: ColumnDef<Task>[] = [
        {
            accessorKey: "task_title",
            header: ({ column }) => <SortableHeader column={column} title="Activity" />,
            cell: ({ row }) => {
                const task = row.original
                const overdue = isOverdue(task.due_date, task.status)
                return (
                    <div className="min-w-[200px] max-w-[320px]">
                        <p className="font-medium text-gray-900 truncate text-base">{task.task_title}</p>
                        {overdue && (
                            <span className="inline-flex items-center text-xs text-rose-500 mt-0.5 font-medium">
                                <AlertTriangle className="h-3.5 w-3.5 mr-0.5" strokeWidth={2} />
                                Overdue
                            </span>
                        )}
                    </div>
                )
            },
        },
        {
            accessorKey: "activity_type.name",
            header: "Type",
            cell: ({ row }) => {
                const type = row.original.activity_type
                return <TypeBadge name={type?.name ?? "—"} color={type?.color} />
            },
        },
        {
            accessorKey: "due_date",
            header: ({ column }) => <SortableHeader column={column} title="Due Date" />,
            cell: ({ row }) => {
                const task = row.original
                const overdue = isOverdue(task.due_date, task.status)
                return (
                    <span className={cn(
                        "text-base",
                        overdue ? "text-rose-600 font-medium" : "text-gray-600"
                    )}>
                        {formatDueDate(task.due_date)}
                    </span>
                )
            },
        },
        {
            accessorKey: "status",
            header: "Status",
            cell: ({ row }) => <StatusDropdown task={row.original} />,
        },
        {
            accessorKey: "participants",
            header: "Team",
            cell: ({ row }) => <AvatarStack participants={row.original.participants || []} />,
            enableSorting: false,
        },
    ]

    if (showActions) {
        columns.push({
            id: "actions",
            header: "",
            cell: ({ row, table }) => {
                // Access hover state from row - we'll handle this via CSS
                return (
                    <div className="opacity-0 group-hover:opacity-100 transition-opacity">
                        <RowActions task={row.original} visible={true} />
                    </div>
                )
            },
            enableSorting: false,
        })
    }

    return columns
}

// ============================================================================
// MAIN COMPONENT
// ============================================================================

export function ActivityDataTable({
    tasks,
    stats,
    onRowClick,
    showActions = true,
    compact = false,
    showHeader = true,
}: ActivityDataTableProps) {
    const [viewMode, setViewMode] = React.useState<ViewMode>("my")
    const [statusFilter, setStatusFilter] = React.useState<StatusFilter>("all")
    const [dateFilter, setDateFilter] = React.useState<DateFilterType>("all")
    const [customRange, setCustomRange] = React.useState<DateRange>({ start: null, end: null })
    const [selectedTask, setSelectedTask] = React.useState<Task | null>(null)
    const [showModal, setShowModal] = React.useState(false)

    const { auth } = usePage<PageProps>().props
    const currentUserId = auth?.user?.id
    const taskData = tasks?.data ?? []

    // Filter tasks by view mode, status, and date
    const filteredTasks = React.useMemo(() => {
        let filtered = taskData.filter((task) => task.status !== "cancelled")
        
        // Filter by view mode (my tasks vs department)
        if (viewMode === "my") {
            filtered = filtered.filter((task) => {
                const isCreator = task.created_by === currentUserId
                const isParticipant = task.participants?.some(
                    (p) => p.user_id === currentUserId || p.id === currentUserId
                )
                return isCreator || isParticipant
            })
        }
        
        // Filter by date range
        if (dateFilter !== "all") {
            filtered = filtered.filter((task) => 
                isWithinDateFilter(task.due_date, dateFilter, customRange)
            )
        }
        
        // Filter by status (from stats cards)
        if (statusFilter !== "all") {
            if (statusFilter === "overdue") {
                filtered = filtered.filter((task) => isOverdue(task.due_date, task.status))
            } else {
                filtered = filtered.filter((task) => task.status === statusFilter)
            }
        }
        
        return filtered
    }, [taskData, viewMode, currentUserId, statusFilter, dateFilter, customRange])

    // Calculate stats based on viewMode and dateFilter (ALWAYS calculate locally for sync)
    const calculatedStats = React.useMemo(() => {
        // Get tasks filtered by view mode and date (not status filter) for accurate card counts
        let baseTasks = taskData.filter((task) => task.status !== "cancelled")
        
        // Apply view mode filter to stats
        if (viewMode === "my") {
            baseTasks = baseTasks.filter((task) => {
                const isCreator = task.created_by === currentUserId
                const isParticipant = task.participants?.some(
                    (p) => p.user_id === currentUserId || p.id === currentUserId
                )
                return isCreator || isParticipant
            })
        }
        
        // Apply date filter to stats
        if (dateFilter !== "all") {
            baseTasks = baseTasks.filter((task) => 
                isWithinDateFilter(task.due_date, dateFilter, customRange)
            )
        }
        
        const overdue = baseTasks.filter(t => isOverdue(t.due_date, t.status)).length
        return {
            total: baseTasks.length,
            planned: baseTasks.filter(t => t.status === "planned").length,
            in_progress: baseTasks.filter(t => t.status === "in_progress").length,
            completed: baseTasks.filter(t => t.status === "completed").length,
            overdue,
        }
    }, [taskData, viewMode, currentUserId, dateFilter, customRange])

    const columns = React.useMemo(() => createColumns(showActions), [showActions])

    const handleRowClick = (task: Task) => {
        if (onRowClick) {
            onRowClick(task)
        } else {
            setSelectedTask(task)
            setShowModal(true)
        }
    }
    
    // Reset status filter when view mode changes
    const handleViewModeChange = (mode: ViewMode) => {
        setViewMode(mode)
        setStatusFilter("all")
    }
    
    // Reset date filter
    const handleDateFilterReset = () => {
        setDateFilter("all")
        setCustomRange({ start: null, end: null })
    }

    // Get filter label for subtitle
    const getFilterLabel = () => {
        const labels: string[] = []
        
        if (statusFilter !== "all") {
            labels.push(statusFilter === "overdue" ? "overdue" : statusFilter.replace("_", " "))
        }
        
        if (dateFilter !== "all") {
            if (dateFilter === "custom" && customRange.start && customRange.end) {
                labels.push(`${format(customRange.start, "dd MMM", { locale: idLocale })} - ${format(customRange.end, "dd MMM", { locale: idLocale })}`)
            } else {
                const dateLabels: Record<DateFilterType, string> = {
                    all: "",
                    today: "hari ini",
                    week: "minggu ini",
                    month: "bulan ini",
                    custom: "",
                }
                if (dateLabels[dateFilter]) labels.push(dateLabels[dateFilter])
            }
        }
        
        return labels.length > 0 ? labels.join(", ") : null
    }

    return (
        <>
            <div className="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                {/* Unified Header with Context Switcher */}
                {showHeader && (
                    <div className="px-6 py-5 border-b border-gray-100">
                        <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                            <div>
                                <h2 className="text-base font-semibold text-gray-900">Activity List</h2>
                                <p className="text-sm text-gray-500 mt-0.5">
                                    {getFilterLabel() 
                                        ? `Showing ${getFilterLabel()} tasks`
                                        : viewMode === "my" 
                                            ? "Your personal tasks and activities"
                                            : "All department activities"
                                    }
                                </p>
                            </div>
                            
                            <div className="flex items-center gap-3">
                                {/* Date Filter */}
                                <DateFilter
                                    value={dateFilter}
                                    onChange={setDateFilter}
                                    customRange={customRange}
                                    onCustomRangeChange={setCustomRange}
                                />
                                
                                {/* Context Switcher - Segmented Control */}
                                <div className="flex items-center p-1 bg-gray-100 rounded-lg">
                                    <button
                                        onClick={() => handleViewModeChange("my")}
                                        className={cn(
                                            "flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-md transition-all",
                                            viewMode === "my"
                                                ? "bg-white text-gray-900 shadow-sm"
                                                : "text-gray-600 hover:text-gray-900"
                                        )}
                                    >
                                        <User className="h-3.5 w-3.5" />
                                        My Tasks
                                    </button>
                                    <button
                                        onClick={() => handleViewModeChange("department")}
                                        className={cn(
                                            "flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-md transition-all",
                                            viewMode === "department"
                                                ? "bg-white text-gray-900 shadow-sm"
                                                : "text-gray-600 hover:text-gray-900"
                                        )}
                                    >
                                        <Users className="h-3.5 w-3.5" />
                                        Department
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                )}

                {/* Interactive Stats Cards - Soft Colored with Quiet Default */}
                {showHeader && (
                    <MetricCards 
                        stats={calculatedStats} 
                        filteredCount={filteredTasks.length}
                        activeFilter={statusFilter}
                        onFilterChange={setStatusFilter}
                    />
                )}

                {/* Table with Search Inside */}
                <div className="border-t border-gray-100">
                    <DataTable
                        columns={columns}
                        data={filteredTasks}
                        searchKey="task_title"
                        searchPlaceholder="Search activities..."
                        onRowClick={handleRowClick}
                        pageSize={compact ? 5 : 10}
                        emptyMessage={
                            statusFilter !== "all"
                                ? `No ${getFilterLabel()} tasks found.`
                                : viewMode === "my"
                                    ? "No tasks assigned to you yet."
                                    : "No activities in your department."
                        }
                        rowClassName="group"
                    />
                </div>
            </div>

            <TaskDetailModal
                task={selectedTask}
                open={showModal}
                onClose={() => {
                    setShowModal(false)
                    setSelectedTask(null)
                }}
            />
        </>
    )
}

export default ActivityDataTable

