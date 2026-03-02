# AI Coding Agent Instructions - Numbering System

## System Overview
This is an enterprise **Purchase Request Management System** built with Laravel 12 + Livewire 3, specifically for WNS (Werkudara Nusantara Sejahtera) business operations. The system handles multi-level approval workflows with hierarchical organizational structure.

**Environment Context:**
- **Development**: Windows (case-insensitive filesystem)
- **Production**: Linux hosting at devlopment.werkudara.com (case-sensitive filesystem) 
- **Critical**: Always follow proper Laravel naming conventions for cross-platform compatibility
- **Current Version**: v.2.2 (October 2025) - Phase 1+2 Performance Optimization COMPLETE + 5 critical bug fixes

## Performance Optimization Status (v.2.2)

### Phase 1: COMPLETE ✅ (October 2025)
**Achievement**: 95-97% query reduction, 83-85% faster load times

#### Task 1.1: Database Performance Indexes ✅
- **15 indexes created** (7 core + 8 supplementary)
- **Query improvement**: 60-95% faster execution
- **Files**: `database/migrations/*_add_performance_indexes_*.php`
- **Documentation**: `database/migrations/README-INDEX-STANDARDS.md` (templates for future modules)

#### Task 1.2: Dashboard N+1 Optimization ✅
- **Query reduction**: 40+ → 10 queries (75% reduction)
- **Load time**: ~150ms → ~50ms (67% faster)
- **Pattern**: Eager loading with `with()`, relationship optimization
- **File**: `app/Livewire/Dashboard/UserDashboard.php`

#### Task 1.3: Dashboard Caching ✅
- **Cache hit improvement**: 48.8% query reduction
- **Average load time**: ~25ms (83% faster)
- **Strategy**: Database cache driver (Redis recommended for production)
- **Cache TTLs**: Stats/Charts (5min), Activities (1min), BUs (60min)
- **File**: `app/Livewire/Dashboard/UserDashboard.php`

#### Task 1.4: Business Unit Switcher Optimization ✅
- **Query reduction**: 75% (4 → 1 queries on cache hit)
- **Hydrate optimization**: 100% (0 queries when session unchanged)
- **UX improvement**: Stay on current page (no redirect)
- **Event architecture**: Unified `business-unit-switched` event
- **File**: `app/Livewire/Components/BusinessUnitSwitcher.php`

### Critical Bug Fixes (v.2.1) ✅
**All 4 bugs resolved** - 100% console error elimination

#### Bug #1: Navbar Not Updating After BU Switch
- **Problem**: Property `$currentBusinessUnit` not updated after session change
- **Fix**: Added immediate property update in `switchBusinessUnit()` method
- **File**: `app/Livewire/Components/BusinessUnitSwitcher.php` (line ~135)

#### Bug #2: Livewire Snapshot Missing (Self-Refresh Conflict)
- **Problem**: Double refresh causing race condition (component + page)
- **Fix**: Removed `$this->dispatch('$refresh')` call
- **File**: `app/Livewire/Components/BusinessUnitSwitcher.php` (line ~145)

#### Bug #3: Event Name Mismatch (Dashboard vs Navbar)
- **Problem**: Dashboard emitted `business-unit-changed`, switcher emitted `business-unit-switched`
- **Fix**: Unified to `business-unit-switched` + added bidirectional listeners
- **Files**: `UserDashboard.php` (line ~156), `BusinessUnitSwitcher.php` (lines ~20, ~235)

#### Bug #4: Dynamic wire:key Anti-Pattern
- **Problem**: `wire:key` used dynamic values causing component identity change
- **Fix**: Changed to static `wire:key="bu-switcher-{{ auth()->id() }}"`
- **Files**: `business-unit-switcher.blade.php`, `app.blade.php`
- **Documentation**: `BUGFIX-WIRE-KEY-ISSUE.md`

#### Bug #5: Dashboard Tidak Sinkron Setelah Switch BU (v.2.2) ✅
- **Problem**: Race condition - Livewire property belum terupdate saat event handler load data
- **Root Cause**: `handleBusinessUnitSwitch()` update property first, then load data → property not hydrated yet → query pakai BU ID lama
- **Fix**: 
  1. Update session FIRST (single source of truth)
  2. Then update property (for UI binding)
  3. Then load data (reads from session, guaranteed fresh)
  4. `getFilteredBusinessUnitIds()` always reads from session first: `session('current_business_unit_id') ?? $this->activeBusinessUnitId`
- **Pattern**: **Session as single source of truth** for Livewire event handlers
- **File**: `app/Livewire/Dashboard/UserDashboard.php` (lines ~207-235, ~250-260)
- **Documentation**: `BUGFIX-DASHBOARD-SYNC-ISSUE.md`

### Phase 2: ✅ COMPLETE (Frontend & Livewire Optimization)
- Task 2.1: Asset loading optimization (lazy load Chart.js) ✅
- Task 2.2: Livewire partial updates (debouncing, wire:target) ✅
- Task 2.3: Lazy loading components (pagination, virtual scrolling) ✅
- **Bonus**: 4 reusable components created (85% code reuse for future modules)

## Email Notification System (v.2.5-beta) ✅

### Implementation Complete (November 26-27, 2025)
Comprehensive email notification system with database fallback, admin configuration panel, and public approval links.

#### Core Features
**EmailNotificationService** (`app/Services/Core/EmailNotificationService.php`):
- Dynamic SMTP configuration from database
- Synchronous email sending (no queue workers required)
- Automatic database fallback (notifications always saved)
- Settings caching (3600s TTL)
- Test email functionality

**Notification Classes** (`app/Notifications/PurchaseRequest/`):
- `ApprovalRequested` - Sent to approver with 3-day signed URL
- `ApprovalCompleted` - Sent when all approvals complete  
- `ApprovalRejected` - Sent to requestor with rejection reason
- **DELETED**: `ApprovalApproved` - Dead code, never instantiated (161 lines removed)

**Architecture**: 3 Notification Classes → 3 Email Templates (1:1 mapping)
- Each notification has corresponding Blade template
- All use dual channels: `['mail', 'database']`
- English language, professional tone
- Mobile-responsive design

#### Admin Configuration Panel
**Route**: `/admin/notification-settings` (Super Admin only)
- SMTP configuration (host, port, username, encrypted password, encryption)
- Email settings (FROM address/name)
- Notification options (enable/disable, fallback toggle, link expiry)
- Test email functionality
- Statistics dashboard (total sent, failed, success rate)

#### Public Approval Links
**Routes**: `/approvals/{approval}/public`
- Signed URLs with 3-day expiry (configurable)
- Rate limiting: `throttle:5,1`
- No authentication required
- One-time use validation
- Approval/rejection with notes

#### Security Features
- SMTP password encrypted with `Crypt::encrypt()`
- Signed URL validation (Laravel native)
- Rate limiting on public endpoints
- Approval status validation (pending only)
- PR status validation (in_approval only)

#### Bug Fixes (v.2.5-beta)
**Bug #6: Null Pointer Dereference Issues (25+ locations)** ✅
- **Files Affected**: EmailNotificationService, ApprovalWorkflowService, all email templates, notification classes
- **Pattern**: Accessing relationship properties without null checks (e.g., `$approval->approver->name`)
- **Solution**: Applied PHP 8 nullsafe operator `?->` with null coalescing `??` fallbacks
- **Example**: `$approval->approver?->name ?? 'Unknown Approver'`

**Bug #7: Legacy Dead Code - ApprovalApproved Files** ✅
- **Problem**: ApprovalApproved notification class and template existed but never used
- **Evidence**: 0 database records, `sendApprovalApproved()` never instantiates it
- **Solution**: Deleted `ApprovalApproved.php` (67 lines) + `approval-approved.blade.php` (94 lines)
- **Result**: Clean 1:1 mapping between notification classes and email templates

**Bug #8: Database Query in View** ✅
- **Problem**: `approval-completed.blade.php` had `@php $approvals = $pr->approvals()->get(); @endphp`
- **Risk**: Violates MVC pattern, harder to test
- **Solution**: Moved query to `ApprovalCompleted::toMail()`, pass as view parameter
- **File**: `app/Notifications/PurchaseRequest/ApprovalCompleted.php` lines 32-44

**Bug #9: UTF-8 BOM in public-approval.blade.php** ✅
- **Problem**: File started with UTF-8 BOM (bytes EF BB BF)
- **Risk**: Extra whitespace before HTML, HTTP header issues
- **Solution**: Re-saved file as UTF-8 without BOM
- **File**: `resources/views/approvals/public-approval.blade.php`

**Bug #10: Duplicate HTML style Attribute** ✅
- **Problem**: `<span style="color: #ef4444;" id="required-indicator" style="display: none;">`
- **Solution**: Merged into single attribute: `style="color: #ef4444; display: none;"`
- **File**: `resources/views/approvals/public-approval.blade.php` line 805

**Bug #11: Rate Limiting Missing on Test Endpoint** ✅
- **Problem**: `/notification-settings/test` POST route lacked rate limiting
- **Solution**: Added `->middleware('throttle:3,1')` (max 3 requests per minute)
- **File**: `routes/web.php` line 157-159

**Bug #12: Undefined Variable $color in public-error.blade.php** ✅
- **Problem**: Variable `$color` not provided, causing "Undefined variable" error
- **Solution**: Added `@php $color = $color ?? 'gray'; @endphp` default value
- **File**: `resources/views/approvals/public-error.blade.php` line 22

**Bug #13: Route [approvals] Not Defined in Public Context** ✅
- **Problem**: `public-success.blade.php` used `route('dashboard')` and `route('approvals')` which require authentication
- **Risk**: Public approver (not logged in) gets "Route not defined" error
- **Solution**: Removed authenticated routes, replaced with single `config('app.url')` button "Close This Page"
- **File**: `resources/views/approvals/public-success.blade.php` line 143-157
- **Why**: Public approvers are external users without system access

#### Key Files
**Services**:
- `app/Services/Core/EmailNotificationService.php` - Central email service
- `app/Services/Modules/PurchaseRequest/ApprovalWorkflowService.php` - Integrated email notifications

**Notifications**:
- `app/Notifications/PurchaseRequest/ApprovalRequested.php`
- `app/Notifications/PurchaseRequest/ApprovalCompleted.php`
- `app/Notifications/PurchaseRequest/ApprovalRejected.php`

**Email Templates**:
- `resources/views/emails/purchase-request/approval-requested.blade.php`
- `resources/views/emails/purchase-request/approval-completed.blade.php`
- `resources/views/emails/purchase-request/approval-rejected.blade.php`
- `resources/views/emails/layouts/email.blade.php` - Shared email layout

**Public Approval**:
- `resources/views/approvals/public-approval.blade.php` - Approval form
- `resources/views/approvals/public-success.blade.php` - Success confirmation
- `resources/views/approvals/public-error.blade.php` - Error page

**Admin Panel**:
- `app/Http/Controllers/Admin/NotificationSettingsController.php`
- `app/Models/Core/NotificationSetting.php` (encrypted password)
- `resources/views/admin/notification-settings/index.blade.php`

**Routes** (`routes/web.php`):
- Lines 16-23: Public routes (no auth)
- Lines 26-34: Public approval routes (signed URLs)
- Lines 134-160: Admin notification settings (Super Admin only)

#### Configuration
**SMTP Settings** (stored in database, not `.env`):
```env
MAIL_MAILER=smtp
MAIL_HOST=uranus.webmail.co.id
MAIL_PORT=587
MAIL_USERNAME=it@werkudara.com
MAIL_PASSWORD=[encrypted in database]
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="it@werkudara.com"
MAIL_FROM_NAME="Werkudara - Purchase Request System"
```

**Notification Options**:
- Email enabled: true/false
- Database fallback: true/false (always recommended)
- Link expiry: 1-14 days (default 3)
- Cache TTL: 3600 seconds (1 hour)

#### Testing
All notification flows tested and verified:
- ✅ PR submission → Approver receives email
- ✅ Public approval link works (no authentication)
- ✅ Approval → Next approver notified OR requestor notified if final
- ✅ Rejection → Requestor receives rejection email
- ✅ All approvals complete → Requestor receives completion email
- ✅ SMTP failure → Database notification still saved (fallback)
- ✅ Expired link → Proper error page shown
- ✅ Double approval prevented → Shows "Already Processed"

#### Performance
- Email send time: 2-5 seconds (synchronous, 5s timeout)
- Settings cached: 100% hit rate after first load (1-hour TTL)
- No N+1 queries: All relationships eager loaded
- Database fallback: Always saves notification (zero data loss)

## Core Architecture Patterns

### 1. Service-Oriented Architecture
- **Naming Services**: `app/Services/Modules/PurchaseRequest/UniversalPRNumberingService.php` - Handles sequential PR number generation per business unit
- **Workflow Services**: `app/Services/Modules/PurchaseRequest/ApprovalWorkflowService.php` - Core approval engine with rule-based approver assignment
- **QR Services**: `app/Services/Core/QrCodeService.php` - PDF verification and tracking

### 2. Livewire Hybrid Architecture (Critical Pattern)
**Performance-optimized pattern using client-side calculations:**
```php
// Use wire:model.blur instead of wire:model.live for forms
wire:model.blur="items.{{ $index }}.quantity" 
// + JavaScript calculateRowTotal() for instant feedback
oninput="calculateRowTotal({{ $index }});"
```
- Files: `resources/views/livewire/modules/purchase-request/create.blade.php`
- **Why**: Reduces server requests by 90%, prevents input lag during fast typing
- **Pattern**: Client-side JS for instant feedback + server-side validation on blur

### 3. Modular Domain Structure
```
app/Models/Modules/PurchaseRequest/
├── PurchaseRequest.php       # Main entity with workflow states
├── PrItem.php               # Line items with expense department tracking  
├── PrApproval.php           # Approval step tracking
└── PrNumberReservation.php  # PR number reservation

app/Services/Modules/PurchaseRequest/
├── PurchaseRequestService.php        # PR business logic
├── ApprovalWorkflowService.php       # Approval engine
└── UniversalPRNumberingService.php   # PR numbering system

app/Livewire/Modules/PurchaseRequest/
└── Create.php         # Main PR creation component (1864 lines)
```

## Business Logic Essentials

### Approval Workflow Rules (ApprovalWorkflowService)
```php
// Amount-based approval hierarchy
if ($amount > 500000) $deptHead = getDepartmentHead();
if ($amount > 1000000) $financeManager = getFinanceManager();  
if ($amount > 5000000) $generalManager = getGeneralManager();
if ($amount > 10000000) $director = getDirector();
```

### Session Context Pattern
**Critical**: All operations require business unit context:
```php
session(['current_business_unit_id' => $businessUnit->id]);
// Used throughout controllers and Livewire components
```

### PR Status Lifecycle
`draft` → `submitted` → `in_approval` → `approved`/`rejected`/`voided`

## Development Workflows

### Database Operations
```bash
# Migration pattern for Purchase Request module
php artisan make:migration create_purchase_requests_table --path=database/migrations/modules/purchase-request

# Always use transactions for PR operations
DB::beginTransaction();
// ... operations
DB::commit();
```

### Testing Pattern  
```bash
php artisan test --filter=PurchaseRequestWorkflowTest
# Key test: tests/Feature/PurchaseRequestWorkflowTest.php
```

### Asset Building (Performance-critical)
```bash
npm run build  # Required after Livewire view changes
php artisan config:cache && php artisan route:cache
```

## UI/UX Conventions

### Scrollbar Stability (v.2)
**Critical**: Content must NOT shift horizontally when scrollbar appears/disappears
```css
/* In resources/css/app.css */
html {
    overflow-y: scroll;           /* Always show scrollbar space */
    scrollbar-gutter: stable;     /* Modern approach to reserve space */
}
```
- Implementation: `resources/css/app.css` lines 5-58
- Layout: `resources/views/layouts/app.blade.php` - body allows scroll, container manages overflow
- Result: Zero horizontal layout shift, consistent content width

### Toast Notification System
```php
// Livewire component dispatch
$this->dispatch('notify', ['message' => 'Success', 'type' => 'success']);
// Handled by resources/views/layouts/app.blade.php notification system
```

### Form Patterns
- **Number inputs**: No spinners using CSS: `[appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none`
- **Loading states**: Always include `wire:loading` attributes for user feedback
- **Responsive tables**: Use `min-w-*` classes for critical columns (QTY: `min-w-20`, Unit: `min-w-32`)

### Alpine.js Integration
```javascript
// Client-side calculations (performance pattern)
function calculateRowTotal(index) {
    const qty = parseInt(qtyInput.value) || 0;
    const price = parseInt(priceInput.value.replace(/[^0-9]/g, '')) || 0;
    totalSpan.textContent = new Intl.NumberFormat('id-ID').format(qty * price);
}
```

## Integration Points

### PDF Generation (Browsershot + Config-Driven Architecture)
- **Primary Method**: Browsershot (Headless Chrome/Puppeteer) - Best for complex CSS
- **Config Switch**: `config('pdf.pdf_method')` in `config/pdf.php` - Allows future alternatives
- **Templates**: `resources/views/purchase-requests/pdf-browser.blade.php` (active template for Browsershot)
- **Alternative Ready**: DomPDF template exists (`pdf-dompdf.blade.php`) but not used in production
- **QR Codes**: Embedded for verification with reusable tokens
- **Custom CSS**: Print-optimized formatting with `@media print` rules
- **IMPORTANT**: QR tokens are REUSABLE - based on preserved `submitted_at` timestamp
- QR Token Formula: `hash('sha256', json_encode(['pr_id', 'user_id', 'submitted_at', 'type']) . app.key)`
- **Controller Pattern**:
  ```php
  // Config-driven PDF generation
  $pdfMethod = config('pdf.pdf_method', 'browsershot');
  if ($pdfMethod === 'browsershot') {
      return $this->generateBrowsershotPdf($pr, $qrCodes, $filename);
  } else {
      return $this->generateDompdfPdf($pr, $qrCodes, $filename);
  }
  ```

### Authentication & Authorization (Spatie)
```php
// Role hierarchy: super_admin > admin > department_head > user  
$user->getAccessLevel(); // Returns access scope
$user->getManagedUserIds(); // Business unit scoped access
```

### Activity Logging (Spatie)
All PR changes logged automatically via `LogsActivity` trait

## Performance Best Practices (Based on Phase 1 Learnings)

### Database Optimization Patterns
1. **Always Use Indexes**: Follow 5-index standard pattern for all modules
   ```php
   // Standard indexes for any module:
   - user_id + status
   - business_unit_id + status + created_at
   - status + created_at
   - department_id + status + created_at (if applicable)
   - Multi-BU context: user_id + business_unit_id + status
   ```

2. **N+1 Query Prevention**: Use eager loading consistently
   ```php
   // ❌ BAD: N+1 queries
   $prs = PurchaseRequest::all();
   foreach ($prs as $pr) { $pr->user->name; }
   
   // ✅ GOOD: Eager loading
   $prs = PurchaseRequest::with(['user', 'items', 'approvals'])->get();
   ```

3. **Caching Strategy**: Implement multi-tier caching
   ```php
   // Pattern from UserDashboard.php
   const CACHE_TTL_STATS = 300;      // 5 min for stats
   const CACHE_TTL_ACTIVITIES = 60;  // 1 min for activities
   const CACHE_TTL_CHARTS = 300;     // 5 min for charts
   const CACHE_TTL_BUS = 3600;       // 60 min for business units
   ```

### Livewire Best Practices (From Bug Fixes)

1. **Event Architecture**: Use unified event names with bidirectional listeners
   ```php
   // ✅ CORRECT: Unified event system
   // Component A emits:
   $this->dispatch('business-unit-switched', businessUnitId: $id);
   
   // Component B listens:
   protected $listeners = ['business-unit-switched' => 'handleBusinessUnitSwitch'];
   
   public function handleBusinessUnitSwitch($businessUnitId) {
       $this->loadData();
   }
   ```

2. **wire:key Pattern**: NEVER use dynamic values for component identity
   ```php
   // ❌ WRONG: Dynamic wire:key causes component destroy/recreate
   <div wire:key="component-{{ $dynamicValue }}">
   
   // ✅ CORRECT: Static wire:key based on entity ID
   <div wire:key="component-{{ $user->id }}">
   
   // ✅ CORRECT: Static wire:key for singletons
   <div wire:key="business-unit-switcher-{{ auth()->id() }}">
   ```

3. **Avoid Refresh Conflicts**: Single refresh source per interaction
   ```php
   // ❌ WRONG: Multiple refresh sources
   $this->dispatch('event');
   $this->dispatch('$refresh');  // Conflict!
   
   // ✅ CORRECT: Let parent handle refresh
   $this->dispatch('event');  // Parent will refresh everything
   ```

4. **Property Synchronization**: Always update properties after session changes
   ```php
   // ✅ CORRECT: Update property immediately
   session(['current_business_unit_id' => $businessUnit->id]);
   $this->currentBusinessUnit = [
       'id' => $businessUnit->id,
       'code' => $businessUnit->code,
       'name' => $businessUnit->name,
   ];
   ```

5. **Session as Single Source of Truth** (CRITICAL for Event Handlers)
   ```php
   // ✅ CORRECT: Session first, property second, then load data
   public function handleBusinessUnitSwitch($businessUnitId): void
   {
       // 1. Update session FIRST (single source of truth)
       session(['current_business_unit_id' => $businessUnitId]);
       
       // 2. Update property (for UI binding)
       $this->activeBusinessUnitId = $businessUnitId;
       
       // 3. Load data (reads from session, guaranteed fresh)
       $this->loadData();
   }
   
   // ✅ CORRECT: Always read from session in data methods
   protected function getFilteredData(): array
   {
       $id = session('current_business_unit_id') ?? $this->activeBusinessUnitId;
       return Model::where('business_unit_id', $id)->get();
   }
   
   // ❌ WRONG: Property first (may be stale during event handling)
   public function handleBusinessUnitSwitch($businessUnitId): void
   {
       $this->activeBusinessUnitId = $businessUnitId; // Not hydrated yet!
       session(['current_business_unit_id' => $businessUnitId]);
       $this->loadData(); // May read stale property!
   }
   ```
   - **Why**: Livewire properties belum hydrated saat event handler dipanggil
   - **Pattern**: Session = persistent (always fresh), Property = UI binding (may lag)
   - **File**: `app/Livewire/Dashboard/UserDashboard.php` (Bug #5 fix)

### Caching Best Practices

1. **Cache Key Structure**: Use consistent naming with context
   ```php
   // Pattern: {component}_{businessUnit}_{user}_{dataType}_{period}
   Cache::remember("dashboard_stats_{$buId}_{$userId}_{$period}", ...)
   Cache::remember("dashboard_activities_{$buId}_{$userId}", ...)
   ```

2. **Cache Invalidation**: Clear related caches on mutations
   ```php
   // In PurchaseRequestService.php
   protected function clearUserDashboardCache(int $userId): void
   {
       Cache::forget("dashboard_stats_{$buId}_{$userId}_today");
       Cache::forget("dashboard_activities_{$buId}_{$userId}");
       // ... clear all related caches
   }
   ```

3. **Hydrate Optimization**: Skip queries when data unchanged
   ```php
   public function hydrate(): void
   {
       $sessionBuId = session('current_business_unit_id');
       if ($this->currentBusinessUnit['id'] == $sessionBuId) {
           return; // ✅ Skip database query!
       }
       // Only fetch if changed
       $this->loadBusinessUnit($sessionBuId);
   }
   ```

## Key Files for AI Context

### Core Business Logic
- `app/Services/Modules/PurchaseRequest/ApprovalWorkflowService.php` - Approval engine
- `app/Services/Modules/PurchaseRequest/PurchaseRequestService.php` - PR business logic
- `app/Models/Modules/PurchaseRequest/PurchaseRequest.php` - Main domain model
- `app/Livewire/Modules/Wns/PurchaseRequests/Create.php` - PR creation component (1703 lines)

### Performance-Optimized Files (v.2.1)
- `app/Livewire/Dashboard/UserDashboard.php` - Full caching + N+1 optimization
- `app/Livewire/Components/BusinessUnitSwitcher.php` - Cache + event architecture + all bug fixes
- `database/migrations/*_add_performance_indexes_*.php` - 15 performance indexes
- `database/migrations/README-INDEX-STANDARDS.md` - Index templates for future modules

### UI Components  
- `resources/views/livewire/modules/purchase-request/create.blade.php` - Main form view
- `resources/views/layouts/app.blade.php` - Toast notification system & scrollbar stability
- `resources/views/purchase-requests/pdf-browser.blade.php` - Active PDF template with QR codes
- `resources/views/pr-numbers/index.blade.php` - PR number reservation page (moved from purchase-requests)

### API Endpoints
- `app/Http/Controllers/Api/PurchaseRequestController.php` - RESTful operations
- `routes/api.php` - Business unit scoped routes

### Diagnostic Tools
- `public/check-hosting.php` - Production hosting diagnostic script (keep this file!)

## Common Pitfalls

### Critical: Case-Sensitivity Issues
- **Linux vs Windows**: Linux is case-sensitive, Windows is NOT
- **Always use proper casing**: Follow exact folder names in project
- **Livewire convention**: `<livewire:modules.purchase-request.create />` resolves to `Modules/PurchaseRequest/`
- **Example of correct casing**:
  ```
  ✅ Correct: app/Livewire/Modules/PurchaseRequest/Create.php
  ✅ Correct: app/Models/Modules/PurchaseRequest/PurchaseRequest.php
  ✅ Correct: app/Services/Modules/PurchaseRequest/ApprovalWorkflowService.php
  ❌ Wrong:   app/Livewire/Modules/purchaserequest/Create.php
  ❌ Wrong:   app/Models/Modules/PurchaseRequest/purchaseRequest.php
  ```
- **Why it matters**: Works on Windows (all variations) but breaks on Linux (only exact match)

### Database & Relations
- **Never use** `departmentUsers()` relation - use `primary_department_id` field instead  
- **Always validate** business unit context before operations
- **Use transactions** for all multi-model operations
- **Test workflow** with different approval amounts to verify rule engine

### Livewire Hosting Compatibility
- **Defensive initialization required**: Always implement `boot()` and `hydrate()` methods in Livewire components
- **Example pattern**:
  ```php
  public function boot(): void
  {
      if (!$this->businessUnit) {
          $this->businessUnit = auth()->user()->currentBusinessUnit ?? 
                               auth()->user()->businessUnits->first();
      }
  }
  
  public function hydrate(): void
  {
      if (!$this->businessUnit) {
          $this->businessUnit = auth()->user()->currentBusinessUnit ?? 
                               auth()->user()->businessUnits->first();
      }
  }
  ```
- **Why**: Prevents null pointer errors during Livewire state hydration on hosting environments

### Storage & Assets
- **Storage symlink**: Always create `php artisan storage:link` on hosting
- **Livewire 3 assets**: Served via routes (/livewire/livewire.js), no publishing needed
- **Config**: `config/livewire.php` has `'inject_assets' => true` (auto-injection enabled)

## Version 2.0 Features (October 2025)

### Complete Resubmit Workflow
**Background**: Previous implementation had critical bugs where editing rejected PRs would auto-resubmit them, and QR tokens would change after resubmit.

**Key Changes**:

1. **Separate Edit and Resubmit Actions**
   - **Edit**: Updates PR data while keeping `status = 'rejected'`
   - **Resubmit**: Resets workflow and changes `status = 'submitted'`
   - File: `app/Livewire/Modules/PurchaseRequest/Create.php`
   ```php
   protected function submitUpdatedRequest()
   {
       $wasRejected = $existingPR->status === 'rejected';
       $targetStatus = $wasRejected ? 'rejected' : 'submitted';
       
       if ($wasRejected) {
           // Just update data, keep rejected status
           $purchaseRequest->update(['last_modified_by' => Auth::id()]);
       } else {
           // Full submit workflow
           $purchaseRequest->update(['status' => 'submitted', 'submitted_at' => now()]);
       }
   }
   ```

2. **QR Token Reusability**
   - **Problem**: QR tokens were changing after resubmit (different timestamp)
   - **Solution**: Preserve original `submitted_at` timestamp
   - File: `app/Services/PurchaseRequestService.php`
   ```php
   public function resubmitPurchaseRequest(PurchaseRequest $pr): PurchaseRequest
   {
       $originalSubmittedAt = $pr->submitted_at; // PRESERVE timestamp
       
       $pr->update([
           'status' => 'submitted',
           'submitted_at' => $originalSubmittedAt ?? now(), // REUSE
           'rejected_at' => null,
       ]);
       
       // QR token = hash(pr_id + user_id + submitted_at + type + app.key)
       // Same submitted_at = Same QR token ✅
   }
   ```

3. **Custom Workflow Preservation**
   - **Problem**: Custom approval workflows were lost after reset
   - **Solution**: Preserve `approval_workflow` JSON and recreate from it
   - File: `app/Services/Modules/PurchaseRequest/ApprovalWorkflowService.php`
   ```php
   public function resetWorkflow(PurchaseRequest $pr): bool
   {
       $pr->approvals()->delete();
       $pr->update([
           'status' => 'draft',
           // approval_workflow is PRESERVED (not set to null)
           'submitted_at' => null,
       ]);
   }
   
   protected function recreateWorkflowFromJson(PurchaseRequest $pr): bool
   {
       foreach ($pr->approval_workflow as $stepData) {
           PrApproval::create([
               'purchase_request_id' => $pr->id,
               'approver_id' => $stepData['approver_id'],
               'step_order' => $stepData['step_order'],
               'approval_type' => $stepData['approval_type'],
               // ... recreate exact workflow
           ]);
       }
   }
   ```

4. **UI Improvements**
   - Orange alert box in rejected PR show page
   - "Save Changes (Rejected)" button in edit form
   - "Resubmit for Approval" button in show page
   - Clear user feedback messages

### Scrollbar Stability Fix

**Problem**: Content shifted horizontally when scrollbar appeared/disappeared during navigation.

**Solution**: Always reserve space for scrollbar
- File: `resources/css/app.css` (lines 5-58)
```css
@layer base {
    html {
        overflow-y: scroll;        /* Always show scrollbar space */
        scrollbar-gutter: stable;  /* Modern CSS approach */
    }
    
    html, body {
        overflow-x: hidden;        /* Prevent horizontal scroll */
        max-width: 100vw;
    }
    
    /* Custom scrollbar styling for better UX */
    ::-webkit-scrollbar { width: 12px; }
    ::-webkit-scrollbar-track { background: #f1f1f1; }
    ::-webkit-scrollbar-thumb { 
        background: #888; 
        border-radius: 6px;
    }
    
    /* Firefox scrollbar */
    * {
        scrollbar-width: thin;
        scrollbar-color: #888 #f1f1f1;
    }
}
```

- Layout Fix: `resources/views/layouts/app.blade.php`
```blade
<!-- BEFORE: overflow-hidden on body prevented scroll -->
<body class="h-full font-inter antialiased overflow-hidden">

<!-- AFTER: body allows scroll, container manages overflow -->
<body class="h-full font-inter antialiased">
<div class="h-full flex overflow-hidden">
    <!-- sidebar -->
    <main class="flex-1 overflow-y-auto">
        <!-- content scrolls here -->
    </main>
</div>
```

**Result**: Zero horizontal layout shift, consistent viewport width across all pages.

### Massive Code Cleanup

**Statistics**:
- **168 files changed**
- **+2,465 insertions**
- **-5,412 deletions**
- **Net reduction: -2,947 lines**

**Deleted Files** (4 total):
1. `resources/views/purchase-requests/pdf.blade.php` (342 lines - obsolete, replaced by pdf-browser.blade.php)
2. `resources/views/purchase-requests/create-with-number.blade.php` (missing component)
3. `resources/views/purchase-requests/create-integrated.blade.php` (empty file)
4. `storage/framework/views/*` (15 compiled view cache files)

**Reorganized Files**:
1. **MOVED**: `purchase-requests/my-numbers.blade.php` → `pr-numbers/index.blade.php`
   - Reason: Route is `/pr-numbers` not `/purchase-requests/my-numbers`
   - Updated: `app/Http/Controllers/PrNumberReservationController.php` line 52

**Routes Cleanup** (14 → 10 routes):
- **DELETED**:
  - `POST /purchase-requests` (store - handled by Livewire)
  - `PUT /purchase-requests/{id}` (update - handled by Livewire)
  - `POST /purchase-requests/{id}/submit` (submit - handled by Livewire)
  - `GET /purchase-requests/create-with-number` (obsolete view)

**Controller Cleanup** (16 → 12 methods):
- **DELETED**: `app/Http/Controllers/PurchaseRequestController.php`
  - `create()` - Livewire handles creation
  - `store()` - Livewire handles storage
  - `update()` - Livewire handles updates
  - `submit()` - Livewire handles submission

**Service Documentation**:
- `app/Services/PurchaseRequestService.php::submitPurchaseRequest()`
  - Added comprehensive PHPDoc explaining method is UNUSED
  - Kept for future "Save as Draft" feature
  - Different from Livewire Create::submitPurchaseRequest()

### Bug Fixes in v.2

1. **Linter Error Fix**
   - File: `app/Http/Controllers/PurchaseRequestController.php` line 140
   - Changed: `auth()->id()` → `Auth::id()`
   - Reason: Better IDE support, use imported facade

2. **Override Bug Fix**
   - File: `app/Livewire/Modules/PurchaseRequest/Create.php`
   - Issue: `submitUpdatedRequest()` always changed status to 'submitted'
   - Fix: Check `$wasRejected` flag, preserve 'rejected' status when editing

3. **QR Token Change Bug**
   - File: `app/Services/Modules/PurchaseRequest/PurchaseRequestService.php`
   - Issue: `submitted_at = now()` created new timestamp → different QR token
   - Fix: `submitted_at = $originalSubmittedAt ?? now()` preserves timestamp

4. **Workflow Loss Bug**
   - File: `app/Services/Modules/PurchaseRequest/ApprovalWorkflowService.php`
   - Issue: `resetWorkflow()` set `approval_workflow = null`
   - Fix: Don't set to null, create `recreateWorkflowFromJson()` method

### New Features in v.2.1

1. **Dashboard Business Unit Sync Fix**
   - **Problem**: Dashboard showed wrong data when switching business units via header
   - **Root Cause**: Two components using different session keys:
     - Header Badge: `session('current_business_unit_id')` (global)
     - Dashboard: `session('dashboard_active_business_unit_id')` (local)
   - **Solution**: 
     - Dashboard now prioritizes global session key
     - `switchBusinessUnit()` saves to BOTH keys for backward compatibility
   - **Files Changed**: `app/Livewire/Dashboard/UserDashboard.php`
   - **Impact**: Business unit switching now works consistently across all pages

2. **PDF Config Refactoring**
   - **Change**: Extracted PDF method selection to config file
   - **Before**: Hardcoded Browsershot in controller
   - **After**: Config-driven with `config('pdf.pdf_method')`
   - **Benefits**: Easy to switch PDF engines without code changes
   - **Files**:
     - `config/pdf.php` - New config file with method selection
     - `app/Http/Controllers/Modules/PurchaseRequest/PurchaseRequestController.php`
       - New method: `generateDompdfPdf()` (alternative, not used)
       - Updated: `downloadPdfPublic()` - Config-driven method selection
   - **Templates**:
     - `pdf-browser.blade.php` - Browsershot template (ACTIVE)
     - `pdf-dompdf.blade.php` - DomPDF fallback (NOT USED)

### Testing Checklist for v.2

When working with PR resubmit features, always test:

1. **Edit Rejected PR**
   - ✅ Status remains 'rejected' after save
   - ✅ Data updates successfully
   - ✅ "Resubmit for Approval" button still visible
   - ✅ QR code matches original

2. **Resubmit Rejected PR**
   - ✅ Status changes to 'submitted' → 'in_approval'
   - ✅ Old approvals deleted
   - ✅ New approvals created from JSON
   - ✅ `submitted_at` timestamp preserved
   - ✅ QR code remains identical (token reusable)
   - ✅ Custom workflow preserved (same approvers, order, types)

3. **Scrollbar Stability**
   - ✅ Navigate from short page to long page
   - ✅ Content doesn't shift horizontally
   - ✅ Scrollbar track always visible
   - ✅ Smooth scrolling behavior
   - ✅ Works on Chrome, Firefox, Edge

4. **File Organization**
   - ✅ No unused view files in `resources/views/purchase-requests/`
   - ✅ PR numbers page at `resources/views/pr-numbers/index.blade.php`
   - ✅ Only active routes in `routes/web.php`
   - ✅ Only active methods in controllers

5. **Performance Testing (v.2.1)**
   - ✅ Dashboard loads in <50ms (with cache hit)
   - ✅ BU switch completes in <100ms
   - ✅ No N+1 queries (check with Debugbar)
   - ✅ Cache hit rate >70%
   - ✅ Zero console errors
   - ✅ All indexes used (verify with EXPLAIN)

### Debugging Tools & Commands

**Check Performance**:
```bash
# Enable query log in tinker
php artisan tinker
DB::enableQueryLog();
// ... perform action ...
dd(DB::getQueryLog());

# Check cache usage
Cache::get('dashboard_stats_1_1_today'); // Returns cached data or null

# Verify indexes
php artisan tinker
DB::select('SHOW INDEX FROM purchase_requests');
```

**Browser Console Debugging**:
```javascript
// Check Livewire component state
Livewire.all(); // List all components

// Monitor events
window.addEventListener('livewire:dispatch', (event) => {
    console.log('Event:', event.detail);
});

// Check wire:key values
document.querySelectorAll('[wire\\:key]').forEach(el => {
    console.log(el.getAttribute('wire:key'));
});
```

### Storage & Assets
- **Storage symlink**: Always create `php artisan storage:link` on hosting
- **Livewire 3 assets**: Served via routes (/livewire/livewire.js), no publishing needed
- **Config**: `config/livewire.php` has `'inject_assets' => true` (auto-injection enabled)

## Production Deployment Checklist

### Pre-Deployment (Local)
1. ✅ Verify folder casing matches Laravel convention (e.g., `Wns` not `WNS`)
2. ✅ Run `vendor/bin/pint` to format code
3. ✅ Run tests: `php artisan test`
4. ✅ Build assets: `npm run build`
5. ✅ Clear local caches: `php artisan optimize:clear`
6. ✅ Test on local: `php artisan serve`
7. ✅ Commit & push: `git add -A && git commit -m "..." && git push`

### Deployment (Hosting)
1. 📤 Upload changed files via FTP/SFTP
2. 🗑️ Delete old incorrectly-cased folders (e.g., old `WNS/` if renamed to `Wns/`)
3. 🔧 SSH Commands:
   ```bash
   cd /path/to/project
   composer dump-autoload    # CRITICAL after namespace changes!
   php artisan storage:link  # If storage not linked yet
   php artisan optimize:clear # Clear all caches
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

### Post-Deployment Verification
1. ✅ Visit application URL
2. ✅ Check browser console (F12) for errors
3. ✅ Test key features (PR creation, approval workflow)
4. ✅ Run diagnostic: `/check-hosting.php` if issues occur
5. ✅ Check logs: `tail -50 storage/logs/laravel.log`

### Common Deployment Issues & Solutions

**Issue: "Class not found" or "Missing component"**
- **Cause**: Folder casing mismatch or autoload not rebuilt
- **Fix**: 
  1. Verify folder casing matches Laravel convention
  2. Run `composer dump-autoload` on hosting
  3. Clear caches: `php artisan optimize:clear`

**Issue: "File not found" for uploads/PDFs**
- **Cause**: Storage symlink not created
- **Fix**: Run `php artisan storage:link` on hosting

**Issue: Livewire JS 404 error**
- **Cause**: Route caching or Livewire not properly installed
- **Fix**: 
  1. `php artisan route:clear`
  2. Verify `config/livewire.php` exists
  3. Check Livewire is in `composer.json`

**Issue: Changes not reflected**
- **Cause**: Cached views/config/routes
- **Fix**: `php artisan optimize:clear` then rebuild caches

## Project Maintenance Best Practices

### Keep Root Directory Clean
**Essential Files Only** (20-25 files recommended):
- ✅ `README.md` - Main documentation
- ✅ `composer.json`, `package.json` - Dependencies
- ✅ `.env`, `.env.example` - Environment configs
- ✅ `phpunit.xml` - Test configuration
- ✅ `artisan` - CLI entry point
- ✅ Configuration files (tailwind, vite, postcss configs)
- ❌ Avoid: Test scripts, temporary .md files, debug scripts in root

### Files to Keep
- **Diagnostic tools**: `public/check-hosting.php` (useful for production debugging)
- **Documentation**: `README.md` only (consolidate other docs into it or delete)
- **Application code**: All `app/`, `resources/`, `config/`, `database/`, `routes/`, `tests/` folders

### Files to Delete After Use
- ❌ Temporary documentation files (DEPLOYMENT-*.md, HOSTING-*.md, CLAUDE.md, etc.)
- ❌ Debug/verification scripts (verify-*.php, debug-*.php, hosting-*.php in root)
- ❌ Deployment scripts (fix-hosting-*.bat/sh/ps1, emergency-*.ps1)
- ❌ Test PDF files in root or storage/ (keep only generated PDFs from actual usage)
- ❌ Backup files (*-backup.php)
- ❌ Cleanup scripts after execution

### Regular Cleanup Commands
```bash
# After debugging session, clean up temporary files:
# 1. Review files in root
Get-ChildItem -File  # Windows PowerShell
ls -la               # Linux/Mac

# 2. Remove temporary docs/scripts (careful!)
Remove-Item *.md -Exclude README.md  # Windows
rm *.md !("README.md")               # Linux

# 3. Clean test PDFs
Remove-Item storage/*.pdf            # Keep only generated PDFs

# 4. Clear Laravel caches
php artisan optimize:clear
```

### Git Best Practices
```bash
# Before committing cleanup:
git status                    # Review what will be deleted
git add -A                    # Stage all changes
git commit -m "chore: cleanup temporary files"

# Add to .gitignore:
*.pdf                         # Except specific docs
*-backup.php
verify-*.php
debug-*.php
DEPLOYMENT-*.md
HOSTING-*.md
```

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to enhance the user's satisfaction building Laravel applications.

## Foundational Context
This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.2.12
- inertiajs/inertia-laravel (INERTIA) - v2
- laravel/framework (LARAVEL) - v12
- laravel/prompts (PROMPTS) - v0
- laravel/sanctum (SANCTUM) - v4
- tightenco/ziggy (ZIGGY) - v2
- laravel/breeze (BREEZE) - v2
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- phpunit/phpunit (PHPUNIT) - v11
- @inertiajs/react (INERTIA) - v2
- alpinejs (ALPINEJS) - v3
- react (REACT) - v19
- tailwindcss (TAILWINDCSS) - v3


## Conventions
- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts
- Do not create verification scripts or tinker when tests cover that functionality and prove it works. Unit and feature tests are more important.

## Application Structure & Architecture
- Stick to existing directory structure - don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling
- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Replies
- Be concise in your explanations - focus on what's important rather than explaining obvious details.

## Documentation Files
- You must only create documentation files if explicitly requested by the user.


=== boost rules ===

## Laravel Boost
- Laravel Boost is an MCP server that comes with powerful tools designed specifically for this application. Use them.

## Artisan
- Use the `list-artisan-commands` tool when you need to call an Artisan command to double check the available parameters.

## URLs
- Whenever you share a project URL with the user you should use the `get-absolute-url` tool to ensure you're using the correct scheme, domain / IP, and port.

## Tinker / Debugging
- You should use the `tinker` tool when you need to execute PHP to debug code or query Eloquent models directly.
- Use the `database-query` tool when you only need to read from the database.

## Reading Browser Logs With the `browser-logs` Tool
- You can read browser logs, errors, and exceptions using the `browser-logs` tool from Boost.
- Only recent browser logs will be useful - ignore old logs.

## Searching Documentation (Critically Important)
- Boost comes with a powerful `search-docs` tool you should use before any other approaches. This tool automatically passes a list of installed packages and their versions to the remote Boost API, so it returns only version-specific documentation specific for the user's circumstance. You should pass an array of packages to filter on if you know you need docs for particular packages.
- The 'search-docs' tool is perfect for all Laravel related packages, including Laravel, Inertia, Livewire, Filament, Tailwind, Pest, Nova, Nightwatch, etc.
- You must use this tool to search for Laravel-ecosystem documentation before falling back to other approaches.
- Search the documentation before making code changes to ensure we are taking the correct approach.
- Use multiple, broad, simple, topic based queries to start. For example: `['rate limiting', 'routing rate limiting', 'routing']`.
- Do not add package names to queries - package information is already shared. For example, use `test resource table`, not `filament 4 test resource table`.

### Available Search Syntax
- You can and should pass multiple queries at once. The most relevant results will be returned first.

1. Simple Word Searches with auto-stemming - query=authentication - finds 'authenticate' and 'auth'
2. Multiple Words (AND Logic) - query=rate limit - finds knowledge containing both "rate" AND "limit"
3. Quoted Phrases (Exact Position) - query="infinite scroll" - Words must be adjacent and in that order
4. Mixed Queries - query=middleware "rate limit" - "middleware" AND exact phrase "rate limit"
5. Multiple Queries - queries=["authentication", "middleware"] - ANY of these terms


=== php rules ===

## PHP

- Always use curly braces for control structures, even if it has one line.

### Constructors
- Use PHP 8 constructor property promotion in `__construct()`.
    - <code-snippet>public function __construct(public GitHub $github) { }</code-snippet>
- Do not allow empty `__construct()` methods with zero parameters.

### Type Declarations
- Always use explicit return type declarations for methods and functions.
- Use appropriate PHP type hints for method parameters.

<code-snippet name="Explicit Return Types and Method Params" lang="php">
protected function isAccessible(User $user, ?string $path = null): bool
{
    ...
}
</code-snippet>

## Comments
- Prefer PHPDoc blocks over comments. Never use comments within the code itself unless there is something _very_ complex going on.

## PHPDoc Blocks
- Add useful array shape type definitions for arrays when appropriate.

## Enums
- Typically, keys in an Enum should be TitleCase. For example: `FavoritePerson`, `BestLake`, `Monthly`.


=== inertia-laravel/core rules ===

## Inertia Core

- Inertia.js components should be placed in the `resources/js/Pages` directory unless specified differently in the JS bundler (vite.config.js).
- Use `Inertia::render()` for server-side routing instead of traditional Blade views.
- Use `search-docs` for accurate guidance on all things Inertia.

<code-snippet lang="php" name="Inertia::render Example">
// routes/web.php example
Route::get('/users', function () {
    return Inertia::render('Users/Index', [
        'users' => User::all()
    ]);
});
</code-snippet>


=== inertia-laravel/v2 rules ===

## Inertia v2

- Make use of all Inertia features from v1 & v2. Check the documentation before making any changes to ensure we are taking the correct approach.

### Inertia v2 New Features
- Polling
- Prefetching
- Deferred props
- Infinite scrolling using merging props and `WhenVisible`
- Lazy loading data on scroll

### Deferred Props & Empty States
- When using deferred props on the frontend, you should add a nice empty state with pulsing / animated skeleton.

### Inertia Form General Guidance
- The recommended way to build forms when using Inertia is with the `<Form>` component - a useful example is below. Use `search-docs` with a query of `form component` for guidance.
- Forms can also be built using the `useForm` helper for more programmatic control, or to follow existing conventions. Use `search-docs` with a query of `useForm helper` for guidance.
- `resetOnError`, `resetOnSuccess`, and `setDefaultsOnSuccess` are available on the `<Form>` component. Use `search-docs` with a query of 'form component resetting' for guidance.


=== laravel/core rules ===

## Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using the `list-artisan-commands` tool.
- If you're creating a generic PHP class, use `artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Database
- Always use proper Eloquent relationship methods with return type hints. Prefer relationship methods over raw queries or manual joins.
- Use Eloquent models and relationships before suggesting raw database queries
- Avoid `DB::`; prefer `Model::query()`. Generate code that leverages Laravel's ORM capabilities rather than bypassing them.
- Generate code that prevents N+1 query problems by using eager loading.
- Use Laravel's query builder for very complex database operations.

### Model Creation
- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `list-artisan-commands` to check the available options to `php artisan make:model`.

### APIs & Eloquent Resources
- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

### Controllers & Validation
- Always create Form Request classes for validation rather than inline validation in controllers. Include both validation rules and custom error messages.
- Check sibling Form Requests to see if the application uses array or string based validation rules.

### Queues
- Use queued jobs for time-consuming operations with the `ShouldQueue` interface.

### Authentication & Authorization
- Use Laravel's built-in authentication and authorization features (gates, policies, Sanctum, etc.).

### URL Generation
- When generating links to other pages, prefer named routes and the `route()` function.

### Configuration
- Use environment variables only in configuration files - never use the `env()` function directly outside of config files. Always use `config('app.name')`, not `env('APP_NAME')`.

### Testing
- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] <name>` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

### Vite Error
- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.


=== laravel/v12 rules ===

## Laravel 12

- Use the `search-docs` tool to get version specific documentation.
- Since Laravel 11, Laravel has a new streamlined file structure which this project uses.

### Laravel 12 Structure
- No middleware files in `app/Http/Middleware/`.
- `bootstrap/app.php` is the file to register middleware, exceptions, and routing files.
- `bootstrap/providers.php` contains application specific service providers.
- **No app\Console\Kernel.php** - use `bootstrap/app.php` or `routes/console.php` for console configuration.
- **Commands auto-register** - files in `app/Console/Commands/` are automatically available and do not require manual registration.

### Database
- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.
- Laravel 11 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models
- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.


=== pint/core rules ===

## Laravel Pint Code Formatter

- You must run `vendor/bin/pint --dirty` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test`, simply run `vendor/bin/pint` to fix any formatting issues.


=== phpunit/core rules ===

## PHPUnit Core

- This application uses PHPUnit for testing. All tests must be written as PHPUnit classes. Use `php artisan make:test --phpunit <name>` to create a new test.
- If you see a test using "Pest", convert it to PHPUnit.
- Every time a test has been updated, run that singular test.
- When the tests relating to your feature are passing, ask the user if they would like to also run the entire test suite to make sure everything is still passing.
- Tests should test all of the happy paths, failure paths, and weird paths.
- You must not remove any tests or test files from the tests directory without approval. These are not temporary or helper files, these are core to the application.

### Running Tests
- Run the minimal number of tests, using an appropriate filter, before finalizing.
- To run all tests: `php artisan test`.
- To run all tests in a file: `php artisan test tests/Feature/ExampleTest.php`.
- To filter on a particular test name: `php artisan test --filter=testName` (recommended after making a change to a related file).


=== inertia-react/core rules ===

## Inertia + React

- Use `router.visit()` or `<Link>` for navigation instead of traditional links.

<code-snippet name="Inertia Client Navigation" lang="react">

import { Link } from '@inertiajs/react'
<Link href="/">Home</Link>

</code-snippet>


=== inertia-react/v2/forms rules ===

## Inertia + React Forms

<code-snippet name="`<Form>` Component Example" lang="react">

import { Form } from '@inertiajs/react'

export default () => (
    <Form action="/users" method="post">
        {({
            errors,
            hasErrors,
            processing,
            wasSuccessful,
            recentlySuccessful,
            clearErrors,
            resetAndClearErrors,
            defaults
        }) => (
        <>
        <input type="text" name="name" />

        {errors.name && <div>{errors.name}</div>}

        <button type="submit" disabled={processing}>
            {processing ? 'Creating...' : 'Create User'}
        </button>

        {wasSuccessful && <div>User created successfully!</div>}
        </>
    )}
    </Form>
)

</code-snippet>


=== tailwindcss/core rules ===

## Tailwind Core

- Use Tailwind CSS classes to style HTML, check and use existing tailwind conventions within the project before writing your own.
- Offer to extract repeated patterns into components that match the project's conventions (i.e. Blade, JSX, Vue, etc..)
- Think through class placement, order, priority, and defaults - remove redundant classes, add classes to parent or child carefully to limit repetition, group elements logically
- You can use the `search-docs` tool to get exact examples from the official documentation when needed.

### Spacing
- When listing items, use gap utilities for spacing, don't use margins.

    <code-snippet name="Valid Flex Gap Spacing Example" lang="html">
        <div class="flex gap-8">
            <div>Superior</div>
            <div>Michigan</div>
            <div>Erie</div>
        </div>
    </code-snippet>


### Dark Mode
- If existing pages and components support dark mode, new pages and components must support dark mode in a similar way, typically using `dark:`.


=== tailwindcss/v3 rules ===

## Tailwind 3

- Always use Tailwind CSS v3 - verify you're using only classes supported by this version.


=== tests rules ===

## Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test` with a specific filename or filter.
</laravel-boost-guidelines>