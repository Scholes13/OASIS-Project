import { describe, expect, it, vi } from 'vitest';
import { render, screen } from '@testing-library/react';
import SlaSettings from '@/Pages/Ticket/SlaSettings';

vi.mock('@inertiajs/react', async () => {
    const actual = await vi.importActual<typeof import('@inertiajs/react')>('@inertiajs/react');

    return {
        ...actual,
        Head: () => null,
        usePage: () => ({ props: { flash: {} } }),
        useForm: () => ({
            put: vi.fn(),
            setData: vi.fn(),
            processing: false,
        }),
    };
});

describe('Ticket SLA Settings page', () => {
    it('shows the fixed 48-hour policy without editable controls', () => {
        render(<SlaSettings settings={[]} />);

        expect(screen.getByText('Current SLA Policy')).toBeInTheDocument();
        expect(screen.getByText(/Low, Medium, High, and Critical tickets share/)).toBeInTheDocument();

        expect(screen.queryByRole('spinbutton')).not.toBeInTheDocument();
        expect(screen.queryByRole('button', { name: /save settings/i })).not.toBeInTheDocument();
        expect(screen.getAllByText('48 hours')).toHaveLength(4);
    });
});
