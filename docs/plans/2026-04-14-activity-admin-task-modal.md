# Activity Admin Task Modal Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make Activity Admin open task details in the same clean modal shell used by `My Tasks`, while preserving the admin full-page route as a fallback.

**Architecture:** Reuse the existing shared task detail modal component with a small admin mode, then add controller and page wiring so Activity Admin department detail can hydrate a selected task from URL state. The existing admin detail page remains unchanged as a fallback target for direct route access.

**Tech Stack:** Laravel, Inertia.js, React, TypeScript, Vitest, PHPUnit

---

## Chunk 1: Tests first

### Task 1: Cover admin modal state in controller and page behavior

**Files:**
- Modify: `tests/Feature/Modules/Activity/ActivityAdminControllerTest.php` (or closest existing Activity Admin feature test file)
- Create or Modify: `tests/React/Pages/Activity/Admin/DepartmentDetail.test.tsx`

- [ ] Add a failing feature test that requests the admin department detail page with modal task query parameters and expects selected task data in the Inertia props.
- [ ] Run the targeted PHP test to confirm it fails for the missing props behavior.
- [ ] Add a failing React test that opens admin task detail from the department page and expects the shared modal shell to appear.
- [ ] Run the targeted Vitest spec to confirm it fails before implementation.

## Chunk 2: Implement shared modal behavior

### Task 2: Extend the shared task modal for admin use

**Files:**
- Modify: `resources/js/inertia/components/activity/TaskDetailModal.tsx`
- Modify: `resources/js/inertia/Pages/Activity/Admin/DepartmentDetail.tsx`
- Modify: `app/Http/Controllers/Modules/Activity/ActivityAdminController.php`

- [ ] Add a small admin mode to the shared modal component so the header copy and actions can adapt without duplicating the visual shell.
- [ ] Add selected task hydration to the Activity Admin department detail controller response.
- [ ] Add URL/query modal helpers to the Activity Admin department page and open the modal from task title clicks.
- [ ] Keep `/activity/admin/task/{id}` working as a standalone fallback page.

## Chunk 3: Agent guidance and verification

### Task 3: Lock frontend execution guidance and verify behavior

**Files:**
- Modify: `AGENTS.md`

- [ ] Update AGENTS guidance so frontend sub-agents explicitly use `frontend-skill` for user-facing UI implementation.
- [ ] Run targeted Vitest, PHPUnit, and TypeScript verification commands.
- [ ] Request QA/review on the finished implementation and address any findings.
