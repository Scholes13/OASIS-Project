# 03 - Position Hierarchy

Status: draft
Depends on: 00-overview, 01-schema-changes

## Current State

Sistem saat ini punya 4 level fix di `positions.access_level` (`Department::ensureDefaultPositions()`):

| Code Pattern | level | access_level | hierarchy_level |
|---|---|---|---|
| `EXEC_<DEPT>` | hod | `executive` | 0 |
| `HOD_<DEPT>` | hod | `department_head` | 1 |
| `LEAD_<DEPT>` | leader | `team_leader` | 2 |
| `STAFF_<DEPT>` | staff | `staff` | 3 |

`access_level` dipakai di authorization gates dan visibility scope (Activity Admin, Cashflow). 4 level ini tidak cukup untuk menampung role baru.

## Role Baru di Struktur 2026

Dari snippet S&M dan koreksi user:

| Role | Letak di Hierarchy | Existing access_level? |
|---|---|---|
| Chief Executive Officer | WG / Executive Office | tidak ada (≈ executive) |
| Managing Director | WG / Executive Office | tidak ada (≈ executive) |
| Chief of Staff | WNS / Executive Office | tidak ada |
| General Manager | WNS / S&M (root dept) | tidak ada (di atas HOD) |
| Asisten GM | WNS / S&M (root dept) | tidak ada |
| Manager (BSD/COM) | WNS / S&M / sub-dept | tidak ada (≈ HOD untuk sub-dept) |
| Coordinator | WNS / S&M / sub-dept | bisa dipetakan ke `team_leader` |
| Lead | WNS / S&M / sub-dept | bisa dipetakan ke `team_leader` |
| Specialist / Analyst / Engineer / Strategist / Designer | WNS / S&M / sub-dept | bisa dipetakan ke `staff` |
| Sales Operation Coordinator | WNS / SO | `team_leader` |
| Sales Operation (staff) | WNS / SO | `staff` |
| Business Solutions Manager (di BSD) | WNS / S&M / BSD | (perlu putusan) |

## Decision

Tetap pakai 4 `access_level` lama untuk semantic authorization, **tambah role baru hanya di level position name**, bukan extend enum. Alasannya:

1. **`access_level` mengontrol gate** — kalau extend, semua callsite gate harus di-audit (ada >40 di codebase). High blast radius.
2. **Bisnis title bisa berubah** (Manager, Specialist, Engineer), tapi struktur otoritas relatif stabil (executive, department_head, team_leader, staff).
3. **Position table sudah support free-form `name`** — title bisa custom tanpa schema change.

### Mapping role baru ke access_level existing

| Role / Title | access_level | level | hierarchy_level | Catatan |
|---|---|---|---|---|
| CEO | `executive` | c_level | 0 | di root dept WG/EXEC |
| Managing Director | `executive` | c_level | 0 | di root dept WG/EXEC |
| Chief of Staff | `executive` | c_level | 0 | di root dept WNS/EXEC (PO 2026-05-26 revision) |
| General Manager | `department_head` | hod | 0 | di **root dept** WNS/S&M; descendant scope dari `Department::descendantIds()`, BUKAN dari access_level |
| Asisten GM | `department_head` | hod | 1 | di root dept WNS/S&M, scope = descendant via parent membership |
| Manager (BSD/COM/CMC) | `department_head` | hod | 1 | di **sub-dept** S&M/BSD dst |
| Coordinator | `team_leader` | leader | 2 | di sub-dept |
| Lead | `team_leader` | leader | 2 | di sub-dept |
| Specialist/Analyst/Engineer/Strategist/Designer | `staff` | staff | 3 | di sub-dept |

### Kenapa GM **bukan** `executive` / `c_level`

Versi awal PRD ini sempat memberikan GM `level=c_level` + `access_level=executive` agar visibility-nya melampaui HOD biasa. Itu **salah**: existing `Position::scopeTopManagement()` (`Position.php:170-176`) mendefinisikan top management sebagai `level=c_level` ATAU `access_level=executive`. Akibatnya gate `hasTopManagementAccess()` true, dan `NavigationService::canAccessPurchasingAdmin()` (`NavigationService.php:385-389`) auto-grant Purchasing Admin ke GM. Itu bukan yang diinginkan.

Visibility GM ke sub-dept (BSD/COM/CMC) datang dari **parent-child relation**, bukan dari position elevation. Jadi GM cukup `level=hod / access_level=department_head` — sama seperti Manager — dengan keuntungan ekstra `descendantIds()` karena dia ada di root dept yang punya children. Approval chain dan `view-reports` gate tetap eksklusif untuk Board of Director di WG/EXEC.

### Standing rule untuk PRD masa depan

Saat membuat role baru di **root dept yang punya children**: default ke `department_head` (bukan `executive`). Visibility lintas-children sudah otomatis lewat `Department::descendantIds()`. Hanya pakai `c_level` / `executive` untuk role BOD-tier yang harus tembus gate organisasional global (Purchasing Admin, view-reports, dst). Pattern lama `EXEC_<DEPT>` di `Department::ensureDefaultPositions()` sengaja dibiarkan untuk backward compat — tapi PRD baru jangan reuse pattern itu untuk role yang bukan BOD-tier.

### Asisten GM = `department_head`

Ainur sebagai Asisten GM dapat:
- Akses descendant (BSD, COM, CMC) sama seperti GM (lewat parent dept membership).
- Tapi hierarchy-nya 1 (di bawah GM yang hierarchy 0), supaya approval chain bisa di-route GM dulu kalau ada keduanya.
- Kalau ada kebutuhan no-approve / read-only, itu ditangani via separate gate, bukan via access_level.

## Position Records yang perlu dibuat

Untuk root dept S&M, override default positions (lihat Section 01 — `ensureDefaultPositions` di-skip untuk sub-dept tapi tetap dipanggil untuk root). Tambah position custom via seeder:

### Untuk WNS / S&M (root dept)

| code | name | level | access_level | hierarchy_level |
|---|---|---|---|---|
| `GM_SM` | General Manager Sales & Marketing | hod | executive | 0 |
| `ASGM_SM` | Asisten GM Sales & Marketing | hod | department_head | 1 |
| (default 4 lainnya tetap auto-generated) | | | | |

### Untuk WNS / S&M / Business Solutions (sub-dept)

| code | name | level | access_level | hierarchy_level |
|---|---|---|---|---|
| `MGR_BSD` | Business Solutions Manager | hod | department_head | 1 |
| `COORD_BSD` | Business Solutions Coordinator | leader | team_leader | 2 |
| `SPEC_BSD` | Business Solutions Specialist | staff | staff | 3 |
| `ENG_BSD` | Commercial Engineer | staff | staff | 3 |

### Untuk WNS / S&M / Commercial (sub-dept)

| code | name | level | access_level | hierarchy_level |
|---|---|---|---|---|
| `MGR_COM` | Commercial Manager | hod | department_head | 1 |
| `ANL_COM` | Pricing & Costing Analyst | staff | staff | 3 |
| `DSN_COM` | Commercial Creative Designer | staff | staff | 3 |

### Untuk WNS / S&M / Corporate Marketing Communication (sub-dept)

| code | name | level | access_level | hierarchy_level |
|---|---|---|---|---|
| `LEAD_CMC` | Brand Experience & Partnership Lead | leader | team_leader | 2 |
| `ANL_CMC` | Market Analyst | staff | staff | 3 |
| `STG_CMC` | Creative Content Strategist | staff | staff | 3 |
| `DSN_CMC` | Creative Content Designer | staff | staff | 3 |

### Untuk WNS / SO (root dept, existing)

Tambah di samping default:

| code | name | level | access_level | hierarchy_level |
|---|---|---|---|---|
| `COORD_SO` | Sales Operation Coordinator | leader | team_leader | 2 |

Position default `STAFF_SO` tetap dipakai untuk Bulqis dan Zaky.

### Untuk WG / Executive Office (root dept baru)

| code | name | level | access_level | hierarchy_level |
|---|---|---|---|---|
| `CEO_EXEC` | Chief Executive Officer | hod | executive | 0 |
| `MD_EXEC` | Managing Director | hod | executive | 0 |

(Default 4 positions dari `ensureDefaultPositions` tetap di-generate, jadi `EXEC_EXEC`, `HOD_EXEC`, dst tetap ada — tidak masalah karena tidak conflict.)

### Untuk WNS / Executive Office (root dept baru, PO 2026-05-26 revision)

| code | name | level | access_level | hierarchy_level |
|---|---|---|---|---|
| `COS_EXEC` | Chief of Staff | c_level | executive | 0 |

Adiel Priyarama (`adiel@werkudara.com`) sits here. Dengan `level=c_level` + `access_level=executive`, `Position::scopeTopManagement()` jadi true sehingga visibility-nya tembus semua module WNS (Activity Admin, Purchasing Admin, IT Support Admin, dst) lewat existing executive-scope guards.

## Constraint: Code Uniqueness

Position `code` di tabel `positions` tidak unique global, tapi unique per `department_id`. Pastikan code-code di atas tidak bentrok dengan default position yang auto-generated.

Cek bentrok:
- `EXEC_SM` (auto) vs `GM_SM` (custom) — beda code, tidak bentrok.
- `HOD_SM` (auto) vs `ASGM_SM` (custom) — beda code, tidak bentrok.

## Implementasi (preview seeder)

```php
// database/seeders/WNS/WNSSalesMarketingPositionSeeder.php
$smDept = Department::where('business_unit_id', $wns->id)->where('code', 'SM')->firstOrFail();

Position::firstOrCreate(
    ['department_id' => $smDept->id, 'code' => 'GM_SM'],
    [
        'name' => 'General Manager Sales & Marketing',
        'level' => 'hod',
        'access_level' => 'executive',
        'hierarchy_level' => 0,
        'is_active' => true,
    ]
);

Position::firstOrCreate(
    ['department_id' => $smDept->id, 'code' => 'ASGM_SM'],
    [
        'name' => 'Asisten GM Sales & Marketing',
        'level' => 'hod',
        'access_level' => 'department_head',
        'hierarchy_level' => 1,
        'is_active' => true,
    ]
);

// ...dst untuk sub-dept
```

## Risiko & Mitigation

| Risk | Mitigation |
|------|------------|
| User dengan `access_level=executive` di S&M (Etik) bisa lihat data dept lain di WNS karena scope query terlalu lebar. | Pastikan scope query selalu cek `descendantIds()` dari dept user, bukan semua dept di BU. Section 05. |
| Manager di sub-dept (BSD) punya `access_level=department_head` → bisa approve PR sub-dept-nya. Tapi di system existing, HOD approve dept (root). Bisa skip approval level S&M. | Approval workflow harus di-update untuk cascade: Manager BSD → GM S&M → BU level. Out of scope PRD ini, follow-up. |
| `team_leader` di sub-dept (Coordinator BSD) dapat scope berbeda dari `team_leader` di dept flat (Leader GA). | Audit existing query yang filter by `level=leader`. Sebagian besar pakai `access_level=team_leader` yang sudah konsisten. |

## Open Question

1. **Manager di sub-dept harus `executive` atau `department_head`?** Saya pilih `department_head` karena scope-nya hanya 1 sub-dept (tidak punya descendant). Konfirmasi?
2. **Brand Experience & Partnership Lead** — sebenarnya nama "Lead" condong ke `team_leader`, tapi posisinya satu-satunya HoD-equivalent di CMC. Pilih `team_leader` atau `department_head`? Saya rekomendasi `team_leader` supaya konsisten dengan title-nya, dan CMC tidak punya HOD formal.
3. **Chief of Staff** — saya pilih `executive`. Konfirmasi atau perlu role khusus (mis. `chief_of_staff` baru)? **(PO 2026-05-26: confirmed `c_level/executive` — Adiel di-host di WNS/EXEC, bukan WG/EXEC.)**

## Verification

Setelah seeder run:
```bash
php artisan tinker
>>> \App\Models\Core\Position::whereHas('department', fn($q) => $q->where('code','SM'))->get(['code','name','access_level','hierarchy_level'])
>>> \App\Models\Core\Position::where('access_level', 'executive')->where('department_id', $sm->id)->count()
# expect: 2 (auto EXEC_SM + custom GM_SM) — atau perlu adjust supaya tidak duplikat semantic
```

Kalau hasil count > yang diinginkan (mis. EXEC_SM auto-generated bertabrakan semantic dengan GM_SM), revisi rule `ensureDefaultPositions` di Section 01 untuk skip `EXEC_*` di root dept yang punya GM.
