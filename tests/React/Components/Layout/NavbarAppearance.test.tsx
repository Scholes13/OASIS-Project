import { describe, expect, it, vi } from 'vitest';
import { render } from '@testing-library/react';
import Navbar from '../../../../resources/js/inertia/components/layout/Navbar';

vi.mock('../../../../resources/js/inertia/components/layout/BusinessUnitSwitcher', () => ({
    default: () => <div data-testid="business-unit-switcher" />,
}));

vi.mock('../../../../resources/js/inertia/components/layout/DepartmentSwitcher', () => ({
    default: () => <div data-testid="department-switcher" />,
}));

vi.mock('../../../../resources/js/inertia/components/layout/UserMenu', () => ({
    default: () => <div data-testid="user-menu" />,
}));

describe('Navbar appearance', () => {
    it('uses white background for top navbar', () => {
        const { container } = render(<Navbar onMenuClick={vi.fn()} sidebarMinimized={false} />);

        const header = container.querySelector('header');

        expect(header).toBeInTheDocument();
        expect(header).toHaveClass('bg-white');
    });
});
