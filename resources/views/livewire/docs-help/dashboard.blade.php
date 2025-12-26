<div class="prose prose-indigo max-w-none">
    <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
        <svg class="w-6 h-6 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"></path>
        </svg>
        Dashboard
    </h2>

    <p class="text-gray-600 mb-6">
        Dashboard memberikan gambaran umum tentang aktivitas dan statistik request Anda. Informasi ditampilkan secara real-time dan dapat difilter berdasarkan rentang waktu.
    </p>

    <!-- Dashboard Components -->
    <h3 class="text-lg font-semibold text-gray-900 mb-3">Komponen Dashboard</h3>
    
    <div class="space-y-4 mb-8">
        <!-- Statistics Cards -->
        <div class="bg-white border border-gray-200 rounded-lg p-4">
            <div class="flex items-start">
                <div class="flex-shrink-0 w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center mr-4">
                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5"></path>
                    </svg>
                </div>
                <div class="flex-1">
                    <h4 class="font-semibold text-gray-900 mb-2">Statistics Cards</h4>
                    <p class="text-gray-600 text-sm mb-2">
                        Kartu statistik menampilkan ringkasan jumlah request berdasarkan status:
                    </p>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                        <div class="bg-blue-50 rounded px-2 py-1 text-xs text-blue-700">Total PR</div>
                        <div class="bg-amber-50 rounded px-2 py-1 text-xs text-amber-700">Pending Approval</div>
                        <div class="bg-emerald-50 rounded px-2 py-1 text-xs text-emerald-700">Approved</div>
                        <div class="bg-red-50 rounded px-2 py-1 text-xs text-red-700">Rejected</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts -->
        <div class="bg-white border border-gray-200 rounded-lg p-4">
            <div class="flex items-start">
                <div class="flex-shrink-0 w-10 h-10 bg-emerald-100 rounded-lg flex items-center justify-center mr-4">
                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6a7.5 7.5 0 107.5 7.5h-7.5V6z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5H21A7.5 7.5 0 0013.5 3v7.5z"></path>
                    </svg>
                </div>
                <div class="flex-1">
                    <h4 class="font-semibold text-gray-900 mb-2">Charts & Grafik</h4>
                    <p class="text-gray-600 text-sm mb-2">
                        Visualisasi data dalam bentuk grafik untuk analisis yang lebih mudah:
                    </p>
                    <ul class="list-disc list-inside text-sm text-gray-600 space-y-1">
                        <li>Trend request per bulan</li>
                        <li>Distribusi status request</li>
                        <li>Request per departemen</li>
                        <li>Total nilai request</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="bg-white border border-gray-200 rounded-lg p-4">
            <div class="flex items-start">
                <div class="flex-shrink-0 w-10 h-10 bg-amber-100 rounded-lg flex items-center justify-center mr-4">
                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="flex-1">
                    <h4 class="font-semibold text-gray-900 mb-2">Recent Activity</h4>
                    <p class="text-gray-600 text-sm mb-2">
                        Daftar aktivitas terbaru yang relevan dengan Anda:
                    </p>
                    <ul class="list-disc list-inside text-sm text-gray-600 space-y-1">
                        <li>Request yang baru dibuat</li>
                        <li>Request yang diapprove/reject</li>
                        <li>Request yang menunggu approval Anda</li>
                        <li>Notifikasi sistem</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="bg-white border border-gray-200 rounded-lg p-4">
            <div class="flex items-start">
                <div class="flex-shrink-0 w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center mr-4">
                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z"></path>
                    </svg>
                </div>
                <div class="flex-1">
                    <h4 class="font-semibold text-gray-900 mb-2">Quick Actions</h4>
                    <p class="text-gray-600 text-sm mb-2">
                        Akses cepat ke fitur yang sering digunakan:
                    </p>
                    <div class="flex flex-wrap gap-2">
                        <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-indigo-100 text-indigo-700">
                            + Create PR
                        </span>
                        <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-emerald-100 text-emerald-700">
                            + Create ST
                        </span>
                        <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-amber-100 text-amber-700">
                            View Approvals
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Date Range Filter -->
    <h3 class="text-lg font-semibold text-gray-900 mb-3">Filter Rentang Waktu</h3>
    <div class="bg-gray-50 rounded-lg p-4 mb-6 border border-gray-200">
        <p class="text-gray-600 text-sm mb-3">
            Gunakan filter rentang waktu untuk melihat data pada periode tertentu:
        </p>
        <div class="flex flex-wrap gap-2">
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-white border border-gray-200 text-gray-700">Today</span>
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-white border border-gray-200 text-gray-700">This Week</span>
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-white border border-gray-200 text-gray-700">This Month</span>
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-white border border-gray-200 text-gray-700">This Quarter</span>
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-white border border-gray-200 text-gray-700">This Year</span>
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-indigo-100 text-indigo-700">Custom Range</span>
        </div>
    </div>

    <!-- Dashboard Types -->
    <h3 class="text-lg font-semibold text-gray-900 mb-3">Tipe Dashboard</h3>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="bg-blue-50 rounded-lg p-4 border border-blue-100">
            <div class="flex items-center mb-2">
                <svg class="w-5 h-5 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"></path>
                </svg>
                <h4 class="font-semibold text-blue-900">User Dashboard</h4>
            </div>
            <p class="text-sm text-blue-700">Dashboard untuk pengguna reguler. Menampilkan request yang dibuat dan status approval.</p>
            <code class="text-xs bg-blue-100 px-2 py-0.5 rounded mt-2 inline-block">/dashboard</code>
        </div>

        <div class="bg-purple-50 rounded-lg p-4 border border-purple-100">
            <div class="flex items-center mb-2">
                <svg class="w-5 h-5 text-purple-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z"></path>
                </svg>
                <h4 class="font-semibold text-purple-900">Admin Dashboard</h4>
            </div>
            <p class="text-sm text-purple-700">Dashboard untuk Purchasing Admin. Menampilkan tasks, statistik departemen, dan audit history.</p>
            <code class="text-xs bg-purple-100 px-2 py-0.5 rounded mt-2 inline-block">/purchasing/admin/dashboard</code>
        </div>
    </div>
</div>
