import type { Article } from './types';

/**
 * ╔═══════════════════════════════════════════════════════════════╗
 * ║  DOCS & HELP — ARTICLE DATABASE                              ║
 * ║                                                               ║
 * ║  Untuk menambah artikel baru:                                 ║
 * ║  1. Tambahkan object baru di array `articles` di bawah.       ║
 * ║  2. Pastikan `category` sesuai dengan CategoryKey.            ║
 * ║  3. Isi `toc` untuk Table of Contents sidebar.                ║
 * ║  4. Isi `content` dengan block-block konten.                  ║
 * ║  5. Set `popular: true` jika ingin tampil di landing page.    ║
 * ╚═══════════════════════════════════════════════════════════════╝
 */

export const articles: Article[] = [
    // ──────────────────────────────────────────────
    // GETTING STARTED
    // ──────────────────────────────────────────────
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
    {
        id: 'create-purchase-request',
        category: 'purchase-request',
        title: 'Panduan Purchase Request (PR)',
        description: 'Pelajari cara membuat permintaan pembelian untuk barang atau jasa dari vendor luar.',
        author: 'Pramuji Arif Y',
        updatedAt: '2026-03-01',
        popular: true,
        toc: [
            { id: 'overview', label: 'Overview' },
            { id: 'cara-membuat', label: 'Cara Membuat PR' },
            { id: 'status-dokumen', label: 'Status Dokumen' },
        ],
        content: [
            {
                type: 'paragraph',
                id: 'overview',
                html: 'Purchase Request digunakan ketika Anda membutuhkan barang atau jasa yang saat ini tidak tersedia di dalam inventaris perusahaan dan perlu dibeli dari vendor/supplier luar.',
            },
            {
                type: 'heading',
                id: 'cara-membuat',
                level: 2,
                text: 'Cara Membuat Purchase Request',
            },
            {
                type: 'ordered-list',
                intro: 'Ikuti langkah-langkah berikut untuk mengajukan PR baru:',
                items: [
                    'Buka menu <strong>Purchase Request</strong> dari sidebar navigasi utama.',
                    'Klik tombol <strong>Create New PR</strong> di pojok kanan atas layar.',
                    'Isi informasi dasar seperti Judul PR, Tanggal Kebutuhan (Date Required), dan Departemen pengaju.',
                    'Tambahkan item yang ingin dibeli dengan mengklik <strong>Add Item</strong> (isi Nama Barang, Kuantitas, Estimasi Harga, dan Spesifikasi teknis jika ada).',
                    'Lampirkan dokumen pendukung di tab Attachments (contoh: penawaran harga dari vendor, brosur, atau persetujuan email).',
                    'Periksa kembali data Anda, lalu klik <strong>Submit</strong>. Permintaan Anda akan otomatis masuk ke alur persetujuan manajer terkait.',
                ],
            },
            {
                type: 'callout',
                variant: 'info',
                title: 'Catatan Penting',
                body: 'PR yang sudah di-submit tidak dapat diubah kembali oleh Anda. Jika Anda menyadari ada kesalahan, segera hubungi Approver pertama Anda untuk meminta status dokumen diubah menjadi <em>Revised</em> agar Anda bisa mengeditnya kembali.',
            },
            {
                type: 'heading',
                id: 'status-dokumen',
                level: 2,
                text: 'Status Dokumen',
            },
            {
                type: 'unordered-list',
                items: [
                    '<strong>Draft:</strong> PR belum di-submit, Anda masih bisa mengedit secara bebas.',
                    '<strong>Pending Approval:</strong> Menunggu persetujuan dari atasan atau pihak berwenang.',
                    '<strong>Approved:</strong> Disetujui sepenuhnya dan dilanjutkan ke Purchasing untuk pembuatan PO.',
                    '<strong>Rejected:</strong> PR ditolak permanen, proses dihentikan.',
                    '<strong>Revised:</strong> Dikembalikan ke pengaju untuk direvisi sebelum disubmit ulang.',
                ],
            },
        ],
    },
    {
        id: 'edit-and-resubmit-pr',
        category: 'purchase-request',
        title: 'Edit & Resubmit PR yang Ditolak',
        description: 'Cara mengedit dan mengajukan ulang Purchase Request yang pernah ditolak.',
        author: 'Pramuji Arif Y',
        updatedAt: '2026-03-01',
        toc: [
            { id: 'overview', label: 'Overview' },
            { id: 'edit-rejected', label: 'Edit PR Rejected' },
            { id: 'resubmit', label: 'Resubmit untuk Approval' },
        ],
        content: [
            {
                type: 'paragraph',
                id: 'overview',
                html: 'Jika PR Anda ditolak (Rejected), Anda masih memiliki kesempatan untuk memperbaiki data dan mengajukan ulang (Resubmit). Proses ini terdiri dari dua langkah terpisah: <strong>Edit</strong> dan <strong>Resubmit</strong>.',
            },
            {
                type: 'heading',
                id: 'edit-rejected',
                level: 2,
                text: 'Edit PR yang Rejected',
            },
            {
                type: 'ordered-list',
                items: [
                    'Buka halaman detail PR yang berstatus <strong>Rejected</strong>.',
                    'Klik tombol <strong>Edit</strong>.',
                    'Lakukan perubahan yang diperlukan (harga, item, deskripsi, dll).',
                    'Klik <strong>Save Changes</strong>. Status PR tetap Rejected — data hanya tersimpan.',
                ],
            },
            {
                type: 'heading',
                id: 'resubmit',
                level: 2,
                text: 'Resubmit untuk Approval',
            },
            {
                type: 'ordered-list',
                items: [
                    'Setelah selesai edit, kembali ke halaman detail PR.',
                    'Klik tombol <strong>Resubmit for Approval</strong>.',
                    'PR akan masuk kembali ke alur persetujuan dari awal.',
                    'Approval workflow (urutan approver) akan dipertahankan sesuai pengajuan awal.',
                ],
            },
            {
                type: 'callout',
                variant: 'info',
                title: 'QR Code Tetap Sama',
                body: 'QR code pada dokumen PR yang di-resubmit akan tetap sama dengan pengajuan pertama. Ini karena sistem mempertahankan timestamp asli untuk menjaga integritas verifikasi dokumen.',
            },
        ],
    },
    {
        id: 'pr-tracking-and-pdf',
        category: 'purchase-request',
        title: 'Tracking Status & Download PDF',
        description: 'Cara melacak status PR dan mengunduh dokumen PDF resmi.',
        author: 'Pramuji Arif Y',
        updatedAt: '2026-03-01',
        toc: [
            { id: 'overview', label: 'Overview' },
            { id: 'tracking', label: 'Tracking Status PR' },
            { id: 'download-pdf', label: 'Download PDF' },
        ],
        content: [
            {
                type: 'paragraph',
                id: 'overview',
                html: 'Anda dapat memantau progres PR kapan saja dan mengunduh dokumen PDF resmi setelah PR disetujui.',
            },
            {
                type: 'heading',
                id: 'tracking',
                level: 2,
                text: 'Tracking Status PR',
            },
            {
                type: 'ordered-list',
                items: [
                    'Buka menu <strong>Purchase Request</strong> dari sidebar.',
                    'Daftar semua PR Anda akan ditampilkan beserta status terkini.',
                    'Klik pada PR untuk melihat detail, termasuk <strong>Approval Timeline</strong> yang menunjukkan siapa yang sudah approve/reject dan kapan.',
                ],
            },
            {
                type: 'heading',
                id: 'download-pdf',
                level: 2,
                text: 'Download PDF',
            },
            {
                type: 'paragraph',
                html: 'Setelah PR berstatus <strong>Approved</strong>, Anda dapat mengunduh dokumen PDF resmi yang dilengkapi QR code untuk verifikasi:',
            },
            {
                type: 'ordered-list',
                items: [
                    'Buka detail PR yang sudah Approved.',
                    'Klik tombol <strong>Download PDF</strong> di bagian atas halaman.',
                    'File PDF akan otomatis terunduh dengan QR code verifikasi.',
                ],
            },
            {
                type: 'callout',
                variant: 'tip',
                title: 'Verifikasi QR Code',
                body: 'QR code pada PDF dapat di-scan untuk memverifikasi keaslian dokumen. Ini berguna saat dokumen dicetak dan dikirim ke pihak luar.',
            },
        ],
    },

    // ──────────────────────────────────────────────
    // STOCK REQUEST
    // ──────────────────────────────────────────────
    {
        id: 'stock-request-guide',
        category: 'stock-request',
        title: 'Panduan Stock Request',
        description: 'Pelajari cara mengajukan permintaan barang dari inventaris gudang perusahaan.',
        author: 'Pramuji Arif Y',
        updatedAt: '2026-03-01',
        toc: [
            { id: 'overview', label: 'Overview' },
            { id: 'langkah', label: 'Langkah Pengajuan' },
            { id: 'persetujuan', label: 'Proses & Pengambilan' },
        ],
        content: [
            {
                type: 'paragraph',
                id: 'overview',
                html: 'Berbeda dengan PR (Purchase Request), Stock Request dikhususkan untuk barang consumable seperti alat tulis, persediaan pantry, alat kebersihan, atau perlengkapan TI kecil yang statusnya In-Stock atau rutin dikelola oleh divisi Inventory/GA/Admin.',
            },
            {
                type: 'heading',
                id: 'langkah',
                level: 2,
                text: 'Langkah Pengajuan Stock Request',
            },
            {
                type: 'ordered-list',
                intro: 'Anda dapat menyelesaikan pembuatan Stock Request (SR) dalam 4 tahapan mudah:',
                items: [
                    'Akses modul <strong>Stock Request</strong> dari sidebar navigasi.',
                    'Klik tombol biru <strong>Create Request</strong>.',
                    'Isi informasi pengajuan dasar, seperti departemen yang akan menanggung penggunaan (pemakaian), dan catatan tambahan apabila ada pesan khusus.',
                    'Tambahkan barang ke keranjang dari daftar <strong>Master Items</strong> yang muncul (pilih jenis dan masukkan kuantitas yang dibutuhkan).',
                ],
            },
            {
                type: 'heading',
                id: 'persetujuan',
                level: 2,
                text: 'Proses Persetujuan & Pengambilan Barang',
            },
            {
                type: 'unordered-list',
                intro: 'Setelah dokumen di-submit, inilah alur perjalanannya:',
                items: [
                    'Dokumen secara otomatis dialihkan (routed) ke atasan/Manajer Anda untuk persetujuan penggunaan stok departemen.',
                    'Setelah disetujui (Approved), notifikasi tugas akan dikirim ke Admin Gudang / Admin GA.',
                    'Admin Gudang akan menyiapkan barang (Picking) dan memperbarui status dokumen menjadi <strong>Ready for Pickup</strong>.',
                    'Setelah Anda menerima barang secara fisik, status Stock Request ini akan ditutup secara otomatis (Completed) di dalam sistem.',
                ],
            },
            {
                type: 'callout',
                variant: 'info',
                title: 'Stok Kosong / Habis?',
                body: 'Jika Anda menemukan item dalam master list tetapi Anda tidak dapat menambahkannya karena peringatan "Out of Stock", ini berarti secara fisik barang tersebut sedang tidak ada. Segera lapor ke Admin Gudang, atau tunggu hingga mereka melakukan pengadaan internal.',
            },
        ],
    },
    {
        id: 'stock-vs-purchase',
        category: 'stock-request',
        title: 'Perbedaan Stock Request vs Purchase Request',
        description: 'Kapan harus menggunakan Stock Request dan kapan Purchase Request.',
        author: 'Pramuji Arif Y',
        updatedAt: '2026-03-01',
        popular: true,
        toc: [
            { id: 'overview', label: 'Overview' },
            { id: 'stock-request', label: 'Stock Request' },
            { id: 'purchase-request', label: 'Purchase Request' },
        ],
        content: [
            {
                type: 'paragraph',
                id: 'overview',
                html: 'Salah satu pertanyaan yang paling sering diajukan oleh pengguna baru adalah membedakan kapan mereka harus membuat dokumen "Stock Request" dan kapan harus menggunakan "Purchase Request". Kesalahan dalam memilih formulir ini dapat memperlambat pemenuhan kebutuhan kerja Anda karena salah divisi yang memproses.',
            },
            {
                type: 'heading',
                id: 'stock-request',
                level: 2,
                text: 'Stock Request (Permintaan Stok)',
            },
            {
                type: 'unordered-list',
                intro: 'Gunakan Stock Request jika barang yang Anda butuhkan memenuhi kriteria ini:',
                items: [
                    'Barang tersebut <strong>sudah tersedia</strong> atau memang secara rutin disimpan di Gudang/Pantry (In-House Inventory).',
                    'Contoh: ATK (Pulpen, Kertas HVS, Tinta Printer), Kopi, Teh, Sabun Cuci Tangan.',
                    'Alur persetujuannya (biasanya) hanya 1 level yaitu ke Manajer langsung, kemudian dokumen otomatis masuk ke Admin Gudang untuk pengambilan barang.',
                ],
            },
            {
                type: 'heading',
                id: 'purchase-request',
                level: 2,
                text: 'Purchase Request (Permintaan Pembelian)',
            },
            {
                type: 'unordered-list',
                intro: 'Gunakan Purchase Request jika:',
                items: [
                    'Barang/Jasa tersebut <strong>tidak ada di gudang</strong> dan harus dibeli dari vendor/supplier eksternal.',
                    'Sifatnya pengadaan baru, proyek, aset perusahaan (Laptop, Mesin), atau sewa layanan (Lisensi Software, Jasa Konsultan).',
                    'Alur persetujuannya lebih panjang karena melibatkan departemen Finance dan tim Purchasing.',
                ],
            },
            {
                type: 'callout',
                variant: 'warning',
                title: 'Penting!',
                body: 'Jika Anda merequest stok (Stock Request) tetapi barangnya ternyata habis di gudang, Admin Gudang yang akan men-trigger Purchase Request secara internal. Anda sebagai User cukup menunggu hingga stok tersebut di-restock oleh gudang.',
            },
        ],
    },

    // ──────────────────────────────────────────────
    // APPROVALS
    // ──────────────────────────────────────────────
    {
        id: 'approval-basics',
        category: 'approvals',
        title: 'Memahami Alur Persetujuan (Approvals)',
        description: 'Modul Approvals untuk pengguna dengan peran Manajer, Kepala Departemen, atau Direksi.',
        author: 'Pramuji Arif Y',
        updatedAt: '2026-03-01',
        toc: [
            { id: 'tindakan', label: 'Tindakan Persetujuan' },
            { id: 'alur-sekuensial', label: 'Alur Sekuensial' },
        ],
        content: [
            {
                type: 'heading',
                id: 'tindakan',
                level: 2,
                text: 'Tindakan yang Dapat Dilakukan',
            },
            {
                type: 'unordered-list',
                items: [
                    '<strong>Approve:</strong> Menyetujui dokumen agar dapat diproses ke tahap selanjutnya.',
                    '<strong>Reject:</strong> Menolak dokumen secara permanen (biasanya memerlukan alasan penolakan). Dokumen yang direject tidak dapat dilanjutkan.',
                    '<strong>Revise (Kembalikan):</strong> Mengembalikan dokumen ke pembuat (requester) agar diperbaiki sebelum disubmit ulang.',
                ],
            },
            {
                type: 'heading',
                id: 'alur-sekuensial',
                level: 2,
                text: 'Alur Sekuensial',
            },
            {
                type: 'paragraph',
                html: 'Persetujuan berjalan secara sekuensial (berurutan). Jika sebuah dokumen membutuhkan persetujuan dari Manager dan Direktur, maka Direktur tidak akan menerima notifikasi atau bisa menyetujui dokumen tersebut sebelum Manager memberikan persetujuannya.',
            },
        ],
    },
    {
        id: 'offline-approval',
        category: 'approvals',
        title: 'Offline Approval (Mark as Offline Approved)',
        description: 'Cara menggunakan fitur persetujuan offline saat approver tidak tersedia di sistem.',
        author: 'Pramuji Arif Y',
        updatedAt: '2026-03-01',
        popular: true,
        toc: [
            { id: 'overview', label: 'Overview' },
            { id: 'langkah', label: 'Langkah Penggunaan' },
            { id: 'kebijakan', label: 'Kebijakan Audit' },
        ],
        content: [
            {
                type: 'paragraph',
                id: 'overview',
                html: 'Dalam situasi tertentu, seorang pimpinan (Approver) mungkin sedang berada di lapangan, tidak memiliki akses internet, atau cuti tetapi memberikan otorisasi persetujuan melalui jalur lain (telepon, WhatsApp, atau menandatangani dokumen cetak/kertas). Untuk kasus ini, Admin atau sistem Administrator dapat menggunakan fitur <strong>Mark as Offline Approved</strong>.',
            },
            {
                type: 'heading',
                id: 'langkah',
                level: 2,
                text: 'Langkah-langkah Penggunaan',
            },
            {
                type: 'ordered-list',
                items: [
                    'Buka detail dokumen (PR/Stock Request) yang sedang berstatus <code>Pending Approval</code>.',
                    'Pastikan Anda login sebagai akun yang memiliki role <strong>Super Admin</strong> atau <strong>Approval Delegate</strong>.',
                    'Klik tombol <code>More Options (...)</code> di pojok kanan atas formulir.',
                    'Pilih opsi <strong>Mark as Offline Approved</strong>. Akan muncul pop-up form.',
                    'Pilih nama Approver sebenarnya yang persetujuannya Anda wakilkan.',
                    '<strong>Lampirkan Bukti Fisik:</strong> Ini adalah tahapan <strong class="text-red-600">Wajib</strong>. Upload foto/scan dari kertas yang sudah ditandatangani, atau screenshot email/chat persetujuan dari beliau.',
                    'Klik Submit. Status persetujuan akan berubah, namun sistem akan memberikan label <em>(Approved Offline by [Your Name])</em> di riwayat audit.',
                ],
            },
            {
                type: 'heading',
                id: 'kebijakan',
                level: 2,
                text: 'Kebijakan Audit',
            },
            {
                type: 'paragraph',
                html: 'Setiap persetujuan offline akan dipantau ketat dalam laporan Audit Trail. Penyalahgunaan fitur ini (menyetujui tanpa bukti otorisasi yang sah) dapat melanggar compliance/SOP perusahaan. Selalu pastikan lampiran yang diupload jelas dan dapat dipertanggungjawabkan kepada auditor internal/eksternal.',
            },
        ],
    },
    {
        id: 'email-approval-link',
        category: 'approvals',
        title: 'Approve via Email Link',
        description: 'Cara menyetujui dokumen melalui link yang dikirim ke email Anda.',
        author: 'Pramuji Arif Y',
        updatedAt: '2026-03-01',
        toc: [
            { id: 'overview', label: 'Overview' },
            { id: 'cara-approve', label: 'Cara Approve via Email' },
            { id: 'link-expired', label: 'Link Expired' },
        ],
        content: [
            {
                type: 'paragraph',
                id: 'overview',
                html: 'Ketika ada dokumen yang membutuhkan persetujuan Anda, sistem akan mengirimkan email notifikasi yang berisi link langsung untuk melakukan approve atau reject <strong>tanpa perlu login</strong> ke sistem.',
            },
            {
                type: 'heading',
                id: 'cara-approve',
                level: 2,
                text: 'Cara Approve via Email',
            },
            {
                type: 'ordered-list',
                items: [
                    'Buka email notifikasi dari OASIS dengan subjek "Approval Required".',
                    'Klik tombol <strong>Review & Approve</strong> di dalam email.',
                    'Anda akan diarahkan ke halaman approval publik (tidak perlu login).',
                    'Review detail dokumen, lalu pilih <strong>Approve</strong> atau <strong>Reject</strong>.',
                    'Tambahkan catatan jika diperlukan, lalu klik <strong>Submit</strong>.',
                ],
            },
            {
                type: 'heading',
                id: 'link-expired',
                level: 2,
                text: 'Link Expired',
            },
            {
                type: 'paragraph',
                html: 'Link approval memiliki masa berlaku <strong>3 hari</strong> sejak dikirim. Jika link sudah expired, Anda akan melihat pesan error. Untuk kasus ini, silakan login ke sistem dan approve langsung dari halaman Approvals.',
            },
            {
                type: 'callout',
                variant: 'info',
                title: 'Keamanan Link',
                body: 'Link approval menggunakan signed URL yang hanya berlaku 1 kali (one-time use). Setelah Anda approve/reject, link tersebut tidak bisa digunakan lagi.',
            },
        ],
    },

    // ──────────────────────────────────────────────
    // ACTIVITY TRACKING
    // ──────────────────────────────────────────────
    {
        id: 'activity-tracking-overview',
        category: 'activity-tracking',
        title: 'Activity Tracking',
        description: 'Mencatat, menugaskan, dan memantau pekerjaan/tugas di dalam tim secara real-time.',
        author: 'Pramuji Arif Y',
        updatedAt: '2026-03-01',
        toc: [
            { id: 'fitur-utama', label: 'Fitur Utama' },
            { id: 'views', label: 'Kanban vs List View' },
        ],
        content: [
            {
                type: 'heading',
                id: 'fitur-utama',
                level: 2,
                text: 'Fitur Utama',
            },
            {
                type: 'unordered-list',
                items: [
                    '<strong>Task Assignment:</strong> Memberikan tugas kepada anggota tim tertentu dengan tenggat waktu (due date).',
                    '<strong>Status Update:</strong> Mengubah status tugas dari <em>To Do</em>, <em>In Progress</em>, hingga <em>Done</em> menggunakan kanban board atau list view.',
                    '<strong>Time Logging:</strong> Mencatat waktu yang dihabiskan untuk menyelesaikan suatu tugas guna memonitor efisiensi dan beban kerja.',
                    '<strong>Backdated Task:</strong> Mencatat tugas yang sudah diselesaikan di masa lalu (bergantung pada konfigurasi kebijakan hari, umumnya maksimal mundur 3 hari).',
                ],
            },
            {
                type: 'heading',
                id: 'views',
                level: 2,
                text: 'Kanban vs List View',
            },
            {
                type: 'paragraph',
                html: 'OASIS menyediakan dua cara untuk melihat dan mengelola tugas:',
            },
            {
                type: 'unordered-list',
                items: [
                    '<strong>Kanban Board:</strong> Tampilan kolom visual (To Do → In Progress → Done). Cocok untuk melihat gambaran besar status semua tugas.',
                    '<strong>List View:</strong> Tampilan tabel dengan fitur sorting dan filter. Cocok untuk pencarian spesifik dan pengelolaan detail.',
                ],
            },
        ],
    },
    {
        id: 'backdated-task',
        category: 'activity-tracking',
        title: 'How to Create a Backdated Task',
        description: 'Cara mencatat tugas yang sudah dikerjakan di masa lalu.',
        author: 'Pramuji Arif Y',
        updatedAt: '2026-03-01',
        popular: true,
        toc: [
            { id: 'overview', label: 'Overview' },
            { id: 'prerequisites', label: 'Prerequisites' },
            { id: 'step-by-step-guide', label: 'Step-by-step Guide' },
            { id: 'troubleshooting', label: 'Troubleshooting' },
        ],
        content: [
            {
                type: 'paragraph',
                id: 'overview',
                html: 'Sometimes you might need to log work that was completed in the past. OASIS allows you to create tasks with a past date, subject to your department\'s configuration and approval policies.',
            },
            {
                type: 'callout',
                variant: 'info',
                title: 'Important Note',
                body: 'By default, you can only backdate tasks up to 3 working days. If you need to log activity older than that, you will be required to submit a Request Backdate Approval form.',
            },
            {
                type: 'heading',
                id: 'prerequisites',
                level: 2,
                text: 'Prerequisites',
            },
            {
                type: 'unordered-list',
                items: [
                    'You must have an active employee account.',
                    'You must be assigned to the relevant department for the task category.',
                ],
            },
            {
                type: 'heading',
                id: 'step-by-step-guide',
                level: 2,
                text: 'Step-by-step Guide',
            },
            {
                type: 'ordered-list',
                intro: 'Follow these steps to log a task that has already been completed:',
                items: [
                    'Navigate to My Tasks or the Activity Dashboard.',
                    'Click on the Create Task button in the top right corner.',
                    'In the Task Form, fill out the Basic Info (Title, Description).',
                    'Locate the Task Date field. Click the calendar icon and select the past date.',
                    'Save the task. If within 3 days, it will be automatically approved.',
                ],
            },
            {
                type: 'heading',
                id: 'troubleshooting',
                level: 2,
                text: 'Troubleshooting',
            },
            {
                type: 'paragraph',
                html: 'If you cannot select a past date, check with your department admin if your permissions have been restricted or if you have pending unresolved backdated requests.',
            },
        ],
    },

    // ──────────────────────────────────────────────
    // CASHFLOW PROJECTION
    // ──────────────────────────────────────────────
    {
        id: 'cashflow-projection-guide',
        category: 'cashflow-projection',
        title: 'Apa itu Cashflow Projection?',
        description: 'Panduan lengkap modul Cashflow Projection: perencanaan arus kas, input finance, dan proyeksi bulanan.',
        author: 'Pramuji Arif Y',
        updatedAt: '2026-03-03',
        popular: true,
        toc: [
            { id: 'overview', label: 'Overview' },
            { id: 'siapa-mengisi', label: 'Siapa yang Mengisi?' },
            { id: 'alur-kerja', label: 'Alur Kerja' },
            { id: 'input-departemen', label: 'Input Departemen' },
            { id: 'input-finance', label: 'Input Finance' },
            { id: 'dashboard-proyeksi', label: 'Dashboard Proyeksi' },
        ],
        content: [
            {
                type: 'paragraph',
                id: 'overview',
                html: '<strong>Cashflow Projection</strong> adalah modul perencanaan arus kas (cash flow) perusahaan yang memungkinkan setiap departemen dan tim Finance untuk bersama-sama menyusun proyeksi pemasukan dan pengeluaran dalam satu tahun berjalan. Tujuannya adalah memberikan gambaran akurat tentang posisi keuangan perusahaan di masa mendatang, sehingga manajemen dapat mengambil keputusan strategis lebih awal — misalnya kapan harus menunda pengeluaran besar, atau kapan ada peluang untuk berinvestasi.',
            },
            {
                type: 'callout',
                variant: 'info',
                title: 'Mengapa Cashflow Projection Penting?',
                body: 'Tanpa proyeksi arus kas, perusahaan bisa mengalami "cash crunch" — situasi di mana uang tunai tidak cukup untuk membiayai operasional meskipun secara laporan keuangan perusahaan terlihat profit. Modul ini membantu mencegah kondisi tersebut dengan visibilitas penuh terhadap rencana masuk dan keluar uang.',
            },
            {
                type: 'heading',
                id: 'siapa-mengisi',
                level: 2,
                text: 'Siapa yang Harus Mengisi?',
            },
            {
                type: 'paragraph',
                html: 'Modul Cashflow Projection melibatkan dua kelompok pengguna utama, masing-masing dengan tanggung jawab yang berbeda:',
            },
            {
                type: 'ordered-list',
                items: [
                    '<strong>Semua Departemen (User Biasa):</strong> Setiap departemen wajib menginput rencana pengeluaran (<em>Cash Out</em>) dan pemasukan (<em>Cash In</em>) mereka melalui halaman <strong>Entries</strong>. Contoh: Departemen Marketing menginput rencana biaya event, Departemen IT menginput biaya server bulanan, Departemen HR menginput rencana perekrutan, dll.',
                    '<strong>Tim Finance / CFC (Cash Flow Controller):</strong> Tim Finance memiliki akses tambahan ke <strong>Dashboard Proyeksi</strong> dan halaman <strong>Finance Inputs</strong>. Mereka bertanggung jawab menginput data keuangan tingkat perusahaan seperti Cash On Hand, estimasi piutang, dan suntikan modal.',
                ],
            },
            {
                type: 'callout',
                variant: 'warning',
                title: 'Akses Berbeda',
                body: 'User biasa (non-finance) hanya dapat mengakses halaman Entries untuk menginput proyeksi departemen mereka. Halaman Dashboard dan Finance Inputs hanya bisa diakses oleh user dengan role Finance atau CFC.',
            },
            {
                type: 'heading',
                id: 'alur-kerja',
                level: 2,
                text: 'Alur Kerja Cashflow Projection',
            },
            {
                type: 'step-list',
                steps: [
                    {
                        title: 'Departemen Menginput Proyeksi',
                        body: 'Masing-masing departemen membuka halaman <strong>Entries</strong> dan menambahkan item proyeksi pengeluaran maupun pemasukan untuk bulan-bulan yang relevan.',
                    },
                    {
                        title: 'Finance Menginput Data Keuangan',
                        body: 'Tim Finance melengkapi data Cash On Hand, estimasi piutang, revenue upcoming event, suntikan modal, dan pemasukan lainnya melalui halaman <strong>Finance Inputs</strong>.',
                    },
                    {
                        title: 'Sistem Menghitung Proyeksi Otomatis',
                        body: 'Sistem secara otomatis menggabungkan semua data dari departemen dan finance untuk menghasilkan ringkasan harian (Daily Summary) dan bulanan (Monthly Summary).',
                    },
                    {
                        title: 'Dashboard Menampilkan Hasil',
                        body: 'Finance dapat melihat grafik proyeksi, closing balance per bulan, dan peringatan jika saldo diprediksi turun di bawah batas minimum (Rp 200.000.000).',
                    },
                ],
            },
            {
                type: 'heading',
                id: 'input-departemen',
                level: 2,
                text: 'Apa yang Diinput oleh Departemen?',
            },
            {
                type: 'paragraph',
                html: 'Setiap departemen menginput <strong>Line Item</strong> melalui halaman Entries. Berikut data yang perlu diisi untuk setiap item proyeksi:',
            },
            {
                type: 'unordered-list',
                items: [
                    '<strong>Tipe Arus:</strong> Pilih <em>Cash In</em> (pemasukan) atau <em>Cash Out</em> (pengeluaran).',
                    '<strong>Kategori:</strong> Pilih kategori transaksi sesuai template departemen Anda (contoh: Gaji, Pembelian Aset, Biaya Event, dll).',
                    '<strong>Tanggal Transaksi:</strong> Perkiraan kapan transaksi akan terjadi.',
                    '<strong>Due Date:</strong> Tanggal jatuh tempo pembayaran (opsional).',
                    '<strong>Jumlah (Amount):</strong> Nilai estimasi dalam Rupiah.',
                    '<strong>Deskripsi:</strong> Penjelasan singkat mengenai transaksi ini.',
                    '<strong>Catatan:</strong> Informasi tambahan jika diperlukan (opsional).',
                ],
            },
            {
                type: 'callout',
                variant: 'tip',
                title: 'Tips Input',
                body: 'Centang "Estimated Date" jika Anda belum yakin dengan tanggal pasti transaksi. Ini membantu Finance memahami bahwa tanggal tersebut masih bersifat perkiraan dan bisa berubah.',
            },
            {
                type: 'heading',
                id: 'input-finance',
                level: 2,
                text: 'Apa yang Diinput oleh Finance?',
            },
            {
                type: 'paragraph',
                html: 'Tim Finance mengisi komponen pemasukan tingkat perusahaan melalui halaman <strong>Finance Inputs</strong> (atau Settings). Data ini diinput <strong>per bulan</strong> dan mencakup:',
            },
            {
                type: 'unordered-list',
                items: [
                    '<strong>Cash On Hand:</strong> Jumlah uang tunai dan saldo rekening yang tersedia saat ini. Ini menjadi opening balance untuk perhitungan bulan tersebut.',
                    '<strong>Estimasi Penerimaan Utang (Receivable Estimate):</strong> Perkiraan piutang yang akan tertagih pada bulan tersebut dari klien/customer.',
                    '<strong>Estimasi Revenue Upcoming Event:</strong> Pendapatan yang diperkirakan masuk dari event atau proyek yang akan datang.',
                    '<strong>Estimasi Suntikan Modal (Capital Injection):</strong> Dana tambahan dari pemegang saham atau investor yang direncanakan masuk.',
                    '<strong>Lain-lain (Other Income):</strong> Pemasukan lainnya yang tidak termasuk kategori di atas, seperti bunga deposito, pengembalian pajak, dll.',
                ],
            },
            {
                type: 'callout',
                variant: 'warning',
                title: 'Perhatian untuk Finance',
                body: 'Pastikan Cash On Hand diperbarui secara rutin setiap awal bulan. Data ini menjadi dasar perhitungan opening balance yang mempengaruhi akurasi seluruh proyeksi. Jika tidak diisi, sistem akan menghitung dari nol.',
            },
            {
                type: 'heading',
                id: 'dashboard-proyeksi',
                level: 2,
                text: 'Membaca Dashboard Proyeksi',
            },
            {
                type: 'paragraph',
                html: 'Dashboard Cashflow Projection (hanya untuk Finance) menampilkan beberapa komponen visual penting:',
            },
            {
                type: 'unordered-list',
                items: [
                    '<strong>Stats Cards:</strong> Menampilkan total Cash In, Cash Out, Net Cashflow, dan Closing Balance untuk bulan yang dipilih.',
                    '<strong>Projection Chart:</strong> Grafik visual yang menunjukkan tren arus kas harian selama bulan yang dipilih.',
                    '<strong>Monthly Summary Table:</strong> Tabel ringkasan 12 bulan yang menunjukkan opening balance, pemasukan, pengeluaran, net cashflow, dan closing balance per bulan.',
                    '<strong>Warning Indicator:</strong> Bulan yang diprediksi memiliki saldo di bawah batas minimum (Rp 200.000.000) akan ditandai dengan warna merah sebagai peringatan.',
                    '<strong>Recent Transactions:</strong> Daftar transaksi terbaru yang telah diinput oleh semua departemen.',
                ],
            },
        ],
    },

    // ──────────────────────────────────────────────
    // DASHBOARD
    // ──────────────────────────────────────────────
    {
        id: 'dashboard-overview',
        category: 'dashboard',
        title: 'Dashboard & Laporan',
        description: 'Dashboard memberikan gambaran menyeluruh tentang statistik dan data sistem secara visual.',
        author: 'Pramuji Arif Y',
        updatedAt: '2026-03-01',
        toc: [
            { id: 'fitur-dashboard', label: 'Fitur Dashboard' },
            { id: 'filter', label: 'Filter & Period' },
            { id: 'export', label: 'Export Laporan' },
        ],
        content: [
            {
                type: 'heading',
                id: 'fitur-dashboard',
                level: 2,
                text: 'Fitur Dashboard',
            },
            {
                type: 'unordered-list',
                items: [
                    '<strong>Statistik Permintaan:</strong> Melihat jumlah PR dan Stock Request yang pending, disetujui, atau ditolak dalam bentuk chart visual.',
                    '<strong>Filter Data:</strong> Anda dapat memfilter data laporan berdasarkan rentang tanggal, departemen, tipe tugas, atau status (aktif/selesai).',
                    '<strong>Export Laporan:</strong> Mengekspor data laporan operasional dan log aktivitas ke dalam format Excel (XLSX) atau PDF untuk kebutuhan audit atau rapat divisi.',
                ],
            },
            {
                type: 'heading',
                id: 'filter',
                level: 2,
                text: 'Filter & Period',
            },
            {
                type: 'paragraph',
                html: 'Dashboard mendukung filter per periode waktu (Hari ini, Minggu ini, Bulan ini, Kustom). Gunakan dropdown di bagian atas untuk mengubah periode data yang ditampilkan.',
            },
            {
                type: 'heading',
                id: 'export',
                level: 2,
                text: 'Export Laporan',
            },
            {
                type: 'paragraph',
                html: 'Klik tombol <strong>Export</strong> di pojok kanan atas dashboard untuk mengunduh data dalam format XLSX atau PDF. Filter yang aktif akan diterapkan pada data yang diekspor.',
            },
        ],
    },

    // ──────────────────────────────────────────────
    // CHANGELOG
    // ──────────────────────────────────────────────
    {
        id: 'changelog-v3-0-3',
        category: 'changelog',
        title: 'OASIS V3.0.3 - Activity Oversight, Workflow Parity & Cashflow Import Update',
        description: 'Minor update yang merangkum peningkatan monitoring Activity, penyempurnaan workflow PR/ST, serta pembaruan chart, export, dan import di Cashflow Projection.',
        author: 'Pramuji Arif Y',
        updatedAt: '2026-04-17',
        bilingual: true,
        toc: [
            { id: 'overview', label: 'Ringkasan Update' },
            { id: 'main-updates', label: 'Peningkatan Utama' },
            { id: 'activity-updates', label: 'Activity & Admin' },
            { id: 'purchasing-updates', label: 'Purchasing & Stock Request' },
            { id: 'cashflow-updates', label: 'Cashflow Projection' },
            { id: 'impact', label: 'Dampak untuk Pengguna' },
        ],
        content: [
            {
                type: 'callout',
                variant: 'info',
                title: 'Minor Update V3.0.3 - 17 April 2026',
                body: '<span class="lang-id">Update ini merangkum peningkatan user-facing yang dikirim dari <strong>6 sampai 17 April 2026</strong>, dengan fokus pada monitoring Activity yang lebih akurat, workflow task dan request yang lebih rapi, serta pengalaman Cashflow Projection yang lebih jelas untuk export dan import.</span><span class="lang-en">This update summarizes user-facing improvements shipped from <strong>April 6 to April 17, 2026</strong>, focused on more accurate Activity monitoring, smoother task and request workflows, and a clearer Cashflow Projection experience for exports and imports.</span>',
            },
            {
                type: 'heading',
                id: 'overview',
                level: 2,
                text: 'Ringkasan Update',
            },
            {
                type: 'paragraph',
                html: '<span class="lang-id">OASIS V3.0.3 adalah minor update yang memperkuat tiga area kerja utama sekaligus: <strong>Activity</strong>, <strong>Purchasing</strong>, dan <strong>Cashflow Projection</strong>. Admin dan supervisor sekarang mendapatkan visibilitas yang lebih konsisten saat melihat data lintas business unit atau fokus ke satu anggota tim, pengguna task mendapat alur modal yang lebih nyaman, modul Stock Request bergerak lebih dekat ke pengalaman Purchase Request, dan tim finance memperoleh export serta import cashflow yang lebih jelas dan lebih ketat.</span><span class="lang-en">OASIS V3.0.3 is a minor update that strengthens three major work areas at once: <strong>Activity</strong>, <strong>Purchasing</strong>, and <strong>Cashflow Projection</strong>. Admins and supervisors now get more consistent visibility when viewing cross-business-unit data or focusing on a single team member, task users get a more comfortable modal workflow, the Stock Request module moves closer to the Purchase Request experience, and finance teams gain clearer and stricter cashflow export and import flows.</span>',
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
                    '<strong><span class="lang-id">Activity Admin kini lebih akurat saat membaca data lintas business unit</span><span class="lang-en">Activity Admin is now more accurate when reading cross-business-unit data</span></strong> <span class="lang-id">dengan roll-up parent BU, filter departemen yang tidak lagi tertinggal setelah switch BU, dan total jam yang tampil lebih rapi tanpa angka pecahan yang aneh.</span><span class="lang-en">with parent-BU roll-up support, department filters that no longer get stuck after a BU switch, and cleaner total-hour values without strange floating-point tails.</span>',
                    '<strong><span class="lang-id">Workflow task Activity terasa lebih nyaman untuk penggunaan harian</span><span class="lang-en">The Activity task workflow feels more comfortable for daily use</span></strong> <span class="lang-id">berkat filter fokus anggota tim, modal yang lebih aman di layar pendek, opsi <em>create again</em>, dan tombol delete yang kembali jelas di detail task.</span><span class="lang-en">thanks to member-focus filtering, safer modals on short screens, an optional <em>create again</em> flow, and a clearly restored delete action in task details.</span>',
                    '<strong><span class="lang-id">Monitoring dan export Activity memberi gambaran partisipasi yang lebih lengkap</span><span class="lang-en">Activity monitoring and export now provide a more complete participation picture</span></strong> <span class="lang-id">karena user yang hanya ditag juga ikut dihitung di export, dan halaman detail admin terasa lebih ringan saat dibuka.</span><span class="lang-en">because users who are only tagged are now counted in exports, and the admin detail page feels lighter when opened.</span>',
                    '<strong><span class="lang-id">Stock Request kini semakin setara dengan Purchase Request</span><span class="lang-en">Stock Request is now much closer to Purchase Request parity</span></strong> <span class="lang-id">dengan resend email approval, akses bukti offline approval yang terproteksi, serta perbaikan submit form dan input tanggal.</span><span class="lang-en">with resend approval email, protected offline approval evidence access, and fixes for form submission and date input behavior.</span>',
                    '<strong><span class="lang-id">Cashflow Projection kini lebih jelas dibaca dan diekspor</span><span class="lang-en">Cashflow Projection is now clearer to read and export</span></strong> <span class="lang-id">melalui istilah <code>Saldo Proyeksi</code>, chart balance-first yang lebih bersih, dan export yang sudah menyertakan saldo proyeksi berjalan.</span><span class="lang-en">through the <code>Saldo Proyeksi</code> terminology, a cleaner balance-first chart, and exports that now include the running projected balance.</span>',
                    '<strong><span class="lang-id">Finance mendapat alur import Excel yang lebih enterprise-ready</span><span class="lang-en">Finance now gets a more enterprise-ready Excel import flow</span></strong> <span class="lang-id">dengan template unduhan resmi, dukungan create/update yang eksplisit, dan error per baris yang lebih mudah ditindaklanjuti.</span><span class="lang-en">with an official downloadable template, explicit create/update support, and row-level errors that are easier to follow up on.</span>',
                ],
            },
            {
                type: 'heading',
                id: 'activity-updates',
                level: 2,
                text: 'Activity & Admin',
            },
            {
                type: 'paragraph',
                html: '<span class="lang-id">Pembaruan Activity di versi ini berfokus pada dua kebutuhan utama: <strong>pengawasan yang lebih akurat</strong> untuk admin dan supervisor, serta <strong>workflow task yang lebih nyaman</strong> untuk pengguna harian. Tampilan admin kini lebih stabil saat digunakan dari parent business unit, filter anggota tim bisa dipakai untuk mengisolasi pekerjaan satu orang, dan berbagai titik gesekan di modal task sudah dirapikan.</span><span class="lang-en">The Activity updates in this version focus on two main needs: <strong>more accurate oversight</strong> for admins and supervisors, and a <strong>more comfortable task workflow</strong> for day-to-day users. The admin view is now more stable when used from a parent business unit, team-member filters can isolate one person’s work, and several friction points inside task modals have been cleaned up.</span>',
            },
            {
                type: 'status-list',
                items: [
                    {
                        label: '<span class="lang-id">Admin Scope Lebih Stabil</span><span class="lang-en">More Stable Admin Scope</span>',
                        description: '<span class="lang-id">Activity Admin kini bisa merangkum child BU dari parent BU, membersihkan filter departemen yang sudah tidak relevan setelah switch BU, dan menampilkan total jam dengan angka yang lebih rapi.</span><span class="lang-en">Activity Admin can now roll up child BUs from a parent BU, clear stale department filters after a BU switch, and show total hours with cleaner values.</span>',
                        color: 'blue',
                    },
                    {
                        label: '<span class="lang-id">Fokus per Member</span><span class="lang-en">Member Focus</span>',
                        description: '<span class="lang-id">Supervisor dapat memfilter dashboard dan task team berdasarkan satu anggota tim, dan filter ini ikut menyelaraskan data view maupun export.</span><span class="lang-en">Supervisors can now filter dashboards and team tasks by a single team member, and that filter stays aligned across views and exports.</span>',
                        color: 'emerald',
                    },
                    {
                        label: '<span class="lang-id">Modal Task Lebih Nyaman</span><span class="lang-en">More Comfortable Task Modals</span>',
                        description: '<span class="lang-id">Modal task kini lebih aman di viewport pendek, memiliki opsi <em>Ingin membuat task lagi?</em>, dan mengembalikan aksi delete yang jelas pada task yang bisa diedit.</span><span class="lang-en">Task modals now behave better on short viewports, include an optional <em>Create another task?</em> flow, and restore a clear delete action for editable tasks.</span>',
                        color: 'amber',
                    },
                    {
                        label: '<span class="lang-id">Export Peserta Lebih Lengkap</span><span class="lang-en">More Complete Participant Export</span>',
                        description: '<span class="lang-id">Export Activity sekarang menghitung user yang hanya ditag sebagai participant dan menambahkan kolom peserta yang lebih informatif di workbook.</span><span class="lang-en">Activity exports now count users who were only tagged as participants and add more informative participant columns to the workbook.</span>',
                        color: 'emerald',
                    },
                ],
            },
            {
                type: 'heading',
                id: 'purchasing-updates',
                level: 2,
                text: 'Purchasing & Stock Request',
            },
            {
                type: 'paragraph',
                html: '<span class="lang-id">Di sisi purchasing, fokus update ada pada <strong>paritas pengalaman</strong> antara Purchase Request dan Stock Request, plus perbaikan stabilitas pada form submit. Hasilnya, pengguna Stock Request kini mendapatkan CTA dan dokumen pendukung yang lebih setara, sementara create/edit flow kembali lebih dapat diandalkan.</span><span class="lang-en">On the purchasing side, the update focuses on <strong>experience parity</strong> between Purchase Request and Stock Request, plus better submit stability. As a result, Stock Request users now get more equivalent CTAs and supporting document access, while create/edit flows become more dependable again.</span>',
            },
            {
                type: 'unordered-list',
                items: [
                    '<strong><span class="lang-id">Stock Request kini mendukung resend email approval</span><span class="lang-en">Stock Request now supports resend approval email</span></strong> <span class="lang-id">agar follow-up ke approver tidak lagi terasa tertinggal dibanding Purchase Request.</span><span class="lang-en">so approver follow-up no longer lags behind the Purchase Request experience.</span>',
                    '<strong><span class="lang-id">Dokumen offline approval dibuka melalui jalur yang lebih aman</span><span class="lang-en">Offline approval evidence now opens through a safer path</span></strong> <span class="lang-id">dengan akses yang diautentikasi dan konteks approval yang lebih konsisten.</span><span class="lang-en">with authenticated access and more consistent approval context.</span>',
                    '<strong><span class="lang-id">Form Stock Request kembali stabil saat submit dan update</span><span class="lang-en">Stock Request forms are stable again on submit and update</span></strong> <span class="lang-id">karena named route yang dibutuhkan frontend sudah dipulihkan.</span><span class="lang-en">because the named routes expected by the frontend have been restored.</span>',
                    '<strong><span class="lang-id">Input tanggal tidak lagi memaksa browser picker</span><span class="lang-en">Date inputs no longer force the browser picker</span></strong> <span class="lang-id">sehingga batch error karena <code>showPicker()</code> tidak lagi mengganggu form PR maupun ST.</span><span class="lang-en">so batch errors caused by <code>showPicker()</code> no longer interrupt PR or ST forms.</span>',
                ],
            },
            {
                type: 'heading',
                id: 'cashflow-updates',
                level: 2,
                text: 'Cashflow Projection',
            },
            {
                type: 'paragraph',
                html: '<span class="lang-id">Cashflow Projection menerima pembaruan yang paling besar di patch ini. Fokusnya adalah membuat data <strong>lebih mudah dibaca</strong>, <strong>lebih konsisten antara dashboard dan export</strong>, dan <strong>lebih aman saat diinput massal</strong> oleh tim finance.</span><span class="lang-en">Cashflow Projection receives the largest set of improvements in this patch. The focus is to make data <strong>easier to read</strong>, <strong>more consistent between the dashboard and exports</strong>, and <strong>safer during bulk input</strong> for finance teams.</span>',
            },
            {
                type: 'status-list',
                items: [
                    {
                        label: '<span class="lang-id">Istilah & Export Lebih Konsisten</span><span class="lang-en">More Consistent Terminology & Export</span>',
                        description: '<span class="lang-id">Dashboard, entries, dan export kini memakai istilah <code>Saldo Proyeksi</code>, mengganti label status yang membingungkan, dan menambahkan saldo proyeksi berjalan ke sheet export.</span><span class="lang-en">Dashboard, entries, and export now use the <code>Saldo Proyeksi</code> wording, replace confusing status labels, and add the running projected balance to export sheets.</span>',
                        color: 'blue',
                    },
                    {
                        label: '<span class="lang-id">Chart Balance-First</span><span class="lang-en">Balance-First Chart</span>',
                        description: '<span class="lang-id">Chart multi-periode kini lebih fokus pada garis saldo proyeksi, mengurangi noise visual, tetapi tetap menyimpan detail inflow dan outflow di tooltip.</span><span class="lang-en">The multi-period chart now focuses on the projected-balance line, reducing visual noise while keeping inflow and outflow details in the tooltip.</span>',
                        color: 'amber',
                    },
                    {
                        label: '<span class="lang-id">Import Excel Lebih Ketat</span><span class="lang-en">Stricter Excel Import</span>',
                        description: '<span class="lang-id">Entries sekarang menyediakan template Excel resmi, dukungan create/update yang eksplisit lewat <code>line_item_id</code>, validasi header yang ketat, dan ringkasan error per baris setelah import.</span><span class="lang-en">Entries now provide an official Excel template, explicit create/update support through <code>line_item_id</code>, strict header validation, and row-level error summaries after import.</span>',
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
                    '<strong><span class="lang-id">Admin dan supervisor</span><span class="lang-en">Admins and supervisors</span></strong> <span class="lang-id">mendapat data Activity yang lebih akurat saat membaca lintas BU, melihat satu anggota tim, atau mengekspor partisipasi task.</span><span class="lang-en">get more accurate Activity data when reading across BUs, focusing on one team member, or exporting task participation.</span>',
                    '<strong><span class="lang-id">Pengguna harian modul Activity</span><span class="lang-en">Daily Activity users</span></strong> <span class="lang-id">merasakan modal task yang lebih stabil, lebih nyaman di layar laptop pendek, dan lebih cepat dipakai untuk create berturut-turut atau delete task.</span><span class="lang-en">experience task modals that are more stable, more comfortable on short laptop screens, and faster to use for consecutive creates or task deletion.</span>',
                    '<strong><span class="lang-id">Pemakai Purchase Request dan Stock Request</span><span class="lang-en">Purchase Request and Stock Request users</span></strong> <span class="lang-id">mendapat alur submit, follow-up approval, dan akses dokumen yang lebih seragam antar modul.</span><span class="lang-en">get more consistent submit flows, approval follow-up, and document access across both modules.</span>',
                    '<strong><span class="lang-id">Tim finance dan CFC</span><span class="lang-en">Finance and CFC teams</span></strong> <span class="lang-id">mendapat chart cashflow yang lebih mudah dibaca, export yang lebih sinkron dengan dashboard, dan import Excel yang lebih aman untuk update massal.</span><span class="lang-en">get a cashflow chart that is easier to read, exports that stay in sync with the dashboard, and a safer Excel import flow for bulk updates.</span>',
                ],
            },
            {
                type: 'callout',
                variant: 'tip',
                title: 'Fokus Update',
                body: '<span class="lang-id">V3.0.3 menutup gelombang pembaruan dari <strong>6-17 April 2026</strong> dengan fokus pada visibilitas data yang lebih kuat, workflow yang lebih mulus, dan operasi finance yang lebih aman untuk penggunaan harian.</span><span class="lang-en">V3.0.3 wraps up the update wave from <strong>April 6-17, 2026</strong> with a focus on stronger data visibility, smoother workflows, and safer finance operations for day-to-day work.</span>',
            },
        ],
    },
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
    },
    {
        id: 'changelog-v3-0-1',
        category: 'changelog',
        title: 'OASIS V3.0.1 - Cashflow Projection Visibility & Control Enhancement',
        description: 'Minor update yang berpusat pada modul Cashflow Projection untuk meningkatkan visibilitas data, kualitas dashboard, dan kontrol histori perubahan.',
        author: 'Pramuji Arif Y',
        updatedAt: '2026-03-27',
        bilingual: true,
        toc: [
            { id: 'overview', label: 'Ringkasan Update' },
            { id: 'main-updates', label: 'Peningkatan Utama' },
            { id: 'dashboard-updates', label: 'Pembaruan Dashboard' },
            { id: 'history-updates', label: 'Penguatan History' },
            { id: 'impact', label: 'Dampak untuk Pengguna' },
        ],
        content: [
            {
                type: 'callout',
                variant: 'info',
                title: 'Minor Update V3.0.1 - 27 Maret 2026',
                body: '<span class="lang-id">Update ini berpusat pada modul <strong>Cashflow Projection</strong> dengan fokus pada peningkatan visibilitas kategori, penyempurnaan tampilan entries, penguatan informasi perubahan data, dan dashboard yang lebih informatif untuk kebutuhan monitoring harian.</span><span class="lang-en">This update is centered on the <strong>Cashflow Projection</strong> module, focusing on category visibility, entries layout refinement, stronger change visibility, and a more informative dashboard for daily monitoring.</span>',
            },
            {
                type: 'heading',
                id: 'overview',
                level: 2,
                text: 'Ringkasan Update',
            },
            {
                type: 'paragraph',
                html: '<span class="lang-id">OASIS V3.0.1 merupakan minor update yang difokuskan pada penyempurnaan pengalaman penggunaan di modul Cashflow Projection. Update ini membuat proses input, review, dan monitoring data menjadi lebih jelas, lebih konsisten, dan lebih mudah dipahami oleh pengguna terkait.</span><span class="lang-en">OASIS V3.0.1 is a minor update focused on improving the user experience in the Cashflow Projection module. This release makes data entry, review, and monitoring clearer, more consistent, and easier to understand for relevant users.</span>',
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
                    '<strong><span class="lang-id">Seluruh kategori ditampilkan untuk user CFC</span><span class="lang-en">All categories are now visible for CFC users</span></strong> <span class="lang-id">agar proses pemantauan dan pengelolaan data menjadi lebih lengkap.</span><span class="lang-en">to support more complete monitoring and data management.</span>',
                    '<strong><span class="lang-id">Urutan tampilan entries disesuaikan</span><span class="lang-en">The entries layout has been reordered</span></strong> <span class="lang-id">menjadi <code>Date - Type - Category - Entry Name - Amount</code> agar pembacaan data lebih cepat dan konsisten.</span><span class="lang-en">to <code>Date - Type - Category - Entry Name - Amount</code> for faster and more consistent data reading.</span>',
                    '<strong><span class="lang-id">Dashboard sudah dilengkapi filter tambahan</span><span class="lang-en">The dashboard now includes additional filters</span></strong> <span class="lang-id">berupa <code>Date</code>, <code>Month</code>, dan <code>Year</code> untuk mempermudah analisis berdasarkan periode yang dibutuhkan.</span><span class="lang-en">for <code>Date</code>, <code>Month</code>, and <code>Year</code> to make period-based analysis easier.</span>',
                    '<strong><span class="lang-id">Dashboard kini menampilkan grafik dengan titik awal 0</span><span class="lang-en">The dashboard now displays charts starting from 0</span></strong> <span class="lang-id">agar pergerakan nilai terlihat lebih proporsional dan lebih mudah dibaca.</span><span class="lang-en">so value movements appear more proportional and easier to read.</span>',
                    '<strong><span class="lang-id">Riwayat perubahan kini menampilkan informasi last edited by</span><span class="lang-en">Change history now displays last edited by information</span></strong> <span class="lang-id">agar pengguna lebih mudah mengetahui siapa yang terakhir melakukan perubahan data.</span><span class="lang-en">so users can clearly identify who last modified the data.</span>',
                    '<strong><span class="lang-id">Visual dashboard juga diperbarui</span><span class="lang-en">The dashboard visual has also been refreshed</span></strong> <span class="lang-id">dengan chart yang lebih informatif, termasuk penanda warna merah otomatis pada kolom cash on hand per tanggal untuk nominal di bawah batas yang telah ditentukan.</span><span class="lang-en">with more informative charts, including an automatic red indicator on the daily cash on hand column for values below the configured threshold.</span>',
                ],
            },
            {
                type: 'heading',
                id: 'dashboard-updates',
                level: 2,
                text: 'Pembaruan Dashboard',
            },
            {
                type: 'paragraph',
                html: '<span class="lang-id">Pembaruan pada dashboard Cashflow Projection difokuskan untuk membantu pengguna membaca kondisi keuangan dengan lebih cepat. Filter periode yang lebih lengkap memudahkan pencarian data yang lebih spesifik, sementara tampilan chart yang dimulai dari nol memberikan gambaran tren yang lebih jelas.</span><span class="lang-en">The Cashflow Projection dashboard update is designed to help users read financial conditions more quickly. More complete period filters make specific data easier to find, while charts starting from zero provide a clearer view of trends.</span>',
            },
            {
                type: 'status-list',
                items: [
                    {
                        label: '<span class="lang-id">Filter Periode</span><span class="lang-en">Period Filters</span>',
                        description: '<span class="lang-id">Pengguna dapat melihat data berdasarkan tanggal, bulan, atau tahun sesuai kebutuhan analisis.</span><span class="lang-en">Users can now view data by date, month, or year based on analysis needs.</span>',
                        color: 'blue',
                    },
                    {
                        label: '<span class="lang-id">Visual Grafik Lebih Jelas</span><span class="lang-en">Clearer Chart Visuals</span>',
                        description: '<span class="lang-id">Grafik dimulai dari nilai 0 agar perubahan nominal terlihat lebih objektif dan tidak menyesatkan secara visual.</span><span class="lang-en">Charts now start from 0 so nominal changes appear more objective and visually accurate.</span>',
                        color: 'emerald',
                    },
                    {
                        label: '<span class="lang-id">Alert Cash on Hand</span><span class="lang-en">Cash on Hand Alert</span>',
                        description: '<span class="lang-id">Nominal cash on hand per tanggal ditandai merah secara otomatis jika nilainya di bawah batas yang telah ditentukan.</span><span class="lang-en">Daily cash on hand values are automatically marked in red when they fall below the configured threshold.</span>',
                        color: 'red',
                    },
                ],
            },
            {
                type: 'heading',
                id: 'history-updates',
                level: 2,
                text: 'Penguatan History',
            },
            {
                type: 'paragraph',
                html: '<span class="lang-id">Update ini juga memperjelas informasi perubahan data melalui tampilan histori yang lebih informatif. Pengguna kini dapat melihat informasi <strong>last edited by</strong> dengan lebih jelas pada data yang telah diperbarui.</span><span class="lang-en">This update also improves change visibility through a more informative history display. Users can now see <strong>last edited by</strong> information more clearly on updated data.</span>',
            },
            {
                type: 'unordered-list',
                items: [
                    '<strong><span class="lang-id">Informasi last edited by ditampilkan lebih jelas</span><span class="lang-en">Last edited by information is now shown more clearly</span></strong> <span class="lang-id">pada data yang sudah mengalami perubahan.</span><span class="lang-en">on data that has already been updated.</span>',
                    '<strong><span class="lang-id">Penelusuran perubahan data menjadi lebih mudah</span><span class="lang-en">Data change tracking is now easier</span></strong> <span class="lang-id">karena pengguna dapat langsung melihat siapa yang terakhir melakukan edit.</span><span class="lang-en">because users can immediately identify who made the latest edit.</span>',
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
                    '<strong><span class="lang-id">User CFC</span><span class="lang-en">CFC users</span></strong> <span class="lang-id">mendapatkan visibilitas kategori yang lebih lengkap untuk proses kerja dan monitoring data.</span><span class="lang-en">now have more complete category visibility for day-to-day work and data monitoring.</span>',
                    '<strong><span class="lang-id">Pengguna dashboard</span><span class="lang-en">Dashboard users</span></strong> <span class="lang-id">lebih mudah membaca tren, memfilter periode, dan mengenali kondisi cash on hand yang memerlukan perhatian cepat.</span><span class="lang-en">can more easily read trends, filter periods, and identify cash on hand conditions that require quick attention.</span>',
                    '<strong><span class="lang-id">Pengguna yang melakukan review data</span><span class="lang-en">Users reviewing data</span></strong> <span class="lang-id">lebih mudah mengetahui siapa yang terakhir melakukan perubahan pada entries yang sudah diperbarui.</span><span class="lang-en">can more easily identify who last modified updated entries.</span>',
                ],
            },
            {
                type: 'callout',
                variant: 'tip',
                title: 'Fokus Update',
                body: '<span class="lang-id">Seluruh pembaruan pada artikel ini berpusat pada <strong>modul Cashflow Projection</strong> sebagai bagian dari minor enhancement untuk meningkatkan kontrol data, kualitas monitoring, dan kejelasan informasi bagi pengguna.</span><span class="lang-en">All updates in this article are centered on the <strong>Cashflow Projection</strong> module as part of a minor enhancement to improve data control, monitoring quality, and information clarity for users.</span>',
            },
        ],
    },
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
    },

    // ──────────────────────────────────────────────
    // FAQ (setiap pertanyaan = 1 artikel)
    // ──────────────────────────────────────────────
    {
        id: 'faq-salah-harga-pr',
        category: 'faq',
        title: 'Salah Memasukkan Harga pada PR yang Sudah Di-submit',
        description: 'Apa yang harus dilakukan jika terlanjur submit PR dengan harga salah.',
        author: 'Pramuji Arif Y',
        updatedAt: '2026-03-01',
        toc: [
            { id: 'jawaban', label: 'Jawaban' },
        ],
        content: [
            {
                type: 'heading',
                id: 'jawaban',
                level: 2,
                text: 'Jawaban',
            },
            {
                type: 'paragraph',
                html: 'Anda tidak bisa mengedit PR yang sudah di-submit. Silakan hubungi approver (Manajer Anda) untuk meminta status dokumen diubah menjadi "Revise" agar Anda bisa mengeditnya, atau Anda bisa membatalkan (Cancel) PR tersebut dan membuat yang baru.',
            },
        ],
    },
    {
        id: 'faq-backdate-task',
        category: 'faq',
        title: 'Mengapa Tidak Bisa Memilih Tanggal Lampau untuk Tugas?',
        description: 'Penjelasan batasan backdated task dan cara mengatasinya.',
        author: 'Pramuji Arif Y',
        updatedAt: '2026-03-01',
        toc: [
            { id: 'jawaban', label: 'Jawaban' },
        ],
        content: [
            {
                type: 'heading',
                id: 'jawaban',
                level: 2,
                text: 'Jawaban',
            },
            {
                type: 'paragraph',
                html: 'Sistem membatasi backdated task maksimal 3 hari kerja ke belakang secara default. Jika Anda butuh lebih dari itu, Anda mungkin memerlukan akses khusus atau mengisi form persetujuan backdate ke HR/Admin.',
            },
        ],
    },
    {
        id: 'faq-offline-approved',
        category: 'faq',
        title: 'Apa Bedanya Mark as Offline Approved?',
        description: 'Penjelasan fitur Offline Approval untuk persetujuan di luar sistem.',
        author: 'Pramuji Arif Y',
        updatedAt: '2026-03-01',
        toc: [
            { id: 'jawaban', label: 'Jawaban' },
        ],
        content: [
            {
                type: 'heading',
                id: 'jawaban',
                level: 2,
                text: 'Jawaban',
            },
            {
                type: 'paragraph',
                html: 'Fitur ini digunakan jika persetujuan (tanda tangan) sudah dilakukan secara fisik (kertas) di luar sistem. Admin dapat menandai dokumen tersebut sebagai disetujui di sistem dengan melampirkan bukti fisik (foto dokumen ber-TTD) yang sudah ditandatangani.',
            },
        ],
    },
    {
        id: 'faq-approver-cuti',
        category: 'faq',
        title: 'Bagaimana Jika Approver Sedang Cuti?',
        description: 'Solusi ketika approver tidak tersedia untuk menyetujui dokumen.',
        author: 'Pramuji Arif Y',
        updatedAt: '2026-03-01',
        toc: [
            { id: 'jawaban', label: 'Jawaban' },
        ],
        content: [
            {
                type: 'heading',
                id: 'jawaban',
                level: 2,
                text: 'Jawaban',
            },
            {
                type: 'paragraph',
                html: 'Jika approver Anda tidak tersedia, hubungi Super Admin untuk melakukan Offline Approval dengan melampirkan bukti otorisasi dari approver tersebut (WhatsApp, email, atau dokumen cetak bertanda tangan).',
            },
        ],
    },
    {
        id: 'faq-cancel-stock-request',
        category: 'faq',
        title: 'Bisa Cancel Stock Request yang Sudah Disubmit?',
        description: 'Cara membatalkan Stock Request yang sudah terlanjur diajukan.',
        author: 'Pramuji Arif Y',
        updatedAt: '2026-03-01',
        toc: [
            { id: 'jawaban', label: 'Jawaban' },
        ],
        content: [
            {
                type: 'heading',
                id: 'jawaban',
                level: 2,
                text: 'Jawaban',
            },
            {
                type: 'paragraph',
                html: 'Ya, selama status masih Pending Approval. Buka detail Stock Request lalu klik tombol Cancel. Setelah disetujui (Approved), pembatalan harus dikoordinasikan dengan Admin Gudang.',
            },
        ],
    },
    {
        id: 'faq-link-expired',
        category: 'faq',
        title: 'Link Approval di Email Expired, Bagaimana?',
        description: 'Solusi ketika link approval di email sudah kedaluwarsa.',
        author: 'Pramuji Arif Y',
        updatedAt: '2026-03-01',
        toc: [
            { id: 'jawaban', label: 'Jawaban' },
        ],
        content: [
            {
                type: 'heading',
                id: 'jawaban',
                level: 2,
                text: 'Jawaban',
            },
            {
                type: 'paragraph',
                html: 'Link approval berlaku 3 hari. Jika sudah expired, silakan login ke sistem OASIS dan approve dokumen langsung dari halaman Approvals.',
            },
        ],
    },
    {
        id: 'faq-dashboard-beda-bu',
        category: 'faq',
        title: 'Kenapa Data Dashboard Berbeda Setelah Switch Business Unit?',
        description: 'Penjelasan mengapa data berubah saat berpindah Business Unit.',
        author: 'Pramuji Arif Y',
        updatedAt: '2026-03-01',
        toc: [
            { id: 'jawaban', label: 'Jawaban' },
        ],
        content: [
            {
                type: 'heading',
                id: 'jawaban',
                level: 2,
                text: 'Jawaban',
            },
            {
                type: 'paragraph',
                html: 'Setiap Business Unit memiliki data terpisah. Setelah switch BU, dashboard menampilkan data khusus BU yang aktif. Pastikan Anda memilih BU yang benar sebelum melihat laporan.',
            },
        ],
    },
    {
        id: 'faq-export-excel',
        category: 'faq',
        title: 'Bagaimana Cara Export Laporan ke Excel?',
        description: 'Langkah-langkah mengekspor data laporan ke format Excel.',
        author: 'Pramuji Arif Y',
        updatedAt: '2026-03-01',
        toc: [
            { id: 'jawaban', label: 'Jawaban' },
        ],
        content: [
            {
                type: 'heading',
                id: 'jawaban',
                level: 2,
                text: 'Jawaban',
            },
            {
                type: 'paragraph',
                html: 'Buka Dashboard, atur filter sesuai kebutuhan (periode, departemen, status), lalu klik tombol Export di pojok kanan atas. Pilih format XLSX atau PDF.',
            },
        ],
    },
];
