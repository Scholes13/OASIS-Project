const fs = require('fs');

const path = './resources/js/inertia/components/activity/TaskFormModal.tsx';
let content = fs.readFileSync(path, 'utf8');

// 1. Rename the component and add props
content = content.replace('export default function TaskForm({ task, activityTypes, departmentUsers = [], backdateEnabled = false, backdatePermission, allowedDateRange }: TaskFormProps) {', 
\`import { Dialog } from '@/components/ui/dialog';
import { X, Calendar as CalendarIcon, Clock, Users as UsersIcon } from 'lucide-react';

interface TaskFormModalProps extends TaskFormProps {
    open: boolean;
    onClose: () => void;
    onSuccess?: () => void;
}

export function TaskFormModal({ open, onClose, onSuccess, task, activityTypes, departmentUsers = [], backdateEnabled = false, backdatePermission, allowedDateRange }: TaskFormModalProps) {\`);

// 2. We need to handle form submission gracefully without redirecting.
// In TaskForm.tsx, handleSubmit uses post/put from useForm. We need to override the onSuccess.
const submitOld = `        if (isEditing) {
            put(route('activity.task.update', { task: task!.id }), {
                preserveScroll: true,
                onSuccess: () => showToast.success('Task updated successfully')
            });
        } else {
            post(route('activity.task.store'), {
                preserveScroll: true,
                onSuccess: (page) => {
                    setCreatedTaskId(page.props.flash?.created_task_id as number);
                    setShowSuccessModal(true);
                }
            });
        }`;

const submitNew = `        const options = {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                showToast.success(isEditing ? 'Task updated successfully' : 'Task created successfully');
                if (onSuccess) onSuccess();
                onClose();
            }
        };

        if (isEditing) {
            put(route('activity.task.update', { task: task!.id }), options);
        } else {
            post(route('activity.task.store'), options);
        }`;
content = content.replace(submitOld, submitNew);

// Remove the SuccessModal logic since we just close and notify now
content = content.replace(/setShowSuccessModal\(true\);/g, '');

// Now replace the entire return block with our shiny new Modal UI
const returnIndex = content.indexOf('    return (\n');
if (returnIndex !== -1) {
    const topContent = content.substring(0, returnIndex);
    
    // Check if we have prioritized types or flat types
    // Using the same logic but mapped into a clean design
    
    const newReturn = `    if (!open) return null;

    return (
        <Dialog open={open} onClose={onClose} className="max-w-[720px] w-full p-0 overflow-hidden bg-white rounded-xl shadow-2xl">
            {/* Header */}
            <div className="flex items-center justify-between px-6 py-4 border-b border-slate-100">
                <h2 className="text-lg font-bold text-slate-800 tracking-tight">
                    {isEditing ? 'Edit Task' : 'Create New Task'}
                </h2>
                <button 
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
                            <label className="text-[13px] font-medium text-slate-700">Task Title <span className="text-rose-500">*</span></label>
                            <input
                                type="text"
                                value={data.task_title}
                                onChange={(e) => setData('task_title', e.target.value)}
                                className={cn("h-10 px-3 py-2 border rounded-lg text-sm transition-all focus:ring-2 focus:ring-[#16599c]/20 outline-none", errors.task_title ? "border-rose-300" : "border-slate-200 focus:border-[#16599c]")}
                                placeholder="What needs to be done?"
                            />
                            {errors.task_title && <p className="text-[11px] text-rose-500">{errors.task_title}</p>}
                        </div>

                        <div className="flex flex-col gap-1.5">
                            <label className="text-[13px] font-medium text-slate-700">Status</label>
                            <select
                                value={data.status}
                                onChange={(e) => {
                                    const newStatus = e.target.value as TaskStatus;
                                    setData('status', newStatus);
                                    if (newStatus === 'completed') {
                                        setData(prev => ({ ...prev, status: newStatus, due_date: '' }));
                                    }
                                }}
                                className="h-10 px-3 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-[#16599c]/20 outline-none focus:border-[#16599c] bg-white cursor-pointer"
                            >
                                <option value="planned">To Do (Planned)</option>
                                <option value="in_progress">In Progress</option>
                                <option value="completed">Completed</option>
                            </select>
                        </div>
                    </div>

                    {/* Categorization Row */}
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div className="flex flex-col gap-1.5">
                            <label className="text-[13px] font-medium text-slate-700">Sub Activity <span className="text-rose-500">*</span></label>
                            <select
                                value={data.sub_activity_id}
                                onChange={(e) => {
                                    const subId = e.target.value;
                                    const parentType = flatActivityTypes.find(t => t.sub_activities?.some(s => s.id.toString() === subId));
                                    if (subId && parentType) {
                                        setData({ ...data, activity_type_id: parentType.id.toString(), sub_activity_id: subId });
                                    } else {
                                        setData('sub_activity_id', subId);
                                    }
                                }}
                                className={cn("h-10 px-3 border rounded-lg text-sm focus:ring-2 focus:ring-[#16599c]/20 outline-none bg-white", errors.sub_activity_id ? "border-rose-300" : "border-slate-200 focus:border-[#16599c]")}
                            >
                                <option value="">Select...</option>
                                {sortedSubActivities.map((sub) => (
                                    <option key={sub.id} value={sub.id.toString()}>{sub.name}</option>
                                ))}
                            </select>
                            {errors.sub_activity_id && <p className="text-[11px] text-rose-500">{errors.sub_activity_id}</p>}
                        </div>

                        <div className="flex flex-col gap-1.5">
                            <label className="text-[13px] font-medium text-slate-700 flex items-center justify-between">
                                Activity Type <span className="text-[10px] text-slate-400 font-normal">Auto</span>
                            </label>
                            <select
                                val
