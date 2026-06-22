import { beforeEach, describe, expect, it, vi } from 'vitest';
import { fireEvent, render, screen } from '@testing-library/react';
import { router } from '@inertiajs/react';
import ActivityDashboard from '@/Pages/Activity/ActivityDashboard';

vi.mock('@inertiajs/react', async () => {
    const actual = await vi.importActual<typeof import('@inertiajs/react')>('@inertiajs/react');

    return {
        ...actual,
        Head: () => null,
        router: {
            ...actual.router,
            get: vi.fn(),
            post: vi.fn(),
            visit: vi.fn(),
        },
    };
});

describe('ActivityDashboard export', () => {
    beforeEach(() => {
        vi.clearAllMocks();
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

    it('uses browser navigation for export downloads in personal view', () => {
        const openSpy = vi.spyOn(window, 'open').mockImplementation(() => null);

        render(
            <ActivityDashboard
                personalStats={{
                    total: 4,
                    completed: 1,
                    in_progress: 2,
                    overdue: 0,
                    planned: 1,
                    completed_this_month: 1,
                }}
                personalVisuals={{
                    roadmap: {
                        data: [],
                        current_page: 1,
                        last_page: 1,
                        per_page: 10,
                        total: 0,
                        links: [],
                        prev_page_url: null,
                        next_page_url: null,
                    },
                    upcoming: [],
                    distribution: [],
                }}
                departmentStats={null}
                departmentVisuals={null}
                canViewReports={false}
                executiveStats={null}
                queryParams={{}}
            />
        );

        fireEvent.click(screen.getByRole('button', { name: /export report/i }));

        expect(openSpy).toHaveBeenCalledWith('/activity.task.export?scope=my', '_self');

        openSpy.mockRestore();
    });

    it('includes member_user_id in department export and preserves department period filters', () => {
        const openSpy = vi.spyOn(window, 'open').mockImplementation(() => null);
        const getSpy = vi.spyOn(router, 'get');

        render(
            <ActivityDashboard
                personalStats={{
                    total: 0,
                    completed: 0,
                    in_progress: 0,
                    overdue: 0,
                    planned: 0,
                    completed_this_month: 0,
                }}
                personalVisuals={{
                    roadmap: {
                        data: [],
                        current_page: 1,
                        last_page: 1,
                        per_page: 10,
                        total: 0,
                        links: [],
                        prev_page_url: null,
                        next_page_url: null,
                    },
                    upcoming: [],
                    distribution: [],
                    focus_breakdown: {
                        total_activities: 0,
                        top_category: { name: 'Tanpa Kategori', count: 0, percentage_of_report: 0 },
                        top_subcategory: { name: 'Tanpa Sub Kategori', count: 0, percentage_of_report: 0 },
                        items: [],
                    },
                }}
                departmentStats={{
                    total: 1,
                    completed: 0,
                    in_progress: 1,
                    overdue: 0,
                    planned: 0,
                    completed_this_month: 0,
                }}
                departmentVisuals={{
                    roadmap: {
                        data: [],
                        current_page: 1,
                        last_page: 1,
                        per_page: 20,
                        total: 0,
                        links: [],
                        prev_page_url: null,
                        next_page_url: null,
                    },
                    upcoming: [],
                    distribution: [],
                    focus_breakdown: {
                        total_activities: 0,
                        top_category: { name: 'Tanpa Kategori', count: 0, percentage_of_report: 0 },
                        top_subcategory: { name: 'Tanpa Sub Kategori', count: 0, percentage_of_report: 0 },
                        items: [],
                    },
                    bottleneck: 0,
                    top_category: '-',
                }}
                departmentMembers={[
                    { id: 9, name: 'Pram' },
                    { id: 10, name: 'Hanung' },
                ]}
                canViewReports={false}
                executiveStats={null}
                queryParams={{ member_user_id: '9', dept_distribution_period: 'month' }}
            />
        );

        fireEvent.click(screen.getByRole('button', { name: /filter/i }));
        fireEvent.change(screen.getByLabelText('Member'), { target: { value: '10' } });

        expect(getSpy).toHaveBeenCalledWith(
            '/activity.dashboard',
            expect.objectContaining({
                dept_distribution_period: 'month',
                member_user_id: '10',
            }),
            expect.any(Object)
        );

        fireEvent.click(screen.getByRole('button', { name: /export report/i }));

        expect(openSpy).toHaveBeenCalledWith('/activity.task.export?scope=department&member_user_id=10', '_self');

        getSpy.mockRestore();
        openSpy.mockRestore();
    });

    it('clears department member focus from the dataset when switching away from department mode', () => {
        const getSpy = vi.spyOn(router, 'get');

        render(
            <ActivityDashboard
                personalStats={{
                    total: 2,
                    completed: 1,
                    in_progress: 1,
                    overdue: 0,
                    planned: 0,
                    completed_this_month: 1,
                }}
                personalVisuals={{
                    roadmap: {
                        data: [],
                        current_page: 1,
                        last_page: 1,
                        per_page: 10,
                        total: 0,
                        links: [],
                        prev_page_url: null,
                        next_page_url: null,
                    },
                    upcoming: [],
                    distribution: [],
                    focus_breakdown: {
                        total_activities: 0,
                        top_category: { name: 'Tanpa Kategori', count: 0, percentage_of_report: 0 },
                        top_subcategory: { name: 'Tanpa Sub Kategori', count: 0, percentage_of_report: 0 },
                        items: [],
                    },
                }}
                departmentStats={{
                    total: 1,
                    completed: 0,
                    in_progress: 1,
                    overdue: 0,
                    planned: 0,
                    completed_this_month: 0,
                }}
                departmentVisuals={{
                    roadmap: {
                        data: [],
                        current_page: 1,
                        last_page: 1,
                        per_page: 20,
                        total: 0,
                        links: [],
                        prev_page_url: null,
                        next_page_url: null,
                    },
                    upcoming: [],
                    distribution: [],
                    focus_breakdown: {
                        total_activities: 0,
                        top_category: { name: 'Tanpa Kategori', count: 0, percentage_of_report: 0 },
                        top_subcategory: { name: 'Tanpa Sub Kategori', count: 0, percentage_of_report: 0 },
                        items: [],
                    },
                    bottleneck: 0,
                    top_category: '-',
                }}
                departmentMembers={[{ id: 9, name: 'Pram' }]}
                canViewReports={false}
                executiveStats={null}
                queryParams={{ member_user_id: '9', dept_distribution_period: 'month', distribution_period: 'week' }}
            />
        );

        fireEvent.click(screen.getByRole('button', { name: 'Personal' }));

        expect(getSpy).toHaveBeenCalledWith(
            '/activity.dashboard',
            expect.objectContaining({
                distribution_period: 'week',
                dept_distribution_period: 'month',
            }),
            expect.any(Object)
        );

        expect(getSpy.mock.calls.at(-1)?.[1]).not.toHaveProperty('member_user_id');

        getSpy.mockRestore();
    });

    it('shows more in-progress work items on the first page to reduce empty space', () => {
        const inProgressTasks = Array.from({ length: 9 }, (_, index) => ({
            id: index + 1,
            task_title: `Task ${index + 1}`,
            status: 'in_progress',
            started_at: '2026-03-31T08:00:00Z',
            participants: [
                {
                    id: index + 1,
                    name: `Member ${index + 1}`,
                    primary_position: { name: 'Staff' },
                },
            ],
        }));

        render(
            <ActivityDashboard
                personalStats={{
                    total: 0,
                    completed: 0,
                    in_progress: 0,
                    overdue: 0,
                    planned: 0,
                    completed_this_month: 0,
                }}
                personalVisuals={{
                    roadmap: {
                        data: [],
                        current_page: 1,
                        last_page: 1,
                        per_page: 10,
                        total: 0,
                        links: [],
                        prev_page_url: null,
                        next_page_url: null,
                    },
                    upcoming: [],
                    distribution: [],
                    focus_breakdown: {
                        total_activities: 0,
                        top_category: { name: 'Tanpa Kategori', count: 0, percentage_of_report: 0 },
                        top_subcategory: { name: 'Tanpa Sub Kategori', count: 0, percentage_of_report: 0 },
                        items: [],
                    },
                }}
                departmentStats={{
                    total: 9,
                    completed: 0,
                    in_progress: 9,
                    overdue: 0,
                    planned: 0,
                    completed_this_month: 0,
                }}
                departmentVisuals={{
                    roadmap: {
                        data: inProgressTasks,
                        current_page: 1,
                        last_page: 1,
                        per_page: 20,
                        total: 9,
                        links: [],
                        prev_page_url: null,
                        next_page_url: null,
                    },
                    upcoming: [],
                    distribution: [],
                    focus_breakdown: {
                        total_activities: 0,
                        top_category: { name: 'Tanpa Kategori', count: 0, percentage_of_report: 0 },
                        top_subcategory: { name: 'Tanpa Sub Kategori', count: 0, percentage_of_report: 0 },
                        items: [],
                    },
                    bottleneck: 0,
                    top_category: '-',
                }}
                canViewReports={false}
                executiveStats={null}
                queryParams={{}}
            />
        );

        expect(screen.getByText('Working on: Task 8')).toBeInTheDocument();
        expect(screen.getByText('Working on: Task 9')).toBeInTheDocument();
        expect(screen.queryByRole('button', { name: '2' })).not.toBeInTheDocument();
    });
});
