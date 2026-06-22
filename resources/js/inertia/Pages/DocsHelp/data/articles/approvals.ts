import type { Article } from '../types';

export const ApprovalsArticles: Article[] = [
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
];
