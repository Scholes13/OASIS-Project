import { beforeEach, describe, expect, it, vi } from 'vitest';
import { fireEvent, render, screen } from '@testing-library/react';
import { router } from '@inertiajs/react';
import { ActivityCalendar } from '@/components/activity/ActivityCalendar';

vi.mock('@fullcalendar/react', () => ({
    default: () => <div data-testid="calendar-mock" />,
}));

vi.mock('@fullcalendar/daygrid', () => ({ default: {} }));
vi.mock('@fullcalendar/timegrid', () => ({ default: {} }));
vi.mock('@fullcalendar/interaction', () => ({ default: {} }));
vi.mock('@fullcalendar/list', () => ({ default: {} }));

describe('ActivityCalendar create entry', () => {
    beforeEach(() => {
        vi.clearAllMocks();
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

        expect(vi.mocked(router.visit)).toHaveBeenCalledWith('/activity.task.create');
    });
});
