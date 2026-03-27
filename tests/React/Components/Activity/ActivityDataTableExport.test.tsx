import { beforeEach, describe, expect, it, vi } from 'vitest';
import { fireEvent, render, screen } from '@testing-library/react';
import { ActivityDataTable } from '@/components/activity/ActivityDataTable';
import type { PaginatedData, Task, TaskStats } from '@/types';

function makeTask(): Task {
    return {
        id: 77,
        task_title: 'Review activity export flow',
        task_description: null,
        status: 'planned',
        priority: 'medium',
        due_date: '2026-03-27',
        business_unit_id: 1,
        department_id: 1,
        activity_type_id: 1,
        created_by: 1,
        activity_type: {
            id: 1,
            name: 'Action Plan',
            code: 'PLAN',
            color: '#2563eb',
        },
        creator: {
            id: 1,
            name: 'Test User',
            email: 'test@example.com',
            role: 'user',
        },
        participants: [],
        department: {
            id: 1,
            name: 'Finance',
            code: 'FIN',
            business_unit_id: 1,
        },
        created_at: '2026-03-20T00:00:00Z',
        updated_at: '2026-03-20T00:00:00Z',
    };
}

describe('ActivityDataTable export', () => {
    const tasks: PaginatedData<Task> = {
        data: [makeTask()],
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

    const stats: TaskStats = {
        total: 1,
        planned: 1,
        in_progress: 0,
        completed: 0,
        overdue: 0,
    };

    beforeEach(() => {
        vi.clearAllMocks();
        global.route = vi.fn((name: string, params?: Record<string, string | number>) => {
            const base = `/${name}`;

            if (!params || Object.keys(params).length === 0) {
                return base;
            }

            return `${base}?${new URLSearchParams(
                Object.entries(params).reduce<Record<string, string>>((carry, [key, value]) => {
                    if (value !== undefined) {
                        carry[key] = String(value);
                    }

                    return carry;
                }, {})
            ).toString()}`;
        });
    });

    it('uses browser navigation for xlsx export downloads', () => {
        const openSpy = vi.spyOn(window, 'open').mockImplementation(() => null);

        render(
            <ActivityDataTable
                tasks={tasks}
                stats={stats}
                filters={{
                    search: '',
                    activity_type_id: '',
                    status: '',
                    date_from: '',
                    date_to: '',
                    scope: 'my',
                }}
            />
        );

        fireEvent.click(screen.getByRole('button', { name: /export xlsx/i }));

        expect(openSpy).toHaveBeenCalledWith('/activity.task.export?scope=my', '_self');

        openSpy.mockRestore();
    });
});
