# Cross-Module Audit Fixes

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Fix 28 bugs, logic gaps, and missing journeys discovered during a full-module audit across Activity, Purchasing, CashflowProjection, Notifications, and Core.

**Architecture:** Targeted fixes per finding — authorization hardening, status transition guards, cleanup cascades, payload normalization, and UI consistency. No new features; only closing gaps in existing journeys.

**Tech Stack:** Laravel 12, PHP 8.2, Inertia.js v2, React 19, TypeScript.

---

## Batch 1: Activity Module (Findings 1–4, 11–14)

### Task 1: Add authorization to task destroy (Finding #1)

**Files:**
- Modify: `app/Http/Controllers/Modules/Activity/ActivityInertiaController.php:577-594`

**Fix:** Add `canEditTask()` check before delete, matching the `update()` method pattern.

```php
public function destroy(EmployeeTask $task)
{
    $user = Auth::user();
    $buId = session('current_business_unit_id');

    abort_unless($this->canEditTask($task, $user, $buId), 403);

    try {
        $task->delete();
        $this->clearCache($buId, $user->id);

        return redirect()
            ->route('activity.task.index')
            ->with('success', 'Task deleted successfully.');
    } catch (\Exception $e) {
        report($e);

        return back()->with('error', 'Failed to delete task.');
    }
}
```

### Task 2: Add cascade deletes for task relations (Finding #2)

**Files:**
- Modify: `app/Models/Modules/Activity/EmployeeTask.php` — add `booted()` static method

**Fix:** Use model `deleting` event to clean up participants, comments, and attachments. Check if DB-level cascades already exist in migrations first. If FK cascades exist, this is defense-in-depth.

```php
protected static function booted(): void
{
    static::deleting(function (EmployeeTask $task): void {
        $task->participants()->detach();
        $task->comments()->forceDelete();
        $task->attachments()->delete();
    });
}
```

**Note:** Comments use SoftDeletes, so `forceDelete()` ensures cleanup on task deletion. Attachments should also be cleaned.

### Task 3: Add status transition guard to quick-update path (Finding #3)

**Files:**
- Modify: `app/Http/Controllers/Modules/Activity/ActivityInertiaController.php:420-421`

**Fix:** Add an allowed-transitions map before applying status changes.

```php
if (isset($validated['status'])) {
    $allowedTransitions = [
        'planned' => ['in_progress', 'completed', 'cancelled'],
        'in_progress' => ['completed', 'cancelled', 'planned'],
        'completed' => [],  // completed is terminal for quick-update
        'cancelled' => [],  // cancelled is terminal for quick-update
    ];

    $currentStatus = $task->status;
    $newStatus = $validated['status'];

    if ($currentStatus === $newStatus || !in_array($newStatus, $allowedTransitions[$currentStatus] ?? [], true)) {
        return back()->with('error', 'Invalid status transition.');
    }

    $updateData['status'] = $newStatus;
    // ... rest of status logic
}
```

**Note:** `in_progress → planned` is allowed but requires `confirm_reset_execution` (handled by validation). `completed` and `cancelled` are terminal for quick-update — full edit is needed to revert.

### Task 4: Add authorization to backdate approve/reject (Finding #4)

**Files:**
- Modify: `app/Http/Controllers/Modules/Activity/ActivityAdminController.php:334-357`

**Fix:** The backdate routes are already inside the `activity.admin.access` middleware group (line 302 of routes/web.php), so only activity admins can reach them. However, add an explicit check that the permission belongs to the admin's BU scope.

```php
public function approveBackdate(Request $request, int $id)
{
    abort_unless(config('features.backdate_approval'), 404);

    $permission = BackdatePermission::findOrFail($id);

    // Verify the permission belongs to the admin's BU scope
    $scopedBuIds = $this->resolveScopedBusinessUnitIds();
    abort_unless(in_array((int) $permission->business_unit_id, $scopedBuIds, true), 403);

    $this->backdateService->approveRequest($permission, Auth::user());

    return back()->with('success', 'Backdate request approved.');
}

public function rejectBackdate(Request $request, int $id)
{
    abort_unless(config('features.backdate_approval'), 404);

    $request->validate(['reason' => 'required|string|max:500']);

    $permission = BackdatePermission::findOrFail($id);

    $scopedBuIds = $this->resolveScopedBusinessUnitIds();
    abort_unless(in_array((int) $permission->business_unit_id, $scopedBuIds, true), 403);

    $this->backdateService->rejectRequest($permission, Auth::user(), $request->input('reason'));

    return back()->with('success', 'Backdate request rejected.');
}
```

### Task 5: Set edited_at/edited_by on partial updates (Finding #11)

**Files:**
- Modify: `app/Http/Controllers/Modules/Activity/ActivityInertiaController.php:412-435`

**Fix:** Add `edited_at` and `edited_by` to the partial update data.

After `$updateData` is built and before `$task->update($updateData)`, add:
```php
$updateData['edited_at'] = now();
$updateData['edited_by'] = $user->id;
```

### Task 6: Enforce confirm_reset_execution server-side (Finding #12)

**Files:**
- Modify: `app/Http/Requests/Modules/Activity/UpdateActivityTaskRequest.php`

**Fix:** The validation already has `confirm_reset_execution` as `sometimes|boolean` in the partial update rules. Add a `withValidator` check for the quick-update path:

In the `withValidator` method, after the existing `validateQuickAction` call, add:
```php
// Block reset to planned without confirmation when task has execution history
if ($isStatusUpdate && $status === 'planned' && $task->started_at) {
    if (!$this->boolean('confirm_reset_execution')) {
        $validator->errors()->add('status', 'Resetting execution history requires confirmation.');
    }
}
```

**Note:** Check if this logic already exists in the current `withValidator`. If it does, this finding is already mitigated.

### Task 7: Document comment visibility as intentional (Finding #13)

**Files:**
- No code change needed if same-department comment visibility is intentional.

**Decision needed:** Is it intentional that same-department users can see comments but not write them? If yes, add a code comment. If no, restrict comment loading to participants only in `getSelectedTaskForModal()`.

For now, add a code comment in `ActivityInertiaController.php` at the `comments_data` loading section:
```php
// Note: Comments are visible to all users who can view the task (same department).
// Write access is restricted to creator/participants in TaskCommentController.
```

### Task 8: Add participant columns to admin export (Finding #14)

**Files:**
- Modify: `app/Services/Modules/Activity/ActivityAdminExportService.php`

**Fix:** Add participant count and participant names columns to the Detail sheet, matching the pattern from `ActivityExportService.php`.

In the detail sheet column headers, add after the existing columns:
```php
'Participants',
'Participant Count',
```

In the detail sheet row data, add:
```php
$task->participants->sortBy('name')->pluck('name')->implode(', '),
$task->participants->count(),
```

---

## Batch 2: Purchasing Module (Findings 5–8, 15–18)

### Task 9: Add ownership check to ST resubmit (Finding #5)

**Files:**
- Modify: `app/Http/Controllers/Modules/Purchasing/StockRequest/StockRequestController.php:657-692`

**Fix:** Add creator check after BU access check.

```php
// Verify ownership
if ((int) $stockRequest->created_by !== Auth::id()) {
    abort(403, 'Only the request creator can resubmit.');
}
```

### Task 10: Add ownership/admin check to ST void (Finding #6)

**Files:**
- Modify: `app/Http/Controllers/Modules/Purchasing/StockRequest/StockRequestController.php:547-569`

**Fix:** Add creator or purchasing admin check.

```php
$user = Auth::user();
$isOwner = (int) $stockRequest->created_by === $user->id;
$isAdmin = $user->isAdminInBuOrAncestor('is_purchasing_admin', (int) session('current_business_unit_id'));

if (!$isOwner && !$isAdmin) {
    abort(403, 'Only the request creator or a purchasing admin can void this request.');
}
```

### Task 11: Block self-approval in workflow creation (Finding #7)

**Files:**
- Modify: `app/Services/Modules/Purchasing/PurchaseRequest/ApprovalWorkflowService.php:75-111`
- Modify: `app/Http/Controllers/Modules/Purchasing/StockRequest/StockRequestController.php` (ST workflow creation)

**Fix:** In `createWorkflowFromRequest()`, add a check that no approver matches the request creator.

For PR:
```php
// Block self-approval
if ((int) $step['approver_id'] === (int) $purchaseRequest->created_by) {
    throw new \Exception('Request creator cannot be assigned as an approver.');
}
```

For ST, find the equivalent workflow creation and add the same check.

### Task 12: Add deactivated approver detection (Finding #8)

**Files:**
- Modify: `app/Services/Modules/Purchasing/PurchaseRequest/ApprovalWorkflowService.php`

**Fix:** When processing approval or checking current approval, verify the approver is still active. Add a helper method:

```php
protected function validateApproverIsActive(int $approverId): void
{
    $approver = User::find($approverId);
    if (!$approver || !$approver->is_active) {
        throw new \Exception('The assigned approver is no longer active. Please contact an administrator to reassign the approval.');
    }
}
```

Call this in the approval processing flow before acting on the approval.

### Task 13: Replace raw /storage/ links for item images (Finding #15)

**Files:**
- Modify: `resources/js/inertia/Pages/Purchasing/StockRequest/Show.tsx:491-499`
- Check: `resources/js/inertia/Pages/Purchasing/PurchaseRequest/Show.tsx` for same pattern

**Fix:** Replace `/storage/${item.image_path}` with a route-based URL. If no dedicated image route exists, create one or use a signed URL approach.

Simplest fix: use the existing storage URL helper but ensure the storage disk is `public`:
```tsx
href={`/storage/${item.image_path}`}
```
This is actually fine if the storage disk is `public` and the files are meant to be publicly accessible within the app. The real concern is whether these images should be access-controlled. For item images on a request that's already visible to the user, public storage is acceptable.

**Decision:** If item images are not sensitive, keep `/storage/` links but add a code comment explaining the access model. If they are sensitive, create an authenticated route.

### Task 14: Fix hardcoded admin role flags (Finding #16)

**Files:**
- Modify: `app/Http/Controllers/Modules/Purchasing/Admin/PurchasingAdminController.php:162-165`

**Fix:** Replace hardcoded values with actual permission checks.

```php
'userRole' => [
    'is_purchasing_admin' => $user->isAdminInBuOrAncestor('is_purchasing_admin', (int) session('current_business_unit_id')),
    'is_management' => $user->hasTopManagementAccess() || $user->isSuperAdmin(),
],
```

### Task 15: Fix polymorphic admin search (Finding #17)

**Files:**
- Modify: `app/Http/Controllers/Modules/Purchasing/Admin/PurchasingAdminController.php:234-264`

**Fix:** Replace try/catch generic search with type-safe filtering by taskable_type.

```php
->when($search, function ($query, $search) {
    $query->where(function ($q) use ($search) {
        $q->whereHasMorph('taskable', [PurchaseRequest::class], function ($sub) use ($search) {
            $sub->where('pr_number', 'like', "%{$search}%");
        })->orWhereHasMorph('taskable', [StockRequest::class], function ($sub) use ($search) {
            $sub->where('st_number', 'like', "%{$search}%");
        });
    });
})
```

### Task 16: Add confirmation dialog for resubmit (Finding #18)

**Files:**
- Modify: `resources/js/inertia/Pages/Purchasing/StockRequest/Show.tsx` — resubmit button
- Modify: `resources/js/inertia/Pages/Purchasing/PurchaseRequest/Show.tsx` — resubmit button

**Fix:** Wrap the resubmit action in a confirmation dialog (use the existing `ConfirmDialog` component pattern from TaskDetailModal).

---

## Batch 3: Cashflow + Notifications + Core (Findings 9–10, 19–22)

### Task 17: Add canManage() check to cashflow delete (Finding #9)

**Files:**
- Modify: `app/Http/Controllers/Modules/CashflowProjection/CashflowProjectionController.php:501`

**Fix:** Change `canAccess()` to `canManage()`:

```php
abort_unless($this->accessService->canManage($user, $businessUnitId), 403);
```

### Task 18: Fix super-admin BU switch logo-only reset (Finding #10)

**Files:**
- Modify: `app/Http/Middleware/EnsureBusinessUnitSelected.php:30-52`

**Fix:** When only the logo is missing, fetch the CURRENT BU (not primary BU) and only update the logo session key.

```php
if ($needsLogoUpdate) {
    // Only logo is missing — restore it for the current BU without changing BU selection
    $currentBu = \App\Models\Core\BusinessUnit::find(session('current_business_unit_id'));
    if ($currentBu) {
        session(['current_business_unit_logo' => $currentBu->logo]);
    }
    // Skip the full reset path
} elseif (!session('current_business_unit_code') || !session('current_business_unit_id')) {
    // Full reset needed — use primary BU
    // ... existing reset logic
}
```

### Task 19: Verify import audit trail (Finding #19)

**Files:**
- Read: `app/Services/Modules/CashflowProjection/CashflowProjectionEntryImportService.php`

**Fix:** Check if the import service already calls the audit service internally. If not, add audit logging for imported entries.

### Task 20: Normalize notification payload contracts (Finding #20)

**Files:**
- Modify: All 13 files under `app/Notifications/`

**Fix:** Ensure all notification classes follow the same contract:
- `via(object $notifiable)` — consistent signature
- `toDatabase()` returns: `category`, `event`, `title`, `message`, `action_url`, `priority`, `occurred_at`
- Remove any extra domain-specific keys that aren't part of the contract, or standardize them

### Task 21: Add defensive payload handling in useNotifications hook (Finding #21)

**Files:**
- Modify: `resources/js/inertia/hooks/useNotifications.ts:59-73`

**Fix:** Add fallback defaults for all fields so missing keys don't break the UI:

```typescript
const newItem: NotificationListItem = {
    id: String(notification.id ?? crypto.randomUUID()),
    type: String(notification.type ?? 'notification'),
    category: String(notification.category ?? 'system'),
    event: String(notification.event ?? 'notification'),
    title: String(notification.title ?? 'New notification'),
    message: String(notification.message ?? ''),
    action_url: notification.action_url ? String(notification.action_url) : null,
    priority: String(notification.priority ?? 'normal'),
    read_at: null,
    created_at: new Date().toISOString(),
    occurred_at: String(notification.occurred_at ?? new Date().toISOString()),
};
```

This is already mostly done — verify the current code has all fallbacks.

### Task 22: Add route-level middleware for cashflow gates (Finding #22)

**Files:**
- Modify: `routes/web.php` — cashflow route group

**Fix:** Add `can:access-cashflow-projection` middleware to the cashflow route group if not already present.

---

## Batch 4: Low-Priority Fixes (Findings 23–28)

### Task 23: Add throttle to comment edit/delete (Finding #23)

**Files:**
- Modify: `routes/web.php:326-327`

**Fix:** Add `throttle:10,1` middleware to comment update and destroy routes.

### Task 24: Add timestamp-based expiration check to backdate (Finding #24)

**Files:**
- Modify: `app/Services/Modules/Activity/BackdatePermissionService.php`

**Fix:** In `checkUserPermission()`, add an explicit timestamp check: `where('granted_until', '>=', now())` to ensure expired permissions are not usable even if the cron hasn't run.

### Task 25: Add user-friendly 404 for missing documents (Finding #25)

**Files:**
- Modify: `app/Http/Controllers/Modules/Purchasing/PurchaseRequest/PurchaseRequestController.php:1309-1312`
- Modify: `app/Http/Controllers/Modules/Purchasing/StockRequest/StockRequestController.php:844-847`

**Fix:** Replace `abort(404)` with a redirect back with a flash message:
```php
if (!Storage::disk('public')->exists($path)) {
    return back()->with('error', 'The requested document file is no longer available. Please contact the request creator to re-upload.');
}
```

### Task 26: Add admin recovery guidance for stuck approvals (Finding #26)

**Files:**
- Modify: `app/Http/Controllers/Modules/Purchasing/PurchaseRequest/PurchaseRequestController.php`
- Modify: `app/Http/Controllers/Modules/Purchasing/StockRequest/StockRequestController.php`

**Fix:** When `currentApproval()` returns null for an in_approval request, return a descriptive error:
```php
if (!$currentApproval) {
    return back()->with('error', 'No active approval step found. The approval workflow may need to be rebuilt. Please contact a purchasing administrator.');
}
```

### Task 27: Document extra export sheet (Finding #27)

**Files:**
- No code change — add a comment in the export method explaining the Finance Inputs sheet.

### Task 28: Make notification category filter data-driven (Finding #28)

**Files:**
- Modify: `app/Http/Controllers/NotificationCenterController.php:16-30`

**Fix:** Extract the category list to a constant or config:
```php
public const NOTIFICATION_CATEGORIES = ['activity', 'purchasing', 'backdate', 'system'];
```

Use this constant in both the filter validation and the Inertia props.

---

## Verification

After all batches:
- `php artisan test --no-coverage` (full suite)
- `npx vitest run` (full frontend suite)
- `vendor/bin/pint --dirty`
- `npx tsc --noEmit --pretty false`
- `npm run build`

## Implementation Order

1. Batch 1 (Activity) — most findings, highest user impact
2. Batch 2 (Purchasing) — authorization gaps are security-critical
3. Batch 3 (Cashflow + Notifications + Core) — mixed severity
4. Batch 4 (Low-priority) — hardening and UX polish
