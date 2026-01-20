# Module Status - React/Inertia Migration

**Last Updated:** January 19, 2026

## Overview

Dokumen ini menjelaskan status migrasi setiap modul dari Livewire ke React/Inertia, termasuk route yang ada dan view yang sudah/belum dibuat.

---

## 📊 Dashboard Module

### Status: ✅ COMPLETE (React/Inertia)

**Routes:**
- `GET /dashboard` → `dashboard`

**React Pages:**
- ✅ `resources/js/inertia/Pages/Dashboard.tsx`

**Features:**
- Stats cards (My PRs, My STs, Pending Approvals, My Tasks)
- Quick actions grid
- Recent activities list
- Fully functional with Sidebar + Navbar

**Layout Components:**
- ✅ `resources/js/inertia/components/layout/Sidebar.tsx` - Navigation sidebar with dropdown support
- ✅ `resources/js/inertia/components/layout/Navbar.tsx` - Top navigation bar
- ✅ `resources/js/inertia/components/layout/BusinessUnitSwitcher.tsx` - Business unit switcher
- ✅ `resources/js/inertia/components/layout/UserMenu.tsx` - User menu with logout
- ✅ `resources/js/inertia/layouts/AppLayout.tsx` - Main layout wrapper

---

## 🛒 Purchasing Module

### Status: ⚠️ PARTIAL (Mixed Livewire + Inertia)

### Navigation Structure

**Purchasing Menu (Dropdown):**
- 📁 Purchasing (parent with dropdown)
  - Purchase Requests → `purchase-requests.index`
  - Stock Requests → `stock-requests.index`
  - All Requests → `purchase-requests.all`
  - Approvals → `approvals.index`
- Purchasing Admin → `purchasing.admin.dashboard` (separate item, permission-based)

### 1. Purchase Requests (PR)

**Routes:**
| Route | Name | Status | View Type |
|-------|------|--------|-----------|
| `GET /purchase-requests` | `purchase-requests.index` | ❌ Livewire | Need React |
| `GET /purchase-requests/create` | `purchase-requests.create` | ❌ Livewire | Need React |
| `GET /purchase-requests/{id}` | `purchase-requests.show` | ❌ Livewire | Need React |
| `GET /purchase-requests/{id}/edit` | `purchase-requests.edit` | ❌ Livewire | Need React |
| `GET /purchase-requests/all/list` | `purchase-requests.all` | ❌ Livewire | Need React |

**React Pages Needed:**
- ❌ `resources/js/inertia/Pages/Purchasing/PurchaseRequest/Index.tsx` - List all PRs
- ❌ `resources/js/inertia/Pages/Purchasing/PurchaseRequest/Create.tsx` - Create new PR
- ❌ `resources/js/inertia/Pages/Purchasing/PurchaseRequest/Show.tsx` - View PR detail
- ❌ `resources/js/inertia/Pages/Purchasing/PurchaseRequest/Edit.tsx` - Edit PR

**Current Implementation:**
- Livewire components in `app/Livewire/Modules/Purchasing/PurchaseRequest/`
- Controllers ready for Inertia in `app/Http/Controllers/Modules/Purchasing/PurchaseRequest/`

### 2. Stock Requests (ST)

**Routes:**
| Route | Name | Status | View Type |
|-------|------|--------|-----------|
| `GET /stock-requests` | `stock-requests.index` | ❌ Livewire | Need React |
| `GET /stock-requests/create` | `stock-requests.create` | ❌ Livewire | Need React |
| `GET /stock-requests/{id}` | `stock-requests.show` | ❌ Livewire | Need React |
| `GET /stock-requests/{id}/edit` | `stock-requests.edit` | ❌ Livewire | Need React |

**React Pages Needed:**
- ❌ `resources/js/inertia/Pages/Purchasing/StockRequest/Index.tsx` - List all STs
- ❌ `resources/js/inertia/Pages/Purchasing/StockRequest/Create.tsx` - Create new ST
- ❌ `resources/js/inertia/Pages/Purchasing/StockRequest/Show.tsx` - View ST detail
- ❌ `resources/js/inertia/Pages/Purchasing/StockRequest/Edit.tsx` - Edit ST

**Current Implementation:**
- Livewire components in `app/Livewire/Modules/Purchasing/StockRequest/`
- Controllers ready for Inertia in `app/Http/Controllers/Modules/Purchasing/StockRequest/`

### 3. Approvals

**Routes:**
| Route | Name | Status | View Type |
|-------|------|--------|-----------|
| `GET /approvals` | `approvals.index` | ❌ Livewire | Need React |
| `GET /approvals/{id}` | `approvals.show` | ❌ Livewire | Need React |
| `GET /stock-approvals` | `stock-approvals.index` | ❌ Livewire | Need React |
| `GET /stock-approvals/{id}` | `stock-approvals.show` | ❌ Livewire | Need React |

**React Pages Needed:**
- ❌ `resources/js/inertia/Pages/Purchasing/Approvals/Index.tsx` - List all pending approvals (PR + ST)
- ❌ `resources/js/inertia/Pages/Purchasing/Approvals/Show.tsx` - Approval detail with approve/reject

**Current Implementation:**
- Livewire components in `app/Livewire/Modules/Purchasing/`
- Controllers in `app/Http/Controllers/Modules/Purchasing/PurchaseRequest/ApprovalController.php`

### 4. Purchasing Admin

**Routes:**
| Route | Name | Status | View Type |
|-------|------|--------|-----------|
| `GET /purchasing/admin/dashboard` | `purchasing.admin.dashboard` | ✅ React | Exists |
| `GET /purchasing/admin/tasks` | `purchasing.admin.tasks` | ❌ Not Implemented | Need React |
| `GET /purchasing/admin/department-report` | `purchasing.admin.department-report` | ❌ Not Implemented | Need React |
| `GET /purchasing/admin/consolidated-report` | `purchasing.admin.consolidated-report` | ❌ Not Implemented | Need React |

**React Pages:**
- ✅ `resources/js/inertia/Pages/PurchasingAdmin/Dashboard.tsx` - Admin dashboard
- ❌ `resources/js/inertia/Pages/PurchasingAdmin/Tasks.tsx` - Task management
- ❌ `resources/js/inertia/Pages/PurchasingAdmin/DepartmentReport.tsx` - Department report
- ❌ `resources/js/inertia/Pages/PurchasingAdmin/ConsolidatedReport.tsx` - Consolidated report

**Current Implementation:**
- Controller: `app/Http/Controllers/Modules/Purchasing/Admin/PurchasingAdminController.php`
- Dashboard page exists, other pages need to be created

---

## 📅 Activity Tracking Module

### Status: ✅ MOSTLY COMPLETE (React/Inertia)

**Routes:**
| Route | Name | Status | View Type |
|-------|------|--------|-----------|
| `GET /activity/task` | `activity.task.index` | ✅ React | Exists |
| `GET /activity/task/create` | `activity.task.create` | ✅ React | Exists |
| `GET /activity/task/{id}` | `activity.task.show` | ✅ React | Exists |
| `GET /activity/task/{id}/edit` | `activity.task.edit` | ✅ React | Exists |
| `GET /activity/dashboard` | `activity.dashboard` | ✅ React | Exists |
| `GET /activity/reporting` | `activity.reporting` | ❌ Not Implemented | Need React |
| `GET /activity/reporting/manager` | `activity.reporting.manager` | ❌ Not Implemented | Need React |

**React Pages:**
- ✅ `resources/js/inertia/Pages/Activity/Dashboard.tsx` - Personal dashboard
- ✅ `resources/js/inertia/Pages/Activity/ActivityDashboard.tsx` - Activity dashboard
- ✅ `resources/js/inertia/Pages/Activity/DepartmentTasks.tsx` - Department tasks
- ✅ `resources/js/inertia/Pages/Activity/TaskDetail.tsx` - Task detail
- ✅ `resources/js/inertia/Pages/Activity/TaskForm.tsx` - Create/Edit task
- ❌ `resources/js/inertia/Pages/Activity/Reporting/BOD.tsx` - BOD reporting dashboard
- ❌ `resources/js/inertia/Pages/Activity/Reporting/Manager.tsx` - Manager dashboard

**Current Implementation:**
- Controller: `app/Http/Controllers/Modules/Activity/ActivityInertiaController.php`
- Most pages exist, only reporting dashboards missing

---

## 💼 Sales CRM Module

### Status: ❌ NOT MIGRATED (Livewire)

**Routes:**
| Route | Name | Status | View Type |
|-------|------|--------|-----------|
| `GET /sales-crm/contacts` | `sales-crm.contacts.index` | ❌ Livewire | Need React |
| `GET /sales-crm/contacts/create` | `sales-crm.contacts.create` | ❌ Livewire | Need React |
| `GET /sales-crm/contacts/{id}` | `sales-crm.contacts.show` | ❌ Livewire | Need React |
| `GET /sales-crm/contacts/{id}/edit` | `sales-crm.contacts.edit` | ❌ Livewire | Need React |
| `GET /sales-crm/activities` | `sales-crm.activities.index` | ❌ Livewire | Need React |
| `GET /sales-crm/activities/create` | `sales-crm.activities.create` | ❌ Livewire | Need React |
| `GET /sales-crm/activities/{id}` | `sales-crm.activities.show` | ❌ Livewire | Need React |
| `GET /sales-crm/activities/{id}/edit` | `sales-crm.activities.edit` | ❌ Livewire | Need React |

**React Pages Needed:**
- ❌ `resources/js/inertia/Pages/SalesCRM/Contacts/Index.tsx` - List contacts
- ❌ `resources/js/inertia/Pages/SalesCRM/Contacts/Create.tsx` - Create contact
- ❌ `resources/js/inertia/Pages/SalesCRM/Contacts/Show.tsx` - View contact
- ❌ `resources/js/inertia/Pages/SalesCRM/Contacts/Edit.tsx` - Edit contact
- ❌ `resources/js/inertia/Pages/SalesCRM/Activities/Index.tsx` - List activities
- ❌ `resources/js/inertia/Pages/SalesCRM/Activities/Create.tsx` - Create activity
- ❌ `resources/js/inertia/Pages/SalesCRM/Activities/Show.tsx` - View activity
- ❌ `resources/js/inertia/Pages/SalesCRM/Activities/Edit.tsx` - Edit activity

**Current Implementation:**
- Livewire components in `app/Livewire/Modules/SalesCrm/`
- No Inertia controllers yet

---

## 🔧 Administration Module

### Status: ❌ NOT MIGRATED (Livewire)

**Routes:**
| Route | Name | Status | View Type |
|-------|------|--------|-----------|
| `GET /admin/users` | `admin.users.index` | ❌ Livewire | Need React |
| `GET /admin/departments` | `admin.departments.index` | ❌ Livewire | Need React |
| `GET /admin/business-units` | `admin.business-units.index` | ❌ Livewire | Need React |
| `GET /admin/pr-categories` | `admin.pr-categories.index` | ❌ Livewire | Need React |
| `GET /admin/activity-types` | `admin.activity-types.index` | ❌ Livewire | Need React |

**React Pages Needed:**
- ❌ `resources/js/inertia/Pages/Admin/Users/Index.tsx` - User management
- ❌ `resources/js/inertia/Pages/Admin/Departments/Index.tsx` - Department management
- ❌ `resources/js/inertia/Pages/Admin/BusinessUnits/Index.tsx` - Business unit management
- ❌ `resources/js/inertia/Pages/Admin/PRCategories/Index.tsx` - PR category management
- ❌ `resources/js/inertia/Pages/Admin/ActivityTypes/Index.tsx` - Activity type management

**Current Implementation:**
- Livewire components in `app/Livewire/Admin/`
- Controllers in `app/Http/Controllers/Admin/`

---

## 📈 Migration Priority

### High Priority (Core Features)
1. **Purchase Requests** - Most used feature
   - Index page (list)
   - Create page
   - Show page (detail)
   - Edit page

2. **Stock Requests** - Second most used
   - Index page (list)
   - Create page
   - Show page (detail)
   - Edit page

3. **Approvals** - Critical workflow
   - Combined index (PR + ST approvals)
   - Approval detail with actions

### Medium Priority
4. **Purchasing Admin** - Management features
   - Tasks page
   - Reports pages

5. **Activity Tracking** - Reporting
   - BOD reporting dashboard
   - Manager dashboard

### Low Priority
6. **Sales CRM** - Less frequently used
   - All pages

7. **Administration** - Admin only
   - All pages

---

## 🎯 Next Steps

### Immediate (Week 1-2)
1. ✅ Complete layout components (Sidebar, Navbar) - DONE
2. Create Purchase Request pages:
   - Index (list with filters)
   - Create (form with items)
   - Show (detail with approval timeline)
   - Edit (form)

### Short Term (Week 3-4)
3. Create Stock Request pages (similar to PR)
4. Create Approvals pages (combined PR + ST)

### Medium Term (Month 2)
5. Complete Purchasing Admin pages
6. Complete Activity Tracking reporting
7. Start Sales CRM migration

### Long Term (Month 3+)
8. Administration module migration
9. Performance optimization
10. Testing and bug fixes

---

## 📝 Notes

### Backward Compatibility
- All Livewire routes still work
- Gradual migration approach
- No breaking changes for users
- Session state consistent across both systems

### Technical Considerations
- Use Inertia.js for page navigation
- Maintain Tailwind CSS styling
- Reuse existing UI components
- Keep API endpoints consistent
- Preserve business logic in controllers

### Testing Strategy
- Test each page after migration
- Verify business unit switching
- Check permissions and access control
- Test form submissions and validations
- Verify PDF generation and downloads
