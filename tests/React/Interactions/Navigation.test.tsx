import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { router } from '@inertiajs/react';
import Sidebar from '@/components/layout/Sidebar';
import type { NavigationMenu } from '@/types';

describe('Navigation Interactions', () => {
  const mockNavigation: NavigationMenu = {
    sections: [
      {
        name: 'Dashboard',
        items: [
          {
            name: 'Dashboard',
            href: '/dashboard',
            icon: 'Home',
            active: false,
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

  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('navigates to dashboard when dashboard link is clicked', () => {
    render(
      <Sidebar
        navigation={mockNavigation}
        currentRoute="/purchase-requests"
        isOpen={true}
        onClose={vi.fn()}
      />
    );

    const dashboardLink = screen.getByText('Dashboard');
    fireEvent.click(dashboardLink);

    expect(router.visit).toHaveBeenCalledWith('/dashboard');
  });

  it('expands dropdown menu when parent item is clicked', async () => {
    render(
      <Sidebar
        navigation={mockNavigation}
        currentRoute="/dashboard"
        isOpen={true}
        onClose={vi.fn()}
      />
    );

    const purchaseRequestsButton = screen.getByText('Purchase Requests').closest('button');
    
    // Initially, children should not be visible
    expect(screen.queryByText('My Requests')).not.toBeInTheDocument();

    // Click to expand
    if (purchaseRequestsButton) {
      fireEvent.click(purchaseRequestsButton);
    }

    await waitFor(() => {
      expect(screen.getByText('My Requests')).toBeInTheDocument();
      expect(screen.getByText('Create New')).toBeInTheDocument();
    });
  });

  it('collapses dropdown menu when parent item is clicked again', async () => {
    render(
      <Sidebar
        navigation={mockNavigation}
        currentRoute="/dashboard"
        isOpen={true}
        onClose={vi.fn()}
      />
    );

    const purchaseRequestsButton = screen.getByText('Purchase Requests').closest('button');

    // Expand
    if (purchaseRequestsButton) {
      fireEvent.click(purchaseRequestsButton);
    }

    await waitFor(() => {
      expect(screen.getByText('My Requests')).toBeInTheDocument();
    });

    // Collapse
    if (purchaseRequestsButton) {
      fireEvent.click(purchaseRequestsButton);
    }

    await waitFor(() => {
      expect(screen.queryByText('My Requests')).not.toBeInTheDocument();
    });
  });

  it('navigates to child route when child link is clicked', async () => {
    render(
      <Sidebar
        navigation={mockNavigation}
        currentRoute="/dashboard"
        isOpen={true}
        onClose={vi.fn()}
      />
    );

    const purchaseRequestsButton = screen.getByText('Purchase Requests').closest('button');
    
    // Expand dropdown
    if (purchaseRequestsButton) {
      fireEvent.click(purchaseRequestsButton);
    }

    await waitFor(() => {
      const createNewLink = screen.getByText('Create New');
      fireEvent.click(createNewLink);
    });

    expect(router.visit).toHaveBeenCalledWith('/purchase-requests/create');
  });

  it('highlights active route in navigation', () => {
    render(
      <Sidebar
        navigation={{
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
          ],
        }}
        currentRoute="/dashboard"
        isOpen={true}
        onClose={vi.fn()}
      />
    );

    const dashboardLink = screen.getByText('Dashboard').closest('a');
    expect(dashboardLink).toHaveClass('bg-indigo-50');
  });

  it('closes mobile sidebar after navigation', () => {
    const onClose = vi.fn();
    render(
      <Sidebar
        navigation={mockNavigation}
        currentRoute="/purchase-requests"
        isOpen={true}
        onClose={onClose}
      />
    );

    const dashboardLink = screen.getByText('Dashboard');
    fireEvent.click(dashboardLink);

    // On mobile, sidebar should close after navigation
    expect(onClose).toHaveBeenCalled();
  });

  it('maintains dropdown state during navigation', async () => {
    const { rerender } = render(
      <Sidebar
        navigation={mockNavigation}
        currentRoute="/dashboard"
        isOpen={true}
        onClose={vi.fn()}
      />
    );

    const purchaseRequestsButton = screen.getByText('Purchase Requests').closest('button');
    
    // Expand dropdown
    if (purchaseRequestsButton) {
      fireEvent.click(purchaseRequestsButton);
    }

    await waitFor(() => {
      expect(screen.getByText('My Requests')).toBeInTheDocument();
    });

    // Simulate navigation (re-render with new route)
    rerender(
      <Sidebar
        navigation={mockNavigation}
        currentRoute="/purchase-requests"
        isOpen={true}
        onClose={vi.fn()}
      />
    );

    // Dropdown should remain expanded
    expect(screen.getByText('My Requests')).toBeInTheDocument();
  });
});
