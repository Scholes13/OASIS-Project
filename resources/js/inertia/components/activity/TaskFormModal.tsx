import { useState, useEffect, FormEvent, useMemo } from 'react';
import { useForm, usePage } from '@inertiajs/react';
import { Dialog } from '@/components/ui/dialog';
import { Calendar as CalendarIcon } from 'lucide-react';
import { cn } from '@/lib/utils';
import { showToast } from '@/components/ui/toast';
import ParticipantSelector from '@/components/activity/form/ParticipantSelector';
import TaskFormModalFooter from '@/components/activity/form/TaskFormModalFooter';
import TaskFormModalHeader from '@/components/activity/form/TaskFormModalHeader';
import TaskFormModalTimeline from '@/components/activity/form/TaskFormModalTimeline';
import type { PageProps, Task, ActivityType, User, TaskStatus, TaskPriority, TaskParticipantUser } from '@/types';

const getTodayLocalDate = () => {
    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
};

const getDatePart = (value?: string | null): string => {
    if (!value) return '';
    return value.includes('T') ? value.split('T')[0] : value;
};

const getTimePart = (value?: string | null): string => {
    if (!value || !value.includes('T')) return '';
    return value.split('T')[1]?.substring(0, 5) || '';
};

const getTaskFormSeed = (task: Task | null, fallbackTaskDate: string) => {
    if (!task) {
        return {
            task_title: '',
            task_description: '',
            activity_type_id: '',
            sub_activity_id: '',
            status: 'planned' as TaskStatus,
            priority: 'medium' as TaskPriority,
            task_date: fallbackTaskDate,
            due_date: '',
            participant_ids: [] as number[],
            start_time: '',
            end_time: '',
            completed_date: fallbackTaskDate,
            confirm_reset_execution: false,
        };
    }

    return {
        task_title: task.task_title || '',
        task_description: task.task_description || '',
        activity_type_id: task.activity_type_id?.toString() || '',
        sub_activity_id: task.sub_activity_id?.toString() || '',
        status: (task.status as TaskStatus) || 'planned',
        priority: (task.priority as TaskPriority) || 'medium',
        task_date: getDatePart(task.task_date) || fallbackTaskDate,
        due_date: getDatePart(task.due_date) || '',
        participant_ids: task.participants?.map((participant: TaskParticipantUser) => participant.id) || [],
        start_time: getTimePart(task.started_at) || task.start_time || '',
        end_time: getTimePart(task.completed_at) || task.end_time || '',
        completed_date: getDatePart(task.completed_at) || getDatePart(task.completed_date) || getDatePart(task.task_date) || fallbackTaskDate,
        confirm_reset_execution: false,
    };
};

interface PrioritizedActivityType extends ActivityType {
    priority: 'favorite' | 'department' | 'other';
}

interface GroupedActivityTypes {
    favorites: PrioritizedActivityType[];
    department: PrioritizedActivityType[];
    others: PrioritizedActivityType[];
}

interface TaskFormProps extends PageProps {
    task: Task | null;
    activityTypes: GroupedActivityTypes | ActivityType[];
    departmentUsers?: User[];
    backdateEnabled?: boolean;
    backdatePermission?: {
        id: number;
        status: string;
        requested_date: string;
        granted_until: string;
        is_active: boolean;
    } | null;
    allowedDateRange: {
        from: string;
        to: string;
    };
}

interface TaskFormData {
    task_title: string;
    task_description: string;
    activity_type_id: string;
    sub_activity_id: string;
    status: TaskStatus;
    priority: TaskPriority;
    task_date: string;
    due_date: string;
    participant_ids: number[];
    start_time: string;
    end_time: string;
    completed_date: string;
    confirm_reset_execution?: boolean;
}

interface BackdateRequestData {
    requested_date: string;
    reason: string;
}

interface TaskFormModalProps {
    open: boolean;
    onClose: () => void;
    task: Task | null;
    initialTaskDate?: string | null;
    activityTypes: GroupedActivityTypes | ActivityType[];
    departmentUsers?: User[];
    backdateEnabled?: boolean;
    backdatePermission?: {
        id: number;
        status: string;
        requested_date: string;
        granted_until: string;
        is_active: boolean;
    } | null;
    allowedDateRange: {
        from: string;
        to: string;
    };
}

export function TaskFormModal({
    open,
    onClose,
    task,
    initialTaskDate = null,
    activityTypes,
    departmentUsers = [],
    backdateEnabled = false,
    backdatePermission = null,
    allowedDateRange,
}: TaskFormModalProps) {
    const isEditing = !!task;
    const [showBackdateModal, setShowBackdateModal] = useState(false);
    const [createAnother, setCreateAnother] = useState(false);

    // Get server date from shared Inertia props to avoid timezone mismatch
    const { serverDate } = usePage().props as { serverDate?: string };
    const todayLocal = serverDate || getTodayLocalDate();
    const defaultTaskDate = initialTaskDate || todayLocal;
    const originalStartedDate = getDatePart(task?.started_at);
    const originalCompletedDate = getDatePart(task?.completed_at);

    const { data, setData, post, put, processing, errors, reset } = useForm<TaskFormData>({
        ...getTaskFormSeed(task, defaultTaskDate),
    });

    useEffect(() => {
        if (open) {
            const createTaskDate = initialTaskDate || getTodayLocalDate();
            setData(getTaskFormSeed(task, createTaskDate));
            if (!task) {
                setCreateAnother(false);
            }
        }
    }, [open, task, initialTaskDate, reset, setData]);

    const isEditingStartedTask = isEditing && !!task?.started_at;
    const isEditingCompletedTask = isEditing && !!task?.completed_at;
    const needsStartCorrection = useMemo(() => {
        if (data.status === 'completed') {
            if (!isEditing) {
                return true;
            }

            if (!task?.started_at) {
                return true;
            }

            return data.task_date !== originalStartedDate;
        }

        if (data.status !== 'in_progress') {
            return false;
        }

        if (!isEditing) {
            return data.task_date !== todayLocal;
        }

        if (!task?.started_at) {
            return data.task_date !== todayLocal;
        }

        return false;
    }, [data.status, data.task_date, isEditing, originalStartedDate, task?.started_at, todayLocal]);

    const needsCompletionCorrection = useMemo(() => {
        if (data.status !== 'completed') {
            return false;
        }

        if (!isEditing || !task?.completed_at) {
            return true;
        }

        return data.completed_date !== originalCompletedDate;
    }, [data.status, data.completed_date, isEditing, originalCompletedDate, task?.completed_at]);

    const showReadOnlyStartSummary = false;
    const showReadOnlyCompletionSummary = false;
    const showStartTimeInput = data.status === 'completed'
        || (data.status === 'in_progress' && (needsStartCorrection || isEditingStartedTask));
    const showCompletionInputs = data.status === 'completed';
    const startedAtDisplayValue = useMemo(() => {
        if (!task?.started_at) {
            return '-';
        }

        const startedTime = getTimePart(task.started_at);
        const startedDate = data.status === 'in_progress' && isEditing ? data.task_date : getDatePart(task.started_at);

        return `${startedDate} ${startedTime}`.trim();
    }, [data.status, data.task_date, isEditing, task?.started_at]);

    const flatActivityTypes = useMemo(() => {
        if (!activityTypes) return [];
        if (Array.isArray(activityTypes)) {
            return activityTypes;
        }
        return [
            ...(activityTypes.favorites || []),
            ...(activityTypes.department || []),
            ...(activityTypes.others || []),
        ];
    }, [activityTypes]);

    const sortedSubActivities = useMemo(() => {
        return flatActivityTypes
            .flatMap((type) => type.sub_activities || [])
            .sort((a, b) => a.name.localeCompare(b.name));
    }, [flatActivityTypes]);

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();

        if (data.status !== 'completed' && data.due_date && data.task_date && data.due_date < data.task_date) {
            showToast.error('Gagal menyimpan task', 'Due date tidak boleh lebih awal dari task date.');
            return;
        }

        const currentQuery = typeof window === 'undefined'
            ? {}
            : Object.fromEntries(new URLSearchParams(window.location.search).entries());
        const url = isEditing
            ? route('activity.task.update', { task: task!.id, ...currentQuery })
            : route('activity.task.store');
        const method = isEditing ? put : post;

        method(url, {
            preserveScroll: true,
            onSuccess: () => {
                const nextCreateSeedDate = data.task_date || initialTaskDate || getTodayLocalDate();

                showToast.success(
                    isEditing ? 'Task updated' : 'Task created',
                    isEditing
                        ? 'Your changes have been saved.'
                        : createAnother
                            ? 'The task has been added. The form is ready for another entry.'
                            : 'The task has been added to your list.'
                );

                if (isEditing) {
                    onClose();
                    reset();
                    return;
                }

                if (createAnother) {
                    setData(getTaskFormSeed(null, nextCreateSeedDate));
                    return;
                }

                onClose();
                reset();
            },
            onError: (formErrors) => {
                const firstError = Object.values(formErrors)[0];
                showToast.error('Failed to save task', firstError || 'Please review the required fields and try again.');
            },
        });
    };

    const toggleParticipant = (userId: number) => {
        const current = data.participant_ids;
        if (current.includes(userId)) {
            setData('participant_ids', current.filter((id) => id !== userId));
        } else {
            setData('participant_ids', [...current, userId]);
        }
    };

    if (!open) return null;

    return (
        <Dialog open={open} onClose={onClose} className="max-w-[720px] w-full p-0 bg-white rounded-xl shadow-2xl overflow-hidden">
            {/* Wrapper — constrains total height and enables flex layout */}
            <div className="flex flex-col" style={{ maxHeight: 'min(92vh, 800px)' }}>
                <TaskFormModalHeader
                    isEditing={isEditing}
                    onClose={onClose}
                />

                {/* Body — scrollable middle section */}
                <div className="flex-1 min-h-0 overflow-y-auto px-6 py-4 overscroll-contain">
                    <form onSubmit={handleSubmit} className="flex flex-col gap-4" id="task-form">
                    
                    {/* Top Row: Title and Status */}
                    <div className="grid grid-cols-1 md:grid-cols-[2fr_1fr] gap-4">
                        <div className="flex flex-col gap-1.5">
                            <label className="text-[12px] font-semibold text-slate-700">Task Title <span className="text-rose-500">*</span></label>
                            <input
                                type="text"
                                value={data.task_title}
                                onChange={(e) => setData('task_title', e.target.value)}
                                className={cn("h-10 px-3 py-2 border rounded-lg text-[14px] transition-all focus:ring-2 focus:ring-[#16599c]/20 outline-none", errors.task_title ? "border-rose-300" : "border-slate-200 focus:border-[#16599c]")}
                                placeholder="What needs to be done?"
                            />
                            {errors.task_title && <p className="text-[11px] text-rose-500">{errors.task_title}</p>}
                        </div>

                        <div className="flex flex-col gap-1.5">
                            <label className="text-[12px] font-semibold text-slate-700">Status</label>
                            <div className="relative">
                                <select
                                    value={data.status}
                                    onChange={(e) => {
                                        const newStatus = e.target.value as TaskStatus;
                                        if (newStatus === 'planned' && isEditing && (task?.started_at || task?.completed_at)) {
                                            const confirmed = window.confirm('Reset execution history and move this task back to planned?');
                                            if (!confirmed) {
                                                return;
                                            }
                                        }

                                        setData((prev) => ({
                                            ...prev,
                                            status: newStatus,
                                            due_date: newStatus === 'completed' ? '' : prev.due_date,
                                            completed_date: newStatus === 'completed'
                                                ? (prev.completed_date || prev.task_date)
                                                : prev.completed_date,
                                            confirm_reset_execution: newStatus === 'planned' && isEditing && Boolean(task?.started_at || task?.completed_at),
                                        }));
                                    }}
                                    className="w-full h-10 pl-8 pr-3 border border-slate-200 rounded-lg text-[14px] focus:ring-2 focus:ring-[#16599c]/20 outline-none focus:border-[#16599c] bg-white cursor-pointer appearance-none"
                                >
                                    <option value="planned">To Do</option>
                                    <option value="in_progress">In Progress</option>
                                    <option value="completed">Completed</option>
                                </select>
                                <div className={cn(
                                    "absolute left-3 top-3.5 w-2.5 h-2.5 rounded-full pointer-events-none",
                                    data.status === 'completed' ? 'bg-emerald-500' :
                                    data.status === 'in_progress' ? 'bg-[#16599c]' : 'bg-slate-300'
                                )} />
                            </div>
                        </div>
                    </div>

                    {/* Categorization Row */}
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div className="flex flex-col gap-1.5">
                            <label className="text-[12px] font-semibold text-slate-700">Activity Type <span className="text-[10px] text-slate-400 font-normal ml-1">Auto</span></label>
                            <select
                                value={data.activity_type_id}
                                disabled
                                className="h-10 px-3 border border-slate-200 rounded-lg text-[14px] bg-slate-50 text-slate-500 cursor-not-allowed appearance-none"
                            >
                                <option value="">Auto-filled...</option>
                                {flatActivityTypes.map((type) => (
                                    <option key={type.id} value={type.id.toString()}>{type.name}</option>
                                ))}
                            </select>
                        </div>
                        
                        <div className="flex flex-col gap-1.5">
                            <label className="text-[12px] font-semibold text-slate-700">Sub Activity <span className="text-rose-500">*</span></label>
                            <select
                                value={data.sub_activity_id}
                                onChange={(e) => {
                                    const subId = e.target.value;
                                    const parentType = flatActivityTypes.find(t => t.sub_activities?.some(s => s.id.toString() === subId));
                                    if (subId && parentType) {
                                        setData({ ...data, activity_type_id: parentType.id.toString(), sub_activity_id: subId });
                                    } else {
                                        setData('sub_activity_id', subId);
                                    }
                                }}
                                className={cn("h-10 px-3 border rounded-lg text-[14px] focus:ring-2 focus:ring-[#16599c]/20 outline-none bg-white appearance-none cursor-pointer", errors.sub_activity_id ? "border-rose-300" : "border-slate-200 focus:border-[#16599c]")}
                            >
                                <option value="">Select...</option>
                                {sortedSubActivities.map((sub) => (
                                    <option key={sub.id} value={sub.id.toString()}>{sub.name}</option>
                                ))}
                            </select>
                            {errors.sub_activity_id && <p className="text-[11px] text-rose-500">{errors.sub_activity_id}</p>}
                        </div>

                        <div className="flex flex-col gap-1.5">
                            <label className="text-[12px] font-semibold text-slate-700">Priority</label>
                            <select
                                value={data.priority}
                                onChange={(e) => setData('priority', e.target.value as TaskPriority)}
                                className="h-10 px-3 border border-slate-200 rounded-lg text-[14px] focus:ring-2 focus:ring-[#16599c]/20 outline-none focus:border-[#16599c] bg-white cursor-pointer appearance-none"
                            >
                                <option value="low">Low</option>
                                <option value="medium">Medium</option>
                                <option value="high">High</option>
                            </select>
                        </div>
                    </div>

                    <TaskFormModalTimeline
                        data={data}
                        errors={errors}
                        allowedDateRange={allowedDateRange}
                        backdateEnabled={backdateEnabled}
                        backdatePermission={backdatePermission}
                        isEditing={isEditing}
                        needsStartCorrection={needsStartCorrection}
                        needsCompletionCorrection={needsCompletionCorrection}
                        showReadOnlyStartSummary={showReadOnlyStartSummary}
                        showReadOnlyCompletionSummary={showReadOnlyCompletionSummary}
                        showStartTimeInput={showStartTimeInput}
                        showCompletionInputs={showCompletionInputs}
                        startedAtDisplayValue={startedAtDisplayValue}
                        completedAtDisplayValue={task?.completed_at ? `${getDatePart(task.completed_at)} ${getTimePart(task.completed_at)}` : '-'}
                        onChange={setData}
                        onRequestBackdate={() => setShowBackdateModal(true)}
                    />

                    <div className="flex flex-col gap-1.5">
                        <label className="text-[12px] font-semibold text-slate-700">Participants</label>
                        <ParticipantSelector
                            users={departmentUsers}
                            selectedIds={data.participant_ids}
                            onToggle={toggleParticipant}
                            compact
                        />
                    </div>

                    {/* Description */}
                    <div className="flex flex-col gap-1.5">
                        <label className="text-[12px] font-semibold text-slate-700">Description (Optional)</label>
                        <textarea
                            value={data.task_description}
                            onChange={(e) => setData('task_description', e.target.value)}
                            rows={2}
                            className="w-full px-3 py-2 border border-slate-200 rounded-lg text-[14px] focus:ring-2 focus:ring-[#16599c]/20 focus:border-[#16599c] outline-none resize-y min-h-[60px]"
                            placeholder="Add details..."
                        />
                    </div>
                </form>
            </div>

            <TaskFormModalFooter
                isEditing={isEditing}
                isCompleted={data.status === 'completed'}
                showReadOnlyStartSummary={showReadOnlyStartSummary}
                createAnother={createAnother}
                processing={processing}
                onCreateAnotherChange={setCreateAnother}
                onClose={onClose}
            />
            </div>{/* end flex wrapper */}
        </Dialog>
    );
}
