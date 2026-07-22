import * as React from "react"
import { router, usePage } from "@inertiajs/react"
import {
  Calendar,
  Info,
} from "lucide-react"
import { cn } from "@/lib/utils"
import { formatDateWib, getDatePart, getWibDateDiffInDays, isOverdueWib, isPastWibDate, isTodayWib } from "@/lib/activityDateTime"
import { Button } from "../ui/button"
import { TaskDetailModal } from "./TaskDetailModal"
import TimelineItem from "./timeline/TimelineItem"
import type { Task, PageProps } from "@/types"

type ViewMode = "my" | "department"

interface ActivityTimelineProps {
  tasks: Task[]
  onTaskClick?: (task: Task) => void
  onCreateTask?: () => void
  onEditTask?: (task: Task) => void
  showDateHeaders?: boolean
  expandable?: boolean
}

// Status styling
const statusStyles: Record<string, { dot: string; label: string }> = {
  planned: { dot: "bg-slate-400", label: "Planned" },
  in_progress: { dot: "bg-amber-500", label: "In Progress" },
  completed: { dot: "bg-emerald-500", label: "Completed" },
}

// Group tasks by date
function groupTasksByDate(tasks: Task[]): Map<string, Task[]> {
  const grouped = new Map<string, Task[]>()
  
  // Separate tasks with and without due_date
  const tasksWithDate = tasks.filter((t): t is Task & { due_date: string } => !!t.due_date)
  const tasksWithoutDate = tasks.filter(t => !t.due_date)
  
  const sortedTasks = [...tasksWithDate].sort((a, b) => b.due_date.localeCompare(a.due_date))

  sortedTasks.forEach((task) => {
    const dateKey = getDatePart(task.due_date)
    if (!grouped.has(dateKey)) {
      grouped.set(dateKey, [])
    }
    grouped.get(dateKey)!.push(task)
  })

  // Group tasks without due_date under a special key
  if (tasksWithoutDate.length > 0) {
    grouped.set("no-date", tasksWithoutDate)
  }

  return grouped
}

// Date header component
function DateHeader({ dateKey }: { dateKey: string }) {
  const today = isTodayWib(dateKey)
  const past = isPastWibDate(dateKey) && !today
  const daysDiff = getWibDateDiffInDays(dateKey) ?? 0
  const daysAgo = Math.abs(daysDiff)

  let label = formatDateWib(dateKey, { weekday: "long", day: "2-digit", month: "long", year: "numeric" })
  if (today) label = "Today"
  else if (daysDiff === -1) label = "Yesterday"
  else if (daysDiff === 1) label = "Tomorrow"
  else if (daysDiff < 0 && daysAgo <= 7) label = `${daysAgo} days ago`
  else if (daysDiff > 0 && daysDiff <= 7) label = `In ${daysAgo} days`

  return (
    <div className="flex items-center gap-3 py-2">
      <div
        className={cn(
          "flex items-center justify-center w-10 h-10 rounded-full text-sm font-bold",
          today
            ? "bg-primary text-white"
            : past
            ? "bg-slate-200 text-slate-600"
            : "bg-blue-100 text-blue-600"
        )}
      >
        {formatDateWib(dateKey, { day: "2-digit" })}
      </div>
      <div>
        <p
          className={cn(
            "text-sm font-semibold",
            today ? "text-primary" : "text-slate-800"
          )}
        >
          {today ? "Today" : formatDateWib(dateKey, { weekday: "long" })}
        </p>
        <p className="text-xs text-slate-500">
          {formatDateWib(dateKey, { month: "long", year: "numeric" })}
          {!today && daysDiff !== 0 && (
            <span className="ml-2 text-slate-400">
              ({daysDiff < 0 ? `${daysAgo}d ago` : `in ${daysAgo}d`})
            </span>
          )}
        </p>
      </div>
    </div>
  )
}

// Main Timeline Component
export function ActivityTimeline({
  tasks,
  onTaskClick,
  onCreateTask,
  onEditTask,
  showDateHeaders = true,
  expandable = true,
}: ActivityTimelineProps) {
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

  // Filter tasks - exclude cancelled from timeline display
  const filteredTasks = React.useMemo(() => {
    return tasks.filter((task) => task.status !== "cancelled")
  }, [tasks])

  const groupedTasks = React.useMemo(() => groupTasksByDate(filteredTasks), [filteredTasks])
  const dateKeys = Array.from(groupedTasks.keys())

  // Count tasks by status
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

  const handleTaskClick = (task: Task) => {
    if (onTaskClick) {
      onTaskClick(task)
    } else {
      setSelectedTask(task)
      setShowModal(true)
    }
  }

  return (
    <>
      <div className="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        {/* Header */}
        <div className="px-5 py-4 border-b border-gray-100">
          <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
              <h2 className="text-lg font-semibold text-gray-900">Activity Timeline</h2>
              <p className="text-sm text-gray-500 mt-0.5">
                Chronological view of your activities
              </p>
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

        {/* Timeline Content */}
        <div className="p-6">
          {filteredTasks.length === 0 ? (
            <div className="text-center py-12">
              <Calendar className="h-12 w-12 text-gray-300 mx-auto mb-4" />
              <h3 className="text-lg font-medium text-gray-900 mb-2">
                {viewMode === "my" ? "No tasks assigned to you" : "No department activities"}
              </h3>
              <p className="text-gray-500 mb-4">
                {viewMode === "my" 
                  ? "Create a new activity or switch to Department view"
                  : "No activities found in your department"
                }
              </p>
              <Button
                variant="primary"
                onClick={() => {
                  if (onCreateTask) {
                    onCreateTask()
                    return
                  }
                  router.visit(route("activity.task.index", { modal: "create" }))
                }}
              >
                Create Activity
              </Button>
            </div>
          ) : (
            <div className="space-y-6">
              {dateKeys.map((dateKey) => {
                const dateTasks = groupedTasks.get(dateKey) || []

                return (
                  <div key={dateKey}>
                    {showDateHeaders && dateKey !== "no-date" && <DateHeader dateKey={dateKey} />}
                    {showDateHeaders && dateKey === "no-date" && (
                      <div className="flex items-center gap-3 py-2">
                        <div className="flex items-center justify-center w-10 h-10 rounded-full bg-gray-200 text-gray-600 text-sm font-bold">-</div>
                        <div>
                          <p className="text-sm font-semibold text-gray-900">No Deadline</p>
                          <p className="text-xs text-gray-500">Tasks without due date</p>
                        </div>
                      </div>
                    )}
                    <div className="mt-2">
                      {dateTasks.map((task, taskIndex) => (
                        <TimelineItem
                          key={task.id}
                          task={task}
                          isLast={taskIndex === dateTasks.length - 1}
                          onTaskClick={handleTaskClick}
                        />
                      ))}
                    </div>
                  </div>
                )
              })}
            </div>
          )}
        </div>

        {/* Footer Help */}
        <div className="px-5 py-3 border-t border-gray-100 bg-gray-50/50">
          <div className="flex items-center gap-4 text-xs text-gray-600">
            <span className="flex items-center gap-1.5">
              <Info className="h-3.5 w-3.5 text-gray-400" />
              Klik task untuk lihat detail
            </span>
            <span className="text-gray-300">•</span>
            <span>Tasks diurutkan berdasarkan tanggal terbaru</span>
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
    </>
  )
}

// Compact timeline for sidebar/widget
interface CompactTimelineProps {
  tasks: Task[]
  limit?: number
  onTaskClick?: (task: Task) => void
}

export function CompactTimeline({ tasks, limit = 5, onTaskClick }: CompactTimelineProps) {
  const recentTasks = tasks.slice(0, limit)

  if (recentTasks.length === 0) {
    return (
      <p className="text-sm text-gray-500 text-center py-4">
        No recent activities
      </p>
    )
  }

  return (
    <div className="space-y-3">
      {recentTasks.map((task) => {
        const overdue = isOverdueWib(task.due_date, task.status)

        return (
          <div
            key={task.id}
            className="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 cursor-pointer transition-colors"
            onClick={() => onTaskClick?.(task)}
          >
            <span className={cn("h-2.5 w-2.5 rounded-full", statusStyles[task.status]?.dot ?? "bg-gray-400")} />
            <div className="flex-1 min-w-0">
              <p className="text-sm font-medium text-gray-900 truncate">
                {task.task_title}
              </p>
              <p className={cn(
                "text-xs",
                overdue ? "text-red-600" : "text-gray-500"
              )}>
                {task.due_date ? formatDateWib(task.due_date, { day: "2-digit", month: "short" }) : '-'}
              </p>
            </div>
          </div>
        )
      })}
    </div>
  )
}

export default ActivityTimeline
