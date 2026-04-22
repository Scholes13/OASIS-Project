import * as React from "react"
import { format } from "date-fns"
import { id as idLocale } from "date-fns/locale"
import { router, usePage } from "@inertiajs/react"
import {
    Calendar,
    User,
    Users,
    X,
    AlertTriangle,
    Tag,
    Building2,
    Play,
    CheckCircle2,
    ExternalLink,
    AlignLeft,
    CheckSquare,
    MessageSquare,
    Clock,
    Folder,
    ChevronDown,
    Plus,
    Trash2,
    Edit,
    MoreHorizontal,
    Share2
} from "lucide-react"
import { Dialog } from "../ui/dialog"
import { ConfirmDialog } from "../ui/ConfirmDialog"
import { ActivityTypeBadge, PriorityBadge, StatusBadge } from "../ui/Badge"
import { cn } from "@/lib/utils"
import { showToast } from "../ui/toast"
import { handleExecutionTimeGuidance } from "./quick-status-guidance"
import type { PageProps, Task } from "@/types"

interface TaskDetailModalProps {
    task: Task | null
    open: boolean
    onClose: () => void
    onEdit?: (task: Task) => void
    mode?: 'default' | 'admin-readonly'
}

function canEditTask(task: Task, currentUserId: number | undefined): boolean {
    if (!currentUserId) return false

    const isParticipant = task.participants?.some((participant) =>
        participant.user_id === currentUserId || participant.id === currentUserId
    )

    return isParticipant || task.created_by === currentUserId
}

export function TaskDetailModal({ task, open, onClose, onEdit, mode = 'default' }: TaskDetailModalProps) {
    const [isLoading, setIsLoading] = React.useState(false)
    const [isDeleting, setIsDeleting] = React.useState(false)
    const [isDeleteDialogOpen, setIsDeleteDialogOpen] = React.useState(false)
    const page = usePage<PageProps>()
    const { auth } = page.props
    const currentUserId = auth?.user?.id
    const isAdminReadonly = mode === 'admin-readonly'
    const isOnDashboard = page.url?.startsWith('/activity/task') ?? false
    const editable = !isAdminReadonly && task ? canEditTask(task, currentUserId) : false
    const showOpenInDashboard = !isAdminReadonly && !isOnDashboard

    const isOverdue = task ? (task.due_date && new Date(task.due_date) < new Date() &&
        !["completed", "cancelled"].includes(task.status)) : false

    const formattedDueDate = task?.due_date
        ? format(new Date(task.due_date), "EEEE, d MMMM yyyy", { locale: idLocale })
        : "-"

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
                onError: (errors) => {
                    if (!handleExecutionTimeGuidance(task, errors, onEdit, onClose)) {
                        showToast.error("Failed to start task")
                    }
                },
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
                onError: (errors) => {
                    if (!handleExecutionTimeGuidance(task, errors, onEdit, onClose)) {
                        showToast.error("Failed to complete task")
                    }
                },
                onFinish: () => setIsLoading(false),
            }
        )
    }

    const handleEdit = () => {
        if (!task) return
        if (onEdit) {
            onEdit(task)
        } else {
            router.visit(route("activity.task.index", { task: task.id, modal: "edit" }))
        }
        handleClose()
    }

    const handleDelete = () => {
        if (!task || isDeleting) return

        setIsDeleteDialogOpen(true)
    }

    const handleConfirmDelete = () => {
        if (!task || isDeleting) return

        setIsDeleting(true)
        router.delete(route("activity.task.destroy", { task: task.id }), {
            preserveScroll: true,
            onSuccess: () => {
                showToast.success("Task deleted")
                handleClose()
            },
            onError: () => {
                showToast.error("Failed to delete task")
            },
            onFinish: () => setIsDeleting(false),
        })
    }

    const handleClose = () => {
        setIsDeleteDialogOpen(false)
        onClose()
    }

    const handleViewDetail = () => {
        if (!task) return
        router.visit(route("activity.task.index", { task: task.id, modal: "detail" }))
        handleClose()
    }

    return (
        <>
            <Dialog open={open} onClose={handleClose} className="flex min-h-0 max-h-[min(85vh,800px)] w-[95vw] max-w-[1000px] flex-col overflow-hidden !rounded-xl !p-0 shadow-2xl">
                {task && (
                    <div className="flex h-full min-h-0 flex-col bg-background">
                    {/* Header */}
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
                                    onClick={handleViewDetail}
                                    className="flex h-9 w-9 items-center justify-center rounded-full text-muted-foreground transition-colors hover:bg-secondary hover:text-primary"
                                    aria-label="Open in Dashboard"
                                    title="Open in Dashboard"
                                >
                                    <ExternalLink className="h-[18px] w-[18px]" />
                                </button>
                            )}
                            {editable && (
                                <button
                                    onClick={handleEdit}
                                    className="flex h-9 w-9 items-center justify-center rounded-full text-muted-foreground transition-colors hover:bg-secondary hover:text-primary"
                                    title="Edit Task"
                                >
                                    <Edit className="h-[18px] w-[18px]" />
                                </button>
                            )}
                            {editable && (
                                <button
                                    onClick={handleDelete}
                                    disabled={isDeleting}
                                    className="flex h-9 w-9 items-center justify-center rounded-full text-rose-500 transition-colors hover:bg-rose-50 hover:text-rose-600 disabled:cursor-not-allowed disabled:opacity-50"
                                    aria-label="Delete Task"
                                    title="Delete Task"
                                >
                                    <Trash2 className="h-[18px] w-[18px]" />
                                </button>
                            )}
                            <button
                                onClick={handleClose}
                                className="flex h-9 w-9 items-center justify-center rounded-full text-muted-foreground transition-colors hover:bg-secondary hover:text-primary"
                                aria-label="Close modal"
                            >
                                <X className="h-[18px] w-[18px]" />
                            </button>
                        </div>
                    </div>

                    {/* Body */}
                    <div className="flex min-h-0 flex-1 overflow-hidden">
                        {/* Main Content */}
                        <div className="flex-1 min-h-0 overflow-y-auto border-r border-border p-8">
                            
                            {/* Description Section */}
                            <div className="mb-8">
                                <div className="mb-3 flex items-center gap-2 text-[14px] font-semibold text-foreground">
                                    <AlignLeft className="h-4 w-4" />
                                    Description
                                </div>
                                <div className="text-[14px] leading-relaxed text-foreground">
                                    {/* {task.description ? (
                                        <div dangerouslySetInnerHTML={{ __html: task.description }} className="prose prose-sm max-w-none dark:prose-invert" />
                                    ) : (
                                        <span className="italic text-muted-foreground">No description provided.</span>
                                    )} */}
                                    <span className="italic text-muted-foreground">Descriptions are typically viewed on the full details page.</span>
                                </div>
                            </div>

                            {/* Subtasks Placeholder (Visual Only) */}
                            <div className="mb-8">
                                <div className="mb-3 flex items-center gap-2 text-[14px] font-semibold text-foreground">
                                    <CheckSquare className="h-4 w-4" />
                                    Subtasks
                                </div>
                                <div>
                                    <div className="flex items-start gap-3 border-b border-dashed border-border py-2">
                                        <input type="checkbox" className="mt-[2px] h-[18px] w-[18px] cursor-not-allowed rounded-[4px] border-2 border-muted-foreground" disabled />
                                        <span className="text-[14px] text-muted-foreground italic">Subtasks functionality is coming soon.</span>
                                    </div>
                                </div>
                                <button className="mt-3 flex items-center gap-1.5 rounded-md border border-dashed border-border px-3 py-1.5 text-[13px] text-muted-foreground transition-colors hover:border-primary hover:bg-background hover:text-primary">
                                    <Plus className="h-3.5 w-3.5" /> Add Subtask
                                </button>
                            </div>

                            {/* Activity Tabs Placeholder (Visual Only) */}
                            <div>
                                <div className="mb-6 flex gap-6 border-b border-border">
                                    <div className="border-b-2 border-primary pb-3 text-[14px] font-medium text-primary">Comments</div>
                                    <div className="border-b-2 border-transparent pb-3 text-[14px] font-medium text-muted-foreground cursor-pointer hover:text-foreground">History</div>
                                    <div className="border-b-2 border-transparent pb-3 text-[14px] font-medium text-muted-foreground cursor-pointer hover:text-foreground">Files (0)</div>
                                </div>

                                <div className="flex gap-3">
                                    <div className="flex h-8 w-8 items-center justify-center rounded-full bg-blue-600 text-xs font-bold text-white">
                                        You
                                    </div>
                                    <div className="flex-1">
                                        <div className="overflow-hidden rounded-md border border-border bg-background">
                                            <input
                                                type="text"
                                                placeholder="Write a comment... (Coming soon)"
                                                className="w-full border-none p-3 text-[14px] outline-none bg-transparent disabled:opacity-50"
                                                disabled
                                            />
                                            <div className="flex justify-end bg-muted p-2">
                                                <button className="rounded-md bg-primary px-3.5 py-1.5 text-[12px] font-medium text-white opacity-50 cursor-not-allowed">
                                                    Post
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>

                        {/* Sidebar */}
                        <div className="flex w-[320px] shrink-0 min-h-0 flex-col gap-6 overflow-y-auto bg-muted p-6">
                            
                            {/* Action Buttons (Moved to sidebar) */}
                            {editable && (task.status === "planned" || task.status === "in_progress") && (
                                <div className="flex gap-2 pb-6 border-b border-border">
                                    {task.status === "planned" && (
                                        <button
                                            onClick={handleStartTask}
                                            disabled={isLoading || isDeleting}
                                            className="flex flex-1 items-center justify-center gap-1.5 rounded-md bg-primary py-2 text-[14px] font-medium text-white transition-colors hover:bg-blue-700 disabled:opacity-50"
                                        >
                                            <Play className="h-4 w-4" /> Start
                                        </button>
                                    )}
                                    {["planned", "in_progress"].includes(task.status) && (
                                        <button
                                            onClick={handleCompleteTask}
                                            disabled={isLoading || isDeleting}
                                            className="flex flex-1 items-center justify-center gap-1.5 rounded-md bg-emerald-600 py-2 text-[14px] font-medium text-white transition-colors hover:bg-emerald-700 disabled:opacity-50"
                                        >
                                            <CheckCircle2 className="h-4 w-4" /> Mark Done
                                        </button>
                                    )}
                                </div>
                            )}

                            {/* Status */}
                            <div className="flex flex-col gap-2">
                                <div className="text-[12px] font-semibold uppercase tracking-wide text-muted-foreground">Status</div>
                                <div className="flex w-full items-center justify-between rounded-md border border-border bg-card px-3 py-2.5 text-[14px] transition-colors hover:border-primary cursor-default">
                                    <div className="flex items-center gap-2">
                                        <StatusBadge status={task.status} />
                                    </div>
                                    <ChevronDown className="h-4 w-4 text-muted-foreground" />
                                </div>
                            </div>

                            {/* Activity Type */}
                            <div className="flex flex-col gap-2">
                                <div className="text-[12px] font-semibold uppercase tracking-wide text-muted-foreground">Activity Type</div>
                                <div className="flex w-full items-center justify-between rounded-md border border-border bg-card px-3 py-2.5 text-[14px] transition-colors hover:border-primary cursor-default">
                                    <div className="flex items-center gap-2">
                                        <Folder className="h-4 w-4 text-muted-foreground" />
                                        <span>{task.activity_type?.name ?? "General Activity"}</span>
                                    </div>
                                </div>
                            </div>

                            {/* Priority */}
                            <div className="flex flex-col gap-2">
                                <div className="text-[12px] font-semibold uppercase tracking-wide text-muted-foreground">Priority</div>
                                <div className="flex w-full items-center justify-between rounded-md border border-border bg-card px-3 py-2.5 text-[14px] transition-colors hover:border-primary cursor-default">
                                    <div className="flex items-center gap-2">
                                        <PriorityBadge priority={task.priority} />
                                    </div>
                                    <ChevronDown className="h-4 w-4 text-muted-foreground" />
                                </div>
                            </div>

                            {/* Assignees */}
                            <div className="flex flex-col gap-2">
                                <div className="text-[12px] font-semibold uppercase tracking-wide text-muted-foreground">Assignees</div>
                                <div className="flex flex-wrap gap-2">
                                    {task.participants && task.participants.length > 0 ? (
                                        task.participants.map((participant) => (
                                            <div key={participant.id} className="flex items-center gap-2 rounded-full border border-border bg-card px-2.5 py-1.5 text-[13px] font-medium text-foreground">
                                                <div className="flex h-[20px] w-[20px] items-center justify-center rounded-full bg-[#bfd6ef] text-[10px] font-bold uppercase text-[#205180]">
                                                    {participant.name?.charAt(0) ?? "-"}
                                                </div>
                                                {participant.name}
                                            </div>
                                        ))
                                    ) : (
                                        <span className="text-[14px] text-muted-foreground">No assignees</span>
                                    )}
                                    <div className="flex h-8 w-8 cursor-not-allowed items-center justify-center rounded-full border border-dashed border-muted-foreground bg-card text-muted-foreground transition-colors hover:border-primary hover:text-primary">
                                        <Plus className="h-4 w-4" />
                                    </div>
                                </div>
                            </div>

                            {/* Due Date */}
                            <div className="flex flex-col gap-2">
                                <div className="text-[12px] font-semibold uppercase tracking-wide text-muted-foreground">Due Date</div>
                                <div className="flex w-full items-center justify-between rounded-md border border-border bg-card px-3 py-2.5 text-[14px] transition-colors hover:border-primary cursor-default">
                                    <div className="flex items-center gap-2">
                                        <Calendar className={cn("h-4 w-4", isOverdue ? "text-rose-500" : "text-muted-foreground")} />
                                        <span className={cn("font-medium", isOverdue && "text-rose-600")}>{formattedDueDate}</span>
                                    </div>
                                </div>
                            </div>

                            {/* Department (Extra feature not in mock but important for Oasis) */}
                            <div className="flex flex-col gap-2">
                                <div className="text-[12px] font-semibold uppercase tracking-wide text-muted-foreground">Department</div>
                                <div className="flex w-full items-center justify-between rounded-md border border-border bg-card px-3 py-2.5 text-[14px] transition-colors hover:border-primary cursor-default">
                                    <div className="flex items-center gap-2">
                                        <Building2 className="h-4 w-4 text-muted-foreground" />
                                        <span>{task.department?.name || "-"}</span>
                                    </div>
                                </div>
                            </div>

                            <div className="flex-1"></div>

                            {showOpenInDashboard && (
                                <button 
                                    onClick={handleViewDetail}
                                    className="mt-4 flex w-full items-center justify-center gap-1.5 rounded-md border border-slate-200 bg-card px-3 py-2.5 text-[13px] font-medium text-foreground transition-colors hover:border-primary hover:text-primary"
                                >
                                    <ExternalLink className="h-4 w-4" /> Open in Dashboard
                                </button>
                            )}

                        </div>
                    </div>
                    </div>
                )}
            </Dialog>

            {task && (
                <ConfirmDialog
                    isOpen={isDeleteDialogOpen}
                    onClose={() => setIsDeleteDialogOpen(false)}
                    onConfirm={handleConfirmDelete}
                    title="Delete Task"
                    message={`Delete this task "${task.task_title}"? This action cannot be undone.`}
                    confirmText="Delete"
                    variant="danger"
                    isLoading={isDeleting}
                />
            )}
        </>
    )
}

export default TaskDetailModal
