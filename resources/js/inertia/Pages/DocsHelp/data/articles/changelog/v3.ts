import type { Article } from '../../types';

export const ChangelogV3Article: Article =
{
        id: 'changelog-v3',
        category: 'changelog',
        title: 'OASIS V3 — Release Notes',
        description: 'Modul baru, peningkatan UI/UX, pembaruan organisasi, dan perbaikan performa.',
        author: 'Pramuji Arif Y',
        updatedAt: '2026-03-05',
        popular: true,
        bilingual: true,
        toc: [
            { id: 'highlights', label: 'Highlights' },
            { id: 'new-features', label: 'Fitur Baru / New Features' },
            { id: 'improvements', label: 'Peningkatan / Improvements' },
            { id: 'bug-fixes', label: 'Perbaikan / Bug Fixes' },
        ],
        content: [
            {
                type: 'callout',
                variant: 'info',
                title: 'Release V3 — 5 Maret 2026',
                body: '<span class="lang-id">Rilis ini menghadirkan modul Cashflow Projection yang sepenuhnya baru untuk perencanaan keuangan ke depan, perluasan cakupan Activity Tracking, serta serangkaian penyempurnaan UI/UX untuk meningkatkan produktivitas harian Anda.</span><span class="lang-en">This release introduces a brand-new Cashflow Projection module for forward-looking financial planning, expanded Activity Tracking coverage, and a suite of UI/UX refinements aimed at boosting day-to-day productivity.</span>',
            },
            {
                type: 'heading',
                id: 'highlights',
                level: 2,
                text: 'Release Highlights',
            },
            {
                type: 'unordered-list',
                items: [
                    '<strong>Cashflow Projection Module</strong> — <span class="lang-id">Modul baru untuk perencanaan dan pemantauan arus kas perusahaan di masa depan. Tersedia untuk user tertentu (Finance & Head of Department).</span><span class="lang-en">New module for forward-looking corporate cashflow planning and monitoring. Available to authorized users (Finance & Head of Department).</span>',
                    '<strong>Realtime Team Dashboard</strong> — <span class="lang-id">Kepala Departemen kini dapat memantau aktivitas dan beban kerja tim secara real-time dengan metrik yang terus diperbarui.</span><span class="lang-en">Heads of Department can now monitor team activity and workload in real-time with continuously updated metrics.</span>',
                    '<strong>Admin Activity Monitoring</strong> — <span class="lang-id">Dashboard baru di level admin untuk pemantauan aktivitas seluruh sistem secara real-time.</span><span class="lang-en">New admin-level dashboard for real-time system-wide activity oversight.</span>',
                ],
            },
            {
                type: 'heading',
                id: 'new-features',
                level: 2,
                text: 'Fitur Baru / New Features',
            },
            {
                type: 'unordered-list',
                items: [
                    '<strong>Modul Cashflow Projection</strong> — <span class="lang-id">Modul baru untuk perencanaan dan pemantauan arus kas perusahaan di masa depan. Kepala Departemen dapat menginput proyeksi bulanan, sementara Finance melakukan konsolidasi. Modul ini hanya tersedia untuk user tertentu yang telah diberikan akses.</span><span class="lang-en">New module for forward-looking corporate cashflow planning and monitoring. Department Heads submit monthly projections while Finance consolidates. Available to authorized users only.</span>',
                    '<strong>Sub-Activity untuk Departemen WNS</strong> — <span class="lang-id">Menambahkan pelacakan sub-aktivitas yang lebih granular untuk departemen Werkudara Nirwana Sakti: <code>SO</code>, <code>CFC</code>, dan <code>BAS</code>.</span><span class="lang-en">Added granular sub-activity tracking for Werkudara Nirwana Sakti departments: <code>SO</code>, <code>CFC</code>, and <code>BAS</code>.</span>',
                    '<strong>Activity Tracking untuk Gooper</strong> — <span class="lang-id">Karyawan business unit Gooper kini dapat mencatat, menugaskan, dan melacak tugas langsung di dalam OASIS.</span><span class="lang-en">Gooper business unit employees can now log, assign, and track tasks directly within OASIS.</span>',
                    '<strong>Admin Activity Monitoring</strong> — <span class="lang-id">Dashboard baru di level admin untuk pemantauan aktivitas seluruh sistem secara real-time di semua business unit.</span><span class="lang-en">New admin-level dashboard for system-wide activity oversight across all business units.</span>',
                    '<strong>Realtime Team Dashboard</strong> — <span class="lang-id">Dashboard live-update bagi Kepala Departemen untuk memantau beban kerja tim, progres tugas, dan output harian secara sekilas.</span><span class="lang-en">Live-updating dashboard for Heads of Department to monitor team workload, task progress, and daily output at a glance.</span>',
                    '<strong>Resend Purchase Request</strong> — <span class="lang-id">Fitur baru untuk mengirim ulang notifikasi email Purchase Request kepada approver, berguna ketika email pertama tidak diterima atau terlewat.</span><span class="lang-en">New resend feature for Purchase Request notifications, useful when the initial email was missed or not received by the approver.</span>',
                ],
            },
            {
                type: 'heading',
                id: 'improvements',
                level: 2,
                text: 'Peningkatan / Improvements',
            },
            {
                type: 'unordered-list',
                items: [
                    '<strong>Perubahan Desain untuk Keterbacaan</strong> — <span class="lang-id">Pembaruan menyeluruh pada tipografi, jarak elemen, dan kontras warna di seluruh modul untuk tampilan yang lebih bersih dan nyaman dibaca.</span><span class="lang-en">Design refresh across all modules with improved typography, spacing, and color contrast for better readability.</span>',
                    '<strong>Penyempurnaan Navigasi</strong> — <span class="lang-id">Pengelompokan sidebar dan pembaruan ikon untuk mengakomodasi modul baru dengan tetap menjaga akses cepat ke fitur yang sudah ada.</span><span class="lang-en">Sidebar grouping and icon updates to accommodate new modules while maintaining quick access to existing features.</span>',
                    '<strong>Layout Responsif</strong> — <span class="lang-id">Tata letak tabel dan form yang lebih baik di layar ukuran tablet, mengurangi scroll horizontal.</span><span class="lang-en">Better table and form layouts on tablet-sized screens, reducing horizontal scrolling.</span>',
                    '<strong>Performa</strong> — <span class="lang-id">Optimasi query dan peningkatan caching yang berkelanjutan untuk pemuatan halaman yang lebih cepat.</span><span class="lang-en">Continued query optimization and caching improvements for faster page loads.</span>',
                ],
            },
            {
                type: 'heading',
                id: 'bug-fixes',
                level: 2,
                text: 'Perbaikan / Bug Fixes & Stability',
            },
            {
                type: 'unordered-list',
                items: [
                    '<strong>Perbaikan Stock Request View Error</strong> — <span class="lang-id">Memperbaiki error tampilan pada halaman detail Stock Request yang muncul pada kondisi tertentu.</span><span class="lang-en">Fixed a view error on the Stock Request detail page that occurred under certain conditions.</span>',
                    '<strong>Sinkronisasi Business Unit</strong> — <span class="lang-id">Mengatasi masalah intermiten di mana konteks Business Unit tidak tersinkronisasi dengan benar setelah perpindahan cepat.</span><span class="lang-en">Resolved intermittent issues with Business Unit context not syncing correctly after rapid switching.</span>',
                    '<strong>Edge Case Approval Workflow</strong> — <span class="lang-id">Memperbaiki edge case di alur approval di mana notifikasi tidak terkirim ke approver berikutnya dalam kondisi tertentu.</span><span class="lang-en">Fixed edge cases in approval workflow where notifications were not dispatched to the next approver.</span>',
                    '<strong>Format Tanggal pada Export Excel</strong> — <span class="lang-id">Memperbaiki inkonsistensi format tanggal pada laporan Excel yang diekspor.</span><span class="lang-en">Corrected date formatting inconsistencies in exported Excel reports.</span>',
                    '<strong>Stabilitas Umum</strong> — <span class="lang-id">Peningkatan stabilitas umum dan perbaikan bug minor di seluruh modul.</span><span class="lang-en">General stability improvements and minor bug fixes across all modules.</span>',
                ],
            },
            {
                type: 'callout',
                variant: 'tip',
                title: 'Ada Pertanyaan atau Masukan? / Questions or Feedback?',
                body: '<span class="lang-id">Jika Anda menemui kendala pada fitur baru atau memiliki saran, hubungi tim IT melalui channel Support atau kirim tiket melalui Help Center ini.</span><span class="lang-en">If you encounter any issues with the new features or have suggestions, reach out to the IT team via the Support channel or submit a ticket through this Help Center.</span>',
            },
        ],
    };
