import '@testing-library/jest-dom';
import { expect, afterEach, vi } from 'vitest';
import { cleanup } from '@testing-library/react';

// Cleanup after each test
afterEach(() => {
  cleanup();
});

// Mock Inertia router
vi.mock('@inertiajs/react', async () => {
  const actual = await vi.importActual('@inertiajs/react');
  return {
    ...actual,
    router: {
      visit: vi.fn(),
      get: vi.fn(),
      post: vi.fn(),
      put: vi.fn(),
      patch: vi.fn(),
      delete: vi.fn(),
      reload: vi.fn(),
      replace: vi.fn(),
      on: vi.fn(),
      cancel: vi.fn(),
    },
    usePage: vi.fn(() => ({
      props: {
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
      },
    })),
  };
});

// Mock window.route (Ziggy)
global.route = vi.fn((name: string, params?: any) => {
  if (params) {
    return `/${name}/${params}`;
  }
  return `/${name}`;
});

// Mock IntersectionObserver
global.IntersectionObserver = class IntersectionObserver {
  constructor() {}
  disconnect() {}
  observe() {}
  takeRecords() {
    return [];
  }
  unobserve() {}
} as any;

// Mock ResizeObserver
global.ResizeObserver = class ResizeObserver {
  constructor() {}
  disconnect() {}
  observe() {}
  unobserve() {}
} as any;

// Mock matchMedia
Object.defineProperty(window, 'matchMedia', {
  writable: true,
  value: vi.fn().mockImplementation((query) => ({
    matches: false,
    media: query,
    onchange: null,
    addListener: vi.fn(),
    removeListener: vi.fn(),
    addEventListener: vi.fn(),
    removeEventListener: vi.fn(),
    dispatchEvent: vi.fn(),
  })),
});
