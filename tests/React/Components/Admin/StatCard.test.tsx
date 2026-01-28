import { render, screen } from '@testing-library/react';
import { describe, it, expect } from 'vitest';
import { StatCard } from '@/components/admin/StatCard';
import { Users } from 'lucide-react';

describe('StatCard Component', () => {
  it('renders title and value correctly', () => {
    render(
      <StatCard
        title="Total Users"
        value={150}
        icon={Users}
        color="indigo"
      />
    );
    
    expect(screen.getByText('Total Users')).toBeInTheDocument();
    expect(screen.getByText('150')).toBeInTheDocument();
  });
  
  it('displays trend indicator when provided', () => {
    render(
      <StatCard
        title="Total Users"
        value={150}
        icon={Users}
        color="indigo"
        trend={{ value: 12, direction: 'up' }}
      />
    );
    
    // Check for trend value (the component shows +12%)
    expect(screen.getByText(/12/)).toBeInTheDocument();
  });

  it('renders without trend indicator', () => {
    render(
      <StatCard
        title="Total Users"
        value={150}
        icon={Users}
        color="indigo"
      />
    );
    
    expect(screen.getByText('Total Users')).toBeInTheDocument();
    expect(screen.getByText('150')).toBeInTheDocument();
  });

  it('handles string values', () => {
    render(
      <StatCard
        title="Status"
        value="Active"
        icon={Users}
        color="emerald"
      />
    );
    
    expect(screen.getByText('Status')).toBeInTheDocument();
    expect(screen.getByText('Active')).toBeInTheDocument();
  });
});
