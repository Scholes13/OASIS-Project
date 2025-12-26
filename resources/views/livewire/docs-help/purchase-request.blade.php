<div class="prose prose-indigo max-w-none">
    <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
        <svg class="w-6 h-6 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z"></path>
        </svg>
        Purchase Request (PR)
    </h2>

    <p class="text-gray-600 mb-6">
        Purchase Request adalah fitur untuk mengajukan permintaan pembelian barang atau jasa. Setiap PR akan melalui proses approval sesuai dengan workflow yang telah ditentukan.
    </p>

    <!-- Workflow Status -->
    <h3 class="text-lg font-semibold text-gray-900 mb-3">Status Workflow</h3>
    <div class="grid grid-cols-2 md:grid-cols-3 gap-3 mb-6">
        <div class="flex items-center space-x-2 bg-gray-100 rounded-lg px-3 py-2">
            <span class="w-2 h-2 bg-gray-400 rounded-full"></span>
            <span class="text-sm text-gray-700"><strong>Draft</strong> - Masih dapat diedit</span>
        </div>
        <div class="flex items-center space-x-2 bg-blue-100 rounded-lg px-3 py-2">
            <span class="w-2 h-2 bg-blue-500 rounded-full"></span>
            <span class="text-sm text-blue-700"><strong>Submitted</strong> - Menunggu approval</span>
        </div>
        <div class="flex items-center space-x-2 bg-amber-100 rounded-lg px-3 py-2">
            <span class="w-2 h-2 bg-amber-500 rounded-full"></span>
            <span class="text-sm text-amber-700"><strong>In Approval</strong> - Sedang diproses</span>
        </div>
        <div class="flex items-center space-x-2 bg-emerald-100 rounded-lg px-3 py-2">
            <span class="w-2 h-2 bg-emerald-500 rounded-full"></span>
            <span class="text-sm text-emerald-700"><strong>Approved</strong> - Disetujui</span>
        </div>
        <div class="flex items-center space-x-2 bg-red-100 rounded-lg px-3 py-2">
            <span class="w-2 h-2 bg-red-500 rounded-full"></span>
            <span class="text-sm text-red-700"><strong>Rejected</strong> - Ditolak</span>
        </div>
        <div class="flex items-center space-x-2 bg-gray-200 rounded-lg px-3 py-2">
            <span class="w-2 h-2 bg-gray-500 rounded-full"></span>
            <span class="text-sm text-gray-700"><strong>Voided</strong> - Dibatalkan</span>
        </div>
    </div>

    <!-- Step by Step Guide -->
    <h3 class="text-lg font-semibold text-gray-900 mb-3">Cara Membuat Purchase Request</h3>
    
    <div class="space-y-4 mb-8">
        <!-- Step 1 -->
        <div class="bg-white border border-gray-200 rounded-lg p-4">
            <div class="flex items-start">
                <span class="flex-shrink-0 w-8 h-8 flex items-center justify-center bg-indigo-600 text-white rounded-full text-sm font-bold mr-4">1</span>
                <div class="flex-1">
                    <h4 class="font-semibold text-gray-900 mb-2">Akses Halaman Purchase Request</h4>
                    <p class="text-gray-600 text-sm mb-2">
                        Navigasi ke menu <strong>Purchasing → Purchase Request</strong> dari sidebar, atau akses langsung ke:
                    </p>
                    <code class="bg-gray-100 px-2 py-1 rounded text-sm text-indigo-600">/purchase-requests</code>
                </div>
            </div>
        </div>

        <!-- Step 2 -->
        <div class="bg-white border border-gray-200 rounded-lg p-4">
            <div class="flex items-start">
                <span class="flex-shrink-0 w-8 h-8 flex items-center justify-center bg-indigo-600 text-white rounded-full text-sm font-bold mr-4">2</span>
                <div class="flex-1">
                    <h4 class="font-semibold text-gray-900 mb-2">Klik Tombol "Create New PR"</h4>
                    <p class="text-gray-600 text-sm">
                        Pada halaman daftar Purchase Request, klik tombol <strong>"Create New PR"</strong> di bagian kanan atas untuk membuat PR baru.
                    </p>
                </div>
            </div>
        </div>

        <!-- Step 3 -->
        <div class="bg-white border border-gray-200 rounded-lg p-4">
            <div class="flex items-start">
                <span class="flex-shrink-0 w-8 h-8 flex items-center justify-center bg-indigo-600 text-white rounded-full text-sm font-bold mr-4">3</span>
                <div class="flex-1">
                    <h4 class="font-semibold text-gray-900 mb-2">Isi Informasi Purchase Request</h4>
                    <div class="text-gray-600 text-sm space-y-2">
                        <p>Lengkapi form dengan informasi berikut:</p>
                        <ul class="list-disc list-inside space-y-1 ml-2">
                            <li><strong>Category</strong> - Pilih kategori PR (wajib)</li>
                            <li><strong>Purpose / Used For</strong> - Jelaskan tujuan pembelian (wajib, min. 10 karakter)</li>
                            <li><strong>Expected Delivery Date</strong> - Tanggal pengiriman yang diharapkan (opsional)</li>
                            <li><strong>Currency</strong> - Mata uang (IDR, USD, EUR, SGD)</li>
                            <li><strong>Supporting Document</strong> - Upload dokumen pendukung jika ada (PDF, Word, Excel)</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 4 -->
        <div class="bg-white border border-gray-200 rounded-lg p-4">
            <div class="flex items-start">
                <span class="flex-shrink-0 w-8 h-8 flex items-center justify-center bg-indigo-600 text-white rounded-full text-sm font-bold mr-4">4</span>
                <div class="flex-1">
                    <h4 class="font-semibold text-gray-900 mb-2">Tambahkan Item</h4>
                    <div class="text-gray-600 text-sm space-y-2">
                        <p>Klik tombol <strong>"Add Item"</strong> untuk menambahkan item yang akan dibeli:</p>
                        <ul class="list-disc list-inside space-y-1 ml-2">
                            <li><strong>Item Name</strong> - Nama barang/jasa (wajib)</li>
                            <li><strong>Brand</strong> - Merek barang (opsional)</li>
                            <li><strong>Description</strong> - Deskripsi/spesifikasi (opsional)</li>
                            <li><strong>Supplier</strong> - Nama supplier (opsional)</li>
                            <li><strong>Quantity</strong> - Jumlah (wajib)</li>
                            <li><strong>Unit</strong> - Satuan (pcs, unit, set, pack, box, kg, meter, liter)</li>
                            <li><strong>Price</strong> - Harga per unit (wajib)</li>
                            <li><strong>Image</strong> - Upload gambar item (opsional)</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 5 -->
        <div class="bg-white border border-gray-200 rounded-lg p-4">
            <div class="flex items-start">
                <span class="flex-shrink-0 w-8 h-8 flex items-center justify-center bg-indigo-600 text-white rounded-full text-sm font-bold mr-4">5</span>
                <div class="flex-1">
                    <h4 class="font-semibold text-gray-900 mb-2">Tentukan Approval Chain</h4>
                    <div class="text-gray-600 text-sm space-y-2">
                        <p>Pilih approver yang akan menyetujui PR Anda:</p>
                        <ul class="list-disc list-inside space-y-1 ml-2">
                            <li>Klik <strong>"Add Approver"</strong> untuk menambahkan approver</li>
                            <li>Pilih approver dari dropdown (berdasarkan hierarki organisasi)</li>
                            <li>Tentukan tipe approval: <strong>Approval</strong> atau <strong>Acknowledge</strong></li>
                            <li>Urutan approver menentukan urutan approval (sequential)</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 6 -->
        <div class="bg-white border border-gray-200 rounded-lg p-4">
            <div class="flex items-start">
                <span class="flex-shrink-0 w-8 h-8 flex items-center justify-center bg-indigo-600 text-white rounded-full text-sm font-bold mr-4">6</span>
                <div class="flex-1">
                    <h4 class="font-semibold text-gray-900 mb-2">Submit atau Save as Draft</h4>
                    <div class="text-gray-600 text-sm space-y-2">
                        <p>Pilih aksi yang sesuai:</p>
                        <div class="flex flex-wrap gap-2 mt-2">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-indigo-100 text-indigo-700">
                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5"></path>
                                </svg>
                                Submit - Kirim untuk approval
                            </span>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700">
                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"></path>
                                </svg>
                                Save Draft - Simpan untuk dilanjutkan nanti
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- PR Numbering -->
    <h3 class="text-lg font-semibold text-gray-900 mb-3">Format Nomor PR</h3>
    <div class="bg-gray-50 rounded-lg p-4 mb-6 border border-gray-200">
        <p class="text-gray-600 text-sm mb-2">
            Setiap PR akan mendapatkan nomor unik dengan format:
        </p>
        <code class="bg-white px-3 py-2 rounded border border-gray-200 text-sm text-indigo-600 block">
            PR/[BUSINESS_UNIT]/[DEPARTMENT]/[YEAR]/[SEQUENCE]
        </code>
        <p class="text-gray-500 text-xs mt-2">
            Contoh: PR/WNS/IT/2025/00001
        </p>
    </div>

    <!-- Tips -->
    <h3 class="text-lg font-semibold text-gray-900 mb-3">Tips</h3>
    <div class="bg-amber-50 rounded-lg p-4 border border-amber-200">
        <ul class="space-y-2 text-sm text-amber-800">
            <li class="flex items-start">
                <svg class="w-4 h-4 mr-2 mt-0.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
                <span>Pastikan semua informasi sudah benar sebelum submit, karena PR yang sudah disubmit tidak dapat diedit.</span>
            </li>
            <li class="flex items-start">
                <svg class="w-4 h-4 mr-2 mt-0.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span>Gunakan fitur "Save Draft" jika Anda belum yakin dengan data yang diinput.</span>
            </li>
            <li class="flex items-start">
                <svg class="w-4 h-4 mr-2 mt-0.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span>Upload gambar item untuk mempermudah approver memahami barang yang diminta.</span>
            </li>
        </ul>
    </div>
</div>
