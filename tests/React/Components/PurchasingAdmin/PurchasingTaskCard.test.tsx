import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import { PurchasingTaskCard } from '@/components/purchasing-admin/PurchasingTaskCard';
import type { AdminTask } from '@/components/purchasing-admin/types';

const task: AdminTask = {
    id: 1,
    taskable_type: 'App\\Models\\Modules\\Purchasing\\PurchaseRequest\\PurchaseRequest',
    taskable_id: 90,
    business_unit_id: 1,
    department_id: 4,
    assigned_admin_id: null,
    status: 'pending_followup',
    estimated_total_price: 4400000,
    realized_total_price: null,
    savings_amount: null,
    savings_percentage: null,
    followup_time_minutes: null,
    completion_time_minutes: null,
    entered_at: '2026-07-15T19:44:00Z',
    started_at: null,
    completed_at: null,
    notes: null,
    department: {
        id: 4,
        name: 'Business & Administrative Services',
    },
    taskable: {
        id: 90,
        pr_number: 'PR.WNS/202607/090',
        department: {
            id: 7,
            name: 'General Affair',
        },
    },
};

describe('PurchasingTaskCard', () => {
    it('shows requesting department instead of purchasing queue department', () => {
        render(<PurchasingTaskCard task={task} showActions={false} />);

        expect(screen.getByText('General Affair')).toBeInTheDocument();
        expect(screen.queryByText('Business & Administrative Services')).not.toBeInTheDocument();
    });

    it('falls back to queue department when source relation is unavailable', () => {
        render(
            <PurchasingTaskCard
                task={{ ...task, taskable: { id: 90, pr_number: 'PR.WNS/202607/090' } }}
                showActions={false}
            />
        );

        expect(screen.getByText('Business & Administrative Services')).toBeInTheDocument();
    });
});
