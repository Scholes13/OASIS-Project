import type { Article } from '../../types';

export const ChangelogV302Article: Article =
{
        id: 'changelog-v3-0-2',
        category: 'changelog',
        title: 'OASIS V3.0.2 - Workflow, Access & Reporting Update',
        description: 'Minor update yang merangkum penyempurnaan workflow task, akses dokumen, pergantian departemen, dan report activity/cashflow.',
        author: 'Pramuji Arif Y',
        updatedAt: '2026-03-31',
        bilingual: true,
        toc: [
            { id: 'overview', label: 'Ringkasan Update' },
            { id: 'main-updates', label: 'Peningkatan Utama' },
            { id: 'workflow-updates', label: 'Workflow Activity' },
            { id: 'access-updates', label: 'Akses & Navigasi' },
            { id: 'reporting-updates', label: 'Pembaruan Reporting' },
            { id: 'impact', label: 'Dampak untuk Pengguna' },
        ],
        content: [
            {
                type: 'callout',
                variant: 'info',
                title: 'Minor Update V3.0.2 - 31 Maret 2026',
                body: '<span class="lang-id">Update ini merangkum beberapa perbaikan penting dari <strong>30 dan 31 Maret 2026</strong>, dengan fokus pada workflow activity yang lebih nyaman, akses dokumen yang lebih aman, pergantian departemen yang kembali lancar, serta report yang lebih kaya informasi.</span><span class="lang-en">This update summarizes several important fixes from <strong>March 30 and 31, 2026</strong>, with a focus on smoother activity workflows, safer document access, restored department switching, and richer reporting.</span>',
            },
            {
                type: 'heading',
                id: 'overview',
                level: 2,
                text: 'Ringkasan Update',
            },
            {
                type: 'paragraph',
                html: '<span class="lang-id">OASIS V3.0.2 adalah minor update yang menggabungkan beberapa penyempurnaan user-facing dari dua hari terakhir. Perubahan ini terutama membantu pengguna bekerja lebih cepat di activity, membaca report dengan lebih jelas, dan membuka dokumen atau berpindah konteks dengan lebih aman.</span><span class="lang-en">OASIS V3.0.2 is a minor update that combines several user-facing improvements from the last two days. These changes mainly help users work faster in Activity, read reports more clearly, and open documents or switch context more safely.</span>',
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
                    '<strong><span class="lang-id">Task activity kini tetap masuk ke modal dashboard</span><span class="lang-en">Activity tasks now stay within the dashboard modal flow</span></strong> <span class="lang-id">sehingga detail, edit, dan create tetap terasa terpadu tanpa pindah ke halaman lama.</span><span class="lang-en">so detail, edit, and create actions remain in one flow instead of jumping back to legacy pages.</span>',
                    '<strong><span class="lang-id">State board Activity kembali stabil setelah drag dibatalkan</span><span class="lang-en">The Activity board state now recovers after a cancelled drag</span></strong> <span class="lang-id">agar kartu task tidak hilang atau bergeser tidak sesuai kondisi server.</span><span class="lang-en">so task cards do not disappear or drift away from the server state.</span>',
                    '<strong><span class="lang-id">Kalender Activity menampilkan owner dengan lebih jelas</span><span class="lang-en">The Activity calendar now shows owners more clearly</span></strong> <span class="lang-id">untuk memudahkan identifikasi penanggung jawab pada setiap tugas.</span><span class="lang-en">to make it easier to identify who owns each task.</span>',
                    '<strong><span class="lang-id">Aksi entry Cashflow dibuat lebih ringkas</span><span class="lang-en">Cashflow entry actions are now more compact</span></strong> <span class="lang-id">dengan alur delete yang lebih sederhana tetapi tetap aman dan mudah dipahami.</span><span class="lang-en">with a simpler delete flow that stays safe and easy to understand.</span>',
                    '<strong><span class="lang-id">Pergantian departemen multi-department sudah pulih</span><span class="lang-en">Multi-department switching has been restored</span></strong> <span class="lang-id">sehingga pengguna yang punya lebih dari satu departemen bisa berpindah konteks lagi dengan normal.</span><span class="lang-en">so users with more than one department can switch context normally again.</span>',
                    '<strong><span class="lang-id">Supporting document Purchase Request kini lebih aman</span><span class="lang-en">Purchase Request supporting documents are now secured</span></strong> <span class="lang-id">dengan akses melalui route aplikasi yang terautorisasi, bukan link storage langsung.</span><span class="lang-en">with access routed through an authorized application endpoint instead of a direct storage link.</span>',
                ],
            },
            {
                type: 'heading',
                id: 'workflow-updates',
                level: 2,
                text: 'Workflow Activity',
            },
            {
                type: 'paragraph',
                html: '<span class="lang-id">Pembaruan workflow Activity difokuskan pada pengalaman kerja harian yang lebih mulus. Pengguna sekarang bisa membuka detail task, melakukan edit, dan membuat task baru dari dashboard modal yang sama, sehingga alur kerja terasa lebih konsisten dan lebih cepat dipindahkan antarstatus.</span><span class="lang-en">The Activity workflow update focuses on a smoother day-to-day experience. Users can now open task details, edit, and create new tasks from the same dashboard modal flow, making the experience more consistent and faster to move through statuses.</span>',
            },
            {
                type: 'status-list',
                items: [
                    {
                        label: '<span class="lang-id">Modal Dashboard</span><span class="lang-en">Dashboard Modal</span>',
                        description: '<span class="lang-id">Task detail dan form edit/create tetap berada di dalam dashboard supaya konteks kerja tidak terputus.</span><span class="lang-en">Task detail and create/edit forms stay inside the dashboard so work context is not broken.</span>',
                        color: 'blue',
                    },
                    {
                        label: '<span class="lang-id">Stabil Setelah Drag</span><span class="lang-en">Stable After Drag</span>',
                        description: '<span class="lang-id">Saat drag dibatalkan, board kembali ke state terbaru yang sama dengan data server.</span><span class="lang-en">When a drag is cancelled, the board returns to the latest state that matches server data.</span>',
                        color: 'emerald',
                    },
                    {
                        label: '<span class="lang-id">Owner Lebih Jelas</span><span class="lang-en">Clearer Ownership</span>',
                        description: '<span class="lang-id">Kalender Activity lebih mudah dipindai karena owner tiap task kini terlihat lebih tegas.</span><span class="lang-en">The Activity calendar is easier to scan because each task owner is now more visible.</span>',
                        color: 'amber',
                    },
                ],
            },
            {
                type: 'heading',
                id: 'access-updates',
                level: 2,
                text: 'Akses & Navigasi',
            },
            {
                type: 'paragraph',
                html: '<span class="lang-id">Perubahan pada akses dan navigasi berfokus pada keamanan dan kelancaran kerja. Supporting document Purchase Request kini dibuka melalui route aplikasi yang di-authenticate, sementara pengguna multi-department kembali bisa berpindah departemen tanpa hambatan.</span><span class="lang-en">The access and navigation changes focus on security and smoother work. Purchase Request supporting documents now open through an authenticated application route, while multi-department users can switch departments again without friction.</span>',
            },
            {
                type: 'unordered-list',
                items: [
                    '<strong><span class="lang-id">Dokumen PR tidak lagi mengandalkan link storage langsung</span><span class="lang-en">PR documents no longer rely on direct storage links</span></strong> <span class="lang-id">sehingga akses lebih aman dan lebih mudah dikontrol.</span><span class="lang-en">so access is safer and easier to control.</span>',
                    '<strong><span class="lang-id">Switch department kembali berfungsi untuk user multi-department</span><span class="lang-en">Department switching works again for multi-department users</span></strong> <span class="lang-id">agar perpindahan konteks kerja tidak mengganggu aktivitas harian.</span><span class="lang-en">so switching work context does not interrupt daily operations.</span>',
                ],
            },
            {
                type: 'heading',
                id: 'reporting-updates',
                level: 2,
                text: 'Pembaruan Reporting',
            },
            {
                type: 'paragraph',
                html: '<span class="lang-id">Reporting Activity dan Cashflow juga ikut naik level pada update ini. Dashboard Activity kini menampilkan breakdown yang lebih dalam, sementara export report memberi gambaran yang lebih kaya untuk monitoring dan analisis tim.</span><span class="lang-en">Activity and Cashflow reporting also received an upgrade in this release. The Activity dashboard now shows deeper breakdowns, while the report export provides richer information for team monitoring and analysis.</span>',
            },
            {
                type: 'status-list',
                items: [
                    {
                        label: '<span class="lang-id">Breakdown Activity</span><span class="lang-en">Activity Breakdown</span>',
                        description: '<span class="lang-id">Dashboard Activity menampilkan insight yang lebih detail untuk membantu membaca distribusi kerja dengan cepat.</span><span class="lang-en">The Activity dashboard now shows more detailed insights to help users read work distribution quickly.</span>',
                        color: 'blue',
                    },
                    {
                        label: '<span class="lang-id">Export Report</span><span class="lang-en">Report Export</span>',
                        description: '<span class="lang-id">Hasil export kini lebih informatif untuk kebutuhan review, monitoring, dan berbagi data tim.</span><span class="lang-en">Exports are now more informative for review, monitoring, and sharing team data.</span>',
                        color: 'emerald',
                    },
                ],
            },
            {
                type: 'heading',
                id: 'impact',
                level: 2,
                text: 'Dampak untuk Pengguna',
            },
            {
                type: 'unordered-list',
                items: [
                    '<strong><span class="lang-id">Tim Activity</span><span class="lang-en">Activity teams</span></strong> <span class="lang-id">mendapat alur kerja yang lebih fokus karena task tetap berada di modal dashboard.</span><span class="lang-en">get a more focused workflow because tasks stay inside the dashboard modal.</span>',
                    '<strong><span class="lang-id">Pengguna multi-department</span><span class="lang-en">Multi-department users</span></strong> <span class="lang-id">bisa pindah departemen lagi tanpa kehilangan konteks kerja.</span><span class="lang-en">can switch departments again without losing work context.</span>',
                    '<strong><span class="lang-id">Pengguna yang memeriksa dokumen dan report</span><span class="lang-en">Users reviewing documents and reports</span></strong> <span class="lang-id">mendapat akses yang lebih aman dan ringkasan data yang lebih mudah dibaca.</span><span class="lang-en">get safer access and report summaries that are easier to read.</span>',
                ],
            },
            {
                type: 'callout',
                variant: 'tip',
                title: 'Fokus Update',
                body: '<span class="lang-id">V3.0.2 menutup beberapa perbaikan penting dari tanggal <strong>30-31 Maret 2026</strong> dengan fokus pada workflow yang lebih mulus, akses yang lebih aman, dan reporting yang lebih berguna untuk pekerjaan harian.</span><span class="lang-en">V3.0.2 wraps up several important fixes from <strong>March 30-31, 2026</strong> with a focus on smoother workflows, safer access, and more useful reporting for daily work.</span>',
            },
        ],
    };
