# PRD: WNS Restructure 2026

Folder: `docs/specs/2026-05-25-wns-restructure-prd/`

PRD untuk update struktur organisasi WNS (introduce Sales & Marketing Department, sub-department concept, GM/Asisten GM role) dan Executive Office di WG dan WNS (PO 2026-05-26 revision: Adiel di WNS/EXEC, bukan WG/EXEC).

## Status Sections

| File | Status | Blocker |
|---|---|---|
| `00-overview.md` | done | — |
| `01-schema-changes.md` | done | — |
| `02-target-structure.md` | **blocked** | konten lengkap `excel/strukturnew.pdf` |
| `03-position-hierarchy.md` | done | — |
| `04-data-migration-plan.md` | **blocked** | email user baru + konten PDF |
| `05-authorization-rules.md` | done | — |
| `06-module-impact.md` | done | — |
| `07-rollout-rollback.md` | done | — |
| `08-verification-checklist.md` | done | — |

## Untuk unblock 02 dan 04

PO perlu siapkan:

1. **Konten lengkap `excel/strukturnew.pdf`** (atau paste sebagai text) — terutama untuk dept WNS lain di luar S&M:
   - HR, ACC, CFC, GA, SS, BAS, BID, PD, ACS, TEP
   - Apakah dept-dept ini juga dapat sub-department, atau tetap flat?
   - Siapa HOD baru di tiap dept?

2. **Email user existing** yang perlu di-confirm di DB (sebagian besar sudah dibuat manual via admin UI, bukan via seeder):
   - WG Board: Fadli (`fadli@werkudara.com` ✓ confirmed di seeder), Yakti (`bagus@werkudara.com` ✓ confirmed di seeder)
   - WNS Chief of Staff: Adiel (`adiel@werkudara.com`) — *PO 2026-05-26: di-host di WNS/EXEC, bukan WG/EXEC*
   - WNS / S&M GM: Etik (`andri@werkudara.com` per koreksi PO)
   - WNS / SO: Gilang (`gilang@werkudara.com` per koreksi PO)
   - WNS / S&M / BSD Managers: Irvani (`irvani@werkudara.com`), Kensrie (`kensrie@werkudara.com`) — confirmed by PO; perlu cek email Emy, Nindy, Mya
   - WNS / S&M / COM: Linda Susanto — confirm email
   - WNS / S&M / CMC: I.D.A. Kayana Abhipraya P.B. — confirm email

   Verify via:
   ```powershell
   php artisan tinker --execute="\App\Models\Core\User::whereIn('email',['fadli@werkudara.com','bagus@werkudara.com','adiel@werkudara.com','andri@werkudara.com','gilang@werkudara.com','irvani@werkudara.com','kensrie@werkudara.com'])->each(fn(\$u) => print(\$u->email.' | dept='.(\$u->primaryDepartment->code ?? 'null').PHP_EOL));"
   ```

3. **Keputusan PO untuk open question:**
   - Approval chain di sub-dept: Manager → langsung BU-level, atau via Asisten GM/GM dulu? (Section 06)
   - Cashflow line item di root dept: boleh atau tidak? Default PRD: tidak. (Section 06)
   - Numbering format: `PR.BSD/...` (leaf) atau `PR.SM-BSD/...`? Default PRD: leaf saja. (Section 06)
   - Department lama WG (CEO, MD, SYSADMIN): di-deactivate dan ganti `EXEC` baru, atau keep? Default PRD: deactivate. (Section 05)
   - Manager di sub-dept: `access_level=department_head`? (Section 03)
   - Brand Experience & Partnership Lead: `team_leader` atau `department_head`? (Section 03)
   - Window maintenance: weekend kapan? (Section 07)

## Reading Order

1. `00-overview.md` — goal, non-goal, summary.
2. `01-schema-changes.md` — migration `parent_department_id` + Department model update.
3. `03-position-hierarchy.md` — mapping role baru (GM, Asisten GM, Manager, dst) ke `access_level`.
4. `05-authorization-rules.md` — scope resolution via `descendantIds()`.
5. `06-module-impact.md` — Activity, Cashflow, Purchasing, Numbering, Inertia.
6. `07-rollout-rollback.md` — urutan eksekusi & rollback.
7. `08-verification-checklist.md` — checklist pre/post-deploy + test.
8. `02-target-structure.md` — *(menunggu data PDF lengkap)*.
9. `04-data-migration-plan.md` — *(menunggu data PDF lengkap + email user baru)*.

## Related Docs

- Baseline domain: `docs/specs/department-system-overview.md`
- Architecture: `docs/architecture.md`
- Coding standards: `docs/coding_standards.json`
- Active execution log: `docs/exec_plans.md`

## Next Action

PM Agent menunggu input dari Product Owner (3 item di atas). Setelah lengkap, draft section 02 dan 04 dimulai.
