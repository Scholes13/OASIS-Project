import type { Article } from '../types';

export const FaqArticles: Article[] = [
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
    }
];
