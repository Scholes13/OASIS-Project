import { describe, expect, it } from 'vitest';
import { fireEvent, render, screen } from '@testing-library/react';
import { TaskFormModal } from '@/components/activity/TaskFormModal';

describe('TaskFormModal due date requirement', () => {
    it('shows required indicator for Due Date when status is not completed', () => {
        render(
            <TaskFormModal
                open={true}
                onClose={() => {}}
                task={null}
                activityTypes={[]}
                departmentUsers={[]}
                allowedDateRange={{ from: '2026-01-01', to: '2026-12-31' }}
            />
        );

        const dueDateLabel = screen.getByText('Due Date', { selector: 'label' });
        expect(dueDateLabel.querySelector('span')?.textContent?.trim()).toBe('*');

        const dueDateInput = screen.getByLabelText(/Due Date/i) as HTMLInputElement;
        expect(dueDateInput.required).toBe(true);
    });

    it('hides required indicator for Due Date when status is completed', () => {
        render(
            <TaskFormModal
                open={true}
                onClose={() => {}}
                task={null}
                activityTypes={[]}
                departmentUsers={[]}
                allowedDateRange={{ from: '2026-01-01', to: '2026-12-31' }}
            />
        );

        fireEvent.change(screen.getByDisplayValue('To Do'), { target: { value: 'completed' } });

        const dueDateLabel = screen.getByText('Due Date', { selector: 'label' });
        expect(dueDateLabel.querySelector('span')).toBeNull();

        const dueDateInput = screen.getByLabelText(/Due Date/i) as HTMLInputElement;
        expect(dueDateInput.required).toBe(false);
    });
});
