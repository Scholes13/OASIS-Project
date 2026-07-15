import { fireEvent, render } from '@testing-library/react';
import { afterEach, describe, expect, it, vi } from 'vitest';
import { PurchaseRequestForm } from '@/components/purchasing/PurchaseRequestForm';
import { StockRequestForm } from '@/components/purchasing/StockRequestForm';

describe('Purchasing request date picker interaction', () => {
    afterEach(() => {
        delete (HTMLInputElement.prototype as Partial<HTMLInputElement>).showPicker;
    });

    it('does not throw when the stock request expected date input is clicked without showPicker permission', () => {
        Object.defineProperty(HTMLInputElement.prototype, 'showPicker', {
            configurable: true,
            value: vi.fn(() => {
                throw new DOMException(
                    "Failed to execute 'showPicker' on 'HTMLInputElement': HTMLInputElement::showPicker() requires a user gesture.",
                    'NotAllowedError'
                );
            }),
        });

        const { container } = render(
            <StockRequestForm
                departments={[]}
                businessUnits={[]}
                availableApprovers={[]}
                onSubmit={vi.fn()}
            />
        );

        const dateInput = container.querySelector('input[type="date"]');

        expect(dateInput).not.toBeNull();
        expect(() => fireEvent.click(dateInput as HTMLInputElement)).not.toThrow();
    });

    it('shows stock request server validation errors and submission progress', () => {
        const { getByText, getByRole } = render(
            <StockRequestForm
                departments={[]}
                businessUnits={[]}
                availableApprovers={[]}
                errors={{
                    expected_date: 'Expected date must be on or after the request date.',
                }}
                processing={true}
                onSubmit={vi.fn()}
            />
        );

        expect(getByText('Expected date must be on or after the request date.')).toBeInTheDocument();
        expect(getByRole('button', { name: /submitting/i })).toBeDisabled();
    });

    it('explains direct Purchasing Admin routing for GA requests', () => {
        const { getByText } = render(
            <StockRequestForm
                departments={[]}
                businessUnits={[]}
                availableApprovers={[]}
                routesDirectlyToPurchasing={true}
                onSubmit={vi.fn()}
            />
        );

        expect(getByText(
            'This request will skip department approval and Stock Review, then go directly to Purchasing Admin.'
        )).toBeInTheDocument();
    });

    it('does not throw when the purchase request expected date input is clicked without showPicker permission', () => {
        Object.defineProperty(HTMLInputElement.prototype, 'showPicker', {
            configurable: true,
            value: vi.fn(() => {
                throw new DOMException(
                    "Failed to execute 'showPicker' on 'HTMLInputElement': HTMLInputElement::showPicker() requires a user gesture.",
                    'NotAllowedError'
                );
            }),
        });

        const { container } = render(
            <PurchaseRequestForm
                categories={[]}
                departments={[]}
                businessUnits={[]}
                availableApprovers={[]}
                onSubmit={vi.fn()}
            />
        );

        const dateInput = container.querySelector('input[type="date"]');

        expect(dateInput).not.toBeNull();
        expect(() => fireEvent.click(dateInput as HTMLInputElement)).not.toThrow();
    });
});
