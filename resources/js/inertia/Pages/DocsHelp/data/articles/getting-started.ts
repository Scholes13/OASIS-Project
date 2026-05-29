import type { Article } from '../types';

export const GettingStartedArticles: Article[] = [
    {
        id: 'welcome-to-oasis',
        category: 'getting-started',
        title: 'Selamat Datang di OASIS',
        description: 'Pelajari cara menggunakan sistem OASIS untuk mempermudah operasional pekerjaan Anda.',
        author: 'Pramuji Arif Y',
        updatedAt: '2026-03-01',
        toc: [
            { id: 'overview', label: 'Overview' },
            { id: 'dashboard', label: 'Mengenal Dashboard' },
            { id: 'profil', label: 'Pengaturan Profil' },
        ],
        content: [
            {
                type: 'paragraph',
                id: 'overview',
                html: 'Sistem ini dirancang untuk mempermudah Anda dalam melakukan permintaan pembelian (Purchase Request), permintaan stok dari inventaris (Stock Request), dan pelacakan aktivitas. Ikuti panduan singkat ini agar Anda dapat mulai menggunakan fitur inti kami.',
            },
            {
                type: 'heading',
                id: 'dashboard',
                level: 2,
                text: 'Mengenal Dashboard',
            },
            {
                type: 'paragraph',
                html: 'Setelah Anda berhasil login, halaman pertama yang Anda lihat adalah Dashboard. Fitur ini menyajikan ringkasan keseluruhan aktivitas Anda dalam sistem.',
            },
            {
                type: 'unordered-list',
                items: [
                    'Lihat notifikasi tugas dan dokumen terbaru di pojok kanan atas layar Anda (Ikon Lonceng).',
                    'Statistik permintaan <em>Pending</em>, <em>Approved</em>, dan <em>Rejected</em> membantu Anda memonitor dokumen mana yang masih menggantung.',
                    'Panel akses cepat di bawah statistik memungkinkan Anda meloncat langsung ke modul Purchase atau Stock dengan sekali klik.',
                ],
            },
            {
                type: 'heading',
                id: 'profil',
                level: 2,
                text: 'Pengaturan Profil',
            },
            {
                type: 'paragraph',
                html: 'Sebelum mengajukan dokumen apa pun, luangkan waktu sejenak untuk memeriksa kelengkapan data diri Anda:',
            },
            {
                type: 'ordered-list',
                items: [
                    'Buka menu Profil Anda di sudut kanan atas lalu pilih <strong>My Profile</strong>.',
                    'Pastikan <strong>Nama Lengkap</strong>, <strong>Email</strong>, dan <strong>Departemen</strong> Anda sudah sesuai.',
                    'Data departemen ini sangat penting karena ia akan <strong>menentukan alur persetujuan (Approval Workflow)</strong>. Jika departemen salah, PR Anda akan tersasar ke Manager dari departemen lain.',
                ],
            },
            {
                type: 'callout',
                variant: 'tip',
                title: 'Tip Navigasi',
                body: 'Gunakan sidebar di sebelah kiri untuk menavigasi antar modul. Anda dapat mengecilkan (collapse) sidebar menggunakan ikon di paling bawah untuk memperlebar area kerja layar Anda.',
            },
        ],
    },
    {
        id: 'login-and-password',
        category: 'getting-started',
        title: 'Login & Ganti Password',
        description: 'Cara login pertama kali dan mengubah kata sandi Anda.',
        author: 'Pramuji Arif Y',
        updatedAt: '2026-03-01',
        toc: [
            { id: 'overview', label: 'Overview' },
            { id: 'login', label: 'Cara Login' },
            { id: 'ganti-password', label: 'Ganti Password' },
        ],
        content: [
            {
                type: 'paragraph',
                id: 'overview',
                html: 'Setiap karyawan baru akan menerima kredensial login melalui email dari Admin IT. Berikut panduan lengkap login dan pengelolaan password.',
            },
            {
                type: 'heading',
                id: 'login',
                level: 2,
                text: 'Cara Login',
            },
            {
                type: 'ordered-list',
                items: [
                    'Buka browser dan akses URL sistem yang diberikan oleh Admin IT.',
                    'Masukkan <strong>Email</strong> dan <strong>Password</strong> yang Anda terima.',
                    'Klik tombol <strong>Login</strong>. Anda akan diarahkan ke Dashboard.',
                ],
            },
            {
                type: 'heading',
                id: 'ganti-password',
                level: 2,
                text: 'Ganti Password',
            },
            {
                type: 'ordered-list',
                items: [
                    'Klik avatar/nama Anda di pojok kanan atas.',
                    'Pilih <strong>My Profile</strong>.',
                    'Scroll ke bagian <strong>Change Password</strong>.',
                    'Masukkan password lama, password baru, dan konfirmasi password baru.',
                    'Klik <strong>Save</strong>.',
                ],
            },
            {
                type: 'callout',
                variant: 'info',
                title: 'Lupa Password?',
                body: 'Jika Anda lupa password, hubungi Admin IT untuk melakukan reset. Saat ini fitur "Forgot Password" melalui email belum tersedia.',
            },
        ],
    },
    {
        id: 'business-unit-switching',
        category: 'getting-started',
        title: 'Mengenal Business Unit & Cara Switch',
        description: 'Memahami konsep Business Unit dan cara berpindah antar unit.',
        author: 'Pramuji Arif Y',
        updatedAt: '2026-03-01',
        toc: [
            { id: 'overview', label: 'Overview' },
            { id: 'apa-itu-bu', label: 'Apa itu Business Unit?' },
            { id: 'cara-switch', label: 'Cara Switch BU' },
        ],
        content: [
            {
                type: 'paragraph',
                id: 'overview',
                html: 'OASIS mendukung operasi multi-perusahaan (multi Business Unit). Jika Anda memiliki akses ke lebih dari satu BU, Anda perlu memahami cara berpindah konteks.',
            },
            {
                type: 'heading',
                id: 'apa-itu-bu',
                level: 2,
                text: 'Apa itu Business Unit?',
            },
            {
                type: 'paragraph',
                html: 'Business Unit (BU) adalah entitas perusahaan yang terpisah di dalam grup WNS. Setiap BU memiliki data, approval workflow, dan nomor dokumen sendiri. Data antar BU <strong>tidak saling tercampur</strong>.',
            },
            {
                type: 'heading',
                id: 'cara-switch',
                level: 2,
                text: 'Cara Switch Business Unit',
            },
            {
                type: 'ordered-list',
                items: [
                    'Lihat nama Business Unit aktif di <strong>header navbar</strong> bagian atas.',
                    'Klik dropdown nama BU tersebut.',
                    'Pilih Business Unit tujuan dari daftar yang muncul.',
                    'Dashboard dan seluruh data akan otomatis berubah sesuai BU yang dipilih.',
                ],
            },
            {
                type: 'callout',
                variant: 'warning',
                title: 'Perhatian',
                body: 'Setelah switch BU, pastikan Anda memeriksa konteks data (terutama Dashboard dan daftar PR) karena semua data yang ditampilkan akan berubah sesuai BU yang aktif.',
            },
        ],
    },

    // ──────────────────────────────────────────────
    // PURCHASE REQUEST
    // ──────────────────────────────────────────────
];
