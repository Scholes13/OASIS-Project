import { useState, useEffect, FormEvent, useMemo } from 'react';
import { Head, Link, useForm, router, usePage } from '@inertiajs/react';
import { motion } from 'framer-motion';
import { Dialog } from '@/components/ui/dialog';
import { X, Calendar as CalendarIcon, Clock, Users as UsersIcon, Check } from 'lucide-react';
import { cn } from '@/lib/utils';
import { ArrowLeft, Star, Building2, Plus, Eye } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { showToast } from '@/components/ui/toast';
import type { PageProps, Task, ActivityType, User, TaskStatus, TaskPriority } from '@/types';

const getTodayLocalDate = () => {
    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
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
}

interface BackdateRequestData {
    requested_date: string;
    reason: string;
}

interface Time24InputProps {
    id: string;
    value: string;
    onChange: (value: string) => void;
    hasError?: boolean;
}

const HOUR_OPTIONS = Array.from({ length: 24 }, (_, i) => i.toString().padStart(2, '0'));
const MINUTE_OPTIONS = Array.from({ length: 60 }, (_, i) => i.toString().padStart(2, '0'));

function Time24Input({ id, value, onChange, hasError = false }: Time24InputProps) {
    const [hour = '', minute = ''] = value ? value.split(':') : ['', ''];

    const handleHourChange = (nextHour: string) => {
        if (!nextHour) {
            onChange('');
            return;
        }
        onChange(`${nextHour}:${minute || '00'}`);
    };

    const handleMinuteChange = (nextMinute: string) => {
        if (!hour) {
            onChange('');
            return;
        }
        onChange(`${hour}:${nextMinute || '00'}`);
    };

    const baseClass = `w-full px-3 py-2.5 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary bg-white ${hasError ? 'border-red-500' : 'border-gray-200'}`;

    return (
        <div className="flex gap-1 items-center">
            <select
                id={`${id}-hour`}
                value={hour}
                onChange={(e) => handleHourChange(e.target.value)}
                className={baseClass}
            >
                <option value="">HH</option>
                {HOUR_OPTIONS.map(h => <option key={h} value={h}>{h}</option>)}
            </select>
            <span className="text-slate-400 font-bold">:</span>
            <select
                id={`${id}-minute`}
                value={minute}
                onChange={(e) => handleMinuteChange(e.target.value)}
                className={baseClass}
            >
                <option value="">MM</option>
                {MINUTE_OPTIONS.map(m => <option key={m} value={m}>{m}</option>)}
            </select>
        </div>
    );
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
    const todayLocal = getTodayLocalDate();
    const defaultTaskDate = initialTaskDate || todayLocal;

    const { data, setData, post, put, processing, errors, reset } = useForm<TaskFormData>({
        task_title: task?.task_title || '',
        task_description: task?.task_description || '',
        activity_type_id: task?.activity_type_id?.toString() || '',
        sub_activity_id: task?.sub_activity_id?.toString() || '',
        status: (task?.status as TaskStatus) || 'planned',
        priority: (task?.priority as TaskPriority) || 'medium',
        task_date: task?.task_date || defaultTaskDate,
        due_date: task?.due_date || '',
        participant_ids: task?.participants?.map((p: any) => p.id) || [],
        start_time: task?.start_time || '',
        end_time: task?.end_time || '',
        completed_date: task?.completed_date || defaultTaskDate,
    });

    useEffect(() => {
        if (open) {
            if (task) {
                setData({
                    task_title: task.task_title || '',
                    task_description: task.task_description || '',
                    activity_type_id: task.activity_type_id?.toString() || '',
                    sub_activity_id: task.sub_activity_id?.toString() || '',
                    status: (task.status as TaskStatus) || 'planned',
                    priority: (task.priority as TaskPriority) || 'medium',
                    task_date: task.task_date || getTodayLocalDate(),
                    due_date: task.due_date || '',
                    participant_ids: task.participants?.map((p: any) => p.id) || [],
                    start_time: task.start_time || '',
                    end_time: task.end_time || '',
                    completed_date: task.completed_date || '',
                });
            } else {
                const createTaskDate = initialTaskDate || getTodayLocalDate();

                setData({
                    task_title: '',
                    task_description: '',
                    activity_type_id: '',
                    sub_activity_id: '',
                    status: 'planned',
                    priority: 'medium',
                    task_date: createTaskDate,
                    due_date: '',
                    participant_ids: [],
                    start_time: '',
                    end_time: '',
                    completed_date: createTaskDate,
                });
            }
        }
    }, [open, task, initialTaskDate, reset, setData]);

    const flatActivityTypes = useMemo(() => {
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

        const url = isEditing ? `/activity/task/${task!.id}` : '/activity/task';
        const method = isEditing ? put : post;

        method(url, {
            preserveScroll: true,
            onSuccess: () => {
                showToast.success(
                    isEditing ? 'Task berhasil diperbarui' : 'Task berhasil dibuat',
                    isEditing ? 'Perubahan sudah tersimpan.' : 'Task baru sudah masuk ke daftar.'
                );
                onClose();
                reset();
            },
            onError: (formErrors) => {
                const firstError = Object.values(formErrors)[0];
                showToast.error('Gagal menyimpan task', firstError || 'Periksa kembali input yang wajib diisi.');
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
        <Dialog open={open} onClose={onClose} className="max-w-[720px] w-full p-0 overflow-hidden bg-white rounded-xl shadow-2xl">
            {/* Header */}
            <div className="flex items-center justify-between px-6 py-4 border-b border-slate-100">
                <h2 className="text-[17px] font-bold text-slate-800 tracking-tight">
                    {isEditing ? 'Edit Task' : 'Create New Task'}
                </h2>
                <button 
                    type="button"
                    onClick={onClose}
                    className="p-1.5 rounded-md text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition-colors"
                >
                    <X className="w-5 h-5" />
                </button>
            </div>

            {/* Body */}
            <div className="p-6 max-h-[75vh] overflow-y-auto">
                <form onSubmit={handleSubmit} className="flex flex-col gap-6" id="task-form">
                    
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
                                        setData('status', newStatus);
                                        if (newStatus === 'completed') {
                                            setData(prev => ({ ...prev, status: newStatus, due_date: '' }));
                                        }
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

                    {/* Timeline & Time Log Panel */}
                    <div className="flex flex-col gap-2 mt-2">
                        <h4 className="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Timeline & Time Log</h4>
                        <div className="bg-slate-50 border border-slate-200 rounded-xl p-4 flex flex-col gap-5">
                            
                            {/* Dates */}
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div className="flex flex-col gap-1.5">
                                    <label className="text-[12px] font-medium text-slate-600">Task Date (Start Date)</label>
                                    <div className="relative">
                                        <input
                                            type="date"
                                            value={data.task_date}
                                            onChange={(e) => setData('task_date', e.target.value)}
                                            min={allowedDateRange?.from}
                                            max={allowedDateRange?.to}
                                            className={cn("w-full h-10 pl-9 pr-3 border rounded-md text-[14px] focus:ring-2 focus:ring-[#16599c]/20 outline-none bg-white cursor-pointer", errors.task_date ? "border-rose-300" : "border-slate-200")}
                                        />
                                        <CalendarIcon className="w-4 h-4 text-slate-400 absolute left-3 top-3 pointer-events-none" />
                                    </div>
                                    {errors.task_date && <p className="text-[10px] text-rose-500">{errors.task_date}</p>}
                                    {backdateEnabled && !backdatePermission?.is_active && (
                                        <button type="button" onClick={() => setShowBackdateModal(true)} className="text-[10px] text-[#16599c] text-left hover:underline mt-0.5 w-fit">Need older date?</button>
                                    )}
                                </div>
                                <div className="flex flex-col gap-1.5">
                                    <label htmlFor="task_due_date" className="text-[12px] font-medium text-slate-600">
                                        Due Date
                                        {data.status !== 'completed' && <span className="text-rose-500"> *</span>}
                                    </label>
                                    <div className="relative">
                                        <input
                                            id="task_due_date"
                                            type="date"
                                            value={data.due_date}
                                            onChange={(e) => setData('due_date', e.target.value)}
                                            required={data.status !== 'completed'}
                                            min={data.status === 'completed' ? undefined : (data.task_date || undefined)}
                                            className="w-full h-10 pl-9 pr-3 border border-slate-200 rounded-md text-[14px] focus:ring-2 focus:ring-[#16599c]/20 outline-none bg-white cursor-pointer"
                                        />
                                        <CalendarIcon className="w-4 h-4 text-slate-400 absolute left-3 top-3 pointer-events-none" />
                                    </div>
                                    {errors.due_date && <p className="text-[10px] text-rose-500">{errors.due_date}</p>}
                                </div>
                            </div>

                            {/* Time Log conditionally rendered */}
                            {((data.status === 'completed') || (data.status === 'in_progress' && data.task_date && data.task_date < todayLocal)) && (
                                <div className="grid grid-cols-1 md:grid-cols-3 gap-4 pt-5 border-t border-slate-200/60">
                                    <div className="flex flex-col gap-1.5">
                                        <label className="text-[12px] font-medium text-slate-600">Start Time <span className="text-rose-500">*</span></label>
                                        <div className="relative">
                                            <input
                                                type="time"
                                                value={data.start_time}
                                                onChange={(e) => setData('start_time', e.target.value)}
                                                className={cn("w-full h-10 pl-9 pr-2 border rounded-md text-[14px] outline-none bg-white focus:border-[#16599c] cursor-pointer", errors.start_time ? "border-rose-300" : "border-slate-200")}
                                            />
                                            <Clock className="w-4 h-4 text-slate-400 absolute left-3 top-3 pointer-events-none" />
                                        </div>
                                        {errors.start_time && <p className="text-[10px] text-rose-500">{errors.start_time}</p>}
                                    </div>

                                    {data.status === 'completed' && (
                                        <>
                                            <div className="flex flex-col gap-1.5">
                                                <label className="text-[12px] font-medium text-slate-600">End Time <span className="text-rose-500">*</span></label>
                                                <div className="relative">
                                                    <input
                                                        type="time"
                                                        value={data.end_time}
                                                        onChange={(e) => setData('end_time', e.target.value)}
                                                        className={cn("w-full h-10 pl-9 pr-2 border rounded-md text-[14px] outline-none bg-white focus:border-[#16599c] cursor-pointer", errors.end_time ? "border-rose-300" : "border-slate-200")}
                                                    />
                                                    <Clock className="w-4 h-4 text-slate-400 absolute left-3 top-3 pointer-events-none" />
                                                </div>
                                                {errors.end_time && <p className="text-[10px] text-rose-500">{errors.end_time}</p>}
                                            </div>
                                            <div className="flex flex-col gap-1.5">
                                                <label className="text-[12px] font-medium text-slate-600">Duration (Calc)</label>
                                                <div className="h-10 px-3 border border-slate-200 bg-white rounded-md text-[14px] text-slate-400 flex items-center cursor-not-allowed">
                                                    {data.start_time && data.end_time ? 'Auto-calculated' : 'Fill times...'}
                                                </div>
                                            </div>
                                        </>
                                    )}
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Participants */}
                    <div className="flex flex-col gap-1.5 mt-2">
                        <label className="text-[12px] font-semibold text-slate-700">Participants</label>
                        <div className="flex flex-wrap gap-2 p-3 border border-slate-200 rounded-lg min-h-[52px]">
                            {departmentUsers.map((user) => (
                                <button
                                    key={user.id}
                                    type="button"
                                    onClick={() => toggleParticipant(user.id)}
                                    className={cn(
                                        "flex items-center gap-2 px-2.5 py-1.5 rounded-full text-[12px] font-medium transition-all border",
                                        data.participant_ids.includes(user.id)
                                            ? "bg-slate-100 text-slate-800 border-slate-200 shadow-sm"
                                            : "bg-white text-slate-500 border-dashed border-slate-300 hover:bg-slate-50"
                                    )}
                                >
                                    <div className={cn(
                                        "w-5 h-5 rounded-full flex items-center justify-center text-[9px] font-bold text-white",
                                        data.participant_ids.includes(user.id) ? "bg-[#16599c]" : "bg-slate-300"
                                    )}>
                                        {user.name.charAt(0).toUpperCase()}
                                    </div>
                                    {user.name}
                                </button>
                            ))}
                            {departmentUsers.length === 0 && (
                                <span className="text-sm text-slate-400 py-1">No other team members in department.</span>
                            )}
                        </div>
                    </div>

                    {/* Description */}
                    <div className="flex flex-col gap-1.5 mt-2">
                        <label className="text-[12px] font-semibold text-slate-700">Description (Optional)</label>
                        <textarea
                            value={data.task_description}
                            onChange={(e) => setData('task_description', e.target.value)}
                            rows={3}
                            className="w-full px-3 py-2.5 border border-slate-200 rounded-lg text-[14px] focus:ring-2 focus:ring-[#16599c]/20 focus:border-[#16599c] outline-none resize-y"
                            placeholder="Add details..."
                        />
                    </div>
                </form>
            </div>

            {/* Footer */}
            <div className="flex items-center justify-between px-6 py-4 bg-slate-50 border-t border-slate-200 rounded-b-xl">
                <div className="text-[12px] text-slate-500 flex items-center gap-1.5">
                    {data.status === 'completed' ? (
                        <><Clock className="w-3.5 h-3.5" /> Time log required for completed task.</>
                    ) : (
                        <><span className="w-2 h-2 rounded-full bg-slate-300"></span> Save directly to To Do</>
                    )}
                </div>
                <div className="flex items-center gap-3">
                    <button type="button" onClick={onClose} className="px-4 py-2 text-sm font-medium text-slate-600 hover:text-slate-900 bg-transparent hover:bg-slate-200/50 rounded-md transition-colors">
                        Cancel
                    </button>
                    <button 
                        type="submit" 
                        form="task-form" 
                        disabled={processing}
                        className="bg-[#16599c] hover:bg-[#124a82] text-white border-none shadow-sm text-sm font-medium px-5 py-2 rounded-md transition-colors disabled:opacity-50"
                    >
                        {isEditing ? 'Save Changes' : 'Create Task'}
                    </button>
                </div>
            </div>
        </Dialog>
    );
}
