import * as React from "react"
import { createPortal } from "react-dom"
import {
  DndContext,
  DragOverlay,
  closestCorners,
  KeyboardSensor,
  PointerSensor,
  useSensor,
  useSensors,
  DragStartEvent,
  DragEndEvent,
  DragOverEvent,
  UniqueIdentifier,
  useDroppable,
} from "@dnd-kit/core"
import {
  SortableContext,
  sortableKeyboardCoordinates,
  useSortable,
  verticalListSortingStrategy,
  arrayMove,
} from "@dnd-kit/sortable"
import { CSS } from "@dnd-kit/utilities"
import { router } from "@inertiajs/react"
import { motion, AnimatePresence } from "framer-motion"
import { format } from "date-fns"
import { id as idLocale } from "date-fns/locale"
import {
  Clock,
  AlertTriangle,
  MoreHorizontal,
  Paperclip,
  MessageSquare,
} from "lucide-react"
import { cn } from "@/lib/utils"
import { showToast } from "../ui/toast"
import { TaskDetailModal } from "./TaskDetailModal"
import type { Task } from "@/types"

// ============================================================================
// TYPES & CONFIGURATION
// ============================================================================

interface KanbanColumn {
  id: string
  title: string
  borderColor: string
  headerBg: string
  headerText: string
}

interface KanbanBoardProps {
  tasks: Task[]
  onStatusChange?: (taskId: number, newStatus: string) => void
  onTaskClick?: (task: Task) => void
}

// Column configuration with color-coded top borders
const columns: KanbanColumn[] = [
  {
    id: "planned",
    title: "Planning",
    borderColor: "border-t-slate-400",
    headerBg: "bg-slate-100",
    headerText: "text-slate-700",
  },
  {
    id: "in_progress",
    title: "In Progress",
    borderColor: "border-t-amber-500",
    headerBg: "bg-amber-50",
    headerText: "text-amber-700",
  },
  {
    id: "completed",
    title: "Completed",
    borderColor: "border-t-emerald-500",
    headerBg: "bg-emerald-50",
    headerText: "text-emerald-700",
  },
]

// Avatar colors for team members
const avatarColors = [
  "bg-indigo-100 text-indigo-700",
  "bg-emerald-100 text-emerald-700",
  "bg-amber-100 text-amber-700",
  "bg-rose-100 text-rose-700",
  "bg-violet-100 text-violet-700",
]

// Activity type colors
const typeColors: Record<string, string> = {
  blue: "bg-blue-50 text-blue-700",
  indigo: "bg-indigo-50 text-indigo-700",
  purple: "bg-purple-50 text-purple-700",
  pink: "bg-pink-50 text-pink-700",
  red: "bg-red-50 text-red-700",
  orange: "bg-orange-50 text-orange-700",
  amber: "bg-amber-50 text-amber-700",
  yellow: "bg-yellow-50 text-yellow-700",
  lime: "bg-lime-50 text-lime-700",
  green: "bg-green-50 text-green-700",
  emerald: "bg-emerald-50 text-emerald-700",
  teal: "bg-teal-50 text-teal-700",
  cyan: "bg-cyan-50 text-cyan-700",
  gray: "bg-gray-100 text-gray-600",
}

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

function isOverdue(dueDate: string, status: string): boolean {
  if (status === "completed" || status === "cancelled") return false
  return new Date(dueDate) < new Date()
}

// ============================================================================
// AVATAR STACK COMPONENT
// ============================================================================

function AvatarStack({ participants, max = 2 }: { participants: any[]; max?: number }) {
  if (!participants || participants.length === 0) {
    return (
      <div className="h-7 w-7 rounded-full bg-slate-100 border-2 border-white flex items-center justify-center text-[10px] text-slate-400">
        ?
      </div>
    )
  }

  const visible = participants.slice(0, max)
  const remaining = participants.length - max

  return (
    <div className="flex -space-x-2">
      {visible.map((p, index) => {
        const name = p.name || p.user?.name || "U"
        return (
          <div
            key={p.id || p.user_id || index}
            className={cn(
              "h-7 w-7 rounded-full border-2 border-white flex items-center justify-center text-[10px] font-semibold shadow-sm",
              avatarColors[index % avatarColors.length]
            )}
            title={name}
          >
            {name.charAt(0).toUpperCase()}
          </div>
        )
      })}
      {remaining > 0 && (
        <div className="h-7 w-7 rounded-full bg-gray-100 border-2 border-white flex items-center justify-center text-[10px] font-semibold text-gray-600 shadow-sm">
          +{remaining}
        </div>
      )}
    </div>
  )
}

// ============================================================================
// TASK CARD COMPONENT - Linear Style
// ============================================================================

interface TaskCardProps {
  task: Task
  isDragging?: boolean
  onTaskClick?: (task: Task) => void
}

function TaskCard({ task, isDragging, onTaskClick }: TaskCardProps) {
  const {
    attributes,
    listeners,
    setNodeRef,
    transform,
    transition,
    isDragging: isSortableDragging,
  } = useSortable({ id: task.id })

  const style = {
    transform: CSS.Transform.toString(transform),
    transition,
  }

  const overdue = isOverdue(task.due_date, task.status)
  const hasDragged = React.useRef(false)

  React.useEffect(() => {
    if (!isDragging) {
      const timeout = setTimeout(() => {
        hasDragged.current = false
      }, 50)
      return () => clearTimeout(timeout)
    } else {
      hasDragged.current = true
    }
  }, [isDragging])

  const handleClick = (e: React.MouseEvent) => {
    if (hasDragged.current || isSortableDragging || isDragging) {
      e.preventDefault()
      return
    }
    onTaskClick?.(task)
  }

  const typeColor = typeColors[task.activity_type?.color || "gray"] || typeColors.gray
  const attachmentCount = task.attachments?.length || 0

  return (
    <motion.div
      ref={setNodeRef}
      style={style}
      layoutId={`task-${task.id}`}
      initial={{ opacity: 0, y: 10 }}
      animate={{ opacity: 1, y: 0 }}
      exit={{ opacity: 0, y: -10 }}
      className={cn(
        "group bg-white p-3 rounded-lg border border-slate-200 shadow-sm cursor-pointer transition-all duration-200",
        "hover:shadow-md hover:border-slate-300",
        isSortableDragging && "opacity-50 shadow-none",
        isDragging && "ring-2 ring-indigo-500 ring-offset-2 z-50"
      )}
      {...attributes}
      {...listeners}
      onClick={handleClick}
    >
      {/* Type Badge - Small */}
      <div className="mb-2">
        <span className={cn(
          "inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium",
          typeColor
        )}>
          {task.activity_type?.name ?? "Activity"}
        </span>
      </div>

      {/* Title */}
      <h4 className="text-sm font-semibold text-slate-900 leading-snug mb-2 line-clamp-2">
        {task.task_title}
      </h4>

      {/* Description - Optional */}
      {task.task_description && (
        <p className="text-xs text-slate-500 line-clamp-2 mb-3">
          {task.task_description}
        </p>
      )}

      {/* Footer */}
      <div className="flex items-center justify-between pt-2 border-t border-slate-100">
        <div className="flex items-center gap-2">
          {/* Due Date */}
          <div className={cn(
            "flex items-center gap-1 text-[11px] font-medium",
            overdue ? "text-rose-600" : "text-slate-500"
          )}>
            <Clock className="h-3 w-3" strokeWidth={2} />
            <span>{format(new Date(task.due_date), "d MMM", { locale: idLocale })}</span>
            {overdue && <AlertTriangle className="h-3 w-3" strokeWidth={2} />}
          </div>

          {/* Priority Dot */}
          <div className="flex items-center gap-1">
            <span className={cn(
              "h-2 w-2 rounded-full",
              task.priority === 'high' ? "bg-rose-500" :
              task.priority === 'medium' ? "bg-amber-500" : "bg-emerald-500"
            )} />
            <span className="text-[10px] text-slate-400 capitalize">{task.priority || 'low'}</span>
          </div>

          {/* Attachments with Icon */}
          {attachmentCount > 0 && (
            <div className="flex items-center gap-0.5 text-slate-400">
              <Paperclip className="h-3 w-3" strokeWidth={2} />
              <span className="text-[10px]">{attachmentCount}</span>
            </div>
          )}
        </div>

        {/* Avatar Stack */}
        <AvatarStack participants={task.participants || []} max={2} />
      </div>
    </motion.div>
  )
}

// ============================================================================
// TASK CARD OVERLAY (for dragging)
// ============================================================================

function TaskCardOverlay({ task }: { task: Task }) {
  const overdue = isOverdue(task.due_date, task.status)
  const typeColor = typeColors[task.activity_type?.color || "gray"] || typeColors.gray

  return (
    <div className="bg-white p-3 rounded-lg border border-slate-200 shadow-xl ring-2 ring-indigo-500/20 transform rotate-2 cursor-grabbing w-[300px]">
      <div className="mb-2">
        <span className={cn(
          "inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium",
          typeColor
        )}>
          {task.activity_type?.name ?? "Activity"}
        </span>
      </div>

      <h4 className="text-sm font-semibold text-slate-900 leading-snug mb-2 line-clamp-2">
        {task.task_title}
      </h4>

      <div className="flex items-center justify-between pt-2 border-t border-slate-100">
        <div className="flex items-center gap-2">
          <div className={cn(
            "flex items-center gap-1 text-[11px] font-medium",
            overdue ? "text-rose-600" : "text-slate-500"
          )}>
            <Clock className="h-3 w-3" strokeWidth={2} />
            <span>{format(new Date(task.due_date), "d MMM", { locale: idLocale })}</span>
          </div>
          <span className={cn(
            "h-2 w-2 rounded-full",
            task.priority === 'high' ? "bg-rose-500" : "bg-emerald-500"
          )} />
        </div>
        <AvatarStack participants={task.participants || []} max={2} />
      </div>
    </div>
  )
}

// ============================================================================
// BOARD COLUMN COMPONENT - With Background Container
// ============================================================================

interface BoardColumnProps {
  column: KanbanColumn
  tasks: Task[]
  onTaskClick?: (task: Task) => void
}

function BoardColumn({ column, tasks, onTaskClick }: BoardColumnProps) {
  // Make the column a droppable area
  const { setNodeRef, isOver } = useDroppable({
    id: column.id,
  })

  return (
    <div className={cn(
      "flex flex-col rounded-xl overflow-hidden min-h-[500px]",
      "bg-slate-50/80 border border-slate-200/60",
      "border-t-4",
      column.borderColor,
      isOver && "ring-2 ring-indigo-400 ring-offset-2 bg-indigo-50/30"
    )}>
      {/* Column Header */}
      <div className={cn(
        "flex items-center justify-between px-4 py-3",
        column.headerBg
      )}>
        <div className="flex items-center gap-2">
          <h3 className={cn("text-sm font-bold", column.headerText)}>
            {column.title}
          </h3>
          <span className={cn(
            "px-2 py-0.5 rounded-full text-xs font-semibold",
            column.headerBg,
            column.headerText,
            "bg-white/60"
          )}>
            {tasks.length}
          </span>
        </div>
        <button className="p-1 rounded hover:bg-white/50 text-slate-400 hover:text-slate-600 transition-colors">
          <MoreHorizontal className="h-4 w-4" strokeWidth={1.5} />
        </button>
      </div>

      {/* Tasks Container - Scrollable with droppable ref */}
      <SortableContext items={tasks.map(t => t.id)} strategy={verticalListSortingStrategy}>
        <div 
          ref={setNodeRef}
          className={cn(
            "flex-1 flex flex-col gap-2 p-3 min-h-[200px] max-h-[calc(100vh-300px)] overflow-y-auto transition-colors",
            isOver && "bg-indigo-50/50"
          )}
        >
          <AnimatePresence mode="popLayout">
            {tasks.length === 0 ? (
              <motion.div
                initial={{ opacity: 0 }}
                animate={{ opacity: 1 }}
                className={cn(
                  "flex flex-col items-center justify-center py-12 text-center rounded-lg border-2 border-dashed",
                  isOver ? "border-indigo-300 bg-indigo-50" : "border-transparent"
                )}
              >
                <div className="h-10 w-10 rounded-full bg-slate-200/50 flex items-center justify-center mb-3">
                  <div className="h-2 w-2 rounded-full bg-slate-300" />
                </div>
                <p className="text-slate-400 text-xs font-medium">No tasks</p>
                <p className="text-slate-300 text-[10px] mt-1">Drag tasks here</p>
              </motion.div>
            ) : (
              tasks.map((task) => (
                <TaskCard
                  key={task.id}
                  task={task}
                  onTaskClick={onTaskClick}
                />
              ))
            )}
          </AnimatePresence>
        </div>
      </SortableContext>
    </div>
  )
}

// ============================================================================
// MAIN KANBAN BOARD COMPONENT
// ============================================================================

export function KanbanBoard({ tasks, onStatusChange, onTaskClick }: KanbanBoardProps) {
  const [activeTask, setActiveTask] = React.useState<Task | null>(null)
  const [localTasks, setLocalTasks] = React.useState<Task[]>(tasks)
  const [selectedTask, setSelectedTask] = React.useState<Task | null>(null)
  const [isModalOpen, setIsModalOpen] = React.useState(false)

  React.useEffect(() => {
    setLocalTasks(tasks)
  }, [tasks])

  const sensors = useSensors(
    useSensor(PointerSensor, {
      activationConstraint: { distance: 5 },
    }),
    useSensor(KeyboardSensor, {
      coordinateGetter: sortableKeyboardCoordinates,
    })
  )

  // Group tasks by status
  const tasksByStatus = React.useMemo(() => {
    const grouped: Record<string, Task[]> = {}
    columns.forEach(col => {
      grouped[col.id] = localTasks.filter(task => task.status === col.id)
    })
    return grouped
  }, [localTasks])

  const findColumnByTaskId = (taskId: UniqueIdentifier): string | null => {
    for (const column of columns) {
      if (tasksByStatus[column.id].some(t => t.id === taskId)) {
        return column.id
      }
    }
    return null
  }

  const handleDragStart = (event: DragStartEvent) => {
    const task = localTasks.find(t => t.id === event.active.id)
    if (task) setActiveTask(task)
  }

  const handleDragOver = (event: DragOverEvent) => {
    const { active, over } = event
    if (!over) return

    const activeColumn = findColumnByTaskId(active.id)
    const overColumn = columns.find(col => col.id === over.id)?.id || findColumnByTaskId(over.id)

    if (!activeColumn || !overColumn || activeColumn === overColumn) return

    setLocalTasks(prev => prev.map(task => 
      task.id === active.id ? { ...task, status: overColumn as Task["status"] } : task
    ))
  }

  const handleDragEnd = (event: DragEndEvent) => {
    const { active, over } = event
    setActiveTask(null)

    if (!over) return

    const activeId = active.id as number
    const activeColumn = findColumnByTaskId(activeId)
    if (!activeColumn) return

    const columnTasks = tasksByStatus[activeColumn] || []
    const oldIndex = columnTasks.findIndex(t => t.id === activeId)
    const overTaskIndex = columnTasks.findIndex(t => t.id === over.id)

    if (overTaskIndex !== -1 && oldIndex !== overTaskIndex) {
      const newColumnTasks = arrayMove(columnTasks, oldIndex, overTaskIndex)
      setLocalTasks(prev => {
        const otherTasks = prev.filter(t => t.status !== activeColumn)
        return [...otherTasks, ...newColumnTasks]
      })
      showToast.success("Task reordered")
      return
    }

    const task = localTasks.find(t => t.id === activeId)
    const overColumn = columns.find(col => col.id === over.id)?.id || findColumnByTaskId(over.id)

    if (task && overColumn && task.status !== overColumn) {
      if (onStatusChange) {
        onStatusChange(activeId, overColumn)
      } else {
        router.put(
          route("activity.task.update", { task: activeId }),
          { status: overColumn },
          {
            preserveScroll: true,
            onSuccess: () => {
              showToast.success("Status updated", `Task moved to ${columns.find(c => c.id === overColumn)?.title}`)
            },
            onError: () => {
              setLocalTasks(tasks)
              showToast.error("Failed to update status")
            },
          }
        )
      }
    }
  }

  const handleTaskClick = (task: Task) => {
    if (onTaskClick) {
      onTaskClick(task)
    } else {
      setSelectedTask(task)
      setIsModalOpen(true)
    }
  }

  return (
    <DndContext
      sensors={sensors}
      collisionDetection={closestCorners}
      onDragStart={handleDragStart}
      onDragOver={handleDragOver}
      onDragEnd={handleDragEnd}
    >
      <div className="grid grid-cols-1 md:grid-cols-3 gap-4 pb-6 px-1">
        {columns.map((column) => (
          <BoardColumn
            key={column.id}
            column={column}
            tasks={tasksByStatus[column.id] || []}
            onTaskClick={handleTaskClick}
          />
        ))}
      </div>

      {typeof window !== 'undefined' && createPortal(
        <DragOverlay dropAnimation={{ duration: 200, easing: 'cubic-bezier(0.18, 0.67, 0.6, 1.22)' }}>
          {activeTask ? <TaskCardOverlay task={activeTask} /> : null}
        </DragOverlay>,
        document.body
      )}

      <TaskDetailModal
        task={selectedTask}
        open={isModalOpen}
        onClose={() => {
          setIsModalOpen(false)
          setSelectedTask(null)
        }}
      />
    </DndContext>
  )
}

export default KanbanBoard

