# 🚀 Quick Start Guide - New Dashboard

## ⚡ TL;DR
Dashboard sekarang menggunakan **data real** dari database, bukan dummy data lagi!

---

## 🎯 Apa yang Berubah?

### **SEBELUM** ❌
```blade
<!-- Hardcoded dummy data -->
<p class="text-3xl font-bold">12</p>  <!-- Fake number -->
<p class="text-3xl font-bold">5</p>   <!-- Fake number -->
<p class="text-3xl font-bold">28</p>  <!-- Fake number -->

<!-- Fake activity -->
<p>PR PR.IT/2025/01/001 was approved</p>  <!-- Fake -->
```

### **SEKARANG** ✅
```php
// Real data from database
{{ $stats['active_prs'] }}      // Real count
{{ $stats['pending_approvals'] }} // Real count
{{ $stats['period_prs'] }}       // Real count
{{ number_format($stats['total_amount']) }} // Real sum

// Real activity from Spatie Log
@foreach($recentActivities as $activity)
    {!! $activity['message'] !!}  // Real data
@endforeach
```

---

## 🆕 Fitur Baru

### 1. **Filter Tanggal** 📅
- **Today** - Data hari ini
- **This Week** - Data minggu ini
- **This Month** - Data bulan ini (default)
- **Last 30 Days** - 30 hari terakhir
- **This Year** - Data tahun ini
- **Custom Range** - Pilih tanggal sendiri

### 2. **Chart Interaktif** 📊
- **Daily Trend** - Grafik line PR per hari
- **Status Distribution** - Grafik donut status PR
- Hover untuk lihat detail!

### 3. **Activity Log Real** 📜
- Data dari Spatie Activity Log
- Icon sesuai tipe aksi
- Warna sesuai status
- Timestamp relatif ("2 hours ago")

### 4. **Reports (Top Management Only)** 🔒
- Hanya untuk General Manager, Director, CEO, Finance Manager
- Super Admin selalu bisa akses
- User biasa tidak bisa akses

---

## 📱 Cara Pakai

### **Step 1: Buka Dashboard**
```
http://localhost:8000/dashboard
```

### **Step 2: Pilih Filter**
1. Klik dropdown "Date Range Filter"
2. Pilih "This Week" / "This Month" / dll
3. Atau pilih "Custom Range" lalu pilih tanggal

### **Step 3: Lihat Chart**
- Hover mouse di grafik untuk lihat detail
- Grafik otomatis update saat ganti filter

### **Step 4: Cek Activity**
- Scroll ke "Recent Activity"
- Lihat 5 aktivitas terakhir
- Warna hijau = approved, merah = rejected, dll

---

## 🔍 Stat Cards Explained

### **Active Purchase Requests** (Biru)
- **Angka Besar**: Jumlah PR yang status `submitted` atau `in_approval`
- **Subtitle**: Jumlah PR dengan status `draft`

### **Pending Approvals** (Orange)
- **Angka Besar**: Jumlah approval yang menunggu keputusan Anda
- **Subtitle**: Jumlah yang overdue (lewat deadline)

### **Selected Period** (Hijau)
- **Angka Besar**: Jumlah PR yang dibuat di periode yang dipilih
- **Subtitle**: Berapa yang approved vs rejected

### **Total Amount** (Ungu)
- **Angka Besar**: Total nilai rupiah PR di periode yang dipilih
- **Subtitle**: "Selected period"

---

## 📊 Chart Guide

### **Daily Trend Chart** (Line Chart)
- **X-Axis**: Tanggal (Oct 1, Oct 2, ...)
- **Y-Axis**: Jumlah PR dibuat
- **Hover**: Lihat exact count per hari

### **Status Distribution** (Doughnut Chart)
- **Abu-abu**: Draft
- **Biru**: Submitted
- **Kuning**: In Approval
- **Hijau**: Approved
- **Merah**: Rejected
- **Abu-abu Gelap**: Voided
- **Hover**: Lihat count + percentage

---

## 🔒 Authorization

### **Regular User**
- ✅ Bisa akses dashboard
- ✅ Lihat data pribadi
- ✅ Gunakan filter & chart
- ❌ **TIDAK** bisa akses `/reports/*`

### **Top Management**
- ✅ Semua fitur regular user
- ✅ **BISA** akses `/reports/*`
- ✅ Lihat "Coming Soon" message (fitur dalam development)

**Top Management Positions**:
- General Manager
- Director
- CEO
- Finance Manager

### **Super Admin**
- ✅ Akses semua fitur
- ✅ Akses admin dashboard via `/admin`
- ✅ Bypass semua authorization

---

## 🧪 Testing Checklist

### **Test Real Data**
1. [ ] Login sebagai user biasa
2. [ ] Buka `/dashboard`
3. [ ] Buat PR baru → Cek "Active PRs" naik
4. [ ] Submit PR → Cek "Pending Approvals" untuk approver naik
5. [ ] Approve PR → Cek "Approved PRs" naik

### **Test Filters**
1. [ ] Pilih "Today" → Lihat hanya PR hari ini
2. [ ] Pilih "This Month" → Lihat PR bulan ini
3. [ ] Pilih "Custom Range" → Input tanggal → Klik "Apply Filter"
4. [ ] Cek chart berubah sesuai filter

### **Test Charts**
1. [ ] Hover di line chart → Lihat tooltip
2. [ ] Hover di doughnut chart → Lihat percentage
3. [ ] Ganti filter → Chart auto-update
4. [ ] Buka browser console → Pastikan no errors

### **Test Activity**
1. [ ] Buat PR → Lihat muncul di "Recent Activity"
2. [ ] Update PR → Lihat activity baru
3. [ ] Approve/Reject → Lihat activity dengan warna berbeda

### **Test Authorization**
1. [ ] Login sebagai user biasa → Akses `/reports/purchase-requests` → Harus 403
2. [ ] Login sebagai General Manager → Akses `/reports/purchase-requests` → Harus OK
3. [ ] Login sebagai Super Admin → Akses semua halaman → Harus OK

---

## 🐛 Troubleshooting

### **Stat cards menunjukkan 0**
**Kemungkinan**: User belum punya PR di database
**Solusi**: Buat PR baru atau seed database

### **Chart tidak muncul**
**Kemungkinan**: Chart.js tidak load dari CDN
**Solusi**: 
1. Cek internet connection
2. Buka browser console, cek error
3. Pastikan ada `<canvas id="dailyTrendChart">`

### **Filter tidak jalan**
**Kemungkinan**: Livewire tidak load
**Solusi**:
1. Cek browser console untuk error
2. Clear browser cache
3. Pastikan `@livewireScripts` ada di layout

### **Activity log kosong**
**Kemungkinan**: Spatie Activity Log belum terekam
**Solusi**:
1. Buat/update PR untuk generate activity
2. Cek `activities` table di database
3. Pastikan model punya trait `LogsActivity`

---

## 📞 Support

**Error?** Cek file log:
```bash
tail -50 storage/logs/laravel.log
```

**Documentation?** Lihat:
- `DASHBOARD-UPDATE.md` - Technical docs lengkap
- `IMPLEMENTATION-SUMMARY.md` - Summary & checklist

**Questions?** Contact project maintainer

---

## ✅ Ready to Test!

```bash
# Start server
php artisan serve

# Visit
http://localhost:8000/dashboard
```

**Enjoy your new real-time dashboard! 🚀**
