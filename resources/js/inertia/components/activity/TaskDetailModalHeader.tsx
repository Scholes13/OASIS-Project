import { Edit, ExternalLink, Trash2, X } from "lucide-react"

import type { Task } from "@/types"

interface TaskDetailModalHeaderProps {
    task: Task
    isAdminReadonly: boolean
    showOpenInDashboard: boolean
    editable: boolean
    isDeleting: boolean
    onViewDetail: () => void
    onEdit: () => void
    onDelete: () => void
    onClose: () => void
}

export function TaskDetailModalHeader({
    task,
    isAdminReadonly,
    showOpenInDashboard,
    editable,
    isDeleting,
    onViewDetail,
    onEdit,
    onDelete,
    onClose,
}: TaskDetailModalHeaderProps) {
    return (
        <div className="flex items-start justify-between border-b border-border bg-background px-8 py-5">
            <div>
                <div className="mb-2 flex items-center gap-1.5 text-[12px] text-muted-foreground">
                    {isAdminReadonly ? 'Activity Admin' : 'Activity Tracking'} / Task / #{task.id}
                </div>
                <h2 className="text-[20px] font-semibold leading-snug text-foreground">
                    {task.task_title}
                </h2>
            </div>
            <div className="flex items-center gap-2">
                {showOpenInDashboard && (
                    <button
                        onClick={onViewDetail}
                        className="flex h-9 w-9 items-center justify-center rounded-full text-muted-foreground transition-colors hover:bg-secondary hover:text-primary"
                        aria-label="Open in Dashboard"
                        title="Open in Dashboard"
                    >
                        <ExternalLink className="h-[18px] w-[18px]" />
                    </button>
                )}
                {editable && (
                    <button
                        onClick={onEdit}
                        className="flex h-9 w-9 items-center justify-center rounded-full text-muted-foreground transition-colors hover:bg-secondary hover:text-primary"
                        title="Edit Task"
                    >
                        <Edit className="h-[18px] w-[18px]" />
                    </button>
                )}
                {editable && (
                    <button
                        onClick={onDelete}
                        disabled={isDeleting}
                        className="flex h-9 w-9 items-center justify-center rounded-full text-rose-500 transition-colors hover:bg-rose-50 hover:text-rose-600 disabled:cursor-not-allowed disabled:opacity-50"
                        aria-label="Delete Task"
                        title="Delete Task"
                    >
                        <Trash2 className="h-[18px] w-[18px]" />
                    </button>
                )}
                <button
                    onClick={onClose}
                    className="flex h-9 w-9 items-center justify-center rounded-full text-muted-foreground transition-colors hover:bg-secondary hover:text-primary"
                    aria-label="Close modal"
                >
                    <X className="h-[18px] w-[18px]" />
                </button>
            </div>
        </div>
    )
}
