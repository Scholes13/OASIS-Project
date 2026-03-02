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
                    <div className="w-full bg-white min-h-[calc(100vh-64px)] pb-12 pt-6">
                        {/* Breadcrumbs */}
                        <div className="flex items-center text-sm text-slate-500 pb-8 px-8 border-b border-slate-100 bg-white max-w-6xl mx-auto w-full">
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
                        
                        <div className="px-8 mt-10">
                            {renderArticleContent(activeArticle)}
                        </div>
                    </div>
                ) : (`

content = content.replace(/                \) : activeArticle \? \([\s\S]*?(?=                \) : \(\n                    \/\* Detailed View \(Section\) \*\/)/, newArticleView);

const newArticleContentRender = `    const renderArticleContent = (articleId: string) => {
        if (articleId === 'backdated-task') {
            return (
                <div className="max-w-6xl mx-auto flex flex-col lg:flex-row gap-16">
                    <div className="flex-1 min-w-0">
                        <div className="mb-10 border-b border-slate-100 pb-8">
                            <span className="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-[#f0f7ff] text-[#16599c] mb-5">
                                Activity Tracking
                            </span>
                            <h1 className="text-[32px] font-bold text-slate-900 mb-5 leading-tight tracking-tight">How to create a backdated task</h1>
                            <div className="flex items-center text-sm text-slate-500 gap-6">
                                <div className="flex items-center gap-2">
                                    <Clock className="w-4 h-4" />
                                    Updated 2 days ago
                                </div>
                                <div className="flex items-center gap-2">
                                    <User className="w-4 h-4" />
                                    Written by Admin Team
                                </div>
                            </div>
                        </div>

                        <div className="prose prose-slate max-w-none text-[15px]">
                            <p className="text-slate-600 leading-relaxed mb-8 text-base" id="overview">
                                Sometimes you might need to log work that was completed in the past. OASIS allows you to create tasks with a past date, subject to your department's configuration and approval policies.
                            </p>

                            <div className="bg-[#f8fafc] border-l-[3px] border-[#16599c] rounded-r-lg p-5 mb-10">
                                <div className="flex items-start">
                                    <Info className="w-5 h-5 text-[#16599c] mr-3 mt-0.5 shrink-0" />
                                    <div>
                                        <h4 className="font-semibold text-[#1e3a8a] mb-1 text-[15px]">Important Note</h4>
                                        <p className="text-[#1e40af] text-sm leading-relaxed">
                                            By default, you can only backdate tasks up to 3 working days. If you need to log activity older than that, you will be required to submit a Request Backdate Approval form.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <h2 className="text-xl font-bold text-slate-900 mb-5 mt-10 tracking-tight" id="prerequisites">Prerequisites</h2>
                            <ul className="list-disc list-outside text-slate-600 space-y-2.5 mb-10 ml-5 marker:text-slate-400">
                                <li className="pl-2">You must have an active employee account.</li>
                                <li className="pl-2">You must be assigned to the relevant department for the task category.</li>
                            </ul>

                            <h2 className="text-xl font-bold text-slate-900 mb-5 mt-10 tracking-tight" id="step-by-step-guide">Step-by-step Guide</h2>
                            <p className="text-slate-600 mb-5">Follow these steps to log a task that has already been completed:</p>
                            <ol className="list-decimal list-outside text-slate-600 space-y-3.5 mb-10 ml-5 marker:text-slate-500">
                                <li className="pl-2">Navigate to My Tasks or the Activity Dashboard.</li>
                                <li className="pl-2">Click on the Create Task button in the top right corner.</li>
                                <li className="pl-2">In the Task Form, fill out the Basic Info (Title, Description).</li>
                                <li className="pl-2">Locate the Task Date field. Click the calendar icon and select the past date.</li>
                                <li className="pl-2">Save the task. If within 3 days, it will be automatically approved.</li>
                            </ol>

                            <h2 className="text-xl font-bold text-slate-900 mb-5 mt-10 tracking-tight" id="troubleshooting">Troubleshooting</h2>
                            <p className="text-slate-600 mb-5 leading-relaxed">If you cannot select a past date, check with your department admin if your permissions have been restricted or if you have pending unresolved backdated requests.</p>
                        </div>
                    </div>

                    {/* Table of Contents - Right Sidebar */}
                    <div className="hidden lg:block w-64 flex-shrink-0 pt-2">
                        <div className="sticky top-6">
                            <h4 className="text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-5">ON THIS PAGE</h4>
                            <nav className="flex flex-col space-y-3.5 relative">
                                <div className="absolute left-0 top-0 bottom-0 w-px bg-slate-200"></div>
                                <a href="#overview" className="text-sm text-[#16599c] font-medium border-l-[2px] border-[#16599c] pl-4 -ml-px relative z-10 transition-colors">Overview</a>
                                <a href="#prerequisites" className="text-sm text-slate-500 hover:text-slate-900 border-l-[2px] border-transparent pl-4 -ml-px relative z-10 transition-colors">Prerequisites</a>
                                <a href="#step-by-step-guide" className="text-sm text-slate-500 hover:text-slate-900 border-l-[2px] border-transparent pl-4 -ml-px relative z-10 transition-colors">Step-by-step Guide</a>
                                <a href="#troubleshooting" className="text-sm text-slate-500 hover:text-slate-900 border-l-[2px] border-transparent pl-4 -ml-px relative z-10 transition-colors">Troubleshooting</a>
                            </nav>
                        </div>
                    </div>
                </div>
            );
        }

        // Fallback for other articles
        return (
            <div className="max-w-4xl mx-auto py-12 text-center mt-6">
                <FileText className="w-16 h-16 text-slate-300 mx-auto mb-4" />
                <h2 className="text-2xl font-bold text-slate-900 mb-2">Article under construction</h2>
                <p className="text-slate-500">This article is currently being written by our team.</p>
            </div>
        );
    };`;

content = content.replace(/    const renderArticleContent = \([\s\S]*?(?=    return \(\n        <>\n            <Head title="Docs & Help" \/>)/, newArticleContentRender + '\n\n');


fs.writeFileSync(filePath, content);
console.log('Fixed DocsHelp/Index.tsx to match design closely');
