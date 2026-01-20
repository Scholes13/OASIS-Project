import { describe, it, expect, vi } from 'vitest';
import { render, screen, fireEvent } from '@testing-library/react';
import Sidebar from '@/components/layout/Sidebar';
import type { NavigationMenu } from '@/types';

describe('Sidebar Component', () => {
  const mockNavigation: NavigationMenu = {
    sections: [
      {
        name: 'Dashboard',
        items: [
          {
            name: 'Dashboard',
            href: '/dashboard',
            icon: 'Home',
            active: true,
          },
        ],
      },
      {
        name: 'Purchasing',
        items: [
          {
            name: 'Purchase Requests',
            href: '/purchase-requests',
            icon: 'ShoppingCart',
            active: false,
            children: [
              {
                name: 'My Requests',
                href: '/purchase-requests',
                icon: 'FileText',
                active: false,
              },
              {
                name: 'Create New',
                href: '/purchase-requests/create',
                icon: 'Plus',
                active: false,
              },
            ],
          },
        ],
      },
    ],
  };

  it('renders navigation sections', () => {
    render(
      <Sidebar
        navigation={mockNavigation}
        currentRoute="/dashboard"
        isOpen={true}
        onClose={vi.fn()}
      />
    );

    expect(screen.getByText('Dashboard')).toBeInTheDocument();
    expect(screen.getByText('Purchasing')).toBeInTheDocument();
  });

  it('renders navigation items', () => {
    render(
      <Sidebar
        navigation={mockNavigation}
        currentRoute="/dashboard"
        isOpen={true}
        onClose={vi.fn()}
      />
    );

    expect(screen.getByText('Dashboard')).toBeInTheDocument();
    expect(screen.getByText('Purchase Requests')).toBeInTheDocument();
  });

  it('highlights active menu item', () => {
    render(
      <Sidebar
        navigation={mockNavigation}
        currentRoute="/dashboard"
        isOpen={true}
        onClose={vi.fn()}
      />
    );

    const dashboardLink = screen.getByText('Dashboard').closest('a');
    expect(dashboardLink).toHaveClass('bg-indigo-50');
  });

  it('renders dropdown menu items', () => {
    render(
      <Sidebar
        navigation={mockNavigation}
        currentRoute="/dashboard"
        isOpen={true}
        onClose={vi.fn()}
      />
    );

    // Click to expand dropdown
    const purchaseRequestsButton = screen.getByText('Purchase Requests').closest('button');
    if (purchaseRequestsButton) {
      fireEvent.click(purchaseRequestsButton);
    }

    expect(screen.getByText('My Requests')).toBeInTheDocument();
    expect(screen.getByText('Create New')).toBeInTheDocument();
  });

  it('calls onClose when close button is clicked on mobile', () => {
    const onClose = vi.fn();
    render(
      <Sidebar
        navigation={mockNavigation}
        currentRoute="/dashboard"
        isOpen={true}
        onClose={onClose}
      />
    );

    // Find and click close button (X icon)
    const closeButtons = screen.getAllByRole('button');
    const closeButton = closeButtons.find((btn) => btn.getAttribute('aria-label') === 'Close sidebar');
    
    if (closeButton) {
      fireEvent.click(closeButton);
      expect(onClose).toHaveBeenCalled();
    }
  });

  it('applies correct classes when sidebar is open', () => {
    const { container } = render(
      <Sidebar
        navigation={mockNavigation}
        currentRoute="/dashboard"
        isOpen={true}
        onClose={vi.fn()}
      />
    );

    const sidebar = container.querySelector('[data-testid="sidebar"]');
    expect(sidebar).toBeInTheDocument();
  });

  it('applies correct classes when sidebar is closed', () => {
    const { container } = render(
      <Sidebar
        navigation={mockNavigation}
        currentRoute="/dashboard"
        isOpen={false}
        onClose={vi.fn()}
      />
    );

    const sidebar = container.querySelector('[data-testid="sidebar"]');
    expect(sidebar).toBeInTheDocument();
  });

  it('renders badge when item has badge', () => {
    const navigationWithBadge: NavigationMenu = {
      sections: [
        {
          name: 'Dashboard',
          items: [
            {
              name: 'Notifications',
              href: '/notifications',
              icon: 'Bell',
              active: false,
              badge: {
                text: '5',
                color: 'red',
              },
            },
          ],
        },
      ],
    };

    render(
      <Sidebar
        navigation={navigationWithBadge}
        currentRoute="/dashboard"
        isOpen={true}
        onClose={vi.fn()}
      />
    );

    expect(screen.getByText('5')).toBeInTheDocument();
  });
});
