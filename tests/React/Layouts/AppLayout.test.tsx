import { describe, it, expect, vi } from 'vitest';
import { render, screen } from '@testing-library/react';
import AppLayout from '../../../resources/js/inertia/layouts/AppLayout';

vi.mock('@/components/layout/Sidebar', () => ({
  default: () => <aside data-testid="sidebar" aria-label="Main navigation" />,
}));

vi.mock('@/components/layout/Navbar', () => ({
  default: () => <header data-testid="navbar" role="banner" />,
}));

vi.mock('@/components/layout/BuTransitionOverlay', () => ({
  default: () => null,
}));

vi.mock('@/components/layout/LogoutOverlay', () => ({
  default: () => null,
}));

vi.mock('@/components/ui/toast', () => ({
  Toaster: () => null,
}));

describe('AppLayout', () => {
  it('renders main content area with flex-1 and overflow-y-auto for full-screen layout', () => {
    const { container } = render(
      <AppLayout>
        <div>Dashboard content</div>
      </AppLayout>
    );

    const main = container.querySelector('main');

    expect(main).toBeInTheDocument();
    expect(main).toHaveClass('flex-1');
    expect(main).toHaveClass('overflow-y-auto');
  });

  it('renders skip-to-content link for WCAG accessibility', () => {
    render(
      <AppLayout>
        <div>Content</div>
      </AppLayout>
    );

    const skipLink = screen.getByText('Skip to content');
    expect(skipLink).toBeInTheDocument();
    expect(skipLink).toHaveAttribute('href', '#main-content');
    expect(skipLink.tagName).toBe('A');
  });

  it('main content has id for skip-to-content target', () => {
    const { container } = render(
      <AppLayout>
        <div>Content</div>
      </AppLayout>
    );

    const main = container.querySelector('#main-content');
    expect(main).toBeInTheDocument();
    expect(main?.tagName).toBe('MAIN');
  });

  it('sidebar is hidden on mobile via lg:block wrapper', () => {
    const { container } = render(
      <AppLayout>
        <div>Content</div>
      </AppLayout>
    );

    const sidebarWrapper = container.querySelector('.hidden.lg\\:block');
    expect(sidebarWrapper).toBeInTheDocument();
  });

  it('main wrapper has no margin on mobile (responsive sidebar)', () => {
    const { container } = render(
      <AppLayout>
        <div>Content</div>
      </AppLayout>
    );

    // The main wrapper should NOT have ml-16 or ml-64 without lg: prefix
    // This ensures mobile gets full width (no sidebar margin)
    const mainWrapper = container.querySelector('.flex-1.flex-col');
    expect(mainWrapper).toBeInTheDocument();

    // Should not have non-responsive margin classes
    expect(mainWrapper).not.toHaveClass('ml-16');
    expect(mainWrapper).not.toHaveClass('ml-64');
  });
});
