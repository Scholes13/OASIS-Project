# Cashflow Entries Actions Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Menambahkan delete action pada cashflow entries dan mengganti tombol edit tunggal menjadi dropdown action yang lebih ringkas.

**Architecture:** Backend menambah route dan controller action delete dengan audit logging append-only. Frontend mengganti action cell menjadi dropdown tiga titik dan memakai modal konfirmasi shared supaya perilaku tetap konsisten dengan modul lain.

**Tech Stack:** Laravel 12, PHP 8.2, Inertia React 19, TypeScript, Headless UI, Vitest, PHPUnit

---

## Chunk 1: Backend delete flow

### Task 1: Route and controller delete behavior

**Files:**
- Modify: `routes/web.php`
- Modify: `app/Http/Controllers/Modules/CashflowProjection/CashflowProjectionController.php`
- Modify: `app/Services/Modules/CashflowProjection/CashflowProjectionAuditService.php`
- Test: `tests/Feature/Modules/CashflowProjection/CashflowProjectionAuditTrailTest.php`

- [ ] Write failing backend test for deleting a visible line item and recording `deleted` audit action.
- [ ] Run focused PHPUnit test and confirm it fails for missing route/action.
- [ ] Add delete route and minimal controller implementation with scope-aware authorization.
- [ ] Record append-only audit log before delete and redirect back to matching entries month/year.
- [ ] Re-run focused PHPUnit test until green.

## Chunk 2: Frontend action dropdown

### Task 2: Entries row actions and confirm modal

**Files:**
- Modify: `resources/js/inertia/Pages/CashflowProjection/Entries.tsx`
- Test: `tests/React/Pages/CashflowProjection/Entries.test.tsx`

- [ ] Write failing React tests for compact action dropdown, edit action, and delete confirmation modal.
- [ ] Run focused Vitest and confirm failures describe missing behavior.
- [ ] Replace standalone `Edit` button with three-dot dropdown using existing UI patterns.
- [ ] Hook `Delete entry` to shared `ConfirmDialog` and Inertia delete with preserved scroll.
- [ ] Re-run focused Vitest until green.

## Chunk 3: Verification and review

### Task 3: Standards and verification

**Files:**
- Modify: `docs/exec_plans.md`

- [ ] Update execution tracking with scope, risks, and verification notes.
- [ ] Review changed files against `docs/coding_standards.json`.
- [ ] Run focused PHPUnit, `vendor/bin/pint --dirty`, focused Vitest if needed, and `npm exec tsc --noEmit --pretty false`.
- [ ] Summarize remaining risk if any verification must be skipped.
