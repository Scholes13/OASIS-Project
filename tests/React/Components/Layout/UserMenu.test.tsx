import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import UserMenu from '@/components/layout/UserMenu';
import type { User } from '@/types';
import { router } from '@inertiajs/react';

describe('UserMenu Component', () => {
  const mockUser: User = {
    id: 1,
    name: 'Test User',
    email: 'test@example.com',
    role: 'user',
    avatar_url: null,
    primary_department_id: 1,
  };

  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('renders user name', () => {
    render(<UserMenu user={mockUser} />);

    expect(screen.getByText('Test User')).toBeInTheDocument();
  });

  it('renders user initials when no avatar', () => {
    render(<UserMenu user={mockUser} />);

    expect(screen.getByText('TU')).toBeInTheDocument();
  });

  it('renders user avatar when available', () => {
    const userWithAvatar: User = {
      ...mockUser,
      avatar_url: '/storage/avatars/test-user.jpg',
    };

    render(<UserMenu user={userWithAvatar} />);

    const avatar = screen.getByAltText('Test User');
    expect(avatar).toBeInTheDocument();
    expect(avatar).toHaveAttribute('src', '/storage/avatars/test-user.jpg');
  });

  it('opens dropdown when clicked', async () => {
    render(<UserMenu user={mockUser} />);

    const button = screen.getByRole('button');
    fireEvent.click(button);

    await waitFor(() => {
      expect(screen.getByText('Profile')).toBeInTheDocument();
      expect(screen.getByText('Logout')).toBeInTheDocument();
    });
  });

  it('navigates to profile when profile option is clicked', async () => {
    render(<UserMenu user={mockUser} />);

    const button = screen.getByRole('button');
    fireEvent.click(button);

    await waitFor(() => {
      const profileLink = screen.getByText('Profile');
      fireEvent.click(profileLink);
    });

    expect(router.visit).toHaveBeenCalledWith('/profile');
  });

  it('calls logout when logout option is clicked', async () => {
    render(<UserMenu user={mockUser} />);

    const button = screen.getByRole('button');
    fireEvent.click(button);

    await waitFor(() => {
      const logoutButton = screen.getByText('Logout');
      fireEvent.click(logoutButton);
    });

    expect(router.post).toHaveBeenCalledWith('/logout');
  });

  it('closes dropdown when clicking outside', async () => {
    render(
      <div>
        <UserMenu user={mockUser} />
        <div data-testid="outside">Outside</div>
      </div>
    );

    const button = screen.getByRole('button');
    fireEvent.click(button);

    await waitFor(() => {
      expect(screen.getByText('Profile')).toBeInTheDocument();
    });

    const outside = screen.getByTestId('outside');
    fireEvent.click(outside);

    await waitFor(() => {
      expect(screen.queryByText('Profile')).not.toBeInTheDocument();
    });
  });

  it('displays user email', async () => {
    render(<UserMenu user={mockUser} />);

    const button = screen.getByRole('button');
    fireEvent.click(button);

    await waitFor(() => {
      expect(screen.getByText('test@example.com')).toBeInTheDocument();
    });
  });

  it('generates correct initials for multi-word names', () => {
    const userWithLongName: User = {
      ...mockUser,
      name: 'John Michael Smith',
    };

    render(<UserMenu user={userWithLongName} />);

    expect(screen.getByText('JM')).toBeInTheDocument();
  });

  it('generates correct initials for single-word names', () => {
    const userWithSingleName: User = {
      ...mockUser,
      name: 'John',
    };

    render(<UserMenu user={userWithSingleName} />);

    expect(screen.getByText('J')).toBeInTheDocument();
  });
});
