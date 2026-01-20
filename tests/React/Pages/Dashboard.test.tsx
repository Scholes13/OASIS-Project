import { describe, it, expect, vi } from 'vitest';
import { render, screen } from '@testing-library/react';
import Dashboard from '@/Pages/Dashboard';
import type { PageProps } from '@/types';

describe('Dashboard Page', () => {
  const mockProps: PageProps = {
    auth: {
      user: {
        id: 1,
        name: 'Test User',
        email: 'test@example.com',
        role: 'user',
        avatar_url: null,
        primary_department_id: 1,
      },
    },
    currentBusinessUnit: {
      id: 1,
      code: 'WNS',
      name: 'WNS Business Unit',
      logo: null,
    },
    availableBusinessUnits: [
      {
        id: 1,
        code: 'WNS',
        name: 'WNS Business Unit',
        logo: null,
      },
    ],
    navigation: {
      sections: [],
    },
    flash: {},
    appName: 'Oasis',
  };

  it('renders dashboard page', () => {
    render(<Dashboard {...mockProps} />);

    expect(screen.getByText('Dashboard')).toBeInTheDocument();
  });

  it('displays welcome message with user name', () => {
    render(<Dashboard {...mockProps} />);

    expect(screen.getByText(/Welcome back, Test User/i)).toBeInTheDocument();
  });

  it('displays current business unit name', () => {
    render(<Dashboard {...mockProps} />);

    expect(screen.getByText(/WNS Business Unit/i)).toBeInTheDocument();
  });

  it('renders stats cards', () => {
    render(<Dashboard {...mockProps} />);

    // Check for common dashboard stats
    expect(screen.getByText(/Total Purchase Requests/i)).toBeInTheDocument();
    expect(screen.getByText(/Pending Approvals/i)).toBeInTheDocument();
  });

  it('renders recent activities section', () => {
    render(<Dashboard {...mockProps} />);

    expect(screen.getByText(/Recent Activities/i)).toBeInTheDocument();
  });

  it('uses AppLayout wrapper', () => {
    const { container } = render(<Dashboard {...mockProps} />);

    // AppLayout should render sidebar and navbar
    expect(container.querySelector('[data-testid="app-layout"]')).toBeInTheDocument();
  });
});
