import * as React from "react"
import FullCalendar from "@fullcalendar/react"
import dayGridPlugin from "@fullcalendar/daygrid"
import timeGridPlugin from "@fullcalendar/timegrid"
import interactionPlugin from "@fullcalendar/interaction"
import listPlugin from "@fullcalendar/list"
import { router, usePage } from "@inertiajs/react"
import { format } from "date-fns"
import {
    Users,
    Info,
} from "lucide-react"
import { cn } from "@/lib/utils"
import { TaskDetailModal } from "./TaskDetailModal"
import CalendarHeader from "./calendar/CalendarHeader"
import CalendarStyles from "./calendar/CalendarStyles"
import type { Task, PageProps } from "@/types"

interface ActivityCalendarProps {
    tasks: Task[]
    onDateClick?: (date: Date) => void
    onEventClick?: (task: Task) => void
    onCreateTask?: (options?: { date?: string }) => void
    onEditTask?: (task: Task) => void
}

type ViewMode = "my" | "department"
type CalendarView = "dayGridMonth" | "timeGridWeek" | "timeGridDay"

// Activity type color mapping - darker shades for better contrast
const activityTypeColors: Record<string, { bg: string; text: string; border: string }> = {
    blue: { bg: "#dbeafe", text: "#1e40af", border: "#3b82f6" },
    indigo: { bg: "#dbeafe", text: "#1e40af", border: "#3b82f6" },
    purple: { bg: "#ede9fe", text: "#5b21b6", border: "#8b5cf6" },
    pink: { bg: "#fce7f3", text: "#9d174d", border: "#ec4899" },
    red: { bg: "#fee2e2", text: "#991b1b", border: "#ef4444" },
    orange: { bg: "#ffedd5", text: "#9a3412", border: "#f97316" },
    amber: { bg: "#fef3c7", text: "#92400e", border: "#f59e0b" },
    yellow: { bg: "#fef9c3", text: "#854d0e", border: "#eab308" },
    lime: { bg: "#ecfccb", text: "#3f6212", border: "#84cc16" },
    green: { bg: "#dcfce7", text: "#166534", border: "#22c55e" },
    emerald: { bg: "#d1fae5", text: "#065f46", border: "#10b981" },
    teal: { bg: "#ccfbf1", text: "#115e59", border: "#14b8a6" },
    cyan: { bg: "#cffafe", text: "#155e75", border: "#06b6d4" },
    gray: { bg: "#f3f4f6", text: "#374151", border: "#6b7280" },
}

// Status styling with better contrast
const statusStyles: Record<string, { dot: string; label: string }> = {
    planned: { dot: "bg-slate-400", label: "Planned" },
    in_progress: { dot: "bg-amber-500", label: "In Progress" },
    completed: { dot: "bg-emerald-500", label: "Completed" },
    cancelled: { dot: "bg-gray-400", label: "Cancelled" },
}

function getActivityColors(type: { color?: string } | undefined) {
    if (!type?.color) return activityTypeColors.gray
    return activityTypeColors[type.color] || activityTypeColors.gray
}

type CalendarParticipant = Task["participants"][number] & {
    avatar_url?: string
}

type CalendarOwner = {
    name: string
    avatar_url?: string
    participantCount: number
}

function getCalendarOwner(task: Task): CalendarOwner {
    const participants = (task.participants || []) as CalendarParticipant[]
    const participant = participants[0]
    const participantUser = participant?.user

    return {
        name:
            participant?.name ||
            participantUser?.name ||
            task.creator?.name ||
            "Owner",
        avatar_url:
            participant?.avatar_url ||
            participantUser?.avatar_url ||
            task.creator?.avatar_url,
        participantCount: participants.length,
    }
}

function getParticipantOwnerInitial(name: string) {
    return name.trim().charAt(0).toUpperCase() || "?"
}

function OwnerBadge({
    owner,
    compact = false,
}: {
    owner: CalendarOwner
    compact?: boolean
}) {
    const ownerInitial = getParticipantOwnerInitial(owner.name)

    return (
        <div
            className={cn(
                "flex shrink-0 items-center gap-1",
                compact && "ml-auto"
            )}
        >
            {owner.avatar_url ? (
                <img
                    src={owner.avatar_url}
                    alt={owner.name}
                    className={cn(
                        "rounded-full object-cover ring-1 ring-white shadow-sm",
                        compact ? "h-5 w-5" : "h-6 w-6"
                    )}
                />
            ) : (
                <div
                    className={cn(
                        "flex items-center justify-center rounded-full bg-white/70 font-semibold text-slate-700 ring-1 ring-white shadow-sm",
                        compact ? "h-5 w-5 text-[10px]" : "h-6 w-6 text-[11px]"
                    )}
                    aria-label={owner.name}
                >
                    {ownerInitial}
                </div>
            )}
        </div>
    )
}

function MonthOwnerBadge({ task }: { task: Task }) {
    const owner = getCalendarOwner(task)
    const remainingParticipants = Math.max(owner.participantCount - 1, 0)

    return (
        <div className="ml-auto flex shrink-0 items-center gap-1">
            {remainingParticipants > 0 && (
                <span className="inline-flex h-5 items-center justify-center rounded-full bg-white/70 px-1 text-[10px] font-semibold text-slate-600 ring-1 ring-white">
                    +{remainingParticipants}
                </span>
            )}
            <OwnerBadge owner={owner} compact />
        </div>
    )
}

function RichOwnerDetails({ task }: { task: Task }) {
    const owner = getCalendarOwner(task)
    const participantCount = owner.participantCount

    return (
        <div className="mt-1 flex items-center justify-between gap-2">
            <div className="flex min-w-0 items-center gap-2">
                <OwnerBadge owner={owner} />
                <div className="min-w-0">
                    <p className="truncate text-[11px] font-semibold text-gray-700">
                        {owner.name}
                    </p>
                    <p className="truncate text-[10px] text-gray-500">
                        {participantCount > 1
                            ? `${participantCount} participants`
                            : "Owner"}
                    </p>
                </div>
            </div>
            {participantCount > 0 && (
                <div className="flex items-center gap-1 text-[10px] font-medium text-gray-600">
                    <Users className="h-3 w-3 text-gray-500" />
                    <span>{participantCount}</span>
                </div>
            )}
        </div>
    )
}

// Custom Event Content with better contrast
function EventContent({ event, view }: { event: any; view: string }) {
    const task = event.extendedProps.task as Task
    const colors = getActivityColors(task.activity_type)
    const isMonthView = view === "dayGridMonth"
    const isOverdue = task.due_date ? (new Date(task.due_date) < new Date() && !["completed", "cancelled"].includes(task.status)) : false

    if (isMonthView) {
        return (
            <div
                className={cn(
                    "flex items-center gap-1.5 px-1.5 py-0.5 text-[11px] rounded cursor-pointer transition-all hover:shadow-sm",
                    "border-l-[3px]"
                )}
                style={{
                    backgroundColor: colors.bg,
                    borderLeftColor: colors.border,
                }}
            >
                <span
                    className={cn(
                        "w-1.5 h-1.5 rounded-full flex-shrink-0",
                        statusStyles[task.status]?.dot || "bg-gray-400"
                    )}
                />
                <span
                    className="min-w-0 flex-1 font-semibold truncate"
                    style={{ color: colors.text }}
                >
                    {event.title}
                </span>
                {isOverdue && (
                    <span className="text-[9px] text-rose-600 font-bold flex-shrink-0">!</span>
                )}
                <MonthOwnerBadge task={task} />
            </div>
        )
    }

    // Week/Day view
    return (
        <div
            className="h-full p-2 text-xs overflow-hidden rounded cursor-pointer border-l-[3px]"
            style={{
                backgroundColor: colors.bg,
                borderLeftColor: colors.border,
            }}
        >
            <div className="flex items-center gap-1 mb-0.5">
                <p className="font-semibold truncate" style={{ color: colors.text }}>
                    {event.title}
                </p>
            </div>
            {task.activity_type && (
                <p className="text-gray-600 truncate text-[10px]">
                    {task.activity_type.name}
                </p>
            )}
            <RichOwnerDetails task={task} />
        </div>
    )
}

export function ActivityCalendar({ tasks, onDateClick, onEventClick, onCreateTask, onEditTask }: ActivityCalendarProps) {
    const calendarRef = React.useRef<FullCalendar>(null)
    const [currentView, setCurrentView] = React.useState<CalendarView>("dayGridMonth")
    const [currentDate, setCurrentDate] = React.useState(new Date())
    const [viewMode, setViewMode] = React.useState<ViewMode>("my")
    const [selectedTask, setSelectedTask] = React.useState<Task | null>(null)
    const [showModal, setShowModal] = React.useState(false)

    // Get current user from page props
    const { auth } = usePage<PageProps>().props
    const currentUserId = auth?.user?.id

    // Handle view mode change - fetch from server with correct scope
    const handleViewModeChange = (mode: ViewMode) => {
        setViewMode(mode)
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

    // Filter tasks - exclude cancelled from calendar display
    const filteredTasks = React.useMemo(() => {
        return tasks.filter((task) => task.status !== "cancelled")
    }, [tasks])

    // Convert tasks to calendar events with status-aware date positioning:
    // - Planned / In Progress: show at task_date (when the work is scheduled)
    // - Completed: show at completed_at date (when the work was actually finished)
    const events = React.useMemo(() => {
        return filteredTasks
            .filter((task) => !!(task.task_date || task.due_date))
            .map((task) => {
            let activityDate: string
            if (task.status === "completed" && task.completed_at) {
                // Completed tasks appear on the day they were finished
                activityDate = task.completed_at.substring(0, 10)
            } else {
                // Planned and In Progress tasks appear on their scheduled date
                activityDate = task.task_date || task.due_date!
            }
            return {
                id: String(task.id),
                title: task.task_title,
                start: activityDate,
                end: activityDate,
                allDay: true,
                extendedProps: {
                    task,
                    status: task.status,
                    activityType: task.activity_type,
                },
            }
        })
    }, [filteredTasks])

    // Navigation handlers
    const handlePrev = () => {
        const api = calendarRef.current?.getApi()
        api?.prev()
        setCurrentDate(api?.getDate() || new Date())
    }

    const handleNext = () => {
        const api = calendarRef.current?.getApi()
        api?.next()
        setCurrentDate(api?.getDate() || new Date())
    }

    const handleToday = () => {
        const api = calendarRef.current?.getApi()
        api?.today()
        setCurrentDate(api?.getDate() || new Date())
    }

    const handleViewChange = (view: CalendarView) => {
        const api = calendarRef.current?.getApi()
        api?.changeView(view)
        setCurrentView(view)
    }

    // Event handlers
    const handleDateClick = (info: any) => {
        const selectedDate = format(info.date, "yyyy-MM-dd")

        if (onCreateTask) {
            onCreateTask({ date: selectedDate })
        } else if (onDateClick) {
            onDateClick(info.date)
        } else {
            router.visit(route("activity.task.index", {
                modal: "create",
                date: selectedDate,
            }))
        }
    }

    const handleEventClick = (info: any) => {
        const task = info.event.extendedProps.task as Task
        if (onEventClick) {
            onEventClick(task)
        } else {
            setSelectedTask(task)
            setShowModal(true)
        }
    }

    // Count tasks by status (exclude cancelled from legend too)
    const statusCounts = React.useMemo(() => {
        const counts: Record<string, number> = {
            planned: 0,
            in_progress: 0,
            completed: 0,
        }
        filteredTasks.forEach((task) => {
            if (counts[task.status] !== undefined) {
                counts[task.status]++
            }
        })
        return counts
    }, [filteredTasks])

    return (
        <>
            <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <CalendarHeader
                    currentDate={currentDate}
                    currentView={currentView}
                    onPrev={handlePrev}
                    onNext={handleNext}
                    onToday={handleToday}
                    onViewChange={handleViewChange}
                    onCreateTask={() => {
                        if (onCreateTask) {
                            onCreateTask()
                            return
                        }

                        router.visit(route("activity.task.index", { modal: "create" }))
                    }}
                />

                {/* Status Legend */}
                <div className="px-5 py-2.5 border-b border-gray-100 bg-gray-50/50 flex items-center justify-between">
                    <div className="flex items-center gap-4 text-xs">
                        {Object.entries(statusCounts).map(([status, count]) => (
                            <div key={status} className="flex items-center gap-1.5">
                                <span
                                    className={cn(
                                        "w-2.5 h-2.5 rounded-full",
                                        statusStyles[status]?.dot || "bg-gray-400"
                                    )}
                                />
                                <span className="text-gray-700 font-medium">
                                    {statusStyles[status]?.label || status}
                                </span>
                                <span className="text-gray-500">({count})</span>
                            </div>
                        ))}
                    </div>
                    <div className="text-xs text-gray-600 font-medium">
                        {filteredTasks.length} task{filteredTasks.length !== 1 ? "s" : ""} total
                    </div>
                </div>

                {/* Calendar */}
                <div className="p-4 calendar-container">
                    <FullCalendar
                        ref={calendarRef}
                        plugins={[dayGridPlugin, timeGridPlugin, interactionPlugin, listPlugin]}
                        initialView={currentView}
                        headerToolbar={false}
                        events={events}
                        selectable={true}
                        selectMirror={true}
                        dayMaxEvents={4}
                        dayMaxEventRows={4}
                        moreLinkClick="popover"
                        weekends={true}
                        dateClick={handleDateClick}
                        eventClick={handleEventClick}
                        eventContent={(arg) => (
                            <EventContent event={arg.event} view={currentView} />
                        )}
                        height="auto"
                        aspectRatio={1.5}
                        eventDisplay="block"
                        moreLinkContent={(args) => (
                            <div className="text-xs font-semibold text-blue-700 hover:text-blue-800 px-1.5 py-0.5 bg-blue-100 rounded cursor-pointer">
                                +{args.num} lainnya
                            </div>
                        )}
                        moreLinkClassNames="fc-more-link-custom"
                        dayCellClassNames={(arg) =>
                            cn(
                                "transition-colors",
                                arg.isToday && "!bg-blue-100",
                                arg.isPast && !arg.isToday && "bg-gray-50/30"
                            )
                        }
                        slotMinTime="06:00:00"
                        slotMaxTime="22:00:00"
                        allDaySlot={true}
                        nowIndicator={true}
                        stickyHeaderDates={true}
                        locale="id"
                        firstDay={0}
                        buttonText={{
                            today: "Hari ini",
                            month: "Bulan",
                            week: "Minggu",
                            day: "Hari",
                        }}
                    />
                </div>

                {/* Footer Help */}
                <div className="px-5 py-3 border-t border-gray-100 bg-gray-50/50">
                    <div className="flex items-center gap-6 text-xs text-gray-600">
                        <span className="flex items-center gap-1.5">
                            <Info className="h-3.5 w-3.5 text-gray-400" />
                            Klik tanggal untuk buat activity
                        </span>
                        <span className="text-gray-300">•</span>
                        <span>Klik event untuk lihat detail</span>
                    </div>
                </div>
            </div>

            {/* Task Detail Modal */}
            <TaskDetailModal
                task={selectedTask}
                open={showModal}
                onClose={() => {
                    setShowModal(false)
                    setSelectedTask(null)
                }}
                onEdit={onEditTask}
            />

            <CalendarStyles />
        </>
    )
}

export default ActivityCalendar
