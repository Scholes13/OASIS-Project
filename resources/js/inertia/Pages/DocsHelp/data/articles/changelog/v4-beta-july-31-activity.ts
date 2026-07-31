import type { Article } from '../../types';

export const ChangelogV4BetaJuly31ActivityArticle: Article = {
    id: 'changelog-v4-beta-july-31-activity',
    category: 'changelog',
    title: 'OASIS V4 Beta - Activity Scope & Stability Update',
    description: 'Pembaruan 31 Juli 2026 untuk filter Activity, fokus anggota tim, dan stabilitas halaman task.',
    author: 'Pramuji Arif Y',
    updatedAt: '2026-07-31',
    popular: true,
    bilingual: true,
    toc: [
        { id: 'overview', label: 'Ringkasan Update' },
        { id: 'task-scope', label: 'Filter Activity' },
        { id: 'stability', label: 'Stabilitas Activity' },
        { id: 'impact', label: 'Dampak untuk Pengguna' },
    ],
    content: [
        {
            type: 'callout',
            variant: 'info',
            title: 'Update 31 Juli 2026',
            body: '<span class="lang-id">Update ini khusus untuk modul Activity. Perubahan berfokus pada filter Personal/Team, filter anggota, dan fallback halaman task saat terjadi error.</span><span class="lang-en">This update is specific to the Activity module. It focuses on the Personal/Team filter, member filtering, and the task page fallback when an error occurs.</span>',
        },
        {
            type: 'heading',
            id: 'overview',
            level: 2,
            text: 'Ringkasan Update',
        },
        {
            type: 'paragraph',
            html: '<span class="lang-id">Pengguna Activity kini dapat membandingkan task pribadi dan task tim sesuai departemen yang sedang dipilih. Perbaikan ini juga menjaga hak edit task tetap terpisah dari hak melihat daftar task.</span><span class="lang-en">Activity users can now compare personal and team tasks within the selected department. The update also keeps task-edit permissions separate from permission to view the task list.</span>',
        },
        {
            type: 'heading',
            id: 'task-scope',
            level: 2,
            text: 'Filter Activity',
        },
        {
            type: 'unordered-list',
            items: [
                '<strong><span class="lang-id">My Tasks dan Team untuk staff</span><span class="lang-en">My Tasks and Team for staff</span></strong> <span class="lang-id">seluruh anggota aktif dengan assignment departemen dapat berpindah antara task pribadi dan task tim.</span><span class="lang-en">all active members with a department assignment can switch between personal and team tasks.</span>',
                '<strong><span class="lang-id">Filter anggota lebih lengkap</span><span class="lang-en">More complete member filter</span></strong> <span class="lang-id">anggota aktif yang memiliki assignment sekunder pada departemen yang sama ikut tersedia dalam filter.</span><span class="lang-en">active members with secondary assignments in the same department are included in the filter.</span>',
                '<strong><span class="lang-id">Scope tetap terbatas</span><span class="lang-en">Scope remains limited</span></strong> <span class="lang-id">data Team hanya berasal dari Business Unit dan departemen aktif pengguna.</span><span class="lang-en">Team data only comes from the user\'s active Business Unit and department.</span>',
            ],
        },
        {
            type: 'heading',
            id: 'stability',
            level: 2,
            text: 'Stabilitas Activity',
        },
        {
            type: 'status-list',
            items: [
                {
                    label: '<span class="lang-id">Fallback tetap mempertahankan filter</span><span class="lang-en">Fallback preserves the filter</span>',
                    description: '<span class="lang-id">Jika halaman task masuk fallback setelah error, status akses Team tetap dikirim sehingga tombol tidak menghilang tanpa alasan.</span><span class="lang-en">If the task page falls back after an error, Team access is still sent so the button does not disappear without explanation.</span>',
                    color: 'blue',
                },
                {
                    label: '<span class="lang-id">Error tercatat untuk debugging</span><span class="lang-en">Errors are logged for debugging</span>',
                    description: '<span class="lang-id">Exception pada proses pemuatan Activity dicatat di log server tanpa menghentikan halaman fallback.</span><span class="lang-en">Exceptions during Activity loading are recorded in the server log without breaking the fallback page.</span>',
                    color: 'amber',
                },
                {
                    label: '<span class="lang-id">Hak edit tetap aman</span><span class="lang-en">Edit rights remain protected</span>',
                    description: '<span class="lang-id">Bisa melihat task Team tidak otomatis memberikan hak mengubah task milik anggota lain.</span><span class="lang-en">Viewing Team tasks does not automatically grant permission to edit another member\'s task.</span>',
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
            type: 'callout',
            variant: 'tip',
            title: 'Fokus Update',
            body: '<span class="lang-id">Update ini hanya menyentuh modul Activity dan tidak mengubah workflow IT Support, Purchasing, Stock Request, atau Cashflow.</span><span class="lang-en">This update only affects the Activity module and does not change the IT Support, Purchasing, Stock Request, or Cashflow workflows.</span>',
        },
    ],
};
