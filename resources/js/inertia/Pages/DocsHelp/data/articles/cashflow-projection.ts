import type { Article } from '../types';

export const CashflowProjectionArticles: Article[] = [
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
];
