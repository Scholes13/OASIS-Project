# Admin Panel Testing Guide

This document provides guidelines and examples for testing admin panel components using Vitest, React Testing Library, and fast-check for property-based testing.

## Testing Stack

- **Vitest**: Fast unit test runner for Vite projects
- **React Testing Library**: Component testing with user-centric queries
- **fast-check**: Property-based testing library for TypeScript
- **@testing-library/user-event**: User interaction simulation
- **@testing-library/jest-dom**: Custom matchers for DOM assertions

## Running Tests

```bash
# Run all tests in watch mode
npm test

# Run tests once
npm run test:run

# Run tests with coverage
npm run test:coverage
```

## Test File Location

All admin panel tests should be placed in:
```
tests/React/Components/Admin/
```

## Unit Testing Examples

### Testing StatCard Component

```typescript
// tests/React/Components/Admin/StatCard.test.tsx
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
    
    expect(screen.getByText('+12%')).toBeInTheDocument();
  });

  it('applies correct color classes', () => {
    const { container } = render(
      <StatCard
        title="Total Users"
        value={150}
        icon={Users}
        color="emerald"
      />
    );
    
    // Check for emerald color classes
    expect(container.querySelector('.bg-emerald-50')).toBeInTheDocument();
  });
});
```

### Testing FileUpload Component

```typescript
// tests/React/Components/Admin/FileUpload.test.tsx
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, it, expect, vi } from 'vitest';
import { FileUpload } from '@/components/admin/FileUpload';

describe('FileUpload Component', () => {
  it('renders upload area', () => {
    const mockOnFileSelect = vi.fn();
    render(
      <FileUpload
        label="Logo"
        accept="image/*"
        maxSize={2 * 1024 * 1024}
        onFileSelect={mockOnFileSelect}
      />
    );
    
    expect(screen.getByText('Logo')).toBeInTheDocument();
    expect(screen.getByText(/click to upload/i)).toBeInTheDocument();
  });

  it('validates file size', async () => {
    const user = userEvent.setup();
    const mockOnFileSelect = vi.fn();
    const alertSpy = vi.spyOn(window, 'alert').mockImplementation(() => {});
    
    render(
      <FileUpload
        label="Logo"
        accept="image/*"
        maxSize={1024} // 1KB
        onFileSelect={mockOnFileSelect}
      />
    );
    
    // Create a file larger than 1KB
    const largeFile = new File(['x'.repeat(2000)], 'large.jpg', { type: 'image/jpeg' });
    const input = screen.getByLabelText('Logo', { selector: 'input' });
    
    await user.upload(input, largeFile);
    
    await waitFor(() => {
      expect(alertSpy).toHaveBeenCalledWith(expect.stringContaining('exceeds'));
    });
    expect(mockOnFileSelect).not.toHaveBeenCalled();
    
    alertSpy.mockRestore();
  });

  it('displays preview when file is selected', () => {
    const mockOnFileSelect = vi.fn();
    const { rerender } = render(
      <FileUpload
        label="Logo"
        accept="image/*"
        maxSize={2 * 1024 * 1024}
        onFileSelect={mockOnFileSelect}
      />
    );
    
    // Rerender with preview
    rerender(
      <FileUpload
        label="Logo"
        accept="image/*"
        maxSize={2 * 1024 * 1024}
        onFileSelect={mockOnFileSelect}
        preview="data:image/png;base64,..."
      />
    );
    
    expect(screen.getByAltText('Preview')).toBeInTheDocument();
    expect(screen.getByText('Remove')).toBeInTheDocument();
  });
});
```

### Testing ColorPicker Component

```typescript
// tests/React/Components/Admin/ColorPicker.test.tsx
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, it, expect, vi } from 'vitest';
import { ColorPicker } from '@/components/admin/ColorPicker';

describe('ColorPicker Component', () => {
  it('renders with initial color', () => {
    const mockOnChange = vi.fn();
    render(
      <ColorPicker
        label="Activity Type Color"
        value="#6366F1"
        onChange={mockOnChange}
      />
    );
    
    expect(screen.getByText('Activity Type Color')).toBeInTheDocument();
    expect(screen.getByText('#6366F1')).toBeInTheDocument();
  });

  it('opens color picker on button click', async () => {
    const user = userEvent.setup();
    const mockOnChange = vi.fn();
    
    render(
      <ColorPicker
        label="Activity Type Color"
        value="#6366F1"
        onChange={mockOnChange}
      />
    );
    
    const button = screen.getByRole('button');
    await user.click(button);
    
    await waitFor(() => {
      expect(screen.getByText('Preset Colors')).toBeInTheDocument();
      expect(screen.getByText('Custom Color')).toBeInTheDocument();
    });
  });

  it('calls onChange when preset color is selected', async () => {
    const user = userEvent.setup();
    const mockOnChange = vi.fn();
    
    render(
      <ColorPicker
        label="Activity Type Color"
        value="#6366F1"
        onChange={mockOnChange}
      />
    );
    
    // Open picker
    await user.click(screen.getByRole('button'));
    
    // Click first preset color
    const presetButtons = screen.getAllByRole('button');
    await user.click(presetButtons[1]); // First preset color button
    
    await waitFor(() => {
      expect(mockOnChange).toHaveBeenCalled();
    });
  });
});
```

## Property-Based Testing Examples

### Property 1: Dashboard Statistics Display

```typescript
// tests/React/Components/Admin/Dashboard.property.test.tsx
import fc from 'fast-check';
import { render, screen } from '@testing-library/react';
import { describe, it, expect } from 'vitest';
import Dashboard from '@/Pages/Admin/Dashboard';

describe('Feature: admin-panel-react-migration, Property 1: Dashboard Statistics Display', () => {
  it('should display all four statistics with correct values for any valid data', () => {
    fc.assert(
      fc.property(
        fc.record({
          total_users: fc.nat(),
          total_business_units: fc.nat(),
          total_departments: fc.nat(),
          total_purchase_requests: fc.nat(),
        }),
        (stats) => {
          render(
            <Dashboard 
              stats={stats}
              recentUsers={[]}
              businessUnitStats={[]}
              monthlyPRs={{}}
            />
          );
          
          expect(screen.getByText(stats.total_users.toString())).toBeInTheDocument();
          expect(screen.getByText(stats.total_business_units.toString())).toBeInTheDocument();
          expect(screen.getByText(stats.total_departments.toString())).toBeInTheDocument();
          expect(screen.getByText(stats.total_purchase_requests.toString())).toBeInTheDocument();
        }
      ),
      { numRuns: 100 }
    );
  });
});
```

### Property 13: File Upload Validation

```typescript
// tests/React/Components/Admin/FileUpload.property.test.tsx
import fc from 'fast-check';
import { describe, it, expect } from 'vitest';

// Validation function to test
function validateFile(file: File, maxSize: number, acceptedTypes: string[]): string | null {
  const fileType = file.type;
  
  const isValidType = acceptedTypes.some(type => {
    if (type.startsWith('.')) {
      return file.name.toLowerCase().endsWith(type.toLowerCase());
    }
    if (type.endsWith('/*')) {
      const baseType = type.split('/')[0];
      return fileType.startsWith(baseType + '/');
    }
    return fileType === type;
  });

  if (!isValidType) {
    return `Invalid file type. Please upload: ${acceptedTypes.join(', ')}`;
  }

  if (file.size > maxSize) {
    const maxSizeMB = (maxSize / (1024 * 1024)).toFixed(1);
    return `File size exceeds ${maxSizeMB}MB. Please upload a smaller file.`;
  }

  return null;
}

describe('Feature: admin-panel-react-migration, Property 13: File Upload Validation', () => {
  it('should validate file type and size for any file', () => {
    const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/svg+xml'];
    const invalidTypes = ['application/pdf', 'text/plain', 'video/mp4'];
    const maxSize = 2 * 1024 * 1024; // 2MB
    
    fc.assert(
      fc.property(
        fc.oneof(
          fc.constantFrom(...validTypes),
          fc.constantFrom(...invalidTypes)
        ),
        fc.integer({ min: 0, max: 5 * 1024 * 1024 }), // 0 to 5MB
        (fileType, fileSize) => {
          const file = new File(['content'], 'test.jpg', { type: fileType });
          Object.defineProperty(file, 'size', { value: fileSize });
          
          const error = validateFile(file, maxSize, validTypes);
          
          const isValidType = validTypes.includes(fileType);
          const isValidSize = fileSize <= maxSize;
          
          if (isValidType && isValidSize) {
            expect(error).toBeNull();
          } else {
            expect(error).not.toBeNull();
            if (!isValidType) {
              expect(error).toContain('Invalid file type');
            }
            if (!isValidSize) {
              expect(error).toContain('exceeds');
            }
          }
        }
      ),
      { numRuns: 100 }
    );
  });
});
```

### Property 50: Table Column Sorting

```typescript
// tests/React/Components/Admin/DataTable.property.test.tsx
import fc from 'fast-check';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, it, expect } from 'vitest';
import { DataTable } from '@/components/admin/DataTable';

describe('Feature: admin-panel-react-migration, Property 50: Table Column Sorting', () => {
  it('should sort data correctly for any dataset', async () => {
    const user = userEvent.setup();
    
    fc.assert(
      fc.asyncProperty(
        fc.array(
          fc.record({
            id: fc.nat(),
            name: fc.string({ minLength: 1, maxLength: 50 }),
            value: fc.integer({ min: 0, max: 1000 }),
          }),
          { minLength: 5, maxLength: 20 }
        ),
        async (data) => {
          const columns = [
            { accessorKey: 'name', header: 'Name' },
            { accessorKey: 'value', header: 'Value' },
          ];
          
          render(
            <DataTable
              data={data}
              columns={columns}
            />
          );
          
          // Click name column header to sort
          const nameHeader = screen.getByText('Name');
          await user.click(nameHeader);
          
          // Get all name cells
          const nameCells = screen.getAllByRole('cell')
            .filter(cell => cell.textContent && data.some(d => d.name === cell.textContent));
          
          // Verify sorted order (ascending)
          const sortedNames = [...data].sort((a, b) => a.name.localeCompare(b.name));
          nameCells.forEach((cell, index) => {
            if (index < sortedNames.length) {
              expect(cell.textContent).toBe(sortedNames[index].name);
            }
          });
        }
      ),
      { numRuns: 100 }
    );
  });
});
```

## Testing Best Practices

### 1. Use User-Centric Queries

Prefer queries that reflect how users interact with the app:

```typescript
// Good
screen.getByRole('button', { name: /submit/i });
screen.getByLabelText('Email');
screen.getByText('Welcome');

// Avoid
container.querySelector('.submit-button');
```

### 2. Test User Interactions

Use `@testing-library/user-event` for realistic user interactions:

```typescript
import userEvent from '@testing-library/user-event';

const user = userEvent.setup();
await user.click(button);
await user.type(input, 'text');
await user.selectOptions(select, 'option1');
```

### 3. Wait for Async Updates

Use `waitFor` for async operations:

```typescript
await waitFor(() => {
  expect(screen.getByText('Success')).toBeInTheDocument();
});
```

### 4. Mock External Dependencies

Mock Inertia router and other external dependencies:

```typescript
import { vi } from 'vitest';
import { router } from '@inertiajs/react';

vi.mock('@inertiajs/react', () => ({
  router: {
    post: vi.fn(),
    visit: vi.fn(),
  },
}));
```

### 5. Property Test Generators

Use appropriate fast-check generators:

```typescript
// Numbers
fc.nat() // Natural numbers (0, 1, 2, ...)
fc.integer({ min: 0, max: 100 }) // Integers in range

// Strings
fc.string() // Any string
fc.string({ minLength: 1, maxLength: 50 }) // Bounded strings
fc.hexaString({ minLength: 6, maxLength: 6 }) // Hex colors

// Arrays
fc.array(fc.nat(), { minLength: 1, maxLength: 10 })

// Objects
fc.record({
  name: fc.string(),
  age: fc.nat(),
})

// Choices
fc.oneof(fc.constant('a'), fc.constant('b'))
fc.constantFrom('red', 'green', 'blue')
```

### 6. Minimum Iterations

All property tests must run at least 100 iterations:

```typescript
fc.assert(
  fc.property(/* ... */),
  { numRuns: 100 } // Required minimum
);
```

## Coverage Goals

- **Target**: 80%+ code coverage
- **Focus**: Core functionality and user interactions
- **Exclude**: Type definitions, configuration files

Run coverage report:
```bash
npm run test:coverage
```

## Continuous Integration

Tests run automatically on:
- Pre-commit hooks
- Pull request checks
- CI/CD pipeline

Ensure all tests pass before committing code.
