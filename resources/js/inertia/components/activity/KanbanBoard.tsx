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
import { router, usePage } from "@inertiajs/react"
import { motion, AnimatePresence } from "framer-motion"
import { format, isToday, isTomorrow } from "date-fns"
import { id as idLocale } from "date-fns/locale"
import {
  Clock,
  AlertTriangle,
  MoreHorizontal,
  Plus,
  User,
  Users,
} from "lucide-react"
import { cn } from "@/lib/utils"
import { showToast } from "../ui/toast"
import { TaskDetailModal } from "./TaskDetailModal"
import type { Task, PageProps } from "@/types"

type ViewMode = "my" | "department"

// ============================================================================
// TYPES & CONFIGURATION
// ============================================================================

interface KanbanColumn {
  id: "planned" | "in_progress" | "completed"
  title: string
  dotClass: string
}

interface KanbanBoardProps {
  tasks: Task[]
  onStatusChange?: (taskId: number, newStatus: string) => void
  onTaskClick?: (task: Task) => void
  onCreateTask?: () => void
  onEditTask?: (task: Task) => void
}

// Column configuration (no in-review lane)
const columns: KanbanColumn[] = [
  {
    id: "planned",
    title: "To Do",
    dotClass: "bg-slate-400",
  },
  {
    id: "in_progress",
    title: "In Progress",
    dotClass: "bg-blue-500",
  },
  {
    id: "completed",
    title: "Done",
    dotClass: "bg-emerald-500",
  },
]

// Avatar colors for team members
const avatarColors = [
  "bg-blue-50 text-blue-700",
  "bg-emerald-100 text-emerald-700",
  "bg-amber-100 text-amber-700",
  "bg-rose-100 text-rose-700",
  "bg-violet-100 text-violet-700",
]

const priorityBadgeClass: Record<string, string> = {
  high: "bg-rose-100 text-rose-600",
  medium: "bg-amber-100 text-amber-600",
  low: "bg-sky-100 text-sky-600",
}

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

function isOverdue(dueDate: string | null, status: string): boolean {
  if (!dueDate) return false
  if (status === "completed" || status === "cancelled") return false
  return new Date(dueDate) < new Date()
}

function canEditTask(task: Task, currentUserId: number | undefined): boolean {
  if (!currentUserId) return false
  const isParticipant = task.participants?.some(p => 
    p.user_id === currentUserId || p.id === currentUserId
  )
  const isCreator = task.created_by === currentUserId
  return isParticipant || isCreator
}

function formatCardDueDate(dueDate: string | null): string {
  if (!dueDate) return "No due date"

  const parsed = new Date(dueDate)
  if (isToday(parsed)) return "Today"
  if (isTomorrow(parsed)) return "Tomorrow"

  return format(parsed, "dd MMM", { locale: idLocale })
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
  isReadOnly?: boolean
}

function TaskCard({ task, isDragging, onTaskClick, isReadOnly = false }: TaskCardProps) {
  const {
    attributes,
    listeners,
    setNodeRef,
    transform,
    transition,
    isDragging: isSortableDragging,
  } = useSortable({ 
    id: task.id,
    disabled: isReadOnly, // Disable drag for read-only tasks
  })

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

  const priorityClass = priorityBadgeClass[task.priority] || "bg-slate-100 text-slate-600"

  return (
    <motion.div
      ref={setNodeRef}
      style={style}
      layoutId={`task-${task.id}`}
      initial={{ opacity: 0, y: 10 }}
      animate={{ opacity: 1, y: 0 }}
      exit={{ opacity: 0, y: -10 }}
      className={cn(
        "group rounded-xl border border-border bg-background p-3.5 shadow-[0_4px_12px_rgba(15,53,85,0.05)] transition-all duration-200",
        isReadOnly 
          ? "cursor-default opacity-80 hover:opacity-90" 
          : "cursor-pointer hover:border-[color:rgba(24,98,167,0.35)] hover:shadow-[0_8px_18px_rgba(15,53,85,0.08)]",
        isSortableDragging && "opacity-50 shadow-none",
        isDragging && "z-50 ring-2 ring-primary ring-offset-2",
        task.status === "completed" && "bg-background/95"
      )}
      {...(isReadOnly ? {} : { ...attributes, ...listeners })}
      onClick={handleClick}
    >
      <div className="mb-2 flex items-center justify-between gap-2">
        <span className={cn("inline-flex rounded-full px-2 py-0.5 text-xs font-semibold", priorityClass)}>
          {task.priority.charAt(0).toUpperCase() + task.priority.slice(1)}
        </span>
        <span className="truncate text-[11px] font-medium text-muted-foreground">
          {task.activity_type?.name ?? "General"}
        </span>
      </div>

      <h4 className="mb-2 line-clamp-2 text-sm font-semibold leading-snug text-foreground">
        {task.task_title}
      </h4>

      {task.task_description && (
        <p className="mb-3 line-clamp-1 text-xs text-muted-foreground">
          {task.task_description}
        </p>
      )}

      <div className="mt-3 flex items-center justify-between border-t border-border pt-2.5">
        <div className={cn(
          "flex items-center gap-1.5 text-xs font-medium",
          overdue ? "text-rose-600" : "text-muted-foreground"
        )}>
          <Clock className="h-3.5 w-3.5" strokeWidth={2} />
          <span>{formatCardDueDate(task.due_date)}</span>
          {overdue && <AlertTriangle className="h-3.5 w-3.5" strokeWidth={2} />}
        </div>
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
  const priorityClass = priorityBadgeClass[task.priority] || "bg-slate-100 text-slate-600"

  return (
    <div className="w-[300px] cursor-grabbing rounded-xl border border-border bg-background p-3.5 shadow-xl ring-2 ring-primary/25">
      <div className="mb-2 flex items-center justify-between gap-2">
        <span className={cn("inline-flex rounded-full px-2 py-0.5 text-xs font-semibold", priorityClass)}>
          {task.priority.charAt(0).toUpperCase() + task.priority.slice(1)}
        </span>
        <span className="truncate text-[11px] font-medium text-muted-foreground">
          {task.activity_type?.name ?? "General"}
        </span>
      </div>

      <h4 className="mb-2 line-clamp-2 text-sm font-semibold leading-snug text-foreground">
        {task.task_title}
      </h4>

      <div className="mt-3 flex items-center justify-between border-t border-border pt-2.5">
        <div className={cn(
          "flex items-center gap-1.5 text-xs font-medium",
          overdue ? "text-rose-600" : "text-muted-foreground"
        )}>
          <Clock className="h-3.5 w-3.5" strokeWidth={2} />
          <span>{formatCardDueDate(task.due_date)}</span>
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
  onCreateTask?: () => void
  currentUserId?: number
  viewMode: ViewMode
}

function BoardColumn({ column, tasks, onTaskClick, onCreateTask, currentUserId, viewMode }: BoardColumnProps) {
  // Make the column a droppable area
  const { setNodeRef, isOver } = useDroppable({
    id: column.id,
  })

  return (
    <div className={cn(
      "flex flex-col overflow-hidden rounded-xl border border-border bg-secondary/70",
      isOver && "ring-2 ring-primary ring-offset-2"
    )}>
      {/* Column Header */}
      <div className="flex items-center justify-between border-b border-border px-4 py-3">
        <div className="flex items-center gap-2">
          <span className={cn("h-2 w-2 rounded-full", column.dotClass)} />
          <h3 className="text-sm font-semibold text-foreground">
            {column.title}
          </h3>
          <span className="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-background px-1.5 text-[11px] font-semibold text-muted-foreground">
            {tasks.length}
          </span>
        </div>
        <button className="rounded p-1 text-muted-foreground transition-colors hover:text-foreground">
          <MoreHorizontal className="h-4 w-4" strokeWidth={1.5} />
        </button>
      </div>

      {/* Tasks Container - Scrollable with droppable ref */}
      <SortableContext items={tasks.map(t => t.id)} strategy={verticalListSortingStrategy}>
        <div 
          ref={setNodeRef}
          className={cn(
            "flex flex-1 flex-col gap-3 p-3 transition-colors",
            "min-h-[420px] max-h-[calc(100vh-280px)] overflow-y-auto",
            isOver && "bg-secondary"
          )}
        >
          <AnimatePresence mode="popLayout">
            {tasks.length === 0 ? (
              <motion.div
                initial={{ opacity: 0 }}
                animate={{ opacity: 1 }}
                className={cn(
                  "flex flex-col items-center justify-center rounded-lg border border-dashed border-border bg-background/80 py-10 text-center",
                  isOver && "border-[color:rgba(24,98,167,0.35)]"
                )}
              >
                <div className="mb-2 flex h-8 w-8 items-center justify-center rounded-full bg-secondary">
                  <div className="h-2 w-2 rounded-full bg-border" />
                </div>
                <p className="text-xs font-medium text-muted-foreground">No tasks</p>
                <p className="mt-1 text-[10px] text-muted-foreground">Drag tasks here</p>
              </motion.div>
            ) : (
              tasks.map((task) => {
                // In department view, tasks that user doesn't own are read-only
                const isReadOnly = viewMode === "department" && !canEditTask(task, currentUserId)
                return (
                  <TaskCard
                    key={task.id}
                    task={task}
                    onTaskClick={onTaskClick}
                    isReadOnly={isReadOnly}
                  />
                )
              })
            )}
          </AnimatePresence>

          <button
            type="button"
            onClick={(e) => {
              e.stopPropagation()
              if (onCreateTask) {
                onCreateTask()
                return
              }
              router.visit(route("activity.task.index", { modal: "create" }))
            }}
            className="inline-flex w-full items-center justify-center gap-1.5 rounded-lg border border-dashed border-border bg-background px-3 py-2 text-sm font-medium text-muted-foreground transition-colors hover:border-[color:rgba(24,98,167,0.35)] hover:text-primary"
          >
            <Plus className="h-4 w-4" strokeWidth={1.75} />
            Add Task
          </button>
        </div>
      </SortableContext>
    </div>
  )
}

// ============================================================================
// MAIN KANBAN BOARD COMPONENT
// ============================================================================

export function KanbanBoard({ tasks, onStatusChange, onTaskClick, onCreateTask, onEditTask }: KanbanBoardProps) {
  const { auth, filters } = usePage<PageProps>().props as PageProps & {
    filters?: { scope?: ViewMode }
  }
  const currentUserId = auth?.user?.id
  const initialScope = filters?.scope === "department" ? "department" : "my"
  
  const [activeTask, setActiveTask] = React.useState<Task | null>(null)
  const [localTasks, setLocalTasks] = React.useState<Task[]>(tasks)
  const [selectedTask, setSelectedTask] = React.useState<Task | null>(null)
  const [isModalOpen, setIsModalOpen] = React.useState(false)
  const [viewMode, setViewMode] = React.useState<ViewMode>(initialScope)
  // Track original status when drag starts to detect actual column changes
  const originalStatusRef = React.useRef<KanbanColumn["id"] | null>(null)

  React.useEffect(() => {
    setLocalTasks(tasks)
  }, [tasks])

  React.useEffect(() => {
    if (filters?.scope === "my" || filters?.scope === "department") {
      setViewMode(filters.scope)
    }
  }, [filters?.scope])

  // Handle view mode change - fetch from server with correct scope
  const handleViewModeChange = (mode: ViewMode) => {
    setViewMode(mode)
    router.get(
      route('activity.task.index'),
      { scope: mode, view: 'board' },
      {
        preserveState: true,
        preserveScroll: true,
        only: ['stats', 'tasks', 'filters'],
      }
    )
  }

  // Filter tasks - hide cancelled in board
  const filteredTasks = React.useMemo(() => {
    return localTasks.filter((task) => task.status !== "cancelled")
  }, [localTasks])

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
    const grouped: Record<KanbanColumn["id"], Task[]> = {
      planned: [],
      in_progress: [],
      completed: [],
    }

    filteredTasks.forEach((task) => {
      if (task.status === "planned" || task.status === "in_progress" || task.status === "completed") {
        grouped[task.status].push(task)
      }
    })

    return grouped
  }, [filteredTasks])

  const findColumnByTaskId = (taskId: UniqueIdentifier): KanbanColumn["id"] | null => {
    for (const column of columns) {
      if (tasksByStatus[column.id].some(t => t.id === taskId)) {
        return column.id
      }
    }
    return null
  }

  const resolveColumnId = (dropId: UniqueIdentifier): KanbanColumn["id"] | null => {
    const directColumn = columns.find((col) => col.id === dropId)
    if (directColumn) return directColumn.id
    return findColumnByTaskId(dropId)
  }

  const handleDragStart = (event: DragStartEvent) => {
    const task = localTasks.find(t => t.id === event.active.id)
    if (task) {
      setActiveTask(task)
      // Store original status when drag starts
      if (task.status === "planned" || task.status === "in_progress" || task.status === "completed") {
        originalStatusRef.current = task.status
      }
    }
  }

  const handleDragOver = (event: DragOverEvent) => {
    const { active, over } = event
    if (!over) return

    const activeColumn = findColumnByTaskId(active.id)
    const overColumn = resolveColumnId(over.id)

    if (!activeColumn || !overColumn || activeColumn === overColumn) return

    setLocalTasks(prev => prev.map(task => 
      task.id === active.id ? { ...task, status: overColumn } : task
    ))
  }

  const handleDragEnd = (event: DragEndEvent) => {
    const { active, over } = event
    setActiveTask(null)
    
    // Get the original status before clearing
    const originalStatus = originalStatusRef.current
    originalStatusRef.current = null

    if (!over) return

    const activeId = active.id as number
    
    // Determine target column - check if dropped on column directly or on a task within column
    const overColumn = resolveColumnId(over.id)

    // Handle reordering within same column
    if (originalStatus && overColumn && originalStatus === overColumn) {
      const columnTasks = tasksByStatus[overColumn]
      const oldIndex = columnTasks.findIndex(t => t.id === activeId)
      const overTaskIndex = columnTasks.findIndex(t => t.id === Number(over.id))

      if (oldIndex !== -1 && overTaskIndex !== -1 && oldIndex !== overTaskIndex) {
        const newColumnTasks = arrayMove(columnTasks, oldIndex, overTaskIndex)
        setLocalTasks(prev => {
          const otherTasks = prev.filter(t => t.status !== overColumn)
          return [...otherTasks, ...newColumnTasks]
        })
        showToast.success("Task reordered")
      }
      return
    }

    // Handle column change - use originalStatus to detect actual change
    if (originalStatus && overColumn && originalStatus !== overColumn) {
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

      <div className="grid grid-cols-1 gap-4 px-1 pb-6 lg:grid-cols-3">
        {columns.map((column) => (
          <BoardColumn
            key={column.id}
            column={column}
            tasks={tasksByStatus[column.id]}
            onTaskClick={handleTaskClick}
            onCreateTask={onCreateTask}
            currentUserId={currentUserId}
            viewMode={viewMode}
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
        onEdit={onEditTask}
      />
    </DndContext>
  )
}

export default KanbanBoard
