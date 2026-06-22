import { cn } from '@/lib/utils';
import type { ActivityType, Task, TaskPriority, TaskStatus } from '@/types';

interface TaskFormBasicData {
    task_title: string;
    activity_type_id: string;
    sub_activity_id: string;
    status: TaskStatus;
    priority: TaskPriority;
    due_date: string;
    completed_date: string;
    confirm_reset_execution?: boolean;
}

interface TaskFormBasicErrors {
    task_title?: string;
    sub_activity_id?: string;
}

interface TaskFormBasicFieldsProps {
    data: TaskFormBasicData;
    errors: TaskFormBasicErrors;
    task: Task | null;
    isEditing: boolean;
    activityTypes: ActivityType[];
    subActivities: NonNullable<ActivityType['sub_activities']>;
    onChange: (key: keyof TaskFormBasicData, value: TaskFormBasicData[keyof TaskFormBasicData]) => void;
    onMerge: (data: TaskFormBasicData) => void;
}

export default function TaskFormBasicFields({
    data,
    errors,
    task,
    isEditing,
    activityTypes,
    subActivities,
    onChange,
    onMerge,
}: TaskFormBasicFieldsProps) {
    return (
        <>
            <div className="grid grid-cols-1 md:grid-cols-[2fr_1fr] gap-4">
                <div className="flex flex-col gap-1.5">
                    <label className="text-[12px] font-semibold text-slate-700">Task Title <span className="text-rose-500">*</span></label>
                    <input
                        type="text"
                        value={data.task_title}
                        onChange={(event) => onChange('task_title', event.target.value)}
                        className={cn(
                            'h-10 px-3 py-2 border rounded-lg text-[14px] transition-all focus:ring-2 focus:ring-[#16599c]/20 outline-none',
                            errors.task_title ? 'border-rose-300' : 'border-slate-200 focus:border-[#16599c]'
                        )}
                        placeholder="What needs to be done?"
                    />
                    {errors.task_title && <p className="text-[11px] text-rose-500">{errors.task_title}</p>}
                </div>

                <div className="flex flex-col gap-1.5">
                    <label className="text-[12px] font-semibold text-slate-700">Status</label>
                    <div className="relative">
                        <select
                            value={data.status}
                            onChange={(event) => {
                                const newStatus = event.target.value as TaskStatus;
                                if (newStatus === 'planned' && isEditing && (task?.started_at || task?.completed_at)) {
                                    const confirmed = window.confirm('Reset execution history and move this task back to planned?');
                                    if (!confirmed) return;
                                }

                                onMerge({
                                    ...data,
                                    status: newStatus,
                                    due_date: newStatus === 'completed' ? '' : data.due_date,
                                    completed_date: newStatus === 'completed'
                                        ? (data.completed_date || '')
                                        : data.completed_date,
                                    confirm_reset_execution: newStatus === 'planned' && isEditing && Boolean(task?.started_at || task?.completed_at),
                                });
                            }}
                            className="w-full h-10 pl-8 pr-3 border border-slate-200 rounded-lg text-[14px] focus:ring-2 focus:ring-[#16599c]/20 outline-none focus:border-[#16599c] bg-white cursor-pointer appearance-none"
                        >
                            <option value="planned">To Do</option>
                            <option value="in_progress">In Progress</option>
                            <option value="completed">Completed</option>
                        </select>
                        <div
                            className={cn(
                                'absolute left-3 top-3.5 w-2.5 h-2.5 rounded-full pointer-events-none',
                                data.status === 'completed' ? 'bg-emerald-500' :
                                    data.status === 'in_progress' ? 'bg-[#16599c]' : 'bg-slate-300'
                            )}
                        />
                    </div>
                </div>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div className="flex flex-col gap-1.5">
                    <label className="text-[12px] font-semibold text-slate-700">Activity Type <span className="text-[10px] text-slate-400 font-normal ml-1">Auto</span></label>
                    <select
                        value={data.activity_type_id}
                        disabled
                        className="h-10 px-3 border border-slate-200 rounded-lg text-[14px] bg-slate-50 text-slate-500 cursor-not-allowed appearance-none"
                    >
                        <option value="">Auto-filled...</option>
                        {activityTypes.map((type) => (
                            <option key={type.id} value={type.id.toString()}>{type.name}</option>
                        ))}
                    </select>
                </div>

                <div className="flex flex-col gap-1.5">
                    <label className="text-[12px] font-semibold text-slate-700">Sub Activity <span className="text-rose-500">*</span></label>
                    <select
                        value={data.sub_activity_id}
                        onChange={(event) => {
                            const subId = event.target.value;
                            const parentType = activityTypes.find((type) => type.sub_activities?.some((sub) => sub.id.toString() === subId));
                            if (subId && parentType) {
                                onMerge({ ...data, activity_type_id: parentType.id.toString(), sub_activity_id: subId });
                                return;
                            }
                            onChange('sub_activity_id', subId);
                        }}
                        className={cn(
                            'h-10 px-3 border rounded-lg text-[14px] focus:ring-2 focus:ring-[#16599c]/20 outline-none bg-white appearance-none cursor-pointer',
                            errors.sub_activity_id ? 'border-rose-300' : 'border-slate-200 focus:border-[#16599c]'
                        )}
                    >
                        <option value="">Select...</option>
                        {subActivities.map((sub) => (
                            <option key={sub.id} value={sub.id.toString()}>{sub.name}</option>
                        ))}
                    </select>
                    {errors.sub_activity_id && <p className="text-[11px] text-rose-500">{errors.sub_activity_id}</p>}
                </div>

                <div className="flex flex-col gap-1.5">
                    <label className="text-[12px] font-semibold text-slate-700">Priority</label>
                    <select
                        value={data.priority}
                        onChange={(event) => onChange('priority', event.target.value as TaskPriority)}
                        className="h-10 px-3 border border-slate-200 rounded-lg text-[14px] focus:ring-2 focus:ring-[#16599c]/20 outline-none focus:border-[#16599c] bg-white cursor-pointer appearance-none"
                    >
                        <option value="low">Low</option>
                        <option value="medium">Medium</option>
                        <option value="high">High</option>
                    </select>
                </div>
            </div>
        </>
    );
}
