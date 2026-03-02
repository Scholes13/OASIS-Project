const fs = require('fs');
const path = require('path');

const filePath = path.join(__dirname, 'resources', 'js', 'inertia', 'Pages', 'DocsHelp', 'Index.tsx');
let content = fs.readFileSync(filePath, 'utf8');

const newGettingStarted = `// ============================================
// Getting Started Section
// ============================================
function GettingStartedSection() {
    return (
        <div className="max-w-none">
            <h2 className="text-2xl font-bold text-slate-900 mb-4 flex items-center">
                <Zap className="w-6 h-6 mr-3 text-[#16599c]" />
                Getting Started
            </h2>

            <p className="text-slate-600 mb-8 leading-relaxed">
                Selamat datang di <strong>Oasis</strong> (Office Administration System). Sistem ini dirancang untuk membantu Anda mengelola proses administrasi kantor dengan lebih efisien, termasuk Purchase Request, Stock Request, dan proses approval.
            </p>

            {/* Overview Cards */}
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-10">
                <div className="bg-[#f0f7ff] rounded-xl p-5 border border-[#e0f0ff]">
                    <div className="flex items-center mb-3">
                        <ShoppingCart className="w-5 h-5 text-[#16599c] mr-3" />
                        <h4 className="font-semibold text-[#1e3a8a]">Purchase Request</h4>
                    </div>
                    <p className="text-sm text-[#1e40af] leading-relaxed">Ajukan permintaan pembelian barang/jasa dengan workflow approval otomatis.</p>
                </div>

                <div className="bg-[#ecfdf5] rounded-xl p-5 border border-[#d1fae5]">
                    <div className="flex items-center mb-3">
                        <Package className="w-5 h-5 text-[#059669] mr-3" />
                        <h4 className="font-semibold text-[#064e3b]">Stock Request</h4>
                    </div>
                    <p className="text-sm text-[#065f46] leading-relaxed">Ajukan pembelian ATK dan barang consumable dengan proses approval yang lebih sederhana.</p>
                </div>

                <div className="bg-[#fffbeb] rounded-xl p-5 border border-[#fef3c7]">
                    <div className="flex items-center mb-3">
                        <CheckCircle className="w-5 h-5 text-[#d97706] mr-3" />
                        <h4 className="font-semibold text-[#78350f]">Approvals</h4>
                    </div>
                    <p className="text-sm text-[#92400e] leading-relaxed">Kelola dan proses approval untuk request yang membutuhkan persetujuan Anda.</p>
                </div>

                <div className="bg-[#faf5ff] rounded-xl p-5 border border-[#f3e8ff]">
                    <div className="flex items-center mb-3">
                        <ClipboardList className="w-5 h-5 text-[#9333ea] mr-3" />
                        <h4 className="font-semibold text-[#581c87]">Activity Tracking</h4>
                    </div>
                    <p className="text-sm text-[#6b21a8] leading-relaxed">Catat dan kelola aktivitas kerja harian dengan berbagai tampilan (list, board, calendar).</p>
                </div>

                <div className="bg-[#f8fafc] rounded-xl p-5 border border-[#e2e8f0] md:col-span-2">
                    <div className="flex items-center mb-3">
                        <BarChart3 className="w-5 h-5 text-[#3b82f6] mr-3" />
                        <h4 className="font-semibold text-[#1e3a8a]">Dashboard</h4>
                    </div>
                    <p className="text-sm text-[#2563eb] leading-relaxed">Pantau statistik dan aktivitas terkini dari semua request Anda.</p>
                </div>
            </div>

            {/* Business Unit Switcher */}
            <h3 className="text-lg font-bold text-slate-900 mb-4">Business Unit Switcher</h3>
            <div className="bg-white rounded-xl p-6 mb-10 border border-slate-200">
                <p className="text-slate-600 mb-4 text-sm leading-relaxed">
                    Jika Anda memiliki akses ke beberapa Business Unit, Anda dapat beralih antar unit menggunakan <strong>Business Unit Switcher</strong> yang terletak di header aplikasi.
                </p>
                <div className="flex items-center text-sm text-slate-500 bg-transparent py-2">
                    <ExternalLink className="w-4 h-4 mr-2 text-slate-400" />
                    <span>Klik dropdown Business Unit di header untuk beralih</span>
                </div>
            </div>

            {/* Quick Navigation */}
            <h3 className="text-lg font-bold text-slate-900 mb-4">Navigasi Cepat</h3>
            <div className="space-y-4">
                <div className="flex items-center text-slate-600 transition-colors">
                    <span className="w-6 h-6 flex-shrink-0 flex items-center justify-center bg-[#e0f0ff] text-[#16599c] rounded-full text-xs font-bold mr-4">1</span>
                    <span className="text-sm leading-relaxed"><strong>Purchasing</strong> → Dashboard, Purchase Request, Stock Request, Approvals</span>
                </div>
                <div className="flex items-center text-slate-600 transition-colors">
                    <span className="w-6 h-6 flex-shrink-0 flex items-center justify-center bg-[#e0f0ff] text-[#16599c] rounded-full text-xs font-bold mr-4">2</span>
                    <span className="text-sm leading-relaxed"><strong>Activity</strong> → Dashboard, My Tasks, Department Tasks, Analytics</span>
                </div>
                <div className="flex items-center text-slate-600 transition-colors">
                    <span className="w-6 h-6 flex-shrink-0 flex items-center justify-center bg-[#e0f0ff] text-[#16599c] rounded-full text-xs font-bold mr-4">3</span>
                    <span className="text-sm leading-relaxed"><strong>Purchasing Admin</strong> → Dashboard Admin, Tasks, History (untuk admin)</span>
                </div>
                <div className="flex items-center text-slate-600 transition-colors">
                    <span className="w-6 h-6 flex-shrink-0 flex items-center justify-center bg-[#e0f0ff] text-[#16599c] rounded-full text-xs font-bold mr-4">4</span>
                    <span className="text-sm leading-relaxed"><strong>Reports</strong> → PR Statistics, Approval Analytics (untuk manager)</span>
                </div>
            </div>
        </div>
    );
}`;

content = content.replace(/\/\/ ============================================\n\/\/ Getting Started Section\n\/\/ ============================================[\s\S]*?(?=\n\n\/\/ ============================================\n\/\/ Purchase Request Section)/, newGettingStarted);


const newArticleView = `                ) : activeArticle ? (
                    /* Article View */
                    <div className="w-full bg-white min-h-[calc(100vh-64px)] pb-12">
                        {/* Breadcrumbs */}
                        <div className="flex items-center text-sm text-slate-500 py-4 px-8 border-b border-slate-100 bg-white">
                            <button onClick={() => { setActiveArticle(null); setActiveSection(null); }} className="hover:text-[#16599c]">Support</button>
                            <ChevronRight className="w-4 h-4 mx-2 text-slate-300" />
                            <button 
                                onClick={() => { setActiveSection(popularArticles.find(a => a.id === activeArticle)?.section || null); setActiveArticle(null); }}
                                className="hover:text-[#16599c]"
                            >
                                {popularArticles.find(a => a.id === activeArticle)?.category}
                            </button>
                            <ChevronRight className="w-4 h-4 mx-2 text-slate-300" />
                            <span className="text-slate-900 font-medium truncate">{popularArticles.find(a => a.id === activeArticle)?.title}</span>
                        </div>
                        
                        <div className="px-8 mt-8">
                            {renderArticleContent(activeArticle)}
      
