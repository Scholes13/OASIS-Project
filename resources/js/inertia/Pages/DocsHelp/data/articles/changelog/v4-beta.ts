import type { Article } from '../../types';

export const ChangelogV4BetaArticle: Article =
{
        id: 'changelog-v4-beta',
        category: 'changelog',
        title: 'OASIS V4 Beta - WNS Restructure 2026, Codebase Overhaul & Module Polish',
        description: 'Update besar yang menghadirkan struktur organisasi WNS 2026 (Sales & Marketing dengan divisi BS/COM/CMC, Chief of Staff), katalog activity untuk divisi baru, refactor menyeluruh untuk maintainability, feature flag IT Support, dan sejumlah perbaikan akses.',
        author: 'Pramuji Arif Y',
        updatedAt: '2026-06-02',
        popular: true,
        bilingual: true,
        toc: [
            { id: 'overview', label: 'Ringkasan Update' },
            { id: 'main-updates', label: 'Peningkatan Utama' },
            { id: 'wns-restructure', label: 'WNS Restructure 2026' },
            { id: 'activity-catalog', label: 'Katalog Activity Divisi Baru' },
            { id: 'access-fixes', label: 'Perbaikan Akses & Otorisasi' },
            { id: 'it-support-flag', label: 'IT Support Module' },
            { id: 'codebase', label: 'Refactor & Maintainability' },
            { id: 'impact', label: 'Dampak untuk Pengguna' },
        ],
        content: [
            {
                type: 'callout',
                variant: 'info',
                title: 'Update V4 Beta - 2 Juni 2026',
                body: '<span class="lang-id">Rilis <strong>Beta</strong> ini merangkum perubahan besar pada struktur organisasi <strong>Werkudara Nirwana Sakti (WNS)</strong>, penambahan katalog activity untuk divisi baru, perombakan internal kode untuk kemudahan perawatan, feature flag untuk modul IT Support, dan sejumlah perbaikan hak akses. Karena berstatus Beta, mohon laporkan kendala apa pun melalui kanal Support.</span><span class="lang-en">This <strong>Beta</strong> release summarizes major changes to the <strong>Werkudara Nirwana Sakti (WNS)</strong> organizational structure, new activity catalogs for new divisions, an internal code overhaul for maintainability, a feature flag for the IT Support module, and several access-control fixes. As a Beta, please report any issues through the Support channel.</span>',
            },
            {
                type: 'image',
                id: 'hero',
                src: '/images/changelog/v4-beta/overview.svg',
                alt: 'OASIS V4 Beta overview',
                caption: '<span class="lang-id">Tangkapan layar ringkasan OASIS V4 Beta. <em>(Ganti berkas di /images/changelog/v4-beta/overview.svg atau .png)</em></span><span class="lang-en">OASIS V4 Beta overview screenshot. <em>(Replace file at /images/changelog/v4-beta/overview.svg or .png)</em></span>',
            },
            {
                type: 'heading',
                id: 'overview',
                level: 2,
                text: 'Ringkasan Update',
            },
            {
                type: 'paragraph',
                html: '<span class="lang-id">OASIS V4 Beta adalah update terbesar sejauh ini. Fokus utamanya ada empat: <strong>(1) Struktur Organisasi WNS 2026</strong> yang memperkenalkan konsep departemen induk-anak, divisi Sales & Marketing (Business Solutions, Commercial, Corporate Marketing Communication), serta peran Chief of Staff; <strong>(2) Katalog Activity</strong> lengkap untuk divisi-divisi baru tersebut; <strong>(3) Refactor menyeluruh</strong> pada kode agar lebih mudah dirawat dan dikembangkan; dan <strong>(4) Perbaikan hak akses</strong> berdasarkan hasil pengujian peran pengguna.</span><span class="lang-en">OASIS V4 Beta is the largest update so far. It focuses on four areas: <strong>(1) the WNS 2026 organizational structure</strong> introducing the parent-child department concept, the Sales & Marketing division (Business Solutions, Commercial, Corporate Marketing Communication), and the Chief of Staff role; <strong>(2) complete Activity catalogs</strong> for those new divisions; <strong>(3) a thorough code refactor</strong> for easier maintenance and future development; and <strong>(4) access-control fixes</strong> based on user-role testing.</span>',
            },
            {
                type: 'heading',
                id: 'main-updates',
                level: 2,
                text: 'Peningkatan Utama',
            },
            {
                type: 'unordered-list',
                items: [
                    '<strong><span class="lang-id">Struktur Sales & Marketing baru di WNS</span><span class="lang-en">New Sales & Marketing structure in WNS</span></strong> <span class="lang-id">dengan tiga divisi (Business Solutions, Commercial, Corporate Marketing Communication) di bawah satu departemen induk, plus peran General Manager dan Asisten GM.</span><span class="lang-en">with three divisions (Business Solutions, Commercial, Corporate Marketing Communication) under one parent department, plus General Manager and Assistant GM roles.</span>',
                    '<strong><span class="lang-id">Konsep departemen induk-anak</span><span class="lang-en">Parent-child department concept</span></strong> <span class="lang-id">sehingga manajer di departemen induk otomatis melihat agregat data dari sub-divisinya.</span><span class="lang-en">so a manager at a parent department automatically sees aggregated data from its sub-divisions.</span>',
                    '<strong><span class="lang-id">Chief of Staff sebagai peran eksekutif WNS</span><span class="lang-en">Chief of Staff as a WNS executive role</span></strong> <span class="lang-id">dengan visibilitas lintas modul untuk memantau seluruh operasional WNS.</span><span class="lang-en">with cross-module visibility to monitor all WNS operations.</span>',
                    '<strong><span class="lang-id">Katalog activity untuk BS, COM, CMC, dan SM</span><span class="lang-en">Activity catalogs for BS, COM, CMC, and SM</span></strong> <span class="lang-id">sehingga setiap divisi punya jenis aktivitas yang relevan saat membuat task.</span><span class="lang-en">so each division has relevant activity types when creating tasks.</span>',
                    '<strong><span class="lang-id">Modul IT Support kini bisa dimatikan per-lingkungan</span><span class="lang-en">IT Support module can now be toggled per environment</span></strong> <span class="lang-id">melalui feature flag, sehingga bisa disembunyikan di produksi sampai siap dirilis.</span><span class="lang-en">via a feature flag, so it can be hidden in production until ready to launch.</span>',
                    '<strong><span class="lang-id">Perbaikan hak akses</span><span class="lang-en">Access-control fixes</span></strong> <span class="lang-id">untuk Cashflow Projection, Purchasing Admin, dan menu sidebar agar tidak ada menu yang muncul tapi tidak bisa diklik.</span><span class="lang-en">for Cashflow Projection, Purchasing Admin, and the sidebar menu so no menu item appears that cannot be opened.</span>',
                ],
            },
            {
                type: 'heading',
                id: 'wns-restructure',
                level: 2,
                text: 'WNS Restructure 2026',
            },
            {
                type: 'paragraph',
                html: '<span class="lang-id">Perubahan terbesar di V4 Beta adalah pengenalan struktur organisasi baru untuk WNS. Sistem kini mendukung <strong>departemen induk-anak</strong> (maksimal satu tingkat), memungkinkan satu departemen induk membawahi beberapa sub-divisi. Departemen <strong>Sales &amp; Marketing (SM)</strong> menjadi induk dari tiga divisi: Business Solutions, Commercial, dan Corporate Marketing Communication.</span><span class="lang-en">The biggest change in V4 Beta is the introduction of a new organizational structure for WNS. The system now supports <strong>parent-child departments</strong> (max one level), letting one parent department oversee several sub-divisions. The <strong>Sales &amp; Marketing (SM)</strong> department becomes the parent of three divisions: Business Solutions, Commercial, and Corporate Marketing Communication.</span>',
            },
            {
                type: 'image',
                id: 'wns-structure-img',
                src: '/images/changelog/v4-beta/wns-structure.svg',
                alt: 'WNS Sales & Marketing structure',
                caption: '<span class="lang-id">Struktur Sales &amp; Marketing WNS dengan tiga divisi. <em>(Ganti berkas di /images/changelog/v4-beta/wns-structure.svg atau .png)</em></span><span class="lang-en">WNS Sales &amp; Marketing structure with three divisions. <em>(Replace file at /images/changelog/v4-beta/wns-structure.svg or .png)</em></span>',
            },
            {
                type: 'status-list',
                items: [
                    {
                        label: '<span class="lang-id">Departemen Induk-Anak</span><span class="lang-en">Parent-Child Departments</span>',
                        description: '<span class="lang-id">Manajer di departemen induk (mis. GM Sales &amp; Marketing) otomatis melihat agregat data dari semua sub-divisi di bawahnya.</span><span class="lang-en">A manager at a parent department (e.g. GM Sales &amp; Marketing) automatically sees aggregated data from all sub-divisions beneath it.</span>',
                        color: 'blue',
                    },
                    {
                        label: '<span class="lang-id">Tiga Divisi Baru</span><span class="lang-en">Three New Divisions</span>',
                        description: '<span class="lang-id">Business Solutions (BS), Commercial (COM), dan Corporate Marketing Communication (CMC) kini menjadi sub-divisi resmi di bawah Sales &amp; Marketing.</span><span class="lang-en">Business Solutions (BS), Commercial (COM), and Corporate Marketing Communication (CMC) are now official sub-divisions under Sales &amp; Marketing.</span>',
                        color: 'emerald',
                    },
                    {
                        label: '<span class="lang-id">General Manager &amp; Asisten GM</span><span class="lang-en">General Manager &amp; Assistant GM</span>',
                        description: '<span class="lang-id">Peran baru di tingkat departemen induk dengan visibilitas ke seluruh divisi melalui relasi induk-anak.</span><span class="lang-en">New roles at the parent-department level with visibility into all divisions via the parent-child relationship.</span>',
                        color: 'amber',
                    },
                    {
                        label: '<span class="lang-id">Chief of Staff (Executive)</span><span class="lang-en">Chief of Staff (Executive)</span>',
                        description: '<span class="lang-id">Peran eksekutif di WNS Executive Office dengan akses pemantauan lintas modul (Activity, Purchasing, IT Support, Cashflow).</span><span class="lang-en">An executive role in the WNS Executive Office with cross-module monitoring access (Activity, Purchasing, IT Support, Cashflow).</span>',
                        color: 'blue',
                    },
                ],
            },
            {
                type: 'paragraph',
                html: '<span class="lang-id">Migrasi ini juga memindahkan pengguna existing ke departemen/posisi baru dan memetakan ulang task historis mereka ke departemen yang sesuai, sehingga dashboard divisi langsung menampilkan riwayat aktivitas yang relevan. Seluruh proses dijalankan otomatis lewat <code>php artisan migrate</code> dan bersifat idempotent (aman dijalankan ulang).</span><span class="lang-en">This migration also moves existing users into their new departments/positions and remaps their historical tasks to the matching department, so division dashboards immediately show relevant activity history. The whole process runs automatically via <code>php artisan migrate</code> and is idempotent (safe to re-run).</span>',
            },
            {
                type: 'heading',
                id: 'activity-catalog',
                level: 2,
                text: 'Katalog Activity Divisi Baru',
            },
            {
                type: 'paragraph',
                html: '<span class="lang-id">Setiap divisi baru kini punya katalog jenis aktivitas (activity type) dan sub-aktivitas sendiri, sehingga anggota tim memilih kategori yang relevan saat membuat task. Departemen induk Sales &amp; Marketing memperoleh gabungan dari ketiga divisinya.</span><span class="lang-en">Each new division now has its own catalog of activity types and sub-activities, so team members pick relevant categories when creating tasks. The parent Sales &amp; Marketing department receives a merged union of its three divisions.</span>',
            },
            {
                type: 'image',
                id: 'activity-catalog-img',
                src: '/images/changelog/v4-beta/activity-catalog.svg',
                alt: 'Activity catalog for new divisions',
                caption: '<span class="lang-id">Pilihan activity type untuk divisi baru saat membuat task. <em>(Ganti berkas di /images/changelog/v4-beta/activity-catalog.svg atau .png)</em></span><span class="lang-en">Activity type options for new divisions when creating a task. <em>(Replace file at /images/changelog/v4-beta/activity-catalog.svg or .png)</em></span>',
            },
            {
                type: 'status-list',
                items: [
                    {
                        label: '<span class="lang-id">Business Solutions (BS)</span><span class="lang-en">Business Solutions (BS)</span>',
                        description: '<span class="lang-id">Mengikuti katalog TEP plus tambahan: Telemarketing - Meeting, Meeting - Presentasi, Networking Activities, dan Relationship Building.</span><span class="lang-en">Mirrors the TEP catalog plus additions: Telemarketing - Meeting, Meeting - Presentasi, Networking Activities, and Relationship Building.</span>',
                        color: 'blue',
                    },
                    {
                        label: '<span class="lang-id">Commercial (COM)</span><span class="lang-en">Commercial (COM)</span>',
                        description: '<span class="lang-id">Costing, Pembuatan Quotation/Proposal/Design, Brainstorming, Internal/Eksternal Meeting, Follow Up Vendor, KPI, Recap Inquiry, dan Event.</span><span class="lang-en">Costing, Quotation/Proposal/Design creation, Brainstorming, Internal/External Meeting, Vendor Follow Up, KPI, Recap Inquiry, and Event.</span>',
                        color: 'emerald',
                    },
                    {
                        label: '<span class="lang-id">Corporate Marketing Communication (CMC)</span><span class="lang-en">Corporate Marketing Communication (CMC)</span>',
                        description: '<span class="lang-id">Content &amp; Social Media (Facebook, Instagram, Website, YouTube, LinkedIn), Project, Administrasi, Leave, Training, dan lainnya.</span><span class="lang-en">Content &amp; Social Media (Facebook, Instagram, Website, YouTube, LinkedIn), Project, Administration, Leave, Training, and more.</span>',
                        color: 'amber',
                    },
                    {
                        label: '<span class="lang-id">Sales &amp; Marketing (SM, induk)</span><span class="lang-en">Sales &amp; Marketing (SM, parent)</span>',
                        description: '<span class="lang-id">Gabungan activity dari BS + COM + CMC, dengan jenis yang tujuannya sama digabung menjadi satu agar daftar tetap ringkas.</span><span class="lang-en">A union of activities from BS + COM + CMC, with same-purpose types merged into one to keep the list concise.</span>',
                        color: 'blue',
                    },
                ],
            },
            {
                type: 'heading',
                id: 'access-fixes',
                level: 2,
                text: 'Perbaikan Akses & Otorisasi',
            },
            {
                type: 'paragraph',
                html: '<span class="lang-id">Setelah pengujian peran pengguna (GM, kepala departemen, staf, Chief of Staff, super admin), beberapa hak akses diperbaiki agar konsisten dan tidak ada menu yang muncul tapi tidak bisa dibuka.</span><span class="lang-en">After user-role testing (GM, department head, staff, Chief of Staff, super admin), several access rules were fixed for consistency so that no menu item appears that cannot be opened.</span>',
            },
            {
                type: 'unordered-list',
                items: [
                    '<strong><span class="lang-id">Cashflow Projection untuk eksekutif</span><span class="lang-en">Cashflow Projection for executives</span></strong> <span class="lang-id">kini bisa diakses oleh manajemen puncak (termasuk Chief of Staff), bukan hanya kepala departemen dan tim finance.</span><span class="lang-en">is now accessible to top management (including the Chief of Staff), not only department heads and the finance team.</span>',
                    '<strong><span class="lang-id">Purchasing Admin untuk manajemen puncak</span><span class="lang-en">Purchasing Admin for top management</span></strong> <span class="lang-id">dapat diakses oleh eksekutif di unit bisnis mana pun, konsisten dengan modul admin lainnya.</span><span class="lang-en">is accessible to executives in any business unit, consistent with the other admin modules.</span>',
                    '<strong><span class="lang-id">Dokumen approval offline</span><span class="lang-en">Offline approval documents</span></strong> <span class="lang-id">kini bisa dilihat oleh manajemen puncak dan purchasing admin, lewat tautan yang aman (bukan akses berkas langsung).</span><span class="lang-en">can now be viewed by top management and purchasing admins, via a secure link (not direct file access).</span>',
                    '<strong><span class="lang-id">Tidak ada lagi menu sidebar yang "mati"</span><span class="lang-en">No more dead sidebar menu items</span></strong> <span class="lang-id">— setiap item yang tampil dijamin bisa dibuka oleh pengguna yang melihatnya, tanpa halaman 403/404.</span><span class="lang-en">— every visible item is guaranteed openable by the user who sees it, with no 403/404 pages.</span>',
                ],
            },
            {
                type: 'heading',
                id: 'it-support-flag',
                level: 2,
                text: 'IT Support Module',
            },
            {
                type: 'paragraph',
                html: '<span class="lang-id">Modul IT Support (WG Ticket) kini dikendalikan oleh sebuah <strong>feature flag</strong>. Di lingkungan pengembangan dan staging modul tetap aktif untuk pengujian, sementara di produksi modul dapat disembunyikan sepenuhnya sampai dinyatakan siap rilis — termasuk menu sidebar, rute, dan tautan Knowledge Base.</span><span class="lang-en">The IT Support (WG Ticket) module is now controlled by a <strong>feature flag</strong>. In development and staging the module stays active for testing, while in production it can be hidden entirely until it is declared ready to launch — including sidebar menus, routes, and the Knowledge Base link.</span>',
            },
            {
                type: 'callout',
                variant: 'tip',
                title: 'Catatan Lingkungan',
                body: '<span class="lang-id">Saat modul dimatikan di produksi, seluruh halaman IT Support tidak dapat diakses dan tidak muncul di navigasi, sehingga tampilan tetap rapi untuk pengguna akhir.</span><span class="lang-en">When the module is turned off in production, all IT Support pages are inaccessible and hidden from navigation, keeping the interface clean for end users.</span>',
            },
        ],
    };


