import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { act, fireEvent, render, screen } from '@testing-library/react'
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
        if (!Element.prototype.getAnimations) {
            Object.defineProperty(Element.prototype, 'getAnimations', {
                configurable: true,
                value: vi.fn(() => []),
            })
        }
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

    it('renders refreshed modal details and due date format', async () => {
        await act(async () => {
            render(
                <TaskDetailModal
                    open={true}
                    task={makeTask()}
                    onClose={() => { }}
                />
            )
        })

        expect(screen.getByText('Action Plan')).toBeInTheDocument()
        expect(screen.getByText('In Progress')).toBeInTheDocument()
        expect(screen.getByText('Review Q3 Financial Report Analysis')).toBeInTheDocument()
        expect(screen.getByText(/Februari 2026/)).toBeInTheDocument()
        expect(screen.getByText('Business & Administrative Services')).toBeInTheDocument()
        expect(screen.getByText('Pramuji Arif Yulianto')).toBeInTheDocument()
        expect(screen.getByRole('button', { name: /done/i })).toBeInTheDocument()
        expect(screen.getAllByRole('button', { name: /open in dashboard/i })[0]).toBeInTheDocument()
    })

    it('runs start action for planned task', async () => {
        await act(async () => {
            render(
                <TaskDetailModal
                    open={true}
                    task={makeTask('planned')}
                    onClose={() => { }}
                />
            )
        })

        await act(async () => {
            fireEvent.click(screen.getByRole('button', { name: /start/i }))
        })
        expect(vi.mocked(router.put)).toHaveBeenCalledWith(
            expect.stringContaining('/activity.task.update'),
            { status: 'in_progress' },
            expect.objectContaining({ preserveScroll: true })
        )
    })

    it('runs complete action and modal-first navigation', async () => {
        const onClose = vi.fn()

        await act(async () => {
            render(
                <TaskDetailModal
                    open={true}
                    task={makeTask('in_progress')}
                    onClose={onClose}
                />
            )
        })

        await act(async () => {
            fireEvent.click(screen.getByRole('button', { name: /done/i }))
        })
        expect(vi.mocked(router.put)).toHaveBeenCalledWith(
            expect.stringContaining('/activity.task.update'),
            { status: 'completed' },
            expect.objectContaining({ preserveScroll: true })
        )

        await act(async () => {
            fireEvent.click(screen.getAllByRole('button', { name: /open in dashboard/i })[0])
        })
        expect(vi.mocked(router.visit)).toHaveBeenCalledWith(
            expect.stringContaining('/activity.task.index?task=99&modal=detail'),
        )
        expect(onClose).toHaveBeenCalledTimes(1)

        await act(async () => {
            fireEvent.click(screen.getByRole('button', { name: /edit task/i }))
        })
        expect(vi.mocked(router.visit)).toHaveBeenCalledWith(
            expect.stringContaining('/activity.task.index?task=99&modal=edit'),
        )
    })

    it('opens edit modal when quick action is blocked for historical task', async () => {
        const onEdit = vi.fn()
        const task = makeTask('planned')
        task.task_date = '2026-04-10'

        await act(async () => {
            render(
                <TaskDetailModal
                    open={true}
                    task={task}
                    onClose={() => {}}
                    onEdit={onEdit}
                />
            )
        })

        await act(async () => {
            fireEvent.click(screen.getByRole('button', { name: /start/i }))
        })

        vi.mocked(router.put).mock.calls[0][2]?.onError?.({
            status: ['Task uses a historical date. Please confirm actual execution time.']
        })

        expect(onEdit).toHaveBeenCalledWith(task)
    })

    it('keeps detail modal panes shrinkable so short viewports can scroll internally', async () => {
        await act(async () => {
            render(
                <TaskDetailModal
                    open={true}
                    task={makeTask()}
                    onClose={() => {}}
                />
            )
        })

        const panel = document.querySelector('[id^="headlessui-dialog-panel"]') as HTMLElement | null
        const shell = document.querySelector('.flex.h-full.flex-col.bg-background') as HTMLElement | null
        const contentSplit = document.querySelector('.flex.flex-1.overflow-hidden') as HTMLElement | null
        const mainPane = document.querySelector('.flex-1.overflow-y-auto.border-r.border-border.p-8') as HTMLElement | null
        const sidePane = [...document.querySelectorAll('div')].find((element) =>
            element.className.includes('w-[320px]') && element.className.includes('overflow-y-auto')
        ) as HTMLElement | undefined

        expect(panel).not.toBeNull()
        expect(shell).not.toBeNull()
        expect(contentSplit).not.toBeNull()
        expect(mainPane).not.toBeNull()
        expect(sidePane).toBeDefined()

        expect(panel!.className).toContain('min-h-0')
        expect(shell!.className).toContain('min-h-0')
        expect(contentSplit!.className).toContain('min-h-0')
        expect(mainPane!.className).toContain('min-h-0')
        expect(sidePane!.className).toContain('min-h-0')
    })
})



