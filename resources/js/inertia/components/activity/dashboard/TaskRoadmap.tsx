import { Link } from '@inertiajs/react';
import { List, Calendar, ArrowRight, CheckCircle2, ChevronLeft, ChevronRight } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { format, parseISO, differenceInDays } from 'date-fns';

interface TaskBasic {
    id: number;
    title: string;
    task_title?: string;
    due_date: string;
    task_description?: string;
    activity_type?: { name: string; color: string };
}

interface PaginatedData<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
}

type TabType = 'todo' | 'inprogress' | 'review';

interface TaskRoadmapProps {
    title: string;
    tasks: TaskBasic[];
    pagination?: PaginatedData<TaskBasic>;
    activeTab: TabType;
    onTabChange: (tab: TabType) => void;
    onPageChange: (url: string | null) => void;
    showCreateButton?: boolean;
    onCreateTask?: () => void;
}

export function TaskRoadmap({
    title,
    tasks,
    pagination,
    activeTab,
    onTabChange,
    onPageChange,
    showCreateButton = true,
    onCreateTask,
}: TaskRoadmapProps) {
    return (
        <div className="lg:col-span-7 bg-white rounded-xl border border-gray-200 shadow-sm flex flex-col">
            {/* Header */}
            <div className="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <h2 className="text-xl font-bold text-gray-900 flex items-center gap-2">
                    <List className="h-5 w-5 text-primary" />
                    {title}
                </h2>
                <div className="flex bg-gray-100 p-1 rounded-lg">
                    {(['todo', 'inprogress', 'review'] as const).map(tab => (
                        <button
                            key={tab}
                            onClick={() => onTabChange(tab)}
                            className={cn(
                                "px-3 py-1.5 text-sm font-semibold rounded-md capitalize transition-all",
                                activeTab === tab ? "bg-white text-gray-900 shadow-sm" : "text-gray-500 hover:text-gray-700"
                            )}
                        >
                            {tab.replace('todo', 'To Do').replace('inprogress', 'In Progress')}
                        </button>
                    ))}
                </div>
            </div>

            {/* Content */}
            <div className="p-0 flex-1 overflow-auto max-h-[520px]">
                {tasks.length > 0 ? (
                    <table className="w-full text-left">
                        <thead className="bg-gray-50 text-sm text-gray-400 uppercase tracking-wider sticky top-0 z-10">
                            <tr>
                                <th className="px-6 py-3 font-medium border-b border-gray-100">Task Name</th>
                                <th className="px-6 py-3 font-medium border-b border-gray-100">Category</th>
                                <th className="px-6 py-3 font-medium border-b border-gray-100">Deadline</th>
                                <th className="px-6 py-3 font-medium text-right border-b border-gray-100">Action</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100 text-base">
                            {tasks.map((task) => {
                                const dueDate = parseISO(task.due_date);
                                const daysDiff = differenceInDays(dueDate, new Date());
                                const isCritical = daysDiff <= 2 && daysDiff >= -1;
                                
                                return (
                                    <tr key={task.id} className="hover:bg-gray-50/50 transition-colors group">
                                        <td className="px-6 py-3.5">
                                            <Link href={route('activity.task.index', { task: task.id, modal: 'detail' })} className="block">
                                                <span className="font-medium text-gray-900 group-hover:text-primary transition-colors">
                                                    {task.task_title || task.title}
                                                </span>
                                                {task.task_description && (
                                                    <p className="text-sm text-gray-400 truncate max-w-[200px] mt-0.5">
                                                        {task.task_description}
                                                    </p>
                                                )}
                                            </Link>
                                        </td>
                                        <td className="px-6 py-3.5">
                                            {task.activity_type ? (
                                                <span 
                                                    className="inline-flex items-center px-2.5 py-0.5 rounded-full text-sm font-medium border" 
                                                    style={{ 
                                                        backgroundColor: `${task.activity_type.color}10`, 
                                                        color: task.activity_type.color, 
                                                        borderColor: `${task.activity_type.color}30` 
                                                    }}
                                                >
                                                    {task.activity_type.name}
                                                </span>
                                            ) : (
                                                <span className="text-gray-400">-</span>
                                            )}
                                        </td>
                                        <td className="px-6 py-3.5">
                                            <div className="flex items-center text-gray-600">
                                                <Calendar className={cn("h-3.5 w-3.5 mr-1.5", isCritical ? "text-red-500" : "text-gray-400")} />
                                                <span className={cn(isCritical ? "text-red-600 font-semibold" : "")}>
                                                    {format(dueDate, 'MMM d')}
                                                </span>
                                            </div>
                                            {isCritical && (
                                                <span className="text-[10px] text-red-500 font-medium block mt-0.5 ml-5">
                                                    Due soon
                                                </span>
                                            )}
                                        </td>
                                        <td className="px-6 py-3.5 text-right">
                                            <Link href={route('activity.task.index', { task: task.id, modal: 'detail' })}>
                                                <Button variant="ghost" size="sm" className="h-8 w-8 p-0 hover:bg-blue-50 hover:text-blue-700">
                                                    <ArrowRight className="h-4 w-4" />
                                                </Button>
                                            </Link>
                                        </td>
                                    </tr>
                                );
                            })}
                        </tbody>
                    </table>
                ) : (
                    <div className="flex flex-col items-center justify-center h-64 text-center">
                        <div className="p-4 bg-gray-50 rounded-full mb-3 shadow-sm border border-gray-100">
                            <CheckCircle2 className="h-8 w-8 text-gray-300" />
                        </div>
                        <p className="text-gray-900 font-medium">No tasks in this stage</p>
                        <p className="text-base text-gray-500 mt-1">Check other tabs or create a new task</p>
                        {showCreateButton && (
                            onCreateTask ? (
                                <Button variant="outline" size="sm" className="mt-4" onClick={onCreateTask}>Create Task</Button>
                            ) : (
                                <Link href={route('activity.task.index', { modal: 'create' })} className="mt-4">
                                    <Button variant="outline" size="sm">Create Task</Button>
                                </Link>
                            )
                        )}
                    </div>
                )}
            </div>

            {/* Pagination */}
            {pagination && pagination.last_page > 1 && (
                <div className="px-6 py-4 border-t border-gray-100 flex items-center justify-between">
                    <p className="text-sm text-gray-500">
                        Showing{' '}
                        <span className="font-medium">
                            {((pagination.current_page - 1) * pagination.per_page) + 1}
                        </span>{' '}
                        to{' '}
                        <span className="font-medium">
                            {Math.min(pagination.current_page * pagination.per_page, pagination.total)}
                        </span>{' '}
                        of <span className="font-medium">{pagination.total}</span>
                    </p>
                    <div className="flex items-center gap-2">
                        <Button 
                            variant="outline" 
                            size="sm" 
                            className="h-8 gap-1 px-2 text-sm" 
                            onClick={() => onPageChange(pagination.prev_page_url)} 
                            disabled={!pagination.prev_page_url}
                        >
                            <ChevronLeft className="h-3.5 w-3.5" /> Prev
                        </Button>
                        <Button 
                            variant="outline" 
                            size="sm" 
                            className="h-8 gap-1 px-2 text-sm" 
                            onClick={() => onPageChange(pagination.next_page_url)} 
                            disabled={!pagination.next_page_url}
                        >
                            Next <ChevronRight className="h-3.5 w-3.5" />
                        </Button>
                    </div>
                </div>
            )}
        </div>
    );
}
