import type { Article } from '../types';

export const StockRequestArticles: Article[] = [
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
];
