import { beforeEach, describe, expect, it, vi } from 'vitest'
import { fireEvent, render, screen } from '@testing-library/react'
import { router } from '@inertiajs/react'
import { TaskDetailModal } from '@/components/activity/TaskDetailModal'
import type { Task, TaskStatus } from '@/types'

function makeTask(status: TaskStatus = 'in_progress'): Task {
    return {
        id: 99,
        task_title: 'Review Q3 Financial Report Analysis',
        task_description: null,
        status,
        priority: 'medium',
        due_date: '2026-02-25',
        business_unit_id: 1,
        department_id: 1,
        activity_type_id: 1,
        created_by: 1,
        activity_type: {
            id: 1,
            code: 'PLAN',
            name: 'Action Plan',
            color: 'gray',
        },
        creator: {
            id: 1,
            name: 'Pramuji Arif Yulianto',
            email: 'pramuji@example.com',
            role: 'user',
        },
        participants: [
            {
                id: 10,
                user_id: 10,
                employee_task_id: 99,
                name: 'Pramuji Arif Yulianto',
            },
        ],
        department: {
            id: 1,
            name: 'Business & Administrative Services',
            code: 'BAS',
            business_unit_id: 1,
        },
        created_at: '2026-02-20T00:00:00Z',
        updated_at: '2026-02-20T00:00:00Z',
    }
}

describe('TaskDetailModal', () => {
    beforeEach(() => {
        vi.clearAllMocks()
        global.route = vi.fn((name: string, params?: Record<string, string | number>) => {
            const base = `/${name}`

            if (!params || Object.keys(params).length === 0) {
                return base
            }

            return `${base}?${new URLSearchParams(
                Object.entries(params).reduce<Record<string, string>>((carry, [key, value]) => {
                    carry[key] = String(value)

                    return carry
                }, {})
            ).toString()}`
        })
    })

    it('renders refreshed modal details and due date format', () => {
        render(
            <TaskDetailModal
                open={true}
                task={makeTask()}
                onClose={() => { }}
            />
        )

        expect(screen.getByText('Action Plan')).toBeInTheDocument()
        expect(screen.getByText('In Progress')).toBeInTheDocument()
        expect(screen.getByText('Review Q3 Financial Report Analysis')).toBeInTheDocument()
        expect(screen.getByText(/Februari 2026/)).toBeInTheDocument()
        expect(screen.getByText('Business & Administrative Services')).toBeInTheDocument()
        expect(screen.getByText('Pramuji Arif Yulianto')).toBeInTheDocument()
        expect(screen.getByRole('button', { name: /done/i })).toBeInTheDocument()
        expect(screen.getAllByRole('button', { name: /open in dashboard/i })[0]).toBeInTheDocument()
    })

    it('runs start action for planned task', () => {
        render(
            <TaskDetailModal
                open={true}
                task={makeTask('planned')}
                onClose={() => { }}
            />
        )

        fireEvent.click(screen.getByRole('button', { name: /start/i }))
        expect(vi.mocked(router.put)).toHaveBeenCalledWith(
            expect.stringContaining('/activity.task.update'),
            { status: 'in_progress' },
            expect.objectContaining({ preserveScroll: true })
        )
    })

    it('runs complete action and modal-first navigation', () => {
        const onClose = vi.fn()

        render(
            <TaskDetailModal
                open={true}
                task={makeTask('in_progress')}
                onClose={onClose}
            />
        )

        fireEvent.click(screen.getByRole('button', { name: /done/i }))
        expect(vi.mocked(router.put)).toHaveBeenCalledWith(
            expect.stringContaining('/activity.task.update'),
            { status: 'completed' },
            expect.objectContaining({ preserveScroll: true })
        )

        fireEvent.click(screen.getAllByRole('button', { name: /open in dashboard/i })[0])
        expect(vi.mocked(router.visit)).toHaveBeenCalledWith(
            expect.stringContaining('/activity.task.index?task=99&modal=detail'),
        )
        expect(onClose).toHaveBeenCalledTimes(1)

        fireEvent.click(screen.getByRole('button', { name: /edit task/i }))
        expect(vi.mocked(router.visit)).toHaveBeenCalledWith(
            expect.stringContaining('/activity.task.index?task=99&modal=edit'),
        )
    })
})
