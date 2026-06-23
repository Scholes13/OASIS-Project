# PRD - Stock Request GA Review Before Purchasing

**Status:** Draft v0.1  
**Date:** 2026-06-23  
**Product Owner:** User  
**Module:** Purchasing / Stock Request

## 1) Latar Belakang

Flow Stock Request saat ini langsung mengikuti approval requester/approver lalu masuk ke proses Purchasing setelah approved. Kebutuhan baru: setiap Stock Request dari department harus melewati review GA Admin terlebih dahulu sebelum masuk Dashboard Purchasing.

GA Admin bertugas cek dan memutuskan item mana yang sudah tersedia di gudang dan item mana yang perlu pengadaan. Setelah GA Admin approve review, Stock Request tetap masuk Dashboard Purchasing dengan semua item tetap terlihat. Item yang tersedia di gudang tampil dengan efek coret agar Purchasing tahu item tersebut tidak perlu diproses pengadaan.

## 2) Tujuan Produk

1. Menambahkan tahap GA Admin Review setelah User Department membuat Stock Request.
2. Memastikan GA Admin bisa approve atau reject Stock Request berdasarkan hasil review stok gudang.
3. Memastikan GA Admin bisa memberi hasil review per item: tersedia di gudang atau perlu pengadaan.
4. Memastikan Dashboard Purchasing menerima Stock Request yang sudah di-ACC GA beserta hasil review item.
5. Memastikan item yang tersedia di gudang tetap tampil di Purchasing, tetapi dicoret dan tidak diproses pengadaan.

## 3) Non-Tujuan

1. Tidak membuat modul inventory/gudang penuh pada fase ini.
2. Tidak melakukan auto deduction stok gudang.
3. Tidak mengubah proses Purchase Request detail di luar kebutuhan tampilan hasil review ST.
4. Tidak membuat multi-level GA approval pada fase awal.

## 4) Pengguna dan Peran

### User Department / Leader / HOD

- Membuat Stock Request.
- Melihat status review GA.
- Melakukan revisi/resubmit jika Stock Request ditolak GA.

### GA Admin Department

- Menerima Stock Request dari department terkait.
- Review item Stock Request.
- Menentukan status per item:
  - `warehouse_stock`: barang tersedia di gudang.
  - `need_procurement`: barang perlu pengadaan.
- Memberi catatan review per item atau catatan umum.
- Approve atau reject Stock Request.

### Superadmin

- Mengatur department capability per Business Unit.
- Menentukan department mana yang menjadi `ga_stock_review`.
- Menentukan department mana yang menjadi `purchasing`.
- Melihat audit perubahan konfigurasi capability.

### Purchasing

- Melihat Stock Request yang sudah di-ACC GA.
- Melihat semua item hasil review GA.
- Memproses hanya item `need_procurement`.
- Melihat item `warehouse_stock` dalam kondisi dicoret/disabled visual.

## 5) Department Capability per Business Unit

Department Capability adalah konfigurasi dari Superadmin untuk menandai fungsi department dalam setiap Business Unit. Sistem tidak boleh bergantung pada nama department seperti `GA`, `General Affair`, atau `Purchasing`.

Tujuan Department Capability:

1. Menentukan department mana yang bertugas sebagai GA Stock Review dalam BU.
2. Menentukan department mana yang bertugas sebagai Purchasing dalam BU.
3. Menghindari hardcode nama department.
4. Mendukung struktur department yang berbeda antar BU.
5. Menjadi dasar routing task, notifikasi, authorization, dan audit.

Capability target:

- `ga_stock_review`: department penerima review ST sebelum Purchasing.
- `purchasing`: department penerima ST setelah GA ACC.

Aturan target:

1. Superadmin mengatur capability per BU dari halaman master/configuration.
2. Setiap BU wajib punya minimal satu department aktif dengan capability `ga_stock_review` untuk flow ST ini.
3. Setiap BU wajib punya minimal satu department aktif dengan capability `purchasing` agar ST bisa masuk Dashboard Purchasing.
4. Satu department boleh memiliki lebih dari satu capability jika struktur BU memang begitu.
5. Nama department bebas dan tidak menjadi logic sistem.
6. Sistem tidak boleh mengirim ST ke GA Review jika capability `ga_stock_review` belum valid.
7. Sistem tidak boleh mengirim ST ke Dashboard Purchasing jika capability `purchasing` belum valid.
8. Semua perubahan capability wajib tercatat audit log.

Contoh konfigurasi:

```text
BU: WNS
Department GA         -> capability: ga_stock_review
Department Purchasing -> capability: purchasing

BU: TEE
Department General Affair -> capability: ga_stock_review
Department Procurement    -> capability: purchasing
```

Routing saat submit ST:

```text
ST dibuat dari BU aktif
-> sistem cari department capability `ga_stock_review` di BU tersebut
-> sistem assign task review ke user eligible dalam department tersebut
-> jika tidak ada capability aktif, submit diblokir dengan error konfigurasi
```

Routing setelah GA ACC:

```text
GA ACC ST
-> sistem cari department capability `purchasing` di BU tersebut
-> sistem kirim ST ke Dashboard Purchasing
-> jika tidak ada capability aktif, ACC diblokir atau masuk error queue konfigurasi
```

Data minimal Department Capability:

- `business_unit_id`.
- `department_id`.
- `capability`.
- `is_active`.
- `created_by`.
- `updated_by`.
- `created_at`.
- `updated_at`.

Audit minimal:

- action: create/update/delete/activate/deactivate.
- before value.
- after value.
- actor Superadmin.
- timestamp.
- reason/note opsional.

## 6) Flow Utama

```text
User Department (Leader/HOD)
        |
        v
Create Stock Request
        |
        v
GA Admin Review per Department
        |
        v
GA cek item:
- barang tersedia gudang
- barang perlu pengadaan
        |
        v
GA Decision
        |
        +--> Reject -> kembali ke requester untuk revisi/resubmit
        |
        +--> ACC -> masuk Dashboard Purchasing dengan hasil review
```

## 7) Flow Detail

### 7.1 Create Stock Request

1. User Department membuat Stock Request seperti flow existing.
2. User mengisi item, qty, kebutuhan, dan dokumen pendukung bila ada.
3. Setelah submit, status Stock Request masuk tahap `ga_review`.
4. Sistem mengirim/menampilkan task review ke GA Admin department terkait.

### 7.2 GA Admin Review

1. GA Admin membuka daftar Stock Request yang perlu direview.
2. GA Admin membuka detail Stock Request.
3. GA Admin review setiap item.
4. GA Admin memilih hasil review per item:
   - `warehouse_stock`: barang ada di gudang.
   - `need_procurement`: barang harus dibeli/diproses Purchasing.
5. GA Admin dapat mengisi catatan item.
6. GA Admin dapat mengisi catatan umum review.
7. GA Admin memilih decision:
   - ACC.
   - Reject.

### 7.3 Reject oleh GA

1. GA Admin wajib mengisi alasan reject.
2. Status Stock Request menjadi `ga_rejected`.
3. Requester menerima status reject dan alasan.
4. Requester dapat revisi/resubmit.
5. Setelah resubmit, Stock Request kembali ke tahap `ga_review`.

### 7.4 ACC oleh GA

1. GA Admin wajib menyelesaikan review semua item.
2. Status Stock Request menjadi `ready_for_purchasing` atau status existing yang dipakai untuk masuk Dashboard Purchasing.
3. Sistem membuat/menampilkan task di Dashboard Purchasing.
4. Semua item tetap dikirim ke Purchasing beserta hasil review GA.

### 7.5 Dashboard Purchasing

1. Purchasing melihat Stock Request yang sudah ACC GA.
2. Semua item tetap tampil.
3. Item `warehouse_stock` tampil dicoret dan tidak menjadi target proses pengadaan.
4. Item `need_procurement` tampil normal dan dapat diproses Purchasing.

Contoh:

```text
ST-001
Item A = warehouse_stock
Item B = need_procurement
```

Tampilan Purchasing:

```text
ST-001
Item A  dicoret  ada stock gudang
Item B  normal   perlu pengadaan
```

## 8) Kebutuhan Fungsional

1. Sistem harus menambahkan tahap GA Review sebelum Stock Request masuk Dashboard Purchasing.
2. Sistem harus menggunakan Department Capability per BU untuk menentukan GA Review dan Purchasing.
3. Sistem harus menyediakan konfigurasi Department Capability untuk Superadmin.
4. Sistem harus menyimpan audit log setiap perubahan Department Capability.
5. Sistem harus membatasi GA Admin hanya melihat Stock Request department/BU yang menjadi area tugasnya.
3. Sistem harus menyediakan halaman/list GA Review Stock Request.
4. Sistem harus menyediakan detail review untuk GA Admin.
5. Sistem harus menyediakan field hasil review per item:
   - `warehouse_stock`.
   - `need_procurement`.
6. Sistem harus menyediakan catatan GA per item.
7. Sistem harus menyediakan catatan umum GA Review.
8. Sistem harus menyediakan decision ACC/Reject untuk GA Admin.
9. Sistem harus mewajibkan semua item punya hasil review sebelum ACC.
10. Sistem harus mewajibkan alasan saat Reject.
11. Sistem harus mengembalikan Stock Request ke requester saat Reject.
12. Sistem harus mengizinkan requester revisi/resubmit setelah Reject.
13. Sistem harus memasukkan Stock Request ke Dashboard Purchasing hanya setelah ACC GA.
14. Sistem harus menampilkan semua item di Dashboard Purchasing.
15. Sistem harus memberi efek coret untuk item `warehouse_stock` di Dashboard Purchasing.
16. Sistem harus mencegah item `warehouse_stock` diproses sebagai item pengadaan.
17. Sistem harus menyimpan audit trail review GA.

## 9) Status yang Dibutuhkan

Status Stock Request target:

- `draft`: masih draft requester.
- `ga_review`: menunggu review GA Admin.
- `ga_rejected`: ditolak GA, menunggu revisi requester.
- `ready_for_purchasing`: sudah ACC GA dan masuk Dashboard Purchasing.
- Status existing setelah Purchasing tetap mengikuti flow existing.

Status item review target:

- `pending_review`: belum direview GA.
- `warehouse_stock`: tersedia di gudang.
- `need_procurement`: perlu pengadaan.

## 10) Data Minimal

### Stock Request

- `ga_review_status` atau mapping ke `status` existing.
- `ga_reviewed_by`.
- `ga_reviewed_at`.
- `ga_review_note`.
- `ga_rejected_reason`.

### Stock Request Item

- `ga_review_result`.
- `ga_review_note`.
- `warehouse_available_qty` opsional.

## 11) Validasi

1. GA Admin tidak bisa ACC jika masih ada item `pending_review`.
2. GA Admin tidak bisa Reject tanpa alasan.
3. Requester tidak bisa edit Stock Request yang sudah ACC GA, kecuali ada flow revisi khusus.
4. Purchasing tidak bisa memproses item `warehouse_stock`.
5. User tanpa akses GA Admin tidak bisa membuka action GA Review.
6. User dari BU/department berbeda tidak bisa review Stock Request yang bukan scope-nya.

## 12) Acceptance Criteria

### AC-1: Submit ST masuk GA Review

Given User Department membuat Stock Request  
When Stock Request disubmit  
Then status menjadi `ga_review`  
And Stock Request muncul di daftar GA Review.

### AC-2: GA wajib review semua item sebelum ACC

Given GA Admin membuka Stock Request  
And masih ada item belum dipilih hasil review  
When GA Admin klik ACC  
Then sistem menolak action  
And menampilkan pesan bahwa semua item wajib direview.

### AC-3: GA ACC masuk Dashboard Purchasing

Given GA Admin sudah review semua item  
When GA Admin klik ACC  
Then Stock Request masuk Dashboard Purchasing  
And semua item tetap tampil.

### AC-4: Item gudang tampil dicoret

Given Stock Request sudah ACC GA  
And Item A memiliki hasil review `warehouse_stock`  
When Purchasing membuka detail Stock Request  
Then Item A tetap tampil  
And Item A tampil dengan efek coret  
And Item A tidak bisa diproses pengadaan.

### AC-5: Item perlu pengadaan tampil normal

Given Stock Request sudah ACC GA  
And Item B memiliki hasil review `need_procurement`  
When Purchasing membuka detail Stock Request  
Then Item B tampil normal  
And Item B dapat diproses Purchasing.

### AC-6: GA Reject kembali ke requester

Given GA Admin membuka Stock Request  
When GA Admin reject dengan alasan  
Then status menjadi `ga_rejected`  
And requester dapat melihat alasan  
And requester dapat revisi/resubmit.

## 13) Dampak UI

### Menu GA Admin

- Tambah menu/list `GA Review Stock Request` atau integrasi ke task dashboard existing.
- Filter minimal:
  - status.
  - department.
  - tanggal.
  - nomor ST.

### Detail GA Review

- Header Stock Request.
- Detail requester/department/BU.
- Tabel item.
- Dropdown/radio hasil review item.
- Field catatan item.
- Field catatan umum.
- Tombol ACC.
- Tombol Reject.

### Dashboard Purchasing

- Tampilkan badge hasil review GA.
- Item `warehouse_stock` memakai efek coret.
- Item `need_procurement` memakai tampilan normal.
- Jika semua item `warehouse_stock`, ST tetap boleh tampil sebagai informasi, tetapi tidak ada item yang perlu diproses pengadaan.

## 14) Dampak Backend

Area kemungkinan terdampak:

- Stock Request controller/action submit.
- Stock Request status lifecycle.
- Stock item schema untuk hasil review GA.
- Authorization GA Admin per department/BU.
- Purchasing dashboard query agar hanya mengambil ST yang sudah ACC GA.
- Audit log/activity log.
- Notification/task untuk GA Admin dan Purchasing.

## 15) Batch Implementation Governance

Implementasi dilakukan per batch agar perubahan besar tetap aman, mudah direview, dan bisa divalidasi di browser.

Flow setiap batch:

```text
Task
-> PRD Review
-> Dikerjakan
-> Code Review / Standards Review
-> Playwright Review
   - akses website
   - smoke test flow
   - UI/UX review
   - console/network check
-> No Bug / No Gap
-> Approve
-> Informasi User
```

Gate wajib per batch:

1. **Task**: scope batch jelas, kecil, dan punya acceptance criteria.
2. **PRD Review**: pastikan batch selaras dengan PRD dan tidak keluar scope.
3. **Dikerjakan**: backend/frontend dibuat sesuai ownership dan coding standards.
4. **Review**: cek security, authorization, data integrity, route/contract, dan standar file size.
5. **Playwright Review**: QA akses website langsung, validasi UI/UX, action, request/response, console error, dan visual gap.
6. **No Bug / Gap**: bug/gap wajib diselesaikan sebelum lanjut.
7. **Approve**: batch dianggap selesai hanya setelah review dan QA clean.
8. **Informasi User**: user diberi ringkasan hasil, file/fitur terdampak, dan status verification.

Batch rencana:

### Batch 1: Department Capability Foundation

- Schema/config Department Capability per BU.
- Superadmin UI untuk assign capability.
- Audit log capability changes.
- Authorization dasar Superadmin.
- Validasi minimal capability `ga_stock_review` dan `purchasing`.

### Batch 2: GA Review Backend Flow

- Status lifecycle ST: `ga_review`, `ga_rejected`, `ready_for_purchasing`.
- Routing submit ST ke department capability `ga_stock_review`.
- Endpoint/action GA ACC/Reject.
- Per-item review result storage.
- Audit activity review GA.

### Batch 3: GA Review UI

- Menu/list GA Review.
- Detail review ST.
- Per-item selector `warehouse_stock` / `need_procurement`.
- Catatan item dan catatan umum.
- Modal ACC/Reject.
- Error state jika capability belum valid.

### Batch 4: Purchasing Dashboard Integration

- Routing ST ACC GA ke department capability `purchasing`.
- Dashboard Purchasing menampilkan semua item hasil review.
- Item `warehouse_stock` tampil dicoret.
- Item `need_procurement` tetap normal dan bisa diproses.
- Guard agar item `warehouse_stock` tidak diproses pengadaan.

### Batch 5: Regression, Polish, and Documentation

- End-to-end Playwright flow requester -> GA -> Purchasing.
- UI/UX review responsive dan empty/error states.
- Regression existing ST/Purchasing flow.
- Update docs jika ada contract final.

## 16) Product Decisions

1. GA reviewer ditentukan dari department yang diberi flag/capability `ga_stock_review` atau label bisnis `General Affair` oleh Superadmin.
2. Tidak ada approval internal requester sebelum GA pada fase ini.
3. User POV: requester seperti Yulia/Hanung dari BAS submit ST, lalu ST langsung masuk ke GA Review.
4. Jika GA approve, dokumen ST memakai format seperti PR dengan tanda tangan:
   - kiri: Pengajuan/requester.
   - kanan: GA approval.
5. `warehouse_available_qty` belum wajib karena belum ada kebutuhan request itu saat ini.
6. Partial stock boleh: jika request qty 10 dan stok gudang 4, item boleh displit menjadi 4 `warehouse_stock` + 6 `need_procurement`.
7. Jika semua item tersedia di gudang, ST masuk arsip/notifikasi, nomor ST tetap lanjut.
8. Hasil review GA bersifat read-only untuk Purchasing.
9. Purchasing hanya membeli/memproses item `need_procurement`.
10. Requester tetap user pembuat ST yang direview oleh GA.

## 17) Open Questions

Tidak ada open question aktif.
