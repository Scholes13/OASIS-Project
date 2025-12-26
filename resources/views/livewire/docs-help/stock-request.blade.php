<div class="prose prose-indigo max-w-none">
    <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
        <svg class="w-6 h-6 mr-2 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"></path>
        </svg>
        Stock Request (ST)
    </h2>

    <p class="text-gray-600 mb-6">
        Stock Request adalah fitur untuk mengajukan permintaan pembelian barang-barang <strong>consumable</strong> seperti ATK (Alat Tulis Kantor), perlengkapan kantor, dan asset yang bisa habis pakai. Berbeda dengan Purchase Request yang untuk barang/jasa umum, Stock Request khusus untuk kebutuhan operasional rutin.
    </p>

    <!-- Use Cases -->
    <div class="bg-emerald-50 rounded-lg p-4 mb-6 border border-emerald-200">
        <h4 class="font-semibold text-emerald-900 mb-2">Contoh Penggunaan Stock Request:</h4>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-white text-emerald-700 border border-emerald-200">📝 Kertas A4</span>
            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-white text-emerald-700 border border-emerald-200">🖊️ Pulpen/Pensil</span>
            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-white text-emerald-700 border border-emerald-200">📁 Map/Folder</span>
            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-white text-emerald-700 border border-emerald-200">🖨️ Tinta Printer</span>
            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-white text-emerald-700 border border-emerald-200">📎 Stapler/Clip</span>
            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-white text-emerald-700 border border-emerald-200">📒 Buku Tulis</span>
            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-white text-emerald-700 border border-emerald-200">🧹 Alat Kebersihan</span>
            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-white text-emerald-700 border border-emerald-200">💡 Lampu/Bohlam</span>
        </div>
    </div>

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
    <h3 class="text-lg font-semibold text-gray-900 mb-3">Cara Membuat Stock Request</h3>
    
    <div class="space-y-4 mb-8">
        <!-- Step 1 -->
        <div class="bg-white border border-gray-200 rounded-lg p-4">
            <div class="flex items-start">
                <span class="flex-shrink-0 w-8 h-8 flex items-center justify-center bg-emerald-600 text-white rounded-full text-sm font-bold mr-4">1</span>
                <div class="flex-1">
                    <h4 class="font-semibold text-gray-900 mb-2">Akses Halaman Stock Request</h4>
                    <p class="text-gray-600 text-sm mb-2">
                        Navigasi ke menu <strong>Purchasing → Stock Request</strong> dari sidebar, atau akses langsung ke:
                    </p>
                    <code class="bg-gray-100 px-2 py-1 rounded text-sm text-emerald-600">/stock-requests</code>
                </div>
            </div>
        </div>

        <!-- Step 2 -->
        <div class="bg-white border border-gray-200 rounded-lg p-4">
            <div class="flex items-start">
                <span class="flex-shrink-0 w-8 h-8 flex items-center justify-center bg-emerald-600 text-white rounded-full text-sm font-bold mr-4">2</span>
                <div class="flex-1">
                    <h4 class="font-semibold text-gray-900 mb-2">Klik Tombol "Create New ST"</h4>
                    <p class="text-gray-600 text-sm">
                        Pada halaman daftar Stock Request, klik tombol <strong>"Create New ST"</strong> di bagian kanan atas untuk membuat ST baru.
                    </p>
                </div>
            </div>
        </div>

        <!-- Step 3 -->
        <div class="bg-white border border-gray-200 rounded-lg p-4">
            <div class="flex items-start">
                <span class="flex-shrink-0 w-8 h-8 flex items-center justify-center bg-emerald-600 text-white rounded-full text-sm font-bold mr-4">3</span>
                <div class="flex-1">
                    <h4 class="font-semibold text-gray-900 mb-2">Isi Informasi Stock Request</h4>
                    <div class="text-gray-600 text-sm space-y-2">
                        <p>Lengkapi form dengan informasi berikut:</p>
                        <ul class="list-disc list-inside space-y-1 ml-2">
                            <li><strong>Purpose / Used For</strong> - Jelaskan tujuan penggunaan barang (wajib)</li>
                            <li><strong>Expected Date</strong> - Tanggal kebutuhan barang (opsional)</li>
                            <li><strong>Notes</strong> - Catatan tambahan (opsional)</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 4 -->
        <div class="bg-white border border-gray-200 rounded-lg p-4">
            <div class="flex items-start">
                <span class="flex-shrink-0 w-8 h-8 flex items-center justify-center bg-emerald-600 text-white rounded-full text-sm font-bold mr-4">4</span>
                <div class="flex-1">
                    <h4 class="font-semibold text-gray-900 mb-2">Tambahkan Item dari Gudang</h4>
                    <div class="text-gray-600 text-sm space-y-2">
                        <p>Klik tombol <strong>"Add Item"</strong> untuk menambahkan item:</p>
                        <ul class="list-disc list-inside space-y-1 ml-2">
                            <li><strong>Item Name</strong> - Nama barang dari gudang (wajib)</li>
                            <li><strong>Description</strong> - Deskripsi/spesifikasi (opsional)</li>
                            <li><strong>Quantity</strong> - Jumlah yang diminta (wajib)</li>
                            <li><strong>Unit</strong> - Satuan barang</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 5 -->
        <div class="bg-white border border-gray-200 rounded-lg p-4">
            <div class="flex items-start">
                <span class="flex-shrink-0 w-8 h-8 flex items-center justify-center bg-emerald-600 text-white rounded-full text-sm font-bold mr-4">5</span>
                <div class="flex-1">
                    <h4 class="font-semibold text-gray-900 mb-2">Tentukan Approval Chain</h4>
                    <div class="text-gray-600 text-sm space-y-2">
                        <p>Pilih approver yang akan menyetujui ST Anda:</p>
                        <ul class="list-disc list-inside space-y-1 ml-2">
                            <li>Klik <strong>"Add Approver"</strong> untuk menambahkan approver</li>
                            <li>Pilih approver dari dropdown</li>
                            <li>Tentukan tipe approval</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 6 -->
        <div class="bg-white border border-gray-200 rounded-lg p-4">
            <div class="flex items-start">
                <span class="flex-shrink-0 w-8 h-8 flex items-center justify-center bg-emerald-600 text-white rounded-full text-sm font-bold mr-4">6</span>
                <div class="flex-1">
                    <h4 class="font-semibold text-gray-900 mb-2">Submit atau Save as Draft</h4>
                    <div class="text-gray-600 text-sm space-y-2">
                        <p>Pilih aksi yang sesuai:</p>
                        <div class="flex flex-wrap gap-2 mt-2">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700">
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

    <!-- ST Numbering -->
    <h3 class="text-lg font-semibold text-gray-900 mb-3">Format Nomor ST</h3>
    <div class="bg-gray-50 rounded-lg p-4 mb-6 border border-gray-200">
        <p class="text-gray-600 text-sm mb-2">
            Setiap ST akan mendapatkan nomor unik dengan format:
        </p>
        <code class="bg-white px-3 py-2 rounded border border-gray-200 text-sm text-emerald-600 block">
            ST/[BUSINESS_UNIT]/[DEPARTMENT]/[YEAR]/[SEQUENCE]
        </code>
        <p class="text-gray-500 text-xs mt-2">
            Contoh: ST/WNS/IT/2025/00001
        </p>
    </div>

    <!-- Difference with PR -->
    <h3 class="text-lg font-semibold text-gray-900 mb-3">Perbedaan dengan Purchase Request</h3>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 border border-gray-200 rounded-lg">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aspek</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Purchase Request (PR)</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Stock Request (ST)</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <tr>
                    <td class="px-4 py-3 text-sm font-medium text-gray-900">Tujuan</td>
                    <td class="px-4 py-3 text-sm text-gray-600">Pembelian barang/jasa umum</td>
                    <td class="px-4 py-3 text-sm text-gray-600">Pembelian ATK & barang consumable</td>
                </tr>
                <tr>
                    <td class="px-4 py-3 text-sm font-medium text-gray-900">Jenis Barang</td>
                    <td class="px-4 py-3 text-sm text-gray-600">Barang/jasa apapun (IT, furniture, jasa, dll)</td>
                    <td class="px-4 py-3 text-sm text-gray-600">ATK, perlengkapan kantor, barang habis pakai</td>
                </tr>
                <tr>
                    <td class="px-4 py-3 text-sm font-medium text-gray-900">Harga</td>
                    <td class="px-4 py-3 text-sm text-gray-600">Wajib diisi</td>
                    <td class="px-4 py-3 text-sm text-gray-600">Opsional (bisa diisi estimasi)</td>
                </tr>
                <tr>
                    <td class="px-4 py-3 text-sm font-medium text-gray-900">Supplier</td>
                    <td class="px-4 py-3 text-sm text-gray-600">Dapat diisi</td>
                    <td class="px-4 py-3 text-sm text-gray-600">Opsional</td>
                </tr>
                <tr>
                    <td class="px-4 py-3 text-sm font-medium text-gray-900">Kategori</td>
                    <td class="px-4 py-3 text-sm text-gray-600">Wajib dipilih</td>
                    <td class="px-4 py-3 text-sm text-gray-600">Tidak diperlukan</td>
                </tr>
                <tr>
                    <td class="px-4 py-3 text-sm font-medium text-gray-900">Contoh</td>
                    <td class="px-4 py-3 text-sm text-gray-600">Laptop, AC, Jasa Konsultan</td>
                    <td class="px-4 py-3 text-sm text-gray-600">Kertas, Pulpen, Tinta Printer</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
