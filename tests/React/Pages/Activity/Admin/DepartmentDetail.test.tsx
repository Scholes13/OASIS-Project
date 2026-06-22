import { act, fireEvent, render, screen } from '@testing-library/react'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { router } from '@inertiajs/react'
import DepartmentDetail from '@/Pages/Activity/Admin/DepartmentDetail'
import type { Task } from '@/types'

function makeTask(id = 99): Task {
    return {
        id,
        task_title: 'Deploy OASIS Update',
        task_description: null,
        status: 'in_progress',
        priority: 'medium',
        due_date: '2026-04-13',
        task_date: '2026-04-13',
        business_unit_id: 1,
        department_id: 1,
        activity_type_id: 1,
        created_by: 1,
        activity_type: {
            id: 1,
            code: 'WEB',
            name: 'Website',
            color: '#2563eb',
        },
        sub_activity: {
            id: 2,
            name: 'Public Deployment',
            activity_type_id: 1,
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
                employee_task_id: id,
                name: 'Pramuji Arif Yulianto',
            },
        ],
        department: {
            id: 1,
            name: 'Business & Administrative Services',
            code: 'BAS',
            business_unit_id: 1,
        },
        created_at: '2026-04-13T07:34:00Z',
        updated_at: '2026-04-13T07:34:00Z',
    }
}

function renderDepartmentDetail(props?: Partial<React.ComponentProps<typeof DepartmentDetail>>) {
    return render(
        <DepartmentDetail
            department={{
                id: 1,
                name: 'Business & Administrative Services',
                code: 'BAS',
                business_unit_id: 1,
            }}
            tasks={{
                data: [makeTask()],
                links: { first: '', last: '', prev: null, next: null },
                meta: {
                    current_page: 1,
                    from: 1,
                    last_page: 1,
                    links: [],
                    path: '/activity.admin.department',
                    per_page: 20,
                    to: 1,
                    total: 1,
                },
            }}
            stats={{ total: 1, completed: 0, in_progress: 1, planned: 0 }}
            userBreakdown={[]}
            activityTypeDistribution={[]}
            activityTypes={[]}
            filters={{
                date_from: '2026-04-01',
                date_to: '2026-04-30',
                status: '',
                activity_type_id: '',
                search: '',
                per_page: '10',
            }}
            selectedTask={null}
            selectedTaskModal={null}
            {...props}
        />
    )
}

describe('Activity Admin DepartmentDetail', () => {
    beforeEach(() => {
        vi.mocked(router.get).mockClear()
        window.history.replaceState({}, '', '/activity/admin/department/1')
    })

    it('renders the executive clean management workspace framing', async () => {
        await act(async () => {
            renderDepartmentDetail({
                userBreakdown: [
                    { created_by: 1, created_by_name: 'Yulia Mekar Rini', total: 12, completed: 10, in_progress: 1, planned: 1 },
                ],
                activityTypeDistribution: [
                    { name: 'Website', color: '#2563eb', count: 7 },
                ],
                stats: { total: 12, completed: 10, in_progress: 1, planned: 1 },
            })
        })

        expect(screen.getByRole('heading', { name: 'Business & Administrative Services' })).toBeInTheDocument()
        expect(screen.getByText('Completion rate')).toBeInTheDocument()
        expect(screen.getByRole('heading', { name: 'Contributor balance' })).toBeInTheDocument()
        expect(screen.getByRole('heading', { name: 'Activity mix' })).toBeInTheDocument()
        expect(screen.getByRole('heading', { name: 'Task register' })).toBeInTheDocument()
        expect(screen.getByRole('button', { name: 'Apply Filters' })).toBeInTheDocument()
    })

    it('applies the full filter set only when the control strip is submitted', async () => {
        await act(async () => {
            renderDepartmentDetail({
                activityTypes: [
                    { id: 1, code: 'WEB', name: 'Website', color: '#2563eb' },
                ],
            })
        })

        fireEvent.change(screen.getByLabelText('Status'), { target: { value: 'completed' } })
        fireEvent.change(screen.getByLabelText('Activity Type'), { target: { value: '1' } })
        fireEvent.change(screen.getByLabelText('Search'), { target: { value: 'Deploy' } })

        expect(router.get).not.toHaveBeenCalled()

        await act(async () => {
            fireEvent.click(screen.getByRole('button', { name: 'Apply Filters' }))
        })

        expect(router.get).toHaveBeenCalledWith(
            '/activity.admin.department?department=1',
            expect.objectContaining({
                date_from: '2026-04-01',
                date_to: '2026-04-30',
                status: 'completed',
                activity_type_id: '1',
                search: 'Deploy',
            }),
            { preserveState: true, preserveScroll: true },
        )
    })

    it('opens task detail in a modal when a task title is clicked', async () => {
        await import('@/components/activity/TaskDetailModal')

        await act(async () => {
            renderDepartmentDetail()
        })

        await act(async () => {
            fireEvent.click(screen.getByRole('button', { name: 'Open task details for Deploy OASIS Update' }))
        })

        expect(await screen.findByText('Activity Admin / Task / #99', {}, { timeout: 3000 })).toBeInTheDocument()
        expect(screen.queryByRole('button', { name: /open in dashboard/i })).not.toBeInTheDocument()
    })
})
