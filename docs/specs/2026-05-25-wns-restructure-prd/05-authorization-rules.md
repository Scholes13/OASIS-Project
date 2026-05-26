# 05 - Authorization Rules

Status: draft
Depends on: 01-schema-changes, 03-position-hierarchy

## Prinsip

Authorization tetap memakai dua sumber yang sudah ada:
1. **Position `access_level`** — `executive`, `department_head`, `team_leader`, `staff`.
2. **Department membership** — via `users.primary_department_id` dan `user_business_units.department_id`.

Yang berubah hanya **scope resolution**: kalau user ada di root department yang punya children, scope-nya otomatis termasuk descendant. Tidak ada gate baru, tidak ada role baru di Spatie Permission.

## Resolusi Scope (`Department::descendantIds`)

Method `descendantIds()` (Section 01) jadi titik tunggal untuk semua query yang scope-nya "department user saat ini".

Kontrak:
```php
$user->primaryDepartment->descendantIds();
// Root dept (S&M):  [SM_id, BSD_id, COM_id, CMC_id]
// Sub-dept (BSD):   [BSD_id]
// Flat dept (HR):   [HR_id]
```

Catatan: hanya **active children** yang ikut. Sub-dept yang `is_active=false` tidak masuk scope.

## Mapping Role → Scope

| Posisi User | access_level | Lokasi | Visibility (Activity, Cashflow, Purchasing) |
|---|---|---|---|
| GM (Etik) | executive | WNS / S&M (root) | semua data S&M + BSD + COM + CMC |
| Asisten GM (Ainur) | department_head | WNS / S&M (root) | sama dengan GM (descendantIds() identik) |
| Manager BSD | department_head | WNS / S&M / BSD (sub) | hanya BSD |
| Coordinator/Lead | team_leader | sub-dept | hanya sub-dept-nya |
| Specialist/Analyst/dst | staff | sub-dept | hanya record sendiri di sub-dept |
| HOD HR (Dita) | department_head | WNS / HR (flat) | hanya HR |
| Staff biasa | staff | dept flat | hanya record sendiri |
| CEO/MD | executive | WG / Executive Office | semua dept WG (lihat catatan WG) |
| Chief of Staff (Adiel) | executive | WNS / Executive Office | semua dept WNS (descendant + executive scope) |
| Super Admin | (bypass) | (any) | semua data lintas BU |

### Catatan WG / Executive Office

**Decision PO 2026-05-25**: keep dept lama WG (CEO, MD, SYSADMIN) tetap aktif. Tambah dept baru `EXEC` di WG sebagai Executive Office. Move Fadli dan Yakti ke `EXEC` via migration command.

**Revision PO 2026-05-26**: Adiel sebagai Chief of Staff di-host di **WNS/EXEC**, bukan WG/EXEC. Cross-BU move: deactivate UBU lama Adiel di WG, buat UBU baru di WNS/EXEC/COS_EXEC. Position COS_EXEC dihapus dari WG/EXEC seeder.

Dampak:
- Dept lama tetap punya history user (account lama yang sudah pernah di-assign), number_sequences, dan activity logs.
- Dept lama tidak dapat user aktif baru karena Fadli/Yakti sudah pindah ke EXEC.
- Boleh di-deactivate manual nanti via admin UI kalau sudah tidak diperlukan, tapi default keep.

## GM vs Asisten GM — perbedaan praktis

Karena keduanya sama-sama di root dept S&M, `descendantIds()` mereka identik. Pembedaan datang dari:

1. **`hierarchy_level`** — GM=0, Asisten GM=1. Berguna untuk approval routing (kalau ada chain "1 approver per level").
2. **`access_level`** — GM=executive, Asisten GM=department_head. Berbeda untuk authorization gate seperti `view-reports` (yang biasanya cuma `executive` ke atas).
3. **Approval workflow** — perlu putusan: PR/ST yang naik dari sub-dept (Manager BSD) di-approve di Asisten GM dulu baru GM, atau langsung GM, atau bebas paralel? Out of scope PRD ini, masuk follow-up.

Untuk PRD foundation: keduanya bisa lihat semua data S&M-tree. Pembedaan approve/no-approve di-handle di approval module nanti.

## Manager Sub-dept — Scope Persempit

Manager BSD (mis. Irvani) punya `access_level=department_head` tapi ada di sub-dept BSD. Scope-nya hanya BSD karena `BSD->descendantIds() = [BSD]` (tidak punya children).

Implikasi: Manager BSD **tidak otomatis** lihat data Manager COM/CMC. Untuk visibility lintas-division, user harus naik ke level GM/Asisten GM.

## Activity Module Scoping

File: `app/Http/Controllers/Modules/Activity/*Controller.php` (banyak), `app/Services/Modules/Activity/*Service.php`.

Pattern lama (yang umum dipakai):
```php
$tasks = EmployeeTask::where('business_unit_id', $buId)
    ->where('department_id', $user->getCurrentDepartmentId())
    ->get();
```

Pattern baru (PRD ini):
```php
$dept = Department::find($user->getCurrentDepartmentId());
$scopeIds = $dept ? $dept->descendantIds() : [];

$tasks = EmployeeTask::where('business_unit_id', $buId)
    ->whereIn('department_id', $scopeIds)
    ->get();
```

Action item: audit semua callsite `where('department_id', ...)` di module Activity. Detail di Section 06.

## Cashflow Module Scoping

`CashflowProjectionLineItem` punya `department_id` per row. Untuk root dept S&M:

- **Read**: GM/Asisten GM lihat line item di S&M + BSD + COM + CMC.
- **Write**: line item dibuat di **leaf dept** (BSD/COM/CMC), bukan di root S&M. GM bisa write di salah satu sub-dept lewat dept switcher.

Decision butuh konfirmasi (Section 06): apakah S&M sebagai root dept ikut bisa punya line item, atau hanya leaf yang valid?

Default di PRD ini: **hanya leaf yang punya line item**. Root dept (S&M) di-block dari pembuatan line item via Form Request validation.

## Purchasing Module Scoping

PR/ST creation:
- User staff/leader bisa buat PR untuk dept-nya sendiri.
- HOD/department_head approve PR dept-nya.
- Untuk sub-dept, Manager BSD approve PR BSD. GM/Asisten GM **tidak otomatis ikut chain** (karena chain saat ini single-level HOD → BU level).

Out of scope: re-design approval chain untuk akomodasi parent-child. Status: follow-up PRD setelah ini.

## Department Switcher

Frontend di `resources/js/inertia/components/layout/DepartmentSwitcher.tsx` perlu update:

- Tampilkan struktur grouped: parent dept di atas, sub-dept indent di bawah.
- User multi-dept bisa switch ke leaf manapun yang dia punya UBU row aktif-nya.
- GM/Asisten GM yang `primary_department_id` di S&M (root) **tetap bisa drill-down** ke BSD/COM/CMC kalau mereka juga punya UBU row di sub-dept tersebut.
- Default: kalau user hanya punya assignment di root S&M (tanpa UBU di sub-dept), dia tetap bisa lihat data semua sub-dept tanpa harus switch (karena scope inherit lewat `descendantIds()`).

Detail wiring di Section 06.

## Backward Compatibility

Semua dept lama (HR, ACC, GA, dst) punya `parent_department_id=null`, `children=[]`, jadi `descendantIds()` return `[self.id]`. Behavior identik dengan sebelum PRD.

User di dept flat tidak terpengaruh perubahan ini.

## Risiko

| Risk | Mitigation |
|---|---|
| Callsite query `where('department_id', $id)` yang belum di-update akan miss data sub-dept saat user di root dept. | Audit menyeluruh di Section 06 + grep test sebelum merge. |
| `descendantIds()` query tambahan per request → N+1. | Eager load `children` di Inertia shared props. Cache result per request via `Auth::user()->loadMissing(...)`. |
| User di dept flat (HR) tidak sengaja kena `descendantIds()` query → wasted call. | Tetap aman, query return `[self.id]` cepat. Indexed by `id`. |
| GM yang tidak punya UBU row di sub-dept tidak bisa create record di sub-dept (mis. buat task untuk BSD). | Form Request validation harus cek scope yang lebih luas (descendant), bukan exact match `department_id = current_department_id`. |

## Verification

```bash
php artisan tinker
>>> $etik = User::where('email','andri@werkudara.com')->first();
>>> $etik->primaryDepartment->descendantIds();
# expect: [SM_id, BSD_id, COM_id, CMC_id]

>>> $irvani = User::where('email','irvani@werkudara.com')->first();
>>> $irvani->primaryDepartment->descendantIds();
# expect: [BSD_id]

>>> $dita = User::where('email','dita@werkudara.com')->first();
>>> $dita->primaryDepartment->descendantIds();
# expect: [HR_id]  // dept flat, tidak terpengaruh
```

Test plan lengkap di Section 08.
