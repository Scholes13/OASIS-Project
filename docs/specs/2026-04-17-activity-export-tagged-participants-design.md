# Activity Export Tagged Participants Design

## Context

User melaporkan bahwa ketika seseorang di-tag pada sebuah activity, hasil export Excel tidak menganggap orang tersebut ikut dalam aktivitas itu sehingga data per orang menjadi kurang hitung. Di codebase ini, semantik "tagged user" pada modul Activity saat ini direpresentasikan oleh relasi `participants` pada `employee_tasks` melalui pivot `task_participants`.

Investigasi awal menemukan dua hal:
- kontrak member focus pada layar `Activity Dashboard` dan `Task Management` sudah memperlakukan task sebagai milik seorang member jika `created_by = member` atau task memiliki `participants.user_id = member`,
- workbook export masih creator-centric: sheet Excel hanya menulis identitas pembuat dan tidak mengekspos participant/tagged user sebagai data eksplisit yang bisa dipakai untuk counting lanjutan.

## Problem Statement

Export Activity perlu merepresentasikan participant/tagged user dengan kontrak yang selaras dengan layar Activity. Tanpa itu, task yang secara UI dianggap melibatkan seorang user belum dapat dihitung dengan benar ketika file Excel dipakai untuk analisis atau rekap manual.

## Goals

- Menyamakan kontrak export dengan kontrak participant pada layar Activity.
- Membuat workbook export mengekspose participant/tagged user secara eksplisit.
- Menutup drift antara scope/query export dan scope/query layar pada kasus creator vs participant.
- Menambah coverage regression untuk memastikan user yang hanya participant tetap tercount pada export.

## Non-Goals

- Mengubah semantik permission atau visibilitas task di modul Activity.
- Menambah relasi mention/tag terpisah di luar `participants`.
- Mendesain ulang seluruh format workbook admin dan dashboard di luar kebutuhan participant counting.

## Assumptions

- "Tagged user" pada laporan user sama dengan user yang ada di relasi `participants`.
- Workbook export dipakai downstream untuk rekap manual sehingga participant data perlu berbentuk kolom yang eksplisit, bukan hanya tersirat lewat filter query.
- Backward compatibility pada sheet lama penting, jadi perubahan sebaiknya additive bila memungkinkan.

## Approaches Considered

### Option 1: Query-only fix

Ubah filter export agar selalu memakai `created_by OR participants`, tetapi biarkan struktur sheet tetap hanya menampilkan pembuat.

Pros:
- patch kecil,
- risiko tampilan workbook rendah.

Cons:
- tidak menyelesaikan akar masalah counting per participant di Excel,
- downstream user tetap tidak punya data participant yang eksplisit,
- bug bisa dianggap "masih ada" walau query sudah lebih longgar.

### Option 2: Add participant columns to workbook

Pertahankan satu baris per task, tetapi tambahkan kolom participant/tagged user ke sheet `Detail` dan `Data Mentah`, misalnya:
- `Jumlah Participant`
- `Daftar Participant`
- `Participant IDs`

Pros:
- paling kecil perubahan model data,
- tetap backward-friendly karena satu task tetap satu baris,
- cukup untuk rekap dan audit manual di Excel,
- paling selaras dengan kebutuhan bug report.

Cons:
- agregasi per participant di Excel tetap dilakukan downstream oleh user/pivot table,
- summary workbook belum menyediakan rekap participant siap pakai.

### Option 3: Add participant columns plus a dedicated per-participant sheet

Selain Option 2, tambahkan sheet baru yang menormalisasi satu baris per pasangan `task x participant`, sehingga counting per user bisa langsung dilakukan dari workbook tanpa parsing string participant.

Pros:
- paling lengkap untuk analitik downstream,
- menghilangkan kebutuhan split manual dari kolom string participant,
- paling future-proof untuk audit dan pivot per orang.

Cons:
- perubahan workbook lebih besar,
- perlu keputusan format dan kolom normalized row,
- risiko file menjadi lebih besar untuk department dengan task multi-participant tinggi.

## Recommendation

Ambil **Option 2** untuk patch ini:
- samakan kontrak query export personal dengan kontrak layar `scope=my` (`created_by OR participants`),
- tambahkan participant metadata yang eksplisit pada sheet task-level (`Detail` dan `Data Mentah`),
- pertahankan seluruh kolom lama agar consumer workbook existing tidak pecah.

Alasan:
- ini langsung menyelesaikan bug user bahwa tagged participant belum tercermin di export,
- blast radius tetap kecil karena perubahan terbatas pada pipeline export backend,
- backward compatibility workbook lebih aman dibanding memperkenalkan sheet baru dan kontrak analitik baru dalam patch yang sama.

## Deferred Follow-up

Sheet normalized `Partisipasi` sengaja **ditunda** ke ticket terpisah jika bisnis memang butuh dataset satu-baris-per-task-per-participant. Follow-up itu perlu keputusan eksplisit tentang:
- apakah creator-only task harus menghasilkan row sintetis,
- kontrak kolom normalized,
- kebutuhan downstream consumer workbook.

## Proposed Design

### 1. Export scope contract

`ActivityExportService::getFilteredTasks()` harus mengikuti kontrak yang sama dengan `ActivityInertiaController::buildTaskScopeQuery()`:
- `scope=my` mencakup task saat current user adalah `created_by` atau ada di `participants`,
- `scope=department` tetap mencakup department scope saat ini,
- `member_user_id` pada department export tetap additive dan memakai `created_by OR participants`.

Ini menghilangkan drift antara layar dan export pada task lama atau task yang belum punya owner-participant sinkron.

### 2. Workbook task-level representation

Sheet `Detail` dan `Data Mentah` akan ditambah kolom participant:
- `Jumlah Participant`
- `Daftar Participant`
- `Participant IDs`

`Daftar Participant` memakai nama participant yang sudah diurutkan stabil.
`Participant IDs` memakai daftar ID yang digabung dengan delimiter tetap untuk machine-readable audit.
Kolom creator tetap dipertahankan agar workbook backward-friendly untuk consumer lama.

### 3. Aggregation and formatting helpers

Tambahkan helper kecil di service export atau aggregation layer untuk:
- resolve participant collection dengan eager loading,
- format participant names dan IDs secara stabil.

Tujuannya menjaga `ActivityExportService` tetap terbaca dan menghindari logic string-building tersebar di beberapa sheet builder.

### 4. Deterministic workbook formatting

Format participant harus eksplisit dan stabil:
- `Jumlah Participant`: integer count dari relasi participant yang diexport,
- `Daftar Participant`: nama participant diurutkan ascending berdasarkan `name`, digabung dengan delimiter `, `,
- `Participant IDs`: ID participant diurutkan ascending numerik, digabung dengan delimiter `|`.

Dengan kontrak ini, workbook tetap human-readable sekaligus cukup stabil untuk parsing downstream sederhana.

## Files Likely Touched

- `app/Services/Modules/Activity/ActivityExportService.php`
- `app/Services/Modules/Activity/ActivityReportAggregationService.php` atau helper baru jika perlu
- `tests/Feature/Modules/Activity/ActivityMemberFocusFilterTest.php`
- test feature baru khusus export workbook participant bila coverage existing mulai terlalu padat

## Testing Strategy

### Backend feature coverage

- export `scope=my` memasukkan task ketika user adalah creator walau bukan participant,
- export `scope=my` memasukkan task ketika user hanya participant,
- export `scope=my` tidak menduplikasi task saat user adalah creator sekaligus participant,
- task tanpa participant menghasilkan `Jumlah Participant = 0`, `Daftar Participant = ''`, dan `Participant IDs = ''` secara deterministik,
- schema sheet `Detail` dan sheet `Data Mentah` sama-sama mempertahankan urutan kolom existing lalu menambahkan kolom participant secara additive,
- sheet `Detail` dan sheet `Data Mentah` sama-sama memuat `Jumlah Participant`, `Daftar Participant`, dan `Participant IDs` dengan format stabil,
- department export dengan `member_user_id` tetap memfilter berdasarkan `created_by OR participants`.

### Verification commands

- `php artisan test tests/Feature/Modules/Activity/ActivityMemberFocusFilterTest.php`
- focused export workbook feature test baru bila dipisah
- `vendor/bin/pint --dirty`

## Risks

- menambah kolom workbook bisa memengaruhi consumer Excel downstream yang mengandalkan posisi kolom tetap,
- eager loading participants pada export besar dapat menaikkan memory footprint bila tidak dibatasi rapi.

## Mitigations

- buat perubahan task-level sheet bersifat additive, bukan mengganti kolom lama,
- eager load `participants:id,name` secara eksplisit untuk menghindari N+1 dan payload berlebih,
- tambah test workbook content sehingga format baru terkunci.

## Acceptance Criteria

- user yang hanya di-tag sebagai participant muncul eksplisit dalam workbook export,
- task yang relevan terhadap seorang user tetap konsisten antara layar dan export,
- workbook menampilkan participant/tagged user secara eksplisit pada sheet task-level sehingga user bisnis tidak perlu menebak dari kolom creator,
- regression tests melindungi kasus creator-only, participant-only, dan creator-plus-participant.
