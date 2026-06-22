# Notification Center Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Add a modern enterprise notification center with a navbar bell, recent dropdown, archive page, unread count, read-marking flow, and initial high-signal event coverage across Activity, Purchasing, and Backdate.

**Architecture:** The backend reuses Laravel database notifications and normalizes notification payloads into a shared rendering contract. The frontend extends the authenticated Inertia layout with a lazy notification bell and dropdown, plus a dedicated archive page. Notification-open is handled through an authenticated redirect endpoint so read tracking stays server-owned and consistent.

**Tech Stack:** Laravel 12, PHP 8.2, Inertia.js v2, React 19, TypeScript, Tailwind CSS v3, PHPUnit 11, Vitest, Laravel Pint.

---

## Chunk 1: Lock The Notification Contract

### Task 0: Fix the known broken notification baseline before feature work expands it

**Files:**
- Modify: `app/Notifications/Activity/BackdateRequestSubmitted.php`
- Modify: any paired email template or sibling backdate notification class that repeats the same route helper after inspection.
- Add or modify: the narrowest existing backdate notification regression test file, or create a focused one if none exists.

- [ ] **Step 1: Write a failing test for the current backdate action route**

Assert that the submitted-backdate notification payload resolves a valid action URL for the approver flow.

- [ ] **Step 2: Run the focused backdate notification test to confirm failure**

Run the narrowest relevant PHPUnit command.
Expected: FAIL because the route helper currently points to a non-existent route name.

- [ ] **Step 3: Fix the route helper and any duplicate usage**

Use the real named route for the backdate approvals page and keep payload shape compatible.

- [ ] **Step 4: Re-run the focused backdate notification test**

Expected: PASS.

### Task 1: Write failing backend tests for list, read, and open flows

**Files:**
- Create: `tests/Feature/Core/NotificationCenterTest.php`
- Inspect for factories and helpers already used by notification tests in sibling feature suites.

- [ ] **Step 1: Write failing tests for unread count and recent list**

Add tests that assert:
- authenticated Inertia responses include unread count,
- recent notifications endpoint returns only the current user's notifications,
- recent notifications are ordered newest first.

- [ ] **Step 2: Run the focused backend test to confirm failure**

Run: `php artisan test tests/Feature/Core/NotificationCenterTest.php`
Expected: FAIL because the routes and shared props do not exist yet.

- [ ] **Step 3: Write failing tests for archive page and open route**

Add tests that assert:
- archive page route renders successfully,
- opening a notification marks it as read,
- opening another user's notification returns `403` or `404`,
- missing `action_url` falls back to the archive page with a warning.

- [ ] **Step 4: Run the focused backend test again**

Run: `php artisan test tests/Feature/Core/NotificationCenterTest.php`
Expected: FAIL on missing controller and route behavior.

### Task 2: Write failing backend tests for Activity tag notifications

**Files:**
- Modify: `tests/Feature/Modules/Activity/...` after locating the most relevant existing task workflow suite.
- Create only if no focused task-flow suite exists.

- [ ] **Step 1: Add a failing test for new participant tagging notifications**

Assert that updating an activity task with a newly added participant creates a notification for the new user.

- [ ] **Step 2: Add a failing test for no duplicate notification on unchanged participants**

Assert that updating the task without adding a new participant does not create another tag notification.

- [ ] **Step 3: Run the focused Activity feature test**

Run the narrowest relevant Activity feature command.
Expected: FAIL because the emission logic does not exist yet.

## Chunk 2: Backend Notification Center Infrastructure

### Task 3: Implement archive, recent, and read routes with controllers

**Files:**
- Modify: `routes/web.php`
- Create: `app/Http/Controllers/NotificationCenterController.php`
- Modify: `app/Http/Middleware/HandleInertiaRequests.php`
- Modify: `resources/js/inertia/types/index.ts` if shared props types need the archive route contract mirrored later.

- [ ] **Step 1: Add authenticated notification routes**

Implement routes for:
- `notifications.index`
- `notifications.recent`
- `notifications.open`
- `notifications.mark-all-read`

- [ ] **Step 2: Implement the controller actions minimally**

Backend should:
- paginate notifications for the archive page,
- return a small recent list for the dropdown,
- mark one notification as read before redirect,
- mark all of the current user's notifications as read.

- [ ] **Step 3: Share unread count globally**

Add `notifications.unread_count` to Inertia shared props for authenticated users.

- [ ] **Step 4: Run focused backend tests**

Run: `php artisan test tests/Feature/Core/NotificationCenterTest.php`
Expected: PASS for the list/read/open contract.

### Task 4: Normalize notification payloads for touched notification classes

**Files:**
- Modify: `app/Notifications/Purchasing/PurchaseRequest/ApprovalRequested.php`
- Modify sibling purchasing notification classes after locating them.
- Modify: `app/Notifications/Activity/BackdateRequestSubmitted.php`
- Modify sibling backdate notification classes after locating them.
- Create: `app/Support/Notifications/NotificationPayload.php` only if a tiny helper materially reduces duplication; otherwise keep logic inline and minimal.

- [ ] **Step 1: Update touched notification classes to expose a common payload shape**

Add normalized keys such as:
- `category`
- `event`
- `title`
- `message`
- `action_url`
- `priority`
- `occurred_at`

- [ ] **Step 2: Preserve existing route semantics and critical legacy fields if currently consumed elsewhere**

Do not break current email or database usage while unifying the frontend rendering contract.

- [ ] **Step 3: Add or update focused tests if payload assertions exist nearby**

Use the smallest regression surface necessary.

## Chunk 3: Activity Tag Notification Emission

### Task 5: Implement Activity tag notification dispatch for newly added participants

**Files:**
- Modify: `app/Http/Controllers/Modules/Activity/ActivityInertiaController.php`
- Modify: `app/Http/Requests/Modules/Activity/StoreActivityTaskRequest.php` only if participant normalization needs adjustment.
- Modify: `app/Http/Requests/Modules/Activity/UpdateActivityTaskRequest.php` only if participant normalization needs adjustment.
- Create: `app/Notifications/Activity/TaskTaggedNotification.php`
- Inspect relevant Activity models for relationships already needed by the notification payload.

- [ ] **Step 1: Identify the exact create and update mutation points for participant changes**

Reuse existing participant sync logic instead of duplicating it. Current repository mapping indicates the active participant attach and sync logic lives in `ActivityInertiaController`, so emit notifications there unless the flow is first refactored into a shared service as part of the task.

- [ ] **Step 2: Dispatch notifications only for newly attached users**

Avoid notifying:
- the actor if the actor adds themselves,
- existing participants when nothing changed,
- removed participants.

Add coverage for both create-time participant tagging and update-time newly added participants.

- [ ] **Step 3: Use English copy and normalized payload fields**

Example title pattern:
- `You were tagged by {actor} in Activity {task_title}`

- [ ] **Step 4: Run focused Activity tests**

Run the narrowest Activity feature test command chosen in Task 2.
Expected: PASS.

## Chunk 4: Frontend Notification Center UI

### Task 6: Write failing frontend tests for bell, dropdown, and archive page

**Files:**
- Create: `tests/React/Components/Layout/NotificationBell.test.tsx`
- Create: `tests/React/Pages/Notifications/Index.test.tsx`
- Modify: `tests/React/...` utilities only if existing Inertia page helpers require route additions.

- [ ] **Step 1: Add failing tests for navbar bell state**

Assert:
- unread badge renders from shared props,
- empty state renders when there are no recent notifications,
- recent notification items render title, message, and relative time.

- [ ] **Step 2: Add failing tests for archive page filtering and mark-all action affordance**

Assert:
- filter tabs render,
- unread-only list state works,
- empty archive state renders cleanly,
- mark-all action is shown when useful.

- [ ] **Step 3: Run the focused frontend tests**

Run: `npm exec vitest run tests/React/Components/Layout/NotificationBell.test.tsx tests/React/Pages/Notifications/Index.test.tsx --runInBand`
Expected: FAIL because the UI does not exist yet.

### Task 7: Implement the bell, dropdown, and archive page

**Files:**
- Modify: `resources/js/inertia/layouts/AppLayout.tsx`
- Inspect and modify: `resources/js/inertia/components/layout/Navbar.tsx`
- Create: `resources/js/inertia/components/layout/NotificationBell.tsx`
- Create: `resources/js/inertia/components/layout/NotificationDropdown.tsx`
- Create: `resources/js/inertia/Pages/Notifications/Index.tsx`
- Modify: `resources/js/inertia/types/index.ts`
- Create: `resources/js/inertia/types/notifications.ts`
- Modify test utilities or page registries only where required.

- [ ] **Step 1: Add shared notification types**

Create typed contracts for:
- notification summary item,
- recent notifications response,
- notifications archive page props,
- shared unread count prop.

- [ ] **Step 2: Implement `NotificationBell` and lazy recent-list fetch**

The bell should:
- show unread count,
- fetch recent notifications when opened,
- render empty, loading, and populated states,
- route notification clicks through `notifications.open`.

- [ ] **Step 3: Implement the full archive page**

The page should:
- render notification cards or rows,
- support category and unread filtering from server-provided props,
- expose `Mark all as read`,
- preserve the clean modern enterprise visual direction.

- [ ] **Step 4: Wire the bell into the existing navbar**

Preserve the current responsive navbar layout and avoid crowding mobile controls.

- [ ] **Step 5: Run focused frontend tests**

Run: `npm exec vitest run tests/React/Components/Layout/NotificationBell.test.tsx tests/React/Pages/Notifications/Index.test.tsx --runInBand`
Expected: PASS.

- [ ] **Step 6: Run type verification**

Run: `npm exec tsc --noEmit --pretty false`
Expected: PASS.

## Chunk 5: Final Verification, Review, And QA

### Task 7.5: Fill the enterprise-pack purchasing gaps that do not already emit runtime notifications

**Files:**
- Modify: Stock Request approval flow classes and controllers or services after locating the current requester-notification mutation points.
- Modify: purchasing admin task assignment service or controller after locating where `assigned_admin_id` is set.
- Create: missing Stock Request notification classes for approved, rejected, and completed states if they do not already exist.
- Add or modify: the narrowest purchasing feature tests that prove the new notifications are emitted.

- [ ] **Step 1: Write failing tests for missing Stock Request requester-facing notifications**

Assert that requester-facing notifications are emitted for the approved, rejected, and completed paths described in the enterprise pack.

- [ ] **Step 2: Write a failing test for purchasing admin task assignment notification emission**

Assert that assignment or claim paths emit `TaskAssigned` when a user is actually assigned the task.

- [ ] **Step 3: Run the focused purchasing tests to confirm failure**

Expected: FAIL because these emissions are not fully wired today.

- [ ] **Step 4: Implement the missing notifications and dispatch wiring**

Keep payloads normalized for the notification center and preserve existing mail behavior where required.

- [ ] **Step 5: Re-run the focused purchasing tests**

Expected: PASS.

### Task 8: Update execution notes and run final verification

**Files:**
- Modify: `docs/exec_plans.md`

- [ ] **Step 1: Update the execution record with implementation notes and verification evidence**

- [ ] **Step 2: Run final focused backend verification**

Run the exact focused feature tests created or modified for:
- `tests/Feature/Core/NotificationCenterTest.php`
- the touched Activity notification test file.

- [ ] **Step 3: Run final focused frontend verification**

Run: `npm exec vitest run tests/React/Components/Layout/NotificationBell.test.tsx tests/React/Pages/Notifications/Index.test.tsx --runInBand`

- [ ] **Step 4: Run formatting and type verification**

Run: `vendor/bin/pint --dirty`

Run: `npm exec tsc --noEmit --pretty false`

- [ ] **Step 5: Send the completed patch through reviewer and QA gates**

Reviewer should check:
- payload consistency,
- authorization,
- contract drift,
- test coverage.

QA should validate:
- unread badge behavior,
- dropdown rendering,
- archive page,
- notification open marks-as-read,
- activity tagging flow.
