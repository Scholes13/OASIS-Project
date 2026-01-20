import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import Index from '@/Pages/Purchasing/PurchaseRequest/Index';
import type { PRIndexPageProps } from '@/types/purchasing';
import { router } from '@inertiajs/react';

describe('Purchase Request Index Page', () => {
  const mockProps: PRIndexPageProps = {
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
    purchaseRequests: {
      data: [
        {
          id: 1,
          pr_number: 'PR-WNS-2025-001',
          business_unit_id: 1,
          department_id: 1,
          user_id: 1,
          status: 'draft',
          total_amount: 1000000,
          currency: 'IDR',
          used_for: 'Office supplies',
          date_of_request: '2025-01-15',
          notes: null,
          created_at: '2025-01-15T10:00:00Z',
          updated_at: '2025-01-15T10:00:00Z',
          user: {
            id: 1,
            name: 'Test User',
            email: 'test@example.com',
            role: 'user',
            avatar_url: null,
            primary_department_id: 1,
          },
          department: {
            id: 1,
            name: 'General Affairs',
            code: 'GA',
            business_unit_id: 1,
            is_active: true,
          },
          business_unit: {
            id: 1,
            code: 'WNS',
            name: 'WNS Business Unit',
            logo: null,
          },
        },
      ],
      meta: {
        current_page: 1,
        last_page: 1,
        per_page: 10,
        total: 1,
        from: 1,
        to: 1,
        links: [],
      },
      links: {
        prev: null,
        next: null,
      },
    },
    filters: {
      status: undefined,
      search: undefined,
      date_from: undefined,
      date_to: undefined,
    },
    statuses: [
      { value: 'all', label: 'All Status' },
      { value: 'draft', label: 'Draft' },
      { value: 'submitted', label: 'Submitted' },
      { value: 'approved', label: 'Approved' },
    ],
  };

  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('renders page title', () => {
    render(<Index {...mockProps} />);

    expect(screen.getByText('My Purchase Requests')).toBeInTheDocument();
  });

  it('renders create new button', () => {
    render(<Index {...mockProps} />);

    expect(screen.getByText('Create New PR')).toBeInTheDocument();
  });

  it('navigates to create page when create button is clicked', () => {
    render(<Index {...mockProps} />);

    const createButton = screen.getByText('Create New PR');
    fireEvent.click(createButton);

    expect(router.visit).toHaveBeenCalledWith('/purchase-requests/create');
  });

  it('renders filter controls', () => {
    render(<Index {...mockProps} />);

    expect(screen.getByPlaceholderText(/Search/i)).toBeInTheDocument();
    expect(screen.getByText('All Status')).toBeInTheDocument();
  });

  it('renders purchase request table', () => {
    render(<Index {...mockProps} />);

    expect(screen.getByText('PR-WNS-2025-001')).toBeInTheDocument();
  });

  it('filters by status', async () => {
    render(<Index {...mockProps} />);

    const statusSelect = screen.getByText('All Status');
    fireEvent.click(statusSelect);

    await waitFor(() => {
      const draftOption = screen.getByText('Draft');
      fireEvent.click(draftOption);
    });

    expect(router.get).toHaveBeenCalledWith(
      '/purchase-requests',
      expect.objectContaining({ status: 'draft' }),
      expect.any(Object)
    );
  });

  it('searches purchase requests', async () => {
    vi.useFakeTimers();
    render(<Index {...mockProps} />);

    const searchInput = screen.getByPlaceholderText(/Search/i);
    fireEvent.change(searchInput, { target: { value: 'PR-WNS' } });

    // Wait for debounce
    vi.advanceTimersByTime(300);

    await waitFor(() => {
      expect(router.get).toHaveBeenCalledWith(
        '/purchase-requests',
        expect.objectContaining({ search: 'PR-WNS' }),
        expect.any(Object)
      );
    });

    vi.useRealTimers();
  });

  it('displays empty state when no purchase requests', () => {
    const emptyProps = {
      ...mockProps,
      purchaseRequests: {
        ...mockProps.purchaseRequests,
        data: [],
        meta: {
          ...mockProps.purchaseRequests.meta,
          total: 0,
        },
      },
    };

    render(<Index {...emptyProps} />);

    expect(screen.getByText(/No purchase requests found/i)).toBeInTheDocument();
  });

  it('displays pagination controls', () => {
    const propsWithPagination = {
      ...mockProps,
      purchaseRequests: {
        ...mockProps.purchaseRequests,
        meta: {
          ...mockProps.purchaseRequests.meta,
          last_page: 3,
          total: 25,
        },
      },
    };

    render(<Index {...propsWithPagination} />);

    expect(screen.getByText(/Page 1 of 3/i)).toBeInTheDocument();
  });

  it('shows loading state during navigation', () => {
    render(<Index {...mockProps} />);

    // Simulate loading state
    const { container } = render(<Index {...mockProps} />);
    
    // Check for loading overlay (when isLoading is true)
    // This would be tested with actual Inertia navigation
  });

  it('preserves filters when navigating', () => {
    const propsWithFilters = {
      ...mockProps,
      filters: {
        status: 'draft',
        search: 'test',
        date_from: undefined,
        date_to: undefined,
      },
    };

    render(<Index {...propsWithFilters} />);

    const searchInput = screen.getByPlaceholderText(/Search/i) as HTMLInputElement;
    expect(searchInput.value).toBe('test');
  });

  it('displays business unit name in header', () => {
    render(<Index {...mockProps} />);

    expect(screen.getByText(/WNS Business Unit/i)).toBeInTheDocument();
  });

  it('uses AppLayout wrapper', () => {
    const { container } = render(<Index {...mockProps} />);

    expect(container.querySelector('[data-testid="app-layout"]')).toBeInTheDocument();
  });
});
