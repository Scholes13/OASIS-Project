import * as React from 'react';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { act, fireEvent, render, screen, within } from '@testing-library/react';
import * as InertiaReact from '@inertiajs/react';
import { router } from '@inertiajs/react';
import { TaskCommentSection } from '@/components/activity/TaskCommentSection';
import type { TaskComment } from '@/types';

// Mock ConfirmDialog to simplify testing
vi.mock('@/components/ui/ConfirmDialog', () => ({
    ConfirmDialog: ({
        isOpen,
        onConfirm,
        onClose,
        title,
    }: {
        isOpen: boolean;
        onConfirm: () => void;
        onClose: () => void;
        title: string;
    }) =>
        isOpen ? (
            <div data-testid="confirm-dialog">
                <span>{title}</span>
                <button onClick={onConfirm} data-testid="confirm-dialog-confirm">
                    Confirm
                </button>
                <button onClick={onClose} data-testid="confirm-dialog-cancel">
                    Cancel
                </button>
            </div>
        ) : null,
}));

const ownComment: TaskComment = {
    id: 1,
    user: { id: 1, name: 'Test User' },
    body: 'This is my comment',
    edited_at: null,
    created_at: new Date().toISOString(),
    can_edit: true,
    can_delete: true,
};

const otherComment: TaskComment = {
    id: 2,
    user: { id: 2, name: 'Other User' },
    body: 'This is another comment',
    edited_at: null,
    created_at: new Date(Date.now() - 3600000).toISOString(),
    can_edit: false,
    can_delete: false,
};

const editedComment: TaskComment = {
    id: 3,
    user: { id: 2, name: 'Other User' },
    body: 'This was edited',
    edited_at: new Date().toISOString(),
    created_at: new Date(Date.now() - 7200000).toISOString(),
    can_edit: false,
    can_delete: false,
};

const deletedUserComment: TaskComment = {
    id: 4,
    user: null,
    body: 'Comment from deleted user',
    edited_at: null,
    created_at: new Date(Date.now() - 86400000).toISOString(),
    can_edit: false,
    can_delete: false,
};

describe('TaskCommentSection', () => {
    let mockFormData: Record<string, unknown>;
    let mockPost: ReturnType<typeof vi.fn>;
    let mockPut: ReturnType<typeof vi.fn>;
    let mockReset: ReturnType<typeof vi.fn>;
    let mockSetData: ReturnType<typeof vi.fn>;

    beforeEach(() => {
        vi.clearAllMocks();

        mockFormData = { body: '' };
        mockPost = vi.fn();
        mockPut = vi.fn();
        mockReset = vi.fn();
        mockSetData = vi.fn((keyOrValue: string | Record<string, unknown>, maybeValue?: unknown) => {
            if (typeof keyOrValue === 'string') {
                mockFormData[keyOrValue] = maybeValue;
            }
        });

        vi.spyOn(InertiaReact, 'useForm').mockImplementation((initialData?: Record<string, unknown>) => {
            mockFormData = { ...initialData };

            return {
                data: mockFormData,
                setData: mockSetData,
                post: mockPost,
                put: mockPut,
                processing: false,
                errors: {},
                reset: mockReset,
            } as unknown as ReturnType<typeof InertiaReact.useForm>;
        });

        if (!Element.prototype.getAnimations) {
            Object.defineProperty(Element.prototype, 'getAnimations', {
                configurable: true,
                value: vi.fn(() => []),
            });
        }
    });

    it('renders comment list with correct data', () => {
        render(
            <TaskCommentSection
                taskId={1}
                comments={[ownComment, otherComment]}
                canComment={true}
            />
        );

        expect(screen.getByText('This is my comment')).toBeInTheDocument();
        expect(screen.getByText('This is another comment')).toBeInTheDocument();
        expect(screen.getByText('Test User')).toBeInTheDocument();
        expect(screen.getByText('Other User')).toBeInTheDocument();
    });

    it('renders empty state when no comments', () => {
        render(
            <TaskCommentSection taskId={1} comments={[]} canComment={true} />
        );

        expect(screen.getByText('Belum ada komentar. Mulai diskusi.')).toBeInTheDocument();
    });

    it('shows edit/delete actions only for own comments', () => {
        render(
            <TaskCommentSection
                taskId={1}
                comments={[ownComment, otherComment]}
                canComment={true}
            />
        );

        // Own comment should have action menu
        expect(screen.getByTestId('comment-menu-1')).toBeInTheDocument();

        // Other comment should not have action menu
        expect(screen.queryByTestId('comment-menu-2')).not.toBeInTheDocument();
    });

    it('hides edit/delete for other users comments', () => {
        render(
            <TaskCommentSection
                taskId={1}
                comments={[otherComment]}
                canComment={true}
            />
        );

        expect(screen.queryByTestId('comment-menu-2')).not.toBeInTheDocument();
    });

    it('post button disabled when input empty', () => {
        render(
            <TaskCommentSection taskId={1} comments={[]} canComment={true} />
        );

        const submitBtn = screen.getByTestId('comment-submit');
        expect(submitBtn).toBeDisabled();
    });

    it('cancelled task disables input (canComment=false)', () => {
        render(
            <TaskCommentSection taskId={1} comments={[]} canComment={false} />
        );

        expect(screen.queryByTestId('comment-input')).not.toBeInTheDocument();
        expect(screen.getByText('Komentar tidak tersedia untuk task ini.')).toBeInTheDocument();
    });

    it('edited comment shows "(edited)" label', () => {
        render(
            <TaskCommentSection
                taskId={1}
                comments={[editedComment]}
                canComment={true}
            />
        );

        expect(screen.getByText('(edited)')).toBeInTheDocument();
    });

    it('renders deleted user as "Deleted User"', () => {
        render(
            <TaskCommentSection
                taskId={1}
                comments={[deletedUserComment]}
                canComment={true}
            />
        );

        expect(screen.getByText('Deleted User')).toBeInTheDocument();
        expect(screen.getByText('Comment from deleted user')).toBeInTheDocument();
    });

    it('opens edit mode when edit action is clicked', async () => {
        render(
            <TaskCommentSection
                taskId={1}
                comments={[ownComment]}
                canComment={true}
            />
        );

        // Open menu
        await act(async () => {
            fireEvent.click(screen.getByTestId('comment-menu-1'));
        });

        // Click edit
        await act(async () => {
            fireEvent.click(screen.getByTestId('comment-edit-1'));
        });

        // Edit textarea should appear
        expect(screen.getByTestId('comment-edit-input-1')).toBeInTheDocument();
        expect(screen.getByText('Save')).toBeInTheDocument();
        expect(screen.getByText('Cancel')).toBeInTheDocument();
    });

    it('opens delete confirm dialog when delete action is clicked', async () => {
        render(
            <TaskCommentSection
                taskId={1}
                comments={[ownComment]}
                canComment={true}
            />
        );

        // Open menu
        await act(async () => {
            fireEvent.click(screen.getByTestId('comment-menu-1'));
        });

        // Click delete
        await act(async () => {
            fireEvent.click(screen.getByTestId('comment-delete-1'));
        });

        // Confirm dialog should appear
        expect(screen.getByTestId('confirm-dialog')).toBeInTheDocument();
        expect(screen.getByText('Delete Comment')).toBeInTheDocument();
    });

    it('calls router.delete when delete is confirmed', async () => {
        render(
            <TaskCommentSection
                taskId={1}
                comments={[ownComment]}
                canComment={true}
            />
        );

        // Open menu -> delete -> confirm
        await act(async () => {
            fireEvent.click(screen.getByTestId('comment-menu-1'));
        });
        await act(async () => {
            fireEvent.click(screen.getByTestId('comment-delete-1'));
        });
        await act(async () => {
            fireEvent.click(screen.getByTestId('confirm-dialog-confirm'));
        });

        expect(vi.mocked(router.delete)).toHaveBeenCalledWith(
            expect.stringContaining('activity.task.comments.destroy'),
            expect.objectContaining({ preserveScroll: true })
        );
    });
});
