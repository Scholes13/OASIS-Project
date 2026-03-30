import { beforeEach, describe, expect, it, vi } from 'vitest';
import { act, fireEvent, render, screen, within } from '@testing-library/react';
import { router } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { KanbanBoard } from '@/components/activity/KanbanBoard';
import type { Task } from '@/types';

let latestDndHandlers: {
    onDragStart?: (event: { active: { id: number } }) => void;
    onDragOver?: (event: { active: { id: number }; over: { id: string } | null }) => void;
    onDragEnd?: (event: { active: { id: number }; over: { id: string } | null }) => void;
} = {};

vi.mock('@dnd-kit/core', () => ({
    DndContext: ({
        children,
        onDragStart,
        onDragOver,
        onDragEnd,
    }: {
        children: ReactNode;
        onDragStart?: (event: { active: { id: number } }) => void;
        onDragOver?: (event: { active: { id: number }; over: { id: string } | null }) => void;
        onDragEnd?: (event: { active: { id: number }; over: { id: string } | null }) => void;
    }) => {
        latestDndHandlers = { onDragStart, onDragOver, onDragEnd };

        return <div>{children}</div>;
    },
    DragOverlay: ({ children }: { children: ReactNode }) => <div>{children}</div>,
    closestCorners: vi.fn(),
    KeyboardSensor: class KeyboardSensor {},
    PointerSensor: class PointerSensor {},
    useSensor: vi.fn(() => ({})),
    useSensors: vi.fn(() => []),
    useDroppable: vi.fn(() => ({
        setNodeRef: vi.fn(),
        isOver: false,
    })),
}));

vi.mock('@dnd-kit/sortable', () => ({
    SortableContext: ({ children }: { children: ReactNode }) => <div>{children}</div>,
    sortableKeyboardCoordinates: vi.fn(),
    useSortable: vi.fn(() => ({
        attributes: {},
        listeners: {},
        setNodeRef: vi.fn(),
        transform: null,
        transition: undefined,
        isDragging: false,
    })),
    verticalListSortingStrategy: {},
    arrayMove: (items: unknown[]) => items,
}));

vi.mock('@dnd-kit/utilities', () => ({
    CSS: {
        Transform: {
            toString: () => undefined,
        },
    },
}));

describe('KanbanBoard create entry', () => {
    const baseTask: Task = {
        id: 1,
        task_title: 'Task A',
        task_description: null,
        status: 'planned',
        priority: 'medium',
        due_date: null,
        business_unit_id: 1,
        department_id: 1,
        activity_type_id: 1,
        created_by: 1,
        activity_type: {
            id: 1,
            code: 'GEN',
            name: 'General',
            color: '#16599c',
            is_active: true,
            sort_order: 1,
            sub_activities: [],
        },
        creator: {
            id: 1,
            name: 'User One',
            email: 'user.one@example.com',
            role: 'user',
            avatar_url: null,
            primary_department_id: 1,
        },
        participants: [],
        department: {
            id: 1,
            name: 'Dept One',
            code: 'D1',
            business_unit_id: 1,
            manager_id: null,
            cost_center: null,
            is_active: true,
        },
        created_at: '2026-02-25T00:00:00Z',
        updated_at: '2026-02-25T00:00:00Z',
    };

    beforeEach(() => {
        vi.clearAllMocks();
        latestDndHandlers = {};
    });

    it('uses modal callback when onCreateTask is provided', () => {
        const onCreateTask = vi.fn();

        render(<KanbanBoard tasks={[baseTask]} onCreateTask={onCreateTask} />);

        fireEvent.click(screen.getAllByRole('button', { name: /add task/i })[0]);

        expect(onCreateTask).toHaveBeenCalledTimes(1);
        expect(vi.mocked(router.visit)).not.toHaveBeenCalled();
    });

    it('falls back to create route when callback is not provided', () => {
        render(<KanbanBoard tasks={[baseTask]} />);

        fireEvent.click(screen.getAllByRole('button', { name: /add task/i })[0]);

        expect(vi.mocked(router.visit)).toHaveBeenCalledWith('/activity.task.index?modal=create');
    });

    it('restores the original board state when a drag is cancelled', () => {
        render(<KanbanBoard tasks={[baseTask]} />);

        expect(within(screen.getByText('To Do').parentElement as HTMLElement).getByText('1')).toBeInTheDocument();
        expect(within(screen.getByText('In Progress').parentElement as HTMLElement).getByText('0')).toBeInTheDocument();

        act(() => {
            latestDndHandlers.onDragStart?.({ active: { id: 1 } });
            latestDndHandlers.onDragOver?.({ active: { id: 1 }, over: { id: 'in_progress' } });
        });

        expect(within(screen.getByText('To Do').parentElement as HTMLElement).getByText('0')).toBeInTheDocument();
        expect(within(screen.getByText('In Progress').parentElement as HTMLElement).getByText('1')).toBeInTheDocument();

        act(() => {
            latestDndHandlers.onDragEnd?.({ active: { id: 1 }, over: null });
        });

        expect(within(screen.getByText('To Do').parentElement as HTMLElement).getByText('1')).toBeInTheDocument();
        expect(within(screen.getByText('In Progress').parentElement as HTMLElement).getByText('0')).toBeInTheDocument();
    });
});
