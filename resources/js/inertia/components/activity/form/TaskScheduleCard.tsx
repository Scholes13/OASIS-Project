import Time24Input from './Time24Input';
import type { TaskPriority, TaskStatus } from '@/types';

interface TaskScheduleData {
    status: TaskStatus;
    priority: TaskPriority;
    task_date: string;
    due_date: string;
    start_time: string;
    end_time: string;
    completed_date: string;
}

interface BackdatePermission {
    requested_date: string;
    granted_until: string;
    is_active: boolean;
}

interface TaskScheduleCardProps {
    data: TaskScheduleData;
    errors: Partial<Record<keyof TaskScheduleData, string>>;
    allowedDateRange: {
        from: string;
        to: string;
    };
    backdateEnabled: boolean;
    backdatePermission?: BackdatePermission | null;
    onChange: <K extends keyof TaskScheduleData>(key: K, value: TaskScheduleData[K]) => void;
    onCompletedDateManualChange: () => void;
    onRequestBackdate: () => void;
}

const getDurationLabel = (data: TaskScheduleData) => {
    const startDate = new Date(`${data.task_date}T${data.start_time}`);
    const endDate = new Date(`${data.completed_date}T${data.end_time}`);
    const diffMs = endDate.getTime() - startDate.getTime();

    if (diffMs < 0) {
        return 'Invalid (waktu selesai harus setelah waktu mulai)';
    }

    const totalMins = Math.floor(diffMs / 60000);
    const days = Math.floor(totalMins / (24 * 60));
    const hours = Math.floor((totalMins % (24 * 60)) / 60);
    const mins = totalMins % 60;
    const parts: string[] = [];

    if (days > 0) parts.push(`${days} hari`);
    if (hours > 0) parts.push(`${hours} jam`);
    if (mins > 0 || parts.length === 0) parts.push(`${mins} menit`);

    return parts.join(' ');
};

export default function TaskScheduleCard({
    data,
    errors,
    allowedDateRange,
    backdateEnabled,
    backdatePermission,
    onChange,
    onCompletedDateManualChange,
    onRequestBackdate,
}: TaskScheduleCardProps) {
    const today = new Date().toISOString().split('T')[0];
    const isBackdate = data.task_date && data.task_date < today;
    const showTimeFields = data.status === 'completed' || (data.status === 'in_progress' && isBackdate);

    return (
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
                        onChange={(event) => onChange('task_date', event.target.value)}
                        min={allowedDateRange.from}
                        max={allowedDateRange.to}
                        className={`w-full px-3 py-2.5 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary bg-white ${errors.task_date ? 'border-red-500' : 'border-gray-200'}`}
                    />
                    {errors.task_date && <p className="mt-1 text-sm text-red-600">{errors.task_date}</p>}
                    {backdateEnabled && (
                        backdatePermission?.is_active ? (
                            <p className="mt-1 text-xs text-green-600">
                                ✓ You can backdate up to {backdatePermission.requested_date} (expires {new Date(backdatePermission.granted_until).toLocaleString()})
                            </p>
                        ) : (
                            <p className="mt-1 text-xs text-gray-500">
                                You can backdate up to {allowedDateRange.from}. Need older dates?{' '}
                                <button
                                    type="button"
                                    onClick={onRequestBackdate}
                                    className="text-primary hover:text-primary font-medium underline"
                                >
                                    Request backdate access
                                </button>
                            </p>
                        )
                    )}
                </div>

                {showTimeFields && (
                    <>
                        <div className="bg-amber-50 border border-amber-200 rounded-lg p-3">
                            <p className="text-sm text-amber-700">
                                <span className="font-medium">
                                    {data.status === 'in_progress' ? 'Backdate In Progress:' : 'Status Completed:'}
                                </span>{' '}
                                {data.status === 'in_progress'
                                    ? 'Silakan isi waktu mulai untuk task backdate.'
                                    : 'Silakan isi tanggal selesai, waktu mulai dan waktu selesai.'}
                            </p>
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                Waktu Mulai <span className="text-red-500">*</span>
                            </label>
                            <Time24Input
                                id="start_time"
                                value={data.start_time}
                                onChange={(value) => onChange('start_time', value)}
                                hasError={!!errors.start_time}
                            />
                            {errors.start_time && <p className="mt-1 text-sm text-red-600">{errors.start_time}</p>}
                            <p className="mt-1 text-xs text-gray-500">
                                Format 24 jam (00:00-23:59). Tanggal mulai: {data.task_date || '-'}
                            </p>
                        </div>

                        {data.status === 'completed' && (
                            <>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Tanggal Selesai <span className="text-red-500">*</span>
                                    </label>
                                    <input
                                        type="date"
                                        value={data.completed_date}
                                        onChange={(event) => {
                                            onChange('completed_date', event.target.value);
                                            onCompletedDateManualChange();
                                        }}
                                        min={data.task_date}
                                        max={allowedDateRange.to}
                                        className={`w-full px-3 py-2.5 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary bg-white ${errors.completed_date ? 'border-red-500' : 'border-gray-200'}`}
                                    />
                                    {errors.completed_date && <p className="mt-1 text-sm text-red-600">{errors.completed_date}</p>}
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Waktu Selesai <span className="text-red-500">*</span>
                                    </label>
                                    <Time24Input
                                        id="end_time"
                                        value={data.end_time}
                                        onChange={(value) => onChange('end_time', value)}
                                        hasError={!!errors.end_time}
                                    />
                                    {errors.end_time && <p className="mt-1 text-sm text-red-600">{errors.end_time}</p>}
                                </div>
                            </>
                        )}

                        {data.status === 'completed' && data.start_time && data.end_time && data.completed_date && (
                            <div className="bg-primary border border-primary rounded-lg p-3">
                                <p className="text-sm text-primary">
                                    <span className="font-medium">Durasi:</span> {getDurationLabel(data)}
                                </p>
                            </div>
                        )}
                    </>
                )}

                {data.status !== 'completed' && (
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">
                            Due Date <span className="text-red-500">*</span>
                        </label>
                        <input
                            type="date"
                            value={data.due_date}
                            onChange={(event) => onChange('due_date', event.target.value)}
                            min={allowedDateRange.from}
                            max={allowedDateRange.to}
                            className={`w-full px-3 py-2.5 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary bg-white ${errors.due_date ? 'border-red-500' : 'border-gray-200'}`}
                        />
                        {errors.due_date && <p className="mt-1 text-sm text-red-600">{errors.due_date}</p>}
                    </div>
                )}
            </div>
        </div>
    );
}
