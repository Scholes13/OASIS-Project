# Design Document: Livewire Full Migration

## Overview

This design document outlines the complete migration of ALL Livewire components from the Oasis application to React/Inertia. The goal is zero Livewire dependencies - full React/Inertia frontend.

**Migration Scope:**
- Remove unused components
- Migrate components with existing React replacements
- Create new React pages for all remaining components
- Remove Livewire package entirely

## Architecture

### Decision Tree Flow

```
LIVEWIRE COMPONENT
        |
        v
  Check References (routes, views, PHP imports)
        |
   +----+----+
   |         |
   v         v
NO REFS    HAS REFS
(Unused)   (Active)
   |         |
   v         v
DELETE    Check React?
           |
      +----+----+
      |         |
      v         v
   EXISTS    NO REACT
  (Duplicate) (Create New)
      |         |
      v         v
  MIGRATE    CREATE REACT
  ROUTE      PAGE & MIGRATE
      |         |
      v         v
  DELETE     DELETE
  LIVEWIRE   LIVEWIRE
```

### Complete Component Inventory

| Category | Components | Action |
|----------|------------|--------|
| **UNUSED** (8) | Sidebar, UserMenu, BusinessUnitSwitcher, LoginForm, Logout, UserDashboard, NumberingStats, DepartmentPurchasingConfig | DELETE |
| **ACTIVITY** (2) | BackdateRequests, BackdateApprovals | CREATE REACT → DELETE |
| **DOCS** (1) | DocsHelp | CREATE REACT → DELETE |
| **SALES CRM** (4) | ActivityIndex, ActivityForm, ContactIndex, ContactForm | CREATE REACT → DELETE |
| **PURCHASING PR** (4) | Create, MyPurchaseRequests, AllRequests, ApprovalsIndex | CREATE REACT → DELETE |
| **PURCHASING ST** (2) | Create, MyStockRequests | CREATE REACT → DELETE |
| **PURCHASING ADMIN** (9) | AdminDashboard, TaskList, TaskDetail, PersonalTaskHistory, ManagementHistory, AuditHistory, DepartmentAuditHistory, DepartmentReport, ConsolidatedReport | CREATE REACT → DELETE |
| **PURCHASING** (1) | AllRequests | CREATE REACT → DELETE |
| **TRAITS** (2) | HasFilters, HasLazyLoading | DELETE (after all components) |

**Total: 33 Livewire files to migrate/remove**

### State Management & Shared Data

When migrating from Livewire to Inertia, ensure proper data sharing:

| Livewire Pattern | Inertia Equivalent |
|------------------|-------------------|
| PHP shared data in Blade | `HandleInertiaRequests.php` shared props |
| `$this->dispatch()` events | Flash messages + React Toast |
| `wire:model` binding | `useForm` hook from Inertia |
| Livewire `rules()` validation | Controller validation + `useForm` errors |
| `$this->emit()` | Inertia events or React state |

### Flash Messages & Toast Integration

```tsx
// Pattern for handling Laravel flash messages in React
// Already implemented in app.tsx or layout component
const { flash } = usePage().props;

useEffect(() => {
    if (flash.success) toast.success(flash.success);
    if (flash.error) toast.error(flash.error);
}, [flash]);
```

### Form Handling with useForm

```tsx
// Standard pattern for migrating Livewire forms
import { useForm } from '@inertiajs/react';

const { data, setData, post, processing, errors } = useForm({
    field: '',
});

const submit = (e) => {
    e.preventDefault();
    post(route('resource.store'));
};
```



### Existing React Components to Reuse

| Component | Location | Use For |
|-----------|----------|---------|
| `DataTable` | `components/admin/DataTable.tsx` | All list views with pagination |
| `StatCard` | `components/admin/StatCard.tsx` | Dashboard statistics |
| `FileUpload` | `components/admin/FileUpload.tsx` | Document uploads |
| `ColorPicker` | `components/admin/ColorPicker.tsx` | Color selections |
| `Badge` | `components/ui/Badge.tsx` | Status display |
| `Dialog` | `components/ui/dialog.tsx` | Modals |
| `Button` | `components/ui/button.tsx` | Actions |
| `Input`, `Select`, `Textarea` | `components/ui/` | Form elements |
| `PurchaseRequestForm` | `components/purchasing/` | PR form (existing) |
| `PurchaseRequestTable` | `components/purchasing/` | PR list (existing) |
| `ApprovalTimeline` | `components/purchasing/` | Approval flow |

### Existing React Pages (Already Migrated)

| Page | Location | Status |
|------|----------|--------|
| Login | `Pages/Auth/Login.tsx` | ✅ Done |
| Dashboard | `Pages/Dashboard.tsx` | ✅ Done |
| Profile | `Pages/Profile/Index.tsx` | ✅ Done |
| Activity Dashboard | `Pages/Activity/Dashboard.tsx` | ✅ Done |
| Activity Tasks | `Pages/Activity/DepartmentTasks.tsx` | ✅ Done |
| Activity Form | `Pages/Activity/TaskForm.tsx` | ✅ Done |
| PR Index | `Pages/Purchasing/PurchaseRequest/Index.tsx` | ✅ Done |
| PR Create | `Pages/Purchasing/PurchaseRequest/Create.tsx` | ✅ Done |
| PR Show | `Pages/Purchasing/PurchaseRequest/Show.tsx` | ✅ Done |
| PR All | `Pages/Purchasing/PurchaseRequest/All.tsx` | ✅ Done |
| PR Approvals | `Pages/Purchasing/PurchaseRequest/Approvals.tsx` | ✅ Done |
| ST Index | `Pages/Purchasing/StockRequest/Index.tsx` | ✅ Done |
| Purchasing Admin Dashboard | `Pages/PurchasingAdmin/Dashboard.tsx` | ✅ Done |
| Purchasing Admin Tasks | `Pages/PurchasingAdmin/Tasks.tsx` | ✅ Done |
| Purchasing Admin Task Detail | `Pages/PurchasingAdmin/TaskDetail.tsx` | ✅ Done |
| Purchasing Admin History | `Pages/PurchasingAdmin/TaskHistory.tsx` | ✅ Done |
| Admin Dashboard | `Pages/Admin/Dashboard.tsx` | ✅ Done |
| Admin Users | `Pages/Admin/Users/` | ✅ Done |
| Admin Business Units | `Pages/Admin/BusinessUnits/` | ✅ Done |
| Admin Departments | `Pages/Admin/Departments/` | ✅ Done |

## Components and Interfaces

### Phase 1: Unused Component Removal (8 files)

Delete directly - no React needed:

| Component | View |
|-----------|------|
| `Layout/Sidebar.php` | `livewire/layout/sidebar.blade.php` |
| `Layout/UserMenu.php` | `livewire/layout/user-menu.blade.php` |
| `Components/BusinessUnitSwitcher.php` | `livewire/components/business-unit-switcher.blade.php` |
| `Forms/LoginForm.php` | `livewire/pages/auth/login.blade.php` |
| `Actions/Logout.php` | N/A |
| `Dashboard/UserDashboard.php` | `livewire/dashboard/user-dashboard.blade.php` |
| `Dashboard/NumberingStats.php` | `livewire/dashboard/numbering-stats.blade.php` |
| `Admin/DepartmentPurchasingConfig.php` | N/A |

### Phase 2: Activity Backdate Migration (2 files)

| Livewire | Route | New React Page |
|----------|-------|----------------|
| `BackdateRequests.php` | `/activity/backdate/requests` | `Pages/Activity/Backdate/Requests.tsx` |
| `BackdateApprovals.php` | `/activity/backdate/approvals` | `Pages/Activity/Backdate/Approvals.tsx` |

### Phase 3: DocsHelp Migration (1 file)

| Livewire | Route | New React Page |
|----------|-------|----------------|
| `DocsHelp.php` | `/docs-help` | `Pages/DocsHelp/Index.tsx` |

### Phase 4: Sales CRM Migration (4 files)

| Livewire | Route | New React Page |
|----------|-------|----------------|
| `ActivityIndex.php` | `/sales-crm/activities` | `Pages/SalesCrm/Activities/Index.tsx` |
| `ActivityForm.php` | `/sales-crm/activities/create` | `Pages/SalesCrm/Activities/Form.tsx` |
| `ContactIndex.php` | `/sales-crm/contacts` | `Pages/SalesCrm/Contacts/Index.tsx` |
| `ContactForm.php` | `/sales-crm/contacts/create` | `Pages/SalesCrm/Contacts/Form.tsx` |

### Phase 5: Purchasing PR Migration (4 files)

Check if routes already use React - if yes, just delete Livewire:

| Livewire | Route | React Status |
|----------|-------|--------------|
| `Create.php` | `/purchase-requests/create` | ✅ React exists |
| `MyPurchaseRequests.php` | `/purchase-requests` | ✅ React exists |
| `AllRequests.php` | `/purchase-requests/all/list` | ✅ React exists |
| `ApprovalsIndex.php` | `/approvals` | ✅ React exists |

### Phase 6: Purchasing ST Migration (2 files)

**⚠️ PRIORITY CHECK REQUIRED**: Verify ST Create route before execution phase.

| Livewire | Route | React Status |
|----------|-------|--------------|
| `Create.php` | `/stock-requests/create` | **CHECK FIRST** |
| `MyStockRequests.php` | `/stock-requests` | ✅ React exists |

### Phase 7: Purchasing Admin Migration (9 files)

Check if routes already use React:

| Livewire | Route | React Status |
|----------|-------|--------------|
| `AdminDashboard.php` | `/purchasing/admin/dashboard` | ✅ React exists |
| `TaskList.php` | `/purchasing/admin/tasks` | ✅ React exists |
| `TaskDetail.php` | `/purchasing/admin/tasks/{id}` | ✅ React exists |
| `PersonalTaskHistory.php` | `/purchasing/admin/personal-task-history` | ✅ React exists |
| `ManagementHistory.php` | `/purchasing/admin/management-history` | Need React |
| `AuditHistory.php` | `/purchasing/admin/audit-history` | Need React |
| `DepartmentAuditHistory.php` | `/purchasing/admin/department-audit-history` | Need React |
| `DepartmentReport.php` | `/purchasing/admin/department-report` | Need React |
| `ConsolidatedReport.php` | `/purchasing/admin/consolidated-report` | Need React |

### Phase 8: Livewire Package Removal

After all components migrated:
1. Remove `livewire/livewire` from `composer.json`
2. Remove `config/livewire.php`
3. Remove `app/Livewire/` directory
4. Remove `resources/views/livewire/` directory
5. Remove Livewire service provider
6. Remove `app/Livewire/Traits/` directory
7. Remove `@livewireStyles` and `@livewireScripts` from Blade layouts
8. Check `app/Http/Kernel.php` or `bootstrap/app.php` for Livewire middleware
9. Remove `stubs/livewire.*.stub` files
10. Clear all caches: `php artisan optimize:clear`

## Technical Addendum

### Middleware Cleanup Checklist

Check these files for Livewire references:
- `app/Http/Kernel.php` (Laravel 10 and below)
- `bootstrap/app.php` (Laravel 11+)
- `app/Providers/AppServiceProvider.php`

### Asset Cleanup

Remove from `resources/views/layouts/app.blade.php`:
```blade
{{-- REMOVE THESE --}}
@livewireStyles
@livewireScripts
```

### Route Cache Management

After each route migration:
```bash
php artisan route:clear
php artisan route:cache  # Only in production
```

### Ziggy Route Verification

After route changes, verify frontend routes:
```bash
php artisan ziggy:generate
```

### Final Route Audit

For Phase 5 & 7 (components marked "React exists"):
- Verify no `wire:key` or Livewire-specific query params
- Check controller returns `Inertia::render()` not Livewire component
- Verify all route parameters are passed correctly

## Data Models

No database changes required. Only file operations:
- Delete PHP classes in `app/Livewire/`
- Delete Blade views in `resources/views/livewire/`
- Update routes in `routes/web.php`
- Create React pages in `resources/js/inertia/Pages/`
- Update `composer.json`

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system.*

### Property 1: View-Component Consistency
*For any* removed Livewire PHP class, its corresponding Blade view must also be removed.
**Validates: Requirements 2.3, 3.5, 4.7**

### Property 2: React Replacement Before Removal
*For any* active Livewire component, a React replacement must exist and be verified before the Livewire component is removed.
**Validates: Requirements 3.1, 4.1**

### Property 3: URL Backward Compatibility
*For any* migrated route, the URL path must remain identical.
**Validates: Requirements 7.3**

### Property 4: Complete Migration
*For any* Livewire component in the codebase, it must either be removed (if unused) or have a React replacement created.
**Validates: Requirements 4.1, 4.6, 4.7**

### Property 5: Package Removal Completeness
*For any* Livewire-related file or configuration, it must be removed after all components are migrated.
**Validates: Requirements 9.1, 9.2, 9.3, 9.4, 9.5**

## Error Handling

### Rollback Strategy
- Git-based rollback per phase
- Document removed files for restoration
- Document original routes before modification

### Laravel Boost MCP Tools for Debugging

| Tool | When to Use |
|------|-------------|
| `mcp_laravel_boost_last_error` | Check last backend error |
| `mcp_laravel_boost_read_log_entries` | Read Laravel logs |
| `mcp_laravel_boost_browser_logs` | Check frontend errors |
| `mcp_laravel_boost_list_routes` | Verify route configuration - **use after each route migration** |
| `mcp_laravel_boost_tinker` | Test controller queries before sending to Inertia props |
| `mcp_laravel_boost_database_query` | Verify data queries |

### MCP Debugging Workflow

```
1. Route Error?
   → mcp_laravel_boost_list_routes (filter by path)
   → Verify Livewire route replaced by Controller route

2. Data Not Showing in React?
   → mcp_laravel_boost_tinker (test query)
   → Check controller returns correct Inertia props

3. 404/Method Not Allowed?
   → php artisan route:clear
   → mcp_laravel_boost_list_routes

4. Frontend Error?
   → mcp_laravel_boost_browser_logs
   → Check React component receives props
```

## Testing Strategy

### Verification Checklist
- [ ] All tests pass
- [ ] Login flow works
- [ ] Dashboard loads
- [ ] All navigation works
- [ ] All CRUD operations work
- [ ] No Livewire references remain

### Manual Verification per Phase
1. Clear caches: `php artisan cache:clear && php artisan view:clear`
2. Test affected routes
3. Check browser console for errors
4. Check Laravel logs

## Implementation Phases

1. **Phase 1**: Remove 8 unused components
2. **Phase 2**: Migrate Activity Backdate (2 components)
3. **Phase 3**: Migrate DocsHelp (1 component)
4. **Phase 4**: Migrate Sales CRM (4 components)
5. **Phase 5**: Clean up Purchasing PR Livewire (4 components - React exists)
6. **Phase 6**: Migrate/Clean Purchasing ST (2 components)
7. **Phase 7**: Migrate/Clean Purchasing Admin (9 components)
8. **Phase 8**: Remove Livewire package & cleanup
