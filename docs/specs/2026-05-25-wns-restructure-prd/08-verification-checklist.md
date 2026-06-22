# 08 - Verification Checklist

Status: draft
Depends on: all previous sections

Checklist ini dijalankan **setelah Section 07 step 9** (smoke test) dan **setelah step 11** (post-deploy).

## Pre-Migration Checklist (Staging)

Sebelum migrasi prod, sudah harus pass di staging:

- [ ] Migration `parent_department_id` jalan tanpa error
- [ ] `Department::create([..., 'parent_department_id' => $parent->id])` sukses
- [ ] Validation reject parent dari BU berbeda
- [ ] Validation reject sub-of-sub (max 1 level)
- [ ] Seeder dept S&M idempotent (run 2x → no error, no duplicate)
- [ ] Seeder position idempotent
- [ ] Data migration command `--dry-run` tidak edit DB
- [ ] Data migration command `--execute` mengubah persis user yang di-list, tidak lebih
- [ ] Total user count sebelum/sesudah identik
- [ ] Total UBU aktif sesudah migrasi = (UBU lama yang di-keep) + (UBU baru yang dibuat)
- [ ] Tidak ada user yang `primary_department_id` jadi NULL setelah migrasi

## Functional Verification

### Schema

```bash
php artisan tinker
>>> Schema::hasColumn('departments','parent_department_id')
# expect: true

>>> Department::find($smId)->children->pluck('code')->all()
# expect: ['BSD','COM','CMC']

>>> Department::find($bsdId)->parent->code
# expect: 'SM'
```

### Authorization Scope

```php
// Etik (GM)
$etik = User::where('email','andri@werkudara.com')->firstOrFail();
$etik->primaryDepartment->code;        // 'SM'
$etik->primaryDepartment->descendantIds();  // [SM, BSD, COM, CMC]

// Ainur (Asisten GM)
$ainur = User::where('email','ainur@werkudara.com')->firstOrFail();
$ainur->primaryDepartment->code;       // 'SM'
$ainur->primaryDepartment->descendantIds();  // sama dengan Etik

// Manager BSD
$irvani = User::where('email','irvani@werkudara.com')->firstOrFail();
$irvani->primaryDepartment->code;      // 'BSD'
$irvani->primaryDepartment->descendantIds();  // [BSD] only

// Dept flat (tidak terpengaruh)
$dita = User::where('email','dita@werkudara.com')->firstOrFail();
$dita->primaryDepartment->descendantIds();  // [HR_id]
```

### Activity Module

- [ ] Etik buka Activity Dashboard: nampilin task dari S&M + BSD + COM + CMC
- [ ] Etik buka Activity Admin > department/{S&M_id}: card breakdown sub-dept tampil
- [ ] Irvani buka Activity Dashboard: hanya task BSD
- [ ] Vanessa (Pricing Analyst COM) buka task list: hanya task COM
- [ ] Member focus filter di S&M nampilin user dari semua sub-dept
- [ ] Export CSV dari S&M include data 3 sub-dept

### Cashflow Module

- [ ] Form create line item: dropdown department reject S&M (root), accept BSD/COM/CMC (leaf)
- [ ] Form create line item di leaf dept (mis. BSD): sukses
- [ ] Etik buka cashflow dashboard: line item BSD/COM/CMC tampil
- [ ] Excel import dengan `department_code=SM` → reject dengan message yang jelas
- [ ] Excel import dengan `department_code=BSD` → sukses

### Purchasing Module

- [ ] User staff BSD buat PR → approver = Manager BSD (Irvani)
- [ ] PR di-approve Manager BSD → naik ke BU-level approval seperti biasa
- [ ] Department report nampilin S&M expandable jadi 3 baris sub-dept
- [ ] PR numbering: dari BSD → format `PR.BSD/...`, bukan `PR.SM/...`

### Numbering

```php
$bsd = Department::where('code','BSD')->first();
$seq = NumberSequence::createOrGetForPeriod(...args including $bsd->id);
$seq->department_id;  // BSD id, bukan SM id
```

### Inertia Props

Login sebagai user S&M, inspect Inertia shared props:

- [ ] `availableDepartments` return tree dengan `children` array di S&M
- [ ] `currentDepartment` return single dept yang aktif
- [ ] `currentBusinessUnit` tetap WNS

### Department Switcher (Frontend)

- [ ] Etik (di S&M) lihat switcher: S&M parent + 3 indented children, semua aktif (kalau punya UBU)
- [ ] Irvani (di BSD): lihat S&M sebagai parent (collapsed/expanded), BSD highlighted
- [ ] Dita (HR flat): switcher tidak nampil (single dept) — behavior lama tetap
- [ ] Click sub-dept → POST /api/department/switch sukses → page refresh dengan dept baru di session

### Data Integrity

- [ ] Tidak ada user dengan `primary_department_id` ke dept yang `is_active=false` dan `parent_department_id` juga inactive
- [ ] Tidak ada UBU `is_primary=true && is_active=false` (orphan primary)
- [ ] Tidak ada `number_sequences` baru dibuat dengan `department_id` ke root S&M
- [ ] Email `andri@werkudara.com` resolve ke user dengan `primary_department_id` = SM dept id

## Regression Verification (modul yang tidak diubah)

User yang TIDAK pindah dept (mis. Dita HR, Krisnanto ACC, Mitha sebelum di-pindah ke BSD belum, dst):

- [ ] Login normal, dashboard tampil sama seperti pre-migrasi
- [ ] Buka task: jumlah & isi sama
- [ ] Buka cashflow: jumlah line item sama
- [ ] Buka PR list: jumlah & status sama

## Test Suite

Wajib pass sebelum merge:

```bash
php artisan test tests/Feature/Core/DepartmentDeleteTest.php
php artisan test tests/Feature/Core/DepartmentParentChildTest.php       # NEW
php artisan test tests/Feature/Activity/ActivityAdminParentBusinessUnitScopeTest.php
php artisan test tests/Feature/Activity/ActivityAdminParentDepartmentScopeTest.php  # NEW
php artisan test tests/Feature/Modules/CashflowProjection/LineItemCreationTest.php
php artisan test tests/Feature/Modules/Purchasing/PurchaseRequestApprovalTest.php
npm run test -- DepartmentSwitcher
npm run test -- DepartmentDetail
```

Test baru yang harus dibuat:

| File | Coverage | Status |
|---|---|---|
| `tests/Feature/Core/DepartmentParentChildTest.php` | create dept dengan parent valid; reject parent BU berbeda; reject sub-of-sub; descendantIds correctness | ✅ done (9 tests) |
| `tests/Feature/Core/DepartmentRestructureServiceTest.php` | move dry-run/execute, idempotency, error paths | ✅ done (6 tests) |
| `tests/Feature/Activity/ActivityAdminParentDepartmentScopeTest.php` | GM lihat task semua sub-dept; Manager sub-dept hanya lihat sub-dept-nya | ⏸ pending (Section 06 controller update) |
| `tests/Feature/Modules/CashflowProjection/RootDepartmentRejectionTest.php` | create line item di root reject; di leaf accept | ⏸ pending (Section 06 form request update) |
| `tests/React/components/layout/DepartmentSwitcher.test.tsx` | render tree; collapse/expand; switch sub-dept | ⏸ pending (Section 06 frontend update) |

## Performance Smoke

- [ ] Activity dashboard load time tidak naik > 200ms vs baseline
- [ ] `descendantIds()` query tidak muncul N+1 (eager load di `availableDepartments`)
- [ ] Tree render di switcher tidak block render untuk user dengan banyak dept

## Sign-off

- [ ] PM Agent: semua section 01-07 implemented sesuai PRD
- [ ] Reviewer: standards check pass (`docs/coding_standards.json`)
- [ ] QA: smoke test pass di staging
- [ ] PO: confirm struktur baru match `excel/strukturnew.pdf`

Kalau ada section yang gagal, **jangan tutup** PRD. Tambah ke `docs/exec_plans.md` sebagai tech debt entry sebelum dianggap selesai.

## Post-Deploy Monitoring (1 minggu)

- [ ] Daily check log error untuk pattern `descendantIds`, `parent_department_id`, `Sales & Marketing`
- [ ] Spot check 3 user random per hari (1 GM-tree, 1 dept flat, 1 super admin) — tanya feedback
- [ ] Track ticket helpdesk yang menyebut "tidak bisa lihat data" / "salah dept"
- [ ] Adjust dan deploy hotfix kalau perlu, log di `docs/exec_plans.md`

## Done Definition

Migrasi dianggap selesai kalau:
1. Semua checklist di section ini centang.
2. Tidak ada bug report dari S&M user dalam 7 hari pertama.
3. Sign-off PO didokumentasikan di entry `docs/exec_plans.md`.
