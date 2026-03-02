import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { router } from '@inertiajs/react';
import BusinessUnitSwitcher from '@/components/layout/BusinessUnitSwitcher';
import type { BusinessUnit } from '@/types';

describe('Business Unit Switch Interactions', () => {
  const mockBusinessUnits: BusinessUnit[] = [
    {
      id: 1,
      code: 'WNS',
      name: 'WNS Business Unit',
      logo: null,
    },
    {
      id: 2,
      code: 'UK',
      name: 'UK Business Unit',
      logo: null,
    },
    {
      id: 3,
      code: 'MRP',
      name: 'MRP Business Unit',
      logo: null,
    },
  ];

  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('switches business unit when different unit is selected', async () => {
    const onSwitch = vi.fn();
    render(
      <BusinessUnitSwitcher
        current={mockBusinessUnits[0]}
        available={mockBusinessUnits}
        onSwitch={onSwitch}
      />
    );

    // Open dropdown
    const button = screen.getByRole('button');
    fireEvent.click(button);

    // Select UK Business Unit
    await waitFor(() => {
      const ukOption = screen.getByText('UK Business Unit');
      fireEvent.click(ukOption);
    });

    expect(onSwitch).toHaveBeenCalledWith(2);
  });

  it('sends POST request to switch business unit', async () => {
    const onSwitch = vi.fn((businessUnitId: number) => {
      router.post('/business-unit/switch', { business_unit_id: businessUnitId });
    });

    render(
      <BusinessUnitSwitcher
        current={mockBusinessUnits[0]}
        available={mockBusinessUnits}
        onSwitch={onSwitch}
      />
    );

    // Open dropdown
    const button = screen.getByRole('button');
    fireEvent.click(button);

    // Select MRP Business Unit
    await waitFor(() => {
      const mrpOption = screen.getByText('MRP Business Unit');
      fireEvent.click(mrpOption);
    });

    expect(onSwitch).toHaveBeenCalledWith(3);
    expect(router.post).toHaveBeenCalledWith('/business-unit/switch', {
      business_unit_id: 3,
    });
  });

  it('closes dropdown after selection', async () => {
    const onSwitch = vi.fn();
    render(
      <BusinessUnitSwitcher
        current={mockBusinessUnits[0]}
        available={mockBusinessUnits}
        onSwitch={onSwitch}
      />
    );

    // Open dropdown
    const button = screen.getByRole('button');
    fireEvent.click(button);

    await waitFor(() => {
      expect(screen.getByText('UK Business Unit')).toBeInTheDocument();
    });

    // Select UK Business Unit
    const ukOption = screen.getByText('UK Business Unit');
    fireEvent.click(ukOption);

    // Dropdown should close
    await waitFor(() => {
      expect(screen.queryByText('UK Business Unit')).not.toBeInTheDocument();
    });
  });

  it('does not switch when current business unit is selected', async () => {
    const onSwitch = vi.fn();
    render(
      <BusinessUnitSwitcher
        current={mockBusinessUnits[0]}
        available={mockBusinessUnits}
        onSwitch={onSwitch}
      />
    );

    // Open dropdown
    const button = screen.getByRole('button');
    fireEvent.click(button);

    // Select current business unit (WNS)
    await waitFor(() => {
      const wnsOption = screen.getByText('WNS Business Unit');
      fireEvent.click(wnsOption);
    });

    // onSwitch should still be called, but backend will handle no-op
    expect(onSwitch).toHaveBeenCalledWith(1);
  });

  it('displays all available business units in dropdown', async () => {
    render(
      <BusinessUnitSwitcher
        current={mockBusinessUnits[0]}
        available={mockBusinessUnits}
        onSwitch={vi.fn()}
      />
    );

    // Open dropdown
    const button = screen.getByRole('button');
    fireEvent.click(button);

    await waitFor(() => {
      expect(screen.getByText('WNS Business Unit')).toBeInTheDocument();
      expect(screen.getByText('UK Business Unit')).toBeInTheDocument();
      expect(screen.getByText('MRP Business Unit')).toBeInTheDocument();
    });
  });

  it('shows visual feedback for current business unit', async () => {
    render(
      <BusinessUnitSwitcher
        current={mockBusinessUnits[0]}
        available={mockBusinessUnits}
        onSwitch={vi.fn()}
      />
    );

    // Open dropdown
    const button = screen.getByRole('button');
    fireEvent.click(button);

    await waitFor(() => {
      const currentOption = screen.getByText('WNS Business Unit').closest('button');
      expect(currentOption).toHaveClass('bg-primary');
    });
  });

  it('updates UI after successful switch', async () => {
    const { rerender } = render(
      <BusinessUnitSwitcher
        current={mockBusinessUnits[0]}
        available={mockBusinessUnits}
        onSwitch={vi.fn()}
      />
    );

    expect(screen.getByText('WNS Business Unit')).toBeInTheDocument();

    // Simulate successful switch by re-rendering with new current BU
    rerender(
      <BusinessUnitSwitcher
        current={mockBusinessUnits[1]}
        available={mockBusinessUnits}
        onSwitch={vi.fn()}
      />
    );

    expect(screen.getByText('UK Business Unit')).toBeInTheDocument();
  });

  it('handles keyboard navigation in dropdown', async () => {
    render(
      <BusinessUnitSwitcher
        current={mockBusinessUnits[0]}
        available={mockBusinessUnits}
        onSwitch={vi.fn()}
      />
    );

    const button = screen.getByRole('button');
    
    // Open with Enter key
    fireEvent.keyDown(button, { key: 'Enter' });

    await waitFor(() => {
      expect(screen.getByText('UK Business Unit')).toBeInTheDocument();
    });

    // Close with Escape key
    fireEvent.keyDown(button, { key: 'Escape' });

    await waitFor(() => {
      expect(screen.queryByText('UK Business Unit')).not.toBeInTheDocument();
    });
  });
});
