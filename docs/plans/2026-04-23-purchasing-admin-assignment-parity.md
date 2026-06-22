# Purchasing Admin Assignment Parity Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Create a Purchasing Admin assignment page 1:1 with the Activity Admin assignment page — dedicated toggle UI for `is_purchasing_admin` and a new `is_purchasing_report_access` flag that unlocks the consolidated report for non-management users.

**Architecture:** Clone the Activity Admin pattern:
- New controller `PurchasingAdminAssignmentController` (clone of `ActivityAdminAssignmentController`)
- New frontend page `Admin/PurchasingAdmins/Index.tsx` (clone of `Admin/ActivityAdmins/Index.tsx`)
- New migration for `is_purchasing_report_access` column
- New gate `view-purchasing-reports` for consolidated report access
- Update consolidated report route to use new gate
- Add admin nav menu item

**Tech Stack:** Laravel 12, Inertia.js v2, React, TypeScript, Tailwind.

---

## Authorization Surface Audit (Purchasing-specific)

| # | Surface | File:Line | Current | Change |
|---|---------|-----------|---------|--------|
| 1 | Purchasing Admin gate | `AppServiceProvider.php:161-195` | Checks `is_purchasing_admin` (already cascaded) | No change needed |
| 2 | Nav: Purchasing Admin | `NavigationService.php:358-373` | Already uses cascade | No change needed |
| 3 | Consolidated report route | `routes/web.php:234-236` | Protected by `can:access-purchasing-admin` | **Add `view-purchasing-reports` gate** |
| 4 | No assignment UI | — | No UI exists | **Create controller + page + routes** |
| 5 | No report access flag | `UserBusinessUnit.php` | Only `is_purchasing_admin` | **Add `is_purchasing_report_access`** |

---

## Edge Case Rules (same as Activity Admin)

| Scenario | Behavior |
|----------|----------|
| `is_purchasing_report_access=true` but `is_purchasing_admin=false` | Prevented in UI + backend requires both on same row |
| Admin toggle OFF while report toggle ON | Auto-revoke `is_purchasing_report_access` |
| Multiple BU assignments | Report access granted if any single row has both flags true |

---

### Task 1: Migration — add `is_purchasing_report_access` column

**Files:**
- Create: `database/migrations/modules/purchasing/2026_04_23_100000_add_is_purchasing_report_access_to_user_business_units.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_business_units', function (Blueprint $table) {
            $table->boolean('is_purchasing_report_access')->default(false)->after('is_purchasing_admin');
        });
    }

    public function down(): void
    {
        Schema::table('user_business_units', function (Blueprint $table) {
            $table->dropColumn('is_purchasing_report_access');
        });
    }
};
```

**Commit:** `feat: add is_purchasing_report_access column to user_business_units`

---

### Task 2: Update UserBusinessUnit model

**Files:**
- Modify: `app/Models/Core/UserBusinessUnit.php`

Add `'is_purchasing_report_access'` to `$fillable` (after `is_purchasing_admin`).
Add `'is_purchasing_report_access' => 'boolean'` to `$casts`.

**Commit:** `feat: register is_purchasing_report_access in UserBusinessUnit model`

---

### Task 3: Add `view-purchasing-reports` gate

**Files:**
- Modify: `app/Providers/AppServiceProvider.php`

Add new gate after the existing `access-purchasing-admin` gate:

```php
// View Purchasing Reports Gate - For purchasing admins with report access toggle
Gate::define('view-purchasing-reports', function ($user) {
    if ($user->isSuperAdmin()) {
        return true;
    }

    if ($user->hasTopManagementAccess()) {
        return true;
    }

    // Parent BU manager-and-above
    $hasManagerInParentBU = $user->activeBusinessUnits()
        ->whereHas('businessUnit', fn ($q) => $q->whereNull('parent_id'))
        ->whereHas('position', fn ($q) => $q->managerAndAbove())
        ->exists();

    if ($hasManagerInParentBU) {
        return true;
    }

    // Purchasing admin with report access toggle ON (same row must have both flags)
    return $user->activeBusinessUnits()
        ->where('is_purchasing_admin', true)
        ->where('is_purchasing_report_access', true)
        ->exists();
});
```

**Commit:** `feat: add view-purchasing-reports gate`

---

### Task 4: Update consolidated report route

**Files:**
- Modify: `routes/web.php` (around line 234-236)

The consolidated report route currently sits inside the `can:access-purchasing-admin` group. Add the new gate as additional middleware:

```php
Route::get('/consolidated-report', [PurchasingAdminController::class, 'consolidatedReport'])
    ->middleware('can:view-purchasing-reports')
    ->name('consolidated-report');
```

**Commit:** `feat: protect consolidated report with view-purchasing-reports gate`

---

### Task 5: Create PurchasingAdminAssignmentController

**Files:**
- Create: `app/Http/Controllers/Admin/PurchasingAdminAssignmentController.php`

Clone the pattern from `ActivityAdminAssignmentController` but for purchasing:

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Core\BusinessUnit;
use App\Models\Core\UserBusinessUnit;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PurchasingAdminAssignmentController extends Controller
{
    /**
     * List all user-BU assignments with purchasing admin toggle.
     */
    public function index(Request $request): Response
    {
        $buFilter = $request->get('business_unit_id', '');
        $search = $request->get('search', '');

        $query = UserBusinessUnit::with(['user:id,name,email', 'businessUnit:id,name,code', 'department:id,name', 'position:id,name,access_level'])
            ->where('is_active', true)
            ->when($buFilter, fn ($q) => $q->where('business_unit_id', $buFilter))
            ->when($search, function ($q, $v) {
                $q->whereHas('user', fn ($q2) => $q2->where('name', 'like', "%{$v}%")->orWhere('email', 'like', "%{$v}%"));
            })
            ->orderByDesc('is_purchasing_admin')
            ->orderBy('business_unit_id');

        $assignments = $query->paginate(30)->withQueryString();

        $businessUnits = BusinessUnit::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $adminCounts = UserBusinessUnit::where('is_active', true)
            ->where('is_purchasing_admin', true)
            ->selectRaw('business_unit_id, COUNT(*) as count')
            ->groupBy('business_unit_id')
            ->pluck('count', 'business_unit_id');

        $reportAccessCounts = UserBusinessUnit::where('is_active', true)
            ->where('is_purchasing_report_access', true)
            ->selectRaw('business_unit_id, COUNT(*) as count')
            ->groupBy('business_unit_id')
            ->pluck('count', 'business_unit_id');

        return Inertia::render('Admin/PurchasingAdmins/Index', [
            'assignments' => $assignments,
            'businessUnits' => $businessUnits,
            'adminCounts' => $adminCounts,
            'reportAccessCounts' => $reportAccessCounts,
            'filters' => [
                'business_unit_id' => $buFilter,
                'search' => $search,
            ],
        ]);
    }

    /**
     * Toggle is_purchasing_admin for a user-BU assignment.
     */
    public function toggle(Request $request, int $id)
    {
        $ubu = UserBusinessUnit::findOrFail($id);

        $newAdminState = ! $ubu->is_purchasing_admin;
        $updates = ['is_purchasing_admin' => $newAdminState];

        // Auto-revoke report access when admin is turned OFF
        if (! $newAdminState && $ubu->is_purchasing_report_access) {
            $updates['is_purchasing_report_access'] = false;
        }

        $ubu->update($updates);

        cache()->forget("bu_list:{$ubu->user_id}");

        $status = $newAdminState ? 'assigned as' : 'removed from';

        return back()->with('success', "{$ubu->user?->name} {$status} Purchasing Admin.");
    }

    /**
     * Toggle is_purchasing_report_access for a user-BU assignment.
     * Only works if is_purchasing_admin is already true.
     */
    public function toggleReportAccess(Request $request, int $id)
    {
        $ubu = UserBusinessUnit::findOrFail($id);

        if (! $ubu->is_purchasing_admin && ! $ubu->is_purchasing_report_access) {
            return back()->with('error', 'User must be a Purchasing Admin first.');
        }

        $ubu->update(['is_purchasing_report_access' => ! $ubu->is_purchasing_report_access]);

        cache()->forget("bu_list:{$ubu->user_id}");

        $status = $ubu->is_purchasing_report_access ? 'granted' : 'revoked';

        return back()->with('success', "Purchasing Report access {$status} for {$ubu->user?->name}.");
    }
}
```

**Commit:** `feat: add PurchasingAdminAssignmentController`

---

### Task 6: Add routes

**Files:**
- Modify: `routes/web.php` (in the admin group, after activity-admins routes)

```php
Route::prefix('purchasing-admins')->name('purchasing-admins.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\PurchasingAdminAssignmentController::class, 'index'])->name('index');
    Route::post('/{id}/toggle', [\App\Http\Controllers\Admin\PurchasingAdminAssignmentController::class, 'toggle'])->name('toggle')->whereNumber('id');
    Route::post('/{id}/toggle-report', [\App\Http\Controllers\Admin\PurchasingAdminAssignmentController::class, 'toggleReportAccess'])->name('toggle-report')->whereNumber('id');
});
```

**Commit:** `feat: add purchasing admin assignment routes`

---

### Task 7: Add admin navigation menu item

**Files:**
- Modify: `app/Services/Core/NavigationService.php` (in `getAdministrationSection()`, after the Activity Admin Assignment item around line 311)

Add:
```php
$items[] = [
    'name' => 'Purchasing Admin Assignment',
    'href' => route('admin.purchasing-admins.index'),
    'icon' => 'shopping-cart',
    'active' => request()->routeIs('admin.purchasing-admins.*'),
];
```

**Commit:** `feat: add purchasing admin assignment to admin navigation`

---

### Task 8: Create frontend page

**Files:**
- Create: `resources/js/inertia/Pages/Admin/PurchasingAdmins/Index.tsx`

Clone from `Admin/ActivityAdmins/Index.tsx` with these changes:
- Replace all "Activity" text with "Purchasing"
- Replace `is_activity_admin` with `is_purchasing_admin`
- Replace `is_activity_report_access` with `is_purchasing_report_access`
- Replace route names: `admin.activity-admins.*` → `admin.purchasing-admins.*`
- Change admin toggle color from blue to green (to distinguish from activity)
- Change report toggle color from purple to indigo
- Update page title and descriptions

**Commit:** `feat: add purchasing admin assignment frontend page`

---

## Consolidated Report Route — Middleware Stacking Note

The consolidated report route sits inside `can:access-purchasing-admin` group AND gets `can:view-purchasing-reports` added. Both gates are enforced (AND logic):
- User must pass `access-purchasing-admin` (is a purchasing admin or top management)
- AND must pass `view-purchasing-reports` (has report access toggle or is top management)

For top management / super admin, both gates pass automatically. For regular purchasing admins, they need `is_purchasing_report_access=true` to see the consolidated report.

The controller `PurchasingAdminController::consolidatedReport()` (line 765) has **no internal authorization** — it relies entirely on route middleware. No controller changes needed.

---

## Summary: All Files Changed

| # | File | Change |
|---|------|--------|
| 1 | `database/migrations/.../2026_04_23_100000_...` | New column `is_purchasing_report_access` |
| 2 | `app/Models/Core/UserBusinessUnit.php` | Register new column |
| 3 | `app/Providers/AppServiceProvider.php` | Add `view-purchasing-reports` gate |
| 4 | `routes/web.php` | Update consolidated report route + add assignment routes |
| 5 | `app/Http/Controllers/Admin/PurchasingAdminAssignmentController.php` | New controller |
| 6 | `resources/js/inertia/Pages/Admin/PurchasingAdmins/Index.tsx` | New frontend page |
| 7 | `app/Services/Core/NavigationService.php` | Add "Purchasing Admin Assignment" to admin nav |

## Files NOT Changed

| File | Reason |
|------|--------|
| `AppServiceProvider.php` `access-purchasing-admin` gate | Already uses cascade from previous implementation |
| `NavigationService.php` `canAccessPurchasingAdmin()` | Already uses cascade |
| `AdminTaskAssignmentService.php` | Data query — not authorization |
| `PurchasingAdminController.php` | Data queries for admin lists — not authorization |
| `Department.php` | Data query helper — not authorization |
