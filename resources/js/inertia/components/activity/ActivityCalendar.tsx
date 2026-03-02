import * as React from "react"
import FullCalendar from "@fullcalendar/react"
import dayGridPlugin from "@fullcalendar/daygrid"
import timeGridPlugin from "@fullcalendar/timegrid"
import interactionPlugin from "@fullcalendar/interaction"
import listPlugin from "@fullcalendar/list"
import { router, usePage } from "@inertiajs/react"
import { format } from "date-fns"
import { id as idLocale } from "date-fns/locale"
import {
    ChevronLeft,
    ChevronRight,
    Plus,
    User,
    Users,
    Info,
} from "lucide-react"
import { cn } from "@/lib/utils"
import { Button } from "../ui/button"
import { showToast } from "../ui/toast"
import { TaskDetailModal } from "./TaskDetailModal"
import type { Task, PageProps } from "@/types"

interface ActivityCalendarProps {
    tasks: Task[]
    onDateClick?: (date: Date) => void
    onEventClick?: (task: Task) => void
    onCreateTask?: (options?: { date?: string }) => void
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
                    "flex items-center gap-1 px-1.5 py-0.5 text-[11px] rounded cursor-pointer transition-all hover:shadow-sm",
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
                    className="font-semibold truncate"
                    style={{ color: colors.text }}
                >
                    {event.title}
                </span>
                {isOverdue && (
                    <span className="text-[9px] text-rose-600 font-bold ml-auto flex-shrink-0">!</span>
                )}
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
            {task.participants && task.participants.length > 0 && (
                <div className="flex items-center gap-1 mt-1">
                    <Users className="h-3 w-3 text-gray-500" />
                    <span className="text-[10px] text-gray-600 font-medium">
                        {task.participants.length}
                    </span>
                </div>
            )}
        </div>
    )
}

export function ActivityCalendar({ tasks, onDateClick, onEventClick, onCreateTask }: ActivityCalendarProps) {
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

    // Convert tasks to calendar events using task_date (activity date)
    // Calendar shows "what the team is doing on each day", not deadlines
    const events = React.useMemo(() => {
        return filteredTasks
            .filter((task) => !!(task.task_date || task.due_date))
            .map((task) => {
            const activityDate = task.task_date || task.due_date!
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
            router.visit(route("activity.task.create"), {
                data: { date: selectedDate },
            })
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
                {/* Header */}
                <div className="px-5 py-4 border-b border-gray-100">
                    <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        {/* Left: Navigation & Title */}
                        <div className="flex items-center gap-4">
                            <div className="flex items-center gap-1">
                                <button
                                    onClick={handlePrev}
                                    className="p-2 rounded-lg text-gray-500 hover:text-gray-700 hover:bg-gray-100 transition-colors"
                                >
                                    <ChevronLeft className="h-5 w-5" />
                                </button>
                                <button
                                    onClick={handleToday}
                                    className="px-3 py-1.5 text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition-colors"
                                >
                                    Today
                                </button>
                                <button
                                    onClick={handleNext}
                                    className="p-2 rounded-lg text-gray-500 hover:text-gray-700 hover:bg-gray-100 transition-colors"
                                >
                                    <ChevronRight className="h-5 w-5" />
                                </button>
                            </div>
                            <h2 className="text-lg font-semibold text-gray-900">
                                {format(
                                    currentDate,
                                    currentView === "dayGridMonth" ? "MMMM yyyy" : "d MMMM yyyy",
                                    { locale: idLocale }
                                )}
                            </h2>
                        </div>

                        {/* Right: View Mode Toggle & Calendar View */}
                        <div className="flex items-center gap-3">

                            {/* Calendar View Switcher */}
                            <div className="flex items-center border border-gray-200 rounded-lg overflow-hidden">
                                <button
                                    onClick={() => handleViewChange("dayGridMonth")}
                                    className={cn(
                                        "px-3 py-1.5 text-sm font-medium transition-colors",
                                        currentView === "dayGridMonth"
                                            ? "bg-primary text-white"
                                            : "bg-white text-gray-600 hover:bg-gray-50"
                                    )}
                                >
                                    Month
                                </button>
                                <button
                                    onClick={() => handleViewChange("timeGridWeek")}
                                    className={cn(
                                        "px-3 py-1.5 text-sm font-medium transition-colors border-x border-gray-200",
                                        currentView === "timeGridWeek"
                                            ? "bg-primary text-white"
                                            : "bg-white text-gray-600 hover:bg-gray-50"
                                    )}
                                >
                                    Week
                                </button>
                                <button
                                    onClick={() => handleViewChange("timeGridDay")}
                                    className={cn(
                                        "px-3 py-1.5 text-sm font-medium transition-colors",
                                        currentView === "timeGridDay"
                                            ? "bg-primary text-white"
                                            : "bg-white text-gray-600 hover:bg-gray-50"
                                    )}
                                >
                                    Day
                                </button>
                            </div>

                            {/* Add Button */}
                            <Button
                                variant="primary"
                                size="sm"
                                onClick={() => {
                                    if (onCreateTask) {
                                        onCreateTask()
                                        return
                                    }

                                    router.visit(route("activity.task.create"))
                                }}
                                className="bg-primary hover:bg-blue-600"
                            >
                                <Plus className="h-4 w-4 mr-1" />
                                Add
                            </Button>
                        </div>
                    </div>
                </div>

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
            />

            {/* Custom styles for FullCalendar */}
            <style>{`
                .calendar-container .fc {
                    font-family: inherit;
                }
                .calendar-container .fc-theme-standard td,
                .calendar-container .fc-theme-standard th {
                    border-color: #e5e7eb;
                }
                .calendar-container .fc-col-header-cell {
                    padding: 12px 0;
                    background: #f9fafb;
                }
                .calendar-container .fc-col-header-cell-cushion {
                    font-weight: 600;
                    color: #374151;
                    text-transform: uppercase;
                    font-size: 11px;
                    letter-spacing: 0.05em;
                }
                .calendar-container .fc-daygrid-day-number {
                    font-weight: 500;
                    color: #6b7280;
                    padding: 8px;
                }
                .calendar-container .fc-daygrid-day.fc-day-today .fc-daygrid-day-number {
                    background: #2563eb;
                    color: white;
                    border-radius: 9999px;
                    width: 28px;
                    height: 28px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }
                .calendar-container .fc-daygrid-day-events {
                    padding: 2px 4px;
                }
                .calendar-container .fc-event {
                    border: none !important;
                    background: transparent !important;
                    margin-bottom: 2px;
                }
                .calendar-container .fc-event-main {
                    padding: 0;
                }
                .calendar-container .fc-daygrid-event-harness {
                    margin-top: 1px;
                }
                .calendar-container .fc-popover {
                    border-radius: 12px;
                    box-shadow: 0 10px 40px rgba(0,0,0,0.15);
                    border: 1px solid #e5e7eb;
                    overflow: hidden;
                }
                .calendar-container .fc-popover-header {
                    background: #f9fafb;
                    padding: 10px 12px;
                    font-weight: 600;
                    color: #374151;
                }
                .calendar-container .fc-popover-body {
                    padding: 8px;
                    max-height: 300px;
                    overflow-y: auto;
                }
                .calendar-container .fc-more-link {
                    margin-top: 2px;
                }
                .calendar-container .fc-daygrid-more-link {
                    background: transparent !important;
                }
                .calendar-container .fc-highlight {
                    background: #dbeafe !important;
                }
            `}</style>
        </>
    )
}

export default ActivityCalendar
