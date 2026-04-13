import * as React from 'react';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { act, fireEvent, render, screen, waitFor } from '@testing-library/react';
import { router } from '@inertiajs/react';
import Dashboard from '@/Pages/Activity/Dashboard';
import type { PaginatedData, Task, TaskFilters, TaskStats } from '@/types';

vi.mock('@/hooks/useBusinessUnit', () => ({
    useBusinessUnit: () => ({
        currentBusinessUnit: {
            id: 1,
            code: 'WNS',
            name: 'WNS',
            logo: null,
        },
        availableBusinessUnits: [],
        isSwitching: false,
        switchBusinessUnit: vi.fn(),
        reload: vi.fn(),
    }),
}));

vi.mock('@/components/activity/TaskFormModal', () => ({
    TaskFormModal: ({
        open,
        task,
        initialTaskDate,
    }: {
        open: boolean;
        task: Task | null;
        initialTaskDate?: string | null;
    }) => (
        open ? (
            <div data-testid="task-form-modal">
                {task ? task.task_title : 'create-task'}
                {initialTaskDate ? `:${initialTaskDate}` : ''}
            </div>
        ) : null
    ),
}));

vi.mock('@/components/activity/TaskDetailModal', () => ({
    TaskDetailModal: ({ open, task, onEdit, onClose }: { open: boolean; task: Task | null; onEdit?: (task: Task) => void; onClose?: () => void }) => (
        open && task ? (
            <div data-testid="task-detail-modal">
                <span>{task.task_title}</span>
                {onEdit ? (
                    <button type="button" aria-label="Edit from detail modal" onClick={() => onEdit(task)}>
                        Edit
                    </button>
                ) : null}
                {onClose ? (
                    <button type="button" aria-label="Close detail modal" onClick={onClose}>
                        Close
                    </button>
                ) : null}
            </div>
        ) : null
    ),
}));

vi.mock('@/components/activity/ActivityDataTable', () => ({
    default: () => <div data-testid="activity-data-table" />,
}));

vi.mock('@/components/activity/KanbanBoard', () => ({
    default: () => <div data-testid="kanban-board" />,
}));

vi.mock('@/components/activity/ActivityCalendar', () => ({
    default: ({ tasks, onEventClick }: { tasks: Task[]; onEventClick?: (task: Task) => void }) => {
        const [selectedTask, setSelectedTask] = React.useState<Task | null>(null);
        const firstTask = tasks[0] ?? null;

        return (
            <div>
                <button
                    type="button"
                    onClick={() => {
                        if (!firstTask) {
                            return;
                        }

                        if (onEventClick) {
                            onEventClick(firstTask);
                            return;
                        }

                        setSelectedTask(firstTask);
                    }}
                >
                    Trigger Calendar Event
                </button>

                {selectedTask ? (
                    <div data-testid="task-detail-modal">{selectedTask.task_title}</div>
                ) : null}
            </div>
        );
    },
}));

vi.mock('@/components/activity/ActivityTimeline', () => ({
    default: () => <div data-testid="activity-timeline" />,
}));

function makeTask(): Task {
    return {
        id: 7,
        task_title: 'Prepare board pack',
        task_description: 'Collect and summarize materials',
        status: 'planned',
        priority: 'medium',
        due_date: '2026-03-30',
        task_date: '2026-03-30',
        business_unit_id: 1,
        department_id: 1,
        activity_type_id: 1,
        created_by: 1,
        activity_type: {
            id: 1,
            code: 'MEET',
            name: 'Meeting',
            color: 'blue',
            sub_activities: [],
        },
        creator: {
            id: 1,
            name: 'Test User',
            email: 'test@example.com',
            role: 'user',
            avatar_url: null,
            primary_department_id: 1,
        },
        participants: [],
        department: {
            id: 1,
            name: 'Corporate Planning',
            code: 'CP',
            business_unit_id: 1,
        },
        created_at: '2026-03-30T00:00:00Z',
        updated_at: '2026-03-30T00:00:00Z',
    };
}

function makeTasks(task: Task): PaginatedData<Task> {
    return {
        data: [task],
        links: {
            first: '/activity.task.index?page=1',
            last: '/activity.task.index?page=1',
            prev: null,
            next: null,
        },
        meta: {
            current_page: 1,
            from: 1,
            last_page: 1,
            links: [],
            path: '/activity.task.index',
            per_page: 15,
            to: 1,
            total: 1,
        },
    };
}

describe('Activity Dashboard calendar click behavior', () => {
    const stats: TaskStats = {
        total: 1,
        planned: 1,
        in_progress: 0,
        completed: 0,
        overdue: 0,
    };

    const filters: TaskFilters = {
        search: '',
        activity_type_id: '',
        status: '',
        date_from: '',
        date_to: '',
        member_user_id: '',
        scope: 'my',
    };

    beforeEach(() => {
        vi.clearAllMocks();
        window.localStorage.clear();
        window.history.pushState({}, '', '/activity/task?view=calendar');
        global.route = vi.fn((name: string, params?: Record<string, string | number>) => {
            const base = `/${name}`;

            if (!params || Object.keys(params).length === 0) {
                return base;
            }

            return `${base}?${new URLSearchParams(
                Object.entries(params).reduce<Record<string, string>>((carry, [key, value]) => {
                    carry[key] = String(value);

                    return carry;
                }, {})
            ).toString()}`;
        });
    });

    it('opens the modal flow from calendar events instead of navigating to the legacy page', async () => {
        render(
            <Dashboard
                stats={stats}
                tasks={makeTasks(makeTask())}
                activityTypes={[]}
                filters={filters}
                departmentUsers={[]}
                backdatePermission={null}
                allowedDateRange={{ from: '', to: '' }}
                backdateEnabled={false}
                prioritizedActivityTypes={[]}
            />
        );

        fireEvent.click(await screen.findByRole('button', { name: 'Trigger Calendar Event' }));

        await waitFor(() => {
            expect(screen.getByTestId('task-detail-modal')).toHaveTextContent('Prepare board pack');
        });

        expect(window.location.search).toContain('task=7');
        expect(window.location.search).toContain('modal=detail');

        fireEvent.click(screen.getByRole('button', { name: /close detail modal/i }));

        await waitFor(() => {
            expect(screen.queryByTestId('task-detail-modal')).not.toBeInTheDocument();
        });

        expect(window.location.search).not.toContain('task=');
        expect(window.location.search).not.toContain('modal=');
        expect(vi.mocked(router.visit)).not.toHaveBeenCalled();
    });

    it('opens the detail modal from query state when the backend provides a modal task', async () => {
        window.history.pushState({}, '', '/activity/task?task=7&modal=detail');

        render(
            <Dashboard
                stats={stats}
                tasks={makeTasks(makeTask())}
                activityTypes={[]}
                filters={filters}
                selectedTask={makeTask()}
                selectedTaskModal="detail"
                departmentUsers={[]}
                backdatePermission={null}
                allowedDateRange={{ from: '', to: '' }}
                backdateEnabled={false}
                prioritizedActivityTypes={[]}
            />
        );

        expect(await screen.findByTestId('task-detail-modal')).toHaveTextContent('Prepare board pack');
    });

    it('opens the edit modal from the hydrated detail modal', async () => {
        window.history.pushState({}, '', '/activity/task?task=7&modal=detail');
        const reloadSpy = vi.spyOn(router, 'reload').mockImplementation((options?: { onSuccess?: () => void }) => {
            options?.onSuccess?.();
        });

        render(
            <Dashboard
                stats={stats}
                tasks={makeTasks(makeTask())}
                activityTypes={[]}
                filters={filters}
                selectedTask={makeTask()}
                selectedTaskModal="detail"
                departmentUsers={[]}
                backdatePermission={null}
                allowedDateRange={{ from: '', to: '' }}
                backdateEnabled={false}
                prioritizedActivityTypes={[]}
            />
        );

        expect(await screen.findByTestId('task-detail-modal')).toHaveTextContent('Prepare board pack');
        fireEvent.click(screen.getByRole('button', { name: /edit from detail modal/i }));

        expect(await screen.findByTestId('task-form-modal')).toHaveTextContent('Prepare board pack');
        reloadSpy.mockRestore();
    });

    it('opens the edit modal from query state when the backend provides edit intent', async () => {
        window.history.pushState({}, '', '/activity/task?task=7&modal=edit');
        const reloadSpy = vi.spyOn(router, 'reload').mockImplementation((options?: { onSuccess?: () => void }) => {
            options?.onSuccess?.();
        });

        render(
            <Dashboard
                stats={stats}
                tasks={makeTasks(makeTask())}
                activityTypes={[]}
                filters={filters}
                selectedTask={makeTask()}
                selectedTaskModal="edit"
                departmentUsers={[]}
                backdatePermission={null}
                allowedDateRange={{ from: '', to: '' }}
                backdateEnabled={false}
                prioritizedActivityTypes={[]}
            />
        );

        expect(await screen.findByTestId('task-form-modal')).toHaveTextContent('Prepare board pack');
        expect(screen.queryByTestId('task-detail-modal')).not.toBeInTheDocument();
        reloadSpy.mockRestore();
    });

    it('opens the create modal from query state and preserves the selected date', async () => {
        window.history.pushState({}, '', '/activity/task?modal=create&date=2026-03-31');
        const reloadSpy = vi.spyOn(router, 'reload').mockImplementation((options?: { onSuccess?: () => void }) => {
            options?.onSuccess?.();
        });

        render(
            <Dashboard
                stats={stats}
                tasks={makeTasks(makeTask())}
                activityTypes={[]}
                filters={filters}
                departmentUsers={[]}
                backdatePermission={null}
                allowedDateRange={{ from: '', to: '' }}
                backdateEnabled={false}
                prioritizedActivityTypes={[]}
            />
        );

        expect(await screen.findByTestId('task-form-modal')).toHaveTextContent('create-task:2026-03-31');
        reloadSpy.mockRestore();
    });

    it('ignores invalid modal query values even when a task id is present', async () => {
        window.history.pushState({}, '', '/activity/task?task=7&modal=foo');

        render(
            <Dashboard
                stats={stats}
                tasks={makeTasks(makeTask())}
                activityTypes={[]}
                filters={filters}
                selectedTask={makeTask()}
                departmentUsers={[]}
                backdatePermission={null}
                allowedDateRange={{ from: '', to: '' }}
                backdateEnabled={false}
                prioritizedActivityTypes={[]}
            />
        );

        await waitFor(() => {
            expect(screen.queryByTestId('task-detail-modal')).not.toBeInTheDocument();
            expect(screen.queryByTestId('task-form-modal')).not.toBeInTheDocument();
        });
    });

    it('shows member filtering only in team scope and clears it when returning to my tasks', async () => {
        window.history.pushState({}, '', '/activity/task?view=list&page=3');
        const getSpy = vi.spyOn(router, 'get').mockImplementation(() => undefined);

        render(
            <Dashboard
                stats={stats}
                tasks={makeTasks(makeTask())}
                activityTypes={[]}
                filters={{ ...filters, scope: 'department' }}
                teamMembers={[
                    { id: 2, name: 'Member A' },
                    { id: 3, name: 'Member B' },
                ]}
                departmentUsers={[]}
                backdatePermission={null}
                allowedDateRange={{ from: '', to: '' }}
                backdateEnabled={false}
                prioritizedActivityTypes={[]}
            />
        );

        fireEvent.click(screen.getByRole('button', { name: /filter/i }));
        fireEvent.change(screen.getByLabelText('Member'), { target: { value: '2' } });

        await waitFor(() => {
            expect(getSpy).toHaveBeenCalledWith(
                '/activity.task.index',
                expect.objectContaining({
                    member_user_id: '2',
                    scope: 'department',
                    view: 'list',
                }),
                expect.any(Object)
            );
        });

        expect(getSpy.mock.calls.at(-1)?.[1]).not.toHaveProperty('page');

        fireEvent.click(screen.getByRole('button', { name: /my tasks/i }));

        await waitFor(() => {
            expect(getSpy.mock.calls.at(-1)?.[1]).not.toHaveProperty('member_user_id');
        });

        getSpy.mockRestore();
    });
});
