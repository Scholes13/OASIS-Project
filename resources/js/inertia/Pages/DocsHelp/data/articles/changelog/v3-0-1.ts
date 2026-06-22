import type { Article } from '../../types';

export const ChangelogV301Article: Article =
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
    };
