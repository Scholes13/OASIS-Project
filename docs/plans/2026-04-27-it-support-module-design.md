# IT Support Module Design

## Overview

Migrate WGTicket (standalone IT support ticketing system) into OASIS as a first-class **IT Support** module. The module follows the exact same admin-assignment pattern as Activity Admin and Purchasing Admin: a `is_it_support_admin` flag on `user_business_units`, BU ancestor cascade, dedicated middleware, gates, navigation, dashboard, and reporting.

All OASIS users can submit tickets and track their own tickets. Users flagged as IT Support admin get an extended menu with dashboard, ticket management, reporting, knowledge base management, and category management.

## Approved Design Decisions

| Decision | Choice |
|---|---|
| Public ticket form (unauthenticated) | **No** — login required, requester auto-filled from user profile |
| Knowledge Base | **Yes** — migrated into module, scoped per BU |
| Data migration from WGTicket | **Later** — build module first, data migration deferred |
| Who can submit tickets | **All OASIS users** |
| Sidebar visibility | **All users** see Submit + My Tickets + KB; **IT Support admins** see extended menu |
| Ticket number format | `IT.{BU_CODE}/YYYYMM/###` (e.g. `IT.WNS/202604/001`) |
| Priority levels | 4: low, medium, high, critical |
| SLA tracking | **Yes** — configurable per BU per priority |
| Assignment target | **Only users with `is_it_support_admin = true`** in BU scope |

## Access Model

### Admin Assignment (mirrors Activity/Purchasing Admin exactly)

**Database columns on `user_business_units`:**
- `is_it_support_admin` (boolean, default false) — after `is_activity_report_access`
- `is_it_support_report_access` (boolean, default false) — after `is_it_support_admin`

**Assignment controller:** `ITSupportAssignmentController`
- `index()` — list users with BU filter and search
- `toggle($id)` — flip `is_it_support_admin`, auto-revoke report access when OFF
- `toggleReportAccess($id)` — flip `is_it_support_report_access`, only when admin is ON

**BU cascade:** `User::isAdminInBuOrAncestor('is_it_support_admin', $buId)`
- IT Support admin at holding BU → automatically admin for all child BUs
- Same ancestor-walk logic already used by Activity and Purchasing

**Middleware:** `ITSupportAccess` (alias: `it.support.access`)
- Super Admin → allowed
- Top Management → allowed
- Otherwise → `isAdminInBuOrAncestor('is_it_support_admin', session BU)`

**Gates:**
- `access-it-support` — can access IT Support admin pages
- `view-it-support-reports` — requires both `is_it_support_admin` AND `is_it_support_report_access`

### Admin Assignment Page
- Route: `admin.it-support-admins.index`, `.toggle`, `.toggle-report`
- Page: `Pages/Admin/ITSupportAdmins/Index.tsx`
- Mirrors `Pages/Admin/PurchasingAdmins/Index.tsx` with IT Support-specific colors and wording

## Navigation

### All authenticated users
```
IT Support
  ├─ Submit Ticket          → /it-support/submit
  ├─ My Tickets             → /it-support/my-tickets
  └─ Knowledge Base         → /it-support/knowledge
```

### IT Support Admin (is_it_support_admin = true via cascade)
```
IT Support
  ├─ Submit Ticket
  ├─ My Tickets
  ├─ Knowledge Base
  ├─── (separator) ───
  ├─ Dashboard              → /it-support/dashboard
  ├─ All Tickets            → /it-support/tickets
  ├─ Reporting              → /it-support/reporting
  ├─ Categories             → /it-support/categories
  └─ Manage Knowledge       → /it-support/knowledge/manage
```

**NavigationService:** Add `canAccessItSupportAdmin()` method using `isAdminInBuOrAncestor('is_it_support_admin', $buId)`.

## Ticket Lifecycle

### Number Format
`IT.{BU_CODE}/YYYYMM/###` — integrates with OASIS `NumberSequence` system.
- Register `IT` as a new numbering module in `numbering_modules` table
- Sequence scoped per BU (no department dimension)
- Counter resets monthly

### Status Workflow
```
  WAITING ──→ IN_PROGRESS ──→ DONE
     │              │
     └──→ CANCELLED ←──┘
```

### Priority & SLA
| Priority | Default SLA |
|---|---|
| Low | 48 hours |
| Medium | 24 hours |
| High | 8 hours |
| Critical | 2 hours |

- SLA timer starts at ticket creation
- Configurable per BU by IT Support admin via `ticket_sla_settings` table
- Dashboard highlights tickets approaching or breaching SLA

### Assignment
- Tickets can only be assigned to users where `is_it_support_admin = true` in the ticket's BU scope
- Self-assignment allowed
- Reassignment allowed

## User Journeys

### Journey 1: Employee submits ticket
1. Login OASIS → Sidebar "IT Support" → "Submit Ticket"
2. Form: Title, Description, Category, Priority, Attachments
3. Requester auto-filled from user profile (name, email, department)
4. Submit → redirect to "My Tickets" with flash + ticket number
5. Notifications dispatched to all IT Support admins in BU

### Journey 2: Employee tracks ticket
1. Sidebar "IT Support" → "My Tickets"
2. Table: my tickets with status, priority, last update
3. Click ticket → read-only detail with public comments and status timeline

### Journey 3: IT Support Admin manages tickets
1. Sidebar "IT Support" → "Dashboard"
2. Summary cards, SLA breach alerts, charts, recent tickets
3. "All Tickets" → filterable table (status, priority, category, assigned, SLA)
4. Click ticket → full detail:
   - Assign to IT Support staff
   - Change status (with transition validation)
   - Add comment (public visible to requester, or private internal-only)
   - Upload attachment
   - Link knowledge article
5. "Reporting" → period filter → charts → export Excel/PDF

### Journey 4: IT Support Admin manages knowledge base
1. Sidebar "IT Support" → "Manage Knowledge"
2. CRUD articles: title, rich-text content, category, tags, publish/draft
3. CRUD categories: hierarchical, icon, order
4. View stats per article

### Journey 5: All users browse knowledge base
1. Sidebar "IT Support" → "Knowledge Base"
2. Browse categories, search articles, read published articles
3. During ticket submission: article suggestions based on title

## Database Schema

### New tables (9)
| Table | Purpose |
|---|---|
| `ticket_categories` | Ticket categories per BU |
| `tickets` | Main ticket table, scoped per BU |
| `ticket_comments` | Comments on tickets (public/private, soft deletes) |
| `ticket_attachments` | File attachments on tickets and comments |
| `ticket_sla_settings` | SLA config per BU per priority |
| `ticket_knowledge_categories` | KB categories per BU (hierarchical) |
| `ticket_knowledge_articles` | KB articles per BU |
| `ticket_knowledge_article` | Pivot: ticket ↔ article |
| `ticket_article_views` | Article view tracking |

### Modified tables (1)
| Table | Changes |
|---|---|
| `user_business_units` | Add `is_it_support_admin`, `is_it_support_report_access` |

### New row in existing tables (1)
| Table | Change |
|---|---|
| `numbering_modules` | Register `IT` module for ticket number generation |

## Notification Flow

Uses OASIS notification center (database + broadcast). New category: `it_support`.

| Event | Recipient | Channel |
|---|---|---|
| Ticket Created | All IT Support admins in BU | In-app + Email |
| Ticket Assigned | Assigned user | In-app + Email |
| Status Changed | Ticket creator | In-app + Email |
| Comment (public) | Creator + assigned | In-app |
| Comment (private) | IT Support admins only | In-app |
| SLA Breach Warning | Assigned + IT Support admins | In-app |

### Integration points
- Add `it_support` to `NotificationCenterController` category allowlist
- Add `it_support` filter tab to notification center frontend
- Follow existing notification payload contract (category, event, title, body, action_url)

## Export

Uses `phpoffice/phpspreadsheet` (already in OASIS `composer.json`). Does NOT use `maatwebsite/excel` (not in OASIS).

- Excel export: ticket list with filters, summary sheet, detail sheet
- PDF export: summary report via `spatie/browsershot`

## File Structure

### Backend
```
app/Http/Controllers/Admin/ITSupportAssignmentController.php
app/Http/Controllers/Modules/Ticket/TicketController.php
app/Http/Controllers/Modules/Ticket/TicketDashboardController.php
app/Http/Controllers/Modules/Ticket/TicketReportingController.php
app/Http/Controllers/Modules/Ticket/TicketCategoryController.php
app/Http/Controllers/Modules/Ticket/KnowledgeBaseController.php
app/Http/Controllers/Modules/Ticket/KnowledgeCategoryController.php
app/Http/Controllers/Modules/Ticket/UserTicketController.php
app/Http/Middleware/ITSupportAccess.php
app/Http/Requests/Ticket/StoreTicketRequest.php
app/Http/Requests/Ticket/UpdateTicketRequest.php
app/Http/Requests/Ticket/StoreTicketCommentRequest.php
app/Http/Requests/Ticket/AssignTicketRequest.php
app/Http/Requests/Ticket/ChangeTicketStatusRequest.php
app/Http/Requests/Ticket/StoreKnowledgeArticleRequest.php
app/Http/Requests/Ticket/UpdateKnowledgeArticleRequest.php
app/Http/Requests/Ticket/StoreCategoryRequest.php
app/Http/Requests/Ticket/UpdateSlaSettingsRequest.php
app/Models/Modules/Ticket/Ticket.php
app/Models/Modules/Ticket/TicketCategory.php
app/Models/Modules/Ticket/TicketComment.php
app/Models/Modules/Ticket/TicketAttachment.php
app/Models/Modules/Ticket/TicketSlaSettings.php
app/Models/Modules/Ticket/KnowledgeCategory.php
app/Models/Modules/Ticket/KnowledgeArticle.php
app/Models/Modules/Ticket/ArticleView.php
app/Services/Modules/Ticket/TicketService.php
app/Services/Modules/Ticket/TicketNumberService.php
app/Services/Modules/Ticket/TicketReportingService.php
app/Services/Modules/Ticket/KnowledgeBaseService.php
app/Services/Modules/Ticket/SlaService.php
app/Notifications/Ticket/TicketCreatedNotification.php
app/Notifications/Ticket/TicketAssignedNotification.php
app/Notifications/Ticket/TicketStatusChangedNotification.php
app/Notifications/Ticket/TicketCommentNotification.php
app/Notifications/Ticket/TicketSlaBreachNotification.php
app/Exports/Modules/Ticket/TicketExport.php
database/migrations/modules/ticket/ (9 migration files)
```

### Frontend
```
resources/js/inertia/types/ticket.ts
resources/js/inertia/Pages/Admin/ITSupportAdmins/Index.tsx
resources/js/inertia/Pages/Ticket/Dashboard.tsx
resources/js/inertia/Pages/Ticket/Index.tsx
resources/js/inertia/Pages/Ticket/Show.tsx
resources/js/inertia/Pages/Ticket/Create.tsx
resources/js/inertia/Pages/Ticket/Edit.tsx
resources/js/inertia/Pages/Ticket/MyTickets.tsx
resources/js/inertia/Pages/Ticket/Reporting.tsx
resources/js/inertia/Pages/Ticket/Categories/Index.tsx
resources/js/inertia/Pages/Ticket/Categories/Create.tsx
resources/js/inertia/Pages/Ticket/Categories/Edit.tsx
resources/js/inertia/Pages/Ticket/SlaSettings.tsx
resources/js/inertia/Pages/Ticket/Knowledge/Index.tsx
resources/js/inertia/Pages/Ticket/Knowledge/Create.tsx
resources/js/inertia/Pages/Ticket/Knowledge/Edit.tsx
resources/js/inertia/Pages/Ticket/Knowledge/Browse.tsx
resources/js/inertia/Pages/Ticket/Knowledge/Article.tsx
resources/js/inertia/Pages/Ticket/Knowledge/Search.tsx
resources/js/inertia/Pages/Ticket/Knowledge/Categories/Index.tsx
resources/js/inertia/Pages/Ticket/Knowledge/Categories/Create.tsx
resources/js/inertia/Pages/Ticket/Knowledge/Categories/Edit.tsx
resources/js/inertia/components/Ticket/TicketForm.tsx
resources/js/inertia/components/Ticket/TicketStatusBadge.tsx
resources/js/inertia/components/Ticket/TicketPriorityBadge.tsx
resources/js/inertia/components/Ticket/SlaBadge.tsx
resources/js/inertia/components/Ticket/CommentSection.tsx
resources/js/inertia/components/Ticket/AttachmentList.tsx
```

### Tests
```
tests/Feature/Modules/Ticket/TicketCrudTest.php
tests/Feature/Modules/Ticket/TicketStatusWorkflowTest.php
tests/Feature/Modules/Ticket/TicketAssignmentTest.php
tests/Feature/Modules/Ticket/TicketCommentTest.php
tests/Feature/Modules/Ticket/TicketCategoryTest.php
tests/Feature/Modules/Ticket/TicketSlaTest.php
tests/Feature/Modules/Ticket/TicketReportingTest.php
tests/Feature/Modules/Ticket/ITSupportAssignmentTest.php
tests/Feature/Modules/Ticket/KnowledgeBaseTest.php
tests/React/Pages/Ticket/Dashboard.test.tsx
tests/React/Pages/Ticket/Index.test.tsx
tests/React/Pages/Ticket/Show.test.tsx
tests/React/Pages/Ticket/MyTickets.test.tsx
tests/React/Pages/Admin/ITSupportAdmins.test.tsx
```

## Route Structure

### User routes (all authenticated users)
```
/it-support/submit          → UserTicketController@create
/it-support/submit (POST)   → UserTicketController@store
/it-support/my-tickets      → UserTicketController@myTickets
/it-support/my-tickets/{id} → UserTicketController@show
/it-support/my-tickets/{id}/comment (POST) → UserTicketController@addComment
/it-support/knowledge       → KnowledgeBaseController@browse
/it-support/knowledge/search → KnowledgeBaseController@search
/it-support/knowledge/{slug} → KnowledgeBaseController@article
```

### IT Support Admin routes (middleware: it.support.access)
```
/it-support/dashboard       → TicketDashboardController@index
/it-support/tickets         → TicketController@index
/it-support/tickets/{id}    → TicketController@show
/it-support/tickets/{id}/edit → TicketController@edit
/it-support/tickets/{id} (PUT) → TicketController@update
/it-support/tickets/{id} (DELETE) → TicketController@destroy
/it-support/tickets/{id}/comment (POST) → TicketController@addComment
/it-support/tickets/{id}/change-status (PUT) → TicketController@changeStatus
/it-support/tickets/{id}/assign (POST) → TicketController@assignTicket
/it-support/tickets/{id}/link-article (POST) → KnowledgeBaseController@linkArticle
/it-support/reporting       → TicketReportingController@index
/it-support/reporting/export/excel → TicketReportingController@exportExcel
/it-support/reporting/export/pdf → TicketReportingController@exportPdf
/it-support/categories      → TicketCategoryController (resource)
/it-support/sla-settings    → TicketDashboardController@slaSettings
/it-support/sla-settings (PUT) → TicketDashboardController@updateSlaSettings
/it-support/knowledge/manage → KnowledgeBaseController@adminIndex
/it-support/knowledge/manage/create → KnowledgeBaseController@adminCreate
/it-support/knowledge/manage (POST) → KnowledgeBaseController@adminStore
/it-support/knowledge/manage/{id}/edit → KnowledgeBaseController@adminEdit
/it-support/knowledge/manage/{id} (PUT) → KnowledgeBaseController@adminUpdate
/it-support/knowledge/manage/{id} (DELETE) → KnowledgeBaseController@adminDestroy
/it-support/knowledge/categories → KnowledgeCategoryController (resource)
```

### Admin assignment routes (admin panel)
```
/admin/it-support-admins           → ITSupportAssignmentController@index
/admin/it-support-admins/{id}/toggle (POST) → ITSupportAssignmentController@toggle
/admin/it-support-admins/{id}/toggle-report (POST) → ITSupportAssignmentController@toggleReportAccess
```

## Reviewer Gap Fixes Incorporated

1. **`UserBusinessUnit` model update** — add columns to `$fillable` and `$casts`
2. **Notification center category allowlist** — add `it_support` to backend controller + frontend filter tabs
3. **Export uses PhpSpreadsheet** — not `maatwebsite/excel` (not in OASIS)
4. **NumberSequence integration** — register `IT` module in `numbering_modules`, sequence per BU only
5. **Knowledge Base vs DocsHelp** — separate purposes: KB = IT FAQ, DocsHelp = OASIS user guide
6. **Frontend shared components** — explicitly reuse `DataTable`, shared UI primitives
7. **Middleware alias** — register `it.support.access` in `bootstrap/app.php`
8. **Sequence scope** — per BU only (no department dimension)
