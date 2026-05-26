import * as React from "react"
import { format, isPast, isToday, isTomorrow, parseISO } from "date-fns"
import { id as idLocale } from "date-fns/locale"
import { router } from "@inertiajs/react"
import { Check } from "lucide-react"

import { cn } from "@/lib/utils"
import { showToast } from "../../ui/toast"
import StatusDropdown from "./StatusDropdown"
import type { Task, TaskStatus } from "@/types"

type ViewMode = "my" | "department"

interface TaskListViewProps {
    tasks: Task[]
    viewMode: ViewMode
    currentUserId?: number
    updatingTaskId: number | null
    showActions: boolean
    onTaskClick: (task: Task) => void
    onUpdatingTaskChange: (taskId: number | null) => void
    onEditTask?: (task: Task) => void
    canEditTask: (task: Task, currentUserId: number | undefined) => boolean
    rowActions: (task: Task, isReadOnly: boolean) => React.ReactNode
}

interface TaskGroup {
    today: Task[]
    upcoming: Task[]
}

function groupTasks(tasks: Task[]): TaskGroup {
    const sorted = [...tasks].sort((a, b) => {
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
            if (isUpcomingTask) upcoming.push(task)
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
}

function formatTaskDue(dueDate: string | null) {
    if (!dueDate) return "No due date"

    const parsedDate = parseISO(dueDate)
    if (isToday(parsedDate)) return "Today"
    if (isTomorrow(parsedDate)) return "Tomorrow"

    return format(parsedDate, "dd MMM", { locale: idLocale })
}

export function TaskListView({
    tasks,
    viewMode,
    currentUserId,
    updatingTaskId,
    showActions,
    onTaskClick,
    onUpdatingTaskChange,
    onEditTask,
    canEditTask,
    rowActions,
}: TaskListViewProps) {
    const groupedListTasks = groupTasks(tasks)

    const handleToggleTaskComplete = (task: Task, isCompleted: boolean) => {
        const isReadOnly = viewMode === "department" && !canEditTask(task, currentUserId)
        if (isReadOnly || updatingTaskId === task.id) return

        const newStatus: TaskStatus = isCompleted ? "planned" : "completed"
        onUpdatingTaskChange(task.id)

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
                onFinish: () => onUpdatingTaskChange(null),
            }
        )
    }

    const renderTaskSection = (title: string, sectionTasks: Task[]) => (
        <section className="mb-6">
            <h3 className="mb-3 text-[15px] font-bold text-slate-800 tracking-tight ml-2">
                {title}
            </h3>

            <div className="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm flex flex-col">
                <div className="min-w-[850px] w-full flex flex-col">
                    <div className="grid grid-cols-[50px_minmax(250px,_3fr)_180px_120px_120px_120px_50px] items-center gap-3 border-b border-slate-100 bg-slate-50 px-4 py-3 text-[11px] font-semibold text-slate-500">
                        <div />
                        <div>Task Name</div>
                        <div>Category</div>
                        <div>Priority</div>
                        <div>Due Date</div>
                        <div>Status</div>
                        <div />
                    </div>

                    {sectionTasks.length === 0 ? (
                        <div className="px-6 py-8 text-sm text-slate-500 text-center">
                            No tasks in this section.
                        </div>
                    ) : (
                        sectionTasks.map((task) => {
                            const isCompleted = task.status === "completed"
                            const isReadOnly = viewMode === "department" && !canEditTask(task, currentUserId)
                            const categoryLabel = task.activity_type?.name || "General"
                            return (
                                <div
                                    key={task.id}
                                    onClick={() => onTaskClick(task)}
                                    className="group grid cursor-pointer grid-cols-[50px_minmax(250px,_3fr)_180px_120px_120px_120px_50px] items-center gap-3 border-b border-slate-100 px-4 py-3.5 last:border-b-0 hover:bg-slate-50/60 transition-colors"
                                >
                                    <div className="flex items-center justify-center">
                                        <button
                                            type="button"
                                            disabled={isReadOnly || updatingTaskId === task.id}
                                            onClick={(event) => {
                                                event.stopPropagation()
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
                                        <p
                                            className={cn(
                                                "truncate text-[14px] font-medium transition-colors duration-200",
                                                isCompleted ? "text-slate-400 line-through" : "text-slate-900"
                                            )}
                                        >
                                            {task.task_title}
                                        </p>
                                    </div>

                                    <div className="min-w-0 pr-4">
                                        <p className="truncate text-[13px] text-slate-500">
                                            {categoryLabel}
                                        </p>
                                    </div>

                                    <div>
                                        <span
                                            className={cn(
                                                "inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-semibold border",
                                                task.priority === "high"
                                                    ? "bg-rose-50 text-rose-600 border-rose-100"
                                                    : task.priority === "medium"
                                                        ? "bg-amber-50 text-amber-600 border-amber-100"
                                                        : "bg-sky-50 text-sky-600 border-sky-100"
                                            )}
                                        >
                                            {task.priority.charAt(0).toUpperCase() + task.priority.slice(1)}
                                        </span>
                                    </div>

                                    <div>
                                        <p className="text-[13px] text-slate-500">
                                            {formatTaskDue(task.due_date)}
                                        </p>
                                    </div>

                                    <div onClick={(event) => event.stopPropagation()}>
                                        <StatusDropdown
                                            task={task}
                                            isReadOnly={isReadOnly}
                                            onEditTask={onEditTask}
                                        />
                                    </div>

                                    <div className="flex justify-end pr-2">
                                        {showActions && rowActions(task, isReadOnly)}
                                    </div>
                                </div>
                            )
                        })
                    )}
                </div>
            </div>
        </section>
    )

    return (
        <>
            {renderTaskSection("Today", groupedListTasks.today)}
            {renderTaskSection("Upcoming", groupedListTasks.upcoming)}
        </>
    )
}

export default TaskListView
