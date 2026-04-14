import { act, fireEvent, render, screen } from '@testing-library/react'
import { describe, expect, it, vi } from 'vitest'
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

describe('Activity Admin DepartmentDetail', () => {
    it('opens the shared task modal in admin mode and hides dashboard deep-link affordances', async () => {
        await act(async () => {
            render(
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
                    }}
                    selectedTask={makeTask()}
                    selectedTaskModal="detail"
                />
            )
        })

        expect(screen.getByRole('heading', { name: 'Deploy OASIS Update' })).toBeInTheDocument()
        expect(screen.getByText('Activity Admin / Task / #99')).toBeInTheDocument()
        expect(screen.queryByRole('button', { name: /open in dashboard/i })).not.toBeInTheDocument()
    })

    it('opens task detail in a modal when a task title is clicked', async () => {
        await act(async () => {
            render(
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
                    }}
                    selectedTask={null}
                    selectedTaskModal={null}
                />
            )
        })

        await act(async () => {
            fireEvent.click(screen.getByRole('button', { name: 'Deploy OASIS Update' }))
        })

        expect(screen.getByText('Activity Admin / Task / #99')).toBeInTheDocument()
    })
})
