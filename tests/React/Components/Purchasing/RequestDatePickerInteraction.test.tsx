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
