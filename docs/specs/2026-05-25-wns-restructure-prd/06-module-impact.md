# 06 - Module Impact

Status: draft
Depends on: 01-schema-changes, 05-authorization-rules

Section ini menjabarkan area kode yang harus disesuaikan dengan parent-child department.

## Audit Strategy

Cari semua callsite yang assume "1 user = 1 dept = 1 scope":

```bash
rg -n "department_id" app/ --type php
rg -n "primary_department_id|getCurrentDepartmentId" app/ --type php
rg -n "where\\('department_id'" app/ --type php
```

Tiap hit di-klasifikasi:
- **Single-leaf semantic** — query memang hanya butuh 1 dept (mis. CRUD form). Tidak perlu diubah.
- **Scope semantic** — query mau "data dept user". Perlu diubah ke `descendantIds()`.

## Activity Tracking

File utama:
- `app/Http/Controllers/Modules/Activity/ActivityAdminController.php`
- `app/Http/Controllers/Modules/Activity/EmployeeTaskController.php`
- `app/Services/Modules/Activity/AdminTaskAssignmentService.php`
- `app/Services/Modules/Activity/ActivityAdminExportService.php`

Perubahan:

1. **Dashboard scoping** — saat user buka Activity Dashboard, scope-nya ikut `descendantIds()`. Saat ini sudah ada logika `parent_business_unit_dashboard` (untuk BU parent), pattern serupa dipakai untuk parent-dept.
2. **Department detail page** (`activity/admin/department/{department}`) — kalau user buka root dept (S&M), tampilkan agregat dari semua sub-dept + breakdown per sub-dept. Kalau buka sub-dept (BSD), tampilkan biasa.
3. **Department dropdown** — saat ini list semua dept aktif di BU. Update jadi tree (root + indented children).
4. **Member focus filter** — sudah additive di-scope dept. Pastikan member list di-fetch dari `descendantIds()`, bukan single dept.
5. **Export** — service export sudah expose `department_id` filter. Update untuk accept array (`whereIn`) supaya GM bisa export 3 sub-dept sekaligus.

## Cashflow Projection

File utama:
- `app/Http/Controllers/Modules/CashflowProjection/*`
- `app/Services/Modules/CashflowProjection/*`
- `app/Models/Modules/CashflowProjection/CashflowProjectionLineItem.php`

Decision: **line item hanya boleh dibuat di leaf dept**.

Form Request `CreateLineItemRequest` (atau setara) tambah validasi:
```php
'department_id' => [
    'required',
    'exists:departments,id',
    function ($attr, $value, $fail) {
        $dept = Department::find($value);
        if ($dept && $dept->children()->exists()) {
            $fail('Cashflow line item harus dibuat di sub-department, bukan di root department.');
        }
    },
],
```

Read query untuk dashboard finance:
```php
// Sebelum
->where('department_id', $deptId)

// Sesudah (kalau dept punya children, ambil dari descendant)
->whereIn('department_id', $dept->descendantIds())
```

Excel import (`docs/specs/2026-04-17-cashflow-entries-excel-import-design.md`):
- Validasi `department_code` harus refer ke leaf dept saja, bukan root.
- Tambah error message: "department `SM` adalah root department. Gunakan kode sub-department (BSD/COM/CMC)."

## Purchasing Module

File utama:
- `app/Http/Controllers/Modules/Purchasing/PurchaseRequestController.php`
- `app/Services/Modules/Purchasing/ApprovalWorkflowService.php`
- `app/Services/Modules/Purchasing/UniversalPRNumberingService.php`

Approval workflow:
- Dept flat (HR, ACC) — tidak berubah, HOD approve langsung.
- Sub-dept (BSD) — Manager (`access_level=department_head`) approve. Setelah itu naik ke BU-level approval seperti biasa.
- Root dept (S&M) — biasanya tidak punya PR sendiri. Kalau ada (mis. GM ajukan PR atas nama S&M tanpa specific division), approver = GM sendiri (auto-approved oleh executive level)? Perlu putusan PO. **Default PRD ini**: PR di S&M tidak diizinkan, harus dari sub-dept.

Department Report (`PurchasingAdminController@departmentReport`):
- Update untuk grup per parent-child. Mis. baris S&M expandable jadi 3 row sub-dept.

`is_purchasing_department` flag:
- Sub-dept BSD/COM/CMC bisa punya flag berbeda dari parent. Tidak inherit otomatis.
- Default semua false. Set manual via admin UI sesuai kebutuhan PO.

## Numbering

File utama:
- `app/Services/Core/Numbering/UniversalPRNumberingService.php`
- `app/Models/Core/NumberSequence.php`

Format current: `PR.{DEPT}/{YEAR}/{MONTH}/{SEQUENCE}` (`BusinessUnitSeeder.php:16`).

Decision: **sequence pakai `code` dari leaf dept**, bukan root.

Contoh:
- PR dari BSD → `PR.BSD/2026/05/001`
- PR dari COM → `PR.COM/2026/05/001`
- Tidak ada `PR.SM/...` karena S&M tidak menerima PR langsung.

Existing logic sudah pakai `department_id` per sequence (lihat `NumberSequence::createOrGetForPeriod`). Tidak perlu schema change. Cukup pastikan `department_id` di sequence selalu leaf-id.

## Inertia Shared Props

File: `app/Http/Middleware/HandleInertiaRequests.php`.

Update method `getAvailableDepartments()` (`HandleInertiaRequests.php:162`) untuk return tree-shaped data:

```php
[
    [
        'id' => 12,
        'code' => 'SM',
        'name' => 'Sales & Marketing',
        'parent_id' => null,
        'children' => [
            ['id' => 13, 'code' => 'BSD', 'name' => 'Business Solutions', 'parent_id' => 12],
            ['id' => 14, 'code' => 'COM', 'name' => 'Commercial', 'parent_id' => 12],
            ['id' => 15, 'code' => 'CMC', 'name' => 'Corporate Marketing Comm.', 'parent_id' => 12],
        ],
    ],
    ['id' => 16, 'code' => 'HR', 'name' => 'Human Resource', 'parent_id' => null, 'children' => []],
    // ...
]
```

`current_department` props tetap return single dept object (yang aktif di session).

## Frontend — Department Switcher

File: `resources/js/inertia/components/layout/DepartmentSwitcher.tsx`.

Update UI:
- Render parent dept dengan icon expand kalau ada children.
- Children indented dengan `pl-6`.
- Kalau user hanya punya 1 dept (flat), behavior tetap seperti sekarang (single button).
- Selected state: highlight current dept; kalau current di sub-dept, parent tetap visible (tidak collapsed).

Component tidak perlu pecah, cukup recursive render kalau children > 0.

## Frontend — Forms yang punya department selector

Form yang punya field "Department" (mis. PR creation, task creation, line item creation):
- `resources/js/inertia/components/forms/DepartmentSelect.tsx` (kalau ada)
- Pages: PR create, ST create, Task create, Cashflow line item create.

Update select option:
- Group by parent. Pakai `<optgroup>` HTML native atau component select dengan group support.
- Disable option untuk root dept (kalau form-nya hanya boleh leaf, mis. Cashflow line item).

## API Endpoints

File: `routes/api.php`, `routes/web.php`.

`GET /api/departments` (`api.php:79`) — saat ini return flat list. Update return tree atau tetap flat tapi include `parent_department_id`. Backward compat: tetap flat, frontend yang bangun tree.

`GET /admin/business-units/{businessUnit}/departments` (`web.php:461`) — sama.

`POST /api/department/switch` — tidak perlu berubah. Validasi UBU sudah cukup.

## Test Surfaces

Test files yang perlu di-update atau ditambah:
- `tests/Feature/Core/DepartmentDeleteTest.php` — tambah case sub-dept tidak boleh delete kalau punya users.
- `tests/Feature/Activity/ActivityAdminParentBusinessUnitScopeTest.php` — pattern serupa untuk parent-dept (`ActivityAdminParentDepartmentScopeTest.php` baru).
- `tests/Feature/Modules/CashflowProjection/LineItemCreationTest.php` — tambah case reject root dept.
- `tests/React/Pages/Activity/Admin/DepartmentDetail.test.tsx` — tambah case render breakdown sub-dept untuk root dept.
- `tests/React/components/layout/DepartmentSwitcher.test.tsx` — test render tree.

Detail di Section 08.

## Migration Sequence

Urutan run:
1. Migration `parent_department_id` (DDL).
2. Seeder `WNSDepartmentSeeder` v2 — tambah dept S&M dan sub-dept (idempotent).
3. Seeder position custom untuk S&M-tree.
4. Data migration script — pindah user sesuai mapping (Section 04).
5. Code deploy — controller, service, frontend update.

Detail di Section 07.

## Risiko Lintas-Modul

| Risk | Mitigation |
|---|---|
| Update di Activity scoping query miss → user di S&M tidak lihat task BSD/COM/CMC. | Audit menyeluruh + integration test untuk S&M user. |
| Numbering format inconsistent — sebagian record pakai `PR.BSD`, sebagian `PR.SM`. | Decision: sequence selalu di leaf. Tidak ada migration untuk PR existing (dept lama tetap pakai sequence lama). |
| Cashflow line item lama yang `department_id` di-rename target ke leaf dept yang belum exist. | Tidak ada rename dept lama. Semua dept lama keep, hanya tambah dept baru. Tidak ada migrasi line item. |
| Frontend tree render lambat untuk BU dengan banyak dept. | Tree-build O(n), n max ~30 dept per BU. Tidak ada concern performance. |

## Open Question (Module-level)

1. **Approval chain di sub-dept** — Manager BSD approve, lalu langsung BU-level, atau Asisten GM/GM ikut chain? PRD ini default skip GM (langsung BU-level). Konfirmasi PO.
2. **Cashflow line item di root dept** — boleh tidak? Default PRD: tidak boleh, harus leaf.
3. **Numbering format** — pakai leaf code (`PR.BSD`)? Atau format baru `PR.SM-BSD` supaya tetap kelihatan parent? Default PRD: pakai leaf code saja.
4. **Department switcher kalau user GM** — tampilkan semua sub-dept walau user tidak punya UBU row di sub-dept (karena scope sudah inherit)? Default PRD: tampilkan semua descendant root dept yang user punya `primary_department_id`-nya.
