import type { Article } from '../../types';

export const ChangelogV4BetaJuly2026Article: Article = {
    id: 'changelog-v4-beta-july-2026',
    category: 'changelog',
    title: 'OASIS V4 Beta - July 2026 Staging Update',
    description: 'Pembaruan staging untuk migrasi IT Support, dashboard dan reporting modern, kebijakan SLA 2 x 24 jam, serta peningkatan alur Purchasing, Stock Request, Activity, dan Cashflow.',
    author: 'Pramuji Arif Y',
    updatedAt: '2026-07-21',
    popular: true,
    bilingual: true,
    toc: [
        { id: 'overview', label: 'Ringkasan Update' },
        { id: 'it-support', label: 'IT Support & Migrasi Request' },
        { id: 'dashboard-reporting', label: 'Dashboard & Reporting' },
        { id: 'workflow-updates', label: 'Workflow Operasional' },
        { id: 'platform-reliability', label: 'Stabilitas Platform' },
        { id: 'impact', label: 'Dampak untuk Pengguna' },
    ],
    content: [
        {
            type: 'callout',
            variant: 'info',
            title: 'Staging Update - 21 Juli 2026',
            body: '<span class="lang-id">Update ini merangkum fitur dan perbaikan yang tersedia di <strong>OASIS Staging</strong> sejak rilis V4 Beta tanggal 2 Juni 2026. Fokus utamanya adalah kesiapan modul IT Support, penyelarasan laporan, dan penguatan workflow operasional. Perubahan production tetap mengikuti proses review dan approval terpisah.</span><span class="lang-en">This update summarizes features and fixes available in <strong>OASIS Staging</strong> since the V4 Beta release on June 2, 2026. It focuses on IT Support readiness, reporting alignment, and stronger operational workflows. Production changes continue to follow a separate review and approval process.</span>',
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
                '<strong><span class="lang-id">Workflow Purchasing dan Stock Request diperkuat</span><span class="lang-en">Purchasing and Stock Request workflows are strengthened</span></strong> <span class="lang-id">melalui approval paralel, handoff task yang lebih jelas, status penyelesaian yang konsisten, dan pesan error yang lebih informatif.</span><span class="lang-en">through parallel approvals, clearer task handoffs, consistent completion states, and more informative error messages.</span>',
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
            html: '<span class="lang-id">Modul IT Support kini aktif di staging untuk proses review. Data historis dari <code>request.werkudara.com</code> telah dimigrasikan ke struktur ticket OASIS menggunakan workflow import yang memiliki preflight, validasi akun tujuan, dan perlindungan terhadap import ganda.</span><span class="lang-en">The IT Support module is now active in staging for review. Historical data from <code>request.werkudara.com</code> has been migrated into the OASIS ticket structure through an import workflow with preflight checks, target-account validation, and duplicate-import protection.</span>',
        },
        {
            type: 'status-list',
            items: [
                {
                    label: '<span class="lang-id">212 Ticket Historis</span><span class="lang-en">212 Historical Tickets</span>',
                    description: '<span class="lang-id">Ticket request lama tersedia di staging beserta metadata requester, kategori, prioritas, komentar, dan attachment sumber yang masih tersedia.</span><span class="lang-en">Legacy request tickets are available in staging with requester metadata, categories, priorities, comments, and source attachments that remain available.</span>',
                    color: 'blue',
                },
                {
                    label: '<span class="lang-id">Workload Terpusat</span><span class="lang-en">Centralized Workload</span>',
                    description: '<span class="lang-id">Seluruh workload hasil migrasi pada staging ditugaskan ke akun Pramuji agar review dan tindak lanjut dapat dilakukan dari satu antrian.</span><span class="lang-en">All migrated staging workload is assigned to the Pramuji account so review and follow-up can be managed from one queue.</span>',
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
            id: 'workflow-updates',
            level: 2,
            text: 'Workflow Operasional',
        },
        {
            type: 'status-list',
            items: [
                {
                    label: '<span class="lang-id">Stock Request</span><span class="lang-en">Stock Request</span>',
                    description: '<span class="lang-id">Approval kepala departemen dapat berjalan paralel, review purchasing lebih jelas, status Done tersinkron, dan PDF menampilkan urutan acknowledgement yang benar tanpa total harga yang tidak diperlukan.</span><span class="lang-en">Department-head approvals can run in parallel, purchasing review is clearer, Done status is synchronized, and PDFs show the correct acknowledgement order without unnecessary price totals.</span>',
                    color: 'blue',
                },
                {
                    label: '<span class="lang-id">Purchasing</span><span class="lang-en">Purchasing</span>',
                    description: '<span class="lang-id">Handoff PR dan Stock Request ke task queue diperjelas, snapshot approver dipertahankan, filter default tidak lagi memenuhi URL, dan nilai desimal ditampilkan dengan benar.</span><span class="lang-en">PR and Stock Request handoffs into the task queue are clearer, approver snapshots are preserved, default filters no longer clutter URLs, and decimal values display correctly.</span>',
                    color: 'emerald',
                },
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
            text: 'Stabilitas Platform',
        },
        {
            type: 'unordered-list',
            items: [
                '<span class="lang-id">Staging memiliki penanda visual yang jelas, password pengujian terkontrol, dan konfigurasi <code>noindex</code> agar tidak masuk mesin pencari.</span><span class="lang-en">Staging has a clear visual indicator, controlled test passwords, and <code>noindex</code> configuration to keep it out of search engines.</span>',
                '<span class="lang-id">Pipeline deployment kini mewajibkan CI sukses sebelum release staging diaktifkan dan menggunakan release terpisah agar rollback lebih aman.</span><span class="lang-en">The deployment pipeline now requires successful CI before activating a staging release and uses separate releases for safer rollback.</span>',
                '<span class="lang-id">Database test CI diisolasi dari database aplikasi, sementara migrasi lintas lingkungan diperkuat agar konsisten di MySQL dan SQLite.</span><span class="lang-en">CI test databases are isolated from application databases, while cross-environment migrations are hardened for consistent MySQL and SQLite behavior.</span>',
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
            title: 'Yang Perlu Direview di Staging',
            body: '<span class="lang-id">Tim dapat meninjau ticket historis, memastikan requester dan kategori sudah tepat, memeriksa workload Pramuji, membandingkan Dashboard dengan Reporting pada periode yang sama, serta menguji alur Purchasing, Stock Request, Activity, dan Cashflow sebelum perubahan dipromosikan ke production.</span><span class="lang-en">Teams can review historical tickets, confirm requester and category mappings, inspect Pramuji workload, compare Dashboard and Reporting over the same period, and test Purchasing, Stock Request, Activity, and Cashflow workflows before changes are promoted to production.</span>',
        },
    ],
};
