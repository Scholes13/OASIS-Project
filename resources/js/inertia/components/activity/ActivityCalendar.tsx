import * as React from "react"
import FullCalendar from "@fullcalendar/react"
import dayGridPlugin from "@fullcalendar/daygrid"
import timeGridPlugin from "@fullcalendar/timegrid"
import interactionPlugin from "@fullcalendar/interaction"
import listPlugin from "@fullcalendar/list"
import { router, usePage } from "@inertiajs/react"
import { format } from "date-fns"
import { Info } from "lucide-react"
import { cn } from "@/lib/utils"
import { TaskDetailModal } from "./TaskDetailModal"
import CalendarHeader from "./calendar/CalendarHeader"
import CalendarEventRenderer, { statusStyles } from "./calendar/CalendarEventRenderer"
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
                            <CalendarEventRenderer event={arg.event} view={currentView} />
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
