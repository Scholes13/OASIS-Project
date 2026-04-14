import * as React from 'react';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { act, fireEvent, render, screen } from '@testing-library/react';
import * as InertiaReact from '@inertiajs/react';
import { router } from '@inertiajs/react';
import { TaskFormModal } from '@/components/activity/TaskFormModal';
import type { Task } from '@/types';

describe('TaskFormModal create-again behavior', () => {
    beforeEach(() => {
        vi.clearAllMocks();

        if (!Element.prototype.getAnimations) {
            Object.defineProperty(Element.prototype, 'getAnimations', {
                configurable: true,
                value: vi.fn(() => []),
            });
        }

        vi.spyOn(InertiaReact, 'useForm').mockImplementation((initialData: Record<string, unknown>) => {
            const initialRef = React.useRef(initialData);
            const [data, setDataState] = React.useState(initialData);

            const setData = React.useCallback((
                keyOrUpdater: string | Record<string, unknown> | ((previous: Record<string, unknown>) => Record<string, unknown>),
                maybeValue?: unknown
            ) => {
                if (typeof keyOrUpdater === 'function') {
                    setDataState((previous) => keyOrUpdater(previous));
                    return;
                }

                if (typeof keyOrUpdater === 'string') {
                    setDataState((previous) => ({
                        ...previous,
                        [keyOrUpdater]: maybeValue,
                    }));
                    return;
                }

                setDataState(keyOrUpdater);
            }, []);

            const post = React.useCallback((url: string, options?: Record<string, unknown>) => {
                vi.mocked(router.post)(url, data, options);
            }, [data]);

            const put = React.useCallback((url: string, options?: Record<string, unknown>) => {
                vi.mocked(router.put)(url, data, options);
            }, [data]);

            const reset = React.useCallback(() => {
                setDataState(initialRef.current);
            }, []);

            return {
                data,
                setData,
                post,
                put,
                processing: false,
                errors: {},
                reset,
            } as ReturnType<typeof InertiaReact.useForm>;
        });
    });

    it('shows an unchecked create-again checkbox only in create mode', async () => {
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

        const checkbox = screen.getByLabelText(/create another task\?/i) as HTMLInputElement;
        expect(checkbox.checked).toBe(false);
    });

    it('closes the modal after successful create when create-again is not checked', async () => {
        const onClose = vi.fn();

        await act(async () => {
            render(
                <TaskFormModal
                    open={true}
                    onClose={onClose}
                    task={null}
                    activityTypes={[]}
                    departmentUsers={[]}
                    allowedDateRange={{ from: '2026-01-01', to: '2026-12-31' }}
                />
            );
        });

        await act(async () => {
            fireEvent.change(screen.getByPlaceholderText('What needs to be done?'), {
                target: { value: 'Prepare weekly report' },
            });
        });

        await act(async () => {
            fireEvent.submit(document.getElementById('task-form')!);
        });

        const postOptions = vi.mocked(router.post).mock.calls[0]?.[2];
        expect(postOptions).toBeDefined();

        await act(async () => {
            postOptions?.onSuccess?.({} as never);
        });

        expect(onClose).toHaveBeenCalledTimes(1);
    });

    it('keeps the modal open and resets the form after successful create when create-again is checked', async () => {
        const onClose = vi.fn();

        await act(async () => {
            render(
                <TaskFormModal
                    open={true}
                    onClose={onClose}
                    task={null}
                    activityTypes={[]}
                    departmentUsers={[]}
                    allowedDateRange={{ from: '2026-01-01', to: '2026-12-31' }}
                />
            );
        });

        await act(async () => {
            fireEvent.change(screen.getByPlaceholderText('What needs to be done?'), {
                target: { value: 'Prepare weekly report' },
            });
            fireEvent.change(screen.getByDisplayValue(/\d{4}-\d{2}-\d{2}/), {
                target: { value: '2026-04-14' },
            });
            fireEvent.click(screen.getByLabelText(/create another task\?/i));
        });

        await act(async () => {
            fireEvent.submit(document.getElementById('task-form')!);
        });

        const postOptions = vi.mocked(router.post).mock.calls[0]?.[2];
        expect(postOptions).toBeDefined();

        await act(async () => {
            postOptions?.onSuccess?.({} as never);
        });

        expect(onClose).not.toHaveBeenCalled();
        expect(screen.getByPlaceholderText('What needs to be done?')).toHaveValue('');
        expect(screen.getByDisplayValue('2026-04-14')).toBeInTheDocument();
        expect(screen.getByLabelText(/create another task\?/i)).toBeChecked();
    });

    it('hides the create-again checkbox in edit mode', async () => {
        const task: Task = {
            id: 1,
            task_title: 'Existing task',
            task_description: null,
            status: 'planned',
            priority: 'medium',
            due_date: '2026-04-15',
            task_date: '2026-04-13',
            business_unit_id: 1,
            department_id: 1,
            activity_type_id: 1,
            created_by: 1,
            activity_type: { id: 1, code: 'TEST', name: 'Test', color: 'blue', sub_activities: [] },
            creator: { id: 1, name: 'User', email: 'user@test.com', role: 'user' },
            participants: [],
            department: { id: 1, name: 'Test Dept', code: 'TD', business_unit_id: 1 },
            created_at: '2026-04-13T08:00:00+07:00',
            updated_at: '2026-04-13T08:00:00+07:00',
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

        expect(screen.queryByLabelText(/create another task\?/i)).not.toBeInTheDocument();
    });
});
