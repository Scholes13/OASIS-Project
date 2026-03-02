import { beforeEach, describe, expect, it, vi } from 'vitest';
import { fireEvent, render, screen } from '@testing-library/react';
import { router } from '@inertiajs/react';
import { ActivityTimeline } from '@/components/activity/ActivityTimeline';

describe('ActivityTimeline create entry', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('uses modal callback when onCreateTask is provided', () => {
        const onCreateTask = vi.fn();

        render(<ActivityTimeline tasks={[]} onCreateTask={onCreateTask} />);

        fireEvent.click(screen.getByRole('button', { name: 'Create Activity' }));

        expect(onCreateTask).toHaveBeenCalledTimes(1);
        expect(vi.mocked(router.visit)).not.toHaveBeenCalled();
    });

    it('falls back to create route when callback is not provided', () => {
        render(<ActivityTimeline tasks={[]} />);

        fireEvent.click(screen.getByRole('button', { name: 'Create Activity' }));

        expect(vi.mocked(router.visit)).toHaveBeenCalledWith('/activity.task.create');
    });
});
