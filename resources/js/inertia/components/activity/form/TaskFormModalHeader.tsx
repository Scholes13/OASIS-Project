import { X } from 'lucide-react';

interface TaskFormModalHeaderProps {
    isEditing: boolean;
    onClose: () => void;
}

export default function TaskFormModalHeader({
    isEditing,
    onClose,
}: TaskFormModalHeaderProps) {
    return (
        <div className="flex items-center justify-between px-6 py-3 border-b border-slate-100 shrink-0">
            <h2 className="text-base font-bold text-slate-800 tracking-tight">
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
    );
}
