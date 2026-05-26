# 07 - Rollout & Rollback

Status: draft
Depends on: 01, 03, 05, 06

## Prinsip

- **Idempotent**: tiap step bisa dijalankan ulang tanpa side effect.
- **Reversible**: tiap step punya rollback path eksplisit.
- **Single deployment window**: tidak ada periode dual-access (per requirement PO).
- **Foreground migration**: user tidak login saat rollout. Maintenance mode aktif.

## Urutan Eksekusi

```
1. Pre-flight checks (env staging dulu)
2. Backup DB
3. Maintenance mode ON
4. Deploy code (migration + model + controller + frontend)
5. Run migration `parent_department_id`
6. Run seeder dept baru (S&M + 3 sub-dept)
7. Run seeder position custom (GM, Manager, dst)
8. Run data migration script (pindah user)
9. Smoke test (admin login, GM login, sample staff login)
10. Maintenance mode OFF
11. Post-deploy verification
```

## Step-by-step

### 1. Pre-flight (staging mirror)

```bash
# Sync staging DB dari production snapshot terbaru
# Jalankan urutan 4-9 di staging dulu, full
# Verify hasil match expected (Section 08)
```

Output yang harus diverifikasi:
- Total user count tidak berubah.
- Total dept aktif: existing + 4 (S&M, BSD, COM, CMC) + Executive Office WG (kalau Opsi A diambil).
- 12 user yang pindah ada di dept baru, UBU lama deactivate.

### 2. Backup DB

```bash
# Production
mysqldump --single-transaction --routines --triggers \
    --databases <db> > backup-pre-restructure-2026-05-25.sql

# Verify size & restore-ability di env terpisah sebelum lanjut.
```

### 3. Maintenance mode

```bash
php artisan down --secret="<token>" --render="errors::503"
```

### 4. Deploy code

Branch: `feature/wns-restructure-2026`. Sudah harus include:
- Migration `2026_05_25_100000_add_parent_department_id_to_departments_table.php`
- Updated `Department` model (relasi parent/children, helper, validation)
- Updated `HandleInertiaRequests` (tree props)
- Updated controller scope (Activity, Cashflow, Purchasing — Section 06)
- Updated frontend `DepartmentSwitcher.tsx` + form selector
- Seeder baru: `WNSSalesMarketingSeeder`, `WNSSalesMarketingPositionSeeder`
- Data migration command: `php artisan wns:migrate-restructure-2026`

### 5. Migration

```bash
php artisan migrate --force
```

Cek hasil:
```bash
php artisan tinker --execute="echo Schema::hasColumn('departments','parent_department_id') ? 'OK' : 'FAIL';"
```

### 6. Seeder dept baru

```bash
php artisan db:seed --class=Database\\Seeders\\WG\\WGExecutiveOfficeSeeder --force
php artisan db:seed --class=Database\\Seeders\\WNS\\WNSExecutiveOfficeSeeder --force
php artisan db:seed --class=Database\\Seeders\\WNS\\WNSSalesMarketingSeeder --force
```

Idempotent: pakai `Department::updateOrCreate(['business_unit_id'=>...,'code'=>...], [...])`.

`WGExecutiveOfficeSeeder` seed dept `EXEC` di WG + 2 position custom (CEO_EXEC, MD_EXEC).
`WNSExecutiveOfficeSeeder` seed dept `EXEC` di WNS + 1 position custom (COS_EXEC) — Chief of Staff Adiel (PO 2026-05-26 revision).
`WNSSalesMarketingSeeder` seed dept `SM` + 3 sub (BSD/COM/CMC) tanpa position (sub-dept skip default).

### 7. Seeder position

```bash
php artisan db:seed --class=Database\\Seeders\\WNS\\WNSSalesMarketingPositionSeeder --force
```

Idempotent: `Position::firstOrCreate(['department_id'=>..., 'code'=>'GM_SM'], [...])`.

### 7.5 Insert user baru (Etik & Kayana)

Dua user benar-benar baru di struktur 2026:
- **Etik Andriyanti** (`andri@werkudara.com`) — General Manager Sales & Marketing
- **I.D.A. Kayana Abhipraya P.B.** (`abhi@werkudara.com`) — Market Analyst CMC

User lain di mapping section 04 sudah existing di DB (per hasil scan tinker).

```bash
php artisan db:seed --class=Database\\Seeders\\WNS\\WNSEtikUserSeeder --force
php artisan db:seed --class=Database\\Seeders\\WNS\\WNSKayanaUserSeeder --force
```

Kedua seeder idempotent (`firstOrCreate` on email).

Verify:
```bash
php artisan tinker --execute="foreach(['andri@werkudara.com','abhi@werkudara.com'] as \$e){\$u=\App\Models\Core\User::where('email',\$e)->first();echo \$e.': '.(\$u? 'OK id='.\$u->id : 'MISSING').PHP_EOL;}"
```

### 8. Data migration

Custom artisan command (bukan seeder, supaya bisa dry-run):

```bash
php artisan wns:migrate-restructure-2026 --dry-run
# review output, lalu
php artisan wns:migrate-restructure-2026 --execute
```

Detail mapping di Section 04. Command harus:
- Update `users.primary_department_id` per mapping.
- Update `users.primary_position_id` per mapping (kalau position user berubah).
- Insert UBU baru untuk (user, WNS, dept_baru, position_baru, is_primary=true).
- Set UBU lama (user, WNS, dept_lama) jadi `is_primary=false, is_active=false`.
- Tidak menyentuh data domain (task, line item, PR) — itu auto-ikut karena `created_by` user-nya tidak berubah.

### 9. Smoke test

Login sample (3 akun):
- Super admin → cek bisa lihat dept tree di admin.
- Etik (GM S&M) → cek dashboard nampilin data BSD/COM/CMC.
- Salah satu user yang pindah (mis. Vanessa) → cek primary dept = COM, bukan SO.

### 10. Maintenance OFF

```bash
php artisan up
```

### 11. Post-deploy verification

Section 08 checklist.

## Rollback Path

Tier 1 — kalau gagal di step 5-8 (sebelum user login lagi):
```bash
mysql <db> < backup-pre-restructure-2026-05-25.sql
git revert <commit>
php artisan up
```

Tier 2 — kalau bug ditemukan setelah user mulai login:
- Tidak full restore (data baru sudah masuk).
- Patch forward: deploy fix, jangan rollback DB.
- Kalau benar-benar harus rollback, koordinasi dengan PO untuk window kedua maintenance.

## Idempotency Guarantees

| Step | Mekanisme | Re-run safe? |
|---|---|---|
| Migration | Laravel migration tracked di `migrations` table | yes |
| Seeder dept | `updateOrCreate` by `(business_unit_id, code)` | yes |
| Seeder position | `firstOrCreate` by `(department_id, code)` | yes |
| Data migration | Cek state UBU sebelum update; skip kalau sudah migrated | yes (dengan flag `--force-rerun` opsional) |

## Maintenance Mode Window

Estimasi: ~30 menit total (mostly buat verification step 9).

- Backup: ~5 menit
- Migration + seeder: ~2 menit
- Data migration command: ~5 menit
- Smoke test: ~15 menit
- Buffer: ~3 menit

Schedule rekomendasi: weekend pagi, jam low-traffic.

## Observability

Selama dan sesudah rollout:
- Monitor `storage/logs/laravel.log` untuk error baru.
- Monitor query log selama 1 jam untuk N+1 baru dari `descendantIds()`.
- Cek error report dari Sentry / log aggregator (kalau ada).
- Notifikasi internal: pos di Slack/email channel saat done.

## Communication

- T-7 hari: announce maintenance window ke user.
- T-1 hari: reminder.
- T-0: maintenance mode banner aktif.
- Post: announce selesai + heads-up untuk user S&M tentang struktur baru.

## Open Question

1. Konfirmasi window maintenance (weekend mana).
2. Konfirmasi PIC backup dan rollback (siapa eksekusi).
3. Apakah ada compliance/audit yang perlu di-loop in (mis. archive tabel sebelum migrasi)?
