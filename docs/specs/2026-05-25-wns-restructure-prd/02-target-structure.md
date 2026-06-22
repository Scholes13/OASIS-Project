# 02 - Target Structure

Status: **BLOCKED** — menunggu input PO
Depends on: 00-overview, 01-schema-changes

## Blocker

Section ini butuh data lengkap dari `excel/strukturnew.pdf`. PM Agent tidak bisa baca PDF langsung. Dibutuhkan salah satu:

- Convert PDF ke text (`pdftotext` / `pdfplumber`) lalu paste konten
- Render PDF ke PNG per halaman lalu attach
- Paste struktur sebagai text manual

## Yang sudah diketahui (dari snippet sebelumnya)

### WG / Executive Office (root dept baru, opsi A di Section 05)

```
WG / Executive Office (code: EXEC)
├── Fadli Fahmi Ali — CEO
└── I Gusti Putu Yaktianuraga — Managing Director
```

Dept lama WG (CEO, MD, SYSADMIN) → **deactivate** (default PRD).

### WNS / Executive Office (root dept baru, PO 2026-05-26 revision)

```
WNS / Executive Office (code: EXEC)
└── Adiel Priyarama — Chief of Staff (adiel@werkudara.com)
```

Adiel sits at WNS, not WG: dia adalah tangan kanan CEO untuk WNS dengan
visibility executive ke semua data WNS (Activity, Purchasing, IT Support,
Cashflow). Mirror pattern WG/EXEC tapi BU-nya WNS.

### WNS / Sales & Marketing (root dept baru)

```
WNS / Sales & Marketing (code: SM)
├── Etik Andriyanti — General Manager (andri@werkudara.com)
├── Ainur Hasanah — Asisten GM (ainur@werkudara.com)
│
├── Business Solutions Division (code: BSD, parent: SM)
│   ├── Irvani Putri — Business Solutions Manager
│   ├── Kensrie Diah A. — Business Solutions Manager
│   ├── Emy Nurhayati — Business Solutions Manager
│   ├── Nindy Amalia — Business Solutions Manager
│   ├── Mya Mar'atus S. — Business Solutions Manager
│   ├── Paramitha Maharesmi (mitha) — Business Solutions Manager
│   ├── Elfasa Khoirumansyah (elfasa) — Business Solutions Specialist
│   ├── Wulida Tsania H.A. (tsania) — Commercial Engineer
│   └── F.A. Anggito Enggarjati (enggar) — Commercial Engineer
│
├── Commercial Division (code: COM, parent: SM)
│   ├── Linda Susanto — Commercial Manager
│   ├── Vanessa Salvathea (vanessa) — Pricing & Costing Analyst
│   ├── Muhammad Haekal Baihaqi (haekal) — Pricing & Costing Analyst
│   ├── Refangga (refangga) — Commercial Creative Designer
│   └── TBA — Commercial Creative Designer
│
└── Corporate Marketing Communication Division (code: CMC, parent: SM)
    ├── Fuad Jaka P. (jaka) — Brand Experience & Partnership Lead
    ├── I.D.A. Kayana Abhipraya P.B. — Market Analyst
    ├── Septian Mahendra D. (septian) — Creative Content Strategist
    └── Andrew Ardhany S. (andrew) — Creative Content Designer
```

### WNS / Sales Operations (dept lama, tetap)

```
WNS / SO (code: SO, existing, flat)
├── Gilang Risnantyo — Sales Operation Coordinator (user baru)
├── Bulqis Purnama Dewi (bulqis) — Sales Operation
└── M. Zaky Al Aqsa (zaky) — Sales Operation
```

## Yang masih kosong

Belum ada info untuk dept-dept WNS berikut (apakah berubah, dapat sub-dept, ganti HOD, dst):

- ACC (Accounting)
- ACS (Art & Creative Support) — sebagian user pindah ke S&M, sisanya?
- BAS (Business & Administrative Services)
- BID (Business Innovation Development) — Jaka pindah, sisa Alif
- CFC (Corporate Finance Controller)
- GA (General Affair)
- HR (Human Resource)
- PD (Product Development) — Mitha, Tsania, Enggar pindah
- SS (Strategic Sourcing)
- TEP (Tour & Event Planning) — Ainur, Elfasa pindah

## Format yang Diharapkan saat Data Tersedia

Untuk tiap dept WNS, PRD butuh:
1. **Code** dan **Name** (kalau berubah)
2. **Status** (active/deactivate)
3. **Sub-dept** (kalau ada) dengan code/name
4. **HOD** (email + name)
5. **Roster** lengkap (siapa pindah, siapa keep, siapa baru)

## Action

PM Agent: **HOLD** sampai PO supply data PDF.

Kalau urgent, fallback minimal: deploy schema + position + S&M tree saja dulu (Section 01, 03), data migration (Section 04) dipecah jadi 2 batch — batch 1 cuma user S&M-tree, batch 2 nanti setelah PO finalize dept lain.
