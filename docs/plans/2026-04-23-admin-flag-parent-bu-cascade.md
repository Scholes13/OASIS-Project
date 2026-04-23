# Admin Flag Parent-BU Cascade + Activity Report Access

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Two related changes:
1. Make `is_activity_admin` and `is_purchasing_admin` flags cascade top-down through the BU hierarchy.
2. Add a new `is_activity_report_access` toggle (Super Admin only) that unlocks BOD-level Activity Reporting for an activity admin.

**Architecture:**
- Add `User::isAdminInBuOrAncestor()` for cascade logic (walks up BU tree once, cached per request).
- Add `is_activity_report_access` column to `user_business_units`.
- Update all 7 authorization surfaces that check these flags.
- Add toggle UI to Activity Admins page.

**Tech Stack:** Laravel 12, Eloquent, existing `BusinessUnit` parent/child hierarchy.

---

## Complete Authorization Surface Audit

Every place in the codebase that references `is_activity_admin`, `is_purchasing_admin`, or `view-reports` (verified via `grep -rn` across `app/`):

| # | Surface | File:Line | Flag | Action |
|---|---------|-----------|------|--------|
| 1 | Activity Admin middleware | `app/Http/Middleware/ActivityAdminAccess.php:40` | `is_activity_admin` | **Use cascade** |
| 2 | Activity Reporting middleware | `app/Http/Middleware/ActivityReportingAccess.php:31-44` | `view-reports` related | **Add `is_activity_report_access` check** |
| 3 | Purchasing Admin gate | `app/Providers/AppServiceProvider.php:187` | `is_purchasing_admin` | **Use cascade** |
| 4 | View Reports gate | `app/Providers/AppServiceProvider.php:143-149` | `view-reports` | **Add `is_activity_report_access` check** |
| 5 | Nav: Activity Admin | `app/Services/Core/NavigationService.php:408` | `is_activity_admin` | **Use cascade** |
| 6 | Nav: Purchasing Admin | `app/Services/Core/NavigationService.php:370` | `is_purchasing_admin` | **Use cascade** |
| 7 | Backdate notifications | `app/Services/Modules/Activity/BackdatePermissionService.php:72` | `is_activity_admin` | No cascade — data query to find who to notify |
| 8 | Purchasing task assignment | `app/Services/Modules/Purchasing/Admin/AdminTaskAssignmentService.php:26,35,54,69` | `is_purchasing_admin` | No cascade — data query to auto-assign tasks |
| 9 | Purchasing admin performance | `app/Http/Controllers/Modules/Purchasing/Admin/PurchasingAdminController.php:701` | `is_purchasing_admin` | No cascade — data query listing admins for report display |
| 10 | Purchasing audit history filter | `app/Http/Controllers/Modules/Purchasing/Admin/PurchasingAdminController.php:938,1020` | `is_purchasing_admin` | No cascade — data query for filter dropdown |
| 11 | Purchasing admin hardcoded prop | `app/Http/Controllers/Modules/Purchasing/Admin/PurchasingAdminController.php:163` | `is_purchasing_admin` | No cascade — static prop passed to frontend (TODO in code) |
| 12 | Department model helper | `app/Models/Core/Department.php:175` | `is_purchasing_admin` | No cascade — data query listing dept admins |
| 13 | UserBusinessUnit model | `app/Models/Core/UserBusinessUnit.php:60-61,69-70` | both | No cascade — model definition (fillable/casts) |
| 14 | Activity Admin assignment UI | `app/Http/Controllers/Admin/ActivityAdminAssignmentController.php:28,39,61` | `is_activity_admin` | No cascade — toggle UI itself. **Add report toggle.** |
| 15 | Frontend: Activity Dashboard | `resources/js/inertia/Pages/Activity/ActivityDashboard.tsx:140,161,165` | `canViewReports` prop | No change — prop comes from controller via gate |
| 16 | Frontend: Activity Admins | `resources/js/inertia/Pages/Admin/ActivityAdmins/Index.tsx` | `is_activity_admin` | **Add `is_activity_report_access` toggle** |

**Cascade vs Data Query distinction (rows 7-14):**
These are all **data queries** — they find specific users for notification, task assignment, report display, or filter dropdowns. Cascade applies only to **authorization** (can the user access the page/feature). If a parent-BU admin needs to appear in a child BU's admin list for notifications or task assignment, they should be explicitly assigned in that child BU too.

---

## Edge Case Rules

| Scenario | Behavior |
|----------|----------|
| `is_activity_report_access=true` but `is_activity_admin=false` | **Prevented in UI** — report toggle only visible/clickable when admin toggle is ON. Backend gate also requires BOTH flags true on the **same** `user_business_units` row. Splitting flags across different rows does NOT qualify. |
| User has multiple BU assignments with different flags | Admin cascade: checked per current BU (walks up ancestors of current BU). Report access: granted only when a **single** `user_business_units` row has both `is_activity_admin=true` AND `is_activity_report_access=true`. Having `is_activity_admin` on row A and `is_activity_report_access` on row B does NOT grant report access. |
| Admin toggle turned OFF while report toggle is ON | Backend auto-sets `is_activity_report_access=false` when `is_activity_admin` is toggled OFF. |
| Cache after toggle | Clear `bu_list:{user_id}` (existing) — sufficient because nav/middleware re-query on each request. No other caches store admin flag state. |

---

## Part A: Admin Flag Cascade (top-down)

### Task 1: Add `isAdminInBuOrAncestor()` helper to User model

**Files:**
- Modify: `app/Models/Core/User.php`

**Add after `canAccessBusinessUnit()` method:**

```php
/**
 * Check if user has an admin flag in the given BU or any of its ancestor BUs.
 *
 * Enables top-down cascade: admin in parent BU = admin in all descendant BUs.
 * Does NOT change user position or access level.
 *
 * Performance: 2 queries max — one for user's admin BU IDs, one for the
 * target BU's ancestor chain. Ancestor walk is in-memory using a pre-loaded
 * parent_id map to avoid N+1.
 *
 * @param  string  $flag  Column name: 'is_activity_admin' or 'is_purchasing_admin'
 * @param  int     $buId  The business unit to check access for
 */
public function isAdminInBuOrAncestor(string $flag, int $buId): bool
{
    // Query 1: Get all BU IDs where user has this admin flag
    $adminBuIds = $this->activeBusinessUnits()
        ->where($flag, true)
        ->pluck('business_unit_id')
        ->toArray();

    if (empty($adminBuIds)) {
        return false;
    }

    // Direct match — no ancestor walk needed
    if (in_array($buId, $adminBuIds, true)) {
        return true;
    }

    // Query 2: Load all BUs as id=>parent_id map (cached per request via static)
    static $buParentMap = null;
    if ($buParentMap === null) {
        $buParentMap = \App\Models\Core\BusinessUnit::pluck('parent_id', 'id')->toArray();
    }

    // Walk up ancestor chain entirely in-memory
    $visited = [$buId];
    $currentParentId = $buParentMap[$buId] ?? null;

    while ($currentParentId) {
        if (in_array($currentParentId, $visited, true)) {
            break; // cycle detection
        }
        $visited[] = $currentParentId;

        if (in_array($currentParentId, $adminBuIds, true)) {
            return true;
        }

        $currentParentId = $buParentMap[$currentParentId] ?? null;
    }

    return false;
}
```

> **Performance:** Query 1 gets user's admin BU IDs. Query 2 loads the full BU parent map (tiny table, ~5-10 rows, cached in static var for the request lifetime). Ancestor walk is pure in-memory array lookups — zero additional DB queries. Max 3-4 iterations for typical WG→WNS→child hierarchy. The static cache assumes BU hierarchy is not mutated during the same request — this is safe because BU structure changes are admin-only operations that happen in separate requests.

**Commit:** `feat: add User::isAdminInBuOrAncestor() for parent-BU cascade`

---

### Task 2: Update ActivityAdminAccess middleware

**Files:**
- Modify: `app/Http/Middleware/ActivityAdminAccess.php:38-41`

**Change:**
```php
// Before (line 38-41)
$isActivityAdmin = $user->activeBusinessUnits()
    ->where('business_unit_id', $currentBuId)
    ->where('is_activity_admin', true)
    ->exists();

// After
$isActivityAdmin = $user->isAdminInBuOrAncestor('is_activity_admin', $currentBuId);
```

**Commit:** `feat: cascade activity admin flag through parent BU hierarchy`

---

### Task 3: Update `access-purchasing-admin` gate

**Files:**
- Modify: `app/Providers/AppServiceProvider.php:183-192`

**Change:**
```php
// Before (line 183-192)
if ($currentBuId) {
    return $user->activeBusinessUnits()
        ->where('business_unit_id', $currentBuId)
        ->where('is_purchasing_admin', true)
        ->whereHas('department', function ($query) {
            $query->where('is_purchasing_department', true);
        })
        ->exists();
}

// After
if ($currentBuId) {
    return $user->isAdminInBuOrAncestor('is_purchasing_admin', $currentBuId);
}
```

> **Note on `is_purchasing_department` removal:** The original check requires the user's department in the current BU to be a purchasing department. For cascade, this doesn't make sense — a parent-BU admin may not have a department assignment in the child BU at all. The purchasing boundary is still preserved by the gate's earlier checks: super admin bypass (line 164), parent-BU top management bypass (line 169), and parent-BU manager bypass (line 179). The cascade only adds one new path: "user has `is_purchasing_admin=true` in an ancestor BU of the current BU." The `is_purchasing_department` constraint continues to apply for **direct** (non-cascaded) assignments via `AdminTaskAssignmentService` (unchanged) and `Department::purchasingAdmins()` (unchanged).

**Commit:** `feat: cascade purchasing admin flag through parent BU hierarchy`

---

### Task 4: Update NavigationService

**Files:**
- Modify: `app/Services/Core/NavigationService.php:406-409` and `:368-372`

**Change `canAccessActivityAdmin()` (line 406-409):**
```php
// Before
return $user->activeBusinessUnits()
    ->where('business_unit_id', $businessUnitId)
    ->where('is_activity_admin', true)
    ->exists();

// After
return $user->isAdminInBuOrAncestor('is_activity_admin', $businessUnitId);
```

**Change `canAccessPurchasingAdmin()` (line 368-372):**
```php
// Before
return $user->businessUnits()
    ->where('business_unit_id', $businessUnitId)
    ->where('is_purchasing_admin', true)
    ->whereHas('department', fn ($q) => $q->where('is_purchasing_department', true))
    ->exists();

// After
return $user->isAdminInBuOrAncestor('is_purchasing_admin', $businessUnitId);
```

**Commit:** `feat: align navigation visibility with parent-BU admin cascade`

---

## Part B: Activity Report Access Toggle

### Task 5: Migration — add `is_activity_report_access` column

**Files:**
- Create: `database/migrations/modules/activity/2026_04_23_000000_add_is_activity_report_access_to_user_business_units.php`

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
            $table->boolean('is_activity_report_access')->default(false)->after('is_activity_admin');
        });
    }

    public function down(): void
    {
        Schema::table('user_business_units', function (Blueprint $table) {
            $table->dropColumn('is_activity_report_access');
        });
    }
};
```

**Commit:** `feat: add is_activity_report_access column to user_business_units`

---

### Task 6: Update UserBusinessUnit model

**Files:**
- Modify: `app/Models/Core/UserBusinessUnit.php`

Add `'is_activity_report_access'` to `$fillable` array (after `is_activity_admin`).
Add `'is_activity_report_access' => 'boolean'` to `$casts` array.

**Commit:** `feat: register is_activity_report_access in UserBusinessUnit model`

---

### Task 7: Update `view-reports` gate

**Files:**
- Modify: `app/Providers/AppServiceProvider.php:143-149`

**Change:**
```php
// Before (line 143-149)
Gate::define('view-reports', function ($user) {
    if ($user->isSuperAdmin()) {
        return true;
    }

    return $user->hasTopManagementAccess();
});

// After
Gate::define('view-reports', function ($user) {
    if ($user->isSuperAdmin()) {
        return true;
    }

    if ($user->hasTopManagementAccess()) {
        return true;
    }

    // Activity admin with report access toggle ON (any BU assignment)
    return $user->activeBusinessUnits()
        ->where('is_activity_admin', true)
        ->where('is_activity_report_access', true)
        ->exists();
});
```

> **Both flags required:** `is_activity_admin=true` AND `is_activity_report_access=true`. Report access alone is not enough.

**Commit:** `feat: allow activity report access toggle to unlock BOD reporting`

---

### Task 8: Update ActivityReportingAccess middleware

**Files:**
- Modify: `app/Http/Middleware/ActivityReportingAccess.php:29-44`

**Change:** Add `is_activity_report_access` check after the permission checks:

```php
// After line 33 (after the permission check block), add:

// Activity admin with report access toggle
$hasReportAccess = $user->activeBusinessUnits()
    ->where('is_activity_admin', true)
    ->where('is_activity_report_access', true)
    ->exists();

if ($hasReportAccess) {
    return $next($request);
}
```

**Commit:** `feat: align ActivityReportingAccess middleware with report toggle`

---

### Task 9: Update ActivityAdminAssignmentController

**Files:**
- Modify: `app/Http/Controllers/Admin/ActivityAdminAssignmentController.php`

**9a: Update `index()` to include report access data:**

In the `with()` call, the `is_activity_report_access` field is already loaded as part of the `UserBusinessUnit` model (it's a column). No query change needed — just ensure the frontend receives it.

Add report access counts alongside admin counts:

```php
$reportAccessCounts = UserBusinessUnit::where('is_active', true)
    ->where('is_activity_report_access', true)
    ->selectRaw('business_unit_id, COUNT(*) as count')
    ->groupBy('business_unit_id')
    ->pluck('count', 'business_unit_id');
```

Pass to Inertia:
```php
'reportAccessCounts' => $reportAccessCounts,
```

**9b: Add `toggleReportAccess()` method:**

```php
/**
 * Toggle is_activity_report_access for a user-BU assignment.
 * Only works if is_activity_admin is already true.
 */
public function toggleReportAccess(Request $request, int $id)
{
    $ubu = UserBusinessUnit::findOrFail($id);

    // Cannot grant report access without admin access
    if (! $ubu->is_activity_admin && ! $ubu->is_activity_report_access) {
        return back()->with('error', 'User must be an Activity Admin first.');
    }

    $ubu->update(['is_activity_report_access' => ! $ubu->is_activity_report_access]);

    cache()->forget("bu_list:{$ubu->user_id}");

    $status = $ubu->is_activity_report_access ? 'granted' : 'revoked';

    return back()->with('success', "Activity Report access {$status} for {$ubu->user?->name}.");
}
```

**9c: Update `toggle()` to auto-revoke report access when admin is turned OFF:**

```php
public function toggle(Request $request, int $id)
{
    $ubu = UserBusinessUnit::findOrFail($id);

    $newAdminState = ! $ubu->is_activity_admin;
    $updates = ['is_activity_admin' => $newAdminState];

    // Auto-revoke report access when admin is turned OFF
    if (! $newAdminState && $ubu->is_activity_report_access) {
        $updates['is_activity_report_access'] = false;
    }

    $ubu->update($updates);

    cache()->forget("bu_list:{$ubu->user_id}");

    $status = $newAdminState ? 'assigned as' : 'removed from';

    return back()->with('success', "{$ubu->user?->name} {$status} Activity Admin.");
}
```

**Commit:** `feat: add report access toggle to activity admin assignment`

---

### Task 10: Add route for report access toggle

**Files:**
- Modify: `routes/web.php` (in the activity-admins group, after the existing toggle route)

```php
Route::post('/{id}/toggle-report', [ActivityAdminAssignmentController::class, 'toggleReportAccess'])
    ->name('toggle-report');
```

This will produce route name: `admin.activity-admins.toggle-report`

**Commit:** `feat: add route for activity report access toggle`

---

### Task 11: Update Activity Admins frontend page

**Files:**
- Modify: `resources/js/inertia/Pages/Admin/ActivityAdmins/Index.tsx`

**11a: Update TypeScript interface:**

```typescript
interface Assignment {
    id: number;
    is_activity_admin: boolean;
    is_activity_report_access: boolean;  // NEW
    is_primary: boolean;
    user: { id: number; name: string; email: string } | null;
    business_unit: BusinessUnit | null;
    department: { id: number; name: string } | null;
    position: { id: number; name: string; access_level: string } | null;
}

interface Props extends PageProps {
    assignments: PaginatedData<Assignment>;
    businessUnits: BusinessUnit[];
    adminCounts: Record<number, number>;
    reportAccessCounts: Record<number, number>;  // NEW
    filters: { business_unit_id: string; search: string };
}
```

**11b: Add report access toggle column:**

In the table, add a new column after the Activity Admin toggle:

- Label: "Report Access"
- Toggle button that calls `route('admin.activity-admins.toggle-report', { id })`
- Only enabled/visible when `is_activity_admin === true`
- Different color (e.g., purple/indigo) to distinguish from admin toggle (blue)
- Disabled state with tooltip "Aktifkan Activity Admin terlebih dahulu" when admin is OFF

**Commit:** `feat: add report access toggle UI to activity admins page`

---

## Summary: All Files Changed

| # | File | Change |
|---|------|--------|
| 1 | `app/Models/Core/User.php` | Add `isAdminInBuOrAncestor()` |
| 2 | `app/Http/Middleware/ActivityAdminAccess.php` | Use cascade |
| 3 | `app/Providers/AppServiceProvider.php` | Cascade purchasing gate + report access in view-reports gate |
| 4 | `app/Services/Core/NavigationService.php` | Cascade both nav checks |
| 5 | `database/migrations/.../2026_04_23_...` | New column |
| 6 | `app/Models/Core/UserBusinessUnit.php` | Register new column |
| 7 | `app/Http/Middleware/ActivityReportingAccess.php` | Add report access check |
| 8 | `app/Http/Controllers/Admin/ActivityAdminAssignmentController.php` | Add toggle + auto-revoke |
| 9 | `routes/web.php` | Add toggle-report route |
| 10 | `resources/js/inertia/Pages/Admin/ActivityAdmins/Index.tsx` | Add toggle UI |

## Files NOT Changed (intentionally)

| File | Reason |
|------|--------|
| `BackdatePermissionService.php` | Data query — finds users to notify, not authorize (audit row 7) |
| `AdminTaskAssignmentService.php` | Data query — auto-assigns tasks to specific BU admins (audit row 8) |
| `PurchasingAdminController.php` | Data queries — lists admins for report display and filter dropdowns (audit rows 9-11) |
| `Department.php` | Data query — `purchasingAdmins()` helper for listing (audit row 12) |
| `ActivityInertiaController.php` | **Verified:** `reportingDashboard()` at line 645-658 only calls `$this->authorize('view-reports')` then returns an Inertia page with `dateRange` and `initialData => null`. All actual data is loaded via API endpoints (protected by `activity.reporting.access` middleware + `can:view-reports` gate). No BU-scoped filtering in the controller — it's a shell page. The gate change in Task 7 is sufficient. |
| `ActivityDashboard.tsx` | `canViewReports` prop comes from `ActivityInertiaController::dashboard()` which calls `canViewExecutiveDashboard()` — this already uses the `view-reports` gate internally. No frontend change needed. |
