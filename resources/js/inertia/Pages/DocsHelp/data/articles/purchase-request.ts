import type { Article } from '../types';

export const PurchaseRequestArticles: Article[] = [
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
];
