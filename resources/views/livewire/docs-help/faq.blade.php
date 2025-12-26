<div class="prose prose-indigo max-w-none">
    <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
        <svg class="w-6 h-6 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z"></path>
        </svg>
        Frequently Asked Questions (FAQ)
    </h2>

    <p class="text-gray-600 mb-6">
        Pertanyaan yang sering diajukan tentang penggunaan sistem Oasis.
    </p>

    <!-- FAQ Items -->
    <div class="space-y-4" x-data="{ openFaq: null }">
        <!-- FAQ 1 -->
        <div class="border border-gray-200 rounded-lg overflow-hidden">
            <button 
                @click="openFaq = openFaq === 1 ? null : 1"
                class="w-full flex items-center justify-between px-4 py-3 bg-gray-50 hover:bg-gray-100 transition-colors text-left"
            >
                <span class="font-medium text-gray-900">Bagaimana cara membuat Purchase Request?</span>
                <svg class="w-5 h-5 text-gray-500 transition-transform" :class="openFaq === 1 ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"></path>
                </svg>
            </button>
            <div x-show="openFaq === 1" x-collapse class="px-4 py-3 bg-white">
                <p class="text-gray-600 text-sm">
                    Untuk membuat Purchase Request, ikuti langkah berikut:
                </p>
                <ol class="list-decimal list-inside text-sm text-gray-600 mt-2 space-y-1">
                    <li>Buka menu <strong>Purchasing → Purchase Request</strong></li>
                    <li>Klik tombol <strong>"Create New PR"</strong></li>
                    <li>Isi informasi PR (kategori, tujuan, dll)</li>
                    <li>Tambahkan item yang akan dibeli</li>
                    <li>Pilih approver</li>
                    <li>Klik <strong>Submit</strong> atau <strong>Save Draft</strong></li>
                </ol>
            </div>
        </div>

        <!-- FAQ 2 -->
        <div class="border border-gray-200 rounded-lg overflow-hidden">
            <button 
                @click="openFaq = openFaq === 2 ? null : 2"
                class="w-full flex items-center justify-between px-4 py-3 bg-gray-50 hover:bg-gray-100 transition-colors text-left"
            >
                <span class="font-medium text-gray-900">Apakah saya bisa mengedit PR yang sudah disubmit?</span>
                <svg class="w-5 h-5 text-gray-500 transition-transform" :class="openFaq === 2 ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"></path>
                </svg>
            </button>
            <div x-show="openFaq === 2" x-collapse class="px-4 py-3 bg-white">
                <p class="text-gray-600 text-sm">
                    <strong>Tidak</strong>, PR yang sudah disubmit tidak dapat diedit. Namun, jika PR ditolak (rejected), Anda dapat mengedit dan submit ulang (resubmit) PR tersebut dengan perbaikan yang diperlukan.
                </p>
                <p class="text-gray-600 text-sm mt-2">
                    Jika Anda perlu membatalkan PR yang sudah disubmit, hubungi admin untuk melakukan void pada PR tersebut.
                </p>
            </div>
        </div>

        <!-- FAQ 3 -->
        <div class="border border-gray-200 rounded-lg overflow-hidden">
            <button 
                @click="openFaq = openFaq === 3 ? null : 3"
                class="w-full flex items-center justify-between px-4 py-3 bg-gray-50 hover:bg-gray-100 transition-colors text-left"
            >
                <span class="font-medium text-gray-900">Apa perbedaan Purchase Request dan Stock Request?</span>
                <svg class="w-5 h-5 text-gray-500 transition-transform" :class="openFaq === 3 ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"></path>
                </svg>
            </button>
            <div x-show="openFaq === 3" x-collapse class="px-4 py-3 bg-white">
                <p class="text-gray-600 text-sm">
                    <strong>Purchase Request (PR)</strong> digunakan untuk mengajukan pembelian barang atau jasa secara umum, seperti laptop, furniture, jasa konsultan, peralatan IT, dll. PR memerlukan informasi harga dan kategori.
                </p>
                <p class="text-gray-600 text-sm mt-2">
                    <strong>Stock Request (ST)</strong> digunakan khusus untuk pembelian barang-barang consumable/habis pakai seperti ATK (kertas, pulpen, tinta printer), perlengkapan kantor, dan kebutuhan operasional rutin. ST lebih sederhana dan tidak memerlukan kategori.
                </p>
            </div>
        </div>

        <!-- FAQ 4 -->
        <div class="border border-gray-200 rounded-lg overflow-hidden">
            <button 
                @click="openFaq = openFaq === 4 ? null : 4"
                class="w-full flex items-center justify-between px-4 py-3 bg-gray-50 hover:bg-gray-100 transition-colors text-left"
            >
                <span class="font-medium text-gray-900">Bagaimana cara beralih antar Business Unit?</span>
                <svg class="w-5 h-5 text-gray-500 transition-transform" :class="openFaq === 4 ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"></path>
                </svg>
            </button>
            <div x-show="openFaq === 4" x-collapse class="px-4 py-3 bg-white">
                <p class="text-gray-600 text-sm">
                    Jika Anda memiliki akses ke beberapa Business Unit, Anda dapat beralih menggunakan <strong>Business Unit Switcher</strong> yang terletak di header aplikasi (sebelah kanan atas).
                </p>
                <p class="text-gray-600 text-sm mt-2">
                    Klik dropdown dan pilih Business Unit yang ingin Anda akses. Semua data akan otomatis difilter sesuai Business Unit yang dipilih.
                </p>
            </div>
        </div>

        <!-- FAQ 5 -->
        <div class="border border-gray-200 rounded-lg overflow-hidden">
            <button 
                @click="openFaq = openFaq === 5 ? null : 5"
                class="w-full flex items-center justify-between px-4 py-3 bg-gray-50 hover:bg-gray-100 transition-colors text-left"
            >
                <span class="font-medium text-gray-900">Siapa yang bisa menjadi approver?</span>
                <svg class="w-5 h-5 text-gray-500 transition-transform" :class="openFaq === 5 ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"></path>
                </svg>
            </button>
            <div x-show="openFaq === 5" x-collapse class="px-4 py-3 bg-white">
                <p class="text-gray-600 text-sm">
                    Approver ditentukan berdasarkan hierarki organisasi. Biasanya termasuk:
                </p>
                <ul class="list-disc list-inside text-sm text-gray-600 mt-2 space-y-1">
                    <li>Atasan langsung (Department Head)</li>
                    <li>Manager/Director</li>
                    <li>Finance Manager (untuk PR dengan nilai tertentu)</li>
                    <li>General Manager/CEO (untuk PR dengan nilai besar)</li>
                </ul>
                <p class="text-gray-600 text-sm mt-2">
                    Daftar approver yang tersedia akan muncul saat Anda membuat PR/ST.
                </p>
            </div>
        </div>

        <!-- FAQ 6 -->
        <div class="border border-gray-200 rounded-lg overflow-hidden">
            <button 
                @click="openFaq = openFaq === 6 ? null : 6"
                class="w-full flex items-center justify-between px-4 py-3 bg-gray-50 hover:bg-gray-100 transition-colors text-left"
            >
                <span class="font-medium text-gray-900">Bagaimana jika approver tidak merespon?</span>
                <svg class="w-5 h-5 text-gray-500 transition-transform" :class="openFaq === 6 ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"></path>
                </svg>
            </button>
            <div x-show="openFaq === 6" x-collapse class="px-4 py-3 bg-white">
                <p class="text-gray-600 text-sm">
                    Sistem akan mengirimkan reminder email kepada approver secara berkala. Jika approver tetap tidak merespon dalam waktu yang ditentukan (sesuai SLA), Anda dapat:
                </p>
                <ul class="list-disc list-inside text-sm text-gray-600 mt-2 space-y-1">
                    <li>Menghubungi approver secara langsung</li>
                    <li>Meminta bantuan admin untuk melakukan <strong>Offline Approval</strong></li>
                    <li>Menghubungi atasan approver untuk eskalasi</li>
                </ul>
            </div>
        </div>

        <!-- FAQ 7 -->
        <div class="border border-gray-200 rounded-lg overflow-hidden">
            <button 
                @click="openFaq = openFaq === 7 ? null : 7"
                class="w-full flex items-center justify-between px-4 py-3 bg-gray-50 hover:bg-gray-100 transition-colors text-left"
            >
                <span class="font-medium text-gray-900">Apa itu Offline Approval?</span>
                <svg class="w-5 h-5 text-gray-500 transition-transform" :class="openFaq === 7 ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"></path>
                </svg>
            </button>
            <div x-show="openFaq === 7" x-collapse class="px-4 py-3 bg-white">
                <p class="text-gray-600 text-sm">
                    <strong>Offline Approval</strong> adalah fitur yang memungkinkan <strong>pembuat request</strong> untuk menandai bahwa PR/ST telah disetujui secara manual/offline (misalnya melalui tanda tangan di dokumen fisik).
                </p>
                <p class="text-gray-600 text-sm mt-2">
                    Prosesnya:
                </p>
                <ol class="list-decimal list-inside text-sm text-gray-600 mt-1 space-y-1">
                    <li>Cetak dokumen PR/ST dan minta tanda tangan approver</li>
                    <li>Buka halaman detail request yang statusnya "In Approval"</li>
                    <li>Klik tombol "Mark Offline Approved"</li>
                    <li><strong>Upload bukti approval (WAJIB)</strong> - format: JPG, PNG, atau PDF</li>
                    <li>Konfirmasi untuk mengubah status menjadi "Approved"</li>
                </ol>
                <p class="text-amber-600 text-sm mt-2">
                    <strong>Catatan:</strong> Upload bukti tanda tangan adalah wajib untuk dokumentasi dan audit trail.
                </p>
            </div>
        </div>

        <!-- FAQ 8 -->
        <div class="border border-gray-200 rounded-lg overflow-hidden">
            <button 
                @click="openFaq = openFaq === 8 ? null : 8"
                class="w-full flex items-center justify-between px-4 py-3 bg-gray-50 hover:bg-gray-100 transition-colors text-left"
            >
                <span class="font-medium text-gray-900">Bagaimana cara melihat history request saya?</span>
                <svg class="w-5 h-5 text-gray-500 transition-transform" :class="openFaq === 8 ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"></path>
                </svg>
            </button>
            <div x-show="openFaq === 8" x-collapse class="px-4 py-3 bg-white">
                <p class="text-gray-600 text-sm">
                    Anda dapat melihat semua request yang pernah Anda buat di:
                </p>
                <ul class="list-disc list-inside text-sm text-gray-600 mt-2 space-y-1">
                    <li><strong>Purchasing → Purchase Request</strong> - untuk daftar PR Anda</li>
                    <li><strong>Purchasing → Stock Request</strong> - untuk daftar ST Anda</li>
                    <li><strong>Purchasing → All Requests</strong> - untuk melihat semua request (PR & ST)</li>
                </ul>
                <p class="text-gray-600 text-sm mt-2">
                    Gunakan filter untuk mencari request berdasarkan status, tanggal, atau nomor request.
                </p>
            </div>
        </div>

        <!-- FAQ 9 -->
        <div class="border border-gray-200 rounded-lg overflow-hidden">
            <button 
                @click="openFaq = openFaq === 9 ? null : 9"
                class="w-full flex items-center justify-between px-4 py-3 bg-gray-50 hover:bg-gray-100 transition-colors text-left"
            >
                <span class="font-medium text-gray-900">Bagaimana cara download PDF request?</span>
                <svg class="w-5 h-5 text-gray-500 transition-transform" :class="openFaq === 9 ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"></path>
                </svg>
            </button>
            <div x-show="openFaq === 9" x-collapse class="px-4 py-3 bg-white">
                <p class="text-gray-600 text-sm">
                    Untuk mendownload PDF request:
                </p>
                <ol class="list-decimal list-inside text-sm text-gray-600 mt-2 space-y-1">
                    <li>Buka detail request (klik pada nomor request)</li>
                    <li>Klik tombol <strong>"Download PDF"</strong> di bagian atas halaman</li>
                    <li>PDF akan otomatis terdownload</li>
                </ol>
                <p class="text-gray-600 text-sm mt-2">
                    PDF berisi informasi lengkap request termasuk QR Code untuk verifikasi.
                </p>
            </div>
        </div>

        <!-- FAQ 10 -->
        <div class="border border-gray-200 rounded-lg overflow-hidden">
            <button 
                @click="openFaq = openFaq === 10 ? null : 10"
                class="w-full flex items-center justify-between px-4 py-3 bg-gray-50 hover:bg-gray-100 transition-colors text-left"
            >
                <span class="font-medium text-gray-900">Siapa yang harus saya hubungi jika ada masalah?</span>
                <svg class="w-5 h-5 text-gray-500 transition-transform" :class="openFaq === 10 ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"></path>
                </svg>
            </button>
            <div x-show="openFaq === 10" x-collapse class="px-4 py-3 bg-white">
                <p class="text-gray-600 text-sm">
                    Jika Anda mengalami masalah teknis atau membutuhkan bantuan:
                </p>
                <ul class="list-disc list-inside text-sm text-gray-600 mt-2 space-y-1">
                    <li>Hubungi <strong>Purchasing Admin</strong> di departemen Anda</li>
                    <li>Hubungi <strong>IT Support</strong> untuk masalah teknis sistem</li>
                    <li>Hubungi <strong>Super Admin</strong> untuk masalah akses atau permission</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Contact Support -->
    <div class="mt-8 bg-indigo-50 rounded-lg p-6 border border-indigo-100">
        <div class="flex items-start">
            <svg class="w-6 h-6 text-indigo-600 mr-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z"></path>
            </svg>
            <div>
                <h4 class="font-semibold text-indigo-900 mb-1">Masih ada pertanyaan?</h4>
                <p class="text-sm text-indigo-700">
                    Jika pertanyaan Anda tidak terjawab di FAQ ini, silakan hubungi tim support atau admin sistem untuk bantuan lebih lanjut.
                </p>
            </div>
        </div>
    </div>
</div>
