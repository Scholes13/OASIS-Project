import * as React from 'react';
import { createPortal } from 'react-dom';
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
} from '@dnd-kit/core';
import { sortableKeyboardCoordinates } from '@dnd-kit/sortable';
import { router } from '@inertiajs/react';
import { showToast } from '@/components/ui/toast';
import { CompleteTaskModal } from './CompleteTaskModal';
import { TaskBoardColumn, type TaskBoardColumnConfig } from './board/TaskBoardColumn';
import { TaskCardOverlay } from './board/TaskBoardCard';
import type { AdminTask } from './types';

// ============================================================================
// TYPES & CONFIGURATION
// ============================================================================

interface PurchasingTaskBoardProps {
    tasks: AdminTask[];
    onStatusChange?: (taskId: number, newStatus: string) => void;
    onTaskClick?: (task: AdminTask) => void;
    onClaim?: (taskId: number) => void;
    onStart?: (taskId: number) => void;
    onComplete?: (task: AdminTask) => void; // Called when task dropped to Complete column
    readonly?: boolean;
}

// Column configuration
const columns: TaskBoardColumnConfig[] = [
    {
        id: 'pending_followup',
        title: 'Pending',
        borderColor: 'border-t-slate-400',
        headerBg: 'bg-slate-100',
        headerText: 'text-slate-700',
        status: 'pending_followup',
    },
    {
        id: 'in_progress',
        title: 'In Progress',
        borderColor: 'border-t-amber-500',
        headerBg: 'bg-amber-50',
        headerText: 'text-amber-700',
        status: 'in_progress',
    },
    {
        id: 'done',
        title: 'Completed',
        borderColor: 'border-t-emerald-500',
        headerBg: 'bg-emerald-50',
        headerText: 'text-emerald-700',
        status: 'done',
    },
];

// ============================================================================
// MAIN KANBAN BOARD COMPONENT
// ============================================================================

export function PurchasingTaskBoard({
    tasks,
    onStatusChange,
    onTaskClick,
    onComplete,
    readonly = false,
}: PurchasingTaskBoardProps) {
    const [activeTask, setActiveTask] = React.useState<AdminTask | null>(null);
    const [localTasks, setLocalTasks] = React.useState<AdminTask[]>(tasks);
    const [pendingCompleteTask, setPendingCompleteTask] = React.useState<AdminTask | null>(null);

    React.useEffect(() => {
        setLocalTasks(tasks);
    }, [tasks]);

    const sensors = useSensors(
        useSensor(PointerSensor, {
            activationConstraint: { distance: 5 },
        }),
        useSensor(KeyboardSensor, {
            coordinateGetter: sortableKeyboardCoordinates,
        })
    );

    // Group tasks by status
    const tasksByStatus = React.useMemo(() => {
        const grouped: Record<string, AdminTask[]> = {};
        columns.forEach(col => {
            grouped[col.id] = localTasks.filter(task => task.status === col.id);
        });
        return grouped;
    }, [localTasks]);

    const findColumnByTaskId = (taskId: UniqueIdentifier): string | null => {
        for (const column of columns) {
            if (tasksByStatus[column.id].some(t => t.id === taskId)) {
                return column.id;
            }
        }
        return null;
    };

    const handleDragStart = (event: DragStartEvent) => {
        if (readonly) return;
        // Store the task with its ORIGINAL status before any drag updates
        const task = tasks.find(t => t.id === event.active.id); // Use props.tasks, not localTasks
        if (task) setActiveTask({ ...task }); // Clone to preserve original status
    };

    const handleDragOver = (event: DragOverEvent) => {
        if (readonly) return;
        const { active, over } = event;
        if (!over) return;

        const activeColumn = findColumnByTaskId(active.id);
        const overColumn = columns.find(col => col.id === over.id)?.id || findColumnByTaskId(over.id);

        if (!activeColumn || !overColumn || activeColumn === overColumn) return;

        setLocalTasks(prev => prev.map(task =>
            task.id === active.id ? { ...task, status: overColumn as AdminTask['status'] } : task
        ));
    };

    const handleDragEnd = (event: DragEndEvent) => {
        if (readonly) {
            setActiveTask(null);
            return;
        }
        const { active, over } = event;
        const originalTask = activeTask; // This has the ORIGINAL status from props.tasks
        setActiveTask(null);

        if (!over || !originalTask) return;

        const activeId = active.id as number;
        const overColumn = columns.find(col => col.id === over.id)?.id || findColumnByTaskId(over.id);
        const originalStatus = originalTask.status;

        // Check if status actually changed (compare original with target)
        if (overColumn && originalStatus !== overColumn) {
            // If dropping to 'done' column, trigger complete modal
            if (overColumn === 'done') {
                // Revert local status until modal is completed
                setLocalTasks(prev => prev.map(t =>
                    t.id === activeId ? { ...originalTask } : t
                ));
                // Trigger complete modal with original task
                if (onComplete) {
                    onComplete(originalTask);
                } else {
                    setPendingCompleteTask(originalTask);
                }
            } else if (onStatusChange) {
                onStatusChange(activeId, overColumn);
            } else {
                // Default: call API to update status
                router.put(
                    route('purchasing.admin.tasks.update-status', { taskId: activeId }),
                    { status: overColumn },
                    {
                        preserveScroll: true,
                        onSuccess: () => {
                            showToast.success('Status updated', `Task moved to ${columns.find(c => c.id === overColumn)?.title}`);
                        },
                        onError: () => {
                            setLocalTasks(tasks);
                            showToast.error('Failed to update status');
                        },
                    }
                );
            }
        }
    };

    // Handler for when complete modal succeeds
    const handleCompleteSuccess = () => {
        setPendingCompleteTask(null);
        // Refresh data from props
        setLocalTasks(tasks);
    };

    return (
        <>
            <DndContext
                sensors={sensors}
                collisionDetection={closestCorners}
                onDragStart={handleDragStart}
                onDragOver={handleDragOver}
                onDragEnd={handleDragEnd}
            >
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4 pb-6 px-1">
                    {columns.map((column) => (
                        <TaskBoardColumn
                            key={column.id}
                            column={column}
                            tasks={tasksByStatus[column.id] || []}
                            onTaskClick={onTaskClick}
                        />
                    ))}
                </div>

                {typeof window !== 'undefined' && createPortal(
                    <DragOverlay dropAnimation={{ duration: 200, easing: 'cubic-bezier(0.18, 0.67, 0.6, 1.22)' }}>
                        {activeTask ? <TaskCardOverlay task={activeTask} /> : null}
                    </DragOverlay>,
                    document.body
                )}
            </DndContext>

            {/* Complete Task Modal - shown when dragging task to Complete column */}
            <CompleteTaskModal
                task={pendingCompleteTask}
                open={!!pendingCompleteTask}
                onClose={() => setPendingCompleteTask(null)}
                onComplete={handleCompleteSuccess}
            />
        </>
    );
}

export default PurchasingTaskBoard;
