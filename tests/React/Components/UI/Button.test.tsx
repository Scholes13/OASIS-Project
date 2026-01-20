import { describe, it, expect, vi } from 'vitest';
import { render, screen, fireEvent } from '@testing-library/react';
import { Button } from '@/components/ui/Button';

describe('Button Component', () => {
  it('renders button with text', () => {
    render(<Button>Click me</Button>);

    expect(screen.getByText('Click me')).toBeInTheDocument();
  });

  it('calls onClick when clicked', () => {
    const onClick = vi.fn();
    render(<Button onClick={onClick}>Click me</Button>);

    fireEvent.click(screen.getByText('Click me'));

    expect(onClick).toHaveBeenCalledTimes(1);
  });

  it('applies default variant styles', () => {
    render(<Button>Default</Button>);

    const button = screen.getByText('Default');
    expect(button).toHaveClass('bg-indigo-600');
  });

  it('applies destructive variant styles', () => {
    render(<Button variant="destructive">Delete</Button>);

    const button = screen.getByText('Delete');
    expect(button).toHaveClass('bg-red-600');
  });

  it('applies outline variant styles', () => {
    render(<Button variant="outline">Outline</Button>);

    const button = screen.getByText('Outline');
    expect(button).toHaveClass('border');
  });

  it('applies ghost variant styles', () => {
    render(<Button variant="ghost">Ghost</Button>);

    const button = screen.getByText('Ghost');
    expect(button).toHaveClass('hover:bg-gray-100');
  });

  it('applies small size styles', () => {
    render(<Button size="sm">Small</Button>);

    const button = screen.getByText('Small');
    expect(button).toHaveClass('h-9');
  });

  it('applies large size styles', () => {
    render(<Button size="lg">Large</Button>);

    const button = screen.getByText('Large');
    expect(button).toHaveClass('h-11');
  });

  it('is disabled when disabled prop is true', () => {
    render(<Button disabled>Disabled</Button>);

    const button = screen.getByText('Disabled');
    expect(button).toBeDisabled();
    expect(button).toHaveClass('disabled:opacity-50');
  });

  it('does not call onClick when disabled', () => {
    const onClick = vi.fn();
    render(
      <Button disabled onClick={onClick}>
        Disabled
      </Button>
    );

    fireEvent.click(screen.getByText('Disabled'));

    expect(onClick).not.toHaveBeenCalled();
  });

  it('renders with custom className', () => {
    render(<Button className="custom-class">Custom</Button>);

    const button = screen.getByText('Custom');
    expect(button).toHaveClass('custom-class');
  });

  it('renders as different element when asChild is true', () => {
    render(
      <Button asChild>
        <a href="/test">Link Button</a>
      </Button>
    );

    const link = screen.getByText('Link Button');
    expect(link.tagName).toBe('A');
    expect(link).toHaveAttribute('href', '/test');
  });

  it('renders icon button', () => {
    render(
      <Button size="icon" aria-label="Icon button">
        <span>🔍</span>
      </Button>
    );

    const button = screen.getByLabelText('Icon button');
    expect(button).toHaveClass('h-10', 'w-10');
  });

  it('supports different button types', () => {
    render(<Button type="submit">Submit</Button>);

    const button = screen.getByText('Submit');
    expect(button).toHaveAttribute('type', 'submit');
  });
});
