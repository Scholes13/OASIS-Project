import { describe, it, expect, vi } from 'vitest';
import { render, screen, fireEvent } from '@testing-library/react';
import PurchaseRequestTable from '@/components/purchasing/PurchaseRequestTable';
import type { PurchaseRequest } from '@/types/purchasing';

describe('PurchaseRequestTable Component', () => {
  const mockPurchaseRequests: PurchaseRequest[] = [
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
    {
      id: 2,
      pr_number: 'PR-WNS-2025-002',
      business_unit_id: 1,
      department_id: 1,
      user_id: 1,
      status: 'approved',
      total_amount: 2500000,
      currency: 'IDR',
      used_for: 'Equipment purchase',
      date_of_request: '2025-01-16',
      notes: null,
      created_at: '2025-01-16T10:00:00Z',
      updated_at: '2025-01-16T10:00:00Z',
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
  ];

  it('renders table with purchase requests', () => {
    render(<PurchaseRequestTable purchaseRequests={mockPurchaseRequests} />);

    expect(screen.getByText('PR-WNS-2025-001')).toBeInTheDocument();
    expect(screen.getByText('PR-WNS-2025-002')).toBeInTheDocument();
  });

  it('displays PR numbers', () => {
    render(<PurchaseRequestTable purchaseRequests={mockPurchaseRequests} />);

    expect(screen.getByText('PR-WNS-2025-001')).toBeInTheDocument();
    expect(screen.getByText('PR-WNS-2025-002')).toBeInTheDocument();
  });

  it('displays requester names', () => {
    render(<PurchaseRequestTable purchaseRequests={mockPurchaseRequests} />);

    const userNames = screen.getAllByText('Test User');
    expect(userNames).toHaveLength(2);
  });

  it('displays department names', () => {
    render(<PurchaseRequestTable purchaseRequests={mockPurchaseRequests} />);

    const departments = screen.getAllByText('General Affairs');
    expect(departments).toHaveLength(2);
  });

  it('displays status badges with correct colors', () => {
    render(<PurchaseRequestTable purchaseRequests={mockPurchaseRequests} />);

    const draftBadge = screen.getByText('Draft');
    expect(draftBadge).toHaveClass('bg-gray-100');

    const approvedBadge = screen.getByText('Approved');
    expect(approvedBadge).toHaveClass('bg-emerald-100');
  });

  it('displays formatted amounts', () => {
    render(<PurchaseRequestTable purchaseRequests={mockPurchaseRequests} />);

    expect(screen.getByText(/1,000,000/)).toBeInTheDocument();
    expect(screen.getByText(/2,500,000/)).toBeInTheDocument();
  });

  it('displays formatted dates', () => {
    render(<PurchaseRequestTable purchaseRequests={mockPurchaseRequests} />);

    expect(screen.getByText(/Jan 15, 2025/)).toBeInTheDocument();
    expect(screen.getByText(/Jan 16, 2025/)).toBeInTheDocument();
  });

  it('makes rows clickable', () => {
    const { container } = render(
      <PurchaseRequestTable purchaseRequests={mockPurchaseRequests} />
    );

    const rows = container.querySelectorAll('tbody tr');
    expect(rows).toHaveLength(2);

    rows.forEach((row) => {
      expect(row).toHaveClass('cursor-pointer');
    });
  });

  it('navigates to detail page when row is clicked', () => {
    render(<PurchaseRequestTable purchaseRequests={mockPurchaseRequests} />);

    const firstRow = screen.getByText('PR-WNS-2025-001').closest('tr');
    if (firstRow) {
      fireEvent.click(firstRow);
      // Inertia router.visit should be called
      expect(vi.mocked(router.visit)).toHaveBeenCalledWith('/purchase-requests/1');
    }
  });

  it('displays empty state when no purchase requests', () => {
    render(<PurchaseRequestTable purchaseRequests={[]} />);

    expect(screen.getByText(/No purchase requests found/i)).toBeInTheDocument();
  });

  it('applies hover styles to rows', () => {
    const { container } = render(
      <PurchaseRequestTable purchaseRequests={mockPurchaseRequests} />
    );

    const rows = container.querySelectorAll('tbody tr');
    rows.forEach((row) => {
      expect(row).toHaveClass('hover:bg-gray-50');
    });
  });

  it('displays all required columns', () => {
    render(<PurchaseRequestTable purchaseRequests={mockPurchaseRequests} />);

    expect(screen.getByText('PR Number')).toBeInTheDocument();
    expect(screen.getByText('Requester')).toBeInTheDocument();
    expect(screen.getByText('Department')).toBeInTheDocument();
    expect(screen.getByText('Status')).toBeInTheDocument();
    expect(screen.getByText('Amount')).toBeInTheDocument();
    expect(screen.getByText('Date')).toBeInTheDocument();
  });

  it('renders different status badges correctly', () => {
    const prWithDifferentStatuses: PurchaseRequest[] = [
      { ...mockPurchaseRequests[0], status: 'draft' },
      { ...mockPurchaseRequests[0], id: 2, status: 'submitted' },
      { ...mockPurchaseRequests[0], id: 3, status: 'in_approval' },
      { ...mockPurchaseRequests[0], id: 4, status: 'approved' },
      { ...mockPurchaseRequests[0], id: 5, status: 'rejected' },
      { ...mockPurchaseRequests[0], id: 6, status: 'voided' },
    ];

    render(<PurchaseRequestTable purchaseRequests={prWithDifferentStatuses} />);

    expect(screen.getByText('Draft')).toHaveClass('bg-gray-100');
    expect(screen.getByText('Submitted')).toHaveClass('bg-blue-100');
    expect(screen.getByText('In Approval')).toHaveClass('bg-amber-100');
    expect(screen.getByText('Approved')).toHaveClass('bg-emerald-100');
    expect(screen.getByText('Rejected')).toHaveClass('bg-red-100');
    expect(screen.getByText('Voided')).toHaveClass('bg-gray-100');
  });
});
