import { act, fireEvent, render, screen } from '@testing-library/react'
import { router } from '@inertiajs/react'
import { describe, expect, it, vi, beforeEach } from 'vitest'
import { ActivityDataTable } from '@/components/activity/ActivityDataTable'
import { showToast } from '@/components/ui/toast'
import type { PaginatedData, Task, TaskFilters, TaskStats } from '@/types'

vi.mock('@/components/ui/toast', () => ({
    showToast: {
        success: vi.fn(),
        error: vi.fn(),
        warning: vi.fn(),
        info: vi.fn(),
        loading: vi.fn(),
        dismiss: vi.fn(),
        promise: vi.fn(),
    },
}))

function makeTask(): Task {
    return {
        id: 1,
        task_title: 'Review monthly ops report',
        task_description: null,
        status: 'planned',
        priority: 'medium',
        due_date: '2026-04-21',
        task_date: '2026-04-10',
        business_unit_id: 1,
        department_id: 1,
        activity_type_id: 1,
        created_by: 1,
        activity_type: {
            id: 1,
            code: 'OPS',
            name: 'Operations',
            color: '#16599c',
            is_active: true,
            sort_order: 1,
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
            name: 'Operations',
            code: 'OPS',
            business_unit_id: 1,
            manager_id: null,
            cost_center: null,
            is_active: true,
        },
        created_at: '2026-04-10T00:00:00Z',
        updated_at: '2026-04-10T00:00:00Z',
    }
}

function makePaginatedTasks(task: Task): PaginatedData<Task> {
    return {
        data: [task],
        links: {
            first: '',
            last: '',
            prev: null,
            next: null,
        },
        meta: {
            current_page: 1,
            from: 1,
            last_page: 1,
            links: [],
            path: '/activity/task',
            per_page: 15,
            to: 1,
            total: 1,
        },
    }
}

describe('ActivityDataTable status dropdown', () => {
    const stats: TaskStats = {
        total: 1,
        planned: 1,
        in_progress: 0,
        completed: 0,
        overdue: 0,
    }

    const filters: TaskFilters = {
        search: '',
        activity_type_id: '',
        status: '',
        date_from: '',
        date_to: '',
        member_user_id: '',
        scope: 'my',
    }

    beforeEach(() => {
        vi.clearAllMocks()
    })

    it('shows the backend validation guidance and opens edit flow for direct complete without started_at', async () => {
        const onEditTask = vi.fn()
        render(
            <ActivityDataTable
                tasks={makePaginatedTasks(makeTask())}
                stats={stats}
                filters={filters}
                onEditTask={onEditTask}
            />
        )

        await act(async () => {
            fireEvent.click(screen.getByRole('button', { name: /planned/i }))
        })

        await act(async () => {
            fireEvent.click(screen.getByRole('button', { name: /completed/i }))
        })

        expect(vi.mocked(router.put)).toHaveBeenCalledWith(
            '/activity.task.update?task=1',
            { status: 'completed' },
            expect.objectContaining({
                preserveScroll: true,
                preserveState: false,
                onError: expect.any(Function),
            })
        )

        await act(async () => {
            vi.mocked(router.put).mock.calls[0][2]?.onError?.({
                status: ['Task uses a historical date. Please confirm actual execution time.'],
            })
        })

        expect(showToast.error).toHaveBeenCalledWith(
            'Task uses a historical date. Please confirm actual execution time.'
        )
        expect(onEditTask).toHaveBeenCalledWith(expect.objectContaining({ id: 1 }))
    })
})
