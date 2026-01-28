# Form Submission State Management

This document describes the form submission utilities and patterns for handling loading states, optimistic updates, and error handling in admin forms.

## Overview

The admin panel provides several utilities for managing form submission states:

1. **LoadingButton** - Button component with built-in loading state
2. **useFormSubmission** - Hook for comprehensive form submission management
3. **useAsyncAction** - Hook for simple async actions (delete, toggle, etc.)
4. **useOptimisticUpdate** - Hook for optimistic UI updates with rollback
5. **useOptimisticList** - Hook for optimistic list operations

## Requirements Satisfied

- **11.2**: Disable submit buttons during submission and show spinner
- **11.7**: Handle optimistic updates with rollback on failure

## LoadingButton Component

### Basic Usage

```tsx
import { LoadingButton } from '@/components/ui/loading-button';

function MyForm() {
  const [isSubmitting, setIsSubmitting] = useState(false);

  return (
    <form onSubmit={handleSubmit}>
      <LoadingButton
        type="submit"
        isLoading={isSubmitting}
        loadingText="Creating..."
      >
        Create User
      </LoadingButton>
    </form>
  );
}
```

### Props

- `isLoading` (boolean): Whether the button is in loading state
- `loadingText` (string, optional): Text to display when loading
- `loadingIcon` (ReactNode, optional): Custom loading icon
- `showLoadingIcon` (boolean, default: true): Whether to show the loading icon
- All standard Button props are supported

### Examples

```tsx
// Basic loading button
<LoadingButton isLoading={isSubmitting}>
  Submit
</LoadingButton>

// With custom loading text
<LoadingButton isLoading={isSubmitting} loadingText="Saving...">
  Save Changes
</LoadingButton>

// With custom loading icon
<LoadingButton 
  isLoading={isSubmitting} 
  loadingIcon={<Spinner />}
>
  Process
</LoadingButton>

// Different variants
<LoadingButton variant="destructive" isLoading={isDeleting}>
  Delete
</LoadingButton>
```

## useFormSubmission Hook

### Basic Usage

```tsx
import { useFormSubmission } from '@/hooks/useFormSubmission';

function UserForm() {
  const { isSubmitting, handleSubmit } = useFormSubmission({
    onSubmit: async (data) => {
      router.post(route('admin.users.store'), data);
    },
    successMessage: 'User created successfully',
    errorMessage: 'Failed to create user',
  });

  return (
    <form onSubmit={form.handleSubmit(handleSubmit)}>
      {/* Form fields */}
      <LoadingButton type="submit" isLoading={isSubmitting}>
        Create User
      </LoadingButton>
    </form>
  );
}
```

### Options

- `onSubmit` (function, required): The submission handler
- `onSuccess` (function, optional): Success callback
- `onError` (function, optional): Error callback
- `successMessage` (string, optional): Success toast message
- `errorMessage` (string, optional): Error toast message
- `showLoadingToast` (boolean, default: false): Show loading toast
- `loadingMessage` (string, default: 'Submitting...'): Loading toast message
- `resetOnSuccess` (boolean, default: false): Reset form on success
- `onReset` (function, optional): Form reset handler

### Return Values

- `isSubmitting` (boolean): Whether form is currently submitting
- `error` (string | null): Current error message
- `handleSubmit` (function): Submit handler to call with form data
- `reset` (function): Reset submission state

### Advanced Example

```tsx
const { isSubmitting, error, handleSubmit, reset } = useFormSubmission({
  onSubmit: async (data) => {
    // Perform submission
    await api.createUser(data);
  },
  onSuccess: () => {
    // Navigate to list page
    router.visit(route('admin.users.index'));
  },
  onError: (err) => {
    // Log error
    console.error('Submission failed:', err);
  },
  successMessage: 'User created successfully',
  errorMessage: 'Failed to create user',
  showLoadingToast: true,
  loadingMessage: 'Creating user...',
  resetOnSuccess: true,
  onReset: () => {
    form.reset();
  },
});
```

## useAsyncAction Hook

### Basic Usage

```tsx
import { useAsyncAction } from '@/hooks/useFormSubmission';

function UserList() {
  const { execute: deleteUser, isLoading } = useAsyncAction({
    action: () => {
      router.delete(route('admin.users.destroy', userId));
    },
    successMessage: 'User deleted successfully',
    confirmMessage: 'Are you sure you want to delete this user?',
  });

  return (
    <LoadingButton
      variant="destructive"
      onClick={deleteUser}
      isLoading={isLoading}
    >
      Delete
    </LoadingButton>
  );
}
```

### Options

- `action` (function, required): The async action to perform
- `onSuccess` (function, optional): Success callback
- `onError` (function, optional): Error callback
- `successMessage` (string, optional): Success toast message
- `errorMessage` (string, optional): Error toast message
- `confirmMessage` (string, optional): Confirmation dialog message

### Return Values

- `execute` (function): Function to execute the action
- `isLoading` (boolean): Whether action is currently executing

## useOptimisticUpdate Hook

### Basic Usage

```tsx
import { useOptimisticUpdate } from '@/hooks/useOptimisticUpdate';

function BusinessUnitCard({ businessUnit }) {
  const { optimisticUpdate } = useOptimisticUpdate();

  const handleToggleStatus = () => {
    optimisticUpdate({
      optimisticData: {
        ...businessUnit,
        is_active: !businessUnit.is_active,
      },
      request: () => {
        router.post(route('admin.business-units.toggle-status', businessUnit.id));
      },
      onRollback: () => {
        console.log('Status toggle failed, rolled back');
      },
    });
  };

  return (
    <Card>
      <h3>{businessUnit.name}</h3>
      <Badge variant={businessUnit.is_active ? 'success' : 'secondary'}>
        {businessUnit.is_active ? 'Active' : 'Inactive'}
      </Badge>
      <Button onClick={handleToggleStatus}>
        Toggle Status
      </Button>
    </Card>
  );
}
```

### Options

- `optimisticData` (T, required): The optimistic data to display immediately
- `request` (function, required): The server request to perform
- `previousData` (T, optional): Previous data to rollback to
- `onRollback` (function, optional): Custom rollback handler
- `onSuccess` (function, optional): Success handler
- `onError` (function, optional): Error handler
- `showRollbackToast` (boolean, default: true): Show toast on rollback

### Return Values

- `optimisticUpdate` (function): Function to perform optimistic update
- `rollback` (function): Function to manually rollback
- `isOptimistic` (boolean): Whether currently in optimistic state
- `optimisticState` (T | null): Current optimistic state

## useOptimisticList Hook

### Basic Usage

```tsx
import { useOptimisticList } from '@/hooks/useOptimisticUpdate';

function CategoryList({ initialCategories }) {
  const { items, addItem, updateItem, deleteItem } = useOptimisticList(initialCategories);

  const handleCreate = (newCategory) => {
    addItem({
      item: { id: Date.now(), ...newCategory },
      request: () => {
        router.post(route('admin.pr-categories.store'), newCategory);
      },
    });
  };

  const handleUpdate = (id, updates) => {
    updateItem({
      id,
      updates,
      request: () => {
        router.put(route('admin.pr-categories.update', id), updates);
      },
    });
  };

  const handleDelete = (id) => {
    deleteItem({
      id,
      request: () => {
        router.delete(route('admin.pr-categories.destroy', id));
      },
    });
  };

  return (
    <div>
      {items.map(item => (
        <CategoryCard
          key={item.id}
          category={item}
          onUpdate={handleUpdate}
          onDelete={handleDelete}
        />
      ))}
    </div>
  );
}
```

### Return Values

- `items` (T[]): Current list of items
- `setItems` (function): Function to manually set items
- `addItem` (function): Function to optimistically add item
- `updateItem` (function): Function to optimistically update item
- `deleteItem` (function): Function to optimistically delete item

## Pattern Examples

### Form with Loading State

```tsx
function UserCreateForm() {
  const [isSubmitting, setIsSubmitting] = useState(false);
  const { register, handleSubmit, formState: { errors } } = useForm();

  const onSubmit = (data) => {
    setIsSubmitting(true);
    
    router.post(route('admin.users.store'), data, {
      onSuccess: () => {
        toast.success('User created successfully');
        setIsSubmitting(false);
      },
      onError: () => {
        toast.error('Failed to create user');
        setIsSubmitting(false);
      },
    });
  };

  return (
    <form onSubmit={handleSubmit(onSubmit)}>
      <Input {...register('name')} error={errors.name?.message} />
      <Input {...register('email')} error={errors.email?.message} />
      
      <LoadingButton type="submit" isLoading={isSubmitting}>
        Create User
      </LoadingButton>
    </form>
  );
}
```

### Delete Action with Confirmation

```tsx
function UserRow({ user }) {
  const { execute: deleteUser, isLoading } = useAsyncAction({
    action: () => {
      router.delete(route('admin.users.destroy', user.id));
    },
    successMessage: `User "${user.name}" deleted successfully`,
    errorMessage: 'Failed to delete user',
    confirmMessage: `Are you sure you want to delete "${user.name}"?`,
  });

  return (
    <tr>
      <td>{user.name}</td>
      <td>{user.email}</td>
      <td>
        <LoadingButton
          variant="destructive"
          size="sm"
          onClick={deleteUser}
          isLoading={isLoading}
        >
          Delete
        </LoadingButton>
      </td>
    </tr>
  );
}
```

### Optimistic Toggle

```tsx
function StatusToggle({ item }) {
  const { optimisticUpdate, isOptimistic } = useOptimisticUpdate();

  const handleToggle = () => {
    optimisticUpdate({
      optimisticData: { ...item, is_active: !item.is_active },
      request: () => {
        router.post(route('toggle-status', item.id));
      },
    });
  };

  return (
    <Button
      onClick={handleToggle}
      disabled={isOptimistic}
      variant={item.is_active ? 'default' : 'outline'}
    >
      {item.is_active ? 'Active' : 'Inactive'}
    </Button>
  );
}
```

## Best Practices

1. **Always disable buttons during submission** to prevent double submissions
2. **Show loading indicators** for operations that take more than 200ms
3. **Use optimistic updates** for instant feedback on toggle/status changes
4. **Provide clear error messages** when operations fail
5. **Use confirmation dialogs** for destructive actions
6. **Reset forms** after successful submission when appropriate
7. **Handle rollback gracefully** with clear user feedback

## Testing

See `TESTING.md` for property-based tests covering:
- Form submission state (Property 42)
- Optimistic updates (Property 46)
- Loading states (Property 2)
