---
inclusion: always
---

# Oasis - Product Context

Enterprise office administration platform for Werkudara Group with multi-business unit support.

## Domain Model

### Modules
| Module | Status | Purpose |
|--------|--------|---------|
| Purchasing (PR/ST) | Active | Purchase & stock request workflows |
| Purchasing Admin | Active | Admin task management, SLA monitoring, price efficiency tracking |
| Activity Tracking | Active | Employee task management with analytics & backdate system |
| Sales CRM | Active | Contact management & sales activity tracking |
| Cashflow Projection | Active | Department-level cashflow forecasting with finance inputs |
| Core Admin | Active | Users, departments, business units, notification settings |
| Docs & Help | Active | In-app documentation and help center |

### Business Unit Hierarchy
- **WG** (Werkudara Group) - Parent holding
- Child units: **WNS**, **UK**, **MRP** (dynamic, configurable)

## Key Domain Concepts

### Business Unit Context
- Session-based: `current_business_unit_id`, `current_business_unit_name`, `current_business_unit_code`
- All data queries MUST filter by active business unit
- Users can belong to multiple units with different roles
- Event `business-unit-switched` triggers context refresh

### Workflow States
```
Purchase/Stock Request: draft -> submitted -> in_approval -> approved|rejected|voided
Purchasing Admin Task: pending_followup -> in_progress -> done
Employee Task: planned -> in_progress -> completed|cancelled
```

### User Roles (Authorization Hierarchy)
1. Super Admin - bypasses all checks (`$user->isSuperAdmin()`)
2. Top Management (GM, Director, CEO) - reports access via `view-reports` gate
3. Finance Manager - financial oversight, cashflow projection finance inputs
4. Purchasing Admin - purchasing admin task management via `access-purchasing-admin` gate
5. Activity Admin - activity tracking admin via `ActivityAdminAccess` middleware
6. Department Heads - approval authority
7. Regular Users - request creators

### Feature Flags
- Configured in `config/features.php`
- `backdate_approval` - toggle backdate approval workflow

## Implementation Guidelines

### Frontend Stack
- **React/Inertia** - All features use React/Inertia with TypeScript
- Pages: `resources/js/inertia/Pages/`
- Components: `resources/js/inertia/components/`

### When Building New Features
- Use React/Inertia with TypeScript
- CRUD patterns: Index, Create, Edit, Show pages
- Use existing components: `DataTable`, `StatCard`, `FileUpload`, `ColorPicker`
- Use existing UI components: `Badge`, `Button`, `Card`, `Dialog`, `ConfirmDialog`, `DatePicker`, `EmptyState`, `LoadingSpinner`, `Toast`
- Use existing hooks: `useBusinessUnit`, `useFilters`, `useFormSubmission`, `useFileUpload`, `usePrefetch`, `useOptimisticUpdate`, `useKeyboardShortcuts`
- See `resources/js/inertia/components/admin/README.md`

### When Building Purchasing Features
- PR/ST share similar patterns: approval workflows, numbering, QR codes, offline approval
- Use `ApprovalWorkflowService` for routing logic
- Numbering is business unit-specific via `UniversalPRNumberingService` / `UniversalStockNumberingService`
- Support both online and offline (paper-based) approval flows
- Approved PR/ST auto-create `AdminTask` via observers (`PurchaseRequestObserver`, `StockRequestObserver`)
- Approval thresholds configured in `config/approval.php`

### When Building Purchasing Admin Features
- Admin tasks are polymorphic (`taskable` -> PR or ST)
- SLA monitoring: follow-up SLA and completion SLA (hours-based, configurable per BU)
- Price efficiency tracking: estimated vs realized pricing with savings calculation
- Item-level realization via `AdminTaskItemRealization`
- SLA violation checking via `CheckSlaViolations` artisan command
- Task assignment via `AdminTaskAssignmentService`

### When Building Activity Features
- Tasks are department-scoped (users see their department's tasks)
- Collaborative: multiple participants can join/update same task
- Backdate system: 1-day default limit, request-based for older dates (`BackdatePermissionService`)
- Activity types are admin-configurable with color coding and prioritization
- Analytics: personal and department-level analytics pages
- Admin dashboard with department detail views
- Export services: `ActivityExportService`, `ActivityAdminExportService`

### When Building Cashflow Projection Features
- Cycle-based: yearly cycles per business unit
- Department-level line items (daily entries)
- Finance inputs: separate finance team entries per cycle
- Access control: department assignment + finance assignment checks
- Dashboard with daily/monthly summaries, period filtering
- Export to CSV/Excel

### When Building Sales CRM Features
- Contact management with auto-generated contact codes
- Activity tracking linked to contacts
- Company visit history tracking
- Contact sources (from activities or manual)
- Caching strategy with module-level cache clearing

## Data Relationships

### Core Entities
```
User -> UserBusinessUnit -> BusinessUnit
User -> Department -> BusinessUnit
```

### Purchasing Module
```
PurchaseRequest -> PrItem, PrApproval, PrCategory, PrNumberReservation
StockRequest -> StockItem, StockApproval, StockNumberReservation
AdminTask (polymorphic -> PR/ST) -> AdminTaskItemRealization
AdminTask -> SlaSettings (per BU or global)
```

### Activity Module
```
EmployeeTask -> TaskParticipant, TaskAttachment
ActivityType -> SubActivity
BackdatePermission -> User
```

### Cashflow Projection Module
```
CashflowProjectionCycle -> CashflowProjectionLineItem (per department)
CashflowProjectionCycle -> CashflowProjectionFinanceInput
```

### Sales CRM Module
```
Contact -> Activity, ContactSource, CompanyVisitHistory
Activity -> Contact, User, BusinessUnit
```

### Approval Chain Pattern
- Sequential or parallel approval steps
- Each step: approver, status, timestamp, optional QR signature
- Offline approval: document upload, designated date tracking

## Notification System

### Channels
- Email (via configurable SMTP, `EmailNotificationService`)
- Database notifications
- Configurable per business unit via admin panel (`NotificationSetting`)

### Notification Types
- Purchasing: `ApprovalRequested`, `ApprovalCompleted`, `ApprovalRejected` (PR & ST)
- Purchasing Admin: `TaskAssigned`, `SlaExceeded`
- Activity: `BackdateRequestSubmitted`, `BackdateRequestApproved`, `BackdateRequestRejected`

### Events
- `PrApprovalCompleted` - fired when PR approval is completed

## Validation Rules

### Business Logic Constraints
- PR/ST numbers are unique per business unit per year
- Approval workflows are business unit-specific
- Task backdating requires permission (default: yesterday only)
- Department heads approve their department's requests
- SLA settings cascade: BU-specific then global fallback

### Status Transitions
- Only `draft` status allows editing
- `submitted` triggers approval workflow
- `voided` is terminal (no further transitions)
- Task `completed`/`cancelled` are terminal states
- Admin task `done` is terminal

## Artisan Commands
| Command | Purpose |
|---------|---------|
| `backfill:admin-tasks` | Backfill admin tasks for existing approved PR/ST |
| `sla:check-violations` | Check and alert on SLA violations |
| `backdate:expire-permissions` | Expire outdated backdate permissions |
| `activity:migrate-types` | Migrate activity types between structures |
