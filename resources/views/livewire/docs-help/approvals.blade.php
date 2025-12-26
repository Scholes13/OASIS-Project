<div class="prose prose-indigo max-w-none">
    <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
        <svg class="w-6 h-6 mr-2 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        Approvals
    </h2>

    <p class="text-gray-600 mb-6">
        Halaman Approvals menampilkan semua request (PR dan ST) yang membutuhkan persetujuan Anda. Sebagai approver, Anda dapat menyetujui atau menolak request yang masuk.
    </p>

    <!-- Approval Types -->
    <h3 class="text-lg font-semibold text-gray-900 mb-3">Tipe Approval</h3>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        <div class="bg-indigo-50 rounded-lg p-4 border border-indigo-100">
            <div class="flex items-center mb-2">
                <svg class="w-5 h-5 text-indigo-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <h4 class="font-semibold text-indigo-900">Approval</h4>
            </div>
            <p class="text-sm text-indigo-700">Persetujuan penuh yang diperlukan untuk melanjutkan proses. Jika ditolak, request akan dikembalikan ke pembuat.</p>
        </div>

        <div class="bg-blue-50 rounded-lg p-4 border border-blue-100">
            <div class="flex items-center mb-2">
                <svg class="w-5 h-5 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                <h4 class="font-semibold text-blue-900">Acknowledge</h4>
            </div>
            <p class="text-sm text-blue-700">Konfirmasi bahwa Anda telah melihat dan mengetahui request. Biasanya untuk informasi saja.</p>
        </div>
    </div>

    <!-- How to Approve -->
    <h3 class="text-lg font-semibold text-gray-900 mb-3">Cara Melakukan Approval</h3>
    
    <div class="space-y-4 mb-8">
        <!-- Step 1 -->
        <div class="bg-white border border-gray-200 rounded-lg p-4">
            <div class="flex items-start">
                <span class="flex-shrink-0 w-8 h-8 flex items-center justify-center bg-amber-600 text-white rounded-full text-sm font-bold mr-4">1</span>
                <div class="flex-1">
                    <h4 class="font-semibold text-gray-900 mb-2">Akses Halaman Approvals</h4>
                    <p class="text-gray-600 text-sm mb-2">
                        Navigasi ke menu <strong>Purchasing → Approvals</strong> dari sidebar, atau akses langsung ke:
                    </p>
                    <code class="bg-gray-100 px-2 py-1 rounded text-sm text-amber-600">/approvals</code>
                </div>
            </div>
        </div>

        <!-- Step 2 -->
        <div class="bg-white border border-gray-200 rounded-lg p-4">
            <div class="flex items-start">
                <span class="flex-shrink-0 w-8 h-8 flex items-center justify-center bg-amber-600 text-white rounded-full text-sm font-bold mr-4">2</span>
                <div class="flex-1">
                    <h4 class="font-semibold text-gray-900 mb-2">Pilih Request yang Akan Diproses</h4>
                    <p class="text-gray-600 text-sm">
                        Pada daftar approval, klik request yang ingin Anda proses untuk melihat detail lengkapnya.
                    </p>
                </div>
            </div>
        </div>

        <!-- Step 3 -->
        <div class="bg-white border border-gray-200 rounded-lg p-4">
            <div class="flex items-start">
                <span class="flex-shrink-0 w-8 h-8 flex items-center justify-center bg-amber-600 text-white rounded-full text-sm font-bold mr-4">3</span>
                <div class="flex-1">
                    <h4 class="font-semibold text-gray-900 mb-2">Review Detail Request</h4>
                    <div class="text-gray-600 text-sm space-y-2">
                        <p>Periksa informasi berikut sebelum mengambil keputusan:</p>
                        <ul class="list-disc list-inside space-y-1 ml-2">
                            <li>Informasi pemohon dan departemen</li>
                            <li>Tujuan/purpose dari request</li>
                            <li>Daftar item yang diminta</li>
                            <li>Total nilai (untuk PR)</li>
                            <li>Dokumen pendukung (jika ada)</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 4 -->
        <div class="bg-white border border-gray-200 rounded-lg p-4">
            <div class="flex items-start">
                <span class="flex-shrink-0 w-8 h-8 flex items-center justify-center bg-amber-600 text-white rounded-full text-sm font-bold mr-4">4</span>
                <div class="flex-1">
                    <h4 class="font-semibold text-gray-900 mb-2">Approve atau Reject</h4>
                    <div class="text-gray-600 text-sm space-y-2">
                        <p>Pilih aksi yang sesuai:</p>
                        <div class="flex flex-wrap gap-2 mt-2">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700">
                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
                                </svg>
                                Approve - Setujui request
                            </span>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700">
                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                                Reject - Tolak request
                            </span>
                        </div>
                        <p class="mt-2 text-amber-600">
                            <strong>Catatan:</strong> Jika menolak, Anda wajib memberikan alasan penolakan.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Approval via Email -->
    <h3 class="text-lg font-semibold text-gray-900 mb-3">Approval via Email</h3>
    <div class="bg-blue-50 rounded-lg p-4 mb-6 border border-blue-200">
        <div class="flex items-start">
            <svg class="w-5 h-5 text-blue-600 mr-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"></path>
            </svg>
            <div>
                <h4 class="font-semibold text-blue-900 mb-1">Notifikasi Email</h4>
                <p class="text-sm text-blue-700 mb-2">
                    Anda akan menerima email notifikasi ketika ada request yang membutuhkan approval Anda. Email berisi:
                </p>
                <ul class="list-disc list-inside text-sm text-blue-700 space-y-1">
                    <li>Ringkasan request (nomor, pemohon, total)</li>
                    <li>Link langsung untuk melihat detail dan melakukan approval</li>
                    <li>QR Code untuk verifikasi dokumen</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Offline Approval -->
    <h3 class="text-lg font-semibold text-gray-900 mb-3">Offline Approval (Mark as Offline Approved)</h3>
    <div class="bg-purple-50 rounded-lg p-4 mb-6 border border-purple-200">
        <div class="flex items-start">
            <svg class="w-5 h-5 text-purple-600 mr-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"></path>
            </svg>
            <div class="flex-1">
                <h4 class="font-semibold text-purple-900 mb-2">Fitur untuk Pembuat Request (User)</h4>
                <p class="text-sm text-purple-700 mb-3">
                    Offline Approval adalah fitur yang memungkinkan <strong>pembuat request</strong> untuk menandai bahwa PR/ST telah disetujui secara manual/offline (misalnya melalui tanda tangan di dokumen fisik).
                </p>
                
                <div class="bg-white rounded-lg p-3 border border-purple-200 mb-3">
                    <h5 class="font-medium text-purple-900 text-sm mb-2">Cara Menggunakan:</h5>
                    <ol class="list-decimal list-inside text-sm text-purple-700 space-y-1">
                        <li>Buka halaman detail PR/ST yang statusnya <strong>"In Approval"</strong></li>
                        <li>Klik tombol <strong>"Mark Offline Approved"</strong> di bagian atas</li>
                        <li><strong>Upload bukti approval</strong> (wajib) - format: JPG, PNG, atau PDF</li>
                        <li>Tambahkan catatan jika diperlukan (opsional)</li>
                        <li>Klik <strong>"Confirm Offline Approval"</strong></li>
                    </ol>
                </div>

                <div class="bg-amber-50 rounded-lg p-3 border border-amber-200">
                    <div class="flex items-start">
                        <svg class="w-4 h-4 text-amber-600 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                        <div>
                            <p class="text-sm font-medium text-amber-800">Persyaratan Upload:</p>
                            <ul class="text-xs text-amber-700 mt-1 space-y-0.5">
                                <li>• Format file: <strong>JPG, PNG, atau PDF</strong></li>
                                <li>• Dokumen harus berisi bukti tanda tangan approver</li>
                                <li>• Upload bukti adalah <strong>WAJIB</strong></li>
                            </ul>
                        </div>
                    </div>
                </div>

                <p class="text-xs text-purple-600 mt-3">
                    <strong>Catatan:</strong> Fitur ini akan melewati proses approval digital dan langsung mengubah status menjadi "Approved".
                </p>
            </div>
        </div>
    </div>

    <!-- Approval Flow -->
    <h3 class="text-lg font-semibold text-gray-900 mb-3">Alur Approval</h3>
    <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex items-center">
                <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"></path>
                    </svg>
                </div>
                <span class="ml-2 text-sm font-medium text-gray-700">Requester</span>
            </div>
            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"></path>
            </svg>
            <div class="flex items-center">
                <div class="w-10 h-10 bg-amber-100 rounded-full flex items-center justify-center">
                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <span class="ml-2 text-sm font-medium text-gray-700">Approver 1</span>
            </div>
            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"></path>
            </svg>
            <div class="flex items-center">
                <div class="w-10 h-10 bg-amber-100 rounded-full flex items-center justify-center">
                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <span class="ml-2 text-sm font-medium text-gray-700">Approver 2</span>
            </div>
            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"></path>
            </svg>
            <div class="flex items-center">
                <div class="w-10 h-10 bg-emerald-100 rounded-full flex items-center justify-center">
                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"></path>
                    </svg>
                </div>
                <span class="ml-2 text-sm font-medium text-gray-700">Approved</span>
            </div>
        </div>
        <p class="text-xs text-gray-500 mt-4 text-center">
            Approval berjalan secara sequential - approver berikutnya baru bisa approve setelah approver sebelumnya selesai
        </p>
    </div>
</div>
