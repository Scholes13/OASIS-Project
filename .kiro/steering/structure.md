---
inclusion: always
---

# Project Structure & Conventions

## Architecture

**Multi-Business Unit Architecture**: Session-based context switching via `current_business_unit_id`. All queries MUST filter by active business unit.

## Frontend Stack (React/Inertia)

All features use React/Inertia with TypeScript.

| Path | Purpose |
|------|---------|
| `resources/js/inertia/Pages/` | React page components |
| `resources/js/inertia/components/` | Reusable React components |
| `resources/js/inertia/layouts/` | Layout components (AdminLayout, AppLayout) |
| `resources/js/inertia/hooks/` | Custom React hooks |
| `resources/js/inertia/types/` | TypeScript type definitions |

### React Page Structure
```
Pages/
  Activity/           # Employee task tracking
    Admin/            # Activity admin dashboard, department detail
    Analytics/        # Personal & department analytics
    Backdate/         # Backdate requests & approvals
  Admin/              # Core admin (users, departments, BUs, activity types, SLA, etc.)
  Auth/               # Login
  CashflowProjection/ # Cashflow forecasting (Index, Entries, Settings)
  DocsHelp/           # In-app documentation
  Purchasing/         # PR & ST CRUD, approvals
    PurchaseRequest/  # PR Index, Create, Show, Form, All, Approvals
    StockRequest/     # ST Index, Create, Show, Form
    StockApproval/    # ST approval Index, Show
  PurchasingAdmin/    # Admin task board, reports, history, audit
  SalesCrm/           # CRM contacts & activities
    Activities/       # Activity Index, Form
    Contacts/         # Contact Index, Form
  Profile/            # User profile
  Dashboard.tsx       # Main dashboard
```

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
- `ChartCard`, `LazyChart` - Chart wrappers with lazy loading
- `ErrorBoundary` - Error boundary wrapper
- See `resources/js/inertia/components/admin/README.md` for full docs

### UI Component Library (`components/ui/`)
- `Badge`, `Button`, `Card` - Basic UI primitives
- `Dialog`, `ConfirmDialog` - Modal dialogs
- `DatePicker`, `Select`, `Input`, `Textarea`, `Label` - Form controls
- `DataTable` (ui version), `EmptyState`, `Skeleton` - Data display
- `Toast` - Notification toasts
- `CommandPalette` - Keyboard-driven command palette
- `LoadingSpinner`, `FullScreenLoader`, `LoadingButton` - Loading states
- `LazyImage` - Lazy-loaded images

### Chart Components (`components/charts/`)
- `BarChart`, `LineChart`, `PieChart` - Chart types
- `StatsComponents` - Statistical display components

### Module-Specific Components
- `components/activity/` - TaskBoard, KanbanBoard, ActivityCalendar, TaskCard, TaskFormModal, etc.
- `components/purchasing/` - PurchaseRequestForm, PRItemsTable, ApprovalTimeline, StockRequestForm
- `components/purchasing-admin/` - PurchasingTaskBoard, TaskCard, TaskCalendar, CompleteTaskModal
- `components/layout/` - Navbar, Sidebar, BusinessUnitSwitcher, DepartmentSwitcher, UserMenu

### Custom Hooks (`hooks/`)
- `useBusinessUnit` - Business unit context & switching
- `useFilters` - URL-based filter state management
- `useFormSubmission` - Form submission with Inertia
- `useFileUpload` - File upload with progress tracking
- `usePrefetch` - Data prefetching
- `useOptimisticUpdate` - Optimistic UI updates
- `useKeyboardShortcuts` - Keyboard shortcut bindings
- `useFullScreenLoader` - Full-screen loading overlay

### TypeScript Types (`types/`)
- `types/index.ts` - Core shared types
- `types/admin.ts` - Admin module types
- `types/purchasing.ts` - Purchasing module types

## Backend Structure

| Path | Purpose |
|------|---------|
| `app/Http/Controllers/` | Inertia controllers returning `Inertia::render()` |
| `app/Http/Controllers/Admin/` | Core admin controllers |
| `app/Http/Controllers/Api/` | API controllers (BusinessUnit, Department) |
| `app/Http/Controllers/Auth/` | Authentication controllers |
| `app/Http/Controllers/Modules/` | Module controllers organized by module |
| `app/Http/Middleware/` | Custom middleware |
| `app/Http/Requests/` | Form request validation classes |
| `app/Models/Core/` | Core models (User, BusinessUnit, Department, etc.) |
| `app/Models/Modules/` | Module models organized by `[Module]/[SubModule]/` |
| `app/Services/Core/` | Core services (Email, Navigation, Numbering, QR) |
| `app/Services/Modules/` | Module business logic services |
| `app/Notifications/` | Notification classes organized by module |
| `app/Events/` | Event classes |
| `app/Observers/` | Model observers |
| `app/Console/Commands/` | Artisan commands |
| `database/migrations/` | Database migrations |

### Controller Structure
```
Controllers/
  Admin/
    ActivityAdminAssignmentController   # Activity admin assignments
    ActivityTypeController              # Activity type CRUD
    AdminController                     # Admin dashboard
    BusinessUnitController              # BU CRUD
    DashboardController                 # Admin dashboard
    DepartmentController                # Department CRUD
    NotificationSettingsController      # Email notification config
    PrCategoryController                # PR category CRUD
    SlaSettingsController               # SLA settings CRUD
    SubActivityController               # Sub-activity CRUD
    UserManagementController            # User CRUD
  Modules/
    Activity/
      ActivityAdminController           # Activity admin features
      ActivityInertiaController         # Activity tracking pages
      ActivityReportingController       # Activity reports
    CashflowProjection/
      CashflowProjectionController     # Cashflow CRUD & dashboard
    Purchasing/
      Admin/PurchasingAdminController   # Purchasing admin tasks
      PurchaseRequest/
        ApprovalController              # PR approval actions
        PrNumberReservationController   # PR number reservation
        PurchaseRequestController       # PR CRUD
      StockRequest/
        StockApprovalController         # ST approval actions
        StockRequestController          # ST CRUD
      PurchasingController              # Purchasing dashboard
  SalesCrmController                    # CRM contacts & activities
  DashboardController                   # Main dashboard
  ProfileController                     # User profile
  DocsHelpController                    # Help documentation
  ErrorLogController                    # Error log viewer
```

### Middleware
| Middleware | Purpose |
|-----------|---------|
| `AdminAccess` | Core admin panel access |
| `ActivityAdminAccess` | Activity admin features |
| `ActivityReportingAccess` | Activity reporting access |
| `PurchasingAdminAccess` | Purchasing admin features (gate: `access-purchasing-admin`) |
| `CheckBusinessUnitAccess` | Verify user has access to current BU |
| `EnsureBusinessUnitSelected` | Require BU selection before proceeding |
| `ApiBusinessUnitContext` | Set BU context for API requests |
| `HandleInertiaRequests` | Inertia shared data |

### Services Structure
```
Services/
  Core/
    EmailNotificationService        # Configurable SMTP email sending
    NavigationService               # Dynamic menu building per user/role
    NumberingService                 # Generic numbering service
    QrCodeService                   # QR code generation
  Modules/
    Activity/
      ActivityAdminExportService    # Admin activity export
      ActivityExportService         # User activity export
      ActivityTypeMigrationService  # Activity type migration
      ActivityTypePrioritizationService  # Type ordering
      BackdatePermissionService     # Backdate request handling
      TaskService                   # Task CRUD operations
    CashflowProjection/
      CashflowProjectionAccessService    # Access control
      CashflowProjectionTemplateService  # Template management
    Purchasing/
      Admin/
        AdminTaskAssignmentService  # Task assignment logic
        AdminTaskService            # Admin task operations
        PriceEfficiencyService      # Price comparison & savings
        SlaMonitoringService        # SLA violation detection
      PurchaseRequest/
        ApprovalWorkflowService     # PR approval routing
        PurchaseRequestService      # PR operations
        UniversalPRNumberingService # BU-specific PR numbering
      StockRequest/
        UniversalStockNumberingService  # BU-specific ST numbering
    SalesCrm/
      ActivityService               # CRM activity operations
      ContactService                # Contact management
```

### Model Observers
| Observer | Purpose |
|----------|---------|
| `PurchaseRequestObserver` | Auto-create AdminTask on PR approval |
| `StockRequestObserver` | Auto-create AdminTask on ST approval |
| `UserBusinessUnitObserver` | Invalidate navigation cache on BU mapping changes |

## Naming Conventions

| Type | Pattern | Example |
|------|---------|---------|
| Models | `App\Models\Modules\[Module]\[SubModule]\[Name]` | `PurchaseRequest\PrItem` |
| Controllers | `App\Http\Controllers\[Context]\[Name]Controller` | `Admin\UserController` |
| Services | `App\Services\Modules\[Module]\[Name]Service` | `ApprovalWorkflowService` |
| Notifications | `App\Notifications\[Module]\[SubModule]\[Name]` | `Purchasing\PurchaseRequest\ApprovalRequested` |
| Form Requests | `App\Http\Requests\[Module]\[Name]Request` | `Purchasing\StorePurchaseRequestRequest` |
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
- **Gates**: `view-reports` for top management, `access-purchasing-admin` for purchasing admins
- **Permissions**: Spatie Permission package, check via `$user->can('permission-name')`
- **Middleware**: Role-based access via custom middleware (see Middleware table above)

## Key Files Reference

- **Routes**: `routes/web.php`
- **Gates**: `app/Providers/AppServiceProvider.php`
- **Event Listeners**: `app/Providers/EventServiceProvider.php`
- **Bootstrap**: `bootstrap/app.php` (middleware, exceptions, routing)
- **Providers**: `bootstrap/providers.php`
- **Config**: `config/approval.php`, `config/features.php`, `config/notification.php`
- **Index Standards**: `database/migrations/README-INDEX-STANDARDS.md`
- **React Components**: `resources/js/inertia/components/admin/README.md`
