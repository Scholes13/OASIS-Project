# 04 - Data Migration Plan

Status: **BLOCKED** — menunggu input PO
Depends on: 02-target-structure, 03-position-hierarchy

## Blocker

Butuh dua input:
1. Konten lengkap `excel/strukturnew.pdf` (untuk dept selain S&M)
2. Email user baru (10 orang, lihat `README.md`)

## Strategi

Data migration dilakukan via custom artisan command (bukan seeder), supaya bisa di-`dry-run`:

```
app/Console/Commands/Wns/MigrateRestructure2026.php
```

Signature:
```
php artisan wns:migrate-restructure-2026 {--dry-run} {--execute} {--user-csv=}
```

Tidak menyentuh data domain (task, line item, PR). Yang berubah hanya:
- `users.primary_department_id`
- `users.primary_position_id`
- `user_business_units` (insert baru, deactivate lama)
- User account baru (insert ke `users` table, default password, force reset di login pertama)

## Mapping User (preview, hanya S&M scope)

User existing yang pindah:

| Email | Nama | Dept Lama | Dept Baru | Position Baru |
|---|---|---|---|---|
| mitha@werkudara.com | Paramitha Maharesmi | WNS/PD | WNS/SM/BSD | MGR_BSD |
| tsania@werkudara.com | Wulida Tsania H.A. | WNS/PD | WNS/SM/BSD | ENG_BSD |
| enggar@werkudara.com | F.A. Anggito Enggarjati | WNS/PD | WNS/SM/BSD | ENG_BSD |
| ainur@werkudara.com | Ainur Hasanah | WNS/TEP | WNS/SM (root) | ASGM_SM |
| elfasa@werkudara.com | Elfasa Khoirumansyah | WNS/TEP | WNS/SM/BSD | SPEC_BSD |
| vanessa@werkudara.com | Vanessa Salvathea | WNS/SO | WNS/SM/COM | ANL_COM |
| haekal@werkudara.com | Muhammad Haekal Baihaqi | WNS/SO | WNS/SM/COM | ANL_COM |
| refangga@werkudara.com | Refangga | WNS/ACS | WNS/SM/COM | DSN_COM |
| jaka@werkudara.com | Fuad Jaka P. | WNS/BID | WNS/SM/CMC | LEAD_CMC |
| septian@werkudara.com | Septian Mahendra D. | WNS/ACS | WNS/SM/CMC | STG_CMC |
| andrew@werkudara.com | Andrew Ardhany S. | WNS/ACS | WNS/SM/CMC | DSN_CMC |
| andri@werkudara.com | Etik Andriyanti | (cek) | WNS/SM (root) | GM_SM |

User existing yang berubah dept/position (sudah ada di DB, tidak perlu insert ulang):

| Email | Nama | Status | Dept Baru | Position Baru | Catatan |
|---|---|---|---|---|---|
| fadli@werkudara.com | Fadli Fahmi Ali | EXISTING | WG/EXEC | CEO_EXEC | confirmed di `UserSeeder.php` |
| bagus@werkudara.com | I Gusti Putu Yaktianuraga | EXISTING | WG/EXEC | MD_EXEC | confirmed di `UserSeeder.php` (position 110 = TOP_MANAGEMENT, akan di-remap ke MD_EXEC) |
| adiel@werkudara.com | Adiel Priyarama | EXISTING | WNS/EXEC | COS_EXEC | manual user, confirmed by PO 2026-05-26 (cross-BU move from WG to WNS) |
| andri@werkudara.com | Etik Andriyanti | EXISTING | WNS/SM (root) | GM_SM | manual user, confirmed by PO |
| ainur@werkudara.com | Ainur Hasanah | EXISTING | WNS/SM (root) | ASGM_SM | seeded di `WNSUserSeeder.php` (dept lama: TEP) |
| gilang@werkudara.com | Gilang Risnantyo | EXISTING | WNS/SO | COORD_SO | manual user, confirmed by PO |
| irvani@werkudara.com | Irvani Putri | EXISTING | WNS/SM/BSD | MGR_BSD | manual user, confirmed by PO |
| kensrie@werkudara.com | Kensrie Diah A. | EXISTING | WNS/SM/BSD | MGR_BSD | manual user, confirmed by PO |
| (TBD email) | Emy Nurhayati | EXISTING | WNS/SM/BSD | MGR_BSD | confirmed by PO; verify email pre-run |
| (TBD email) | Nindy Amalia | EXISTING | WNS/SM/BSD | MGR_BSD | confirmed by PO; verify email pre-run |
| (TBD email) | Mya Mar'atus S. | EXISTING | WNS/SM/BSD | MGR_BSD | confirmed by PO; verify email pre-run |
| (TBD email) | Linda Susanto | EXISTING | WNS/SM/COM | MGR_COM | confirmed by PO; verify email pre-run |
| mitha@werkudara.com | Paramitha Maharesmi | EXISTING | WNS/SM/BSD | MGR_BSD | seeded di `WNSUserSeeder.php` (dept lama: PD) |
| tsania@werkudara.com | Wulida Tsania H.A. | EXISTING | WNS/SM/BSD | ENG_BSD | seeded (dept lama: PD) |
| enggar@werkudara.com | F.A. Anggito Enggarjati | EXISTING | WNS/SM/BSD | ENG_BSD | seeded (dept lama: PD) |
| elfasa@werkudara.com | Elfasa Khoirumansyah | EXISTING | WNS/SM/BSD | SPEC_BSD | seeded (dept lama: TEP) |
| vanessa@werkudara.com | Vanessa Salvathea | EXISTING | WNS/SM/COM | ANL_COM | seeded (dept lama: SO) |
| haekal@werkudara.com | Muhammad Haekal Baihaqi | EXISTING | WNS/SM/COM | ANL_COM | seeded (dept lama: SO) |
| refangga@werkudara.com | Refangga | EXISTING | WNS/SM/COM | DSN_COM | seeded (dept lama: ACS) |
| jaka@werkudara.com | Fuad Jaka P. | EXISTING | WNS/SM/CMC | LEAD_CMC | seeded (dept lama: BID) |
| septian@werkudara.com | Septian Mahendra D. | EXISTING | WNS/SM/CMC | STG_CMC | seeded (dept lama: ACS) |
| andrew@werkudara.com | Andrew Ardhany S. | EXISTING | WNS/SM/CMC | DSN_CMC | seeded (dept lama: ACS) |

User baru yang harus di-insert sebelum migration:

| Email | Nama | Status | Dept Tujuan | Position |
|---|---|---|---|---|
| (TBD email, mis. `kayana@werkudara.com`) | I.D.A. Kayana Abhipraya P.B. | **NEW** | WNS/SM/CMC | ANL_CMC |

Insert via seeder kecil idempotent: `database/seeders/WNS/WNSKayanaUserSeeder.php`. Jalan **sebelum** data migration command (lihat Section 07 step 7.5).

**Pre-run verification** untuk Emy, Nindy, Mya, Linda — confirm email aktualnya:
```powershell
php artisan tinker --execute="\App\Models\Core\User::where('name','like','%Emy Nurhayati%')->orWhere('name','like','%Nindy Amalia%')->orWhere('name','like','%Mar''atus%')->orWhere('name','like','%Linda Susanto%')->get(['id','name','email'])->each(fn(\$u) => print(\$u->email.' | '.\$u->name.PHP_EOL));"
```

User yang TIDAK pindah (tetap di dept lama):

| Email | Dept | Catatan |
|---|---|---|
| bulqis@werkudara.com | WNS/SO | tetap |
| zaky@werkudara.com | WNS/SO | tetap |
| chelsea@werkudara.com | WNS/SO | tetap (asumsi, perlu konfirmasi) |
| ...semua user dept HR/ACC/CFC/GA/SS/BAS dst | (cek section 02 saat data lengkap) |

## Logika Command

Pseudo-code:

```php
public function handle()
{
    $dryRun = $this->option('dry-run');
    $execute = $this->option('execute');

    if (!$dryRun && !$execute) {
        $this->error('Pakai --dry-run atau --execute');
        return 1;
    }

    DB::beginTransaction();
    try {
        foreach ($this->getMoveMap() as $entry) {
            $this->moveUser($entry, $dryRun);
        }
        foreach ($this->getNewUserMap() as $entry) {
            $this->createUser($entry, $dryRun);
        }

        if ($dryRun) {
            DB::rollBack();
            $this->info('Dry-run selesai. Tidak ada perubahan.');
        } else {
            DB::commit();
            $this->info('Migrasi selesai.');
        }
    } catch (\Throwable $e) {
        DB::rollBack();
        $this->error("Gagal: {$e->getMessage()}");
        return 1;
    }
    return 0;
}

protected function moveUser(array $entry, bool $dryRun): void
{
    $user = User::where('email', $entry['email'])->first();
    if (!$user) {
        $this->warn("Skip: {$entry['email']} tidak ditemukan");
        return;
    }

    $newDept = Department::where('business_unit_id', $entry['bu_id'])
        ->where('code', $entry['dept_code'])->firstOrFail();
    $newPosition = Position::where('department_id', $newDept->id)
        ->where('code', $entry['position_code'])->firstOrFail();

    $this->line(sprintf(
        '%s: %s -> %s (%s)',
        $user->email,
        $user->primaryDepartment?->code ?? '(none)',
        $newDept->code,
        $newPosition->code
    ));

    if ($dryRun) return;

    // Update primary
    $user->update([
        'primary_department_id' => $newDept->id,
        'primary_position_id'   => $newPosition->id,
    ]);

    // Deactivate UBU lama
    UserBusinessUnit::where('user_id', $user->id)
        ->where('business_unit_id', $entry['bu_id'])
        ->where('is_active', true)
        ->update(['is_active' => false, 'is_primary' => false]);

    // Insert UBU baru
    UserBusinessUnit::create([
        'user_id'         => $user->id,
        'business_unit_id'=> $entry['bu_id'],
        'department_id'   => $newDept->id,
        'position_id'     => $newPosition->id,
        'is_primary'      => true,
        'is_active'       => true,
    ]);
}
```

## Idempotency

Re-run safe via cek state:

```php
$alreadyMigrated = UserBusinessUnit::where('user_id', $user->id)
    ->where('department_id', $newDept->id)
    ->where('is_active', true)
    ->exists();

if ($alreadyMigrated) {
    $this->info("Skip {$user->email}: sudah migrated");
    return;
}
```

## Data yang IKUT user otomatis

Tidak perlu di-migrate manual karena referensi by `user_id` (bukan `department_id`):

- `EmployeeTask.created_by`
- `TaskParticipant.user_id`
- `BackdatePermission`
- `Notification.notifiable_id`
- `PurchaseRequest.requested_by`
- `CashflowProjectionLineItem.created_by/updated_by`
- `ActivityLog.causer_id`

## Data yang TIDAK IKUT user (tetap di dept lama)

Tetap di dept lama untuk preserve history:

- `EmployeeTask.department_id` — task lama tetap di dept lama
- `CashflowProjectionLineItem.department_id` — line item lama tetap
- `NumberSequence.department_id` — sequence dept lama tidak di-rebrand
- `ActivityLog.subject` (Department change events) — log tetap

## Open Question

1. Ada user yang **tidak pindah** tapi dept lamanya kosong total setelah migrasi? Apakah dept itu di-deactivate?
2. User existing yang belum confirm email-nya (terutama Etik = `andri`)?
3. User baru: password default `werkudara88` (sama dengan user lain), force reset di first login?

## Action

PM Agent: **HOLD** sampai PO supply data lengkap.
