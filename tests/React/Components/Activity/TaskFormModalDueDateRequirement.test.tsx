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
        it('shows editable start time pre-filled from started_at when editing in_progress task', async () => {
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

            // Start Time input is always shown for in_progress tasks with started_at
            const startTimeInput = screen.getByLabelText(/Start Time/i) as HTMLInputElement;
            expect(startTimeInput).toBeInTheDocument();
            expect(startTimeInput.value).toBe('08:30');
        });

        it('keeps start time editable when task_date changes for in_progress task', async () => {
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

            // Start Time input remains visible and editable after task_date change
            const startTimeInput = screen.getByLabelText(/Start Time/i) as HTMLInputElement;
            expect(startTimeInput).toBeInTheDocument();
            expect(startTimeInput.value).toBe('08:30');
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
            // Start Time input is shown (editable) for in_progress tasks with started_at
            const startTimeInput = screen.getByLabelText(/Start Time/i) as HTMLInputElement;
            expect(startTimeInput).toBeInTheDocument();
            expect(startTimeInput.value).toBe('08:30');
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

        it('shows editable execution inputs for completed task', async () => {
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

            // Completed tasks always show editable Start Time, End Time, and Completed Date
            const startTimeInput = screen.getByLabelText(/Start Time/i) as HTMLInputElement;
            expect(startTimeInput).toBeInTheDocument();
            expect(startTimeInput.value).toBe('08:30');

            const endTimeInput = screen.getByLabelText(/End Time/i) as HTMLInputElement;
            expect(endTimeInput).toBeInTheDocument();
            expect(endTimeInput.value).toBe('10:45');

            expect(screen.getByLabelText(/Completed Date/i)).toBeInTheDocument();
        });

        it('keeps execution inputs editable for completed task even when task_date matches started_at date', async () => {
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

            // Task date and Completed Date both show '2026-04-13'; target the task_date input (first match)
            const dateInputs = screen.getAllByDisplayValue('2026-04-13');
            const taskDateInput = dateInputs[0];
            await act(async () => {
                fireEvent.change(taskDateInput, { target: { value: '2026-04-12' } });
            });

            // Completed tasks always show editable Start Time and End Time
            expect(screen.getByLabelText(/Start Time/i)).toBeInTheDocument();
            expect(screen.getByLabelText(/End Time/i)).toBeInTheDocument();
            expect(screen.getByLabelText(/Completed Date/i)).toBeInTheDocument();
        });

        it('shows all execution fields when finishing an in_progress task with existing started_at', async () => {
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

            // Completed status always shows all editable execution fields
            const startTimeInput = screen.getByLabelText(/Start Time/i) as HTMLInputElement;
            expect(startTimeInput).toBeInTheDocument();
            expect(startTimeInput.value).toBe('08:30');
            expect(screen.getByLabelText(/Completed Date/i)).toBeInTheDocument();
            expect(screen.getByLabelText(/End Time/i)).toBeInTheDocument();
        });
    });
});
