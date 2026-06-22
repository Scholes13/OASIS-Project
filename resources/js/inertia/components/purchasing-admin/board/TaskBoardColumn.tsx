import { useDroppable } from '@dnd-kit/core';
import { SortableContext, verticalListSortingStrategy } from '@dnd-kit/sortable';
import { AnimatePresence, motion } from 'framer-motion';
import { MoreHorizontal } from 'lucide-react';
import { cn } from '@/lib/utils';
import type { AdminTask } from '../types';
import { TaskBoardCard } from './TaskBoardCard';

export interface TaskBoardColumnConfig {
    id: string;
    title: string;
    borderColor: string;
    headerBg: string;
    headerText: string;
    status: AdminTask['status'];
}

interface TaskBoardColumnProps {
    column: TaskBoardColumnConfig;
    tasks: AdminTask[];
    onTaskClick?: (task: AdminTask) => void;
}

export function TaskBoardColumn({ column, tasks, onTaskClick }: TaskBoardColumnProps) {
    const { setNodeRef, isOver } = useDroppable({
        id: column.id,
    });

    return (
        <div
            className={cn(
                'flex flex-col rounded-xl overflow-hidden min-h-[500px]',
                'bg-slate-50/80 border border-slate-200/60',
                'border-t-4',
                column.borderColor,
                isOver && 'ring-2 ring-blue-400 ring-offset-2 bg-blue-50'
            )}
        >
            <div
                className={cn(
                    'flex items-center justify-between px-4 py-3',
                    column.headerBg
                )}
            >
                <div className="flex items-center gap-2">
                    <h3 className={cn('text-sm font-bold', column.headerText)}>
                        {column.title}
                    </h3>
                    <span
                        className={cn(
                            'px-2 py-0.5 rounded-full text-xs font-semibold',
                            column.headerBg,
                            column.headerText,
                            'bg-white/60'
                        )}
                    >
                        {tasks.length}
                    </span>
                </div>
                <button className="p-1 rounded hover:bg-white/50 text-slate-400 hover:text-slate-600 transition-colors">
                    <MoreHorizontal className="h-4 w-4" strokeWidth={1.5} />
                </button>
            </div>

            <SortableContext
                items={tasks.map((task) => task.id)}
                strategy={verticalListSortingStrategy}
            >
                <div
                    ref={setNodeRef}
                    className={cn(
                        'flex-1 flex flex-col gap-2 p-3 min-h-[200px] max-h-[calc(100vh-300px)] overflow-y-auto transition-colors',
                        isOver && 'bg-blue-50'
                    )}
                >
                    <AnimatePresence mode="popLayout">
                        {tasks.length === 0 ? (
                            <motion.div
                                initial={{ opacity: 0 }}
                                animate={{ opacity: 1 }}
                                className={cn(
                                    'flex flex-col items-center justify-center py-12 text-center rounded-lg border-2 border-dashed',
                                    isOver ? 'border-primary bg-primary/10' : 'border-transparent'
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
                                <TaskBoardCard
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
    );
}
