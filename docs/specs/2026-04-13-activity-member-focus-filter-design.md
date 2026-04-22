# Activity Member Focus Filter Design

**Date:** 2026-04-13
**Status:** Approved
**Owner:** PM Agent

## Goal

Menambahkan filter anggota tim pada permukaan Activity yang berbasis tim agar user bisa fokus melihat pekerjaan satu member saja tanpa mencampur seluruh anggota departemen.

Target utama:
- `Activity/Dashboard` pada scope `Team`
- `Activity/ActivityDashboard` pada view `Department`
- export yang berasal dari `Activity/ActivityDashboard`

Filter harus mempengaruhi seluruh dataset yang dipakai halaman, bukan hanya list yang terlihat.

## Approved Product Direction

User mengonfirmasi kebutuhan ini sebagai `filter by user`, bukan filter periode seperti cashflow.

Perilaku yang disetujui:
- filter dipakai untuk memfokuskan satu member tertentu, misalnya Hanung ingin melihat pekerjaan Pram saja
- saat satu member dipilih, semua cards, charts, list, board, calendar, timeline, insight, dan export ikut memakai dataset yang sama
- definisi "kerjaan member" memakai dua kondisi:
  - task dibuat oleh member tersebut
  - atau member tersebut menjadi participant
- jika kedua kondisi sama-sama benar, task tetap dihitung satu kali
- user tidak perlu melakukan review spec ulang setelah reviewer sub-agent menyetujui dan gap sudah ditutup; implementasi boleh lanjut langsung

## Canonical Terminology

| Surface | UI label | Canonical state/param | Member filter active? |
| --- | --- | --- | --- |
| `Activity/Dashboard` | `My Tasks` | `scope=my` | No |
| `Activity/Dashboard` | `Team` | `scope=department` | Yes |
| `Activity/ActivityDashboard` | `Personal` | frontend `viewMode=personal` | No |
| `Activity/ActivityDashboard` | `Department` | frontend `viewMode=department` | Yes |
| `Activity/ActivityDashboard` | `Executive` | frontend `viewMode=executive` | No |

Aturan implementasi:
- istilah `Team` di UI task management selalu dipetakan ke `scope=department`
- istilah `Department` di dashboard analytics adalah mode tampilan frontend, bukan query param backend baru
- backend tidak membaca `viewMode` dari React state; backend hanya menerima `member_user_id` dan menerapkannya pada dataset department yang memang dibangun dalam response
- `member_user_id` hanya boleh aktif pada dua keadaan yang ditandai `Yes` di tabel di atas

## Canonical Empty Value

Gunakan aturan nilai kosong berikut:
- di request/query string: parameter dihilangkan
- di helper/backend internal: `null`
- di props `Activity/Dashboard.filters.member_user_id`: `''` agar selaras dengan model filter string yang sudah ada
- di props `Activity/ActivityDashboard.queryParams.member_user_id`: `null` atau string id, mengikuti pola query param halaman ini

Frontend wajib melakukan mapping eksplisit:
- `''` di task management berarti `All Team Members`
- `null` di activity dashboard berarti `All Department Members`

Typing rule:
- request/frontend form state di task management tetap memakai string agar selaras dengan `TaskFilters`
- backend boleh parse menjadi integer internal
- response props harus kembali ke representasi canonical surface masing-masing (`''` untuk task management, `null` untuk dashboard query params) agar tidak terjadi loop sinkronisasi

## Current State Summary

### `Activity/Dashboard`

- Halaman `resources/js/inertia/Pages/Activity/Dashboard.tsx` sudah punya scope toggle `My Tasks` vs `Team`
- Filter dropdown saat ini hanya mendukung:
  - `activity_type_id`
  - `status`
  - `date_from`
  - `date_to`
- Backend `ActivityInertiaController@index` membaca `scope` dan filter dasar tersebut, tetapi belum punya filter anggota tim
- Stats dan task dataset dibangun dari scope aktif, lalu dipakai lintas list, board, calendar, dan timeline

### `Activity/ActivityDashboard`

- Halaman `resources/js/inertia/Pages/Activity/ActivityDashboard.tsx` sudah punya mode `Personal`, `Department`, dan opsional `Executive`
- Data department dashboard dibangun melalui:
  - `getDepartmentStats(...)`
  - `getDepartmentVisuals(...)`
  - export `activity.task.export`
- Tombol `Filter` di header belum memfokuskan data ke anggota tertentu
- Backend visual department saat ini selalu menghitung seluruh department scope aktif

## User Journey

### Journey yang ingin dicapai

- Supervisor atau HoD masuk ke `Task Management`, mengubah scope ke `Team`, lalu memilih satu member
- Setelah memilih member, halaman hanya menampilkan task yang relevan ke member itu pada semua mode tampilan
- Supervisor atau HoD masuk ke `Activity Dashboard`, berpindah ke mode `Department`, lalu memilih satu member
- KPI, focus panel, dan insight langsung merefleksikan pekerjaan member itu saja
- Saat export ditekan, file unduhan konsisten dengan member filter yang sedang aktif

### Journey yang tetap dipertahankan

- `My Tasks` dan `Personal` tetap menjadi view personal tanpa dropdown member
- `Executive` tetap agregat lintas unit dan tidak memakai filter member department-level ini
- hak akses yang sudah ada tidak berubah; filter hanya mempersempit dataset yang memang sudah boleh dilihat

## Recommended Approach

### 1. Tambahkan satu kontrak filter lintas permukaan

Gunakan query parameter baru:
- `member_user_id`

Aturan kontraknya:
- aktif untuk `Activity/Dashboard` hanya saat `scope=department`
- aktif untuk `Activity/ActivityDashboard` hanya pada dataset department
- diabaikan atau direset saat user berada di `my/personal/executive`

Manfaat pendekatan ini:
- frontend dan backend memakai bahasa filter yang sama
- export bisa mengikuti filter yang sama
- lebih kecil risiko drift dibanding dua parameter berbeda

### Request and response contract

#### `Activity/Dashboard`

Request params:
- `scope`: `'my' | 'department'`
- `member_user_id`: `string | undefined`
- filter lain yang sudah ada

Response props:
- `filters.member_user_id`: `string`
- `teamMembers`: `Array<{ id: number; name: string }>`

Semantics:
- jika `scope !== 'department'`, response harus mengembalikan `filters.member_user_id = ''`
- jika request mengirim `member_user_id` tidak valid, response juga harus mengembalikan `filters.member_user_id = ''`

#### `Activity/ActivityDashboard`

Request params:
- `distribution_period`
- `dept_distribution_period`
- `member_user_id`

Response props:
- `queryParams.member_user_id`: `string | null`
- `departmentMembers`: `Array<{ id: number; name: string }>`

Semantics:
- jika mode frontend bukan `department`, request interaktif tidak boleh mengirim `member_user_id`
- jika request langsung/deep link tetap mengirim nilai itu, backend tidak membaca `viewMode` dari client state
- backend hanya boleh menerapkan `member_user_id` pada dataset department di response dashboard
- dataset personal dan executive harus tetap memakai perilaku lama walaupun parameter itu ikut terkirim
- nilai tersanitasi tetap dikembalikan di `queryParams.member_user_id`
- pembersihan `member_user_id` saat user keluar dari mode `Department` adalah tanggung jawab frontend, bukan kewajiban backend sanitization

#### Export endpoint

Request params:
- `scope`
- `member_user_id`
- filter lain yang sudah ada (`date_from`, `date_to`, `status`, `activity_type_id`, dan sejenisnya)

Semantics:
- export harus memakai aturan sanitasi dan aturan matching yang sama dengan surface asal
- request export dengan `scope !== 'department'` harus memperlakukan `member_user_id` sebagai `null`
- request export dengan `member_user_id` invalid harus diperlakukan sebagai `null`, bukan error 500 dan bukan dataset melebar
- dokumen ini menstandardisasi kontrak `member_user_id`; filter non-member yang sudah berbeda antar surface tetap mengikuti perilaku surface masing-masing

Pemetaan source dashboard ke export:
- `Activity/ActivityDashboard` mode `personal` mengirim `scope=my`
- `Activity/ActivityDashboard` mode `department` mengirim `scope=department`
- karena filter member hanya hidup pada mode `department`, export hanya boleh menerapkan `member_user_id` saat menerima `scope=department`

Export entry points yang wajib ikut kontrak ini:
- tombol export pada header `Activity/ActivityDashboard`
- export dari `Activity/Dashboard` atau komponen tabel task management yang sudah ada

Guardrail implementasi:
- jangan overload parameter atau argumen lama yang sekarang dipakai untuk semantik actor/user scope participant-only
- service export perlu menerima argumen member focus yang terpisah dari argumen actor scope yang sudah ada sekarang
- semantik export `scope=my` harus tetap identik dengan perilaku sebelum fitur ini ditambahkan

### 2. Filter dataset di level query sumber, bukan di level presentasi

Filter member harus dipasang pada query sumber sebelum menghitung:
- stats
- list tasks
- board tasks
- calendar tasks
- timeline tasks
- roadmap/team activity rows
- focus breakdown
- insight
- export dataset

Aturan matching task:
- sertakan task bila `created_by = member_user_id`
- atau bila ada participant dengan `user_id = member_user_id`
- tidak ada cabang ketiga terpisah untuk owner; owner sudah terwakili oleh relasi creator atau participant yang memiliki flag owner

Implementasi harus membungkus dua kondisi itu di satu blok `where (...)` agar hasilnya konsisten dan tidak menduplikasi row.

Pola query yang diharapkan:
- gunakan kondisi root task:
  - `where('created_by', $memberUserId)`
  - `orWhereHas('participants', fn (...) => ...)`
- jangan melakukan join participants yang bisa menduplikasi task rows pada stats atau export
- bila sebuah query membutuhkan join lain di tahap berikutnya, hasil task harus tetap dijaga unik lewat pola query root yang sama atau `distinct(employee_tasks.id)` bila memang tidak terhindarkan

### 2a. Base scope invariants

Member filter adalah predicate tambahan dan tidak boleh menggantikan base scope yang sudah ada.

Aturan wajib:
- `Activity/Dashboard`
  - base scope `my` tetap sama seperti sekarang
  - base scope `department` tetap sama seperti sekarang, yaitu `(department_id = current_department) OR (viewer adalah participant)`
  - `member_user_id` hanya boleh diterapkan sebagai `AND (created_by = member OR has participant member)` di atas base scope `department`
  - artinya, jika viewer saat ini memang masih melihat task lintas department melalui cabang legacy `viewer adalah participant`, perilaku itu tetap dipertahankan dan hanya dipersempit oleh predicate member focus
- `Activity/ActivityDashboard`
  - personal datasets tetap personal seperti sekarang
  - department datasets tetap department-only seperti sekarang
  - `member_user_id` hanya menjadi penyaring tambahan pada department datasets
- Export
  - base scope export saat `scope=my` tetap mempertahankan semantik lama
  - member focus hanya boleh diterapkan pada export `scope=department`

Dengan aturan ini, member filter tidak boleh memperlebar akses di luar dataset yang memang sudah visible sebelum filter ditambahkan.

### 3. Sanitasi member terhadap scope aktif

Backend harus menyediakan daftar member valid untuk scope aktif dan hanya menerima `member_user_id` yang ada pada daftar itu.

Sumber daftar member valid harus mengikuti assignment department aktif pada BU saat ini, bukan hanya `users.primary_department_id`, karena repository ini sudah mendukung perpindahan context department melalui assignment aktif.

Jika tidak valid, backend harus:
- menormalkan filter menjadi `null`
- mengembalikan nilai filter yang sudah dibersihkan ke frontend

Algoritma validasi yang diwajibkan:
- ambil assignment aktif dari `user_business_units`
- batasi ke `business_unit_id` context aktif
- batasi ke `department_id` context aktif
- ambil `user_id` yang unik dari assignment tersebut
- gunakan hanya user aktif yang masih memiliki assignment aktif pada context itu
- jika hasil kosong, opsi member menjadi array kosong dan `member_user_id` dipaksa `null`

Ini mencegah kasus:
- user pindah department/BU lalu dropdown masih menyimpan member lama
- halaman tampak kosong karena filter lama tetap menempel
- export menggunakan member yang tidak lagi valid

## Backend Design

### `ActivityInertiaController@index`

Tambahkan ke kontrak filters:
- `member_user_id`

Perubahan perilaku:
- saat `scope=my`, abaikan `member_user_id`
- saat `scope=department`, sanitasi `member_user_id` terhadap daftar anggota department yang valid
- jika valid, tambahkan filter:
  - `created_by = member_user_id`
  - `OR whereHas(participants.user_id = member_user_id)`

Stats untuk task index harus dihitung dari query yang sama, bukan query scope lama tanpa member filter.

Canonicalization behavior:
- backend tidak perlu melakukan redirect hanya untuk membersihkan query
- response props harus selalu membawa nilai filter yang sudah dibersihkan
- frontend harus menyinkronkan local state dari props backend yang tersanitasi
- saat user berpindah dari `Team` ke `My Tasks`, request interaktif berikutnya harus menghapus `member_user_id` dari URL payload

Response Inertia perlu menambahkan:
- `teamMembers` yang berisi daftar member valid untuk dropdown
- `filters.member_user_id` hasil sanitasi backend

### `ActivityInertiaController@dashboard`

Tambahkan pembacaan `member_user_id` untuk department analytics.

Perubahan perilaku:
- personal stats dan personal visuals tetap tidak memakai filter member
- department stats dan department visuals menerima `member_user_id` hasil sanitasi
- export memakai parameter yang sama agar workbook selaras

Canonicalization behavior:
- saat frontend keluar dari mode `Department`, request berikutnya harus menghapus `member_user_id`
- bila deep link awal masih membawa `member_user_id`, backend tetap hanya menerapkannya pada dataset department
- frontend bertanggung jawab menghapus parameter itu pada interaksi berikutnya jika user tidak lagi berada di mode `Department`

Tambahkan helper bersama untuk:
- resolve member options
- sanitize requested member id
- apply member focus filter ke query task

Helper ini wajib dipakai ulang oleh task index, dashboard, dan export supaya tidak muncul tiga versi logika filter.

### Export contract

Endpoint export activity harus menerima `member_user_id` dan membatasi dataset dengan aturan yang sama.

Jika filter aktif:
- nama file export tidak wajib berubah
- tetapi isi workbook harus mengikuti member yang dipilih

Jika filter tidak valid atau scope tidak mendukung:
- export tetap berhasil
- dataset diperlakukan seolah tidak ada member filter
- tidak boleh ada jalur khusus yang memakai filter participant-only

Representasi nilai kosong export:
- jika UI sedang tidak memilih member, parameter `member_user_id` tidak perlu dikirim
- jika request langsung membawa nilai invalid, backend mengubahnya ke internal `null` sebelum membangun query export

## Frontend Design

### `Activity/Dashboard`

- Perluas `TaskFilters` dengan `member_user_id`
- Di `resources/js/inertia/components/activity/FilterDropdown.tsx`, tambahkan field `Member` hanya ketika `filters.scope === 'department'`
- Opsi pertama:
  - `All Team Members`
- Opsi berikutnya:
  - daftar anggota dari prop backend
- Saat scope berubah dari `department` ke `my`, frontend harus menghapus `member_user_id`
- Saat backend mengembalikan filters tersanitasi, local state perlu sinkron agar dropdown tidak drift
- `Dashboard.tsx` menjadi source of truth untuk nilai filter; `FilterDropdown` harus mengikuti props tersanitasi dan tidak menjadi sumber state independen yang bisa menyimpang
- saat member berubah, pagination list harus kembali ke halaman pertama dan tidak membawa page lama

### `Activity/ActivityDashboard`

- Aktifkan tombol `Filter` yang sudah ada di header
- Saat mode `Department`, popover berisi select `Member`
- Saat mode `Personal` atau `Executive`, popover disembunyikan atau field member tidak dirender
- Tombol filter perlu indikator aktif jika satu member sedang dipilih
- request perubahan member hanya me-reload props dashboard yang relevan, mengikuti pola Inertia yang sudah dipakai halaman ini
- request perubahan member harus mempertahankan period query yang masih relevan seperti `dept_distribution_period`
- perubahan member harus mereset pagination roadmap department ke halaman pertama jika ada page query aktif
- saat user berpindah dari `Department` ke `Personal` atau `Executive`, frontend harus segera membersihkan `member_user_id` dari request berikutnya

### Empty states and affordance

Jika member filter aktif dan dataset kosong:
- tampilkan pesan yang menandakan ini hasil filter, bukan error
- contoh arah copy:
  - `Tidak ada aktivitas untuk member ini pada filter yang aktif.`

Tidak perlu menambahkan fitur pencarian member pada iterasi pertama kecuali daftar member terbukti terlalu besar.

## Data Contract

### Request params

- `scope`
- `member_user_id`
- filter yang sudah ada sekarang (`status`, `activity_type_id`, `date_from`, `date_to`, period filter dashboard, dan lain-lain sesuai permukaan)

Catatan:
- kontrak yang diseragamkan oleh dokumen ini adalah `member_user_id`
- filter non-member tetap boleh berbeda antar surface, tetapi export dari masing-masing surface wajib meneruskan `member_user_id` tersanitasi jika scope/mode mendukungnya

### Inertia props minimum

Untuk `Activity/Dashboard`:
- `filters.member_user_id`
- `teamMembers`

Untuk `Activity/ActivityDashboard`:
- `queryParams.member_user_id`
- `departmentMembers`

Setiap opsi member minimal memuat:
- `id`
- `name`

`email` opsional bila memang sudah mudah tersedia, tetapi tidak wajib untuk iterasi pertama.

Daftar ini diambil dari assignment aktif `user_business_units` untuk kombinasi BU + department context yang sedang dipakai.

Pada iterasi ini, fallback yang diperbolehkan hanya:
- tidak ada fallback tambahan selain hasil assignment aktif
- jika assignment aktif tidak menemukan siapa pun, dropdown dikembalikan kosong

Ini lebih aman dibanding fallback ke seluruh `users.primary_department_id` yang bisa tidak sinkron dengan context department aktif.

## Testing Strategy

### Backend

Tambahkan regression coverage untuk:
- task index team scope memfilter stats dan tasks berdasarkan creator
- task index team scope memfilter stats dan tasks berdasarkan participant
- task index team scope tidak double-count saat member adalah creator sekaligus participant
- task index mengabaikan `member_user_id` saat scope `my`
- task index memastikan member filter tidak memperlebar base scope department yang sudah ada
- dashboard department stats dan visuals ikut berubah saat `member_user_id` aktif
- dashboard personal dan executive datasets tetap tidak berubah saat `member_user_id` ikut terkirim
- invalid `member_user_id` dinormalisasi
- request export langsung dengan `member_user_id` invalid diperlakukan aman
- export `scope=my` tetap mempertahankan semantik lama
- export hanya berisi data member yang dipilih saat filter aktif
- kedua entry point export meneruskan `member_user_id` tersanitasi dengan benar

### Frontend

Tambahkan focused React tests untuk:
- `FilterDropdown` menampilkan field `Member` hanya di scope team
- perubahan scope menghapus `member_user_id`
- dashboard filter member mengirim request dengan `member_user_id`
- indicator aktif muncul saat member dipilih
- state frontend re-sync ke props backend yang sudah tersanitasi
- perubahan member tidak memicu loop request karena mismatch tipe string vs int
- perubahan member di dashboard mempertahankan query period yang masih aktif

## Risks and Guardrails

- jangan sampai filter member memperluas akses ke task di luar department scope yang memang boleh dilihat user
- jangan pertahankan `member_user_id` saat user kembali ke `my/personal`
- jangan hitung stats dari query yang berbeda dengan query task/export
- hindari membuat logika filter terpisah antara dashboard, task index, dan export
- jangan mengubah semantik lama export `scope=my`

## Out of Scope

- tidak menambah multi-select member pada iterasi ini
- tidak menambah filter period model cashflow pada dashboard activity
- tidak mengubah permission model department analytics atau executive analytics
- tidak menambah dependency baru
