import * as React from 'react';
import { router } from '@inertiajs/react';
import { useDroppable } from '@dnd-kit/core';
import { SortableContext, useSortable, verticalListSortingStrategy } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import { AnimatePresence, motion } from 'framer-motion';
import { format, isToday, isTomorrow } from 'date-fns';
import { id as idLocale } from 'date-fns/locale';
import { AlertTriangle, Clock, MoreHorizontal, Plus } from 'lucide-react';
import { cn } from '@/lib/utils';
import type { Task } from '@/types';

export type ViewMode = 'my' | 'department';

export interface KanbanColumnConfig {
    id: 'planned' | 'in_progress' | 'completed';
    title: string;
    dotClass: string;
}

interface BoardColumnProps {
    column: KanbanColumnConfig;
    tasks: Task[];
    onTaskClick?: (task: Task) => void;
    onCreateTask?: () => void;
    currentUserId?: number;
    viewMode: ViewMode;
}

const avatarColors = [
    'bg-blue-50 text-blue-700',
    'bg-emerald-100 text-emerald-700',
    'bg-amber-100 text-amber-700',
    'bg-rose-100 text-rose-700',
    'bg-violet-100 text-violet-700',
];

const priorityBadgeClass: Record<string, string> = {
    high: 'bg-rose-100 text-rose-600',
    medium: 'bg-amber-100 text-amber-600',
    low: 'bg-sky-100 text-sky-600',
};

function isOverdue(dueDate: string | null, status: string): boolean {
    if (!dueDate) return false;
    if (status === 'completed' || status === 'cancelled') return false;
    return new Date(dueDate) < new Date();
}

function canEditTask(task: Task, currentUserId: number | undefined): boolean {
    if (!currentUserId) return false;
    const isParticipant = task.participants?.some((participant) =>
        participant.user_id === currentUserId || participant.id === currentUserId
    );
    return Boolean(isParticipant) || task.created_by === currentUserId;
}

function formatCardDueDate(dueDate: string | null): string {
    if (!dueDate) return 'No due date';
    const parsed = new Date(dueDate);
    if (isToday(parsed)) return 'Today';
    if (isTomorrow(parsed)) return 'Tomorrow';
    return format(parsed, 'dd MMM', { locale: idLocale });
}

function AvatarStack({ participants, max = 2 }: { participants: Task['participants']; max?: number }) {
    if (!participants || participants.length === 0) {
        return (
            <div className="h-7 w-7 rounded-full bg-slate-100 border-2 border-white flex items-center justify-center text-[10px] text-slate-400">
                ?
            </div>
        );
    }

    const visible = participants.slice(0, max);
    const remaining = participants.length - max;

    return (
        <div className="flex -space-x-2">
            {visible.map((participant, index) => {
                const name = participant.name || participant.user?.name || 'U';
                return (
                    <div
                        key={participant.id || participant.user_id || index}
                        className={cn(
                            'h-7 w-7 rounded-full border-2 border-white flex items-center justify-center text-[10px] font-semibold shadow-sm',
                            avatarColors[index % avatarColors.length]
                        )}
                        title={name}
                    >
                        {name.charAt(0).toUpperCase()}
                    </div>
                );
            })}
            {remaining > 0 && (
                <div className="h-7 w-7 rounded-full bg-gray-100 border-2 border-white flex items-center justify-center text-[10px] font-semibold text-gray-600 shadow-sm">
                    +{remaining}
                </div>
            )}
        </div>
    );
}

function TaskCard({ task, isDragging, onTaskClick, isReadOnly = false }: { task: Task; isDragging?: boolean; onTaskClick?: (task: Task) => void; isReadOnly?: boolean }) {
    const { attributes, listeners, setNodeRef, transform, transition, isDragging: isSortableDragging } = useSortable({
        id: task.id,
        disabled: isReadOnly,
    });
    const overdue = isOverdue(task.due_date, task.status);
    const hasDragged = React.useRef(false);

    React.useEffect(() => {
        if (!isDragging) {
            const timeout = setTimeout(() => { hasDragged.current = false; }, 50);
            return () => clearTimeout(timeout);
        }
        hasDragged.current = true;
    }, [isDragging]);

    const priorityClass = priorityBadgeClass[task.priority] || 'bg-slate-100 text-slate-600';

    return (
        <motion.div
            ref={setNodeRef}
            style={{ transform: CSS.Transform.toString(transform), transition }}
            layoutId={`task-${task.id}`}
            initial={{ opacity: 0, y: 10 }}
            animate={{ opacity: 1, y: 0 }}
            exit={{ opacity: 0, y: -10 }}
            className={cn(
                'group rounded-xl border border-border bg-background p-3.5 shadow-[0_4px_12px_rgba(15,53,85,0.05)] transition-all duration-200',
                isReadOnly ? 'cursor-default opacity-80 hover:opacity-90' : 'cursor-pointer hover:border-[color:rgba(24,98,167,0.35)] hover:shadow-[0_8px_18px_rgba(15,53,85,0.08)]',
                isSortableDragging && 'opacity-50 shadow-none',
                isDragging && 'z-50 ring-2 ring-primary ring-offset-2',
                task.status === 'completed' && 'bg-background/95'
            )}
            {...(isReadOnly ? {} : { ...attributes, ...listeners })}
            onClick={(event) => {
                if (hasDragged.current || isSortableDragging || isDragging) {
                    event.preventDefault();
                    return;
                }
                onTaskClick?.(task);
            }}
        >
            <KanbanCardContent task={task} overdue={overdue} priorityClass={priorityClass} />
        </motion.div>
    );
}

export function TaskCardOverlay({ task }: { task: Task }) {
    const overdue = isOverdue(task.due_date, task.status);
    const priorityClass = priorityBadgeClass[task.priority] || 'bg-slate-100 text-slate-600';

    return (
        <div className="w-[300px] cursor-grabbing rounded-xl border border-border bg-background p-3.5 shadow-xl ring-2 ring-primary/25">
            <KanbanCardContent task={task} overdue={overdue} priorityClass={priorityClass} compact />
        </div>
    );
}

function KanbanCardContent({ task, overdue, priorityClass, compact = false }: { task: Task; overdue: boolean; priorityClass: string; compact?: boolean }) {
    return (
        <>
            <div className="mb-2 flex items-center justify-between gap-2">
                <span className={cn('inline-flex rounded-full px-2 py-0.5 text-xs font-semibold', priorityClass)}>
                    {task.priority.charAt(0).toUpperCase() + task.priority.slice(1)}
                </span>
                <span className="truncate text-[11px] font-medium text-muted-foreground">
                    {task.activity_type?.name ?? 'General'}
                </span>
            </div>
            <h4 className="mb-2 line-clamp-2 text-sm font-semibold leading-snug text-foreground">
                {task.task_title}
            </h4>
            {!compact && task.task_description && (
                <p className="mb-3 line-clamp-1 text-xs text-muted-foreground">{task.task_description}</p>
            )}
            <div className="mt-3 flex items-center justify-between border-t border-border pt-2.5">
                <div className={cn('flex items-center gap-1.5 text-xs font-medium', overdue ? 'text-rose-600' : 'text-muted-foreground')}>
                    <Clock className="h-3.5 w-3.5" strokeWidth={2} />
                    <span>{formatCardDueDate(task.due_date)}</span>
                    {overdue && !compact && <AlertTriangle className="h-3.5 w-3.5" strokeWidth={2} />}
                </div>
                <AvatarStack participants={task.participants || []} max={2} />
            </div>
        </>
    );
}

export default function BoardColumn({ column, tasks, onTaskClick, onCreateTask, currentUserId, viewMode }: BoardColumnProps) {
    const { setNodeRef, isOver } = useDroppable({ id: column.id });

    return (
        <div className={cn('flex flex-col overflow-hidden rounded-xl border border-border bg-secondary/70', isOver && 'ring-2 ring-primary ring-offset-2')}>
            <div className="flex items-center justify-between border-b border-border px-4 py-3">
                <div className="flex items-center gap-2">
                    <span className={cn('h-2 w-2 rounded-full', column.dotClass)} />
                    <h3 className="text-sm font-semibold text-foreground">{column.title}</h3>
                    <span className="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-background px-1.5 text-[11px] font-semibold text-muted-foreground">
                        {tasks.length}
                    </span>
                </div>
                <button className="rounded p-1 text-muted-foreground transition-colors hover:text-foreground">
                    <MoreHorizontal className="h-4 w-4" strokeWidth={1.5} />
                </button>
            </div>
            <SortableContext items={tasks.map((task) => task.id)} strategy={verticalListSortingStrategy}>
                <div
                    ref={setNodeRef}
                    className={cn('flex flex-1 flex-col gap-3 p-3 transition-colors min-h-[420px] max-h-[calc(100vh-280px)] overflow-y-auto', isOver && 'bg-secondary')}
                >
                    <AnimatePresence mode="popLayout">
                        {tasks.length === 0 ? (
                            <motion.div initial={{ opacity: 0 }} animate={{ opacity: 1 }} className={cn('flex flex-col items-center justify-center rounded-lg border border-dashed border-border bg-background/80 py-10 text-center', isOver && 'border-[color:rgba(24,98,167,0.35)]')}>
                                <div className="mb-2 flex h-8 w-8 items-center justify-center rounded-full bg-secondary">
                                    <div className="h-2 w-2 rounded-full bg-border" />
                                </div>
                                <p className="text-xs font-medium text-muted-foreground">No tasks</p>
                                <p className="mt-1 text-[10px] text-muted-foreground">Drag tasks here</p>
                            </motion.div>
                        ) : tasks.map((task) => (
                            <TaskCard
                                key={task.id}
                                task={task}
                                onTaskClick={onTaskClick}
                                isReadOnly={viewMode === 'department' && !canEditTask(task, currentUserId)}
                            />
                        ))}
                    </AnimatePresence>

                    <button
                        type="button"
                        onClick={(event) => {
                            event.stopPropagation();
                            if (onCreateTask) {
                                onCreateTask();
                                return;
                            }
                            router.visit(route('activity.task.index', { modal: 'create' }));
                        }}
                        className="inline-flex w-full items-center justify-center gap-1.5 rounded-lg border border-dashed border-border bg-background px-3 py-2 text-sm font-medium text-muted-foreground transition-colors hover:border-[color:rgba(24,98,167,0.35)] hover:text-primary"
                    >
                        <Plus className="h-4 w-4" strokeWidth={1.75} />
                        Add Task
                    </button>
                </div>
            </SortableContext>
        </div>
    );
}
