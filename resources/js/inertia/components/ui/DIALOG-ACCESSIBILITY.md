# Dialog Component Accessibility

## Overview

The Dialog component uses Headless UI's Dialog component which provides built-in accessibility features compliant with WAI-ARIA Dialog (Modal) Pattern.

## Automatic Accessibility Features

### 1. Focus Management

**Focus Trapping:**
- When a dialog opens, focus is automatically moved to the first focusable element inside the dialog
- Focus is trapped within the dialog - Tab and Shift+Tab cycle through focusable elements within the modal only
- Users cannot tab to elements outside the dialog while it's open

**Focus Return:**
- When the dialog closes, focus automatically returns to the element that triggered the dialog
- This ensures keyboard users don't lose their place in the page

### 2. Keyboard Support

**Escape Key:**
- Pressing Escape automatically closes the dialog
- This is handled by Headless UI's `onClose` callback

**Tab Navigation:**
- Tab and Shift+Tab navigate through focusable elements within the dialog
- Focus wraps from last to first element and vice versa

### 3. ARIA Attributes

Headless UI automatically adds:
- `role="dialog"` to the dialog element
- `aria-modal="true"` to indicate the dialog is modal
- `aria-labelledby` linking to the DialogTitle
- `aria-describedby` linking to the DialogDescription (if present)

### 4. Backdrop Interaction

- Clicking the backdrop (overlay) closes the dialog
- This is configurable via the `onClose` prop

## Usage Example

```tsx
import { Dialog, DialogHeader, DialogTitle, DialogDescription, DialogContent, DialogFooter } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';

function MyComponent() {
  const [isOpen, setIsOpen] = useState(false);

  return (
    <>
      <Button onClick={() => setIsOpen(true)}>
        Open Dialog
      </Button>

      <Dialog open={isOpen} onClose={() => setIsOpen(false)}>
        <DialogHeader onClose={() => setIsOpen(false)}>
          <DialogTitle>Confirm Action</DialogTitle>
          <DialogDescription>
            Are you sure you want to proceed with this action?
          </DialogDescription>
        </DialogHeader>
        
        <DialogContent>
          <p>This action cannot be undone.</p>
        </DialogContent>
        
        <DialogFooter>
          <Button variant="outline" onClick={() => setIsOpen(false)}>
            Cancel
          </Button>
          <Button onClick={handleConfirm}>
            Confirm
          </Button>
        </DialogFooter>
      </Dialog>
    </>
  );
}
```

## Accessibility Testing

### Manual Testing Checklist

- [ ] **Focus moves to dialog on open**
  - Open the dialog
  - Verify focus is on the first focusable element (usually close button or first input)

- [ ] **Focus is trapped within dialog**
  - Press Tab repeatedly
  - Verify focus cycles through elements within the dialog only
  - Verify focus wraps from last to first element

- [ ] **Escape key closes dialog**
  - Open the dialog
  - Press Escape
  - Verify dialog closes

- [ ] **Focus returns on close**
  - Open dialog from a button
  - Close dialog (via Escape, backdrop click, or close button)
  - Verify focus returns to the button that opened the dialog

- [ ] **Backdrop click closes dialog**
  - Open the dialog
  - Click outside the dialog (on the backdrop)
  - Verify dialog closes

### Screen Reader Testing

- [ ] **Dialog is announced**
  - Open dialog with screen reader active
  - Verify screen reader announces "dialog" or "modal dialog"

- [ ] **Title is read**
  - Verify screen reader reads the DialogTitle

- [ ] **Description is read**
  - Verify screen reader reads the DialogDescription (if present)

### Automated Testing

```tsx
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { Dialog, DialogTitle } from '@/components/ui/dialog';

describe('Dialog Accessibility', () => {
  it('traps focus within dialog', async () => {
    const user = userEvent.setup();
    const onClose = vi.fn();
    
    render(
      <Dialog open={true} onClose={onClose}>
        <DialogTitle>Test Dialog</DialogTitle>
        <button>First Button</button>
        <button>Second Button</button>
      </Dialog>
    );

    const firstButton = screen.getByText('First Button');
    const secondButton = screen.getByText('Second Button');

    // Focus should be on first button
    expect(firstButton).toHaveFocus();

    // Tab to second button
    await user.tab();
    expect(secondButton).toHaveFocus();

    // Tab should wrap back to first button
    await user.tab();
    expect(firstButton).toHaveFocus();
  });

  it('closes on Escape key', async () => {
    const user = userEvent.setup();
    const onClose = vi.fn();
    
    render(
      <Dialog open={true} onClose={onClose}>
        <DialogTitle>Test Dialog</DialogTitle>
      </Dialog>
    );

    await user.keyboard('{Escape}');
    expect(onClose).toHaveBeenCalled();
  });

  it('returns focus to trigger element', async () => {
    const user = userEvent.setup();
    const { rerender } = render(
      <>
        <button>Open Dialog</button>
        <Dialog open={false} onClose={() => {}}>
          <DialogTitle>Test Dialog</DialogTitle>
        </Dialog>
      </>
    );

    const openButton = screen.getByText('Open Dialog');
    openButton.focus();
    expect(openButton).toHaveFocus();

    // Open dialog
    rerender(
      <>
        <button>Open Dialog</button>
        <Dialog open={true} onClose={() => {}}>
          <DialogTitle>Test Dialog</DialogTitle>
        </Dialog>
      </>
    );

    // Focus should move to dialog
    expect(openButton).not.toHaveFocus();

    // Close dialog
    rerender(
      <>
        <button>Open Dialog</button>
        <Dialog open={false} onClose={() => {}}>
          <DialogTitle>Test Dialog</DialogTitle>
        </Dialog>
      </>
    );

    // Focus should return to button
    expect(openButton).toHaveFocus();
  });
});
```

## ConfirmDialog Component

The `ConfirmDialog` component is a pre-built confirmation dialog that inherits all accessibility features from the base Dialog component:

```tsx
<ConfirmDialog
  open={isOpen}
  onClose={() => setIsOpen(false)}
  onConfirm={handleConfirm}
  title="Delete User"
  description="Are you sure you want to delete this user? This action cannot be undone."
  confirmText="Delete"
  cancelText="Cancel"
  variant="danger"
  loading={isDeleting}
/>
```

## Best Practices

### 1. Always Provide a Title

```tsx
// ✅ Good - Has title
<Dialog open={isOpen} onClose={onClose}>
  <DialogTitle>Confirm Action</DialogTitle>
  {/* content */}
</Dialog>

// ❌ Bad - No title
<Dialog open={isOpen} onClose={onClose}>
  {/* content without title */}
</Dialog>
```

### 2. Use DialogDescription for Context

```tsx
// ✅ Good - Provides context
<Dialog open={isOpen} onClose={onClose}>
  <DialogTitle>Delete User</DialogTitle>
  <DialogDescription>
    This will permanently delete the user and all associated data.
  </DialogDescription>
  {/* content */}
</Dialog>
```

### 3. Provide Clear Action Buttons

```tsx
// ✅ Good - Clear action buttons
<DialogFooter>
  <Button variant="outline" onClick={onClose}>
    Cancel
  </Button>
  <Button variant="destructive" onClick={onConfirm}>
    Delete User
  </Button>
</DialogFooter>

// ❌ Bad - Unclear buttons
<DialogFooter>
  <Button onClick={onClose}>No</Button>
  <Button onClick={onConfirm}>Yes</Button>
</DialogFooter>
```

### 4. Handle Loading States

```tsx
// ✅ Good - Disables buttons during loading
<DialogFooter>
  <Button variant="outline" onClick={onClose} disabled={isLoading}>
    Cancel
  </Button>
  <Button onClick={onConfirm} loading={isLoading}>
    Confirm
  </Button>
</DialogFooter>
```

## Common Issues and Solutions

### Issue: Focus Not Returning

**Problem:** Focus doesn't return to trigger element after closing.

**Solution:** Ensure the trigger element still exists in the DOM when the dialog closes. If the trigger is conditionally rendered, use a ref to maintain focus.

### Issue: Multiple Dialogs

**Problem:** Opening a dialog from within another dialog.

**Solution:** Headless UI supports nested dialogs. Each dialog maintains its own focus trap. Close dialogs in reverse order (last opened, first closed).

### Issue: Form Inputs Not Focusable

**Problem:** Form inputs inside dialog don't receive focus.

**Solution:** Ensure inputs don't have `tabindex="-1"`. Headless UI will automatically focus the first focusable element.

## Resources

- [Headless UI Dialog Documentation](https://headlessui.com/react/dialog)
- [WAI-ARIA Dialog Pattern](https://www.w3.org/WAI/ARIA/apg/patterns/dialog-modal/)
- [WCAG 2.1 Focus Management](https://www.w3.org/WAI/WCAG21/Understanding/focus-order.html)
