# Sales CRM Module Implementation Tasks - v2.5

**Project**: WNS Purchase Request Management System  
**Version**: v2.5-beta  
**Created**: October 13, 2025  
**Branch**: v2.5-beta  
**Status**: 🟡 In Progress

---

## 📋 Task Overview

| Phase | Tasks | Status | Priority | Estimated Time |
|-------|-------|--------|----------|----------------|
| **Phase 1** | Database & Models | ✅ COMPLETE (5/5) | 🔴 HIGH | 2-3 hours |
| **Phase 2** | Services & Business Logic | ✅ COMPLETE (3/3) | 🔴 HIGH | 2 hours |
| **Phase 3** | Livewire Components | 🔴 Not Started | 🟡 MEDIUM | 3 hours |
| **Phase 4** | UI & Integration | 🔴 Not Started | 🟡 MEDIUM | 2 hours |
| **Phase 5** | Testing & Polish | 🔴 Not Started | 🟢 LOW | 1 hour |

**Total Estimated Time**: 10-11 hours  
**Expected Completion**: October 14-15, 2025

---

## 🔴 PHASE 1: Database & Models (HIGH PRIORITY)

### Task 1.1: Create Activities Migration
**Priority**: 🔴 CRITICAL  
**Estimated Time**: 30 minutes  
**Status**: ⬜ Not Started

#### Objectives
- [ ] Create activities table migration
- [ ] Apply 5-index standard (Task 1.1 template)
- [ ] Add proper foreign keys
- [ ] Add soft deletes

#### Implementation Steps
```bash
php artisan make:migration create_activities_table --path=database/migrations/modules/sales-crm
```

#### Table Schema
```php
- id (PK)
- business_unit_id (FK, indexed)
- user_id (FK, indexed - sales person)
- contact_id (FK, nullable, indexed)
- activity_date (date, indexed)
- activity_type (enum: call, visit, meeting, blitz, follow_up, other)
- title (string)
- description (text, nullable)
- location (string, nullable)
- start_time (time, nullable)
- end_time (time, nullable)
- duration_minutes (integer, nullable)
- result (enum: success, follow_up_needed, no_answer, rejected, other)
- notes (text, nullable)
- status (enum: planned, completed, cancelled)
- timestamps
- soft_deletes
```

#### Indexes (5-index standard)
```sql
1. idx_activities_user_status_date (user_id, status, activity_date)
2. idx_activities_bu_status_date (business_unit_id, status, activity_date)
3. idx_activities_contact_date (contact_id, activity_date)
4. idx_activities_type_status (activity_type, status)
5. idx_activities_date_status (activity_date, status)
```

#### Testing Checklist
- [ ] Migration runs successfully
- [ ] All indexes created
- [ ] Foreign keys work correctly
- [ ] Soft delete works

#### Files to Create
- `database/migrations/modules/sales-crm/YYYY_MM_DD_HHMMSS_create_activities_table.php`

---

### Task 1.2: Create Contacts Migration
**Priority**: 🔴 CRITICAL  
**Estimated Time**: 30 minutes  
**Status**: ⬜ Not Started

#### Objectives
- [ ] Create contacts table migration
- [ ] Apply 5-index standard
- [ ] Add unique constraint on code
- [ ] Add soft deletes

#### Implementation Steps
```bash
php artisan make:migration create_contacts_table --path=database/migrations/modules/sales-crm
```

#### Table Schema
```php
- id (PK)
- business_unit_id (FK, indexed)
- created_by (FK - user who added)
- assigned_to (FK, nullable - current owner)
- code (string, unique - CONT-WNS-25-00001)
- name (string)
- email (string, nullable)
- phone (string, nullable)
- mobile (string, nullable)
- birth_date (date, nullable)
- company (string, nullable, indexed)
- department (string, nullable)
- position (string, nullable)
- social_media (json, nullable)
- status (enum: active, inactive, archived)
- category (enum: lead, prospect, customer, partner)
- address (text, nullable)
- notes (text, nullable)
- timestamps
- soft_deletes
```

#### Indexes (5-index standard)
```sql
1. idx_contacts_bu_status_created (business_unit_id, status, created_at)
2. idx_contacts_assigned_status (assigned_to, status)
3. idx_contacts_company_status (company, status)
4. idx_contacts_category_status (category, status)
5. UNIQUE idx_contacts_code (code)
```

#### Testing Checklist
- [ ] Migration runs successfully
- [ ] All indexes created
- [ ] Unique code constraint works
- [ ] Soft delete works

#### Files to Create
- `database/migrations/modules/sales-crm/YYYY_MM_DD_HHMMSS_create_contacts_table.php`

---

### Task 1.3: Create Contact Sources Migration
**Priority**: 🔴 CRITICAL  
**Estimated Time**: 20 minutes  
**Status**: ⬜ Not Started

#### Objectives
- [ ] Create contact_sources table migration
- [ ] Link to contacts and activities
- [ ] Add indexes for source tracking

#### Implementation Steps
```bash
php artisan make:migration create_contact_sources_table --path=database/migrations/modules/sales-crm
```

#### Table Schema
```php
- id (PK)
- contact_id (FK, indexed)
- source_type (enum: activity, manual, import, referral, website, event)
- source_activity_id (FK, nullable)
- activity_type (string, nullable - denormalized)
- source_user_id (FK, nullable)
- source_notes (string, nullable)
- source_date (date)
- timestamps
```

#### Indexes
```sql
1. idx_contact_sources_contact_type (contact_id, source_type)
2. idx_contact_sources_activity (source_activity_id)
3. idx_contact_sources_user (source_user_id)
4. idx_contact_sources_type_date (source_type, source_date)
```

#### Testing Checklist
- [ ] Migration runs successfully
- [ ] Foreign keys work
- [ ] Can track both activity and manual sources

#### Files to Create
- `database/migrations/modules/sales-crm/YYYY_MM_DD_HHMMSS_create_contact_sources_table.php`

---

### Task 1.4: Create Company Visit History Migration
**Priority**: 🟡 MEDIUM  
**Estimated Time**: 20 minutes  
**Status**: ⬜ Not Started

#### Objectives
- [ ] Create company_visit_history table migration
- [ ] Add unique constraint (BU + company + department)
- [ ] Add analytics indexes

#### Implementation Steps
```bash
php artisan make:migration create_company_visit_history_table --path=database/migrations/modules/sales-crm
```

#### Table Schema
```php
- id (PK)
- business_unit_id (FK, indexed)
- company_name (string, indexed)
- department (string, nullable)
- activity_id (FK - last activity)
- contact_id (FK, nullable - last contact)
- user_id (FK - last sales person)
- last_visit_at (datetime)
- total_visits (integer, default 1)
- timestamps
```

#### Indexes
```sql
1. idx_cvh_company_dept (company_name, department)
2. idx_cvh_bu_last_visit (business_unit_id, last_visit_at)
3. UNIQUE idx_cvh_unique (business_unit_id, company_name, department)
```

#### Testing Checklist
- [ ] Migration runs successfully
- [ ] Unique constraint prevents duplicates
- [ ] Can track visit history

#### Files to Create
- `database/migrations/modules/sales-crm/YYYY_MM_DD_HHMMSS_create_company_visit_history_table.php`

---

### Task 1.5: Create Models with Relationships
**Priority**: 🔴 CRITICAL  
**Estimated Time**: 45 minutes  
**Status**: ⬜ Not Started

#### Objectives
- [ ] Create Activity model with relationships
- [ ] Create Contact model with relationships
- [ ] Create ContactSource model
- [ ] Create CompanyVisitHistory model
- [ ] Add factories for testing

#### Implementation Steps
```bash
# Create models with factory
php artisan make:model Models/Modules/SalesCrm/Activity --factory
php artisan make:model Models/Modules/SalesCrm/Contact --factory
php artisan make:model Models/Modules/SalesCrm/ContactSource --factory
php artisan make:model Models/Modules/SalesCrm/CompanyVisitHistory --factory
```

#### Activity Model Relationships
```php
- belongsTo: user, businessUnit, contact
- hasOne: companyVisitHistory
```

#### Contact Model Relationships
```php
- belongsTo: businessUnit, createdBy (User), assignedTo (User)
- hasMany: activities
- hasOne: source (ContactSource)
```

#### ContactSource Model Relationships
```php
- belongsTo: contact, sourceActivity (Activity), sourceUser (User)
```

#### Testing Checklist
- [ ] All models created
- [ ] Relationships work correctly
- [ ] Factories generate valid data
- [ ] Can eager load relationships

#### Files to Create
- `app/Models/Modules/SalesCrm/Activity.php`
- `app/Models/Modules/SalesCrm/Contact.php`
- `app/Models/Modules/SalesCrm/ContactSource.php`
- `app/Models/Modules/SalesCrm/CompanyVisitHistory.php`
- `database/factories/ActivityFactory.php`
- `database/factories/ContactFactory.php`
- `database/factories/ContactSourceFactory.php`

---

## 🔴 PHASE 2: Services & Business Logic (HIGH PRIORITY)

### Task 2.1: Create Activity Service
**Priority**: 🔴 CRITICAL  
**Estimated Time**: 45 minutes  
**Status**: ⬜ Not Started

#### Objectives
- [ ] Create ActivityService class
- [ ] Implement CRUD operations
- [ ] Implement activity with contact creation
- [ ] Implement company visit history update

#### Implementation Steps
```bash
php artisan make:class Services/Modules/SalesCrm/ActivityService
```

#### Key Methods
```php
- createActivity(array $data): Activity
- createActivityWithContact(array $activityData, array $contactData): Activity
- updateActivity(Activity $activity, array $data): Activity
- deleteActivity(Activity $activity): bool
- getActivitiesForUser(User $user, array $filters = []): Collection
- updateCompanyVisitHistory(Contact $contact, Activity $activity): void
```

#### Testing Checklist
- [ ] Can create activity without contact
- [ ] Can create activity with new contact
- [ ] Can create activity with existing contact
- [ ] Company visit history updates correctly
- [ ] Uses transactions properly

#### Files to Create
- `app/Services/Modules/SalesCrm/ActivityService.php`

---

### Task 2.2: Create Contact Service
**Priority**: 🔴 CRITICAL  
**Estimated Time**: 45 minutes  
**Status**: ⬜ Not Started

#### Objectives
- [ ] Create ContactService class
- [ ] Implement CRUD operations
- [ ] Implement contact code generation
- [ ] Implement manual contact creation with source tracking

#### Implementation Steps
```bash
php artisan make:class Services/Modules/SalesCrm/ContactService
```

#### Key Methods
```php
- createManualContact(array $data): Contact
- updateContact(Contact $contact, array $data): Contact
- deleteContact(Contact $contact): bool
- generateContactCode(int $businessUnitId): string
- assignContact(Contact $contact, User $user): Contact
- getContactsForUser(User $user, array $filters = []): Collection
```

#### Testing Checklist
- [ ] Contact code generates correctly (CONT-WNS-25-00001)
- [ ] Manual contact creates source record
- [ ] Can assign contact to different user
- [ ] Filters work correctly

#### Files to Create
- `app/Services/Modules/SalesCrm/ContactService.php`

---

### Task 2.3: Update User Model
**Priority**: 🟡 MEDIUM  
**Estimated Time**: 15 minutes  
**Status**: ⬜ Not Started

#### Objectives
- [ ] Add Sales role helper methods
- [ ] Add activity relationships
- [ ] Add contact relationships

#### Code Changes
```php
// Add to User model
public function isSales(): bool
public function canManageSalesCRM(): bool
public function activities(): HasMany
public function assignedContacts(): HasMany
public function createdContacts(): HasMany
```

#### Testing Checklist
- [ ] Role helpers work
- [ ] Relationships work
- [ ] Can eager load activities and contacts

#### Files to Modify
- `app/Models/User.php`

---

## 🟡 PHASE 3: Livewire Components (MEDIUM PRIORITY)

### Task 3.1: Create Activity Index Component
**Priority**: 🟡 MEDIUM  
**Estimated Time**: 45 minutes  
**Status**: ⬜ Not Started

#### Objectives
- [ ] Create Activity Index Livewire component
- [ ] Use HasFilters trait
- [ ] Use HasLazyLoading trait
- [ ] Implement filtering (date, type, status)

#### Implementation Steps
```bash
php artisan make:livewire Modules/SalesCrm/Activities/Index
```

#### Features
- List activities with pagination
- Filter by date range, activity type, status
- Filter by contact (if viewing from contact page)
- Eager load relationships (user, contact)
- Use loading skeleton

#### Testing Checklist
- [ ] Activities list loads
- [ ] Filters work correctly
- [ ] Pagination works
- [ ] Loading states display

#### Files to Create
- `app/Livewire/Modules/SalesCrm/Activities/Index.php`
- `resources/views/livewire/modules/sales-crm/activities/index.blade.php`

---

### Task 3.2: Create Activity Create/Edit Component
**Priority**: 🟡 MEDIUM  
**Estimated Time**: 1 hour  
**Status**: ⬜ Not Started

#### Objectives
- [ ] Create Activity Create Livewire component
- [ ] Form for activity data
- [ ] Optional contact creation
- [ ] Contact search/select

#### Implementation Steps
```bash
php artisan make:livewire Modules/SalesCrm/Activities/Create
```

#### Features
- Activity form (date, type, title, description, location)
- Time tracking (start, end, duration auto-calculate)
- Contact selection (existing or create new)
- Contact form (toggle to show/hide)
- Real-time validation

#### Testing Checklist
- [ ] Can create activity without contact
- [ ] Can create activity with new contact
- [ ] Can create activity with existing contact
- [ ] Validation works
- [ ] Duration auto-calculates

#### Files to Create
- `app/Livewire/Modules/SalesCrm/Activities/Create.php`
- `resources/views/livewire/modules/sales-crm/activities/create.blade.php`

---

### Task 3.3: Create Contact Index Component
**Priority**: 🟡 MEDIUM  
**Estimated Time**: 45 minutes  
**Status**: ⬜ Not Started

#### Objectives
- [ ] Create Contact Index Livewire component
- [ ] Use HasFilters trait
- [ ] Implement search and filtering

#### Implementation Steps
```bash
php artisan make:livewire Modules/SalesCrm/Contacts/Index
```

#### Features
- List contacts with pagination
- Search by name, company, phone
- Filter by category (lead, prospect, customer)
- Filter by status (active, inactive)
- Show source badge
- Eager load relationships

#### Testing Checklist
- [ ] Contacts list loads
- [ ] Search works
- [ ] Filters work
- [ ] Source badges display correctly

#### Files to Create
- `app/Livewire/Modules/SalesCrm/Contacts/Index.php`
- `resources/views/livewire/modules/sales-crm/contacts/index.blade.php`

---

### Task 3.4: Create Contact Create/Edit Component
**Priority**: 🟡 MEDIUM  
**Estimated Time**: 45 minutes  
**Status**: ⬜ Not Started

#### Objectives
- [ ] Create Contact Create Livewire component
- [ ] Manual contact form
- [ ] Social media inputs (JSON)

#### Implementation Steps
```bash
php artisan make:livewire Modules/SalesCrm/Contacts/Create
```

#### Features
- Contact form (name, email, phone, company, etc)
- Social media inputs (LinkedIn, Instagram, Facebook)
- Birth date picker
- Source notes field
- Real-time validation

#### Testing Checklist
- [ ] Can create manual contact
- [ ] Social media JSON saves correctly
- [ ] Source record created
- [ ] Validation works

#### Files to Create
- `app/Livewire/Modules/SalesCrm/Contacts/Create.php`
- `resources/views/livewire/modules/sales-crm/contacts/create.blade.php`

---

### Task 3.5: Create Contact Show Component
**Priority**: 🟡 MEDIUM  
**Estimated Time**: 1 hour  
**Status**: ⬜ Not Started

#### Objectives
- [ ] Create Contact Show Livewire component
- [ ] Display contact profile
- [ ] Show activity history
- [ ] Show company visit history

#### Implementation Steps
```bash
php artisan make:livewire Modules/SalesCrm/Contacts/Show
```

#### Features
- Contact profile card
- Source information badge
- Activity history (timeline view)
- Company visit stats
- Social media links
- Quick actions (edit, assign, archive)

#### Testing Checklist
- [ ] Contact profile displays
- [ ] Activity history loads
- [ ] Source badge shows correctly
- [ ] Company visit history displays

#### Files to Create
- `app/Livewire/Modules/SalesCrm/Contacts/Show.php`
- `resources/views/livewire/modules/sales-crm/contacts/show.blade.php`

---

## 🟡 PHASE 4: UI & Integration (MEDIUM PRIORITY)

### Task 4.1: Create Routes
**Priority**: 🟡 MEDIUM  
**Estimated Time**: 15 minutes  
**Status**: ⬜ Not Started

#### Objectives
- [ ] Add CRM routes to web.php
- [ ] Add permission middleware
- [ ] Group routes by module

#### Code Changes
```php
// In routes/web.php
Route::middleware(['auth', 'permission:view_activities'])->group(function () {
    Route::get('/activities', \App\Livewire\Modules\SalesCrm\Activities\Index::class)
        ->name('activities.index');
    Route::get('/activities/create', \App\Livewire\Modules\SalesCrm\Activities\Create::class)
        ->name('activities.create');
    
    Route::get('/contacts', \App\Livewire\Modules\SalesCrm\Contacts\Index::class)
        ->name('contacts.index');
    Route::get('/contacts/create', \App\Livewire\Modules\SalesCrm\Contacts\Create::class)
        ->name('contacts.create');
    Route::get('/contacts/{contact}', \App\Livewire\Modules\SalesCrm\Contacts\Show::class)
        ->name('contacts.show');
});
```

#### Testing Checklist
- [ ] Routes work
- [ ] Permission middleware blocks non-sales users
- [ ] Named routes work

#### Files to Modify
- `routes/web.php`

---

### Task 4.2: Update Navigation Menu
**Priority**: 🟡 MEDIUM  
**Estimated Time**: 15 minutes  
**Status**: ⬜ Not Started

#### Objectives
- [ ] Add CRM menu items
- [ ] Add permission checks
- [ ] Add icons

#### Code Changes
```blade
{{-- In resources/views/layouts/navigation.blade.php --}}
@can('view_activities')
<li>
    <a href="{{ route('activities.index') }}">
        <svg><!-- calendar icon --></svg>
        Daily Activities
    </a>
</li>
<li>
    <a href="{{ route('contacts.index') }}">
        <svg><!-- users icon --></svg>
        Contacts
    </a>
</li>
@endcan
```

#### Testing Checklist
- [ ] Menu items display for sales users
- [ ] Menu items hidden for non-sales users
- [ ] Active state works

#### Files to Modify
- `resources/views/layouts/navigation.blade.php`

---

### Task 4.3: Create Permissions & Roles
**Priority**: 🔴 CRITICAL  
**Estimated Time**: 20 minutes  
**Status**: ⬜ Not Started

#### Objectives
- [ ] Update RoleSeeder with Sales permissions
- [ ] Add Sales role
- [ ] Test permission assignments

#### Implementation Steps
```bash
php artisan make:seeder SalesCrmPermissionSeeder
```

#### Permissions to Create
```php
'view_activities',
'create_activities',
'edit_activities',
'delete_activities',
'view_contacts',
'create_contacts',
'edit_contacts',
'delete_contacts',
'manage_sales_crm', // Admin only
```

#### Testing Checklist
- [ ] Seeder runs successfully
- [ ] Sales role has correct permissions
- [ ] Admin has CRM management permission
- [ ] Regular users don't have CRM access

#### Files to Create/Modify
- `database/seeders/SalesCrmPermissionSeeder.php`
- Update `database/seeders/DatabaseSeeder.php`

---

### Task 4.4: Create Dashboard Widgets
**Priority**: 🟢 LOW  
**Estimated Time**: 30 minutes  
**Status**: ⬜ Not Started

#### Objectives
- [ ] Add "Today's Activities" widget
- [ ] Add "Recent Contacts" widget
- [ ] Add "Top Companies" widget

#### Implementation Steps
- Add widgets to Sales dashboard
- Use reusable components
- Add to Dashboard/UserDashboard.php

#### Testing Checklist
- [ ] Widgets display for sales users
- [ ] Data loads correctly
- [ ] Widgets hidden for non-sales users

#### Files to Modify
- `app/Livewire/Dashboard/UserDashboard.php`
- `resources/views/livewire/dashboard/user-dashboard.blade.php`

---

## 🟢 PHASE 5: Testing & Polish (LOW PRIORITY)

### Task 5.1: Create Feature Tests
**Priority**: 🟢 LOW  
**Estimated Time**: 30 minutes  
**Status**: ⬜ Not Started

#### Objectives
- [ ] Test activity CRUD
- [ ] Test contact CRUD
- [ ] Test permissions
- [ ] Test source tracking

#### Implementation Steps
```bash
php artisan make:test Feature/SalesCrm/ActivityTest
php artisan make:test Feature/SalesCrm/ContactTest
php artisan make:test Feature/SalesCrm/PermissionTest
```

#### Testing Checklist
- [ ] All CRUD operations work
- [ ] Permissions enforced
- [ ] Source tracking works
- [ ] Company visit history updates

#### Files to Create
- `tests/Feature/SalesCrm/ActivityTest.php`
- `tests/Feature/SalesCrm/ContactTest.php`
- `tests/Feature/SalesCrm/PermissionTest.php`

---

### Task 5.2: Code Formatting & Optimization
**Priority**: 🟢 LOW  
**Estimated Time**: 15 minutes  
**Status**: ⬜ Not Started

#### Objectives
- [ ] Run Laravel Pint
- [ ] Build assets
- [ ] Clear caches

#### Commands
```bash
vendor/bin/pint
npm run build
php artisan optimize:clear
```

#### Testing Checklist
- [ ] No linting errors
- [ ] Assets compiled
- [ ] Caches cleared

---

### Task 5.3: Documentation
**Priority**: 🟢 LOW  
**Estimated Time**: 15 minutes  
**Status**: ⬜ Not Started

#### Objectives
- [ ] Update README with CRM features
- [ ] Update DEVELOPER-GUIDE
- [ ] Create API documentation (if needed)

#### Files to Update
- `README.md`
- `DEVELOPER-GUIDE-v2.5.md`
- `docs/v2.5-SALES-CRM-PLANNING.md`

---

## 🎯 Success Metrics

### Must-Have (Minimum Viable Product)
- [ ] Can create activities (with/without contact)
- [ ] Can create contacts manually
- [ ] Source tracking works (activity vs manual)
- [ ] Activity history visible in contact profile
- [ ] Permission system works (sales only access)
- [ ] Business unit scoped (multi-BU support)

### Nice-to-Have
- [ ] Company visit history tracking
- [ ] Dashboard widgets
- [ ] Export features (Excel/PDF)
- [ ] Bulk import contacts

### Optional (Future Enhancements)
- [ ] Mobile app integration
- [ ] Email/SMS integration
- [ ] Advanced analytics dashboard
- [ ] Lead scoring system

---

## 🚀 Deployment Checklist

### Pre-Deployment
- [ ] All tests passing
- [ ] Code formatted (Pint)
- [ ] Assets built (npm run build)
- [ ] Migrations ready
- [ ] Seeders ready

### Deployment Steps
1. [ ] Backup database
2. [ ] Upload files to hosting
3. [ ] Run migrations: `php artisan migrate --force`
4. [ ] Run seeders: `php artisan db:seed --class=SalesCrmPermissionSeeder`
5. [ ] Clear caches: `php artisan optimize:clear`
6. [ ] Build cache: `php artisan config:cache && php artisan route:cache`

### Post-Deployment
- [ ] Test activity creation
- [ ] Test contact creation
- [ ] Test permissions
- [ ] Check error logs
- [ ] User acceptance testing

---

## 📝 Notes & Blockers

### Current Blockers
_None yet_

### Decisions Made
1. ✅ 2 separate modules (Activity + Contact) with relationships
2. ✅ Dedicated `contact_sources` table for tracking
3. ✅ Sales role gets PR access + CRM access (not CRM only)
4. ✅ Multi-BU architecture (business_unit_id everywhere)
5. ✅ 5-index standard for all tables (performance first)

### Questions to Resolve
_None yet_

---

## 🔄 Progress Tracking

**Start Date**: October 13, 2025  
**Current Phase**: Phase 3 (Livewire Components) - Ready to start  
**Completion**: 40% (Phase 1 + Phase 2 done)

**Daily Updates**:
- **Oct 13, 2025 - 13:00**: Task list created, ready to start Phase 1
- **Oct 13, 2025 - 13:30**: ✅ Phase 1 COMPLETE - All 4 migrations + 4 models created with relationships
- **Oct 13, 2025 - 14:00**: ✅ Phase 2 COMPLETE - ActivityService, ContactService, User model updated

---

**Ready to start? Let's begin with Task 1.1! 🚀**
