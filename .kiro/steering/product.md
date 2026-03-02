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
| Activity Tracking | Active | Employee task management |
| Sales CRM | Planned | Customer relationship management |
| Core Admin | Active | Users, departments, business units |

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
Purchase/Stock Request: draft → submitted → in_approval → approved|rejected|voided
Employee Task: planned → in_progress → completed|cancelled
```

### User Roles (Authorization Hierarchy)
1. Super Admin - bypasses all checks (`$user->isSuperAdmin()`)
2. Top Management (GM, Director, CEO) - reports access via `view-reports` gate
3. Finance Manager - financial oversight
4. Department Heads - approval authority
5. Regular Users - request creators

## Implementation Guidelines

### Frontend Stack
- **React/Inertia** - All features use React/Inertia with TypeScript
- Pages: `resources/js/inertia/Pages/`
- Components: `resources/js/inertia/components/`

### When Building New Features
- Use React/Inertia with TypeScript
- CRUD patterns: Index, Create, Edit, Show pages
- Use existing components: `DataTable`, `StatCard`, `FileUpload`, `ColorPicker`
- See `resources/js/inertia/components/admin/README.md`

### When Building Purchasing Features
- PR/ST share similar patterns: approval workflows, numbering, QR codes, offline approval
- Use `ApprovalWorkflowService` for routing logic
- Numbering is business unit-specific via `UniversalPRNumberingService` / `UniversalStockNumberingService`
- Support both online and offline (paper-based) approval flows

### When Building Activity Features
- Tasks are department-scoped (users see their department's tasks)
- Collaborative: multiple participants can join/update same task
- Backdate system: 1-day default limit, request-based for older dates
- Activity types are admin-configurable with color coding

## Data Relationships

### Core Entities
```
User → UserBusinessUnit → BusinessUnit
User → Department → BusinessUnit
PurchaseRequest → PrItem, PrApproval, PrCategory
StockRequest → StockItem, StockApproval
EmployeeTask → TaskParticipant, TaskAttachment, ActivityType → SubActivity
```

### Approval Chain Pattern
- Sequential or parallel approval steps
- Each step: approver, status, timestamp, optional QR signature
- Offline approval: document upload, designated date tracking

## Validation Rules

### Business Logic Constraints
- PR/ST numbers are unique per business unit per year
- Approval workflows are business unit-specific
- Task backdating requires permission (default: yesterday only)
- Department heads approve their department's requests

### Status Transitions
- Only `draft` status allows editing
- `submitted` triggers approval workflow
- `voided` is terminal (no further transitions)
- Task `completed`/`cancelled` are terminal states
