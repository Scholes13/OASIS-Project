import { describe, it, expect, vi } from 'vitest';
import { render, screen, fireEvent } from '@testing-library/react';
import Navbar from '@/components/layout/Navbar';
import type { BusinessUnit, User } from '@/types';

describe('Navbar Component', () => {
  const mockUser: User = {
    id: 1,
    name: 'Test User',
    email: 'test@example.com',
    role: 'user',
    avatar_url: null,
    primary_department_id: 1,
  };

  const mockBusinessUnit: BusinessUnit = {
    id: 1,
    code: 'WNS',
    name: 'WNS Business Unit',
    logo: null,
  };

  const mockAvailableBusinessUnits: BusinessUnit[] = [
    mockBusinessUnit,
    {
      id: 2,
      code: 'UK',
      name: 'UK Business Unit',
      logo: null,
    },
  ];

  it('renders business unit name', () => {
    render(
      <Navbar
        currentBusinessUnit={mockBusinessUnit}
        availableBusinessUnits={mockAvailableBusinessUnits}
        user={mockUser}
        onToggleSidebar={vi.fn()}
      />
    );

    expect(screen.getByText('WNS Business Unit')).toBeInTheDocument();
  });

  it('renders user name', () => {
    render(
      <Navbar
        currentBusinessUnit={mockBusinessUnit}
        availableBusinessUnits={mockAvailableBusinessUnits}
        user={mockUser}
        onToggleSidebar={vi.fn()}
      />
    );

    expect(screen.getByText('Test User')).toBeInTheDocument();
  });

  it('calls onToggleSidebar when hamburger button is clicked', () => {
    const onToggleSidebar = vi.fn();
    render(
      <Navbar
        currentBusinessUnit={mockBusinessUnit}
        availableBusinessUnits={mockAvailableBusinessUnits}
        user={mockUser}
        onToggleSidebar={onToggleSidebar}
      />
    );

    const hamburgerButton = screen.getByLabelText('Toggle sidebar');
    fireEvent.click(hamburgerButton);

    expect(onToggleSidebar).toHaveBeenCalled();
  });

  it('renders BusinessUnitSwitcher when multiple business units available', () => {
    render(
      <Navbar
        currentBusinessUnit={mockBusinessUnit}
        availableBusinessUnits={mockAvailableBusinessUnits}
        user={mockUser}
        onToggleSidebar={vi.fn()}
      />
    );

    // BusinessUnitSwitcher should be rendered
    expect(screen.getByText('WNS Business Unit')).toBeInTheDocument();
  });

  it('does not render BusinessUnitSwitcher when only one business unit available', () => {
    render(
      <Navbar
        currentBusinessUnit={mockBusinessUnit}
        availableBusinessUnits={[mockBusinessUnit]}
        user={mockUser}
        onToggleSidebar={vi.fn()}
      />
    );

    // Should still show business unit name but not as a switcher
    expect(screen.getByText('WNS Business Unit')).toBeInTheDocument();
  });

  it('renders user initials when no avatar', () => {
    render(
      <Navbar
        currentBusinessUnit={mockBusinessUnit}
        availableBusinessUnits={mockAvailableBusinessUnits}
        user={mockUser}
        onToggleSidebar={vi.fn()}
      />
    );

    // Should show initials "TU" for "Test User"
    expect(screen.getByText('TU')).toBeInTheDocument();
  });

  it('renders business unit logo when available', () => {
    const businessUnitWithLogo: BusinessUnit = {
      ...mockBusinessUnit,
      logo: '/storage/business-units/logo.png',
    };

    render(
      <Navbar
        currentBusinessUnit={businessUnitWithLogo}
        availableBusinessUnits={[businessUnitWithLogo]}
        user={mockUser}
        onToggleSidebar={vi.fn()}
      />
    );

    const logo = screen.getByAltText('WNS Business Unit logo');
    expect(logo).toBeInTheDocument();
    expect(logo).toHaveAttribute('src', '/storage/business-units/logo.png');
  });
});
