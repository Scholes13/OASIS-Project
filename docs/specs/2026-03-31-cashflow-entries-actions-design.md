# Cashflow Entries Actions Design

**Date:** 2026-03-31

## Goal
Menambahkan aksi `Delete` pada tabel `All Entries` di modul Cashflow Projection dan menyederhanakan aksi baris menjadi satu dropdown yang compact, estetik, dan konsisten dengan pola UI repo.

## Approved UX
- Kolom `Action` menggunakan tombol ikon tiga titik.
- Dropdown berisi dua item:
  - `Edit entry`
  - `Delete entry`
- `Delete entry` menggunakan aksen merah lembut dan membuka modal konfirmasi klasik.
- Modal konfirmasi menyebut nama entry agar jelas item yang dihapus.
- Semua user yang bisa mengakses halaman `cashflow-projection.entries` dapat menghapus entry yang terlihat dalam scope mereka.

## Backend Design
- Tambah route `DELETE /cashflow-projection/line-items/{lineItem}`.
- Tambah action controller untuk menghapus line item secara aman.
- Otorisasi delete mengikuti visibilitas halaman `entries`:
  - finance/CFC dapat menghapus line item dalam business unit aktif atau linked BU yang memang masuk scope,
  - non-finance dapat menghapus line item pada departemen yang memang berada dalam scope akses mereka.
- Audit log tetap append-only dengan action `deleted`.
- Redirect kembali ke halaman `entries` pada bulan/tahun yang sama dengan flash success/error yang konsisten.

## Frontend Design
- Ganti tombol `Edit` tunggal di tabel dengan menu `MoreHorizontal`.
- Dropdown rata kanan agar tidak bentrok dengan kolom amount.
- `Edit entry` mempertahankan flow edit saat ini.
- `Delete entry` membuka `ConfirmDialog` yang sudah ada di shared UI.
- Setelah delete sukses:
  - modal tertutup,
  - dropdown tertutup,
  - scroll tetap dipertahankan.

## Error Handling
- Jika entry tidak ditemukan atau tidak lagi valid, redirect aman dengan flash error generik.
- Tidak mengekspos detail exception internal ke user.
- State dropdown/modal tidak boleh tertinggal saat request batal atau selesai.

## Verification
- Feature test backend untuk delete route dan audit trail delete.
- React test untuk dropdown action dan modal delete.
- `vendor/bin/pint --dirty`
- `npm exec tsc --noEmit --pretty false`
