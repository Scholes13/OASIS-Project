import { motion } from 'framer-motion';
import TaskCard from './TaskCard';
import type { Task, TaskStatus } from '@/types';

interface TaskBoardProps {
    tasks: Task[];
}

interface BoardColumnProps {
    title: string;
    status: TaskStatus;
    tasks: Task[];
    color: string;
    bgColor: string;
}

function BoardColumn({ title, status, tasks, color, bgColor }: BoardColumnProps) {
    const filteredTasks = tasks.filter(t => t.status === status);

    return (
        <div className="flex-1 min-w-[280px] max-w-[350px]">
            {/* Column Header */}
            <div className={`${bgColor} rounded-t-lg px-4 py-3 border-b-2 ${color.replace('text-', 'border-')}`}>
                <div className="flex items-center justify-between">
                    <h3 className={`font-semibold ${color}`}>{title}</h3>
                    <span className={`text-sm ${color} opacity-75`}>{filteredTasks.length}</span>
                </div>
            </div>

            {/* Column Content */}
            <div className="bg-gray-50 rounded-b-lg p-3 min-h-[400px] space-y-3">
                {filteredTasks.length === 0 ? (
                    <div className="text-center py-8 text-gray-400 text-sm">
                        No tasks
                    </div>
                ) : (
                    filteredTasks.map((task, index) => (
                        <TaskCard key={task.id} task={task} index={index} />
                    ))
                )}
            </div>
        </div>
    );
}

export default function TaskBoard({ tasks }: TaskBoardProps) {
    const columns: { title: string; status: TaskStatus; color: string; bgColor: string }[] = [
        { title: 'Planned', status: 'planned', color: 'text-blue-600', bgColor: 'bg-blue-50' },
        { title: 'In Progress', status: 'in_progress', color: 'text-yellow-600', bgColor: 'bg-yellow-50' },
        { title: 'Completed', status: 'completed', color: 'text-green-600', bgColor: 'bg-green-50' },
        { title: 'Cancelled', status: 'cancelled', color: 'text-gray-600', bgColor: 'bg-gray-100' },
    ];

    return (
        <motion.div
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            transition={{ duration: 0.3 }}
            className="flex gap-4 overflow-x-auto pb-4"
        >
            {columns.map((column) => (
                <BoardColumn
                    key={column.status}
                    title={column.title}
                    status={column.status}
                    tasks={tasks}
                    color={column.color}
                    bgColor={column.bgColor}
                />
            ))}
        </motion.div>
    );
}
