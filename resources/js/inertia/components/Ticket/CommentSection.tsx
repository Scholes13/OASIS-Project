import { useState } from 'react';
import { useForm } from '@inertiajs/react';
import { Lock, Send, User } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { toast } from '@/components/ui/toast';
import { cn } from '@/lib/utils';
import type { TicketComment, TicketCommentFormData } from '@/types';

interface CommentSectionProps {
    comments: TicketComment[];
    ticketId: number;
    commentRoute: string;
    canAddPrivateComment?: boolean;
    onCommentAdded?: () => void;
    className?: string;
}

export function CommentSection({
    comments,
    ticketId,
    commentRoute,
    canAddPrivateComment = false,
    onCommentAdded,
    className,
}: CommentSectionProps) {
    const [showPrivateToggle, setShowPrivateToggle] = useState(false);
    const [isPrivate, setIsPrivate] = useState(false);

    const { data, setData, post, processing, reset } = useForm<TicketCommentFormData>({
        content: '',
        is_private: false,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        
        if (!data.content.trim()) {
            toast.warning('Komentar tidak boleh kosong');
            return;
        }

        post(commentRoute, {
            onSuccess: () => {
                toast.success('Komentar berhasil ditambahkan');
                reset();
                setIsPrivate(false);
                onCommentAdded?.();
            },
            onError: () => {
                toast.error('Gagal menambahkan komentar');
            },
        });
    };

    const formatDate = (dateString: string) => {
        const date = new Date(dateString);
        return date.toLocaleDateString('id-ID', {
            day: 'numeric',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    return (
        <div className={cn('space-y-4', className)}>
            {/* Comments List */}
            <div className="space-y-3">
                {comments.length === 0 ? (
                    <p className="text-sm text-gray-400 text-center py-4">
                        Belum ada komentar
                    </p>
                ) : (
                    comments.map((comment) => (
                        <div
                            key={comment.id}
                            className={cn(
                                'p-4 rounded-lg',
                                comment.is_private ? 'bg-amber-50 border border-amber-100' : 'bg-gray-50'
                            )}
                        >
                            {/* Comment Header */}
                            <div className="flex items-center justify-between mb-2">
                                <div className="flex items-center gap-2">
                                    <div className="w-8 h-8 bg-gray-300 rounded-full flex items-center justify-center">
                                        {comment.user?.avatar_url ? (
                                            <img
                                                src={comment.user.avatar_url}
                                                alt={comment.user.name}
                                                className="w-8 h-8 rounded-full"
                                            />
                                        ) : (
                                            <User className="w-4 h-4 text-gray-600" />
                                        )}
                                    </div>
                                    <div>
                                        <p className="text-sm font-medium text-gray-900">
                                            {comment.user?.name || 'Unknown User'}
                                        </p>
                                        <p className="text-xs text-gray-400">
                                            {formatDate(comment.created_at)}
                                        </p>
                                    </div>
                                </div>
                                {comment.is_private && (
                                    <span className="inline-flex items-center gap-1 px-2 py-0.5 bg-amber-100 text-amber-700 text-xs rounded-full">
                                        <Lock className="w-3 h-3" />
                                        Private
                                    </span>
                                )}
                            </div>

                            {/* Comment Content */}
                            <div className="text-sm text-gray-700 whitespace-pre-wrap">
                                {comment.content}
                            </div>

                            {/* Edit/Delete Actions (if allowed) */}
                            {comment.can_edit || comment.can_delete ? (
                                <div className="flex gap-2 mt-2 pt-2 border-t border-gray-100">
                                    {comment.can_edit && (
                                        <button className="text-xs text-gray-500 hover:text-blue-600">
                                            Edit
                                        </button>
                                    )}
                                    {comment.can_delete && (
                                        <button className="text-xs text-gray-500 hover:text-red-600">
                                            Hapus
                                        </button>
                                    )}
                                </div>
                            ) : null}
                        </div>
                    ))
                )}
            </div>

            {/* Add Comment Form */}
            <form onSubmit={handleSubmit} className="space-y-2">
                <Textarea
                    value={data.content}
                    onChange={(e) => setData('content', e.target.value)}
                    placeholder="Tulis komentar..."
                    rows={3}
                    className="resize-none"
                />
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-2">
                        {canAddPrivateComment && (
                            <label className="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
                                <input
                                    type="checkbox"
                                    checked={isPrivate}
                                    onChange={(e) => {
                                        setIsPrivate(e.target.checked);
                                        setData('is_private', e.target.checked);
                                    }}
                                    className="rounded border-gray-300 text-amber-600 focus:ring-amber-500"
                                />
                                <Lock className="w-4 h-4" />
                                Komentar privat (hanya visible untuk admin)
                            </label>
                        )}
                    </div>
                    <Button
                        type="submit"
                        size="sm"
                        loading={processing}
                        disabled={!data.content.trim()}
                    >
                        <Send className="w-4 h-4 mr-1" />
                        Kirim
                    </Button>
                </div>
            </form>
        </div>
    );
}