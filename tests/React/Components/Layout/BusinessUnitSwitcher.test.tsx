import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import BusinessUnitSwitcher from '@/components/layout/BusinessUnitSwitcher';
import type { BusinessUnit } from '@/types';
import { router } from '@inertiajs/react';

describe('BusinessUnitSwitcher Component', () => {
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
      logo: '/storage/business-units/mrp-logo.png',
    },
  ];

  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('renders current business unit', () => {
    render(
      <BusinessUnitSwitcher
        current={mockBusinessUnits[0]}
        available={mockBusinessUnits}
        onSwitch={vi.fn()}
      />
    );

    expect(screen.getByText('WNS Business Unit')).toBeInTheDocument();
  });

  it('opens dropdown when clicked', async () => {
    render(
      <BusinessUnitSwitcher
        current={mockBusinessUnits[0]}
        available={mockBusinessUnits}
        onSwitch={vi.fn()}
      />
    );

    const button = screen.getByRole('button');
    fireEvent.click(button);

    await waitFor(() => {
      expect(screen.getByText('UK Business Unit')).toBeInTheDocument();
      expect(screen.getByText('MRP Business Unit')).toBeInTheDocument();
    });
  });

  it('highlights current business unit in dropdown', async () => {
    render(
      <BusinessUnitSwitcher
        current={mockBusinessUnits[0]}
        available={mockBusinessUnits}
        onSwitch={vi.fn()}
      />
    );

    const button = screen.getByRole('button');
    fireEvent.click(button);

    await waitFor(() => {
      const currentOption = screen.getByText('WNS Business Unit').closest('button');
      expect(currentOption).toHaveClass('bg-indigo-50');
    });
  });

  it('calls onSwitch when different business unit is selected', async () => {
    const onSwitch = vi.fn();
    render(
      <BusinessUnitSwitcher
        current={mockBusinessUnits[0]}
        available={mockBusinessUnits}
        onSwitch={onSwitch}
      />
    );

    const button = screen.getByRole('button');
    fireEvent.click(button);

    await waitFor(() => {
      const ukOption = screen.getByText('UK Business Unit');
      fireEvent.click(ukOption);
    });

    expect(onSwitch).toHaveBeenCalledWith(2);
  });

  it('renders business unit logos when available', async () => {
    render(
      <BusinessUnitSwitcher
        current={mockBusinessUnits[0]}
        available={mockBusinessUnits}
        onSwitch={vi.fn()}
      />
    );

    const button = screen.getByRole('button');
    fireEvent.click(button);

    await waitFor(() => {
      const logo = screen.getByAltText('MRP Business Unit logo');
      expect(logo).toBeInTheDocument();
      expect(logo).toHaveAttribute('src', '/storage/business-units/mrp-logo.png');
    });
  });

  it('renders business unit codes', async () => {
    render(
      <BusinessUnitSwitcher
        current={mockBusinessUnits[0]}
        available={mockBusinessUnits}
        onSwitch={vi.fn()}
      />
    );

    const button = screen.getByRole('button');
    fireEvent.click(button);

    await waitFor(() => {
      expect(screen.getByText('WNS')).toBeInTheDocument();
      expect(screen.getByText('UK')).toBeInTheDocument();
      expect(screen.getByText('MRP')).toBeInTheDocument();
    });
  });

  it('does not render when only one business unit available', () => {
    const { container } = render(
      <BusinessUnitSwitcher
        current={mockBusinessUnits[0]}
        available={[mockBusinessUnits[0]]}
        onSwitch={vi.fn()}
      />
    );

    expect(container.firstChild).toBeNull();
  });

  it('closes dropdown when clicking outside', async () => {
    render(
      <div>
        <BusinessUnitSwitcher
          current={mockBusinessUnits[0]}
          available={mockBusinessUnits}
          onSwitch={vi.fn()}
        />
        <div data-testid="outside">Outside</div>
      </div>
    );

    const button = screen.getByRole('button');
    fireEvent.click(button);

    await waitFor(() => {
      expect(screen.getByText('UK Business Unit')).toBeInTheDocument();
    });

    const outside = screen.getByTestId('outside');
    fireEvent.click(outside);

    await waitFor(() => {
      expect(screen.queryByText('UK Business Unit')).not.toBeInTheDocument();
    });
  });
});
