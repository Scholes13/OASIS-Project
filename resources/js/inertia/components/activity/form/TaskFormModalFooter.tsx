import { Clock } from 'lucide-react';

interface TaskFormModalFooterProps {
    isEditing: boolean;
    isCompleted: boolean;
    showReadOnlyStartSummary: boolean;
    createAnother: boolean;
    processing: boolean;
    onCreateAnotherChange: (checked: boolean) => void;
    onClose: () => void;
}

export default function TaskFormModalFooter({
    isEditing,
    isCompleted,
    showReadOnlyStartSummary,
    createAnother,
    processing,
    onCreateAnotherChange,
    onClose,
}: TaskFormModalFooterProps) {
    return (
        <div className="flex items-center justify-between px-6 py-3 bg-slate-50 border-t border-slate-200 rounded-b-xl shrink-0">
            <div className="flex flex-col gap-2">
                <div className="text-[12px] text-slate-500 flex items-center gap-1.5">
                    {isCompleted ? (
                        <>
                            <Clock className="w-3.5 h-3.5" />
                            Time log required for completed task.
                        </>
                    ) : showReadOnlyStartSummary ? (
                        <>
                            <Clock className="w-3.5 h-3.5" />
                            Started time is managed by the system for active tasks.
                        </>
                    ) : null}
                </div>

                {!isEditing && (
                    <label className="flex items-center gap-2 text-[12px] font-medium text-slate-600 cursor-pointer select-none">
                        <input
                            type="checkbox"
                            checked={createAnother}
                            onChange={(event) => onCreateAnotherChange(event.target.checked)}
                            className="h-4 w-4 rounded border-slate-300 text-[#16599c] focus:ring-[#16599c]/20"
                        />
                        <span>Create another task?</span>
                    </label>
                )}
            </div>

            <div className="flex items-center gap-3">
                <button
                    type="button"
                    onClick={onClose}
                    className="px-4 py-2 text-sm font-medium text-slate-600 hover:text-slate-900 bg-transparent hover:bg-slate-200/50 rounded-md transition-colors"
                >
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
    );
}
