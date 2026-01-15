import * as React from "react"
import { Link, usePage } from "@inertiajs/react"
import { format, isToday, isPast, isFuture, startOfDay, differenceInDays } from "date-fns"
import { id as idLocale } from "date-fns/locale"
import { motion, AnimatePresence } from "framer-motion"
import {
  Calendar,
  Clock,
  Users,
  User,
  ChevronRight,
  CheckCircle2,
  Circle,
  PlayCircle,
  XCircle,
  AlertTriangle,
  ArrowRight,
  Info,
} from "lucide-react"
import { cn } from "@/lib/utils"
import { Badge, StatusBadge, ActivityTypeBadge } from "../ui/Badge"
import { Button } from "../ui/Button"
import { TaskDetailModal } from "./TaskDetailModal"
import type { Task, PageProps } from "@/types"

type ViewMode = "my" | "department"

interface ActivityTimelineProps {
  tasks: Task[]
  onTaskClick?: (task: Task) => void
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
  
  const sortedTasks = [...tasks].sort((a, b) => 
    new Date(b.due_date).getTime() - new Date(a.due_date).getTime()
  )

  sortedTasks.forEach((task) => {
    const dateKey = format(new Date(task.due_date), "yyyy-MM-dd")
    if (!grouped.has(dateKey)) {
      grouped.set(dateKey, [])
    }
    grouped.get(dateKey)!.push(task)
  })

  return grouped
}

// Date header component
function DateHeader({ date }: { date: Date }) {
  const today = isToday(date)
  const past = isPast(date) && !today
  const daysAgo = differenceInDays(new Date(), date)

  let label = format(date, "EEEE, dd MMMM yyyy", { locale: idLocale })
  if (today) label = "Today"
  else if (daysAgo === 1) label = "Yesterday"
  else if (daysAgo === -1) label = "Tomorrow"
  else if (daysAgo > 0 && daysAgo <= 7) label = `${daysAgo} days ago`
  else if (daysAgo < 0 && daysAgo >= -7) label = `In ${Math.abs(daysAgo)} days`

  return (
    <div className="flex items-center gap-3 py-2">
      <div
        className={cn(
          "flex items-center justify-center w-10 h-10 rounded-full text-sm font-bold",
          today
            ? "bg-indigo-600 text-white"
            : past
            ? "bg-gray-200 text-gray-600"
            : "bg-blue-100 text-blue-600"
        )}
      >
        {format(date, "dd")}
      </div>
      <div>
        <p
          className={cn(
            "text-sm font-semibold",
            today ? "text-indigo-600" : "text-gray-900"
          )}
        >
          {today ? "Today" : format(date, "EEEE", { locale: idLocale })}
        </p>
        <p className="text-xs text-gray-500">
          {format(date, "MMMM yyyy", { locale: idLocale })}
          {!today && daysAgo !== 0 && (
            <span className="ml-2 text-gray-400">
              ({daysAgo > 0 ? `${daysAgo}d ago` : `in ${Math.abs(daysAgo)}d`})
            </span>
          )}
        </p>
      </div>
    </div>
  )
}

// Status icon component
function StatusIcon({ status }: { status: string }) {
  const iconMap: Record<string, React.ReactNode> = {
    planned: <Circle className="h-4 w-4 text-blue-500" />,
    in_progress: <PlayCircle className="h-4 w-4 text-amber-500" />,
    completed: <CheckCircle2 className="h-4 w-4 text-green-500" />,
    cancelled: <XCircle className="h-4 w-4 text-gray-400" />,
  }
  return iconMap[status] || <Circle className="h-4 w-4 text-gray-400" />
}

// Timeline item component
interface TimelineItemProps {
  task: Task
  isLast: boolean
  onTaskClick?: (task: Task) => void
  expanded?: boolean
}

function TimelineItem({ task, isLast, onTaskClick, expanded = false }: TimelineItemProps) {
  const [isExpanded, setIsExpanded] = React.useState(expanded)
  const overdue = isPast(new Date(task.due_date)) && !isToday(new Date(task.due_date)) && 
    task.status !== "completed" && task.status !== "cancelled"

  return (
    <div className="relative pl-8 pb-6 last:pb-0">
      {/* Timeline line */}
      {!isLast && (
        <div className="absolute left-[11px] top-6 bottom-0 w-0.5 bg-gray-200" />
      )}

      {/* Timeline dot */}
      <div
        className={cn(
          "absolute left-0 top-1 w-6 h-6 rounded-full flex items-center justify-center bg-white border-2",
          task.status === "completed"
            ? "border-green-500"
            : task.status === "in_progress"
            ? "border-amber-500"
            : task.status === "cancelled"
            ? "border-gray-300"
            : overdue
            ? "border-red-500"
            : "border-blue-500"
        )}
      >
        <StatusIcon status={task.status} />
      </div>

      {/* Content */}
      <motion.div
        initial={{ opacity: 0, x: -10 }}
        animate={{ opacity: 1, x: 0 }}
        className={cn(
          "bg-white rounded-lg border p-4 shadow-sm hover:shadow-md transition-all cursor-pointer",
          overdue && "border-red-200 bg-red-50/30"
        )}
        onClick={() => onTaskClick?.(task)}
      >
        {/* Header */}
        <div className="flex items-start justify-between gap-3">
          <div className="flex-1 min-w-0">
            <div className="flex items-center gap-2 mb-1">
              <ActivityTypeBadge
                name={task.activity_type?.name ?? "Unknown"}
                color={task.activity_type?.color}
                size="sm"
              />
              <StatusBadge status={task.status} size="sm" />
              {overdue && (
                <Badge variant="destructive" size="sm">
                  <AlertTriangle className="h-3 w-3 mr-1" />
                  Overdue
                </Badge>
              )}
            </div>
            <h4 className="font-medium text-gray-900 line-clamp-1">
              {task.task_title}
            </h4>
          </div>
          <ChevronRight
            className={cn(
              "h-5 w-5 text-gray-400 transition-transform",
              isExpanded && "rotate-90"
            )}
          />
        </div>

        {/* Meta info */}
        <div className="flex items-center gap-4 mt-2 text-xs text-gray-500">
          <div className="flex items-center gap-1">
            <Clock className="h-3 w-3" />
            <span>{format(new Date(task.due_date), "HH:mm", { locale: idLocale }) || "All day"}</span>
          </div>
          {task.duration_minutes && (
            <div className="flex items-center gap-1">
              <ArrowRight className="h-3 w-3" />
              <span>
                {Math.floor(task.duration_minutes / 60)}h {task.duration_minutes % 60}m
              </span>
            </div>
          )}
          {task.participants && task.participants.length > 0 && (
            <div className="flex items-center gap-1">
              <Users className="h-3 w-3" />
              <span>{task.participants.length} participant(s)</span>
            </div>
          )}
        </div>

        {/* Expanded details */}
        <AnimatePresence>
          {isExpanded && task.task_details && (
            <motion.div
              initial={{ height: 0, opacity: 0 }}
              animate={{ height: "auto", opacity: 1 }}
              exit={{ height: 0, opacity: 0 }}
              className="overflow-hidden"
            >
              <p className="mt-3 pt-3 border-t border-gray-100 text-sm text-gray-600">
                {task.task_details}
              </p>
            </motion.div>
          )}
        </AnimatePresence>
      </motion.div>
    </div>
  )
}

// Main Timeline Component
export function ActivityTimeline({
  tasks,
  onTaskClick,
  showDateHeaders = true,
  expandable = true,
}: ActivityTimelineProps) {
  const [viewMode, setViewMode] = React.useState<ViewMode>("my")
  const [selectedTask, setSelectedTask] = React.useState<Task | null>(null)
  const [showModal, setShowModal] = React.useState(false)

  // Get current user from page props
  const { auth } = usePage<PageProps>().props
  const currentUserId = auth?.user?.id

  // Filter tasks based on view mode and exclude cancelled
  const filteredTasks = React.useMemo(() => {
    // First, exclude cancelled tasks
    let filtered = tasks.filter((task) => task.status !== "cancelled")
    
    if (viewMode === "my") {
      filtered = filtered.filter((task) => {
        const isCreator = task.created_by === currentUserId
        const isParticipant = task.participants?.some(
          (p) => p.user_id === currentUserId || p.id === currentUserId
        )
        return isCreator || isParticipant
      })
    }
    return filtered
  }, [tasks, viewMode, currentUserId])

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
      <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        {/* Header */}
        <div className="px-5 py-4 border-b border-gray-100">
          <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
              <h2 className="text-lg font-semibold text-gray-900">Activity Timeline</h2>
              <p className="text-sm text-gray-500 mt-0.5">
                Chronological view of your activities
              </p>
            </div>

            {/* My Tasks / Department Toggle */}
            <div className="flex items-center p-1 bg-gray-100 rounded-lg">
              <button
                onClick={() => setViewMode("my")}
                className={cn(
                  "flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium rounded-md transition-all",
                  viewMode === "my"
                    ? "bg-white text-indigo-600 shadow-sm"
                    : "text-gray-600 hover:text-gray-900"
                )}
              >
                <User className="h-4 w-4" />
                My Tasks
              </button>
              <button
                onClick={() => setViewMode("department")}
                className={cn(
                  "flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium rounded-md transition-all",
                  viewMode === "department"
                    ? "bg-white text-indigo-600 shadow-sm"
                    : "text-gray-600 hover:text-gray-900"
                )}
              >
                <Users className="h-4 w-4" />
                Department
              </button>
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
              <Link href={route("activity.task.create")}>
                <Button variant="primary">Create Activity</Button>
              </Link>
            </div>
          ) : (
            <div className="space-y-6">
              {dateKeys.map((dateKey) => {
                const date = new Date(dateKey)
                const dateTasks = groupedTasks.get(dateKey) || []

                return (
                  <div key={dateKey}>
                    {showDateHeaders && <DateHeader date={date} />}
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
        const overdue = isPast(new Date(task.due_date)) && 
          task.status !== "completed" && task.status !== "cancelled"

        return (
          <div
            key={task.id}
            className="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 cursor-pointer transition-colors"
            onClick={() => onTaskClick?.(task)}
          >
            <StatusIcon status={task.status} />
            <div className="flex-1 min-w-0">
              <p className="text-sm font-medium text-gray-900 truncate">
                {task.task_title}
              </p>
              <p className={cn(
                "text-xs",
                overdue ? "text-red-600" : "text-gray-500"
              )}>
                {format(new Date(task.due_date), "dd MMM", { locale: idLocale })}
              </p>
            </div>
          </div>
        )
      })}
    </div>
  )
}

export default ActivityTimeline

