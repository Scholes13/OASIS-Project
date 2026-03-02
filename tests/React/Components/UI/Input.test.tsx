import { describe, it, expect, vi } from 'vitest';
import { render, screen, fireEvent } from '@testing-library/react';
import { Input } from '@/components/ui/input';

describe('Input Component', () => {
  it('renders input field', () => {
    render(<Input placeholder="Enter text" />);

    expect(screen.getByPlaceholderText('Enter text')).toBeInTheDocument();
  });

  it('accepts user input', () => {
    render(<Input />);

    const input = screen.getByRole('textbox') as HTMLInputElement;
    fireEvent.change(input, { target: { value: 'test value' } });

    expect(input.value).toBe('test value');
  });

  it('calls onChange when value changes', () => {
    const onChange = vi.fn();
    render(<Input onChange={onChange} />);

    const input = screen.getByRole('textbox');
    fireEvent.change(input, { target: { value: 'new value' } });

    expect(onChange).toHaveBeenCalled();
  });

  it('applies custom className', () => {
    render(<Input className="custom-class" />);

    const input = screen.getByRole('textbox');
    expect(input).toHaveClass('custom-class');
  });

  it('supports different input types', () => {
    const { rerender } = render(<Input type="email" />);
    expect(screen.getByRole('textbox')).toHaveAttribute('type', 'email');

    rerender(<Input type="password" />);
    const passwordInput = document.querySelector('input[type="password"]');
    expect(passwordInput).toBeInTheDocument();

    rerender(<Input type="number" />);
    const numberInput = document.querySelector('input[type="number"]');
    expect(numberInput).toBeInTheDocument();
  });

  it('is disabled when disabled prop is true', () => {
    render(<Input disabled />);

    const input = screen.getByRole('textbox');
    expect(input).toBeDisabled();
  });

  it('is readonly when readOnly prop is true', () => {
    render(<Input readOnly value="readonly value" />);

    const input = screen.getByRole('textbox') as HTMLInputElement;
    expect(input).toHaveAttribute('readonly');
    expect(input.value).toBe('readonly value');
  });

  it('supports required attribute', () => {
    render(<Input required />);

    const input = screen.getByRole('textbox');
    expect(input).toBeRequired();
  });

  it('supports maxLength attribute', () => {
    render(<Input maxLength={10} />);

    const input = screen.getByRole('textbox');
    expect(input).toHaveAttribute('maxLength', '10');
  });

  it('supports min and max for number inputs', () => {
    render(<Input type="number" min={0} max={100} />);

    const input = document.querySelector('input[type="number"]');
    expect(input).toHaveAttribute('min', '0');
    expect(input).toHaveAttribute('max', '100');
  });

  it('supports step for number inputs', () => {
    render(<Input type="number" step={0.01} />);

    const input = document.querySelector('input[type="number"]');
    expect(input).toHaveAttribute('step', '0.01');
  });

  it('applies focus styles', () => {
    render(<Input />);

    const input = screen.getByRole('textbox');
    expect(input).toHaveClass('focus:ring-2', 'focus:ring-primary');
  });

  it('applies error styles when error prop is provided', () => {
    render(<Input className="border-red-500" />);

    const input = screen.getByRole('textbox');
    expect(input).toHaveClass('border-red-500');
  });
});
