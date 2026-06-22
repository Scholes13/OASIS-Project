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
} from "@dnd-kit/core"
import {
  sortableKeyboardCoordinates,
  arrayMove,
} from "@dnd-kit/sortable"
import { router, usePage } from "@inertiajs/react"
import BoardColumn, { TaskCardOverlay, type KanbanColumnConfig, type ViewMode } from "./kanban/KanbanColumn"
import { showToast } from "../ui/toast"
import { handleExecutionTimeGuidance } from "./quick-status-guidance"
import { TaskDetailModal } from "./TaskDetailModal"
import type { Task, PageProps } from "@/types"

// ============================================================================
// TYPES & CONFIGURATION
// ============================================================================

interface KanbanBoardProps {
  tasks: Task[]
  onStatusChange?: (taskId: number, newStatus: string) => void
  onTaskClick?: (task: Task) => void
  onCreateTask?: () => void
  onEditTask?: (task: Task) => void
}

// Column configuration (no in-review lane)
const columns: KanbanColumnConfig[] = [
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
  const originalStatusRef = React.useRef<KanbanColumnConfig["id"] | null>(null)

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
    const grouped: Record<KanbanColumnConfig["id"], Task[]> = {
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

  const findColumnByTaskId = (taskId: UniqueIdentifier): KanbanColumnConfig["id"] | null => {
    for (const column of columns) {
      if (tasksByStatus[column.id].some(t => t.id === taskId)) {
        return column.id
      }
    }
    return null
  }

  const resolveColumnId = (dropId: UniqueIdentifier): KanbanColumnConfig["id"] | null => {
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

    if (!over) {
      setLocalTasks([...tasks])
      return
    }

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
      const movedTask = tasks.find(task => task.id === activeId)

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
            onError: (errors) => {
              setLocalTasks(tasks)

              if (movedTask && !handleExecutionTimeGuidance(movedTask, errors, onEditTask)) {
                showToast.error("Failed to update status")
              } else if (!movedTask) {
                showToast.error("Failed to update status")
              }
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
