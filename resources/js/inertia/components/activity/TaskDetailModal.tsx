import * as React from "react"
import { format } from "date-fns"
import { id as idLocale } from "date-fns/locale"
import { router } from "@inertiajs/react"
import {
    Calendar,
    User,
    Users,
    Paperclip,
    X,
    AlertTriangle,
    Tag,
    Building2,
    Play,
    CheckCircle2,
    ExternalLink,
    FileText,
} from "lucide-react"
import { Dialog } from "../ui/dialog"
import { ActivityTypeBadge, StatusBadge } from "../ui/Badge"
import { cn } from "@/lib/utils"
import { showToast } from "../ui/toast"
import { LazyImage } from "../ui/LazyImage"
import type { Task } from "@/types"

interface TaskDetailModalProps {
    task: Task | null
    open: boolean
    onClose: () => void
    onEdit?: (task: Task) => void
}

export function TaskDetailModal({ task, open, onClose, onEdit }: TaskDetailModalProps) {
    const [isLoading, setIsLoading] = React.useState(false)

    const isOverdue = task ? (new Date(task.due_date) < new Date() &&
        !["completed", "cancelled"].includes(task.status)) : false

    const handleStartTask = () => {
        if (!task) return
        setIsLoading(true)
        router.put(
            route("activity.task.update", { task: task.id }),
            { status: "in_progress" },
            {
                preserveScroll: true,
                onSuccess: () => {
                    showToast.success("Task started")
                    onClose()
                },
                onError: () => showToast.error("Failed to start task"),
                onFinish: () => setIsLoading(false),
            }
        )
    }

    const handleCompleteTask = () => {
        if (!task) return
        setIsLoading(true)
        router.put(
            route("activity.task.update", { task: task.id }),
            { status: "completed" },
            {
                preserveScroll: true,
                onSuccess: () => {
                    showToast.success("Task completed!")
                    onClose()
                },
                onError: () => showToast.error("Failed to complete task"),
                onFinish: () => setIsLoading(false),
            }
        )
    }

    const handleEdit = () => {
        if (!task) return
        if (onEdit) {
            onEdit(task)
        } else {
            router.visit(route("activity.task.edit", { task: task.id }))
        }
        onClose()
    }

    const handleViewDetail = () => {
        if (!task) return
        router.visit(route("activity.task.show", { task: task.id }))
        onClose()
    }

    return (
        <Dialog open={open} onClose={onClose} className="max-w-xl">
            {task && (
                <>
                    {/* Header */}
                    <div className="flex items-start justify-between pb-4 border-b border-gray-100">
                        <div className="flex flex-wrap items-center gap-2">
                            <ActivityTypeBadge
                                name={task.activity_type?.name ?? "Activity"}
                                color={task.activity_type?.color}
                            />
                            <StatusBadge status={task.status} />
                            {isOverdue && (
                                <span className="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium bg-rose-100 text-rose-700">
                                    <AlertTriangle className="h-3 w-3" />
                                    Overdue
                                </span>
                            )}
                        </div>
                        <button
                            onClick={onClose}
                            className="p-1.5 rounded-lg hover:bg-gray-100 text-gray-400 hover:text-gray-600 transition-colors"
                        >
                            <X className="h-5 w-5" />
                        </button>
                    </div>

                    {/* Title */}
                    <div className="py-4">
                        <h2 className="text-lg font-semibold text-gray-900 leading-tight">
                            {task.task_title}
                        </h2>
                    </div>

                    {/* Info Grid */}
                    <div className="space-y-4 pb-4">
                        {/* Due Date */}
                        <div className="flex items-start gap-3">
                            <div className="flex items-center justify-center w-8 h-8 rounded-lg bg-gray-50">
                                <Calendar className={cn("h-4 w-4", isOverdue ? "text-rose-500" : "text-gray-400")} />
                            </div>
                            <div className="flex-1 min-w-0">
                                <p className="text-xs font-medium text-gray-500 mb-0.5">Due Date</p>
                                <p className={cn(
                                    "text-sm font-medium",
                                    isOverdue ? "text-rose-600" : "text-gray-900"
                                )}>
                                    {format(new Date(task.due_date), "EEEE, d MMMM yyyy", { locale: idLocale })}
                                </p>
                            </div>
                        </div>

                        {/* Department */}
                        {task.department?.name && (
                            <div className="flex items-start gap-3">
                                <div className="flex items-center justify-center w-8 h-8 rounded-lg bg-gray-50">
                                    <Building2 className="h-4 w-4 text-gray-400" />
                                </div>
                                <div className="flex-1 min-w-0">
                                    <p className="text-xs font-medium text-gray-500 mb-0.5">Department</p>
                                    <p className="text-sm font-medium text-gray-900">{task.department.name}</p>
                                </div>
                            </div>
                        )}

                        {/* Priority */}
                        <div className="flex items-start gap-3">
                            <div className="flex items-center justify-center w-8 h-8 rounded-lg bg-gray-50">
                                <Tag className="h-4 w-4 text-gray-400" />
                            </div>
                            <div className="flex-1 min-w-0">
                                <p className="text-xs font-medium text-gray-500 mb-0.5">Priority</p>
                                <span className={cn(
                                    "inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium capitalize",
                                    task.priority === "high" && "bg-rose-100 text-rose-700",
                                    task.priority === "medium" && "bg-amber-100 text-amber-700",
                                    task.priority === "low" && "bg-emerald-100 text-emerald-700",
                                    !["high", "medium", "low"].includes(task.priority) && "bg-gray-100 text-gray-700"
                                )}>
                                    {task.priority}
                                </span>
                            </div>
                        </div>

                        {/* Participants */}
                        {task.participants && task.participants.length > 0 && (
                            <div className="flex items-start gap-3">
                                <div className="flex items-center justify-center w-8 h-8 rounded-lg bg-gray-50">
                                    <Users className="h-4 w-4 text-gray-400" />
                                </div>
                                <div className="flex-1 min-w-0">
                                    <p className="text-xs font-medium text-gray-500 mb-1.5">Participants</p>
                                    <div className="flex flex-wrap gap-2">
                                        {task.participants.map((p, i) => (
                                            <span
                                                key={i}
                                                className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-indigo-50 text-indigo-700"
                                            >
                                                <span className="h-5 w-5 rounded-full bg-indigo-100 flex items-center justify-center text-[0.625rem] font-bold text-indigo-600">
                                                    {p.name?.charAt(0).toUpperCase()}
                                                </span>
                                                {p.name}
                                            </span>
                                        ))}
                                    </div>
                                </div>
                            </div>
                        )}

                        {/* Creator */}
                        <div className="flex items-start gap-3">
                            <div className="flex items-center justify-center w-8 h-8 rounded-lg bg-gray-50">
                                <User className="h-4 w-4 text-gray-400" />
                            </div>
                            <div className="flex-1 min-w-0">
                                <p className="text-xs font-medium text-gray-500 mb-0.5">Created by</p>
                                <p className="text-sm font-medium text-gray-900">{task.creator?.name ?? "Unknown"}</p>
                            </div>
                        </div>
                    </div>

                    {/* Description */}
                    {task.task_description && (
                        <div className="py-4 border-t border-gray-100">
                            <div className="flex items-center gap-2 mb-2">
                                <FileText className="h-4 w-4 text-gray-400" />
                                <p className="text-xs font-medium text-gray-500">Description</p>
                            </div>
                            <p className="text-sm text-gray-600 leading-relaxed whitespace-pre-wrap">
                                {task.task_description}
                            </p>
                        </div>
                    )}

                    {/* Attachments */}
                    {task.attachments && task.attachments.length > 0 && (
                        <div className="py-4 border-t border-gray-100">
                            <div className="flex items-center gap-2 mb-3">
                                <Paperclip className="h-4 w-4 text-gray-400" />
                                <p className="text-xs font-medium text-gray-500">
                                    Attachments ({task.attachments.length})
                                </p>
                            </div>
                            <div className="grid grid-cols-4 gap-2">
                                {task.attachments.slice(0, 4).map((attachment, i) => (
                                    <a
                                        key={i}
                                        href={attachment.url}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="aspect-square rounded-lg bg-gray-100 overflow-hidden hover:opacity-80 transition-opacity flex items-center justify-center"
                                    >
                                        {attachment.mime_type?.startsWith("image/") ? (
                                            <LazyImage
                                                src={attachment.url}
                                                alt={attachment.original_name}
                                                className="w-full h-full object-cover"
                                            />
                                        ) : (
                                            <FileText className="h-6 w-6 text-gray-400" />
                                        )}
                                    </a>
                                ))}
                            </div>
                        </div>
                    )}

                    {/* Footer Actions */}
                    <div className="flex items-center justify-between pt-4 border-t border-gray-100">
                        <button
                            onClick={handleViewDetail}
                            className="inline-flex items-center gap-1.5 text-sm font-medium text-indigo-600 hover:text-indigo-700 transition-colors"
                        >
                            <ExternalLink className="h-4 w-4" />
                            View Full Details
                        </button>
                        <div className="flex items-center gap-2">
                            {task.status === "planned" && (
                                <button
                                    onClick={handleStartTask}
                                    disabled={isLoading}
                                    className="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition-colors disabled:opacity-50"
                                >
                                    <Play className="h-4 w-4" />
                                    Start
                                </button>
                            )}
                            {["planned", "in_progress"].includes(task.status) && (
                                <button
                                    onClick={handleCompleteTask}
                                    disabled={isLoading}
                                    className="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition-colors disabled:opacity-50"
                                >
                                    <CheckCircle2 className="h-4 w-4" />
                                    Done
                                </button>
                            )}
                        </div>
                    </div>
                </>
            )}
        </Dialog>
    )
}

export default TaskDetailModal

