import * as React from "react"
import { AnimatePresence, motion } from "framer-motion"
import { AlertTriangle, ArrowRight, CheckCircle2, ChevronRight, Circle, Clock, PlayCircle, Users, XCircle } from "lucide-react"
import { ActivityTypeBadge, Badge, StatusBadge } from "../../ui/Badge"
import { isOverdueWib } from "@/lib/activityDateTime"
import { cn } from "@/lib/utils"
import type { Task } from "@/types"

interface TimelineItemProps {
  task: Task
  isLast: boolean
  onTaskClick?: (task: Task) => void
  expanded?: boolean
}

function StatusIcon({ status }: { status: string }) {
  const icons: Record<string, React.ReactNode> = {
    planned: <Circle className="h-4 w-4 text-blue-500" />,
    in_progress: <PlayCircle className="h-4 w-4 text-amber-500" />,
    completed: <CheckCircle2 className="h-4 w-4 text-green-500" />,
    cancelled: <XCircle className="h-4 w-4 text-gray-400" />,
  }

  return icons[status] || <Circle className="h-4 w-4 text-gray-400" />
}

export default function TimelineItem({ task, isLast, onTaskClick, expanded = false }: TimelineItemProps) {
  const [isExpanded] = React.useState(expanded)
  const overdue = isOverdueWib(task.due_date, task.status)
  const duration = Number((task as Task & { duration_minutes?: number }).duration_minutes ?? 0)

  return (
    <div className="relative pl-8 pb-6 last:pb-0">
      {!isLast && <div className="absolute left-[11px] top-6 bottom-0 w-0.5 bg-gray-200" />}
      <div
        className={cn(
          "absolute left-0 top-1 w-6 h-6 rounded-full flex items-center justify-center bg-white border-2",
          task.status === "completed" ? "border-green-500" :
            task.status === "in_progress" ? "border-amber-500" :
              task.status === "cancelled" ? "border-gray-300" :
                overdue ? "border-red-500" : "border-blue-500"
        )}
      >
        <StatusIcon status={task.status} />
      </div>
      <motion.div
        initial={{ opacity: 0, x: -10 }}
        animate={{ opacity: 1, x: 0 }}
        className={cn(
          "bg-white rounded-xl border border-slate-200 p-4 shadow-sm hover:bg-slate-50/50 hover:border-slate-300 transition-all cursor-pointer",
          overdue && "border-red-200 bg-red-50/30"
        )}
        onClick={() => onTaskClick?.(task)}
      >
        <div className="flex items-start justify-between gap-3">
          <div className="flex-1 min-w-0">
            <div className="flex items-center gap-2 mb-1">
              <ActivityTypeBadge name={task.activity_type?.name ?? "Unknown"} color={task.activity_type?.color} />
              <StatusBadge status={task.status} />
              {overdue && (
                <Badge variant="danger">
                  <AlertTriangle className="h-3 w-3 mr-1" />
                  Overdue
                </Badge>
              )}
            </div>
            <h4 className="font-medium text-gray-900 line-clamp-1">{task.task_title}</h4>
          </div>
          <ChevronRight className={cn("h-5 w-5 text-gray-400 transition-transform", isExpanded && "rotate-90")} />
        </div>
        <div className="flex items-center gap-4 mt-2 text-xs text-gray-500">
          <div className="flex items-center gap-1">
            <Clock className="h-3 w-3" />
            <span>{task.due_date ? "All day" : "-"}</span>
          </div>
          {duration ? (
            <div className="flex items-center gap-1">
              <ArrowRight className="h-3 w-3" />
              <span>{Math.floor(duration / 60)}h {duration % 60}m</span>
            </div>
          ) : null}
          {task.participants && task.participants.length > 0 && (
            <div className="flex items-center gap-1">
              <Users className="h-3 w-3" />
              <span>{task.participants.length} participant(s)</span>
            </div>
          )}
        </div>
        <AnimatePresence>
          {isExpanded && task.task_description && (
            <motion.div initial={{ height: 0, opacity: 0 }} animate={{ height: "auto", opacity: 1 }} exit={{ height: 0, opacity: 0 }} className="overflow-hidden">
              <p className="mt-3 pt-3 border-t border-slate-100 text-sm text-gray-600">{task.task_description}</p>
            </motion.div>
          )}
        </AnimatePresence>
      </motion.div>
    </div>
  )
}
