const fs = require('fs');

const path = './resources/js/inertia/components/activity/TaskFormModal.tsx';

const content = `import { useState, useEffect, FormEvent, useMemo } from 'react';
import { Head, Link, useForm, router, usePage } from '@inertiajs/react';
import { motion, AnimatePresence } from 'framer-motion';
import { Dialog } from '@/components/ui/dialog';
import { X, Calendar as CalendarIcon, Clock, Users as UsersIcon, Check } from 'lucide-react';
import { cn } from '@/lib/utils';
import { ArrowLeft, Star, Building2, Plus, Eye } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { showToast } from '@/components/ui/toast';
import type { PageProps, Task, ActivityType, User, TaskStatus, TaskPriority } from '@/types';

interface PrioritizedActivityType extends ActivityType {
    priority: 'favorite' | 'department' | 'other';
}

interface GroupedActivityTypes {
    favorites: PrioritizedActivityType[];
    department: PrioritizedActivityType[];
    others: PrioritizedActivityType[];
}

export interface TaskFormProps extends PageProps {
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

export interface TaskFormModalProps extends TaskFormProps {
    open: boolean;
    onClose: () => void;
    onSuccess?: () => void;
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

export function TaskFormModal({ open, onClose, onSuccess, task, activityTypes, departmentUsers = [], backdateEnabled = false, backdatePermission, allowedDateRange }: TaskFormModalProps) {
    const isEditing = !!task;

    const flatActivityTypes = useMemo(() => {
        if (Array.isArray(activityTypes)) {
            return activityTypes;
        }
        return [
            ...(activityTypes?.favorites || []),
            ...(activityTypes?.department || []),
            ...(activityTypes?.others || [])
        ];
    }, [activityTypes]);

    const defaultStatus = task?.status || 'planned';
    
    // Safety check for allowedDateRange
    const safeAllowedDateRange = allowedDateRange || { from: new Date().toISOString().split('T')[0], to: new Date().toISOString().split('T')[0] };

    const initialTaskDate = task?.task_date ? task.task_date.split('T')[0] : new Date().toISOString().split('T')[0];

    const { data, setData, post, put, processing, errors } = useForm<TaskFormData>({
        task_title: task?.task_title || '',
        task_description: task?.task_description || '',
        activity_type_id: task?.activity_type_id?.toString() || '',
        sub_activity_id: task?.sub_activity_id?.toString() || '',
        status: defaultStatus,
        priority: task?.priority || 'medium',
        task_date: initialTaskDate,
        due_date: task?.due_date ? task.due_date.split('T')[0] : '',
        participant_ids: task?.participants?.map(p => p.id) || [],
        start_time: task?.started_at ? task.started_at.split('T')[1]?.substring(0, 5) || '' : '',
        end_time: task?.completed_at ? task.completed_at.split('T')[1]?.substring(0, 5) || '' : '',
        completed_date: task?.completed_at ? task.completed_at.split('T')[0] : initialTaskDate,
    });

    const sortedSubActivities = useMemo(() => {
        if (!data.activity_type_id) return [];
        const type = flatActivityTypes.find(t => t.id.toString() === data.activity_type_id);
        return type?.sub_activities || [];
    }, [data.activity_type_id, flatActivityTypes]);

    const toggleParticipant = (userId: number) => {
        const currentIds = data.participant_ids || [];
        if (currentIds.includes(userId)) {
            setData('participant_ids', currentIds.filter(id => id !== userId));
        } else {
            setData('participant_ids', [...currentIds, userId]);
        }
    };

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();

        const options = {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                showToast.success(isEditing ? "Task updated successfully" : "Task created successfully");
                if (onSuccess) onSuccess();
                onClose();
            }
        };

        if (isEditing) {
            put(route("activity.task.update", { task: task!.id }), options);
        } else {
            post(route("activity.task.store"), options);
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
     
