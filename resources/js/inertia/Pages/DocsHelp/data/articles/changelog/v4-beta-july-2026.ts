import type { Article } from '../../types';

export const ChangelogV4BetaJuly2026Article: Article = {
    id: 'changelog-v4-beta-july-2026',
    category: 'changelog',
    title: 'OASIS V4 Beta - IT Support, Purchasing & Workflow Update',
    description: 'Pembaruan Juni-Juli 2026 untuk IT Support, Purchase Request, approval, Stock Request, dashboard dan reporting, Activity, Cashflow, serta stabilitas sistem.',
    author: 'Pramuji Arif Y',
    updatedAt: '2026-07-21',
    popular: true,
    bilingual: true,
    toc: [
        { id: 'overview', label: 'Ringkasan Update' },
        { id: 'it-support', label: 'IT Support & Migrasi Request' },
        { id: 'dashboard-reporting', label: 'Dashboard & Reporting' },
        { id: 'purchase-request', label: 'Purchase Request & Approval' },
        { id: 'stock-request', label: 'Stock Request' },
        { id: 'workflow-updates', label: 'Activity & Cashflow' },
        { id: 'platform-reliability', label: 'Keamanan & Stabilitas' },
        { id: 'impact', label: 'Dampak untuk Pengguna' },
    ],
    content: [
        {
            type: 'callout',
            variant: 'info',
            title: 'Update 21 Juli 2026',
            body: '<span class="lang-id">Update ini merangkum fitur dan perbaikan OASIS sejak rilis V4 Beta tanggal 2 Juni 2026. Fokus utamanya adalah IT Support, perbaikan Purchase Request dan approval, penyelarasan laporan, serta penguatan workflow operasional.</span><span class="lang-en">This update summarizes OASIS features and fixes since the V4 Beta release on June 2, 2026. It focuses on IT Support, Purchase Request and approval fixes, reporting alignment, and stronger operational workflows.</span>',
        },
        {
            type: 'heading',
            id: 'overview',
            level: 2,
            text: 'Ringkasan Update',
        },
        {
            type: 'unordered-list',
            items: [
                '<strong><span class="lang-id">IT Support siap diuji dengan data request historis</span><span class="lang-en">IT Support is ready for testing with historical request data</span></strong> <span class="lang-id">melalui importer yang aman, idempotent, dan dapat memindahkan ticket beserta komentar serta attachment yang tersedia.</span><span class="lang-en">through a safe, idempotent importer that can migrate tickets together with their comments and available attachments.</span>',
                '<strong><span class="lang-id">Dashboard dan Reporting kini konsisten</span><span class="lang-en">Dashboard and Reporting are now consistent</span></strong> <span class="lang-id">dengan filter modern, pilihan This Year dan All Data, volume ticket nyata, workload, serta perhitungan SLA yang sama.</span><span class="lang-en">with modern filters, This Year and All Data presets, real ticket volume, workload, and matching SLA calculations.</span>',
                '<strong><span class="lang-id">SLA diseragamkan menjadi 2 x 24 jam</span><span class="lang-en">SLA is standardized at 2 x 24 hours</span></strong> <span class="lang-id">untuk seluruh prioritas agar Dashboard, Reporting, Excel, dan PDF menggunakan kebijakan yang sama.</span><span class="lang-en">for every priority so Dashboard, Reporting, Excel, and PDF use the same policy.</span>',
                '<strong><span class="lang-id">Purchase Request, approval, dan Stock Request diperkuat</span><span class="lang-en">Purchase Request, approvals, and Stock Request are strengthened</span></strong> <span class="lang-id">melalui routing task yang tepat, approval paralel, riwayat approver yang stabil, handoff yang lebih jelas, dan status penyelesaian yang konsisten.</span><span class="lang-en">through correct task routing, parallel approvals, stable approver history, clearer handoffs, and consistent completion states.</span>',
                '<strong><span class="lang-id">Cashflow dan Activity lebih nyaman digunakan</span><span class="lang-en">Cashflow and Activity are easier to use</span></strong> <span class="lang-id">dengan penyempurnaan chart, import workbook, tampilan waktu, navigasi task, dan responsivitas lintas perangkat.</span><span class="lang-en">with improved charts, workbook imports, time display, task navigation, and cross-device responsiveness.</span>',
            ],
        },
        {
            type: 'heading',
            id: 'it-support',
            level: 2,
            text: 'IT Support & Migrasi Request',
        },
        {
            type: 'paragraph',
            html: '<span class="lang-id">Modul IT Support kini dilengkapi workflow untuk memindahkan data historis dari <code>request.werkudara.com</code> ke struktur ticket OASIS. Workflow ini telah divalidasi menggunakan data request historis dan memiliki preflight, validasi akun tujuan, serta perlindungan terhadap import ganda.</span><span class="lang-en">The IT Support module now includes a workflow for moving historical data from <code>request.werkudara.com</code> into the OASIS ticket structure. The workflow has been validated with historical request data and includes preflight checks, target-account validation, and duplicate-import protection.</span>',
        },
        {
            type: 'status-list',
            items: [
                {
                    label: '<span class="lang-id">Validasi 212 Ticket Historis</span><span class="lang-en">212 Historical Tickets Validated</span>',
                    description: '<span class="lang-id">Hasil validasi importer mencakup metadata requester, kategori, prioritas, komentar, dan attachment sumber yang masih tersedia.</span><span class="lang-en">Importer validation covers requester metadata, categories, priorities, comments, and source attachments that remain available.</span>',
                    color: 'blue',
                },
                {
                    label: '<span class="lang-id">Opsi Workload Terpusat</span><span class="lang-en">Centralized Workload Option</span>',
                    description: '<span class="lang-id">Importer dapat menugaskan seluruh workload hasil migrasi ke satu akun tervalidasi agar review dan tindak lanjut dapat dilakukan dari satu antrian.</span><span class="lang-en">The importer can assign all migrated workload to one validated account so review and follow-up can be managed from one queue.</span>',
                    color: 'emerald',
                },
                {
                    label: '<span class="lang-id">Import Aman & Dapat Diulang</span><span class="lang-en">Safe & Repeatable Import</span>',
                    description: '<span class="lang-id">Importer memeriksa collision, mapping user/departemen, file sumber, dan import ID sebelum menulis data.</span><span class="lang-en">The importer checks collisions, user/department mappings, source files, and import IDs before writing data.</span>',
                    color: 'amber',
                },
            ],
        },
        {
            type: 'heading',
            id: 'dashboard-reporting',
            level: 2,
            text: 'Dashboard & Reporting',
        },
        {
            type: 'unordered-list',
            items: [
                '<strong><span class="lang-id">Filter tanggal ringkas</span><span class="lang-en">Compact date filters</span></strong> <span class="lang-id">menggabungkan preset, rentang tanggal yang mudah dibaca, custom date picker, dan tombol Apply dalam satu control modern.</span><span class="lang-en">combine presets, a readable date range, a custom date picker, and an Apply button in one modern control.</span>',
                '<strong><span class="lang-id">Preset langsung diterapkan</span><span class="lang-en">Presets apply immediately</span></strong> <span class="lang-id">untuk Today, This Week, This Month, 30 Days, 90 Days, This Year, dan All Data.</span><span class="lang-en">for Today, This Week, This Month, 30 Days, 90 Days, This Year, and All Data.</span>',
                '<strong><span class="lang-id">Ticket Volume Tracker memakai data nyata</span><span class="lang-en">Ticket Volume Tracker uses real data</span></strong> <span class="lang-id">dan menampilkan jumlah pada hari aktif terbaru tanpa ruang kosong berlebihan.</span><span class="lang-en">and displays totals for the latest active days without excessive empty space.</span>',
                '<strong><span class="lang-id">Reporting dan export mengikuti periode aktif</span><span class="lang-en">Reporting and exports follow the active period</span></strong> <span class="lang-id">sehingga angka di layar, file Excel, dan PDF menggunakan filter yang sama.</span><span class="lang-en">so on-screen metrics, Excel files, and PDFs use the same filter.</span>',
                '<strong><span class="lang-id">SLA 48 jam untuk semua prioritas</span><span class="lang-en">48-hour SLA for every priority</span></strong> <span class="lang-id">menghilangkan selisih perhitungan breached antara Dashboard dan Reporting.</span><span class="lang-en">eliminates breached-count differences between Dashboard and Reporting.</span>',
            ],
        },
        {
            type: 'heading',
            id: 'purchase-request',
            level: 2,
            text: 'Purchase Request & Approval',
        },
        {
            type: 'unordered-list',
            items: [
                '<strong><span class="lang-id">Alur PR dan follow-up purchasing lebih jelas</span><span class="lang-en">Clearer PR and purchasing follow-up flow</span></strong> <span class="lang-id">dengan status approval, proses purchasing, dan penyelesaian task yang tersambung pada halaman detail yang sama.</span><span class="lang-en">with approval status, purchasing work, and task completion connected on the same detail page.</span>',
                '<strong><span class="lang-id">Task PR masuk ke antrian Purchasing yang tepat</span><span class="lang-en">PR tasks reach the correct Purchasing queue</span></strong> <span class="lang-id">sementara departemen pemohon tetap terlihat sebagai sumber request. Task baru tersedia untuk manual claim dan tidak lagi otomatis masuk ke admin yang hanya memiliki akses baca.</span><span class="lang-en">while the requester department remains visible as the request source. New tasks are available for manual claim and are no longer auto-assigned to read-only admins.</span>',
                '<strong><span class="lang-id">Tidak ada task ganda</span><span class="lang-en">No duplicate tasks</span></strong> <span class="lang-id">saat transisi approval diproses ulang, dan dokumen otomatis berstatus Done setelah task purchasing selesai.</span><span class="lang-en">when approval transitions are processed again, and documents automatically move to Done after the purchasing task is completed.</span>',
                '<strong><span class="lang-id">Riwayat approver tetap akurat</span><span class="lang-en">Approver history stays accurate</span></strong> <span class="lang-id">karena snapshot approver dipertahankan walaupun aturan approval atau struktur organisasi berubah.</span><span class="lang-en">because approver snapshots are preserved even when approval rules or the organization structure change.</span>',
                '<strong><span class="lang-id">Informasi admin muncul pada waktunya</span><span class="lang-en">Admin information appears at the right time</span></strong> <span class="lang-id">sehingga nama assigned admin baru ditampilkan setelah follow-up PR benar-benar dimulai.</span><span class="lang-en">so the assigned admin name appears only after PR follow-up has actually started.</span>',
                '<strong><span class="lang-id">Email approval diperbarui</span><span class="lang-en">Updated approval emails</span></strong> <span class="lang-id">dengan tampilan request, item, dan tindakan approval yang lebih mudah dipahami.</span><span class="lang-en">with clearer request, item, and approval-action information.</span>',
                '<strong><span class="lang-id">Daftar task lebih bersih</span><span class="lang-en">Cleaner task lists</span></strong> <span class="lang-id">karena filter default tidak lagi memenuhi URL dan nilai desimal pada detail task ditampilkan dengan benar.</span><span class="lang-en">because default filters no longer clutter the URL and decimal values display correctly on task details.</span>',
            ],
        },
        {
            type: 'heading',
            id: 'stock-request',
            level: 2,
            text: 'Stock Request',
        },
        {
            type: 'unordered-list',
            items: [
                '<strong><span class="lang-id">Approval kepala departemen dapat berjalan paralel</span><span class="lang-en">Department-head approvals can run in parallel</span></strong> <span class="lang-id">untuk request yang melibatkan lebih dari satu departemen.</span><span class="lang-en">for requests involving more than one department.</span>',
                '<strong><span class="lang-id">Review dan claim purchasing lebih transparan</span><span class="lang-en">More transparent purchasing review and claim</span></strong> <span class="lang-id">dengan acknowledgement kepala departemen, status reviewer, dan progress penyelesaian yang terlihat pada detail request.</span><span class="lang-en">with department-head acknowledgement, reviewer status, and completion progress visible on request details.</span>',
                '<strong><span class="lang-id">Task dapat diselesaikan tanpa harga realisasi</span><span class="lang-en">Tasks can finish without realized prices</span></strong> <span class="lang-id">saat proses memang tidak membutuhkan pencatatan harga.</span><span class="lang-en">when the process does not require price recording.</span>',
                '<strong><span class="lang-id">PDF diperbaiki</span><span class="lang-en">Improved PDFs</span></strong> <span class="lang-id">dengan ukuran QR yang konsisten, urutan tanda tangan dan acknowledgement yang benar, serta tanpa total harga yang tidak diperlukan.</span><span class="lang-en">with consistent QR sizing, correct signature and acknowledgement order, and no unnecessary price totals.</span>',
                '<strong><span class="lang-id">Pesan gagal submit lebih jelas</span><span class="lang-en">Clearer submission errors</span></strong> <span class="lang-id">agar pengguna mengetahui data atau approval yang masih perlu dilengkapi.</span><span class="lang-en">so users know which data or approvals still need attention.</span>',
            ],
        },
        {
            type: 'heading',
            id: 'workflow-updates',
            level: 2,
            text: 'Activity & Cashflow',
        },
        {
            type: 'status-list',
            items: [
                {
                    label: '<span class="lang-id">Activity</span><span class="lang-en">Activity</span>',
                    description: '<span class="lang-id">Update task biasa kembali ke halaman asal yang tepat, tampilan waktu diseragamkan, dan layout lintas modul diperkuat untuk layar kecil.</span><span class="lang-en">Regular task updates return to the correct originating page, time display is standardized, and cross-module layouts are improved for smaller screens.</span>',
                    color: 'amber',
                },
                {
                    label: '<span class="lang-id">Cashflow Projection</span><span class="lang-en">Cashflow Projection</span>',
                    description: '<span class="lang-id">Chart projection dan proses import workbook diperbarui dengan kontrol ledger yang lebih ramah pengguna serta feedback import yang lebih jelas.</span><span class="lang-en">Projection charts and workbook imports are updated with friendlier ledger controls and clearer import feedback.</span>',
                    color: 'blue',
                },
            ],
        },
        {
            type: 'heading',
            id: 'platform-reliability',
            level: 2,
            text: 'Keamanan & Stabilitas',
        },
        {
            type: 'unordered-list',
            items: [
                '<span class="lang-id">Setiap pembaruan wajib melewati pemeriksaan otomatis sebelum dapat dipasang, sehingga error build, migrasi, dan regression dapat ditemukan lebih awal.</span><span class="lang-en">Every update must pass automated checks before installation so build, migration, and regression errors can be found earlier.</span>',
                '<span class="lang-id">Sistem menggunakan release terpisah agar aktivasi pembaruan dan rollback dapat dilakukan dengan lebih aman.</span><span class="lang-en">The system uses separate releases so updates can be activated and rolled back more safely.</span>',
                '<span class="lang-id">Database pengujian diisolasi dari data aplikasi untuk mencegah test memengaruhi data kerja.</span><span class="lang-en">Test databases are isolated from application data to prevent tests from affecting working data.</span>',
            ],
        },
        {
            type: 'heading',
            id: 'impact',
            level: 2,
            text: 'Dampak untuk Pengguna',
        },
        {
            type: 'callout',
            variant: 'tip',
            title: 'Yang Baru untuk Pengguna',
            body: '<span class="lang-id">Tim IT mendapat workflow migrasi request yang lebih aman dan opsi pemusatan workload. Pengguna juga mendapat filter Dashboard dan Reporting yang konsisten, alur Purchase Request dan Stock Request yang lebih jelas, serta perbaikan navigasi Activity dan import Cashflow.</span><span class="lang-en">IT teams gain a safer request migration workflow and a centralized workload option. Users also get consistent Dashboard and Reporting filters, clearer Purchase Request and Stock Request flows, and improved Activity navigation and Cashflow imports.</span>',
        },
    ],
};
