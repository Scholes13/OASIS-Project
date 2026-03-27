import { beforeEach, describe, expect, it, vi } from 'vitest';
import { fireEvent, render, screen } from '@testing-library/react';
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
});
