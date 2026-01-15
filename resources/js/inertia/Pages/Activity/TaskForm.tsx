import { useState, useEffect, FormEvent } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { motion } from 'framer-motion';
import { ArrowLeft } from 'lucide-react';
import { Button } from '@/components/ui/Button';
import { Card, CardBody } from '@/components/ui/Card';
import type { PageProps, Task, ActivityType, User, TaskStatus, TaskPriority } from '@/types';

interface TaskFormProps extends PageProps {
    task: Task | null;
    activityTypes: ActivityType[];
    departmentUsers?: User[];
}

interface TaskFormData {
    task_title: string;
    task_description: string;
    activity_type_id: string;
    sub_activity_id: string;
    status: TaskStatus;
    priority: TaskPriority;
    due_date: string;
    start_date: string;
    participant_ids: number[];
}

export default function TaskForm({ task, activityTypes, departmentUsers = [] }: TaskFormProps) {
    const isEditing = !!task;

    const { data, setData, post, put, processing, errors, reset } = useForm<TaskFormData>({
        task_title: task?.task_title || '',
        task_description: task?.task_description || '',
        activity_type_id: task?.activity_type_id?.toString() || '',
        sub_activity_id: task?.sub_activity_id?.toString() || '',
        status: task?.status || 'planned',
        priority: task?.priority || 'medium',
        due_date: task?.due_date ? task.due_date.split('T')[0] : '',
        start_date: task?.start_date ? task.start_date.split('T')[0] : '',
        participant_ids: task?.participants?.map(p => p.user_id) || [],
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

    const toggleParticipant = (userId: number) => {
        const current = data.participant_ids;
        if (current.includes(userId)) {
            setData('participant_ids', current.filter(id => id !== userId));
        } else {
            setData('participant_ids', [...current, userId]);
        }
    };

    return (
        <>
            <Head title={isEditing ? `Edit: ${task?.task_title}` : 'New Task'} />

            <div className="py-6">
                <div className="px-4 sm:px-6 lg:px-8 max-w-3xl mx-auto">
                    {/* Back Button */}
                    <div className="mb-6">
                        <Link
                            href={isEditing ? route('activity.task.show', { task: task!.id }) : route('activity.task.index')}
                            className="inline-flex items-center text-sm text-gray-600 hover:text-gray-900"
                        >
                            <ArrowLeft className="w-4 h-4 mr-2" />
                            {isEditing ? 'Back to Task' : 'Back to Tasks'}
                        </Link>
                    </div>

                    <motion.div
                        initial={{ opacity: 0, y: 20 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{ duration: 0.3 }}
                    >
                        <Card>
                            <CardBody>
                                <h1 className="text-xl font-bold text-gray-900 mb-6">
                                    {isEditing ? 'Edit Task' : 'Create New Task'}
                                </h1>

                                <form onSubmit={handleSubmit} className="space-y-6">
                                    {/* Task Title */}
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Task Title <span className="text-red-500">*</span>
                                        </label>
                                        <input
                                            type="text"
                                            value={data.task_title}
                                            onChange={(e) => setData('task_title', e.target.value)}
                                            className={`w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 ${
                                                errors.task_title ? 'border-red-500' : 'border-gray-300'
                                            }`}
                                            placeholder="Enter task title..."
                                        />
                                        {errors.task_title && (
                                            <p className="mt-1 text-sm text-red-600">{errors.task_title}</p>
                                        )}
                                    </div>

                                    {/* Activity Type & Sub Activity */}
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                                Activity Type <span className="text-red-500">*</span>
                                            </label>
                                            <select
                                                value={data.activity_type_id}
                                                onChange={(e) => setData('activity_type_id', e.target.value)}
                                                className={`w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 ${
                                                    errors.activity_type_id ? 'border-red-500' : 'border-gray-300'
                                                }`}
                                            >
                                                <option value="">Select type...</option>
                                                {activityTypes.map((type) => (
                                                    <option key={type.id} value={type.id}>
                                                        {type.name}
                                                    </option>
                                                ))}
                                            </select>
                                            {errors.activity_type_id && (
                                                <p className="mt-1 text-sm text-red-600">{errors.activity_type_id}</p>
                                            )}
                                        </div>

                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                                Sub Activity
                                            </label>
                                            <select
                                                value={data.sub_activity_id}
                                                onChange={(e) => setData('sub_activity_id', e.target.value)}
                                                disabled={!selectedActivityType?.sub_activities?.length}
                                                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 disabled:bg-gray-100"
                                            >
                                                <option value="">Select sub activity...</option>
                                                {selectedActivityType?.sub_activities?.map((sub) => (
                                                    <option key={sub.id} value={sub.id}>
                                                        {sub.name}
                                                    </option>
                                                ))}
                                            </select>
                                        </div>
                                    </div>

                                    {/* Status & Priority */}
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                                Status <span className="text-red-500">*</span>
                                            </label>
                                            <select
                                                value={data.status}
                                                onChange={(e) => setData('status', e.target.value as TaskStatus)}
                                                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
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
                                            <select
                                                value={data.priority}
                                                onChange={(e) => setData('priority', e.target.value as TaskPriority)}
                                                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                            >
                                                <option value="low">Low</option>
                                                <option value="medium">Medium</option>
                                                <option value="high">High</option>
                                            </select>
                                        </div>
                                    </div>

                                    {/* Dates */}
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                                Start Date
                                            </label>
                                            <input
                                                type="date"
                                                value={data.start_date}
                                                onChange={(e) => setData('start_date', e.target.value)}
                                                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                            />
                                        </div>

                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                                Due Date <span className="text-red-500">*</span>
                                            </label>
                                            <input
                                                type="date"
                                                value={data.due_date}
                                                onChange={(e) => setData('due_date', e.target.value)}
                                                className={`w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 ${
                                                    errors.due_date ? 'border-red-500' : 'border-gray-300'
                                                }`}
                                            />
                                            {errors.due_date && (
                                                <p className="mt-1 text-sm text-red-600">{errors.due_date}</p>
                                            )}
                                        </div>
                                    </div>

                                    {/* Description */}
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Description
                                        </label>
                                        <textarea
                                            value={data.task_description}
                                            onChange={(e) => setData('task_description', e.target.value)}
                                            rows={4}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                            placeholder="Enter task description..."
                                        />
                                    </div>

                                    {/* Participants */}
                                    {departmentUsers.length > 0 && (
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                                Participants
                                            </label>
                                            <div className="border border-gray-300 rounded-lg p-3 max-h-48 overflow-y-auto">
                                                <div className="space-y-2">
                                                    {departmentUsers.map((user) => (
                                                        <label
                                                            key={user.id}
                                                            className="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 cursor-pointer"
                                                        >
                                                            <input
                                                                type="checkbox"
                                                                checked={data.participant_ids.includes(user.id)}
                                                                onChange={() => toggleParticipant(user.id)}
                                                                className="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
                                                            />
                                                            <div className="w-8 h-8 rounded-full bg-indigo-500 flex items-center justify-center text-white text-sm font-medium">
                                                                {user.name.charAt(0).toUpperCase()}
                                                            </div>
                                                            <div>
                                                                <p className="text-sm font-medium text-gray-900">{user.name}</p>
                                                                <p className="text-xs text-gray-500">{user.email}</p>
                                                            </div>
                                                        </label>
                                                    ))}
                                                </div>
                                            </div>
                                            {data.participant_ids.length > 0 && (
                                                <p className="mt-1 text-sm text-gray-500">
                                                    {data.participant_ids.length} participant(s) selected
                                                </p>
                                            )}
                                        </div>
                                    )}

                                    {/* Actions */}
                                    <div className="flex items-center justify-end gap-3 pt-4 border-t border-gray-200">
                                        <Link href={route('activity.task.index')}>
                                            <Button type="button" variant="secondary">
                                                Cancel
                                            </Button>
                                        </Link>
                                        <Button type="submit" isLoading={processing}>
                                            {isEditing ? 'Update Task' : 'Create Task'}
                                        </Button>
                                    </div>
                                </form>
                            </CardBody>
                        </Card>
                    </motion.div>
                </div>
            </div>
        </>
    );
}

