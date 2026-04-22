# Activity Quick Status Timestamps Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Restore Activity quick status updates so `To Do -> In Progress` and `In Progress -> Done` use real transition timestamps, while direct `To Do -> Done` still requires manual execution-time confirmation.

**Architecture:** Keep the backend route and partial-update flow intact, but narrow the quick-action validation guard in `UpdateActivityTaskRequest` so historical `task_date` no longer blocks starting work or completing already-started work. Lock the behavior with focused feature coverage on the update endpoint and keep the existing frontend guidance path for truly blocked direct-complete cases.

**Tech Stack:** Laravel 12, PHPUnit 11, Inertia/React TypeScript

---

### Task 1: Lock the desired quick-status rules in backend tests

**Files:**
- Modify: `tests/Feature/Modules/Activity/ActivityTaskModalRedirectTest.php`

- [ ] **Step 1: Write failing tests**
- [ ] **Step 2: Run focused PHPUnit to confirm the new expectations fail**
- [ ] **Step 3: Implement the minimal validation change**
- [ ] **Step 4: Re-run focused PHPUnit to confirm green**

### Task 2: Preserve the frontend guidance contract for the remaining blocked path

**Files:**
- Verify: `resources/js/inertia/components/activity/TaskDetailModal.tsx`
- Verify: `resources/js/inertia/components/activity/KanbanBoard.tsx`
- Verify: `resources/js/inertia/components/activity/ActivityDataTable.tsx`
- Verify: `tests/React/Components/Activity/TaskDetailModal.test.tsx`
- Verify: `tests/React/Components/Activity/KanbanBoardCreateEntry.test.tsx`
- Verify: `tests/React/Components/Activity/ActivityDataTableStatusDropdown.test.tsx`

- [ ] **Step 1: Run focused React coverage to confirm the direct-complete guidance still behaves correctly**
- [ ] **Step 2: Run `npm exec tsc --noEmit --pretty false`**
