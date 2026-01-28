---
inclusion: always
---

# Project Structure & Conventions

## Architecture

**Multi-Business Unit Architecture**: Session-based context switching via `current_business_unit_id`. All queries MUST filter by active business unit.

## Frontend Stack (React/Inertia - Primary)

New features MUST use React/Inertia. Livewire is legacy and only maintained for existing Purchasing module views.

| Path | Purpose |
|------|---------|
| `resources/js/inertia/Pages/` | React page components |
| `resources/js/inertia/components/` | Reusable React components |
| `resources/js/inertia/layouts/` | Layout components (AdminLayout, AppLayout) |
| `resources/js/inertia/hooks/` | Custom React hooks |
| `resources/js/inertia/types/` | TypeScript type definitions |

### React Component Patterns

```tsx
// Page component pattern
import { Head } from '@inertiajs/react';
import { AdminLayout } from '@/layouts/AdminLayout';

interface Props {
    data: SomeType[];
}

export default function Index({ data }: Props) {
    return (
        <AdminLayout>
            <Head title="Page Title" />
            {/* content */}
        </AdminLayout>
    );
}
```

### Key React Components
- `DataTable` - Server-side paginated tables with search/filter
- `StatCard` - Dashboard statistics cards
- `FileUpload` - File upload with progress
- `ColorPicker` - Color selection input
- See `resources/js/inertia/components/admin/README.md` for full docs

## Backend Structure

| Path | Purpose |
|------|---------|
| `app/Http/Controllers/` | Inertia controllers returning `Inertia::render()` |
| `app/Models/Core/` | Core models (User, BusinessUnit, Department) |
| `app/Models/Modules/` | Module models organized by `[Module]/[SubModule]/` |
| `app/Services/Modules/` | Business logic services |
| `database/migrations/modules/` | Module-specific migrations |

## Naming Conventions

| Type | Pattern | Example |
|------|---------|---------|
| Models | `App\Models\Modules\[Module]\[SubModule]\[Name]` | `PurchaseRequest\PrItem` |
| Controllers | `App\Http\Controllers\[Context]\[Name]Controller` | `Admin\UserController` |
| Services | `App\Services\Modules\[Module]\[Name]Service` | `ApprovalWorkflowService` |
| React Pages | `Pages/[Module]/[Resource]/[Action].tsx` | `Admin/Users/Index.tsx` |
| Tables | snake_case, plural | `purchase_requests`, `pr_items` |

## Business Unit Context

| Session Key | Purpose |
|-------------|---------|
| `current_business_unit_id` | Active business unit ID (required for queries) |
| `current_business_unit_name` | Display name |
| `current_business_unit_code` | Short code |

### React Hook for Business Unit
```tsx
import { useBusinessUnit } from '@/hooks/useBusinessUnit';

const { currentBusinessUnit, isSwitching } = useBusinessUnit(['data-key']);
```

## Authorization

- **Super Admin**: `$user->isSuperAdmin()` bypasses all checks
- **Gates**: `view-reports` for top management (GM, Director, CEO, Finance Manager)
- **Permissions**: Spatie Permission package, check via `$user->can('permission-name')`

## Legacy (Livewire) - Maintenance Only

⚠️ DO NOT create new Livewire components. Only maintain existing:
- Purchasing module (PR/ST create, list, approvals)
- Dashboard widgets
- Business unit switcher

| Legacy Path | Status |
|-------------|--------|
| `app/Livewire/Modules/Purchasing/` | Maintenance only |
| `resources/views/livewire/` | Maintenance only |

## Key Files Reference

- **Routes**: `routes/web.php`
- **Gates**: `app/Providers/AppServiceProvider.php`
- **Index Standards**: `database/migrations/README-INDEX-STANDARDS.md`
- **React Components**: `resources/js/inertia/components/admin/README.md`
