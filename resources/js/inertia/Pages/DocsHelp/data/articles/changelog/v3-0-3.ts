import type { Article } from '../../types';

export const ChangelogV303Article: Article =
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
    };
