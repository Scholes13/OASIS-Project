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

  it('displays request purposes', () => {
    render(<PurchaseRequestTable purchaseRequests={mockPurchaseRequests} />);

    expect(screen.getByText('Office supplies')).toBeInTheDocument();
    expect(screen.getByText('Equipment purchase')).toBeInTheDocument();
  });

  it('displays department codes', () => {
    render(<PurchaseRequestTable purchaseRequests={mockPurchaseRequests} />);

    const departments = screen.getAllByText('GA');
    expect(departments).toHaveLength(2);
  });

  it('displays status badges with correct colors', () => {
    render(<PurchaseRequestTable purchaseRequests={mockPurchaseRequests} />);

    const draftBadge = screen.getByText('Draft');
    expect(draftBadge).toHaveClass('text-gray-600');

    const approvedBadge = screen.getByText('Approved');
    expect(approvedBadge).toHaveClass('text-emerald-600');
  });

  it('displays formatted amounts', () => {
    render(<PurchaseRequestTable purchaseRequests={mockPurchaseRequests} />);

    expect(screen.getByText('IDR 1.000.000')).toBeInTheDocument();
    expect(screen.getByText('IDR 2.500.000')).toBeInTheDocument();
  });

  it('displays formatted dates', () => {
    render(<PurchaseRequestTable purchaseRequests={mockPurchaseRequests} />);

    expect(screen.getByText('15 Januari 2025')).toBeInTheDocument();
    expect(screen.getByText('16 Januari 2025')).toBeInTheDocument();
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

  it('reports the selected request when a row is clicked', () => {
    const onRowClick = vi.fn();
    render(
      <PurchaseRequestTable
        purchaseRequests={mockPurchaseRequests}
        onRowClick={onRowClick}
      />
    );

    const firstRow = screen.getByText('PR-WNS-2025-001').closest('tr');
    expect(firstRow).not.toBeNull();
    fireEvent.click(firstRow!);

    expect(onRowClick).toHaveBeenCalledWith(mockPurchaseRequests[0]);
  });

  it('renders no data rows when no purchase requests exist', () => {
    const { container } = render(<PurchaseRequestTable purchaseRequests={[]} />);

    expect(container.querySelectorAll('tbody tr')).toHaveLength(0);
  });

  it('applies hover styles to rows', () => {
    const { container } = render(
      <PurchaseRequestTable purchaseRequests={mockPurchaseRequests} />
    );

    const rows = container.querySelectorAll('tbody tr');
    rows.forEach((row) => {
      expect(row).toHaveClass('hover:bg-gray-50/50');
    });
  });

  it('displays all required columns', () => {
    render(<PurchaseRequestTable purchaseRequests={mockPurchaseRequests} />);

    expect(screen.getByText('DEPT')).toBeInTheDocument();
    expect(screen.getByText('NO. PR')).toBeInTheDocument();
    expect(screen.getByText('USED FOR')).toBeInTheDocument();
    expect(screen.getByText('AMOUNT')).toBeInTheDocument();
    expect(screen.getByText('DATE')).toBeInTheDocument();
    expect(screen.getByText('STATUS')).toBeInTheDocument();
    expect(screen.getByText('ACTIONS')).toBeInTheDocument();
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

    expect(screen.getByText('Draft')).toHaveClass('text-gray-600');
    expect(screen.getByText('Submitted')).toHaveClass('text-blue-600');
    expect(screen.getByText('In Approval')).toHaveClass('text-blue-600');
    expect(screen.getByText('Approved')).toHaveClass('text-emerald-600');
    expect(screen.getByText('Rejected')).toHaveClass('text-red-600');
    expect(screen.getByText('Voided')).toHaveClass('text-gray-500');
  });
});
