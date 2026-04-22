import * as React from "react"
import { useForm, router } from "@inertiajs/react"
import { MoreHorizontal, Edit, Trash2, Send } from "lucide-react"
import { ConfirmDialog } from "../ui/ConfirmDialog"
import type { TaskComment } from "@/types"

interface TaskCommentSectionProps {
    taskId: number
    comments: TaskComment[]
    canComment: boolean
}

function formatRelativeTime(dateString: string): string {
    const now = new Date()
    const date = new Date(dateString)
    const diffMs = now.getTime() - date.getTime()
    const diffSec = Math.floor(diffMs / 1000)
    const diffMin = Math.floor(diffSec / 60)
    const diffHour = Math.floor(diffMin / 60)
    const diffDay = Math.floor(diffHour / 24)

    if (diffSec < 60) return "just now"
    if (diffMin < 60) return `${diffMin}m ago`
    if (diffHour < 24) return `${diffHour}h ago`
    if (diffDay < 7) return `${diffDay}d ago`

    return date.toLocaleDateString("en-US", { month: "short", day: "numeric" })
}

function UserAvatar({ name }: { name: string | null }) {
    const initial = name ? name.charAt(0).toUpperCase() : "?"
    return (
        <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-blue-100 text-sm font-medium text-blue-600">
            {initial}
        </div>
    )
}

function CommentItem({
    comment,
    taskId,
    canComment,
}: {
    comment: TaskComment
    taskId: number
    canComment: boolean
}) {
    const [isEditing, setIsEditing] = React.useState(false)
    const [showMenu, setShowMenu] = React.useState(false)
    const [isDeleteDialogOpen, setIsDeleteDialogOpen] = React.useState(false)
    const [isDeleting, setIsDeleting] = React.useState(false)
    const menuRef = React.useRef<HTMLDivElement>(null)

    const editForm = useForm({ body: comment.body })

    React.useEffect(() => {
        function handleClickOutside(e: MouseEvent) {
            if (menuRef.current && !menuRef.current.contains(e.target as Node)) {
                setShowMenu(false)
            }
        }
        if (showMenu) {
            document.addEventListener("mousedown", handleClickOutside)
        }
        return () => document.removeEventListener("mousedown", handleClickOutside)
    }, [showMenu])

    const handleEdit = () => {
        setShowMenu(false)
        editForm.setData("body", comment.body)
        setIsEditing(true)
    }

    const handleCancelEdit = () => {
        setIsEditing(false)
        editForm.setData("body", comment.body)
    }

    const handleSaveEdit = (e: React.FormEvent) => {
        e.preventDefault()
        editForm.put(
            route("activity.task.comments.update", { task: taskId, comment: comment.id }),
            {
                preserveScroll: true,
                onSuccess: () => setIsEditing(false),
            }
        )
    }

    const handleDelete = () => {
        setShowMenu(false)
        setIsDeleteDialogOpen(true)
    }

    const handleConfirmDelete = () => {
        setIsDeleting(true)
        router.delete(
            route("activity.task.comments.destroy", { task: taskId, comment: comment.id }),
            {
                preserveScroll: true,
                onSuccess: () => setIsDeleteDialogOpen(false),
                onFinish: () => setIsDeleting(false),
            }
        )
    }

    const hasActions = canComment && (comment.can_edit || comment.can_delete)

    return (
        <>
            <div className="group flex gap-3 py-3" data-testid={`comment-${comment.id}`}>
                <UserAvatar name={comment.user?.name ?? null} />
                <div className="min-w-0 flex-1">
                    <div className="flex items-center gap-2">
                        <span className="text-sm font-medium text-foreground">
                            {comment.user?.name ?? "Deleted User"}
                        </span>
                        <span className="text-xs text-muted-foreground">
                            {formatRelativeTime(comment.created_at)}
                        </span>
                        {comment.edited_at && (
                            <span className="text-xs italic text-muted-foreground">(edited)</span>
                        )}
                        {hasActions && (
                            <div className="relative ml-auto" ref={menuRef}>
                                <button
                                    onClick={() => setShowMenu(!showMenu)}
                                    className="flex h-6 w-6 items-center justify-center rounded opacity-0 transition-opacity hover:bg-secondary group-hover:opacity-100"
                                    aria-label="Comment actions"
                                    data-testid={`comment-menu-${comment.id}`}
                                >
                                    <MoreHorizontal className="h-4 w-4 text-muted-foreground" />
                                </button>
                                {showMenu && (
                                    <div className="absolute right-0 top-full z-10 mt-1 w-32 rounded-md border border-border bg-card py-1 shadow-lg">
                                        {comment.can_edit && (
                                            <button
                                                onClick={handleEdit}
                                                className="flex w-full items-center gap-2 px-3 py-1.5 text-left text-sm text-foreground hover:bg-secondary"
                                                data-testid={`comment-edit-${comment.id}`}
                                            >
                                                <Edit className="h-3.5 w-3.5" /> Edit
                                            </button>
                                        )}
                                        {comment.can_delete && (
                                            <button
                                                onClick={handleDelete}
                                                className="flex w-full items-center gap-2 px-3 py-1.5 text-left text-sm text-rose-600 hover:bg-rose-50"
                                                data-testid={`comment-delete-${comment.id}`}
                                            >
                                                <Trash2 className="h-3.5 w-3.5" /> Delete
                                            </button>
                                        )}
                                    </div>
                                )}
                            </div>
                        )}
                    </div>

                    {isEditing ? (
                        <form onSubmit={handleSaveEdit} className="mt-2">
                            <textarea
                                value={editForm.data.body}
                                onChange={(e) => editForm.setData("body", e.target.value)}
                                className="w-full rounded-md border border-border bg-background p-2 text-sm outline-none focus:border-primary"
                                rows={3}
                                data-testid={`comment-edit-input-${comment.id}`}
                            />
                            <div className="mt-1.5 flex gap-2">
                                <button
                                    type="submit"
                                    disabled={editForm.processing || !editForm.data.body.trim()}
                                    className="rounded-md bg-primary px-3 py-1 text-xs font-medium text-white hover:bg-blue-700 disabled:opacity-50"
                                >
                                    Save
                                </button>
                                <button
                                    type="button"
                                    onClick={handleCancelEdit}
                                    className="rounded-md border border-border px-3 py-1 text-xs font-medium text-foreground hover:bg-secondary"
                                >
                                    Cancel
                                </button>
                            </div>
                        </form>
                    ) : (
                        <p className="mt-1 whitespace-pre-wrap text-sm text-foreground">{comment.body}</p>
                    )}
                </div>
            </div>

            <ConfirmDialog
                isOpen={isDeleteDialogOpen}
                onClose={() => setIsDeleteDialogOpen(false)}
                onConfirm={handleConfirmDelete}
                title="Delete Comment"
                message="Are you sure you want to delete this comment? This action cannot be undone."
                confirmText="Delete"
                variant="danger"
                isLoading={isDeleting}
            />
        </>
    )
}

export function TaskCommentSection({ taskId, comments, canComment }: TaskCommentSectionProps) {
    const form = useForm({ body: "" })
    const listRef = React.useRef<HTMLDivElement>(null)

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault()
        if (!form.data.body.trim()) return

        form.post(route("activity.task.comments.store", { task: taskId }), {
            preserveScroll: true,
            onSuccess: () => form.reset(),
        })
    }

    return (
        <div className="flex flex-col">
            {/* Comment List */}
            <div ref={listRef} className="max-h-[300px] divide-y divide-border overflow-y-auto">
                {comments.length === 0 ? (
                    <div className="py-8 text-center text-sm text-muted-foreground">
                        Belum ada komentar. Mulai diskusi.
                    </div>
                ) : (
                    comments.map((comment) => (
                        <CommentItem
                            key={comment.id}
                            comment={comment}
                            taskId={taskId}
                            canComment={canComment}
                        />
                    ))
                )}
            </div>

            {/* Comment Input */}
            {canComment ? (
                <form onSubmit={handleSubmit} className="mt-4 border-t border-border pt-4">
                    <textarea
                        value={form.data.body}
                        onChange={(e) => form.setData("body", e.target.value)}
                        placeholder="Tulis komentar..."
                        className="w-full rounded-md border border-border bg-background p-3 text-sm outline-none focus:border-primary"
                        rows={2}
                        data-testid="comment-input"
                    />
                    <div className="mt-2 flex justify-end">
                        <button
                            type="submit"
                            disabled={form.processing || !form.data.body.trim()}
                            className="flex items-center gap-1.5 rounded-md bg-primary px-4 py-1.5 text-xs font-medium text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                            data-testid="comment-submit"
                        >
                            <Send className="h-3.5 w-3.5" /> Kirim
                        </button>
                    </div>
                </form>
            ) : (
                <div className="mt-4 border-t border-border pt-4 text-center text-sm text-muted-foreground">
                    Komentar tidak tersedia untuk task ini.
                </div>
            )}
        </div>
    )
}

export default TaskCommentSection
