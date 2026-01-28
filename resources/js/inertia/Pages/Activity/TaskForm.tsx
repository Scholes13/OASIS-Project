import { useState, useEffect, FormEvent } from 'react';
import { Head, Link, useForm, router } from '@inertiajs/react';
import { motion } from 'framer-motion';
import { ArrowLeft } from 'lucide-react';
import { Button } from '@/components/ui/button';
import type { PageProps, Task, ActivityType, User, TaskStatus, TaskPriority } from '@/types';

interface TaskFormProps extends PageProps {
    task: Task | null;
    activityTypes: ActivityType[];
    departmentUsers?: User[];
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
}

interface BackdateRequestData {
    reason: string;
}

export default function TaskForm({ task, activityTypes, departmentUsers = [], backdatePermission, allowedDateRange }: TaskFormProps) {
    const isEditing = !!task;
    const [showBackdateModal, setShowBackdateModal] = useState(false);

    // Set default status to in_progress for new tasks, or use existing status for edits
    const defaultStatus: TaskStatus = task?.status || 'in_progress';

    const { data, setData, post, put, processing, errors } = useForm<TaskFormData>({
        task_title: task?.task_title || '',
        task_description: task?.task_description || '',
        activity_type_id: task?.activity_type_id?.toString() || '',
        sub_activity_id: task?.sub_activity_id?.toString() || '',
        status: defaultStatus,
        priority: task?.priority || 'medium',
        task_date: task?.task_date ? task.task_date.split('T')[0] : new Date().toISOString().split('T')[0],
        due_date: task?.due_date ? task.due_date.split('T')[0] : '',
        participant_ids: task?.participants?.map(p => p.user_id) || [],
    });

    const { data: backdateData, setData: setBackdateData, post: postBackdate, processing: backdateProcessing, errors: backdateErrors, reset: resetBackdate } = useForm<BackdateRequestData>({
        reason: '',
    });

    const [selectedActivityType, setSelectedActivityType] = useState<ActivityType | null>(
        task?.activity_type || null
    );

    // Update sub-activities when activity type changes
    useEffect(() => {
        if (data.activity_type_id) {
            const actType = activityTypes.find(t => t.id.toString() === data.activity_type_id);
            setSelectedActivityType(actType || null);
            if (!actType?.sub_activities?.find(s => s.id.toString() === data.sub_activity_id)) {
                setData('sub_activity_id', '');
            }
        } else {
            setSelectedActivityType(null);
        }
    }, [data.activity_type_id, activityTypes]);

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();

        if (isEditing) {
            put(route('activity.task.update', { task: task!.id }));
        } else {
            post(route('activity.task.store'));
        }
    };

    const handleBackdateRequest = (e: FormEvent) => {
        e.preventDefault();

        postBackdate(route('activity.backdate.request.submit'), {
            onSuccess: () => {
                setShowBackdateModal(false);
                resetBackdate();
                // Reload page to get updated permission
                router.reload();
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
                                                const parentType = activityTypes.find(t => t.sub_activities?.some(s => s.id.toString() === subId));

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
                                            {activityTypes.map((type) => (
                                                type.sub_activities && type.sub_activities.length > 0 && (
                                                    <optgroup key={type.id} label={type.name}>
                                                        {type.sub_activities.map((sub) => (
                                                            <option key={sub.id} value={sub.id.toString()}>
                                                                {sub.name}
                                                            </option>
                                                        ))}
                                                    </optgroup>
                                                )
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
                                            {activityTypes.map((type) => (
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
                                            onChange={(e) => setData('status', e.target.value as TaskStatus)}
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
                                        {backdatePermission?.is_active ? (
                                            <p className="mt-1 text-xs text-green-600">
                                                ✓ You can backdate up to {backdatePermission.requested_date} (expires {new Date(backdatePermission.granted_until).toLocaleString()})
                                            </p>
                                        ) : (
                                            <p className="mt-1 text-xs text-gray-500">
                                                You can backdate up to {allowedDateRange.from}. Need older dates? <button type="button" onClick={() => setShowBackdateModal(true)} className="text-indigo-600 hover:text-indigo-700 font-medium underline">Request backdate access</button>
                                            </p>
                                        )}
                                    </div>

                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Due Date <span className="text-red-500">*</span>
                                        </label>
                                        <input
                                            type="date"
                                            value={data.due_date}
                                            onChange={(e) => setData('due_date', e.target.value)}
                                            className={`w-full px-3 py-2.5 border rounded-lg focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 bg-white ${errors.due_date ? 'border-red-500' : 'border-gray-200'
                                                }`}
                                        />
                                        {errors.due_date && (
                                            <p className="mt-1 text-sm text-red-600">{errors.due_date}</p>
                                        )}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </motion.div>
            </div>

            {/* Backdate Request Modal */}
            {showBackdateModal && (
                <div className="fixed inset-0 z-[9999] overflow-y-auto">
                    <div className="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                        {/* Background overlay */}
                        <div
                            className="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75"
                            onClick={() => setShowBackdateModal(false)}
                        ></div>

                        {/* Modal panel */}
                        <div className="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full relative z-[10000]">
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
                                                    <p className="mt-1 text-xs text-gray-500">Minimum 10 characters, maximum 500 characters</p>
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
                                                                Once approved, you'll be able to enter tasks with dates older than yesterday. The permission will be valid until the end of the approval day.
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
                                        disabled={backdateProcessing}
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
        </div>
    );
}

