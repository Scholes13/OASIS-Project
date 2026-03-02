# PRD - Cashflow Projection (MVP)

**Status:** Draft v0.7  
**Date:** 2026-02-18  
**Product Owner:** User (doc owner)  
**Tech Owner:** Engineering Team

## 1) Latar Belakang

Saat ini belum ada modul khusus untuk perencanaan cashflow bulanan per Business Unit (BU). Tim membutuhkan satu sumber kebenaran untuk menyusun proyeksi kas 12 bulan ke depan berbasis input departemen, lalu melihat rekap BU dalam dashboard dan export Excel untuk kebutuhan rapat budgeting.

## 2) Tujuan Produk

1. Menyediakan proses input proyeksi cashflow bulanan yang terstruktur per departemen dalam setiap BU.
2. Menyediakan dashboard ringkasan proyeksi per BU untuk horizon 12 bulan.
3. Menyediakan export Excel untuk kebutuhan review dan distribusi laporan.

## 3) Non-Tujuan (MVP)

1. Belum ada approval berjenjang.
2. Belum integrasi auto dari modul lain (input masih manual).
3. Belum ada machine learning / advanced forecasting.

## 4) Ruang Lingkup MVP

### In Scope
- Periode perencanaan berbasis cycle (tahun/periode) per BU.
- Input detail template departemen (line item pemasukan/pengeluaran) per bulan.
- Daftar departemen diambil dari master data departemen milik BU aktif.
- Template khusus untuk `ACC`, `HR`, `CFC`, dan template standar untuk departemen lain.
- Input `Cash On Hand` oleh Finance/CFC sebagai komponen saldo awal.
- Input sumber cashflow pemasukan: estimasi penerimaan utang, estimasi revenue upcoming event (sementara manual, target API), suntikan modal, dan lain-lain.
- Warning jika saldo proyeksi di bawah batas minimum global `200.000.000` (berlaku untuk semua BU).
- Status cycle: `draft` dan `published`.
- Dashboard ringkasan proyeksi BU + filter tahun/departemen.
- Export Excel untuk ringkasan dan detail.

### Out of Scope
- Approval workflow multi-level.
- Integrasi sumber data otomatis dari modul external/internal lain.
- Simulasi what-if lanjutan (best/base/worst).

## 5) Pengguna dan Akses (MVP)

- **Head Department BU**: membuat dan mengubah input departemen dalam BU yang diizinkan.
- **Finance/CFC BU**: mengisi `Cash On Hand` dan komponen pemasukan khusus finance.
- **User lain**: tidak melihat menu modul dan tidak memiliki akses ke halaman Cashflow Projection.

Catatan: model akses mengikuti constraint Business Unit yang sudah ada di aplikasi.

### Aturan Visibilitas Sidebar

- Menu `Cashflow Projection` hanya tampil di sidebar untuk:
  - user dengan posisi `Head Department` pada BU aktif, atau
  - user finance pada BU aktif (contoh WNS: departemen `CFC`).
- User selain dua kelompok di atas tidak melihat menu sama sekali agar tidak mengganggu workflow modul lain.
- Akses langsung via URL tetap dibatasi (authorization server-side), respon `403` untuk user tanpa hak.

## 6) Alur Proses Utama

1. User memilih BU dan tahun cycle.
2. Sistem membuat/membuka `Projection Cycle` status `draft`.
3. Sistem memuat daftar departemen dari BU aktif dan menentukan jenis template (ACC/HR/CFC/standar).
4. Head Department mengisi line item detail bulanan pada template departemennya.
5. Finance/CFC mengisi `Cash On Hand` serta komponen pemasukan finance.
6. Sistem menghitung subtotal pemasukan, subtotal pengeluaran, net cashflow, dan saldo akhir proyeksi per bulan.
7. Sistem menampilkan warning jika saldo di bawah nilai minimum.
8. User publish cycle saat data siap.
9. Data tampil di dashboard dan dapat diexport ke Excel.

## 7) Kebutuhan Fungsional

1. Create/Edit/Delete line item proyeksi departemen per bulan.
2. Validasi nilai numerik, bulan, dan scope departemen sesuai BU aktif.
3. Sistem harus mendukung template input berbeda:
   - Template khusus: `ACC`, `HR`, `CFC`
   - Template standar: departemen selain `ACC/HR/CFC`
4. Input `Cash On Hand` wajib untuk perhitungan saldo bulanan.
5. Input cashflow pemasukan minimal mencakup:
   - Estimasi penerimaan utang
   - Estimasi revenue upcoming event (sementara manual)
   - Estimasi suntikan modal
   - Lain-lain
6. Perhitungan otomatis total pemasukan, pengeluaran, net cashflow, dan saldo akhir proyeksi.
7. Tampilkan warning jika saldo akhir di bawah minimum balance global `200.000.000`.
8. Dashboard ringkasan per BU untuk 12 bulan ke depan.
9. Export Excel untuk:
   - Ringkasan BU per bulan
   - Detail per departemen
10. Kemampuan publish snapshot angka final cycle tanpa approval formal.

### Rumus Perhitungan MVP

- `Total Pengeluaran` = penjumlahan semua line item pengeluaran (ACC + HR + CFC + departemen lain)
- `Total Pemasukan` = penerimaan utang + revenue upcoming event + suntikan modal + lain-lain
- `Net Cashflow` = `Total Pemasukan - Total Pengeluaran`
- `Saldo Akhir Proyeksi` = `Cash On Hand + Net Cashflow`
- Jika `Saldo Akhir Proyeksi < Minimum Balance`, tampilkan warning

### Mapping Baseline Excel (IN-OUT-DEPT-ACTION)

- Konvensi nama sheet: `<FLOW>-<DEPT>-<ACTION>`.
- `IN` = menambah saldo kas, `OUT` = mengurangi saldo kas.
- `DEPT` diambil dari departemen aktif dalam BU yang dipilih di sistem.
- Untuk BU `Werkudara Nirwana Sakti (WNS)`, departemen aktif saat ini: `ACC`, `ACS`, `BAS`, `BID`, `CFC`, `GA`, `HR`, `PD`, `SO`, `SS`, `TEP`.
- Definisi `Other Dept` = semua departemen aktif BU terpilih selain `ACC`, `HR`, dan `CFC`.
- `ACTION` adalah jenis aktivitas keuangan yang dilakukan departemen.

**Pemetaan IN (baseline):**
- `IN-ACC-Piutang & Revenue` -> estimasi penerimaan utang + estimasi revenue upcoming event (sementara manual, target API).
- `IN-CFC-Penerimaan PengemPinj` -> penerimaan pengembalian pinjaman.
- `IN-CFC-Suntikan Modal` -> suntikan modal owner.

**Pemetaan OUT (baseline):**
- `OUT-ACC-PAJAK` -> pembayaran pajak.
- `OUT-HR-Gaji & Benefit` -> biaya gaji dan benefit.
- `OUT-CFC-Corporate Expenses` -> biaya korporat CFC.
- `OUT-<DEPT>-OPS` -> biaya operasional departemen (template standar departemen).
- `OUT-CFC-Bunga & Angsuran` -> bunga dan angsuran.
- `OUT-TEP-Cost Of Revenue` -> cost of revenue upcoming event.
- `OUT-CFC-Hutang Usaha` -> pembayaran hutang usaha.
- `OUT-HR-Pemberian Pinjaman` -> pemberian pinjaman.
- `OUT-CFC-Pengembalian Suntikan Modal` -> pengembalian suntikan modal owner.

### Catatan Adaptasi dari Excel ke Aplikasi MVP

- Baseline Excel saat ini dominan harian (per tanggal), sedangkan MVP aplikasi menggunakan agregasi bulanan (12 bulan).
- Sistem harus mendukung import/mapping dari pola harian ke nilai bulanan agar hasil dashboard tetap konsisten dengan struktur Excel lama.
- Formula saldo tetap mengikuti prinsip Excel: saldo awal (`Cash On Hand`) + total penerimaan - total pengeluaran.
- Label `RESERVASI` dan `TAKSHAKA` pada file Excel diperlakukan sebagai referensi legacy; sistem aplikasi mengikuti master BU/departemen di database.
- `Takshaka` adalah business unit tersendiri (`TEE`), bukan departemen di WNS.
- Jika kebutuhan `Reservasi` diarahkan ke `Corporate Travel`, maka data tersebut mengikuti BU `MRP` dengan departemen `CT` (bukan `Other Dept` di WNS).

### Matriks Input MVP per Departemen (WNS)

**A. Input spesial ACC**
- `IN-ACC-Piutang & Revenue`
  - Field minimal: `deskripsi`, `nominal`, `tanggal_estimasi`, `bulan_target`, `sumber` (`manual`/`api_soon`), `catatan`.
- `OUT-ACC-PAJAK`
  - Field minimal: `jenis_pajak`, `nominal`, `jatuh_tempo`, `bulan_target`, `catatan`.
- `OUT-ACC-OPS` (opsional jika dipakai)
  - Field minimal: `keterangan`, `nominal`, `tanggal`, `bulan_target`, `catatan`.

**B. Input spesial HR**
- `OUT-HR-Gaji & Benefit`
  - Field minimal: `keterangan`, `nominal`, `tanggal`, `bulan_target`, `catatan`.
- `OUT-HR-Pemberian Pinjaman`
  - Field minimal: `deskripsi_pinjaman`, `nominal`, `tanggal`, `bulan_target`, `catatan`.
- `OUT-HR-OPS`
  - Field minimal: `keterangan`, `nominal`, `tanggal`, `bulan_target`, `catatan`.

**C. Input spesial Finance/CFC**
- `Cash On Hand` (per bulan)
  - Field minimal: `bulan`, `cash_on_hand`.
- `IN-CFC-Penerimaan Pengembalian Pinjaman`
  - Field minimal: `deskripsi`, `nominal`, `tanggal_estimasi`, `bulan_target`, `catatan`.
- `IN-CFC-Suntikan Modal`
  - Field minimal: `sumber_owner`, `nominal`, `tanggal_estimasi`, `bulan_target`, `catatan`.
- `OUT-CFC-Corporate Expenses`
  - Field minimal: `keterangan`, `nominal`, `jatuh_tempo`, `tanggal_bayar_rencana`, `bulan_target`, `catatan`.
- `OUT-CFC-Bunga & Angsuran`
  - Field minimal: `nama_pos`, `bunga`, `pokok`, `nomor_kontrak`, `rekening_bank`, `periode_pembayaran`, `nominal_pembayaran`, `tanggal`, `bulan_target`, `catatan`.
- `OUT-CFC-Hutang Usaha`
  - Field minimal: `vendor/deskripsi`, `nominal`, `jatuh_tempo`, `bulan_target`, `catatan`.
- `OUT-CFC-Pengembalian Suntikan Modal`
  - Field minimal: `deskripsi`, `nominal`, `tanggal`, `bulan_target`, `catatan`.

**D. Input template Other Dept (standar)**
- Berlaku untuk semua dept aktif WNS selain `ACC`, `HR`, `CFC` (`ACS`, `BAS`, `BID`, `GA`, `PD`, `SO`, `SS`, `TEP`).
- `OUT-<DEPT>-OPS`
  - Field minimal: `keterangan`, `nominal`, `tanggal`, `bulan_target`, `catatan`.

### Aturan Tanggal Input dan Agregasi Dashboard

- Setiap baris `IN/OUT` wajib punya `tanggal_transaksi_rencana` (tanggal uang diperkirakan benar-benar masuk/keluar kas).
- Untuk `IN`, tanggal yang dipakai adalah tanggal estimasi kas diterima (bukan tanggal invoice dibuat).
- Untuk `OUT`, tanggal yang dipakai adalah tanggal rencana bayar/eksekusi kas keluar (bukan hanya tanggal jatuh tempo dokumen).
- Field `jatuh_tempo` tetap boleh disimpan sebagai referensi, tetapi perhitungan dashboard memakai `tanggal_transaksi_rencana`.
- `bulan_target` diturunkan otomatis dari `tanggal_transaksi_rencana` (timezone lokal BU), bukan diinput manual.
- Jika tanggal pasti belum diketahui, user wajib isi minimal tanggal target konservatif (default rekomendasi: akhir bulan) dan tandai sebagai estimasi.
- Dashboard menampilkan:
  - `plus/minus harian` berdasarkan tanggal transaksi.
  - `plus/minus bulanan` hasil agregasi semua tanggal dalam bulan tersebut.
  - `saldo akhir bulanan` untuk evaluasi warning minimum balance global.

## 8) Kebutuhan Non-Fungsional

1. **Keamanan akses:** user hanya bisa akses data sesuai BU/departemen yang berhak.
2. **Audit minimal:** simpan jejak waktu dan pengguna saat create/update/publish.
3. **Kinerja:** dashboard BU 12 bulan harus responsif untuk penggunaan harian.
4. **Reliabilitas:** publish tidak boleh merusak data draft aktif.

## 9) Desain Data Tingkat Tinggi

- `projection_cycles`
  - business_unit_id, year, status, published_at, published_by
- `projection_department_sheets`
  - projection_cycle_id, department_id, template_type(acc/hr/cfc/standard)
- `projection_line_items`
  - sheet_id, category, flow_type(in/out), action_code, amount, transaction_date, due_date, is_estimated_date, notes, source_type(manual/api)
- `projection_finance_inputs`
  - projection_cycle_id, month, cash_on_hand, receivable_estimate, upcoming_event_revenue_estimate, capital_injection_estimate, other_income
- `projection_action_templates`
  - code, flow_type, default_department_code, action_name, is_special_template
- `projection_settings`
  - minimum_balance_global (default `200000000`)
- `projection_snapshots`
  - projection_cycle_id, payload_json, version, created_by

Struktur ini disiapkan agar nantinya mudah ditambah approval dan integrasi auto baseline.

## 10) Dashboard dan Reporting

- View utama: 12 bulan, cash on hand, total income, total expense, net cashflow, saldo akhir.
- Breakdown by department dan kategori.
- Highlight warning untuk bulan dengan saldo di bawah minimum balance.
- Export Excel mengikuti filter aktif (BU, tahun, departemen).

## 11) Strategy Branch, Staging, Production

- `v4-beta` sebagai branch staging.
- `main` sebagai branch production.
- `feature/*` branch dari `v4-beta`, merge via PR ke `v4-beta`.
- Promote ke production melalui PR `v4-beta -> main`.

## 12) Success Metrics (MVP)

1. Minimal 1 cycle aktif per BU untuk periode berjalan.
2. Pengisian data departemen selesai sebelum tenggat budgeting bulanan.
3. Laporan Excel dipakai sebagai bahan rapat tanpa olah manual besar.

## 13) Risiko dan Pertanyaan Terbuka

1. Definisi detail field input khusus ACC, HR, dan CFC per bulan perlu finalisasi bisnis.
2. Estimasi revenue upcoming event mulai kapan beralih dari manual ke API?
3. Perlu lock period setelah publish atau tetap boleh edit dengan version baru?

---

Dokumen ini adalah baseline PRD awal dan dimiliki Product Owner untuk refinement berikutnya.
