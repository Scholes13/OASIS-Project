import { describe, it, expect } from 'vitest';
import { render, screen } from '@testing-library/react';
import { Badge } from '@/components/ui/Badge';

describe('Badge Component', () => {
  it('renders badge with text', () => {
    render(<Badge>New</Badge>);

    expect(screen.getByText('New')).toBeInTheDocument();
  });

  it('applies default variant styles', () => {
    render(<Badge>Default</Badge>);

    const badge = screen.getByText('Default');
    expect(badge).toHaveClass('bg-gray-100');
  });

  it('applies success variant styles', () => {
    render(<Badge variant="success">Success</Badge>);

    const badge = screen.getByText('Success');
    expect(badge).toHaveClass('bg-emerald-100');
  });

  it('applies warning variant styles', () => {
    render(<Badge variant="warning">Warning</Badge>);

    const badge = screen.getByText('Warning');
    expect(badge).toHaveClass('bg-amber-100');
  });

  it('applies error variant styles', () => {
    render(<Badge variant="error">Error</Badge>);

    const badge = screen.getByText('Error');
    expect(badge).toHaveClass('bg-red-100');
  });

  it('applies info variant styles', () => {
    render(<Badge variant="info">Info</Badge>);

    const badge = screen.getByText('Info');
    expect(badge).toHaveClass('bg-blue-100');
  });

  it('renders with custom className', () => {
    render(<Badge className="custom-class">Custom</Badge>);

    const badge = screen.getByText('Custom');
    expect(badge).toHaveClass('custom-class');
  });

  it('renders with icon', () => {
    render(
      <Badge>
        <span>✓</span> Approved
      </Badge>
    );

    expect(screen.getByText('✓')).toBeInTheDocument();
    expect(screen.getByText('Approved')).toBeInTheDocument();
  });

  it('applies correct text color for each variant', () => {
    const { rerender } = render(<Badge variant="success">Success</Badge>);
    expect(screen.getByText('Success')).toHaveClass('text-emerald-700');

    rerender(<Badge variant="warning">Warning</Badge>);
    expect(screen.getByText('Warning')).toHaveClass('text-amber-700');

    rerender(<Badge variant="error">Error</Badge>);
    expect(screen.getByText('Error')).toHaveClass('text-red-700');

    rerender(<Badge variant="info">Info</Badge>);
    expect(screen.getByText('Info')).toHaveClass('text-blue-700');
  });
});
