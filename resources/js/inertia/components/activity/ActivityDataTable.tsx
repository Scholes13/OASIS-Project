import * as React from "react"
import { Link, router, usePage } from "@inertiajs/react"
import { ColumnDef } from "@tanstack/react-table"
import {
    format,
    isPast,
    isToday,
    isTomorrow,
    isThisWeek,
    isThisMonth,
    startOfDay,
    endOfDay,
    startOfWeek,
    endOfWeek,
    startOfMonth,
    endOfMonth,
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
    Check,
    CheckCircle2,
    PlayCircle,
    ListTodo,
    Calendar,
    X,
    Download,
} from "lucide-react"
import { DataTable, SortableHeader } from "../ui/data-table"
import { openDownloadInSameTab } from "@/lib/download"
import { cn } from "@/lib/utils"
import { ACTIVITY_STATUS_CONFIG, ACTIVITY_TYPE_COLORS, AVATAR_COLORS } from "@/lib/activityConstants"
import { formatDueDate, isOverdue, isWithinDateFilter, type DateFilter } from "@/lib/dateFilters"
import { TaskDetailModal } from "./TaskDetailModal"
import { showToast } from "../ui/toast"
import { handleExecutionTimeGuidance } from "./quick-status-guidance"
import type { Task, PaginatedData, PageProps, TaskStatus, TaskStats, TaskFilters } from "@/types"
import { Popover, Transition, Portal } from "@headlessui/react"

type ViewMode = "my" | "department"
type DateFilterType = "all" | "today" | "week" | "month" | "custom"

interface DateRange {
    start: Date | null
    end: Date | null
}

interface ActivityDataTableProps {
    tasks: PaginatedData<Task>
    stats?: TaskStats
    filters?: TaskFilters & { scope?: string }
    onRowClick?: (task: Task) => void
    onEditTask?: (task: Task) => void
    showActions?: boolean
    compact?: boolean
    showHeader?: boolean
}

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

function canEditTask(task: Task, currentUserId: number | undefined): boolean {
    if (!currentUserId) return false
    const isParticipant = task.participants?.some(p =>
        p.user_id === currentUserId || p.id === currentUserId
    )
    const isCreator = task.created_by === currentUserId
    return isParticipant || isCreator
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
                                            className="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
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
        bg: "bg-blue-500",
        border: "border-primary",
        hoverBorder: "hover:border-primary",
        text: "text-primary",
        icon: "text-primary",
        ring: "ring-primary",
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

const statusConfig = ACTIVITY_STATUS_CONFIG

function StatusDropdown({ task, isReadOnly = false, onEditTask }: { task: Task; isReadOnly?: boolean; onEditTask?: (task: Task) => void }) {
    const [isUpdating, setIsUpdating] = React.useState(false)
    const buttonRef = React.useRef<HTMLButtonElement>(null)
    const [panelPosition, setPanelPosition] = React.useState({ top: 0, left: 0, openUpward: false })
    const current = statusConfig[task.status] || statusConfig.planned

    const handleStatusChange = (newStatus: TaskStatus, close: () => void) => {
        if (newStatus === task.status) {
            close()
            return
        }

        setIsUpdating(true)
        router.put(
            route("activity.task.update", { task: task.id }),
            { status: newStatus },
            {
                preserveScroll: true,
                preserveState: false,
                only: ['tasks', 'stats', 'filters'],
                onSuccess: () => {
                    showToast.success("Status updated", `${task.task_title} → ${statusConfig[newStatus]?.label}`)
                    close()
                },
                onError: (errors) => {
                    if (!handleExecutionTimeGuidance(task, errors, onEditTask, close)) {
                        showToast.error("Failed to update status")
                    }
                },
                onFinish: () => setIsUpdating(false),
            }
        )
    }

    const calculatePosition = () => {
        if (buttonRef.current) {
            const rect = buttonRef.current.getBoundingClientRect()
            const spaceBelow = window.innerHeight - rect.bottom
            const openUpward = spaceBelow < 200

            setPanelPosition({
                top: openUpward ? rect.top - 6 : rect.bottom + 6,
                left: rect.left,
                openUpward,
            })
        }
    }

    // Read-only: just show status badge without dropdown
    if (isReadOnly) {
        return (
            <div
                className={cn(
                    "inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-md text-[12px] font-medium whitespace-nowrap ring-1 ring-inset opacity-75",
                    current.bg, current.text, current.ring
                )}
            >
                {current.icon}
                {current.label}
            </div>
        )
    }

    return (
        <Popover className="relative">
            {({ close, open }) => (
                <>
                    <Popover.Button
                        ref={buttonRef}
                        className={cn(
                            "inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-md text-[12px] font-medium whitespace-nowrap ring-1 ring-inset transition-all",
                            current.bg, current.text, current.ring,
                            "hover:ring-2",
                            isUpdating && "opacity-50 cursor-wait"
                        )}
                        onClick={(e) => {
                            e.stopPropagation()
                            calculatePosition()
                        }}
                        disabled={isUpdating}
                    >
                        {current.icon}
                        {current.label}
                        <ChevronDown className="h-3.5 w-3.5 opacity-50" />
                    </Popover.Button>
                    <Portal>
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
                                static
                                className="fixed z-[9999] w-40 bg-white rounded-lg shadow-lg ring-1 ring-gray-200 py-1"
                                style={{
                                    top: panelPosition.openUpward ? 'auto' : panelPosition.top,
                                    bottom: panelPosition.openUpward ? (window.innerHeight - panelPosition.top) : 'auto',
                                    left: panelPosition.left,
                                }}
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
                                            <CheckCircle2 className="h-3.5 w-3.5 ml-auto text-primary" />
                                        )}
                                    </button>
                                ))}
                            </Popover.Panel>
                        </Transition>
                    </Portal>
                </>
            )}
        </Popover>
    )
}

// ============================================================================
// AVATAR STACK - Overlapping Avatars
// ============================================================================

const avatarColors = AVATAR_COLORS

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

const typeColors = ACTIVITY_TYPE_COLORS

function TypeBadge({ name, color }: { name: string; color?: string }) {
    const colorClass = typeColors[color || "gray"] || typeColors.gray
    return (
        <span className={cn(
            "inline-flex items-center px-2.5 py-1 rounded-md text-sm font-medium ring-1 ring-inset",
            colorClass.bg, colorClass.text
        )}>
            {name}
        </span>
    )
}

// ============================================================================
// ROW ACTION MENU - Hidden by Default
// ============================================================================

function RowActions({ task, visible, isReadOnly = false, onEditTask }: { task: Task; visible: boolean; isReadOnly?: boolean; onEditTask?: (task: Task) => void }) {
    // Read-only: only show View action
    if (isReadOnly) {
        return (
            <Link
                href={route("activity.task.index", { task: task.id, modal: "detail" })}
                className={cn(
                    "p-1.5 rounded-md hover:bg-gray-100 transition-all",
                    visible ? "opacity-100" : "opacity-0"
                )}
                onClick={(e) => e.stopPropagation()}
            >
                <Eye className="h-4 w-4 text-gray-400" strokeWidth={1.5} />
            </Link>
        )
    }

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
                        href={route("activity.task.index", { task: task.id, modal: "detail" })}
                        className="flex items-center px-3 py-1.5 text-xs text-gray-700 hover:bg-gray-50"
                    >
                        <Eye className="mr-2 h-3.5 w-3.5" strokeWidth={1.5} />
                        View
                    </Link>
                    {onEditTask ? (
                        <button
                            onClick={(e) => {
                                e.stopPropagation()
                                onEditTask(task)
                            }}
                            className="flex w-full items-center px-3 py-1.5 text-xs text-gray-700 hover:bg-gray-50"
                        >
                            <Edit className="mr-2 h-3.5 w-3.5" strokeWidth={1.5} />
                            Edit
                        </button>
                    ) : (
                        <Link
                            href={route("activity.task.index", { task: task.id, modal: "edit" })}
                            className="flex items-center px-3 py-1.5 text-xs text-gray-700 hover:bg-gray-50"
                        >
                            <Edit className="mr-2 h-3.5 w-3.5" strokeWidth={1.5} />
                            Edit
                        </Link>
                    )}
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

interface ColumnMeta {
    viewMode: ViewMode
    currentUserId: number | undefined
    hasMultipleDepartments: boolean
}

const createColumns = (showActions: boolean): ColumnDef<Task>[] => {
    const columns: ColumnDef<Task>[] = [
        {
            accessorKey: "task_title",
            header: ({ column }) => <SortableHeader column={column} title="Activity" />,
            cell: ({ row, table }) => {
                const task = row.original
                const overdue = isOverdue(task.due_date, task.status === "completed" || task.status === "cancelled" ? task.updated_at : null)
                const meta = table.options.meta as ColumnMeta | undefined
                const showDeptBadge = meta?.hasMultipleDepartments && task.department?.code
                return (
                    <div className="min-w-[200px] max-w-[320px]">
                        <div className="flex items-center gap-2">
                            <p className="font-medium text-gray-900 truncate text-base">{task.task_title}</p>
                            {showDeptBadge && (
                                <span
                                    className="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-gray-100 text-gray-600 flex-shrink-0"
                                    title={task.department.name}
                                >
                                    {task.department.code}
                                </span>
                            )}
                        </div>
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
                const overdue = isOverdue(task.due_date, task.status === "completed" || task.status === "cancelled" ? task.updated_at : null)
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
            cell: ({ row, table }) => {
                const meta = table.options.meta as ColumnMeta | undefined
                const isReadOnly = meta?.viewMode === "department" && !canEditTask(row.original, meta?.currentUserId)
                return <StatusDropdown task={row.original} isReadOnly={isReadOnly} />
            },
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
                const meta = table.options.meta as ColumnMeta | undefined
                const isReadOnly = meta?.viewMode === "department" && !canEditTask(row.original, meta?.currentUserId)
                return (
                    <div className="opacity-0 group-hover:opacity-100 transition-opacity">
                        <RowActions task={row.original} visible={true} isReadOnly={isReadOnly} />
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
    filters,
    onRowClick,
    onEditTask,
    showActions = true,
    compact = false,
    showHeader = true,
}: ActivityDataTableProps) {
    // Sync viewMode with backend scope filter
    const initialScope = (filters?.scope as ViewMode) || "my"
    const [viewMode, setViewMode] = React.useState<ViewMode>(initialScope)
    const [statusFilter, setStatusFilter] = React.useState<StatusFilter>("all")
    const [dateFilter, setDateFilter] = React.useState<DateFilterType>("all")
    const [customRange, setCustomRange] = React.useState<DateRange>({ start: null, end: null })
    const [selectedTask, setSelectedTask] = React.useState<Task | null>(null)
    const [showModal, setShowModal] = React.useState(false)
    const [updatingTaskId, setUpdatingTaskId] = React.useState<number | null>(null)

    // Sync viewMode when filters.scope changes from backend
    React.useEffect(() => {
        if (filters?.scope) {
            setViewMode(filters.scope as ViewMode)
        }
    }, [filters?.scope])

    const { auth, availableDepartments } = usePage<PageProps>().props
    const currentUserId = auth?.user?.id
    const hasMultipleDepartments = (availableDepartments as any[])?.length > 1
    const taskData = tasks?.data ?? []

    // Filter tasks by status and date only (viewMode is now handled by backend)
    const filteredTasks = React.useMemo(() => {
        let filtered = taskData.filter((task) => task.status !== "cancelled")

        // Filter by date range
        if (dateFilter !== "all") {
            filtered = filtered.filter((task) =>
                isWithinDateFilter(task.due_date, dateFilter === "custom" ? { from: customRange.start?.toISOString() || '', to: customRange.end?.toISOString() || '' } : dateFilter as DateFilter)
            )
        }

        // Filter by status (from stats cards)
        if (statusFilter !== "all") {
            if (statusFilter === "overdue") {
                filtered = filtered.filter((task) => isOverdue(task.due_date, task.status === "completed" || task.status === "cancelled" ? task.updated_at : null))
            } else {
                filtered = filtered.filter((task) => task.status === statusFilter)
            }
        }

        return filtered
    }, [taskData, statusFilter, dateFilter, customRange])

    const handleRowClick = (task: Task) => {
        if (onRowClick) {
            onRowClick(task)
        } else {
            setSelectedTask(task)
            setShowModal(true)
        }
    }

    // Handle view mode change - send to backend for server-side filtering
    const handleViewModeChange = (mode: ViewMode) => {
        setViewMode(mode)
        setStatusFilter("all")

        // Send scope to backend for correct pagination
        router.get(
            route('activity.task.index'),
            { scope: mode },
            {
                preserveState: true,
                preserveScroll: true,
                only: ['stats', 'tasks', 'filters'],
            }
        )
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

    const priorityBadgeClass: Record<string, string> = {
        high: "bg-red-100 text-red-600",
        medium: "bg-amber-100 text-amber-600",
        low: "bg-sky-100 text-sky-600",
    }

    const statusBadgeConfig: Record<string, { label: string; className: string }> = {
        planned: { label: "To Do", className: "bg-slate-100 text-slate-600" },
        in_progress: { label: "In Progress", className: "bg-[#e8f3ff] text-primary" },
        completed: { label: "Done", className: "bg-emerald-100 text-emerald-700" },
        cancelled: { label: "Cancelled", className: "bg-gray-100 text-gray-500" },
    }

    const groupedListTasks = React.useMemo(() => {
        const sorted = [...filteredTasks].sort((a, b) => {
            if (!a.due_date && !b.due_date) return a.task_title.localeCompare(b.task_title)
            if (!a.due_date) return 1
            if (!b.due_date) return -1
            return parseISO(a.due_date).getTime() - parseISO(b.due_date).getTime()
        })

        const today: Task[] = []
        const upcoming: Task[] = []

        sorted.forEach((task) => {
            const isUpcomingTask = task.status === "planned"

            if (!task.due_date) {
                if (isUpcomingTask) {
                    upcoming.push(task)
                }
                return
            }

            const dueDate = parseISO(task.due_date)
            const isLate = isPast(dueDate) && !isToday(dueDate) && task.status !== "completed"

            if (isToday(dueDate) || isLate) {
                today.push(task)
            } else if (isUpcomingTask) {
                upcoming.push(task)
            }
        })

        return { today, upcoming }
    }, [filteredTasks])

    const formatTaskDue = (dueDate: string | null) => {
        if (!dueDate) return "No due date"
        const parsedDate = parseISO(dueDate)
        if (isToday(parsedDate)) return "Today"
        if (isTomorrow(parsedDate)) return "Tomorrow"
        return format(parsedDate, "dd MMM", { locale: idLocale })
    }

    const handleToggleTaskComplete = (task: Task, isCompleted: boolean) => {
        const isReadOnly = viewMode === "department" && !canEditTask(task, currentUserId)
        if (isReadOnly || updatingTaskId === task.id) return

        const newStatus: TaskStatus = isCompleted ? "planned" : "completed"
        setUpdatingTaskId(task.id)

        router.put(
            route("activity.task.update", { task: task.id }),
            { status: newStatus },
            {
                preserveScroll: true,
                preserveState: true,
                only: ["tasks", "stats", "filters"],
                onSuccess: () => {
                    showToast.success(
                        "Task updated",
                        `${task.task_title} marked as ${newStatus === "completed" ? "done" : "to do"}`
                    )
                },
                onError: () => showToast.error("Failed to update task status"),
                onFinish: () => setUpdatingTaskId(null),
            }
        )
    }

    const renderTaskSection = (title: string, sectionTasks: Task[]) => (
        <section className="mb-6">
            <h3 className="mb-3 text-[15px] font-bold text-slate-800 tracking-tight ml-2">{title}</h3>

            <div className="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm flex flex-col">
                <div className="min-w-[850px] w-full flex flex-col">
                    {/* Native Grid Table Header */}
                    <div className="grid grid-cols-[50px_minmax(250px,_3fr)_180px_120px_120px_120px_50px] items-center gap-3 border-b border-slate-100 bg-slate-50 px-4 py-3 text-[11px] font-semibold text-slate-500">
                        <div></div>
                        <div>Task Name</div>
                        <div>Category</div>
                        <div>Priority</div>
                        <div>Due Date</div>
                        <div>Status</div>
                        <div></div>
                    </div>

                    {/* Table Body */}
                    {sectionTasks.length === 0 ? (
                        <div className="px-6 py-8 text-sm text-slate-500 text-center">No tasks in this section.</div>
                    ) : (
                        sectionTasks.map((task) => {
                            const isCompleted = task.status === "completed"
                            const isReadOnly = viewMode === "department" && !canEditTask(task, currentUserId)
                            const categoryLabel = task.activity_type?.name || "General"
                            const statusBadge = statusBadgeConfig[task.status] || statusBadgeConfig.planned

                            return (
                                <div
                                    key={task.id}
                                    onClick={() => handleRowClick(task)}
                                    className="group grid cursor-pointer grid-cols-[50px_minmax(250px,_3fr)_180px_120px_120px_120px_50px] items-center gap-3 border-b border-slate-100 px-4 py-3.5 last:border-b-0 hover:bg-slate-50/60 transition-colors"
                                >
                                    <div className="flex items-center justify-center">
                                        <button
                                            type="button"
                                            disabled={isReadOnly || updatingTaskId === task.id}
                                            onClick={(e) => {
                                                e.stopPropagation()
                                                handleToggleTaskComplete(task, isCompleted)
                                            }}
                                            className={cn(
                                                "inline-flex h-[18px] w-[18px] items-center justify-center rounded-[4px] border transition-colors shadow-sm",
                                                isCompleted
                                                    ? "border-[#16599c] bg-[#16599c] text-white"
                                                    : "border-slate-300 hover:border-[#16599c] text-transparent bg-white",
                                                (isReadOnly || updatingTaskId === task.id) && "cursor-not-allowed opacity-50 hover:border-slate-300"
                                            )}
                                        >
                                            <Check className="h-3.5 w-3.5" strokeWidth={3} />
                                        </button>
                                    </div>

                                    <div className="min-w-0 pr-4">
                                        <p className={cn(
                                            "truncate text-[14px] font-medium transition-colors duration-200",
                                            isCompleted ? "text-slate-400 line-through" : "text-slate-900"
                                        )}>
                                            {task.task_title}
                                        </p>
                                    </div>

                                    <div className="min-w-0 pr-4">
                                        <p className="truncate text-[13px] text-slate-500">{categoryLabel}</p>
                                    </div>

                                    <div>
                                        <span className={cn("inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-semibold border", 
                                            task.priority === 'high' ? "bg-rose-50 text-rose-600 border-rose-100" :
                                            task.priority === 'medium' ? "bg-amber-50 text-amber-600 border-amber-100" :
                                            "bg-sky-50 text-sky-600 border-sky-100"
                                        )}>
                                            {task.priority.charAt(0).toUpperCase() + task.priority.slice(1)}
                                        </span>
                                    </div>

                                    <div>
                                        <p className="text-[13px] text-slate-500">{formatTaskDue(task.due_date)}</p>
                                    </div>

                                    <div onClick={(e) => e.stopPropagation()}>
                                        <StatusDropdown task={task} isReadOnly={isReadOnly} onEditTask={onEditTask} />
                                    </div>

                                    <div className="flex justify-end pr-2">
                                        {showActions && (
                                            <div
                                                className="opacity-0 group-hover:opacity-100 transition-opacity"
                                                onClick={(e) => e.stopPropagation()}
                                            >
                                                <RowActions task={task} visible={true} isReadOnly={isReadOnly} onEditTask={onEditTask} />
                                            </div>
                                        )}
                                    </div>
                                </div>
                            );
                        })
                    )}
                </div>
            </div>
        </section>
    );

    return (
        <>
            <div className={cn(
                "overflow-visible",
                !compact && "rounded-xl bg-white border border-border shadow-[0_8px_24px_rgba(15,53,85,0.06)]"
            )}>
                {/* Unified Header with Context Switcher */}
                {showHeader && (
                    <div className="border-b border-border px-6 py-5">
                        <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                            <div>
                                <h2 className="text-base font-semibold text-foreground">Activity List</h2>
                                <p className="mt-0.5 text-sm text-muted-foreground">
                                    {getFilterLabel()
                                        ? `Showing ${getFilterLabel()} tasks`
                                        : viewMode === "my"
                                            ? "Your personal tasks and activities"
                                            : "All department activities"
                                    }
                                </p>
                            </div>

                            <div className="flex items-center gap-3">
                                {/* Export Button */}
                                <button
                                    type="button"
                                    onClick={() => openDownloadInSameTab(route('activity.task.export', {
                                        scope: viewMode, // 'my' or 'department'
                                        member_user_id: viewMode === 'department' && filters?.member_user_id
                                            ? filters.member_user_id
                                            : undefined,
                                        date_from: dateFilter === 'custom' && customRange.start
                                            ? format(customRange.start, 'yyyy-MM-dd')
                                            : dateFilter === 'today' ? format(new Date(), 'yyyy-MM-dd')
                                                : dateFilter === 'week' ? format(startOfWeek(new Date(), { weekStartsOn: 1 }), 'yyyy-MM-dd')
                                                    : dateFilter === 'month' ? format(startOfMonth(new Date()), 'yyyy-MM-dd')
                                                        : undefined,
                                        date_to: dateFilter === 'custom' && customRange.end
                                            ? format(customRange.end, 'yyyy-MM-dd')
                                            : dateFilter === 'today' ? format(new Date(), 'yyyy-MM-dd')
                                                : dateFilter === 'week' ? format(endOfWeek(new Date(), { weekStartsOn: 1 }), 'yyyy-MM-dd')
                                                    : dateFilter === 'month' ? format(endOfMonth(new Date()), 'yyyy-MM-dd')
                                                        : undefined,
                                        status: statusFilter !== 'all' ? statusFilter : undefined,
                                    }))}
                                    className="inline-flex items-center gap-1.5 rounded-lg border border-[color:rgba(24,98,167,0.22)] bg-[color:rgba(24,98,167,0.1)] px-3 py-2 text-sm font-medium text-primary transition-colors hover:bg-[color:rgba(24,98,167,0.16)]"
                                >
                                    <Download className="h-4 w-4" strokeWidth={1.5} />
                                    Export XLSX
                                </button>

                                {/* Date Filter */}
                                <DateFilter
                                    value={dateFilter}
                                    onChange={setDateFilter}
                                    customRange={customRange}
                                    onCustomRangeChange={setCustomRange}
                                />

                            </div>
                        </div>
                    </div>
                )}

                {/* List Section - grouped by due date context */}
                <div className={cn("space-y-8", showHeader && "border-t border-border p-6")}>
                    {renderTaskSection("Today", groupedListTasks.today)}
                    {renderTaskSection("Upcoming", groupedListTasks.upcoming)}

                    {tasks?.meta?.last_page > 1 && (
                        <div className="flex flex-col gap-3 border-t border-border pt-4 sm:flex-row sm:items-center sm:justify-between">
                            <p className="text-sm text-muted-foreground">
                                Showing {tasks.meta.from ?? 0} - {tasks.meta.to ?? 0} of {tasks.meta.total} tasks
                            </p>

                            <div className="flex items-center gap-2">
                                <button
                                    type="button"
                                    disabled={!tasks.links.prev}
                                    onClick={() => {
                                        if (tasks.links.prev) {
                                            router.visit(tasks.links.prev, {
                                                preserveState: true,
                                                preserveScroll: true,
                                                only: ["tasks", "stats", "filters"],
                                            })
                                        }
                                    }}
                                    className={cn(
                                        "rounded-md border px-3 py-1.5 text-sm",
                                        tasks.links.prev
                                            ? "border-border bg-background text-foreground hover:bg-secondary"
                                            : "cursor-not-allowed border-border bg-slate-50 text-slate-400"
                                    )}
                                >
                                    Previous
                                </button>

                                <button
                                    type="button"
                                    disabled={!tasks.links.next}
                                    onClick={() => {
                                        if (tasks.links.next) {
                                            router.visit(tasks.links.next, {
                                                preserveState: true,
                                                preserveScroll: true,
                                                only: ["tasks", "stats", "filters"],
                                            })
                                        }
                                    }}
                                    className={cn(
                                        "rounded-md border px-3 py-1.5 text-sm",
                                        tasks.links.next
                                            ? "border-border bg-background text-foreground hover:bg-secondary"
                                            : "cursor-not-allowed border-border bg-slate-50 text-slate-400"
                                    )}
                                >
                                    Next
                                </button>
                            </div>
                        </div>
                    )}
                </div>
            </div>

            <TaskDetailModal
                task={selectedTask}
                open={showModal}
                onClose={() => {
                    setShowModal(false)
                    setSelectedTask(null)
                }}
                onEdit={onEditTask}
            />
        </>
    )
}

export default ActivityDataTable
