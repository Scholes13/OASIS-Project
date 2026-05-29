import type { Article } from '../../types';

export const ChangelogV304Article: Article =
{
        id: 'changelog-v3-0-4',
        category: 'changelog',
        title: 'OASIS V3.0.4 - Notification Center, Task Comments & Error Page Redesign',
        description: 'Update yang menghadirkan Notification Center real-time, komentar pada task Activity, halaman error yang lebih modern, serta perbaikan stabilitas navigasi dan session.',
        author: 'Pramuji Arif Y',
        updatedAt: '2026-04-23',
        bilingual: true,
        toc: [
            { id: 'overview', label: 'Ringkasan Update' },
            { id: 'main-updates', label: 'Peningkatan Utama' },
            { id: 'notification-center', label: 'Notification Center' },
            { id: 'activity-updates', label: 'Activity Module' },
            { id: 'ux-updates', label: 'UX & Error Pages' },
            { id: 'stability-fixes', label: 'Stabilitas & Perbaikan' },
            { id: 'impact', label: 'Dampak untuk Pengguna' },
        ],
        content: [
            {
                type: 'callout',
                variant: 'info',
                title: 'Update V3.0.4 - 23 April 2026',
                body: '<span class="lang-id">Update ini merangkum peningkatan user-facing yang dikirim dari <strong>17 sampai 23 April 2026</strong>, dengan fokus pada Notification Center real-time, fitur komentar task di Activity, halaman error yang lebih modern, serta perbaikan stabilitas session dan navigasi.</span><span class="lang-en">This update summarizes user-facing improvements shipped from <strong>April 17 to April 23, 2026</strong>, focused on a real-time Notification Center, task comments in Activity, modernized error pages, and session/navigation stability fixes.</span>',
            },
            {
                type: 'heading',
                id: 'overview',
                level: 2,
                text: 'Ringkasan Update',
            },
            {
                type: 'paragraph',
                html: '<span class="lang-id">OASIS V3.0.4 adalah update yang membawa tiga perubahan besar: <strong>Notification Center</strong> yang memungkinkan pengguna menerima notifikasi real-time langsung di dalam aplikasi, <strong>Task Comments</strong> yang memungkinkan diskusi langsung di dalam task Activity, dan <strong>Error Pages</strong> yang didesain ulang dengan ilustrasi modern. Selain itu, beberapa perbaikan stabilitas penting juga disertakan untuk memastikan pengalaman kerja harian yang lebih mulus.</span><span class="lang-en">OASIS V3.0.4 is an update that brings three major changes: a <strong>Notification Center</strong> that lets users receive real-time notifications directly inside the application, <strong>Task Comments</strong> that enable discussion within Activity tasks, and <strong>Error Pages</strong> redesigned with modern illustrations. Several important stability fixes are also included to ensure a smoother daily work experience.</span>',
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
                    '<strong><span class="lang-id">Notification Center hadir untuk notifikasi real-time</span><span class="lang-en">Notification Center arrives for real-time notifications</span></strong> <span class="lang-id">dengan dukungan broadcasting via Laravel Reverb, sehingga notifikasi approval, task assignment, dan alert penting lainnya langsung muncul tanpa perlu refresh halaman.</span><span class="lang-en">with broadcasting support via Laravel Reverb, so approval notifications, task assignments, and other important alerts appear instantly without page refresh.</span>',
                    '<strong><span class="lang-id">Task di Activity kini mendukung komentar</span><span class="lang-en">Activity tasks now support comments</span></strong> <span class="lang-id">sehingga anggota tim bisa berdiskusi, memberikan update, atau menanyakan detail langsung di dalam konteks task tanpa harus berpindah ke aplikasi chat.</span><span class="lang-en">so team members can discuss, provide updates, or ask for details directly within the task context without switching to a chat application.</span>',
                    '<strong><span class="lang-id">Halaman error didesain ulang dengan ilustrasi artistik</span><span class="lang-en">Error pages redesigned with artistic illustrations</span></strong> <span class="lang-id">untuk pengalaman yang lebih ramah saat pengguna menemui halaman 403, 404, 419, 500, atau 503 — dengan navigasi kembali yang lebih jelas.</span><span class="lang-en">for a friendlier experience when users encounter 403, 404, 419, 500, or 503 pages — with clearer navigation back.</span>',
                    '<strong><span class="lang-id">Session expired kini redirect ke login</span><span class="lang-en">Expired sessions now redirect to login</span></strong> <span class="lang-id">alih-alih menampilkan halaman error 419, sehingga pengguna bisa langsung login kembali tanpa kebingungan.</span><span class="lang-en">instead of showing a 419 error page, so users can immediately log back in without confusion.</span>',
                    '<strong><span class="lang-id">Paginasi PR/ST dan task card dashboard diperbaiki</span><span class="lang-en">PR/ST pagination and dashboard task cards fixed</span></strong> <span class="lang-id">agar tampilan daftar dokumen dan kartu tugas di dashboard lebih konsisten dan mudah dibaca.</span><span class="lang-en">so document lists and task cards on the dashboard display more consistently and are easier to read.</span>',
                ],
            },
            {
                type: 'heading',
                id: 'notification-center',
                level: 2,
                text: 'Notification Center',
            },
            {
                type: 'paragraph',
                html: '<span class="lang-id">Notification Center adalah fitur baru yang menjadi pusat informasi real-time di OASIS. Dengan dukungan <strong>Laravel Reverb</strong> sebagai WebSocket server, notifikasi kini dikirim secara instan ke browser pengguna tanpa perlu refresh halaman. Fitur ini mencakup infrastruktur broadcasting yang sepenuhnya baru dan terintegrasi dengan alur kerja yang sudah ada.</span><span class="lang-en">The Notification Center is a new feature that serves as the real-time information hub in OASIS. Powered by <strong>Laravel Reverb</strong> as the WebSocket server, notifications are now delivered instantly to the user\'s browser without page refresh. This feature includes an entirely new broadcasting infrastructure integrated with existing workflows.</span>',
            },
            {
                type: 'status-list',
                items: [
                    {
                        label: '<span class="lang-id">Notifikasi Real-Time</span><span class="lang-en">Real-Time Notifications</span>',
                        description: '<span class="lang-id">Alert untuk approval request, task assignment, dan event penting lainnya langsung muncul di ikon lonceng tanpa refresh halaman.</span><span class="lang-en">Alerts for approval requests, task assignments, and other important events appear instantly on the bell icon without page refresh.</span>',
                        color: 'blue',
                    },
                    {
                        label: '<span class="lang-id">Broadcasting Infrastructure</span><span class="lang-en">Broadcasting Infrastructure</span>',
                        description: '<span class="lang-id">Infrastruktur WebSocket baru berbasis Laravel Reverb dan Laravel Echo yang menjadi fondasi untuk fitur real-time di masa depan.</span><span class="lang-en">New WebSocket infrastructure based on Laravel Reverb and Laravel Echo that serves as the foundation for future real-time features.</span>',
                        color: 'emerald',
                    },
                    {
                        label: '<span class="lang-id">Cross-Module Hardening</span><span class="lang-en">Cross-Module Hardening</span>',
                        description: '<span class="lang-id">Penguatan integrasi notifikasi di seluruh modul (Activity, Purchasing, Approvals) agar alert terkirim secara konsisten.</span><span class="lang-en">Strengthened notification integration across all modules (Activity, Purchasing, Approvals) so alerts are delivered consistently.</span>',
                        color: 'amber',
                    },
                ],
            },
            {
                type: 'heading',
                id: 'activity-updates',
                level: 2,
                text: 'Activity Module',
            },
            {
                type: 'paragraph',
                html: '<span class="lang-id">Modul Activity mendapat dua peningkatan signifikan: <strong>Task Comments</strong> untuk kolaborasi langsung di dalam task, dan <strong>Quick-Status timestamps</strong> yang memberikan visibilitas lebih jelas kapan sebuah task berpindah status. Modal task juga diperkuat agar tetap stabil setelah operasi komentar.</span><span class="lang-en">The Activity module receives two significant enhancements: <strong>Task Comments</strong> for direct collaboration within tasks, and <strong>Quick-Status timestamps</strong> that provide clearer visibility into when a task changes status. Task modals are also hardened to remain stable after comment operations.</span>',
            },
            {
                type: 'status-list',
                items: [
                    {
                        label: '<span class="lang-id">Komentar Task</span><span class="lang-en">Task Comments</span>',
                        description: '<span class="lang-id">Anggota tim kini bisa menambahkan komentar di dalam task untuk diskusi, update progress, atau klarifikasi — tanpa perlu berpindah ke aplikasi lain.</span><span class="lang-en">Team members can now add comments inside tasks for discussion, progress updates, or clarification — without switching to another application.</span>',
                        color: 'blue',
                    },
                    {
                        label: '<span class="lang-id">Quick-Status Timestamps</span><span class="lang-en">Quick-Status Timestamps</span>',
                        description: '<span class="lang-id">Setiap perpindahan status task (To Do, In Progress, Done) kini tercatat waktunya, membantu supervisor memantau kecepatan penyelesaian tugas.</span><span class="lang-en">Every task status transition (To Do, In Progress, Done) is now timestamped, helping supervisors monitor task completion speed.</span>',
                        color: 'emerald',
                    },
                    {
                        label: '<span class="lang-id">Modal Stabil Setelah Komentar</span><span class="lang-en">Modal Stable After Comments</span>',
                        description: '<span class="lang-id">Modal task tidak lagi tertutup atau kehilangan state setelah menambah, mengedit, atau menghapus komentar.</span><span class="lang-en">Task modals no longer close or lose state after adding, editing, or deleting comments.</span>',
                        color: 'amber',
                    },
                ],
            },
            {
                type: 'heading',
                id: 'ux-updates',
                level: 2,
                text: 'UX & Error Pages',
            },
            {
                type: 'paragraph',
                html: '<span class="lang-id">Halaman error (403, 404, 419, 500, 503) didesain ulang sepenuhnya dengan ilustrasi SVG artistik yang memberikan pengalaman lebih ramah saat pengguna menemui kendala. Layout frameless yang bersih memastikan fokus tetap pada pesan error dan navigasi kembali.</span><span class="lang-en">Error pages (403, 404, 419, 500, 503) have been completely redesigned with artistic SVG illustrations that provide a friendlier experience when users encounter issues. A clean frameless layout ensures focus stays on the error message and navigation back.</span>',
            },
            {
                type: 'unordered-list',
                items: [
                    '<strong><span class="lang-id">Ilustrasi SVG artistik</span><span class="lang-en">Artistic SVG illustrations</span></strong> <span class="lang-id">menggantikan halaman error default yang polos, memberikan identitas visual yang lebih profesional.</span><span class="lang-en">replace the plain default error pages, providing a more professional visual identity.</span>',
                    '<strong><span class="lang-id">Layout frameless modern</span><span class="lang-en">Modern frameless layout</span></strong> <span class="lang-id">tanpa sidebar atau navbar, sehingga halaman error terasa bersih dan tidak membingungkan.</span><span class="lang-en">without sidebar or navbar, so error pages feel clean and uncluttered.</span>',
                    '<strong><span class="lang-id">Session expired langsung ke login</span><span class="lang-en">Expired session goes straight to login</span></strong> <span class="lang-id">alih-alih menampilkan error 419, pengguna langsung diarahkan ke halaman login untuk pengalaman yang lebih mulus.</span><span class="lang-en">instead of showing a 419 error, users are redirected straight to the login page for a smoother experience.</span>',
                ],
            },
            {
                type: 'heading',
                id: 'stability-fixes',
                level: 2,
                text: 'Stabilitas & Perbaikan',
            },
            {
                type: 'paragraph',
                html: '<span class="lang-id">Beberapa perbaikan stabilitas penting disertakan untuk memastikan pengalaman kerja harian yang lebih andal di seluruh modul.</span><span class="lang-en">Several important stability fixes are included to ensure a more reliable daily work experience across all modules.</span>',
            },
            {
                type: 'unordered-list',
                items: [
                    '<strong><span class="lang-id">Paginasi PR dan ST diperbaiki</span><span class="lang-en">PR and ST pagination fixed</span></strong> <span class="lang-id">agar navigasi halaman daftar dokumen tidak lagi menampilkan data yang salah atau kosong.</span><span class="lang-en">so document list page navigation no longer shows incorrect or empty data.</span>',
                    '<strong><span class="lang-id">Task card dashboard lebih jelas</span><span class="lang-en">Dashboard task cards are clearer</span></strong> <span class="lang-id">dengan tampilan informasi yang lebih konsisten dan mudah dipindai.</span><span class="lang-en">with more consistent and scannable information display.</span>',
                    '<strong><span class="lang-id">Guard null user di navigation middleware</span><span class="lang-en">Null user guard in navigation middleware</span></strong> <span class="lang-id">mencegah error 500 yang terjadi saat session user hilang di tengah navigasi.</span><span class="lang-en">prevents 500 errors that occurred when user session was lost during navigation.</span>',
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
                    '<strong><span class="lang-id">Semua pengguna</span><span class="lang-en">All users</span></strong> <span class="lang-id">kini menerima notifikasi real-time di dalam aplikasi melalui Notification Center, tanpa perlu menunggu email atau refresh halaman.</span><span class="lang-en">now receive real-time in-app notifications through the Notification Center, without waiting for email or page refresh.</span>',
                    '<strong><span class="lang-id">Tim Activity</span><span class="lang-en">Activity teams</span></strong> <span class="lang-id">bisa berdiskusi langsung di dalam task melalui fitur komentar, dan memantau perpindahan status task dengan timestamp yang lebih jelas.</span><span class="lang-en">can discuss directly within tasks through the comments feature, and monitor task status transitions with clearer timestamps.</span>',
                    '<strong><span class="lang-id">Pengguna yang mengalami session expired</span><span class="lang-en">Users experiencing expired sessions</span></strong> <span class="lang-id">langsung diarahkan ke halaman login alih-alih melihat halaman error yang membingungkan.</span><span class="lang-en">are redirected straight to the login page instead of seeing a confusing error page.</span>',
                    '<strong><span class="lang-id">Pengguna PR dan ST</span><span class="lang-en">PR and ST users</span></strong> <span class="lang-id">mendapat tampilan paginasi dan task card yang lebih konsisten di dashboard.</span><span class="lang-en">get more consistent pagination and task card display on the dashboard.</span>',
                ],
            },
            {
                type: 'callout',
                variant: 'tip',
                title: 'Fokus Update',
                body: '<span class="lang-id">V3.0.4 menghadirkan fondasi real-time baru melalui <strong>Notification Center</strong> dan <strong>broadcasting infrastructure</strong>, memperkaya kolaborasi tim dengan <strong>Task Comments</strong>, dan memoles pengalaman pengguna dengan <strong>error pages</strong> yang lebih modern serta perbaikan stabilitas di seluruh modul.</span><span class="lang-en">V3.0.4 introduces a new real-time foundation through the <strong>Notification Center</strong> and <strong>broadcasting infrastructure</strong>, enriches team collaboration with <strong>Task Comments</strong>, and polishes the user experience with more modern <strong>error pages</strong> and stability fixes across all modules.</span>',
            },
        ],
    };
