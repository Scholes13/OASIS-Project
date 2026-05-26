import { Calendar as CalendarIcon, Clock } from 'lucide-react';
import { cn } from '@/lib/utils';
import type { TaskStatus } from '@/types';

interface TimelineData {
    status: TaskStatus;
    task_date: string;
    due_date: string;
    start_time: string;
    end_time: string;
    completed_date: string;
}

interface TaskFormModalTimelineProps {
    data: TimelineData;
    errors: Partial<Record<keyof TimelineData, string>>;
    allowedDateRange: {
        from: string;
        to: string;
    };
    backdateEnabled: boolean;
    backdatePermission?: { is_active: boolean } | null;
    isEditing: boolean;
    needsStartCorrection: boolean;
    needsCompletionCorrection: boolean;
    showReadOnlyStartSummary: boolean;
    showReadOnlyCompletionSummary: boolean;
    showStartTimeInput: boolean;
    showCompletionInputs: boolean;
    startedAtDisplayValue: string;
    completedAtDisplayValue: string;
    onChange: <K extends keyof TimelineData>(key: K, value: TimelineData[K]) => void;
    onRequestBackdate: () => void;
}

export default function TaskFormModalTimeline({
    data,
    errors,
    allowedDateRange,
    backdateEnabled,
    backdatePermission,
    isEditing,
    needsStartCorrection,
    needsCompletionCorrection,
    showReadOnlyStartSummary,
    showReadOnlyCompletionSummary,
    showStartTimeInput,
    showCompletionInputs,
    startedAtDisplayValue,
    completedAtDisplayValue,
    onChange,
    onRequestBackdate,
}: TaskFormModalTimelineProps) {
    const showTimeSection = showReadOnlyStartSummary
        || showReadOnlyCompletionSummary
        || showStartTimeInput
        || showCompletionInputs;

    return (
        <div className="flex flex-col gap-2">
            <h4 className="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Timeline & Time Log</h4>
            <div className="bg-slate-50 border border-slate-200 rounded-xl p-4 flex flex-col gap-5">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div className="flex flex-col gap-1.5">
                        <label className="text-[12px] font-medium text-slate-600">Task Date (Start Date)</label>
                        <div className="relative">
                            <input
                                type="date"
                                value={data.task_date}
                                onChange={(event) => onChange('task_date', event.target.value)}
                                min={allowedDateRange?.from}
                                max={allowedDateRange?.to}
                                className={cn(
                                    'w-full h-10 pl-9 pr-3 border rounded-md text-[14px] focus:ring-2 focus:ring-[#16599c]/20 outline-none bg-white cursor-pointer',
                                    errors.task_date ? 'border-rose-300' : 'border-slate-200'
                                )}
                            />
                            <CalendarIcon className="w-4 h-4 text-slate-400 absolute left-3 top-3 pointer-events-none" />
                        </div>
                        {errors.task_date && <p className="text-[10px] text-rose-500">{errors.task_date}</p>}
                        {backdateEnabled && !backdatePermission?.is_active && (
                            <button
                                type="button"
                                onClick={onRequestBackdate}
                                className="text-[10px] text-[#16599c] text-left hover:underline mt-0.5 w-fit"
                            >
                                Need older date?
                            </button>
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
                                onChange={(event) => onChange('due_date', event.target.value)}
                                required={data.status !== 'completed'}
                                min={data.status === 'completed' ? undefined : (data.task_date || undefined)}
                                className="w-full h-10 pl-9 pr-3 border border-slate-200 rounded-md text-[14px] focus:ring-2 focus:ring-[#16599c]/20 outline-none bg-white cursor-pointer"
                            />
                            <CalendarIcon className="w-4 h-4 text-slate-400 absolute left-3 top-3 pointer-events-none" />
                        </div>
                        {errors.due_date && <p className="text-[10px] text-rose-500">{errors.due_date}</p>}
                    </div>
                </div>

                {showTimeSection && (
                    <div className="flex flex-col gap-4 pt-5 border-t border-slate-200/60">
                        {(needsStartCorrection || needsCompletionCorrection) && isEditing && (
                            <div className="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-[12px] text-amber-800">
                                Task date changed. Please confirm the actual execution time for this task.
                            </div>
                        )}

                        {(showReadOnlyStartSummary || showReadOnlyCompletionSummary) && (
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                {showReadOnlyStartSummary && <ReadonlyField label="Started At" value={startedAtDisplayValue} />}
                                {showReadOnlyCompletionSummary && (
                                    <>
                                        <ReadonlyField label="Completed At" value={completedAtDisplayValue} />
                                        <ReadonlyField label="Duration" value="System-managed" muted />
                                    </>
                                )}
                            </div>
                        )}

                        {(showStartTimeInput || showCompletionInputs) && (
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                {showStartTimeInput && (
                                    <TimeField
                                        label="Start Time"
                                        value={data.start_time}
                                        error={errors.start_time}
                                        onChange={(value) => onChange('start_time', value)}
                                    />
                                )}
                                {showCompletionInputs && (
                                    <>
                                        <TimeField
                                            label="End Time"
                                            value={data.end_time}
                                            error={errors.end_time}
                                            onChange={(value) => onChange('end_time', value)}
                                        />
                                        <DateField
                                            value={data.completed_date}
                                            min={data.task_date || undefined}
                                            error={errors.completed_date}
                                            onChange={(value) => onChange('completed_date', value)}
                                        />
                                    </>
                                )}
                            </div>
                        )}
                    </div>
                )}
            </div>
        </div>
    );
}

function ReadonlyField({ label, value, muted = false }: { label: string; value: string; muted?: boolean }) {
    return (
        <div className="flex flex-col gap-1.5">
            <label className="text-[12px] font-medium text-slate-600">{label}</label>
            <div className={`h-10 px-3 border border-slate-200 bg-white rounded-md text-[14px] flex items-center ${muted ? 'text-slate-500' : 'text-slate-700'}`}>
                {value}
            </div>
        </div>
    );
}

function TimeField({ label, value, error, onChange }: { label: string; value: string; error?: string; onChange: (value: string) => void }) {
    return (
        <div className="flex flex-col gap-1.5">
            <label className="text-[12px] font-medium text-slate-600">{label} <span className="text-rose-500">*</span></label>
            <div className="relative">
                <input
                    type="time"
                    aria-label={label}
                    value={value}
                    onChange={(event) => onChange(event.target.value)}
                    className={cn('w-full h-10 pl-9 pr-2 border rounded-md text-[14px] outline-none bg-white focus:border-[#16599c] cursor-pointer', error ? 'border-rose-300' : 'border-slate-200')}
                />
                <Clock className="w-4 h-4 text-slate-400 absolute left-3 top-3 pointer-events-none" />
            </div>
            {error && <p className="text-[10px] text-rose-500">{error}</p>}
        </div>
    );
}

function DateField({ value, min, error, onChange }: { value: string; min?: string; error?: string; onChange: (value: string) => void }) {
    return (
        <div className="flex flex-col gap-1.5">
            <label className="text-[12px] font-medium text-slate-600">Completed Date <span className="text-rose-500">*</span></label>
            <div className="relative">
                <input
                    type="date"
                    aria-label="Completed Date"
                    value={value}
                    onChange={(event) => onChange(event.target.value)}
                    min={min}
                    className={cn('w-full h-10 pl-9 pr-3 border rounded-md text-[14px] focus:ring-2 focus:ring-[#16599c]/20 outline-none bg-white cursor-pointer', error ? 'border-rose-300' : 'border-slate-200')}
                />
                <CalendarIcon className="w-4 h-4 text-slate-400 absolute left-3 top-3 pointer-events-none" />
            </div>
            {error && <p className="text-[10px] text-rose-500">{error}</p>}
        </div>
    );
}
