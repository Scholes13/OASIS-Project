# Activity Dashboard Export Report Detail Design

**Date:** 2026-03-31
**Status:** Approved
**Owner:** PM Agent

## Goal

Meningkatkan kualitas report pada `activity/dashboard` dan file export activity agar:
- summary lebih detail dan lebih mudah dipahami user non-analyst,
- export menampilkan `description` dan `summary` yang lebih informatif,
- breakdown kategori ikut menampilkan sub kategori,
- semua metrik penting menampilkan count dan persentase,
- workbook export tetap nyaman dibaca sekaligus aman untuk di-pivot atau diolah ulang.

## Approved UX Direction

User menyetujui arah **Hybrid A**.

Prinsip utamanya:
- kartu `Team Total Hours` atau padanan personal hours **tetap dipertahankan** sebagai bagian inti journey untuk membaca beban kerja dan indikasi overwork,
- kartu KPI utama lain tetap dipertahankan sebisa mungkin agar pola scan pengguna tidak berubah drastis,
- kebutuhan detail report ditambahkan terutama pada panel insight / focus di sisi kanan, bukan dengan menghilangkan fungsi time management yang sudah dipakai,
- dashboard dan export harus menceritakan data yang sama agar user tidak perlu memahami dua versi report yang berbeda.

## Current State Summary

- Dashboard `ActivityDashboard` sudah memiliki:
  - segmented control personal/department,
  - quick filter period,
  - kartu `Team Total Hours`, `Active Projects`, dan `Avg. Efficiency`,
  - panel `Team Activity`,
  - panel `Department Focus`,
  - tombol `Export Report`.
- Saat ini panel `Department Focus` hanya menunjukkan kategori utama dan persentase umum.
- Export workbook activity saat ini:
  - memiliki satu sheet detail task mentah,
  - memiliki satu sheet `Summary` yang masih tipis,
  - belum menampilkan `task_description`,
  - belum memiliki kolom `summary`,
  - belum menampilkan breakdown sub kategori di summary,
  - belum menyusun sheet mentah secara eksplisit untuk workflow pivot-friendly.

## User Journey

### Journey yang dipertahankan

- User tetap masuk ke dashboard untuk membaca kondisi kerja personal atau department secara cepat.
- User tetap memakai `Total Hours` sebagai sinyal awal untuk workload, kapasitas, dan indikasi overwork.
- User tetap dapat masuk ke export dari tombol yang sama tanpa perubahan pola navigasi.

### Journey yang diperbaiki

- Setelah melihat workload lewat `Total Hours`, user bisa langsung membaca panel report yang lebih kaya untuk memahami:
  - kategori dominan,
  - sub kategori dominan,
  - count,
  - persentase kontribusi.
- Saat user menekan export, file yang diunduh sudah membawa detail yang konsisten dengan insight yang dilihat di dashboard.
- Tim analyst atau user lanjutan dapat langsung membuat pivot dari raw sheet tanpa perlu membersihkan format export terlebih dahulu.

## Recommended Approach

### 1. Preserve time-management cards, upgrade report insight panel

- Pertahankan kartu `Team Total Hours` / personal hours pada posisi KPI atas.
- Pertahankan `Active Projects` dan `Avg. Efficiency` kecuali ada constraint layout saat implementasi.
- Upgrade panel kanan `Department Focus` agar menampilkan:
  - kategori,
  - sub kategori,
  - count,
  - share terhadap total report.
- Tambahkan visual distribution sederhana yang membantu scan cepat tanpa menggantikan tabel detail.

### 2. Align dashboard summary and workbook summary

- Dashboard dan workbook harus memakai sumber agregasi yang konsisten untuk:
  - status counts,
  - status percentages,
  - category distribution,
  - subcategory distribution.
- Jika dashboard menyebut kategori atau sub kategori teratas, workbook summary harus menunjukkan angka yang sama.
- Hindari logika agregasi terpisah yang bisa menghasilkan mismatch antar channel report.

### 3. Upgrade workbook into a readable + pivotable report pack

- Workbook export dibagi ke beberapa sheet dengan fungsi berbeda:
  - `Detail` / `Activity Report`: daftar aktivitas lengkap untuk pembacaan harian,
  - `Summary`: KPI umum, status mix, top category, top subcategory,
  - `Category Breakdown`: category + subcategory dengan count dan persen,
  - `Raw Data`: data flat satu baris per aktivitas untuk pivot lanjutan.
- `Raw Data` menjadi sumber paling aman untuk pivot dan olah ulang.
- Sheet summary boleh lebih presentational, tetapi raw sheet harus tetap datar dan stabil.

## Workbook Design

### Sheet 1: Detail

Tujuan:
- mudah dibaca supervisor atau user umum.

Nama sheet final:
- `Detail`

Kolom final:
- `No`
- `Tanggal`
- `Judul Aktivitas`
- `Deskripsi`
- `Ringkasan Aktivitas`
- `Kategori`
- `Sub Kategori`
- `Status`
- `Prioritas`
- `Pembuat`
- `Departemen`
- `Jatuh Tempo`
- `Mulai`
- `Selesai`
- `Durasi (menit)`
- `Catatan`

Aturan:
- `Description` mengambil `task_description` asli.
- `Summary` adalah ringkasan pendek yang lebih mudah dibaca untuk kebutuhan report.
- Jika summary tidak disimpan di database, summary dapat dibentuk secara deterministik dari data task yang sudah ada, bukan input manual baru.

### Sheet 2: Summary

Tujuan:
- mudah dipahami manajemen dalam satu layar.

Nama sheet final:
- `Ringkasan`

Isi minimum:
- generated timestamp,
- total activities,
- completed count + percent,
- in progress count + percent,
- planned count + percent,
- cancelled count + percent,
- top category,
- top subcategory,
- completion rate.

Aturan:
- `completed %`, `in progress %`, `planned %`, dan `cancelled %` dihitung dari `status_count / total_activities`.
- `completion rate` memakai rumus `completed_count / total_activities`.
- implementasi pertama **tidak mewajibkan chart XLSX**; tabel angka menjadi sumber utama yang harus ada.

### Sheet 3: Category Breakdown

Tujuan:
- memberi penjelasan yang actionable atas panel fokus.

Nama sheet final:
- `Breakdown Kategori`

Kolom final:
- `Category`
- `Subcategory`
- `Count`
- `% of Category`
- `% of Report`

Aturan:
- semua persentase dihitung eksplisit, bukan hanya count,
- sub kategori ditampilkan dalam konteks category induknya,
- `% of Category` dihitung dari `subcategory_count / total_count_dalam_category_induk`,
- `% of Report` dihitung dari `subcategory_count / total_activities`,
- urutkan dari kontribusi terbesar ke terkecil agar mudah discan.

### Sheet 4: Raw Data

Tujuan:
- menjadi sumber pivotable dan bisa diolah ulang.

Nama sheet final:
- `Data Mentah`

Aturan pivotability:
- satu header row saja,
- satu activity = satu row,
- tanpa merge cell di area data,
- tanpa subtotal yang disisipkan di tengah,
- tipe kolom stabil dan eksplisit,
- gunakan label kolom yang mudah dipahami user Excel.

Kolom final:
- `id_tugas`
- `tanggal_tugas`
- `judul_aktivitas`
- `deskripsi_aktivitas`
- `ringkasan_aktivitas`
- `kategori`
- `sub_kategori`
- `status`
- `prioritas`
- `nama_pembuat`
- `nama_departemen`
- `jatuh_tempo`
- `waktu_mulai`
- `waktu_selesai`
- `durasi_menit`
- `catatan`

Aturan bahasa:
- workbook export memakai label sheet dan header kolom berbahasa Indonesia secara konsisten,
- dashboard web mempertahankan label UI yang sudah ada saat ini agar perubahan fokus pada report detail, bukan relabeling halaman.

## Dashboard Design

### KPI area

- `Team Total Hours` tetap dipertahankan.
- Bila data durasi tidak tersedia penuh, fallback estimation saat ini tetap boleh dipakai dengan labeling yang jujur terhadap sifat estimasinya bila perlu.
- Tujuan KPI area tetap untuk time management, bukan diubah total menjadi report-only cards.

### Insight / Focus area

Komponen insight memakai perilaku yang sama di dua mode:
- mode personal memakai label `My Focus`,
- mode department memakai label `Department Focus`.

Isi panel di kedua mode:
  - top category,
  - top subcategory,
  - count,
  - percent,
  - compact distribution bar.

Definisi percent di dashboard:
- percent yang tampil di panel focus memakai `% of Report`,
- `% of Report` dihitung dari `item_count / total_activities_dalam_scope_mode_aktif`,
- status cards atau summary cards memakai denominator total aktivitas di scope aktif yang sama.

Batas perilaku per mode:
- mode personal menghitung hanya task dalam scope export `my`,
- mode department menghitung hanya task dalam scope export `department`,
- label boleh berbeda, tetapi struktur data dan denominator harus konsisten antar mode.

- Bila ruang tidak cukup, detail sub kategori ditampilkan terbatas pada beberapa baris teratas, sementara detail lengkap tersedia di export.

### Export action

- Tombol `Export Report` tetap berada di header action bar.
- Perilaku download browser tetap dipertahankan seperti implementasi sekarang.
- Query filter yang diteruskan pada implementasi ini dikunci ke:
  - `scope`,
  - `date_from`,
  - `date_to`,
  - `status`,
  - `activity_type_id`.
- Tidak ada filter export baru di luar daftar di atas pada scope implementasi pertama ini.

## Data and Contract Design

### Backend aggregation

- Tambahkan helper / service-level aggregation yang bisa dipakai ulang untuk:
  - dashboard visual distribution,
  - workbook summary,
  - workbook category breakdown.
- Agregasi harus menghitung:
  - total task,
  - per-status count,
  - per-status percentage,
  - per-category count,
  - per-subcategory count,
  - share within category,
  - share across total report.

### Export query

- Export task query perlu eager-load tetap aman:
  - `activityType`,
  - `subActivity`,
  - `creator`,
  - `department`.
- `task_description` harus ikut dipakai untuk sheet detail dan raw.
- Otorisasi export tetap mengikuti perilaku saat ini:
  - `scope=my` berarti current active business unit + current department + task yang melibatkan user saat ini,
  - `scope=department` berarti current active business unit + current department aktif tanpa memperluas akses lintas department,
  - tidak ada pelebaran akses export di perubahan ini.

### Summary generation

- `Summary` report tidak menambah field input baru ke form task.
- `Ringkasan Aktivitas` dibentuk secara deterministik dari data task yang sudah ada dengan template final:
  - `{Judul Aktivitas} | {Status Label} | {Kategori} > {Sub Kategori} | Due {Tanggal Jatuh Tempo}`
- Aturan fallback:
  - jika `Sub Kategori` kosong, bagian category menjadi `{Kategori}`,
  - jika `Due Date` kosong, bagian `| Due ...` dihilangkan,
  - jika `Kategori` kosong, gunakan `Tanpa Kategori`,
  - jika `Sub Kategori` kosong dan `Kategori` kosong, jangan tampilkan separator `>`,
  - jika `Judul Aktivitas` kosong, gunakan `Aktivitas tanpa judul`.
- Panjang `Ringkasan Aktivitas` dipotong maksimal 120 karakter agar tetap nyaman dibaca di Excel.
- Rumus pembentukan summary harus deterministic dan testable.

### Ownership boundaries

- Backend owns:
  - export query scope,
  - aggregation logic,
  - workbook structure and summary contract,
  - deterministic summary generation.
- Frontend owns:
  - dashboard focus rendering,
  - preservation of hours/time-management cards,
  - empty state rendering,
  - download trigger behavior.
- Reviewer owns:
  - contract drift checks,
  - standards validation,
  - verification evidence review.

## Error Handling

- Export tetap harus berhasil walau beberapa task tidak punya `task_description`, `subActivity`, atau `duration_minutes`.
- Nilai kosong ditampilkan dengan fallback aman seperti `-`, tanpa melempar error ke user.
- Implementasi pertama tidak mewajibkan chart di XLSX untuk menghindari risiko workbook rusak atau instabilitas library.
- Dashboard tidak boleh gagal render hanya karena distribution data kosong; tampilkan empty state yang konsisten.
- Jika hasil filter export kosong:
  - workbook tetap diunduh,
  - semua sheet tetap dibuat,
  - sheet detail dan raw hanya berisi header tanpa baris data,
  - sheet summary menampilkan total `0`,
  - sheet category breakdown tidak memiliki baris data selain header.
- Jika generation workbook gagal sebelum stream dimulai:
  - log exception di backend,
  - kembalikan response error generik tanpa detail internal,
  - tidak menambah workflow retry, queue, atau asynchronous export pada scope perubahan ini.
- Untuk implementasi pertama, tidak ada optimasi khusus large export selain query dan sheet design yang tetap sederhana; jika terjadi kegagalan operasional, perlakuannya mengikuti generic export failure di atas.

## Testing Strategy

### Backend tests

- coverage untuk workbook detail yang kini memuat `Description` dan `Summary`,
- coverage untuk summary workbook yang memuat count dan percent per status,
- coverage untuk breakdown category + subcategory,
- coverage untuk raw sheet yang tetap flat dan pivotable,
- coverage untuk konsistensi filter scope `my` vs `department`.

### Frontend tests

- coverage bahwa `Team Total Hours` tetap tampil pada dashboard,
- coverage bahwa panel focus personal dan department memakai denominator yang benar untuk count dan percent,
- coverage bahwa export action tetap memakai browser download flow,
- coverage terhadap empty state distribution jika tidak ada data.

### Verification

- focused PHPUnit tests untuk export workbook,
- focused Vitest coverage untuk dashboard report rendering bila kontrak frontend berubah,
- `vendor/bin/pint --dirty`,
- `npm exec tsc --noEmit --pretty false`.

## Risks and Mitigations

- Risk: kartu report baru menggeser fungsi time management yang sudah dipakai user.
  - Mitigation: pertahankan `Team Total Hours` di KPI area sesuai approval Hybrid A.
- Risk: dashboard dan export menghasilkan angka berbeda.
  - Mitigation: satukan jalur agregasi atau helper ringkasan.
- Risk: workbook menjadi cantik tetapi tidak enak dipivot.
  - Mitigation: sediakan raw sheet flat yang diperlakukan sebagai source of truth untuk olah ulang.
- Risk: chart di XLSX rawan atau tidak stabil.
  - Mitigation: chart bersifat bonus, sedangkan tabel count + percent adalah requirement utama.
- Risk: fallback duration estimation bisa terbaca sebagai angka pasti.
  - Mitigation: pertahankan perilaku saat ini atau beri labeling yang jelas jika estimasi dipakai.

## Acceptance Criteria

- Dashboard tetap mempertahankan `Team Total Hours` / hours summary sebagai bagian inti journey.
- Panel report di dashboard personal dan department menunjukkan category dan subcategory, tidak hanya category utama.
- Dashboard dan workbook menunjukkan count serta persentase untuk metrik utama dengan denominator yang konsisten terhadap total aktivitas dalam scope aktif.
- Workbook export menambahkan `Deskripsi` dan `Ringkasan Aktivitas`.
- Workbook export memiliki sheet raw yang flat dan aman untuk pivot.
- Export tetap bisa diunduh dari flow sekarang tanpa perubahan navigasi user.
