# PRD - WNS Restructure 2026

Status: draft
Created: 2026-05-25
Owner: PM Agent
Stakeholders: Product Owner, Core Admin module, Activity Tracking, Cashflow Projection, Purchasing

## Goal

Mengakomodasi struktur organisasi baru Werkudara Group (efektif TBA 2026) yang memperkenalkan:

1. **Sales & Marketing Department** baru di WNS dengan 3 sub-department (Division):
   - Business Solutions Division
   - Commercial Division
   - Corporate Marketing Communication Division
2. **General Manager** dan **Asisten GM** sebagai role di level Department (di atas sub-department).
3. **Executive Office** di WG dengan Board of Director (CEO, MD) dan
   **Executive Office** di WNS untuk Chief of Staff (Adiel) — *PO 2026-05-26
   revision: Adiel di-host di WNS, bukan WG*.
4. **Konsep parent-child department** sebagai foundation hirarki dua tingkat.

Tanpa update ini, struktur baru tidak bisa dimodelkan dengan benar di sistem (saat ini hanya support 1 tingkat: BU → Department).

## Non-Goal

- Tidak mengubah konsep Business Unit. WG tetap parent, WNS/UT/MRP/WNN tetap child.
- Tidak menyentuh module yang deprecated (`SalesCrm`).
- Tidak mengubah skema `users` di luar `primary_department_id` reassignment.
- Tidak refaktor `position` table — hanya extend kalau diperlukan untuk role baru (GM, Manager, Coordinator).
- Tidak rebuild `number_sequences`. Sequence per dept lama tetap aktif untuk backward compatibility.
- Tidak migrasi history activity log — hanya assignment user yang pindah, history tetap di dept lama.

## Summary

| Area | Perubahan |
|------|-----------|
| Schema | Tambah `parent_department_id` (nullable, self-FK) ke tabel `departments`. |
| Position | Extend `access_level` enum dengan `general_manager`, `manager`, `coordinator` (TBD). |
| Departments | Buat dept S&M baru di WNS + 3 sub-dept (BSD, COM, CMC). Buat dept Executive Office di WG (TBD). |
| Users | Pindahkan + remap dept/position untuk ~12 user existing (data ikut). User board WG (Fadli, Yakti) dan staff S&M (Etik, Ainur, Irvani, Kensrie, dst) sudah ada di DB — hanya perlu remap, bukan insert baru. Adiel pindah ke WNS/EXEC (cross-BU). |
| Authorization | GM dan Asisten GM dapat akses descendant sub-dept via `descendantIds()` resolution. |
| Modules | Update Activity & Cashflow scoping query untuk handle parent-child. Numbering tetap per leaf dept. |
| Rollout | Seeder + data migration script. Tidak ada periode dual-access. |

## Out of Scope (untuk PRD ini)

- Approval workflow re-routing per dept baru — dikerjakan terpisah setelah dept baru hidup.
- Frontend redesign Department switcher untuk grouped display — bisa jadi follow-up.
- Reporting per division di Activity Admin — nungguin section 06.

## Section Files

PRD ini dipecah per area supaya review-able:

- `01-schema-changes.md` — migration `parent_department_id` + constraint.
- `02-target-structure.md` — full org chart WG + WNS sesudah update (butuh konten PDF lengkap).
- `03-position-hierarchy.md` — extend access_level + mapping role baru.
- `04-data-migration-plan.md` — per user: pindah / baru / keep + skrip migrasi (butuh email user baru).
- `05-authorization-rules.md` — GM, Asisten GM, Manager, Coordinator scope.
- `06-module-impact.md` — Activity, Cashflow, Purchasing, Numbering, Inertia props.
- `07-rollout-rollback.md` — urutan seeder, data script, rollback path.
- `08-verification-checklist.md` — test plan + sanity check.

## Open Questions (blokir progress)

1. **Konten lengkap `excel/strukturnew.pdf`** — apakah dept WNS lain (HR, ACC, CFC, GA, SS, BAS, BID, PD, ACS, TEP) juga dapat sub-department, atau tetap flat? Section 02 dan 04 menunggu jawaban ini.
2. **Email user baru** — Fadli Fahmi Ali, I Gusti Putu Yaktianuraga, Gilang Risnantyo, Irvani Putri, Kensrie Diah A., Emy Nurhayati, Nindy Amalia, Mya Mar'atus S., Linda Susanto, I.D.A. Kayana Abhipraya P.B.
3. **Asisten GM scope** — apakah Ainur (Asisten GM) dapat full visibility seperti Etik (GM), atau read-only/no-approve?
4. **Cashflow line item per division** — apakah BSD/COM/CMC punya line item sendiri, atau roll-up ke S&M?
5. **Numbering format** — sub-dept pakai sequence sendiri (`PR.BSD/...`) atau pakai parent dept (`PR.SM/...`)?

## References

- Baseline: `docs/specs/department-system-overview.md`
- Architecture: `docs/architecture.md`
- Source kode utama: `app/Models/Core/Department.php`, `app/Http/Middleware/EnsureBusinessUnitSelected.php`
- Seeder yang akan diupdate: `database/seeders/WNS/WNSDepartmentSeeder.php`, `database/seeders/WNS/WNSUserSeeder.php`
