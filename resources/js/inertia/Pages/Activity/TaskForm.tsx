import { useState, useEffect, FormEvent, useMemo } from 'react';
import { Head, Link, useForm, router, usePage } from '@inertiajs/react';
import { motion } from 'framer-motion';
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

    const baseClass = `w-full px-3 py-2.5 border rounded-lg focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 bg-white ${hasError ? 'border-red-500' : 'border-gray-200'}`;

    return (
        <div className="grid grid-cols-2 gap-2">
            <select
                id={`${id}-hour`}
                value={hour}
                onChange={(e) => handleHourChange(e.target.value)}
                className={baseClass}
            >
                <option value="">Jam</option>
                {HOUR_OPTIONS.map((h) => (
                    <option key={h} value={h}>{h}</option>
                ))}
            </select>
            <select
                id={`${id}-minute`}
                value={minute}
                onChange={(e) => handleMinuteChange(e.target.value)}
                disabled={!hour}
                className={baseClass}
            >
                <option value="">Menit</option>
                {MINUTE_OPTIONS.map((m) => (
                    <option key={m} value={m}>{m}</option>
                ))}
            </select>
        </div>
    );
}

export default function TaskForm({ task, activityTypes, departmentUsers = [], backdateEnabled = false, backdatePermission, allowedDateRange }: TaskFormProps) {
    const isEditing = !!task;
    const [showBackdateModal, setShowBackdateModal] = useState(false);
    const [showSuccessModal, setShowSuccessModal] = useState(false);
    const [createdTaskId, setCreatedTaskId] = useState<number | null>(null);

    // Get flash data from page props
    const { flash } = usePage<{ flash: { created_task_id?: number; success?: string } }>().props;

    // Check for created_task_id on mount/update
    useEffect(() => {
        if (flash?.created_task_id && !isEditing) {
            setCreatedTaskId(flash.created_task_id);
            setShowSuccessModal(true);
        }
    }, [flash?.created_task_id, isEditing]);

    // Normalize activity types - handle both grouped and flat array formats
    const flatActivityTypes = useMemo(() => {
        if (Array.isArray(activityTypes)) {
            return activityTypes;
        }
        // Grouped format from prioritization service
        return [
            ...activityTypes.favorites,
            ...activityTypes.department,
            ...activityTypes.others,
        ];
    }, [activityTypes]);

    // Get all sub activities sorted alphabetically for dropdown
    const sortedSubActivities = useMemo(() => {
        const allSubs: Array<{ id: number; name: string; activityTypeId: number; activityTypeName: string }> = [];

        flatActivityTypes.forEach((type) => {
            if (type.sub_activities && type.sub_activities.length > 0) {
                type.sub_activities.forEach((sub) => {
                    allSubs.push({
                        id: sub.id,
                        name: sub.name,
                        activityTypeId: type.id,
                        activityTypeName: type.name,
                    });
                });
            }
        });

        return allSubs.sort((a, b) => a.name.localeCompare(b.name, 'id'));
    }, [flatActivityTypes]);

    // Check if we have grouped format
    const isGrouped = !Array.isArray(activityTypes);

    // Set default status to in_progress for new tasks, or use existing status for edits
    const defaultStatus: TaskStatus = task?.status || 'in_progress';

    // Debug: log participant data on mount
    console.log('TaskForm init:', {
        taskId: task?.id,
        taskParticipants: task?.participants,
        mappedIds: task?.participants?.map(p => ({ id: p.id, type: typeof p.id })),
        departmentUsers: departmentUsers?.map(u => ({ id: u.id, name: u.name })),
    });

    // Filter out owner from participant_ids for edit mode
    // Owner is handled separately in backend, and owner is excluded from departmentUsers
    // Only include IDs that exist in departmentUsers to avoid validation errors
    const departmentUserIds = departmentUsers?.map(u => u.id) || [];
    const initialParticipantIds = isEditing
        ? (task?.participants
            ?.map(p => Number(p.id))
            .filter(id => !isNaN(id) && id > 0 && departmentUserIds.includes(id)) || [])
        : [];

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
        participant_ids: initialParticipantIds,
        start_time: task?.started_at ? task.started_at.split('T')[1]?.substring(0, 5) || '' : '',
        end_time: task?.completed_at ? task.completed_at.split('T')[1]?.substring(0, 5) || '' : '',
        completed_date: task?.completed_at ? task.completed_at.split('T')[0] : initialTaskDate,
    });

    const { data: backdateData, setData: setBackdateData, post: postBackdate, processing: backdateProcessing, errors: backdateErrors, reset: resetBackdate } = useForm<BackdateRequestData>({
        requested_date: '',
        reason: '',
    });

    const [selectedActivityType, setSelectedActivityType] = useState<ActivityType | null>(
        task?.activity_type || null
    );

    // Track if completed_date was manually changed
    const [completedDateManuallySet, setCompletedDateManuallySet] = useState(false);

    // Auto-sync completed_date when task_date changes (unless manually set)
    useEffect(() => {
        if (!completedDateManuallySet && data.task_date) {
            setData('completed_date', data.task_date);
        }
    }, [data.task_date]);

    // Update sub-activities when activity type changes
    useEffect(() => {
        if (data.activity_type_id) {
            const actType = flatActivityTypes.find(t => t.id.toString() === data.activity_type_id);
            setSelectedActivityType(actType || null);
            if (!actType?.sub_activities?.find(s => s.id.toString() === data.sub_activity_id)) {
                setData('sub_activity_id', '');
            }
        } else {
            setSelectedActivityType(null);
        }
    }, [data.activity_type_id, flatActivityTypes]);

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();

        const options = {
            onError: (errors: Record<string, string>) => {
                // Show first validation error as toast
                const firstError = Object.values(errors)[0];
                if (firstError) {
                    showToast.error('Gagal menyimpan task', firstError);
                } else {
                    showToast.error('Gagal menyimpan task', 'Terjadi kesalahan, silakan coba lagi');
                }
            },
            preserveScroll: true,
        };

        if (isEditing) {
            put(route('activity.task.update', { task: task!.id }), options);
        } else {
            post(route('activity.task.store'), options);
        }
    };

    const handleCreateAnother = () => {
        setShowSuccessModal(false);
        setCreatedTaskId(null);
        setCompletedDateManuallySet(false);
        // Reset form data
        const todayStr = new Date().toISOString().split('T')[0];
        setData({
            task_title: '',
            task_description: '',
            activity_type_id: '',
            sub_activity_id: '',
            status: 'in_progress',
            priority: 'medium',
            task_date: todayStr,
            due_date: '',
            participant_ids: [],
            start_time: '',
            end_time: '',
            completed_date: todayStr,
        });
        showToast.success('Form direset, silakan buat task baru');
    };

    const handleViewTask = () => {
        if (createdTaskId) {
            router.visit(route('activity.task.show', { task: createdTaskId }));
        }
    };

    const handleGoToList = () => {
        router.visit(route('activity.task.index'));
    };

    const handleBackdateRequest = (e: FormEvent) => {
        e.preventDefault();

        postBackdate(route('activity.backdate.request.submit'), {
            onSuccess: () => {
                setShowBackdateModal(false);
                resetBackdate();
                showToast.success('Permintaan backdate berhasil dikirim');
                // Reload page to get updated permission
                router.reload();
            },
            onError: (errors: Record<string, string>) => {
                const firstError = Object.values(errors)[0];
                showToast.error('Gagal mengirim permintaan', firstError || 'Terjadi kesalahan');
            },
        });
    };

    const toggleParticipant = (userId: number) => {
        const current = data.participant_ids;
        if (current.includes(userId)) {
            setData('participant_ids', current.filter(id => id !== userId));
        } else {
            setData('participant_ids', [...current, userId]);
        }
    };

    const backRoute = isEditing ? route('activity.task.show', { task: task!.id }) : route('activity.task.index');

    return (
        <div className="min-h-screen bg-slate-50/50 pb-12">
            <Head title={isEditing ? `Edit: ${task?.task_title}` : 'New Task'} />

            {/* Top Navigation Bar */}
            <div className="bg-white border-b border-gray-200 sticky top-0 z-30">
                <div className="max-w-full mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="flex items-center justify-between h-16">
                        <div className="flex items-center gap-4">
                            <Link
                                href={backRoute}
                                className="p-2 rounded-full text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors"
                            >
                                <ArrowLeft className="w-5 h-5" />
                            </Link>
                            <div className="h-6 w-px bg-gray-200 mx-2 hidden sm:block"></div>
                            <nav className="hidden sm:flex" aria-label="Breadcrumb">
                                <ol className="flex items-center space-x-2">
                                    <li>
                                        <Link href={route('activity.task.index')} className="text-sm font-medium text-gray-500 hover:text-gray-700">Tasks</Link>
                                    </li>
                                    <li>
                                        <div className="flex items-center">
                                            <span className="text-gray-300 mx-2">/</span>
                                            <span className="text-sm font-medium text-gray-900" aria-current="page">
                                                {isEditing ? 'Edit Task' : 'New Task'}
                                            </span>
                                        </div>
                                    </li>
                                </ol>
                            </nav>
                        </div>

                        <div className="flex items-center gap-3">
                            <Link href={backRoute}>
                                <Button variant="secondary" size="sm" className="shadow-sm border-gray-300">
                                    Cancel
                                </Button>
                            </Link>
                            <Button
                                onClick={handleSubmit}
                                isLoading={processing}
                                size="sm"
                                className="shadow-sm"
                            >
                                {isEditing ? 'Update Task' : 'Create Task'}
                            </Button>
                        </div>
                    </div>
                </div>
            </div>

            <div className="max-w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <motion.div
                    initial={{ opacity: 0, y: 20 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{ duration: 0.3 }}
                >
                    <div className="mb-8">
                        <h1 className="text-3xl font-bold text-gray-900 tracking-tight leading-tight mb-2">
                            {isEditing ? 'Edit Task' : 'Create New Task'}
                        </h1>
                        <p className="text-lg text-gray-500">
                            Fill in the details below to {isEditing ? 'update the' : 'create a new'} task.
                        </p>
                    </div>

                    <form onSubmit={handleSubmit} className="grid grid-cols-1 xl:grid-cols-3 gap-8">
                        {/* Main Content Column (2/3 width) */}
                        <div className="xl:col-span-2 space-y-8">
                            {/* Basic Info Card */}
                            <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                                <div className="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                                    <h3 className="text-lg font-semibold text-gray-900">Task Information</h3>
                                </div>
                                <div className="p-6 md:p-8 space-y-6">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Task Title <span className="text-red-500">*</span>
                                        </label>
                                        <input
                                            type="text"
                                            value={data.task_title}
                                            onChange={(e) => setData('task_title', e.target.value)}
                                            className={`w-full px-4 py-2.5 border rounded-lg focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all ${errors.task_title ? 'border-red-500 focus:ring-red-500/20' : 'border-gray-200'
                                                }`}
                                            placeholder="e.g., Q3 Financial Report Review"
                                        />
                                        {errors.task_title && (
                                            <p className="mt-1 text-sm text-red-600">{errors.task_title}</p>
                                        )}
                                    </div>

                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Description
                                        </label>
                                        <textarea
                                            value={data.task_description}
                                            onChange={(e) => setData('task_description', e.target.value)}
                                            rows={6}
                                            className="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all resize-y"
                                            placeholder="Provide detailed description, objectives, and any necessary context..."
                                        />
                                    </div>
                                </div>
                            </div>

                            {/* Participants Card */}
                            {departmentUsers.length > 0 && (
                                <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                                    <div className="px-6 py-4 border-b border-gray-100 bg-gray-50/50 flex items-center justify-between">
                                        <h3 className="text-lg font-semibold text-gray-900">Participants</h3>
                                        {data.participant_ids.length > 0 && (
                                            <span className="px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-700">
                                                {data.participant_ids.length} Selected
                                            </span>
                                        )}
                                    </div>
                                    <div className="p-6">
                                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                            {departmentUsers.map((user) => (
                                                <label
                                                    key={user.id}
                                                    className={`relative flex items-center gap-4 p-3 rounded-xl border transition-all cursor-pointer group ${data.participant_ids.includes(user.id)
                                                        ? 'border-indigo-500 bg-indigo-50/50 ring-1 ring-indigo-500'
                                                        : 'border-gray-200 hover:border-gray-300 hover:bg-gray-50'
                                                        }`}
                                                >
                                                    <input
                                                        type="checkbox"
                                                        checked={data.participant_ids.includes(user.id)}
                                                        onChange={() => toggleParticipant(user.id)}
                                                        className="sr-only"
                                                    />
                                                    <div className={`w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold transition-colors ${data.participant_ids.includes(user.id)
                                                        ? 'bg-indigo-600 text-white'
                                                        : 'bg-gray-100 text-gray-500 group-hover:bg-gray-200'
                                                        }`}>
                                                        {user.name.charAt(0).toUpperCase()}
                                                    </div>
                                                    <div className="flex-1 min-w-0">
                                                        <p className={`font-medium truncate ${data.participant_ids.includes(user.id) ? 'text-indigo-900' : 'text-gray-900'
                                                            }`}>
                                                            {user.name}
                                                        </p>
                                                        <p className="text-xs text-gray-500 truncate">{user.email}</p>
                                                    </div>
                                                    {data.participant_ids.includes(user.id) && (
                                                        <div className="absolute top-3 right-3 w-2 h-2 rounded-full bg-indigo-500"></div>
                                                    )}
                                                </label>
                                            ))}
                                        </div>
                                    </div>
                                </div>
                            )}
                        </div>

                        {/* Sidebar Column (1/3 width) */}
                        <div className="space-y-8">
                            {/* Classification Card */}
                            <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                                <div className="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                                    <h3 className="text-lg font-semibold text-gray-900">Classification</h3>
                                </div>
                                <div className="p-6 space-y-5">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Sub Activity <span className="text-red-500">*</span>
                                        </label>
                                        <select
                                            value={data.sub_activity_id}
                                            onChange={(e) => {
                                                const subId = e.target.value;
                                                const parentType = flatActivityTypes.find(t => t.sub_activities?.some(s => s.id.toString() === subId));

                                                if (subId && parentType) {
                                                    setData({
                                                        ...data,
                                                        activity_type_id: parentType.id.toString(),
                                                        sub_activity_id: subId
                                                    });
                                                } else {
                                                    setData('sub_activity_id', subId);
                                                }
                                            }}
                                            className="w-full px-3 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 bg-white"
                                        >
                                            <option value="">Select sub activity...</option>
                                            {sortedSubActivities.map((sub) => (
                                                <option key={sub.id} value={sub.id.toString()}>
                                                    {sub.name}
                                                </option>
                                            ))}
                                        </select>
                                    </div>

                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Activity Type <span className="text-gray-400 text-xs font-normal">(Auto-filled)</span>
                                        </label>
                                        <select
                                            value={data.activity_type_id}
                                            onChange={(e) => setData('activity_type_id', e.target.value)}
                                            className={`w-full px-3 py-2.5 border rounded-lg focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 bg-gray-50 ${errors.activity_type_id ? 'border-red-500' : 'border-gray-200'
                                                }`}
                                        >
                                            <option value="">Select type...</option>
                                            {flatActivityTypes.map((type) => (
                                                <option key={type.id} value={type.id.toString()}>
                                                    {type.name}
                                                </option>
                                            ))}
                                        </select>
                                        {errors.activity_type_id && (
                                            <p className="mt-1 text-sm text-red-600">{errors.activity_type_id}</p>
                                        )}
                                    </div>
                                </div>
                            </div>

                            {/* Status & Priority Card */}
                            <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                                <div className="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                                    <h3 className="text-lg font-semibold text-gray-900">Status & Priority</h3>
                                </div>
                                <div className="p-6 space-y-5">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Status <span className="text-red-500">*</span>
                                        </label>
                                        <select
                                            value={data.status}
                                            onChange={(e) => {
                                                const newStatus = e.target.value as TaskStatus;
                                                setData('status', newStatus);
                                                // Clear due_date when switching to completed (no deadline needed)
                                                if (newStatus === 'completed') {
                                                    setData(prev => ({ ...prev, status: newStatus, due_date: '' }));
                                                }
                                            }}
                                            className="w-full px-3 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 bg-white"
                                        >
                                            <option value="planned">Planned</option>
                                            <option value="in_progress">In Progress</option>
                                            <option value="completed">Completed</option>
                                            <option value="cancelled">Cancelled</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Priority <span className="text-red-500">*</span>
                                        </label>
                                        <div className="grid grid-cols-3 gap-2">
                                            {['low', 'medium', 'high'].map((p) => (
                                                <button
                                                    key={p}
                                                    type="button"
                                                    onClick={() => setData('priority', p as TaskPriority)}
                                                    className={`px-3 py-2 text-sm font-medium rounded-lg border transition-all ${data.priority === p
                                                        ? p === 'high'
                                                            ? 'bg-red-50 border-red-200 text-red-700 ring-1 ring-red-200'
                                                            : p === 'medium'
                                                                ? 'bg-orange-50 border-orange-200 text-orange-700 ring-1 ring-orange-200'
                                                                : 'bg-blue-50 border-blue-200 text-blue-700 ring-1 ring-blue-200'
                                                        : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50'
                                                        }`}
                                                >
                                                    {p.charAt(0).toUpperCase() + p.slice(1)}
                                                </button>
                                            ))}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {/* Schedule Card */}
                            <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                                <div className="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                                    <h3 className="text-lg font-semibold text-gray-900">Schedule</h3>
                                </div>
                                <div className="p-6 space-y-5">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Task Date <span className="text-red-500">*</span>
                                        </label>
                                        <input
                                            type="date"
                                            value={data.task_date}
                                            onChange={(e) => setData('task_date', e.target.value)}
                                            min={allowedDateRange.from}
                                            max={allowedDateRange.to}
                                            className={`w-full px-3 py-2.5 border rounded-lg focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 bg-white ${errors.task_date ? 'border-red-500' : 'border-gray-200'
                                                }`}
                                        />
                                        {errors.task_date && (
                                            <p className="mt-1 text-sm text-red-600">{errors.task_date}</p>
                                        )}
                                        {backdateEnabled && (
                                            backdatePermission?.is_active ? (
                                                <p className="mt-1 text-xs text-green-600">
                                                    ✓ You can backdate up to {backdatePermission.requested_date} (expires {new Date(backdatePermission.granted_until).toLocaleString()})
                                                </p>
                                            ) : (
                                                <p className="mt-1 text-xs text-gray-500">
                                                    You can backdate up to {allowedDateRange.from}. Need older dates? <button type="button" onClick={() => setShowBackdateModal(true)} className="text-indigo-600 hover:text-indigo-700 font-medium underline">Request backdate access</button>
                                                </p>
                                            )
                                        )}
                                    </div>

                                    {/* Time Fields - Show for completed OR backdate in_progress */}
                                    {(() => {
                                        const today = new Date().toISOString().split('T')[0];
                                        const isBackdate = data.task_date && data.task_date < today;
                                        const showTimeFields = data.status === 'completed' || (data.status === 'in_progress' && isBackdate);

                                        if (!showTimeFields) return null;

                                        return (
                                            <>
                                                <div className="bg-amber-50 border border-amber-200 rounded-lg p-3">
                                                    <p className="text-sm text-amber-700">
                                                        <span className="font-medium">
                                                            {data.status === 'in_progress' ? 'Backdate In Progress:' : 'Status Completed:'}
                                                        </span>{' '}
                                                        {data.status === 'in_progress'
                                                            ? 'Silakan isi waktu mulai untuk task backdate.'
                                                            : 'Silakan isi tanggal selesai, waktu mulai dan waktu selesai.'
                                                        }
                                                    </p>
                                                </div>

                                                <div>
                                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                                        Waktu Mulai <span className="text-red-500">*</span>
                                                    </label>
                                                    <Time24Input
                                                        id="start_time"
                                                        value={data.start_time}
                                                        onChange={(value) => setData('start_time', value)}
                                                        hasError={!!errors.start_time}
                                                    />
                                                    {errors.start_time && (
                                                        <p className="mt-1 text-sm text-red-600">{errors.start_time}</p>
                                                    )}
                                                    <p className="mt-1 text-xs text-gray-500">
                                                        Format 24 jam (00:00-23:59). Tanggal mulai: {data.task_date || '-'}
                                                    </p>
                                                </div>

                                                {/* Tanggal Selesai + Waktu Selesai - show for completed status */}
                                                {data.status === 'completed' && (
                                                    <>
                                                        <div>
                                                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                                                Tanggal Selesai <span className="text-red-500">*</span>
                                                            </label>
                                                            <input
                                                                type="date"
                                                                value={data.completed_date}
                                                                onChange={(e) => {
                                                                    setData('completed_date', e.target.value);
                                                                    setCompletedDateManuallySet(true);
                                                                }}
                                                                min={data.task_date}
                                                                max={allowedDateRange.to}
                                                                className={`w-full px-3 py-2.5 border rounded-lg focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 bg-white ${errors.completed_date ? 'border-red-500' : 'border-gray-200'
                                                                    }`}
                                                            />
                                                            {errors.completed_date && (
                                                                <p className="mt-1 text-sm text-red-600">{errors.completed_date}</p>
                                                            )}
                                                            <p className="mt-1 text-xs text-gray-500">
                                                                Default sama dengan tanggal task. Ubah jika selesai di hari berbeda.
                                                            </p>
                                                        </div>

                                                        <div>
                                                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                                                Waktu Selesai <span className="text-red-500">*</span>
                                                            </label>
                                                            <Time24Input
                                                                id="end_time"
                                                                value={data.end_time}
                                                                onChange={(value) => setData('end_time', value)}
                                                                hasError={!!errors.end_time}
                                                            />
                                                            {errors.end_time && (
                                                                <p className="mt-1 text-sm text-red-600">{errors.end_time}</p>
                                                            )}
                                                            <p className="mt-1 text-xs text-gray-500">
                                                                Format 24 jam (00:00-23:59)
                                                            </p>
                                                            {data.completed_date && data.completed_date === data.task_date && (
                                                                <p className="mt-1 text-xs text-gray-500">
                                                                    Waktu selesai harus setelah waktu mulai
                                                                </p>
                                                            )}
                                                        </div>
                                                    </>
                                                )}

                                                {/* Duration Preview - for completed tasks */}
                                                {data.status === 'completed' && data.start_time && data.end_time && data.completed_date && (
                                                    <div className="bg-indigo-50 border border-indigo-200 rounded-lg p-3">
                                                        <p className="text-sm text-indigo-700">
                                                            <span className="font-medium">Durasi:</span>{' '}
                                                            {(() => {
                                                                const startDate = new Date(`${data.task_date}T${data.start_time}`);
                                                                const endDate = new Date(`${data.completed_date}T${data.end_time}`);
                                                                const diffMs = endDate.getTime() - startDate.getTime();
                                                                if (diffMs < 0) return 'Invalid (waktu selesai harus setelah waktu mulai)';
                                                                const totalMins = Math.floor(diffMs / 60000);
                                                                const days = Math.floor(totalMins / (24 * 60));
                                                                const hours = Math.floor((totalMins % (24 * 60)) / 60);
                                                                const mins = totalMins % 60;
                                                                const parts: string[] = [];
                                                                if (days > 0) parts.push(`${days} hari`);
                                                                if (hours > 0) parts.push(`${hours} jam`);
                                                                if (mins > 0 || parts.length === 0) parts.push(`${mins} menit`);
                                                                return parts.join(' ');
                                                            })()}
                                                        </p>
                                                    </div>
                                                )}
                                            </>
                                        );
                                    })()}

                                    {/* Due Date - hidden when status is completed (task already done, no deadline needed) */}
                                    {data.status !== 'completed' && (
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Due Date <span className="text-red-500">*</span>
                                        </label>
                                        <input
                                            type="date"
                                            value={data.due_date}
                                            onChange={(e) => setData('due_date', e.target.value)}
                                            min={allowedDateRange.from}
                                            max={allowedDateRange.to}
                                            className={`w-full px-3 py-2.5 border rounded-lg focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 bg-white ${errors.due_date ? 'border-red-500' : 'border-gray-200'
                                                }`}
                                        />
                                        {errors.due_date && (
                                            <p className="mt-1 text-sm text-red-600">{errors.due_date}</p>
                                        )}
                                        {backdateEnabled && (
                                            backdatePermission?.is_active ? (
                                                <p className="mt-1 text-xs text-green-600">
                                                    ✓ You can backdate up to {backdatePermission.requested_date} (expires {new Date(backdatePermission.granted_until).toLocaleString()})
                                                </p>
                                            ) : (
                                                <p className="mt-1 text-xs text-gray-500">
                                                    You can backdate up to yesterday. <button type="button" onClick={() => setShowBackdateModal(true)} className="text-indigo-600 hover:text-indigo-700 underline">Request more</button>
                                                </p>
                                            )
                                        )}
                                    </div>
                                    )}
                                </div>
                            </div>
                        </div>
                    </form>
                </motion.div>
            </div>

            {/* Backdate Request Modal */}
            {backdateEnabled && showBackdateModal && (
                <div className="fixed inset-0 z-[9999] overflow-y-auto">
                    <div className="flex items-center justify-center min-h-screen px-4 text-center">
                        {/* Background overlay */}
                        <div
                            className="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75"
                            onClick={() => setShowBackdateModal(false)}
                        ></div>

                        {/* Modal panel - centered */}
                        <div className="relative bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:max-w-lg sm:w-full z-[10000]">
                            <form onSubmit={handleBackdateRequest}>
                                <div className="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                    <div className="sm:flex sm:items-start">
                                        <div className="mt-3 text-center sm:mt-0 sm:text-left w-full">
                                            <h3 className="text-lg leading-6 font-medium text-gray-900">
                                                Request Backdate Access
                                            </h3>
                                            <p className="mt-2 text-sm text-gray-600">
                                                Request permission to enter tasks with older dates. Your department head will review and approve your request.
                                            </p>
                                            <div className="mt-4 space-y-4">
                                                {/* Requested Date */}
                                                <div>
                                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                                        Backdate To <span className="text-red-500">*</span>
                                                    </label>
                                                    <input
                                                        type="date"
                                                        value={backdateData.requested_date}
                                                        onChange={(e) => setBackdateData('requested_date', e.target.value)}
                                                        max={new Date(Date.now() - 2 * 24 * 60 * 60 * 1000).toISOString().split('T')[0]}
                                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                                    />
                                                    {backdateErrors.requested_date && (
                                                        <p className="mt-1 text-sm text-red-600">{backdateErrors.requested_date}</p>
                                                    )}
                                                    <p className="mt-1 text-xs text-gray-500">
                                                        Select the earliest date you need to create tasks for
                                                    </p>
                                                </div>

                                                {/* Reason */}
                                                <div>
                                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                                        Reason <span className="text-red-500">*</span>
                                                    </label>
                                                    <textarea
                                                        value={backdateData.reason}
                                                        onChange={(e) => setBackdateData('reason', e.target.value)}
                                                        rows={4}
                                                        placeholder="Explain why you need backdate access..."
                                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                                    />
                                                    {backdateErrors.reason && (
                                                        <p className="mt-1 text-sm text-red-600">{backdateErrors.reason}</p>
                                                    )}
                                                    <div className="mt-1 flex justify-between text-xs text-gray-500">
                                                        <span>Minimum 10 characters</span>
                                                        <span>{backdateData.reason.trim().length} characters</span>
                                                    </div>
                                                </div>

                                                {/* Info Box */}
                                                <div className="bg-blue-50 border border-blue-200 rounded-lg p-3">
                                                    <div className="flex">
                                                        <div className="flex-shrink-0">
                                                            <svg className="h-5 w-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                            </svg>
                                                        </div>
                                                        <div className="ml-3">
                                                            <p className="text-sm text-blue-700">
                                                                Once approved, you'll be able to enter tasks from the selected date until today. The permission will be valid until the end of the approval day.
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div className="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                                    <button
                                        type="submit"
                                        disabled={backdateProcessing || backdateData.reason.trim().length < 10 || !backdateData.requested_date}
                                        className="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        {backdateProcessing ? 'Submitting...' : 'Submit Request'}
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => {
                                            setShowBackdateModal(false);
                                            resetBackdate();
                                        }}
                                        className="mt-3 w-full inline-flex justify-center rounded-lg border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:w-auto sm:text-sm"
                                    >
                                        Cancel
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            )}

            {/* Success Modal - Create Another */}
            {showSuccessModal && (
                <div className="fixed inset-0 z-[9999] overflow-y-auto">
                    <div className="flex items-center justify-center min-h-screen px-4 text-center">
                        <div
                            className="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75"
                            onClick={() => setShowSuccessModal(false)}
                        ></div>

                        <div className="relative bg-white rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:max-w-md sm:w-full z-[10000]">
                            <div className="bg-white px-6 pt-6 pb-4">
                                <div className="text-center">
                                    <div className="mx-auto flex items-center justify-center h-14 w-14 rounded-full bg-emerald-100 mb-4">
                                        <svg className="h-7 w-7 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                    </div>
                                    <h3 className="text-xl font-semibold text-gray-900 mb-2">
                                        Task Berhasil Dibuat!
                                    </h3>
                                    <p className="text-sm text-gray-500">
                                        Apa yang ingin Anda lakukan selanjutnya?
                                    </p>
                                </div>
                            </div>
                            <div className="bg-gray-50 px-6 py-4 space-y-3">
                                <button
                                    onClick={handleCreateAnother}
                                    className="w-full inline-flex items-center justify-center gap-2 rounded-lg border border-transparent shadow-sm px-4 py-2.5 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors"
                                >
                                    <Plus className="h-5 w-5" />
                                    Buat Task Lagi
                                </button>
                                <button
                                    onClick={handleViewTask}
                                    className="w-full inline-flex items-center justify-center gap-2 rounded-lg border border-gray-300 shadow-sm px-4 py-2.5 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors"
                                >
                                    <Eye className="h-5 w-5" />
                                    Lihat Task
                                </button>
                                <button
                                    onClick={handleGoToList}
                                    className="w-full inline-flex items-center justify-center gap-2 rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-500 hover:text-gray-700 hover:bg-gray-100 transition-colors"
                                >
                                    Kembali ke Daftar Task
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}
