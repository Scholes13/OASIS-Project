import type { ActivityType, TaskPriority, TaskStatus } from '@/types';

interface SubActivityOption {
    id: number;
    name: string;
}

interface TaskClassificationData {
    activity_type_id: string;
    sub_activity_id: string;
    status: TaskStatus;
    priority: TaskPriority;
    due_date?: string;
}

interface TaskClassificationCardProps {
    data: TaskClassificationData;
    activityTypes: ActivityType[];
    subActivities: SubActivityOption[];
    errors: Partial<Record<keyof TaskClassificationData, string>>;
    onChange: <K extends keyof TaskClassificationData>(key: K, value: TaskClassificationData[K]) => void;
    onChangeMany: (data: TaskClassificationData) => void;
}

export default function TaskClassificationCard({
    data,
    activityTypes,
    subActivities,
    errors,
    onChange,
    onChangeMany,
}: TaskClassificationCardProps) {
    return (
        <>
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
                            onChange={(event) => {
                                const subId = event.target.value;
                                const parentType = activityTypes.find((type) =>
                                    type.sub_activities?.some((sub) => sub.id.toString() === subId)
                                );

                                if (subId && parentType) {
                                    onChangeMany({
                                        ...data,
                                        activity_type_id: parentType.id.toString(),
                                        sub_activity_id: subId,
                                    });
                                    return;
                                }

                                onChange('sub_activity_id', subId);
                            }}
                            className="w-full px-3 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary bg-white"
                        >
                            <option value="">Select sub activity...</option>
                            {subActivities.map((sub) => (
                                <option
                                    key={sub.id}
                                    value={sub.id.toString()}
                                >
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
                            onChange={(event) => onChange('activity_type_id', event.target.value)}
                            className={`w-full px-3 py-2.5 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary bg-gray-50 ${errors.activity_type_id ? 'border-red-500' : 'border-gray-200'}`}
                        >
                            <option value="">Select type...</option>
                            {activityTypes.map((type) => (
                                <option
                                    key={type.id}
                                    value={type.id.toString()}
                                >
                                    {type.name}
                                </option>
                            ))}
                        </select>
                        {errors.activity_type_id && <p className="mt-1 text-sm text-red-600">{errors.activity_type_id}</p>}
                    </div>
                </div>
            </div>

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
                            onChange={(event) => {
                                const status = event.target.value as TaskStatus;
                                onChange('status', status);
                                if (status === 'completed') {
                                    onChangeMany({ ...data, status, due_date: '' });
                                }
                            }}
                            className="w-full px-3 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary bg-white"
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
                            {['low', 'medium', 'high'].map((priority) => (
                                <button
                                    key={priority}
                                    type="button"
                                    onClick={() => onChange('priority', priority as TaskPriority)}
                                    className={`px-3 py-2 text-sm font-medium rounded-lg border transition-all ${data.priority === priority ? 'bg-blue-50 border-blue-200 text-blue-700 ring-1 ring-blue-200' : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50'}`}
                                >
                                    {priority.charAt(0).toUpperCase() + priority.slice(1)}
                                </button>
                            ))}
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}
