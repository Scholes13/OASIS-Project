# IT Support Module Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Build the IT Support module in OASIS, migrating WGTicket's ticketing functionality into the OASIS modular monolith with the same admin-assignment pattern as Activity Admin and Purchasing Admin.

**Architecture:** New `Modules/Ticket/` module following OASIS conventions. Admin access via `is_it_support_admin` flag on `user_business_units` with BU ancestor cascade. All users can submit tickets; IT Support admins manage, assign, and report. Route prefix: `/it-support`. Ticket numbers: `IT.{BU}/YYYYMM/###`.

**Tech Stack:** Laravel 12, PHP 8.2, React 19, TypeScript, Inertia.js v2, Tailwind CSS, PhpSpreadsheet, Spatie Permission, OASIS notification center

**Design doc:** `docs/plans/2026-04-27-it-support-module-design.md`

---

## Phase 0: Foundation — Schema, Permissions, Admin Assignment

### Task 1: Add IT Support admin flags to user_business_units

**Files:**
- Create: `database/migrations/modules/ticket/2026_04_27_000001_add_it_support_flags_to_user_business_units.php`
- Modify: `app/Models/Core/UserBusinessUnit.php` — add to `$fillable` and `$casts`

**Step 1: Write the migration**

```php
// database/migrations/modules/ticket/2026_04_27_000001_add_it_support_flags_to_user_business_units.php
Schema::table('user_business_units', function (Blueprint $table) {
    $table->boolean('is_it_support_admin')->default(false)->after('is_activity_report_access');
    $table->boolean('is_it_support_report_access')->default(false)->after('is_it_support_admin');
});
```

**Step 2: Update UserBusinessUnit model**

Add `'is_it_support_admin'` and `'is_it_support_report_access'` to `$fillable` array and `$casts` array (as `'boolean'`).

Reference: `app/Models/Core/UserBusinessUnit.php:52-75` for existing pattern.

**Step 3: Run migration**

```bash
php artisan migrate
```

**Step 4: Verify**

```bash
php artisan tinker --execute="echo implode(', ', Schema::getColumnListing('user_business_units'));"
```

**Step 5: Commit**

```bash
git add database/migrations/modules/ticket/ app/Models/Core/UserBusinessUnit.php
git commit -m "feat(ticket): add is_it_support_admin flags to user_business_units"
```

---

### Task 2: Create ticket module database tables

**Files:**
- Create: `database/migrations/modules/ticket/2026_04_27_000002_create_ticket_categories_table.php`
- Create: `database/migrations/modules/ticket/2026_04_27_000003_create_tickets_table.php`
- Create: `database/migrations/modules/ticket/2026_04_27_000004_create_ticket_comments_table.php`
- Create: `database/migrations/modules/ticket/2026_04_27_000005_create_ticket_attachments_table.php`
- Create: `database/migrations/modules/ticket/2026_04_27_000006_create_ticket_sla_settings_table.php`
- Create: `database/migrations/modules/ticket/2026_04_27_000007_create_ticket_knowledge_categories_table.php`
- Create: `database/migrations/modules/ticket/2026_04_27_000008_create_ticket_knowledge_articles_table.php`
- Create: `database/migrations/modules/ticket/2026_04_27_000009_create_ticket_knowledge_article_table.php`
- Create: `database/migrations/modules/ticket/2026_04_27_000010_create_ticket_article_views_table.php`

**Step 1: Create migration directory**

```bash
mkdir -p database/migrations/modules/ticket
```

**Step 2: Create each migration file**

```php
// ticket_categories
Schema::create('ticket_categories', function (Blueprint $table) {
    $table->id();
    $table->foreignId('business_unit_id')->constrained('business_units')->cascadeOnDelete();
    $table->string('name');
    $table->text('description')->nullable();
    $table->string('color', 7)->default('#6366f1');
    $table->boolean('is_active')->default(true);
    $table->timestamps();
    $table->index(['business_unit_id', 'is_active']);
});

// tickets
Schema::create('tickets', function (Blueprint $table) {
    $table->id();
    $table->foreignId('business_unit_id')->constrained('business_units')->cascadeOnDelete();
    $table->string('ticket_number')->unique();
    $table->string('title');
    $table->text('description');
    $table->foreignId('requester_id')->constrained('users')->cascadeOnDelete();
    $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
    $table->enum('status', ['waiting', 'in_progress', 'done', 'cancelled'])->default('waiting');
    $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');
    $table->foreignId('category_id')->nullable()->constrained('ticket_categories')->nullOnDelete();
    $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
    $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
    $table->timestamp('follow_up_at')->nullable();
    $table->timestamp('resolved_at')->nullable();
    $table->string('form_token')->nullable()->index();
    $table->timestamps();
    $table->index(['business_unit_id', 'status']);
    $table->index(['business_unit_id', 'status', 'created_at']);
    $table->index(['assigned_to', 'status']);
    $table->index(['department_id', 'status']);
    $table->index(['requester_id', 'created_at']);
    $table->index(['created_by', 'created_at']);
});

// ticket_comments
Schema::create('ticket_comments', function (Blueprint $table) {
    $table->id();
    $table->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();
    $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
    $table->text('content');
    $table->boolean('is_private')->default(false);
    $table->timestamps();
    $table->softDeletes();
    $table->index(['ticket_id', 'created_at']);
});

// ticket_attachments
Schema::create('ticket_attachments', function (Blueprint $table) {
    $table->id();
    $table->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();
    $table->foreignId('comment_id')->nullable()->constrained('ticket_comments')->nullOnDelete();
    $table->string('filename');
    $table->string('original_filename');
    $table->string('file_path');
    $table->string('file_type')->nullable();
    $table->unsignedBigInteger('file_size')->default(0);
    $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamps();
    $table->index('ticket_id');
});

// ticket_sla_settings
Schema::create('ticket_sla_settings', function (Blueprint $table) {
    $table->id();
    $table->foreignId('business_unit_id')->constrained('business_units')->cascadeOnDelete();
    $table->enum('priority', ['low', 'medium', 'high', 'critical']);
    $table->unsignedInteger('resolution_hours');
    $table->timestamps();
    $table->unique(['business_unit_id', 'priority']);
});

// ticket_knowledge_categories
Schema::create('ticket_knowledge_categories', function (Blueprint $table) {
    $table->id();
    $table->foreignId('business_unit_id')->constrained('business_units')->cascadeOnDelete();
    $table->string('name');
    $table->string('slug')->unique();
    $table->text('description')->nullable();
    $table->foreignId('parent_id')->nullable()->constrained('ticket_knowledge_categories')->nullOnDelete();
    $table->string('icon')->nullable();
    $table->integer('order')->default(0);
    $table->timestamps();
    $table->index(['business_unit_id', 'parent_id']);
});

// ticket_knowledge_articles
Schema::create('ticket_knowledge_articles', function (Blueprint $table) {
    $table->id();
    $table->foreignId('business_unit_id')->constrained('business_units')->cascadeOnDelete();
    $table->string('title');
    $table->string('slug')->unique();
    $table->longText('content');
    $table->foreignId('category_id')->nullable()->constrained('ticket_knowledge_categories')->nullOnDelete();
    $table->boolean('is_published')->default(false);
    $table->unsignedBigInteger('views_count')->default(0);
    $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamp('published_at')->nullable();
    $table->text('meta_description')->nullable();
    $table->json('tags')->nullable();
    $table->timestamps();
    $table->index(['business_unit_id', 'is_published']);
    $table->index(['category_id', 'is_published']);
});

// ticket_knowledge_article (pivot)
Schema::create('ticket_knowledge_article', function (Blueprint $table) {
    $table->id();
    $table->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();
    $table->foreignId('knowledge_article_id')->constrained('ticket_knowledge_articles')->cascadeOnDelete();
    $table->timestamps();
    $table->unique(['ticket_id', 'knowledge_article_id']);
});

// ticket_article_views
Schema::create('ticket_article_views', function (Blueprint $table) {
    $table->id();
    $table->foreignId('article_id')->constrained('ticket_knowledge_articles')->cascadeOnDelete();
    $table->string('ip_address', 45)->nullable();
    $table->text('user_agent')->nullable();
    $table->string('visitor_fingerprint')->nullable()->index();
    $table->string('session_id')->nullable();
    $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamp('viewed_at')->nullable();
    $table->timestamps();
    $table->unique(['article_id', 'visitor_fingerprint', 'user_id'], 'ticket_article_views_unique');
});
```

**Step 3: Run migrations**

```bash
php artisan migrate
```

**Step 4: Commit**

```bash
git add database/migrations/modules/ticket/
git commit -m "feat(ticket): add database tables for IT Support module"
```

---

### Task 3: Register IT numbering module and seed SLA defaults

**Files:**
- Create: `database/seeders/ITSupportSeeder.php`

**Step 1: Create seeder**

The seeder should:
- Insert `IT` row into `numbering_modules` table (code: `IT`, name: `IT Support Ticket`)
- Insert default SLA settings for a default BU (low=48h, medium=24h, high=8h, critical=2h)

Reference: check existing `numbering_modules` rows and `NumberSequence` model at `app/Models/Core/NumberSequence.php:191-225`.

**Step 2: Run seeder**

```bash
php artisan db:seed --class=ITSupportSeeder
```

**Step 3: Commit**

```bash
git add database/seeders/ITSupportSeeder.php
git commit -m "feat(ticket): seed IT numbering module and default SLA settings"
```

---

### Task 4: Create ITSupportAccess middleware and register gates

**Files:**
- Create: `app/Http/Middleware/ITSupportAccess.php`
- Modify: `bootstrap/app.php` — register alias `it.support.access`
- Modify: `app/Providers/AppServiceProvider.php` — add gates

**Step 1: Create middleware**

Mirror `app/Http/Middleware/ActivityAdminAccess.php` exactly:

```php
namespace App\Http\Middleware;

class ITSupportAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) { return redirect()->route('login'); }
        if ($user->isSuperAdmin()) { return $next($request); }
        if ($user->hasTopManagementAccess()) { return $next($request); }

        $currentBuId = session('current_business_unit_id');
        if (! $currentBuId) { abort(403, 'No business unit selected.'); }

        if (! $user->isAdminInBuOrAncestor('is_it_support_admin', $currentBuId)) {
            abort(403, 'Unauthorized. IT Support access required.');
        }

        return $next($request);
    }
}
```

**Step 2: Register middleware alias in `bootstrap/app.php`**

Add `'it.support.access' => \App\Http\Middleware\ITSupportAccess::class` alongside existing aliases.

Reference: `bootstrap/app.php:35-45`.

**Step 3: Add gates in AppServiceProvider**

```php
Gate::define('access-it-support', function ($user) {
    if ($user->isSuperAdmin()) return true;
    if ($user->hasTopManagementAccess()) return true;
    $currentBuId = session('current_business_unit_id');
    return $currentBuId && $user->isAdminInBuOrAncestor('is_it_support_admin', $currentBuId);
});

Gate::define('view-it-support-reports', function ($user) {
    if ($user->isSuperAdmin()) return true;
    if ($user->hasTopManagementAccess()) return true;
    return $user->activeBusinessUnits()
        ->where('is_it_support_admin', true)
        ->where('is_it_support_report_access', true)
        ->exists();
});
```

Reference: `app/Providers/AppServiceProvider.php:141-242` for existing gate patterns.

**Step 4: Run `vendor/bin/pint --dirty`**

**Step 5: Commit**

```bash
git add app/Http/Middleware/ITSupportAccess.php bootstrap/app.php app/Providers/AppServiceProvider.php
git commit -m "feat(ticket): add ITSupportAccess middleware and authorization gates"
```

---

### Task 5: Create IT Support admin assignment controller and routes

**Files:**
- Create: `app/Http/Controllers/Admin/ITSupportAssignmentController.php`
- Modify: `routes/web.php` — add admin assignment routes

**Step 1: Create controller**

Mirror `app/Http/Controllers/Admin/PurchasingAdminAssignmentController.php` exactly, replacing:
- `is_purchasing_admin` → `is_it_support_admin`
- `is_purchasing_report_access` → `is_it_support_report_access`
- route names → `admin.it-support-admins.*`
- page → `Admin/ITSupportAdmins/Index`

Methods: `index()`, `toggle($id)`, `toggleReportAccess($id)`

**Step 2: Add routes in admin section of `routes/web.php`**

```php
Route::prefix('it-support-admins')->name('it-support-admins.')->group(function () {
    Route::get('/', [ITSupportAssignmentController::class, 'index'])->name('index');
    Route::post('/{id}/toggle', [ITSupportAssignmentController::class, 'toggle'])->name('toggle')->whereNumber('id');
    Route::post('/{id}/toggle-report', [ITSupportAssignmentController::class, 'toggleReportAccess'])->name('toggle-report')->whereNumber('id');
});
```

Reference: `routes/web.php:419-429` for existing admin assignment route pattern.

**Step 3: Run `vendor/bin/pint --dirty`**

**Step 4: Commit**

```bash
git add app/Http/Controllers/Admin/ITSupportAssignmentController.php routes/web.php
git commit -m "feat(ticket): add IT Support admin assignment controller and routes"
```

---

## Phase 1: Backend Domain — Models, Services, Requests

### Task 6: Create Eloquent models

**Files:**
- Create: `app/Models/Modules/Ticket/Ticket.php`
- Create: `app/Models/Modules/Ticket/TicketCategory.php`
- Create: `app/Models/Modules/Ticket/TicketComment.php`
- Create: `app/Models/Modules/Ticket/TicketAttachment.php`
- Create: `app/Models/Modules/Ticket/TicketSlaSettings.php`
- Create: `app/Models/Modules/Ticket/KnowledgeCategory.php`
- Create: `app/Models/Modules/Ticket/KnowledgeArticle.php`
- Create: `app/Models/Modules/Ticket/ArticleView.php`

**Key model: `Ticket.php`**

```php
namespace App\Models\Modules\Ticket;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Core\User;

class Ticket extends Model
{
    protected $fillable = [
        'business_unit_id', 'ticket_number', 'title', 'description',
        'requester_id', 'department_id', 'status', 'priority',
        'category_id', 'assigned_to', 'created_by',
        'follow_up_at', 'resolved_at', 'form_token',
    ];

    protected $casts = [
        'follow_up_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    // Relationships
    public function businessUnit(): BelongsTo { return $this->belongsTo(BusinessUnit::class); }
    public function department(): BelongsTo { return $this->belongsTo(Department::class); }
    public function category(): BelongsTo { return $this->belongsTo(TicketCategory::class, 'category_id'); }
    public function assignedUser(): BelongsTo { return $this->belongsTo(User::class, 'assigned_to'); }
    public function requester(): BelongsTo { return $this->belongsTo(User::class, 'requester_id'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function comments(): HasMany { return $this->hasMany(TicketComment::class); }
    public function attachments(): HasMany { return $this->hasMany(TicketAttachment::class); }
    public function knowledgeArticles(): BelongsToMany {
        return $this->belongsToMany(KnowledgeArticle::class, 'ticket_knowledge_article');
    }

    // Scopes
    public function scopeWaiting($query) { return $query->where('status', 'waiting'); }
    public function scopeInProgress($query) { return $query->where('status', 'in_progress'); }
    public function scopeDone($query) { return $query->where('status', 'done'); }
    public function scopeForBusinessUnit($query, int $buId) { return $query->where('business_unit_id', $buId); }
    public function scopeForBusinessUnits($query, array $buIds) { return $query->whereIn('business_unit_id', $buIds); }

    // SLA
    public function isSlaBreach(): bool { /* compare created_at + SLA hours vs now */ }
    public function getSlaDeadlineAttribute(): ?Carbon { /* created_at + SLA hours */ }
}
```

**Key model: `TicketSlaSettings.php`**

```php
class TicketSlaSettings extends Model
{
    protected $fillable = ['business_unit_id', 'priority', 'resolution_hours'];
    protected $casts = ['resolution_hours' => 'integer'];

    public function businessUnit(): BelongsTo { return $this->belongsTo(BusinessUnit::class); }
}
```

Follow OASIS conventions: explicit return types, type hints, proper casts.

**Step 1:** Create all 8 model files

**Step 2:** Run `vendor/bin/pint --dirty`

**Step 3:** Commit

```bash
git add app/Models/Modules/Ticket/
git commit -m "feat(ticket): add Eloquent models for IT Support module"
```

---

### Task 7: Create services

**Files:**
- Create: `app/Services/Modules/Ticket/TicketService.php`
- Create: `app/Services/Modules/Ticket/TicketNumberService.php`
- Create: `app/Services/Modules/Ticket/TicketReportingService.php`
- Create: `app/Services/Modules/Ticket/KnowledgeBaseService.php`
- Create: `app/Services/Modules/Ticket/SlaService.php`

**TicketService:** create ticket, update status (with transition validation), assign, add comment, add attachment, duplicate prevention via form_token + cache, dashboard metrics.

**TicketNumberService:** generate `IT.{BU_CODE}/YYYYMM/###` using OASIS NumberSequence. Reference: `app/Models/Core/NumberSequence.php:191-225`.

**TicketReportingService:** period-filtered aggregation, metrics by status/priority/category/staff, avg resolution time, export data prep.

**KnowledgeBaseService:** article CRUD with slug generation, category hierarchy, search/suggest, view tracking, article-ticket linking.

**SlaService:** get SLA for BU+priority, check breach status, get deadline, seed defaults for new BU.

**Step 1:** Create all 5 service files with explicit return types

**Step 2:** Run `vendor/bin/pint --dirty`

**Step 3:** Commit

```bash
git add app/Services/Modules/Ticket/
git commit -m "feat(ticket): add service layer for IT Support module"
```

---

### Task 8: Create form requests

**Files:**
- Create: `app/Http/Requests/Ticket/StoreTicketRequest.php`
- Create: `app/Http/Requests/Ticket/UpdateTicketRequest.php`
- Create: `app/Http/Requests/Ticket/StoreTicketCommentRequest.php`
- Create: `app/Http/Requests/Ticket/AssignTicketRequest.php`
- Create: `app/Http/Requests/Ticket/ChangeTicketStatusRequest.php`
- Create: `app/Http/Requests/Ticket/StoreKnowledgeArticleRequest.php`
- Create: `app/Http/Requests/Ticket/UpdateKnowledgeArticleRequest.php`
- Create: `app/Http/Requests/Ticket/StoreCategoryRequest.php`
- Create: `app/Http/Requests/Ticket/UpdateSlaSettingsRequest.php`

**StoreTicketRequest example:**

```php
public function rules(): array
{
    return [
        'title' => ['required', 'string', 'max:255'],
        'description' => ['required', 'string'],
        'priority' => ['required', 'in:low,medium,high,critical'],
        'category_id' => ['nullable', 'exists:ticket_categories,id'],
        'attachments' => ['nullable', 'array', 'max:5'],
        'attachments.*' => ['file', 'max:10240'], // 10MB
        'form_token' => ['nullable', 'string'],
    ];
}
```

**AssignTicketRequest:** validate `assigned_to` exists in users AND has `is_it_support_admin = true` in ticket's BU.

**ChangeTicketStatusRequest:** validate status transition is allowed.

**Step 1:** Create all 9 form request files

**Step 2:** Run `vendor/bin/pint --dirty`

**Step 3:** Commit

```bash
git add app/Http/Requests/Ticket/
git commit -m "feat(ticket): add form requests for IT Support module"
```

---

## Phase 2: Backend API Surface — Controllers, Routes, Notifications

### Task 9: Create ticket controllers

**Files:**
- Create: `app/Http/Controllers/Modules/Ticket/UserTicketController.php` — for all users (submit, my tickets)
- Create: `app/Http/Controllers/Modules/Ticket/TicketController.php` — for IT Support admin (manage all tickets)
- Create: `app/Http/Controllers/Modules/Ticket/TicketDashboardController.php` — dashboard + SLA settings
- Create: `app/Http/Controllers/Modules/Ticket/TicketReportingController.php` — reporting + export
- Create: `app/Http/Controllers/Modules/Ticket/TicketCategoryController.php` — category CRUD
- Create: `app/Http/Controllers/Modules/Ticket/KnowledgeBaseController.php` — KB browse + admin CRUD
- Create: `app/Http/Controllers/Modules/Ticket/KnowledgeCategoryController.php` — KB category CRUD

**UserTicketController (all authenticated users):**
```php
public function create(): Response           // Inertia::render('Ticket/Create')
public function store(StoreTicketRequest): RedirectResponse
public function myTickets(Request): Response  // Inertia::render('Ticket/MyTickets')
public function show(Ticket): Response        // Inertia::render('Ticket/Show') — read-only for requester
public function addComment(StoreTicketCommentRequest, Ticket): RedirectResponse
```

**TicketDashboardController (IT Support admin):**

Follow `ActivityAdminController` pattern at `app/Http/Controllers/Modules/Activity/ActivityAdminController.php`:
- `resolveScopedBusinessUnitIds()` — current BU + all descendants
- All queries use `->whereIn('business_unit_id', $scopedBuIds)`
- Dashboard returns: summary cards, SLA breach count, charts, recent tickets

**Step 1:** Create all 7 controller files

**Step 2:** Run `vendor/bin/pint --dirty`

**Step 3:** Commit

```bash
git add app/Http/Controllers/Modules/Ticket/
git commit -m "feat(ticket): add controllers for IT Support module"
```

---

### Task 10: Register all IT Support routes

**Files:**
- Modify: `routes/web.php`

**Step 1: Add user routes (all authenticated users)**

```php
Route::middleware(['auth', 'verified', 'ensure.business.unit.selected'])
    ->prefix('it-support')
    ->name('it-support.')
    ->group(function () {
        // User ticket submission
        Route::get('/submit', [UserTicketController::class, 'create'])->name('submit');
        Route::post('/submit', [UserTicketController::class, 'store'])->name('submit.store');
        Route::get('/my-tickets', [UserTicketController::class, 'myTickets'])->name('my-tickets');
        Route::get('/my-tickets/{ticket}', [UserTicketController::class, 'show'])->name('my-tickets.show');
        Route::post('/my-tickets/{ticket}/comment', [UserTicketController::class, 'addComment'])->name('my-tickets.comment');

        // Knowledge base browse (all users)
        Route::get('/knowledge', [KnowledgeBaseController::class, 'browse'])->name('knowledge');
        Route::get('/knowledge/search', [KnowledgeBaseController::class, 'search'])->name('knowledge.search');
        Route::get('/knowledge/{slug}', [KnowledgeBaseController::class, 'article'])->name('knowledge.article');
        Route::post('/knowledge/suggest', [KnowledgeBaseController::class, 'suggestArticles'])->name('knowledge.suggest');
    });
```

**Step 2: Add IT Support admin routes**

```php
Route::middleware(['auth', 'verified', 'ensure.business.unit.selected', 'it.support.access'])
    ->prefix('it-support')
    ->name('it-support.admin.')
    ->group(function () {
        // Dashboard
        Route::get('/dashboard', [TicketDashboardController::class, 'index'])->name('dashboard');

        // SLA Settings
        Route::get('/sla-settings', [TicketDashboardController::class, 'slaSettings'])->name('sla-settings');
        Route::put('/sla-settings', [TicketDashboardController::class, 'updateSlaSettings'])->name('sla-settings.update');

        // All tickets management
        Route::get('/tickets', [TicketController::class, 'index'])->name('tickets.index');
        Route::get('/tickets/{ticket}', [TicketController::class, 'show'])->name('tickets.show');
        Route::get('/tickets/{ticket}/edit', [TicketController::class, 'edit'])->name('tickets.edit');
        Route::put('/tickets/{ticket}', [TicketController::class, 'update'])->name('tickets.update');
        Route::delete('/tickets/{ticket}', [TicketController::class, 'destroy'])->name('tickets.destroy');
        Route::post('/tickets/{ticket}/comment', [TicketController::class, 'addComment'])->name('tickets.comment');
        Route::put('/tickets/{ticket}/change-status', [TicketController::class, 'changeStatus'])->name('tickets.changeStatus');
        Route::post('/tickets/{ticket}/assign', [TicketController::class, 'assignTicket'])->name('tickets.assign');
        Route::post('/tickets/{ticket}/link-article', [KnowledgeBaseController::class, 'linkArticle'])->name('tickets.linkArticle');

        // Reporting
        Route::get('/reporting', [TicketReportingController::class, 'index'])->name('reporting');
        Route::get('/reporting/export/excel', [TicketReportingController::class, 'exportExcel'])->name('reporting.exportExcel');
        Route::get('/reporting/export/pdf', [TicketReportingController::class, 'exportPdf'])->name('reporting.exportPdf');

        // Categories
        Route::resource('/categories', TicketCategoryController::class)->names('categories');

        // Knowledge base admin
        Route::get('/knowledge/manage', [KnowledgeBaseController::class, 'adminIndex'])->name('knowledge.index');
        Route::get('/knowledge/manage/create', [KnowledgeBaseController::class, 'adminCreate'])->name('knowledge.create');
        Route::post('/knowledge/manage', [KnowledgeBaseController::class, 'adminStore'])->name('knowledge.store');
        Route::get('/knowledge/manage/{article}/edit', [KnowledgeBaseController::class, 'adminEdit'])->name('knowledge.edit');
        Route::put('/knowledge/manage/{article}', [KnowledgeBaseController::class, 'adminUpdate'])->name('knowledge.update');
        Route::delete('/knowledge/manage/{article}', [KnowledgeBaseController::class, 'adminDestroy'])->name('knowledge.destroy');
        Route::resource('/knowledge/categories', KnowledgeCategoryController::class)->names('knowledge.categories');
    });
```

**Step 3: Verify routes**

```bash
php artisan route:list --name=it-support
```

**Step 4: Commit**

```bash
git add routes/web.php
git commit -m "feat(ticket): register IT Support module routes"
```

---

### Task 11: Create notification classes and update notification center

**Files:**
- Create: `app/Notifications/Ticket/TicketCreatedNotification.php`
- Create: `app/Notifications/Ticket/TicketAssignedNotification.php`
- Create: `app/Notifications/Ticket/TicketStatusChangedNotification.php`
- Create: `app/Notifications/Ticket/TicketCommentNotification.php`
- Create: `app/Notifications/Ticket/TicketSlaBreachNotification.php`
- Modify: `app/Http/Controllers/NotificationCenterController.php` — add `it_support` to category allowlist

**Step 1: Create 5 notification classes**

Follow existing pattern. Each notification must:
- Use `database` and `broadcast` channels
- Set `category: 'it_support'`
- Set event-specific `event` field (e.g. `ticket_created`, `ticket_assigned`)
- Include `title`, `body`, `action_url` in `toArray()`

Reference: existing notifications under `app/Notifications/Activity/` and `app/Notifications/Purchasing/`.

**Step 2: Update NotificationCenterController category allowlist**

At `app/Http/Controllers/NotificationCenterController.php:14-31`, add `'it_support'` to the allowed categories array.

**Step 3: Run `vendor/bin/pint --dirty`**

**Step 4: Commit**

```bash
git add app/Notifications/Ticket/ app/Http/Controllers/NotificationCenterController.php
git commit -m "feat(ticket): add notification classes and register it_support category"
```

---

### Task 12: Create export classes

**Files:**
- Create: `app/Exports/Modules/Ticket/TicketExport.php`

**Important:** Use `phpoffice/phpspreadsheet` directly (already in OASIS `composer.json`). Do NOT use `maatwebsite/excel` (not available in OASIS).

Reference: check existing export patterns in Activity or Cashflow modules for PhpSpreadsheet usage.

**Step 1:** Create Excel export class with period filtering, summary sheet, detail sheet

**Step 2:** Run `vendor/bin/pint --dirty`

**Step 3:** Commit

```bash
git add app/Exports/Modules/Ticket/
git commit -m "feat(ticket): add export classes for IT Support module"
```

---

## Phase 3: Navigation Integration

### Task 13: Add IT Support to navigation service and update notification center frontend

**Files:**
- Modify: `app/Services/Core/NavigationService.php`
- Modify: notification center frontend (add `it_support` filter tab)

**Step 1: Add navigation method**

Add `canAccessItSupportAdmin()` method:

```php
protected function canAccessItSupportAdmin(User $user, int $businessUnitId): bool
{
    if ($user->isSuperAdmin()) return true;
    if ($user->hasTopManagementAccess()) return true;
    return $user->isAdminInBuOrAncestor('is_it_support_admin', $businessUnitId);
}
```

Reference: `app/Services/Core/NavigationService.php:362-407` for existing pattern.

**Step 2: Add IT Support menu section**

Build menu items:
- All users: Submit Ticket, My Tickets, Knowledge Base
- IT Support admin: + Dashboard, All Tickets, Reporting, Categories, Manage Knowledge, SLA Settings

**Step 3: Add `it_support` filter tab to notification center frontend**

Find the notification center page/component that renders category filter tabs and add `it_support` / `IT Support` alongside existing categories.

Reference: `resources/js/inertia/components/layout/NotificationBell.tsx:1-115` and notification archive page.

**Step 4: Run `vendor/bin/pint --dirty` and `npm exec tsc --noEmit --pretty false`**

**Step 5: Commit**

```bash
git add app/Services/Core/NavigationService.php resources/js/
git commit -m "feat(ticket): add IT Support to navigation and notification center"
```

---

## Phase 4: Frontend — TypeScript Types and React Pages

### Task 14: Create TypeScript types

**Files:**
- Create: `resources/js/inertia/types/ticket.ts`

Define interfaces for: `Ticket`, `TicketCategory`, `TicketComment`, `TicketAttachment`, `TicketSlaSettings`, `KnowledgeCategory`, `KnowledgeArticle`, `TicketDashboardMetrics`, page props interfaces.

**Step 1:** Create types file

**Step 2:** Run `npm exec tsc --noEmit --pretty false`

**Step 3:** Commit

```bash
git add resources/js/inertia/types/ticket.ts
git commit -m "feat(ticket): add TypeScript types for IT Support module"
```

---

### Task 15: Create IT Support Admin Assignment page

**Files:**
- Create: `resources/js/inertia/Pages/Admin/ITSupportAdmins/Index.tsx`

Mirror `resources/js/inertia/Pages/Admin/PurchasingAdmins/Index.tsx` with:
- IT Support-specific colors (e.g. blue/cyan theme)
- Title: "IT Support Assignment"
- Toggle columns: IT Support Admin, Report Access
- Tooltip: "Aktifkan IT Support Admin terlebih dahulu"
- Routes: `admin.it-support-admins.toggle`, `admin.it-support-admins.toggle-report`

**Step 1:** Create the page

**Step 2:** Run `npm exec tsc --noEmit --pretty false`

**Step 3:** Commit

```bash
git add resources/js/inertia/Pages/Admin/ITSupportAdmins/
git commit -m "feat(ticket): add IT Support admin assignment page"
```

---

### Task 16: Create shared Ticket components

**Files:**
- Create: `resources/js/inertia/components/Ticket/TicketStatusBadge.tsx`
- Create: `resources/js/inertia/components/Ticket/TicketPriorityBadge.tsx`
- Create: `resources/js/inertia/components/Ticket/SlaBadge.tsx`
- Create: `resources/js/inertia/components/Ticket/TicketForm.tsx`
- Create: `resources/js/inertia/components/Ticket/CommentSection.tsx`
- Create: `resources/js/inertia/components/Ticket/AttachmentList.tsx`

Reuse existing shared UI primitives from `resources/js/inertia/components/ui/`.

**Step 1:** Create all shared components

**Step 2:** Run `npm exec tsc --noEmit --pretty false`

**Step 3:** Commit

```bash
git add resources/js/inertia/components/Ticket/
git commit -m "feat(ticket): add shared Ticket UI components"
```

---

### Task 17: Create user-facing pages (Submit, My Tickets, KB Browse)

**Files:**
- Create: `resources/js/inertia/Pages/Ticket/Create.tsx` — submit ticket form
- Create: `resources/js/inertia/Pages/Ticket/MyTickets.tsx` — my tickets list
- Create: `resources/js/inertia/Pages/Ticket/Show.tsx` — ticket detail (read-only for requester, full for admin)
- Create: `resources/js/inertia/Pages/Ticket/Knowledge/Browse.tsx` — KB home
- Create: `resources/js/inertia/Pages/Ticket/Knowledge/Article.tsx` — article detail
- Create: `resources/js/inertia/Pages/Ticket/Knowledge/Search.tsx` — search results

**Step 1:** Create all 6 pages

**Step 2:** Run `npm exec tsc --noEmit --pretty false`

**Step 3:** Commit

```bash
git add resources/js/inertia/Pages/Ticket/
git commit -m "feat(ticket): add user-facing ticket and knowledge base pages"
```

---

### Task 18: Create IT Support admin pages (Dashboard, All Tickets, Reporting)

**Files:**
- Create: `resources/js/inertia/Pages/Ticket/Dashboard.tsx`
- Create: `resources/js/inertia/Pages/Ticket/Index.tsx` — all tickets (admin)
- Create: `resources/js/inertia/Pages/Ticket/Edit.tsx` — edit ticket (admin)
- Create: `resources/js/inertia/Pages/Ticket/Reporting.tsx`
- Create: `resources/js/inertia/Pages/Ticket/SlaSettings.tsx`

Dashboard: use Recharts for charts, reuse `DataTable` from `resources/js/inertia/components/admin/DataTable.tsx`.

Index: use TanStack Table with filters (status, priority, category, assigned, SLA breach).

Reporting: period filter, summary cards, charts, export buttons.

**Step 1:** Create all 5 pages

**Step 2:** Run `npm exec tsc --noEmit --pretty false`

**Step 3:** Commit

```bash
git add resources/js/inertia/Pages/Ticket/
git commit -m "feat(ticket): add IT Support admin pages"
```

---

### Task 19: Create category and knowledge base admin pages

**Files:**
- Create: `resources/js/inertia/Pages/Ticket/Categories/Index.tsx`
- Create: `resources/js/inertia/Pages/Ticket/Categories/Create.tsx`
- Create: `resources/js/inertia/Pages/Ticket/Categories/Edit.tsx`
- Create: `resources/js/inertia/Pages/Ticket/Knowledge/Index.tsx` — admin list
- Create: `resources/js/inertia/Pages/Ticket/Knowledge/Create.tsx`
- Create: `resources/js/inertia/Pages/Ticket/Knowledge/Edit.tsx`
- Create: `resources/js/inertia/Pages/Ticket/Knowledge/Categories/Index.tsx`
- Create: `resources/js/inertia/Pages/Ticket/Knowledge/Categories/Create.tsx`
- Create: `resources/js/inertia/Pages/Ticket/Knowledge/Categories/Edit.tsx`

**Step 1:** Create all 9 pages

**Step 2:** Run `npm exec tsc --noEmit --pretty false`

**Step 3:** Commit

```bash
git add resources/js/inertia/Pages/Ticket/
git commit -m "feat(ticket): add category and knowledge base admin pages"
```

---

## Phase 5: Testing

### Task 20: Create backend tests

**Files:**
- Create: `tests/Feature/Modules/Ticket/ITSupportAssignmentTest.php`
- Create: `tests/Feature/Modules/Ticket/TicketCrudTest.php`
- Create: `tests/Feature/Modules/Ticket/TicketStatusWorkflowTest.php`
- Create: `tests/Feature/Modules/Ticket/TicketAssignmentTest.php`
- Create: `tests/Feature/Modules/Ticket/TicketCommentTest.php`
- Create: `tests/Feature/Modules/Ticket/TicketCategoryTest.php`
- Create: `tests/Feature/Modules/Ticket/TicketSlaTest.php`
- Create: `tests/Feature/Modules/Ticket/TicketReportingTest.php`
- Create: `tests/Feature/Modules/Ticket/KnowledgeBaseTest.php`

**Coverage targets:**
- IT Support admin toggle + auto-revoke report access
- Ticket CRUD with BU scoping
- Status transition validation (allowed/blocked transitions)
- Assignment only to IT Support admins
- Comment create/edit/delete with ownership + private/public
- SLA breach detection
- Reporting data accuracy
- Knowledge base CRUD, search, view tracking
- Middleware and gate enforcement

**Step 1:** Create all 9 test files

**Step 2:** Run tests

```bash
php artisan test tests/Feature/Modules/Ticket/
```

**Step 3:** Commit

```bash
git add tests/Feature/Modules/Ticket/
git commit -m "feat(ticket): add backend tests for IT Support module"
```

---

### Task 21: Create frontend tests

**Files:**
- Create: `tests/React/Pages/Ticket/Dashboard.test.tsx`
- Create: `tests/React/Pages/Ticket/Index.test.tsx`
- Create: `tests/React/Pages/Ticket/Show.test.tsx`
- Create: `tests/React/Pages/Ticket/MyTickets.test.tsx`
- Create: `tests/React/Pages/Admin/ITSupportAdmins.test.tsx`

**Step 1:** Create Vitest tests following OASIS React test patterns

**Step 2:** Run tests

```bash
npm exec vitest run tests/React/Pages/Ticket/ tests/React/Pages/Admin/ITSupportAdmins.test.tsx --runInBand
```

**Step 3:** Commit

```bash
git add tests/React/
git commit -m "feat(ticket): add frontend tests for IT Support module"
```

---

## Phase 6: Verification & Documentation

### Task 22: Full verification pass

**Step 1:** Run full PHPUnit suite

```bash
php artisan test
```

**Step 2:** Run Pint

```bash
vendor/bin/pint --dirty
```

**Step 3:** Run TypeScript check

```bash
npm exec tsc --noEmit --pretty false
```

**Step 4:** Run build

```bash
npm run build
```

**Step 5:** Fix any failures

**Step 6:** Commit all fixes

---

### Task 23: Update documentation

**Files:**
- Modify: `docs/exec_plans.md` — add IT Support module migration entry
- Modify: `docs/architecture.md` — add `Modules/Ticket/` to module namespace list

**Step 1:** Add exec plan entry with status, scope, risks, verification

**Step 2:** Update architecture.md module list

**Step 3:** Commit

```bash
git add docs/
git commit -m "docs: add IT Support module to architecture and exec plans"
```

---

## Execution Order Summary

| Phase | Tasks | Focus |
|---|---|---|
| Phase 0 | 1-5 | Foundation: schema, flags, middleware, gates, admin assignment |
| Phase 1 | 6-8 | Backend domain: models, services, form requests |
| Phase 2 | 9-12 | Backend API: controllers, routes, notifications, exports |
| Phase 3 | 13 | Integration: navigation, notification center frontend |
| Phase 4 | 14-19 | Frontend: types, components, all React pages |
| Phase 5 | 20-21 | Testing: backend + frontend tests |
| Phase 6 | 22-23 | Verification + documentation |

**Total: 23 tasks**

**Dependencies:**
- Phase 0 must complete before Phase 1
- Phase 1 must complete before Phase 2
- Phase 2 must complete before Phase 3-4
- Tasks within each phase can be parallelized where file ownership is disjoint
