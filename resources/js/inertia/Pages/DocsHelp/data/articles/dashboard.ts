import type { Article } from '../types';

export const DashboardArticles: Article[] = [
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
];
