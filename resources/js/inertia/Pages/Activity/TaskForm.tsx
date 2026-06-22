import { useState, useEffect, FormEvent, useMemo } from 'react';
import { Head, Link, useForm, router, usePage } from '@inertiajs/react';
import { motion } from 'framer-motion';
import { ArrowLeft, Star, Building2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { showToast } from '@/components/ui/toast';
import BackdateRequestModal from '@/components/activity/form/BackdateRequestModal';
import ParticipantSelector from '@/components/activity/form/ParticipantSelector';
import TaskClassificationCard from '@/components/activity/form/TaskClassificationCard';
import TaskScheduleCard from '@/components/activity/form/TaskScheduleCard';
import TaskSuccessModal from '@/components/activity/form/TaskSuccessModal';
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
        if (!activityTypes) return [];
        if (Array.isArray(activityTypes)) {
            return activityTypes;
        }
        // Grouped format from prioritization service
        return [
            ...(activityTypes.favorites || []),
            ...(activityTypes.department || []),
            ...(activityTypes.others || []),
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
            router.visit(route('activity.task.index', { task: createdTaskId, modal: 'detail' }));
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

    const backRoute = isEditing ? route('activity.task.index', { task: task!.id, modal: 'detail' }) : route('activity.task.index');

    return (
        <div className="w-full pb-12">
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
                                            className={`w-full px-4 py-2.5 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all ${errors.task_title ? 'border-red-500 focus:ring-red-500/20' : 'border-gray-200'
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
                                            className="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all resize-y"
                                            placeholder="Provide detailed description, objectives, and any necessary context..."
                                        />
                                    </div>
                                </div>
                            </div>

                            <ParticipantSelector
                                users={departmentUsers}
                                selectedIds={data.participant_ids}
                                onToggle={toggleParticipant}
                            />
                        </div>

                        {/* Sidebar Column (1/3 width) */}
                        <div className="space-y-8">
                            <TaskClassificationCard
                                data={data}
                                activityTypes={flatActivityTypes}
                                subActivities={sortedSubActivities}
                                errors={errors}
                                onChange={setData}
                                onChangeMany={setData}
                            />

                            <TaskScheduleCard
                                data={data}
                                errors={errors}
                                allowedDateRange={allowedDateRange}
                                backdateEnabled={backdateEnabled}
                                backdatePermission={backdatePermission}
                                onChange={setData}
                                onCompletedDateManualChange={() => setCompletedDateManuallySet(true)}
                                onRequestBackdate={() => setShowBackdateModal(true)}
                            />
                        </div>
                    </form>
                </motion.div>
            </div>

            {backdateEnabled && showBackdateModal && (
                <BackdateRequestModal
                    data={backdateData}
                    errors={backdateErrors}
                    processing={backdateProcessing}
                    onChange={setBackdateData}
                    onSubmit={handleBackdateRequest}
                    onClose={() => {
                        setShowBackdateModal(false);
                        resetBackdate();
                    }}
                />
            )}

            {showSuccessModal && (
                <TaskSuccessModal
                    onCreateAnother={handleCreateAnother}
                    onViewTask={handleViewTask}
                    onGoToList={handleGoToList}
                    onClose={() => setShowSuccessModal(false)}
                />
            )}
        </div>
    );
}
