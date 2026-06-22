import * as React from 'react';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { fireEvent, render, screen, within } from '@testing-library/react';
import { router } from '@inertiajs/react';
import { ActivityCalendar } from '@/components/activity/ActivityCalendar';
import type { Task } from '@/types';

interface FullCalendarMockApi {
    prev: () => void;
    next: () => void;
    today: () => void;
    getDate: () => Date;
    changeView: () => void;
}

interface FullCalendarMockHandle {
    getApi: () => FullCalendarMockApi;
}

interface FullCalendarMockEvent {
    title: string;
    extendedProps: {
        task: Task;
    };
}

interface FullCalendarMockProps {
    events?: FullCalendarMockEvent[];
    eventContent?: (arg: { event: FullCalendarMockEvent }) => React.ReactNode;
}

vi.mock('@fullcalendar/react', () => ({
    default: React.forwardRef<FullCalendarMockHandle, FullCalendarMockProps>((props, ref) => {
        React.useImperativeHandle(ref, () => ({
            getApi: () => ({
                prev: vi.fn(),
                next: vi.fn(),
                today: vi.fn(),
                getDate: () => new Date('2026-03-30T00:00:00.000Z'),
                changeView: vi.fn(),
            }),
        }));

        const firstEvent = props.events?.[0];
        const eventContent = firstEvent ? props.eventContent({ event: firstEvent }) : null;

        return <div data-testid="calendar-mock">{eventContent}</div>;
    }),
}));

vi.mock('@fullcalendar/daygrid', () => ({ default: {} }));
vi.mock('@fullcalendar/timegrid', () => ({ default: {} }));
vi.mock('@fullcalendar/interaction', () => ({ default: {} }));
vi.mock('@fullcalendar/list', () => ({ default: {} }));

function makeTask(overrides: Partial<Task> = {}): Task {
    return {
        id: 1,
        task_title: 'Review month view',
        task_description: null,
        status: 'planned',
        priority: 'medium',
        due_date: null,
        task_date: '2026-03-30',
        business_unit_id: 1,
        department_id: 1,
        activity_type_id: 1,
        created_by: 1,
        activity_type: {
            id: 1,
            name: 'General',
            code: 'general',
            color: 'blue',
        },
        creator: {
            id: 1,
            name: 'Owner One',
            email: 'owner@example.com',
            role: 'user',
            avatar_url: 'https://example.com/owner.png',
        },
        participants: [
            {
                id: 11,
                user_id: 1,
                employee_task_id: 1,
                name: 'Owner One',
                email: 'owner@example.com',
                user: {
                    id: 1,
                    name: 'Owner One',
                    email: 'owner@example.com',
                    role: 'user',
                    avatar_url: 'https://example.com/owner.png',
                },
            },
            {
                id: 12,
                user_id: 2,
                employee_task_id: 1,
                name: 'Teammate Two',
                email: 'teammate@example.com',
                user: {
                    id: 2,
                    name: 'Teammate Two',
                    email: 'teammate@example.com',
                    role: 'user',
                },
            },
        ],
        department: {
            id: 1,
            name: 'Sales',
            code: 'SAL',
        },
        created_at: '2026-03-30T00:00:00.000Z',
        updated_at: '2026-03-30T00:00:00.000Z',
        ...overrides,
    };
}

function renderMonthEvent(task: Task) {
    render(<ActivityCalendar tasks={[task]} />);
    return screen.getByTestId('calendar-mock');
}

describe('ActivityCalendar create entry', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('shows a flat participant owner with avatar in month view', () => {
        const task = makeTask({
            creator: {
                id: 1,
                name: 'Creator Zero',
                email: 'creator@example.com',
                role: 'user',
            },
            participants: [
                {
                    id: 11,
                    user_id: 1,
                    employee_task_id: 1,
                    name: 'Owner One',
                    email: 'owner@example.com',
                    avatar_url: 'https://example.com/owner.png',
                } as unknown as Task['participants'][number],
                {
                    id: 12,
                    user_id: 2,
                    employee_task_id: 1,
                    name: 'Teammate Two',
                    email: 'teammate@example.com',
                } as unknown as Task['participants'][number],
            ],
        });

        const event = renderMonthEvent(task);

        expect(within(event).getByText('Review month view')).toBeInTheDocument();
        expect(within(event).getByRole('img', { name: /owner one/i })).toBeInTheDocument();
        expect(within(event).getByText('+1')).toBeInTheDocument();
    });

    it('falls back to initials when the owner avatar is missing', () => {
        const task = makeTask({
            creator: {
                id: 1,
                name: 'Creator Zero',
                email: 'creator@example.com',
                role: 'user',
            },
            participants: [
                {
                    id: 11,
                    user_id: 1,
                    employee_task_id: 1,
                    name: 'Flat Owner',
                    email: 'flat@example.com',
                } as unknown as Task['participants'][number],
            ],
        });

        const event = renderMonthEvent(task);

        expect(within(event).getByLabelText('Flat Owner')).toHaveTextContent('F');
    });

    it('keeps the owner marker at the far right for overdue month entries', () => {
        const task = makeTask({
            creator: {
                id: 1,
                name: 'Creator Zero',
                email: 'creator@example.com',
                role: 'user',
            },
            due_date: '2026-03-01',
            participants: [
                {
                    id: 11,
                    user_id: 1,
                    employee_task_id: 1,
                    name: 'Owner One',
                    email: 'owner@example.com',
                    avatar_url: 'https://example.com/owner.png',
                } as unknown as Task['participants'][number],
                {
                    id: 12,
                    user_id: 2,
                    employee_task_id: 1,
                    name: 'Teammate Two',
                    email: 'teammate@example.com',
                } as unknown as Task['participants'][number],
            ],
        });

        const event = renderMonthEvent(task);
        const content = within(event).getByText('Review month view').closest('div');

        expect(content).toBeTruthy();
        expect(content?.textContent).toContain('!');
        expect(content?.lastElementChild).toHaveTextContent('+1');
    });

    it('shows richer owner information in week view than month view', async () => {
        const task = makeTask({
            creator: {
                id: 1,
                name: 'Creator Zero',
                email: 'creator@example.com',
                role: 'user',
            },
            participants: [
                {
                    id: 11,
                    user_id: 1,
                    employee_task_id: 1,
                    name: 'Owner One',
                    email: 'owner@example.com',
                    avatar_url: 'https://example.com/owner.png',
                    user: {
                        id: 1,
                        name: 'Owner One',
                        email: 'owner@example.com',
                        role: 'user',
                        avatar_url: 'https://example.com/owner.png',
                    },
                } as unknown as Task['participants'][number],
            ],
        });

        render(<ActivityCalendar tasks={[task]} />);

        expect(screen.getByText('Review month view')).toBeInTheDocument();
        expect(screen.getByRole('img', { name: /owner one/i })).toBeInTheDocument();

        fireEvent.click(screen.getByRole('button', { name: /week/i }));

        const weekView = screen.getByTestId('calendar-mock');
        expect(within(weekView).getByText('Owner One')).toBeInTheDocument();
        expect(within(weekView).getByText('General')).toBeInTheDocument();
        expect(within(weekView).getByText('1')).toBeInTheDocument();
    });

    it('uses modal callback for Add button when onCreateTask is provided', () => {
        const onCreateTask = vi.fn();

        render(<ActivityCalendar tasks={[]} onCreateTask={onCreateTask} />);

        fireEvent.click(screen.getByRole('button', { name: /add/i }));

        expect(onCreateTask).toHaveBeenCalledTimes(1);
        expect(vi.mocked(router.visit)).not.toHaveBeenCalled();
    });

    it('falls back to create route for Add button when callback is not provided', () => {
        render(<ActivityCalendar tasks={[]} />);

        fireEvent.click(screen.getByRole('button', { name: /add/i }));

        expect(vi.mocked(router.visit)).toHaveBeenCalledWith('/activity.task.index?modal=create');
    });
});
