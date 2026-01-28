# Accessibility Guidelines for Admin Panel

## Overview

This document outlines the accessibility features implemented across all admin panel pages to ensure WCAG 2.1 Level AA compliance.

## ARIA Labels Implementation

### Interactive Elements Without Text

All icon-only buttons and links must have `aria-label` attributes:

```tsx
// ✅ Good - Icon button with aria-label
<button aria-label="Edit user" onClick={handleEdit}>
  <Edit className="w-4 h-4" />
</button>

// ❌ Bad - Icon button without aria-label
<button onClick={handleEdit}>
  <Edit className="w-4 h-4" />
</button>
```

### Navigation Links

Navigation links should have descriptive aria-labels:

```tsx
// ✅ Good - Descriptive aria-label
<Link href="/admin/users" aria-label="View all users">
  View All <ArrowRight />
</Link>

// ✅ Good - Context-specific aria-label
<Link href={route('admin.users.edit', user.id)} aria-label={`Edit ${user.name}`}>
  <Edit />
</Link>
```

### Action Buttons

Action buttons should describe the action and target:

```tsx
// ✅ Good - Describes action and target
<button 
  onClick={() => handleDelete(item)} 
  aria-label={`Delete ${item.name}`}
>
  <Trash2 />
</button>

// ✅ Good - Toggle button with state
<button 
  onClick={() => handleToggle(item)} 
  aria-label={`${item.is_active ? 'Deactivate' : 'Activate'} ${item.name}`}
>
  <Power />
</button>
```

### Search Inputs

Search inputs should have proper labels and roles:

```tsx
// ✅ Good - Labeled search input
<div role="search">
  <Label htmlFor="search">Search</Label>
  <Input
    id="search"
    type="search"
    placeholder="Search users..."
    aria-label="Search users"
  />
</div>
```

### Form Fields

All form fields must have associated labels:

```tsx
// ✅ Good - Label associated with input
<div>
  <Label htmlFor="email">Email Address</Label>
  <Input
    id="email"
    type="email"
    aria-required="true"
    aria-invalid={!!errors.email}
    aria-describedby={errors.email ? "email-error" : undefined}
  />
  {errors.email && (
    <p id="email-error" className="text-sm text-red-600" role="alert">
      {errors.email}
    </p>
  )}
</div>
```

### Tooltips

Elements with tooltips should use aria-describedby:

```tsx
// ✅ Good - Tooltip with aria-describedby
<button
  aria-label="Configure purchasing"
  aria-describedby="purchasing-tooltip"
>
  <ShoppingCart />
</button>
<div id="purchasing-tooltip" role="tooltip" className="hidden">
  Configure purchasing settings for this department
</div>
```

## Modal Focus Management

### Dialog Component

The Dialog component (using Headless UI) automatically handles:
- Focus trapping within the modal
- Returning focus to trigger element on close
- Escape key to close
- Backdrop click to close

```tsx
// ✅ Good - Dialog with proper focus management
<Dialog open={isOpen} onClose={handleClose}>
  <DialogHeader onClose={handleClose}>
    <DialogTitle>Confirm Action</DialogTitle>
  </DialogHeader>
  <DialogContent>
    <p>Are you sure you want to proceed?</p>
  </DialogContent>
  <DialogFooter>
    <Button variant="outline" onClick={handleClose}>
      Cancel
    </Button>
    <Button onClick={handleConfirm}>
      Confirm
    </Button>
  </DialogFooter>
</Dialog>
```

### Custom Modals

If creating custom modals without Headless UI, implement focus management:

```tsx
import { useEffect, useRef } from 'react';

function CustomModal({ isOpen, onClose, children }) {
  const modalRef = useRef<HTMLDivElement>(null);
  const previousFocusRef = useRef<HTMLElement | null>(null);

  useEffect(() => {
    if (isOpen) {
      // Store currently focused element
      previousFocusRef.current = document.activeElement as HTMLElement;
      
      // Focus first focusable element in modal
      const firstFocusable = modalRef.current?.querySelector(
        'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
      ) as HTMLElement;
      firstFocusable?.focus();

      // Handle Escape key
      const handleEscape = (e: KeyboardEvent) => {
        if (e.key === 'Escape') onClose();
      };
      document.addEventListener('keydown', handleEscape);

      return () => {
        document.removeEventListener('keydown', handleEscape);
        // Return focus to previous element
        previousFocusRef.current?.focus();
      };
    }
  }, [isOpen, onClose]);

  if (!isOpen) return null;

  return (
    <div
      ref={modalRef}
      role="dialog"
      aria-modal="true"
      aria-labelledby="modal-title"
    >
      {children}
    </div>
  );
}
```

## Keyboard Navigation

### Tab Order

Ensure logical tab order through proper HTML structure:

```tsx
// ✅ Good - Logical tab order
<form>
  <Input id="name" /> {/* Tab 1 */}
  <Input id="email" /> {/* Tab 2 */}
  <Button type="submit">Submit</Button> {/* Tab 3 */}
</form>
```

### Skip Links

Add skip links for keyboard users:

```tsx
// In AdminLayout
<a
  href="#main-content"
  className="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 focus:z-50 focus:px-4 focus:py-2 focus:bg-indigo-600 focus:text-white focus:rounded-lg"
>
  Skip to main content
</a>
```

## Screen Reader Support

### Status Messages

Use role="status" for dynamic status messages:

```tsx
// ✅ Good - Status message for screen readers
{isLoading && (
  <div role="status" aria-live="polite" className="sr-only">
    Loading data...
  </div>
)}
```

### Error Messages

Use role="alert" for error messages:

```tsx
// ✅ Good - Error alert
{error && (
  <div role="alert" className="text-red-600">
    {error}
  </div>
)}
```

### Live Regions

Use aria-live for dynamic content updates:

```tsx
// ✅ Good - Live region for search results
<div aria-live="polite" aria-atomic="true">
  {filteredResults.length} results found
</div>
```

## Testing Checklist

### Manual Testing

- [ ] All interactive elements are keyboard accessible
- [ ] Tab order is logical
- [ ] Focus indicators are visible
- [ ] Modals trap focus correctly
- [ ] Escape key closes modals
- [ ] Focus returns to trigger element after modal close

### Screen Reader Testing

- [ ] All images have alt text
- [ ] All form fields have labels
- [ ] Error messages are announced
- [ ] Status changes are announced
- [ ] Icon-only buttons have aria-labels

### Automated Testing

Run accessibility tests with axe-core:

```bash
npm run test:a11y
```

## Common Patterns

### Data Tables

```tsx
<table role="table" aria-label="Users table">
  <thead>
    <tr>
      <th scope="col">Name</th>
      <th scope="col">Email</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td>{user.name}</td>
      <td>{user.email}</td>
    </tr>
  </tbody>
</table>
```

### Pagination

```tsx
<nav aria-label="Pagination">
  <button
    aria-label="Go to previous page"
    disabled={currentPage === 1}
  >
    Previous
  </button>
  <button
    aria-label="Go to next page"
    disabled={currentPage === lastPage}
  >
    Next
  </button>
</nav>
```

### Breadcrumbs

```tsx
<nav aria-label="Breadcrumb">
  <ol>
    <li><Link href="/">Home</Link></li>
    <li><Link href="/admin">Admin</Link></li>
    <li aria-current="page">Users</li>
  </ol>
</nav>
```

## Resources

- [WCAG 2.1 Guidelines](https://www.w3.org/WAI/WCAG21/quickref/)
- [ARIA Authoring Practices](https://www.w3.org/WAI/ARIA/apg/)
- [Headless UI Accessibility](https://headlessui.com/react/dialog#accessibility-notes)
- [React Accessibility](https://react.dev/learn/accessibility)
