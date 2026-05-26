import * as React from "react"
import { Link, router, usePage } from "@inertiajs/react"
import { ColumnDef } from "@tanstack/react-table"
import {
    format,
    startOfWeek,
    endOfWeek,
    startOfMonth,
    endOfMonth,
} from "date-fns"
import { id as idLocale } from "date-fns/locale"
import {
    MoreHorizontal,
    Eye,
    Edit,
    Trash2,
    AlertTriangle,
    Download,
} from "lucide-react"
import { SortableHeader } from "../ui/data-table"
import { openDownloadInSameTab } from "@/lib/download"
import { cn } from "@/lib/utils"
import { formatDueDate, isOverdue, isWithinDateFilter, type DateFilter } from "@/lib/dateFilters"
import DateFilterControl, { type DateFilterType, type DateRange } from "./datatable/DateFilter"
import { type StatusFilter } from "./datatable/MetricCards"
import StatusDropdown from "./datatable/StatusDropdown"
import TaskListView from "./datatable/TaskListView"
import AvatarStack from "./datatable/AvatarStack"
import TypeBadge from "./datatable/TypeBadge"
import { TaskDetailModal } from "./TaskDetailModal"
import type { Task, PaginatedData, PageProps, TaskStats, TaskFilters } from "@/types"
import { Popover, Transition } from "@headlessui/react"

type ViewMode = "my" | "department"

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

function canEditTask(task: Task, currentUserId: number | undefined): boolean {
    if (!currentUserId) return false
    const isParticipant = task.participants?.some(p =>
        p.user_id === currentUserId || p.id === currentUserId
    )
    const isCreator = task.created_by === currentUserId
    return isParticipant || isCreator
}

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
                                <DateFilterControl
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
                    <TaskListView
                        tasks={filteredTasks}
                        viewMode={viewMode}
                        currentUserId={currentUserId}
                        updatingTaskId={updatingTaskId}
                        showActions={showActions}
                        onTaskClick={handleRowClick}
                        onUpdatingTaskChange={setUpdatingTaskId}
                        onEditTask={onEditTask}
                        canEditTask={canEditTask}
                        rowActions={(task, isReadOnly) => (
                            <div
                                className="opacity-0 group-hover:opacity-100 transition-opacity"
                                onClick={(event) => event.stopPropagation()}
                            >
                                <RowActions
                                    task={task}
                                    visible={true}
                                    isReadOnly={isReadOnly}
                                    onEditTask={onEditTask}
                                />
                            </div>
                        )}
                    />

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
