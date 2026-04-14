import { describe, expect, it } from 'vitest';
import { act, fireEvent, render, screen } from '@testing-library/react';
import { TaskFormModal } from '@/components/activity/TaskFormModal';
import type { Task } from '@/types';
import { beforeEach, vi } from 'vitest';

const getTomorrowLocalDate = (): string => {
    const date = new Date();
    date.setDate(date.getDate() + 1);

    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
};

beforeEach(() => {
    if (!Element.prototype.getAnimations) {
        Object.defineProperty(Element.prototype, 'getAnimations', {
            configurable: true,
            value: vi.fn(() => []),
        });
    }
});

describe('TaskFormModal due date requirement', () => {
    it('shows required indicator for Due Date when status is not completed', async () => {
        await act(async () => {
            render(
                <TaskFormModal
                    open={true}
                    onClose={() => {}}
                    task={null}
                    activityTypes={[]}
                    departmentUsers={[]}
                    allowedDateRange={{ from: '2026-01-01', to: '2026-12-31' }}
                />
            );
        });

        const dueDateLabel = screen.getByText('Due Date', { selector: 'label' });
        expect(dueDateLabel.querySelector('span')?.textContent?.trim()).toBe('*');

        const dueDateInput = screen.getByLabelText(/Due Date/i) as HTMLInputElement;
        expect(dueDateInput.required).toBe(true);
    });

    it('hides required indicator for Due Date when status is completed', async () => {
        await act(async () => {
            render(
                <TaskFormModal
                    open={true}
                    onClose={() => {}}
                    task={null}
                    activityTypes={[]}
                    departmentUsers={[]}
                    allowedDateRange={{ from: '2026-01-01', to: '2026-12-31' }}
                />
            );
        });

        await act(async () => {
            fireEvent.change(screen.getByDisplayValue('To Do'), { target: { value: 'completed' } });
        });

        const dueDateLabel = screen.getByText('Due Date', { selector: 'label' });
        expect(dueDateLabel.querySelector('span')).toBeNull();

        const dueDateInput = screen.getByLabelText(/Due Date/i) as HTMLInputElement;
        expect(dueDateInput.required).toBe(false);
    });

    describe('execution timestamp display and correction', () => {
        it('derives start time from started_at when editing in_progress task', async () => {
            const task: Task = {
                id: 1,
                task_title: 'Test Task',
                task_description: null,
                status: 'in_progress',
                priority: 'medium',
                due_date: '2026-04-15',
                task_date: '2026-04-13',
                started_at: '2026-04-13T08:30:00+07:00',
                business_unit_id: 1,
                department_id: 1,
                activity_type_id: 1,
                created_by: 1,
                activity_type: { id: 1, code: 'TEST', name: 'Test', color: 'blue' },
                creator: { id: 1, name: 'User', email: 'user@test.com', role: 'user' },
                participants: [],
                department: { id: 1, name: 'Test Dept', code: 'TD', business_unit_id: 1 },
                created_at: '2026-04-13T08:00:00+07:00',
                updated_at: '2026-04-13T08:30:00+07:00',
            };

            await act(async () => {
                render(
                    <TaskFormModal
                        open={true}
                        onClose={() => {}}
                        task={task}
                        activityTypes={[]}
                        departmentUsers={[]}
                        allowedDateRange={{ from: '2026-01-01', to: '2026-12-31' }}
                    />
                );
            });

            expect(screen.queryByText(/Started At/i)).toBeInTheDocument();
            expect(screen.queryByLabelText(/Start Time/i)).not.toBeInTheDocument();
        });

        it('keeps started task read only and previews shifted date when task_date changes', async () => {
            const task: Task = {
                id: 1,
                task_title: 'Test Task',
                task_description: null,
                status: 'in_progress',
                priority: 'medium',
                due_date: '2026-04-15',
                task_date: '2026-04-13',
                started_at: '2026-04-13T08:30:00+07:00',
                business_unit_id: 1,
                department_id: 1,
                activity_type_id: 1,
                created_by: 1,
                activity_type: { id: 1, code: 'TEST', name: 'Test', color: 'blue' },
                creator: { id: 1, name: 'User', email: 'user@test.com', role: 'user' },
                participants: [],
                department: { id: 1, name: 'Test Dept', code: 'TD', business_unit_id: 1 },
                created_at: '2026-04-13T08:00:00+07:00',
                updated_at: '2026-04-13T08:30:00+07:00',
            };

            await act(async () => {
                render(
                    <TaskFormModal
                        open={true}
                        onClose={() => {}}
                        task={task}
                        activityTypes={[]}
                        departmentUsers={[]}
                        allowedDateRange={{ from: '2026-01-01', to: '2026-12-31' }}
                    />
                );
            });

            const taskDateInput = screen.getByDisplayValue('2026-04-13');
            await act(async () => {
                fireEvent.change(taskDateInput, { target: { value: '2026-04-14' } });
            });

            expect(screen.queryByLabelText(/Start Time/i)).not.toBeInTheDocument();
            expect(screen.queryByText(/Task date changed/i)).not.toBeInTheDocument();
            expect(screen.getByText('2026-04-14 08:30')).toBeInTheDocument();
        });

        it('normalizes iso task and due dates so edit modal shows initial values', async () => {
            const task: Task = {
                id: 1,
                task_title: 'Deploy OASIS Update',
                task_description: null,
                status: 'in_progress',
                priority: 'medium',
                due_date: '2026-04-14T00:00:00.000000Z',
                task_date: '2026-04-13T00:00:00.000000Z',
                started_at: '2026-04-13T08:30:00+07:00',
                business_unit_id: 1,
                department_id: 1,
                activity_type_id: 1,
                created_by: 1,
                activity_type: { id: 1, code: 'TEST', name: 'Website', color: 'blue' },
                creator: { id: 1, name: 'User', email: 'user@test.com', role: 'user' },
                participants: [],
                department: { id: 1, name: 'Test Dept', code: 'TD', business_unit_id: 1 },
                created_at: '2026-04-13T08:00:00+07:00',
                updated_at: '2026-04-13T08:30:00+07:00',
            };

            await act(async () => {
                render(
                    <TaskFormModal
                        open={true}
                        onClose={() => {}}
                        task={task}
                        activityTypes={[{ id: 1, code: 'TEST', name: 'Website', color: 'blue', sub_activities: [] }]}
                        departmentUsers={[]}
                        allowedDateRange={{ from: '2026-01-01', to: '2026-12-31' }}
                    />
                );
            });

            expect(screen.getByDisplayValue('2026-04-13')).toBeInTheDocument();
            expect(screen.getByDisplayValue('2026-04-14')).toBeInTheDocument();
            expect(screen.queryByText(/Task date changed/i)).not.toBeInTheDocument();
            expect(screen.queryByLabelText(/Start Time/i)).not.toBeInTheDocument();
            expect(screen.getByText(/Started At/i)).toBeInTheDocument();
        });

        it('requires start time when creating a future dated in_progress task', async () => {
            const tomorrow = getTomorrowLocalDate();

            await act(async () => {
                render(
                    <TaskFormModal
                        open={true}
                        onClose={() => {}}
                        task={null}
                        activityTypes={[]}
                        departmentUsers={[]}
                        allowedDateRange={{ from: '2026-01-01', to: '2026-12-31' }}
                    />
                );
            });

            await act(async () => {
                fireEvent.change(screen.getByDisplayValue('To Do'), { target: { value: 'in_progress' } });
                fireEvent.change(screen.getByDisplayValue(/\d{4}-\d{2}-\d{2}/), { target: { value: tomorrow } });
            });

            expect(screen.getByLabelText(/Start Time/i)).toBeInTheDocument();
        });

        it('shows read-only execution summary for completed task without date changes', async () => {
            const task: Task = {
                id: 1,
                task_title: 'Test Task',
                task_description: null,
                status: 'completed',
                priority: 'medium',
                due_date: '2026-04-15',
                task_date: '2026-04-13',
                started_at: '2026-04-13T08:30:00+07:00',
                completed_at: '2026-04-13T10:45:00+07:00',
                business_unit_id: 1,
                department_id: 1,
                activity_type_id: 1,
                created_by: 1,
                activity_type: { id: 1, code: 'TEST', name: 'Test', color: 'blue' },
                creator: { id: 1, name: 'User', email: 'user@test.com', role: 'user' },
                participants: [],
                department: { id: 1, name: 'Test Dept', code: 'TD', business_unit_id: 1 },
                created_at: '2026-04-13T08:00:00+07:00',
                updated_at: '2026-04-13T10:45:00+07:00',
            };

            await act(async () => {
                render(
                    <TaskFormModal
                        open={true}
                        onClose={() => {}}
                        task={task}
                        activityTypes={[]}
                        departmentUsers={[]}
                        allowedDateRange={{ from: '2026-01-01', to: '2026-12-31' }}
                    />
                );
            });

            expect(screen.queryByText(/Started At/i)).toBeInTheDocument();
            expect(screen.queryByText(/Completed At/i)).toBeInTheDocument();
            expect(screen.queryByText(/Duration/i)).toBeInTheDocument();
            
            expect(screen.queryByLabelText(/Start Time/i)).not.toBeInTheDocument();
            expect(screen.queryByLabelText(/End Time/i)).not.toBeInTheDocument();
        });

        it('keeps completed task start time read only when task_date matches stored started_at date', async () => {
            const task: Task = {
                id: 1,
                task_title: 'Test Task',
                task_description: null,
                status: 'completed',
                priority: 'medium',
                due_date: '2026-04-15',
                task_date: '2026-04-13',
                started_at: '2026-04-12T08:30:00+07:00',
                completed_at: '2026-04-13T10:45:00+07:00',
                business_unit_id: 1,
                department_id: 1,
                activity_type_id: 1,
                created_by: 1,
                activity_type: { id: 1, code: 'TEST', name: 'Test', color: 'blue' },
                creator: { id: 1, name: 'User', email: 'user@test.com', role: 'user' },
                participants: [],
                department: { id: 1, name: 'Test Dept', code: 'TD', business_unit_id: 1 },
                created_at: '2026-04-13T08:00:00+07:00',
                updated_at: '2026-04-13T10:45:00+07:00',
            };

            await act(async () => {
                render(
                    <TaskFormModal
                        open={true}
                        onClose={() => {}}
                        task={task}
                        activityTypes={[]}
                        departmentUsers={[]}
                        allowedDateRange={{ from: '2026-01-01', to: '2026-12-31' }}
                    />
                );
            });

            await act(async () => {
                fireEvent.change(screen.getByDisplayValue('2026-04-13'), { target: { value: '2026-04-12' } });
            });

            expect(screen.queryByLabelText(/Start Time/i)).not.toBeInTheDocument();
            expect(screen.queryByLabelText(/End Time/i)).not.toBeInTheDocument();
            expect(screen.getByText(/Started At/i)).toBeInTheDocument();
        });

        it('requires only completion fields when finishing an in_progress task with existing started_at', async () => {
            const task: Task = {
                id: 1,
                task_title: 'Test Task',
                task_description: null,
                status: 'in_progress',
                priority: 'medium',
                due_date: '2026-04-15',
                task_date: '2026-04-13',
                started_at: '2026-04-13T08:30:00+07:00',
                business_unit_id: 1,
                department_id: 1,
                activity_type_id: 1,
                created_by: 1,
                activity_type: { id: 1, code: 'TEST', name: 'Test', color: 'blue' },
                creator: { id: 1, name: 'User', email: 'user@test.com', role: 'user' },
                participants: [],
                department: { id: 1, name: 'Test Dept', code: 'TD', business_unit_id: 1 },
                created_at: '2026-04-13T08:00:00+07:00',
                updated_at: '2026-04-13T08:30:00+07:00',
            };

            await act(async () => {
                render(
                    <TaskFormModal
                        open={true}
                        onClose={() => {}}
                        task={task}
                        activityTypes={[]}
                        departmentUsers={[]}
                        allowedDateRange={{ from: '2026-01-01', to: '2026-12-31' }}
                    />
                );
            });

            await act(async () => {
                fireEvent.change(screen.getByDisplayValue('In Progress'), { target: { value: 'completed' } });
            });

            expect(screen.queryByLabelText(/Start Time/i)).not.toBeInTheDocument();
            expect(screen.getByLabelText(/Completed Date/i)).toBeInTheDocument();
            expect(screen.getByLabelText(/End Time/i)).toBeInTheDocument();
            expect(screen.getByText(/Started At/i)).toBeInTheDocument();
        });
    });
});
