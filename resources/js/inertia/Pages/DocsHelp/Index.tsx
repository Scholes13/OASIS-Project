import { Head } from '@inertiajs/react';
import { User } from 'lucide-react';
import { Search, MessageCircle } from 'lucide-react';
import { useState } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import {
    BookOpen,
    ShoppingCart,
    Package,
    CheckCircle,
    BarChart3,
    HelpCircle,
    Zap,
    ChevronDown,
    ChevronRight,
    Mail,
    FileText,
    ExternalLink,
    AlertTriangle,
    Info,
    Eye,
    Users,
    Clock,
    Bookmark,
    Send,
    ClipboardList,
    Calendar,
    UserPlus,
    CalendarClock,
    Columns3,
    List,
    PieChart,
} from 'lucide-react';

type SectionKey = 'getting-started' | 'purchase-request' | 'stock-request' | 'approvals' | 'activity-tracking' | 'dashboard' | 'faq';

interface Section {
    key: SectionKey;
    label: string;
    icon: React.ElementType;
    color: string;
}

const sections: Section[] = [
    { key: 'getting-started', label: 'Getting Started', icon: Zap, color: 'indigo' },
    { key: 'purchase-request', label: 'Purchase Request', icon: ShoppingCart, color: 'indigo' },
    { key: 'stock-request', label: 'Stock Request', icon: Package, color: 'emerald' },
    { key: 'approvals', label: 'Approvals', icon: CheckCircle, color: 'amber' },
    { key: 'activity-tracking', label: 'Activity Tracking', icon: ClipboardList, color: 'purple' },
    { key: 'dashboard', label: 'Dashboard', icon: BarChart3, color: 'blue' },
    { key: 'faq', label: 'FAQ', icon: HelpCircle, color: 'indigo' },
];

export default function DocsHelpIndex() {
    const [activeSection, setActiveSection] = useState<SectionKey | null>(null);
    const [activeArticle, setActiveArticle] = useState<string | null>(null);
    const [searchQuery, setSearchQuery] = useState('');

    const filteredSections = sections.filter(s => 
        s.label.toLowerCase().includes(searchQuery.toLowerCase())
    );

    const popularArticles = [
        { 
            id: 'backdated-task', 
            title: 'How to create a backdated task', 
            time: 'Updated 2 days ago', 
            section: 'activity-tracking' as SectionKey,
            author: 'Admin Team',
            category: 'Activity Tracking'
        },
        { 
            id: 'approval-workflow', 
            title: 'Approval workflow for purchases over $5,000', 
            time: 'Updated 1 week ago', 
            section: 'approvals' as SectionKey,
            author: 'Purchasing Team',
            category: 'Approvals'
        },
        { 
            id: 'stock-vs-purchase', 
            title: 'Understanding Stock Request vs Purchase Request', 
            time: 'Updated 2 weeks ago', 
            section: 'faq' as SectionKey,
            author: 'Admin Team',
            category: 'FAQ'
        },
        { 
            id: 'offline-approval', 
            title: 'Using offline approval (Mark as Offline Approved)', 
            time: 'Updated 1 month ago', 
            section: 'approvals' as SectionKey,
            author: 'Purchasing Team',
            category: 'Approvals'
        }
    ];

    const renderArticleContent = (articleId: string) => {
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
    };

    return (
        <>
            <Head title="Docs & Help" />
            <div className="min-h-screen bg-[#f8fafc]">
                {/* Main Content Area */}
                {!activeSection && !activeArticle ? (
                    <div className="max-w-5xl mx-auto px-6 py-12">
                        {/* Hero Search */}
                        <div className="text-center mb-12">
                            <h1 className="text-3xl font-bold text-slate-900 mb-3">How can we help you?</h1>
                            <p className="text-slate-500 mb-8">
                                Search for guides, API docs, and troubleshooting tips.
                            </p>
                            <div className="max-w-2xl mx-auto relative">
                                <div className="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <Search className="h-5 w-5 text-slate-400" />
                                </div>
                                <input
                                    type="text"
                                    className="block w-full pl-11 pr-4 py-3 border border-slate-200 rounded-xl text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-[#16599c] focus:border-transparent shadow-sm transition-shadow"
                                    placeholder="Search documentation..."
                                    value={searchQuery}
                                    onChange={(e) => setSearchQuery(e.target.value)}
                                />
                            </div>
                        </div>

                        {/* Categories Grid */}
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-12">
                            {filteredSections.map((section) => {
                                const Icon = section.icon;
                                return (
                                    <div 
                                        key={section.key}
                                        onClick={() => setActiveSection(section.key)}
                                        className="bg-white border border-slate-200 rounded-xl p-6 cursor-pointer hover:shadow-md hover:border-[#16599c] transition-all group"
                                    >
                                        <div className="w-12 h-12 bg-blue-50 rounded-lg flex items-center justify-center mb-4 text-[#16599c] group-hover:scale-110 transition-transform">
                                            <Icon className="w-6 h-6" />
                                        </div>
                                        <h3 className="text-lg font-semibold text-slate-900 mb-2">{section.label}</h3>
                                        <p className="text-sm text-slate-500 line-clamp-2">
                                            {section.key === 'getting-started' && 'Learn the basics of OASIS, account setup, and quick navigation tips.'}
                                            {section.key === 'purchase-request' && 'Guides on creating purchase requests and submission workflow.'}
                                            {section.key === 'stock-request' && 'Learn how to request consumable items and office supplies.'}
                                            {section.key === 'approvals' && 'Understanding the approval process, sequential flows, and offline approvals.'}
                                            {section.key === 'activity-tracking' && 'Managing tasks, timelines, team assignments, and reporting.'}
                                            {section.key === 'dashboard' && 'Understanding statistics, exports, and data filters.'}
                                            {section.key === 'faq' && 'Frequently asked questions and troubleshooting guides.'}
                                        </p>
                                    </div>
                                );
                            })}
                        </div>

                        {/* Popular Articles */}
                        <div className="mb-12">
                            <h3 className="text-xl font-semibold text-slate-900 mb-4">Popular Articles</h3>
                            <div className="bg-white border border-slate-200 rounded-xl overflow-hidden">
                                {popularArticles.map((article) => (
                                    <div 
                                        key={article.id} 
                                        onClick={() => setActiveArticle(article.id)}
                                        className="flex items-center justify-between p-4 border-b border-slate-100 last:border-0 hover:bg-slate-50 cursor-pointer transition-colors"
                                    >
                                        <div className="flex items-center text-sm font-medium text-slate-700">
                                            <FileText className="w-5 h-5 text-[#16599c] mr-3" />
                                            {article.title}
                                        </div>
                                        <div className="flex items-center text-xs text-slate-400">
                                            <span>{article.time}</span>
                                            <ChevronRight className="w-4 h-4 ml-3" />
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>

                        {/* Support Banner */}
                        <div className="bg-gradient-to-br from-[#eff6ff] to-[#dbeafe] rounded-xl p-8 flex flex-col md:flex-row items-center justify-between gap-6">
                            <div>
                                <h3 className="text-lg font-semibold text-[#1e40af] mb-1">Still need help?</h3>
                                <p className="text-[#1e3a8a] text-sm">Our support team is available Mon-Fri, 9am - 6pm.</p>
                            </div>
                            <button className="flex items-center px-5 py-2.5 bg-[#16599c] text-white rounded-lg text-sm font-medium hover:bg-[#124a82] transition-colors shrink-0">
                                <MessageCircle className="w-4 h-4 mr-2" />
                                Contact Support
                            </button>
                        </div>
                    </div>
                ) : activeArticle ? (
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
                ) : (
                    /* Detailed View (Section) */
                    <div className="max-w-7xl mx-auto px-6 py-8">
                        <button 
                            onClick={() => setActiveSection(null)}
                            className="flex items-center text-sm text-slate-500 hover:text-[#16599c] mb-6 transition-colors font-medium"
                        >
                            <ChevronRight className="w-4 h-4 mr-1 rotate-180" />
                            Back to Help Center
                        </button>
                        
                        <div className="flex flex-col lg:flex-row gap-8">
                            {/* Sidebar Navigation */}
                            <div className="lg:w-64 flex-shrink-0">
                                <nav className="bg-white rounded-xl border border-slate-200 overflow-hidden sticky top-6">
                                    <div className="px-5 py-4 border-b border-slate-100">
                                        <h3 className="text-xs font-bold text-slate-400 uppercase tracking-wider">Categories</h3>
                                    </div>
                                    <div className="p-3 space-y-1">
                                        {sections.map((section) => {
                                            const Icon = section.icon;
                                            return (
                                                <button
                                                    key={section.key}
                                                    onClick={() => setActiveSection(section.key)}
                                                    className={`w-full text-left px-3 py-2.5 rounded-lg text-sm transition-colors flex items-center gap-3 ${
                                                        activeSection === section.key
                                                            ? 'bg-blue-50 text-[#16599c] font-medium'
                                                            : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'
                                                    }`}
                                                >
                                                    <Icon className="w-4 h-4" />
                                                    {section.label}
                                                </button>
                                            );
                                        })}
                                    </div>
                                </nav>
                            </div>

                            {/* Main Content */}
                            <div className="flex-1 min-w-0">
                                <div className="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm">
                                    <div className="p-8">
                                        <AnimatePresence mode="wait">
                                            <motion.div
                                                key={activeSection}
                                                initial={{ opacity: 0, y: 10 }}
                                                animate={{ opacity: 1, y: 0 }}
                                                exit={{ opacity: 0, y: -10 }}
                                                transition={{ duration: 0.2 }}
                                            >
                                                {activeSection === 'getting-started' && <GettingStartedSection />}
                                                {activeSection === 'purchase-request' && <PurchaseRequestSection />}
                                                {activeSection === 'stock-request' && <StockRequestSection />}
                                                {activeSection === 'approvals' && <ApprovalsSection />}
                                                {activeSection === 'activity-tracking' && <ActivityTrackingSection />}
                                                {activeSection === 'dashboard' && <DashboardSection />}
                                                {activeSection === 'faq' && <FAQSection />}
                                            </motion.div>
                                        </AnimatePresence>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                )}
            </div>
        </>
    );
}

// ============================================
// Getting Started Section
// ============================================
function GettingStartedSection() {
    return (
        <div className="prose prose-indigo max-w-none">
            <h2 className="text-xl font-bold text-gray-900 mb-4 flex items-center">
                <Zap className="w-6 h-6 mr-2 text-indigo-600" />
                Getting Started
            </h2>

            <p className="text-gray-600 mb-6">
                Selamat datang di <strong>Oasis</strong> (Office Administration System). Sistem ini dirancang untuk membantu Anda mengelola proses administrasi kantor dengan lebih efisien, termasuk Purchase Request, Stock Request, dan proses approval.
            </p>

            {/* Overview Cards */}
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
                <div className="bg-indigo-50 rounded-lg p-4 border border-indigo-100">
                    <div className="flex items-center mb-2">
                        <ShoppingCart className="w-5 h-5 text-indigo-600 mr-2" />
                        <h4 className="font-semibold text-indigo-900">Purchase Request</h4>
                    </div>
                    <p className="text-sm text-indigo-700">Ajukan permintaan pembelian barang/jasa dengan workflow approval otomatis.</p>
                </div>

                <div className="bg-emerald-50 rounded-lg p-4 border border-emerald-100">
                    <div className="flex items-center mb-2">
                        <Package className="w-5 h-5 text-emerald-600 mr-2" />
                        <h4 className="font-semibold text-emerald-900">Stock Request</h4>
                    </div>
                    <p className="text-sm text-emerald-700">Ajukan pembelian ATK dan barang consumable dengan proses approval yang lebih sederhana.</p>
                </div>

                <div className="bg-amber-50 rounded-lg p-4 border border-amber-100">
                    <div className="flex items-center mb-2">
                        <CheckCircle className="w-5 h-5 text-amber-600 mr-2" />
                        <h4 className="font-semibold text-amber-900">Approvals</h4>
                    </div>
                    <p className="text-sm text-amber-700">Kelola dan proses approval untuk request yang membutuhkan persetujuan Anda.</p>
                </div>

                <div className="bg-purple-50 rounded-lg p-4 border border-purple-100">
                    <div className="flex items-center mb-2">
                        <ClipboardList className="w-5 h-5 text-purple-600 mr-2" />
                        <h4 className="font-semibold text-purple-900">Activity Tracking</h4>
                    </div>
                    <p className="text-sm text-purple-700">Catat dan kelola aktivitas kerja harian dengan berbagai tampilan (list, board, calendar).</p>
                </div>

                <div className="bg-blue-50 rounded-lg p-4 border border-blue-100 md:col-span-2">
                    <div className="flex items-center mb-2">
                        <BarChart3 className="w-5 h-5 text-blue-600 mr-2" />
                        <h4 className="font-semibold text-blue-900">Dashboard</h4>
                    </div>
                    <p className="text-sm text-blue-700">Pantau statistik dan aktivitas terkini dari semua request Anda.</p>
                </div>
            </div>

            {/* Business Unit Switcher */}
            <h3 className="text-lg font-semibold text-gray-900 mb-3">Business Unit Switcher</h3>
            <div className="bg-gray-50 rounded-lg p-4 mb-6 border border-gray-200">
                <p className="text-gray-600 mb-3">
                    Jika Anda memiliki akses ke beberapa Business Unit, Anda dapat beralih antar unit menggunakan <strong>Business Unit Switcher</strong> yang terletak di header aplikasi.
                </p>
                <div className="flex items-center space-x-2 text-sm text-gray-500">
                    <ExternalLink className="w-4 h-4" />
                    <span>Klik dropdown Business Unit di header untuk beralih</span>
                </div>
            </div>

            {/* Quick Navigation */}
            <h3 className="text-lg font-semibold text-gray-900 mb-3">Navigasi Cepat</h3>
            <div className="space-y-2">
                <div className="flex items-center text-gray-600">
                    <span className="w-6 h-6 flex items-center justify-center bg-indigo-100 text-indigo-600 rounded-full text-xs font-medium mr-3">1</span>
                    <span><strong>Purchasing</strong> → Dashboard, Purchase Request, Stock Request, Approvals</span>
                </div>
                <div className="flex items-center text-gray-600">
                    <span className="w-6 h-6 flex items-center justify-center bg-indigo-100 text-indigo-600 rounded-full text-xs font-medium mr-3">2</span>
                    <span><strong>Activity</strong> → Dashboard, My Tasks, Department Tasks, Analytics</span>
                </div>
                <div className="flex items-center text-gray-600">
                    <span className="w-6 h-6 flex items-center justify-center bg-indigo-100 text-indigo-600 rounded-full text-xs font-medium mr-3">3</span>
                    <span><strong>Purchasing Admin</strong> → Dashboard Admin, Tasks, History (untuk admin)</span>
                </div>
                <div className="flex items-center text-gray-600">
                    <span className="w-6 h-6 flex items-center justify-center bg-indigo-100 text-indigo-600 rounded-full text-xs font-medium mr-3">4</span>
                    <span><strong>Reports</strong> → PR Statistics, Approval Analytics (untuk manager)</span>
                </div>
            </div>
        </div>
    );
}

// ============================================
// Purchase Request Section
// ============================================
function PurchaseRequestSection() {
    return (
        <div className="prose prose-indigo max-w-none">
            <h2 className="text-xl font-bold text-gray-900 mb-4 flex items-center">
                <ShoppingCart className="w-6 h-6 mr-2 text-indigo-600" />
                Purchase Request (PR)
            </h2>

            <p className="text-gray-600 mb-6">
                Purchase Request adalah fitur untuk mengajukan permintaan pembelian barang atau jasa. Setiap PR akan melalui proses approval sesuai dengan workflow yang telah ditentukan.
            </p>

            {/* Workflow Status */}
            <h3 className="text-lg font-semibold text-gray-900 mb-3">Status Workflow</h3>
            <div className="grid grid-cols-2 md:grid-cols-3 gap-3 mb-6">
                <StatusBadge color="gray" label="Draft" description="Masih dapat diedit" />
                <StatusBadge color="blue" label="Submitted" description="Menunggu approval" />
                <StatusBadge color="amber" label="In Approval" description="Sedang diproses" />
                <StatusBadge color="emerald" label="Approved" description="Disetujui" />
                <StatusBadge color="red" label="Rejected" description="Ditolak" />
                <StatusBadge color="gray" label="Voided" description="Dibatalkan" darker />
            </div>

            {/* Step by Step Guide */}
            <h3 className="text-lg font-semibold text-gray-900 mb-3">Cara Membuat Purchase Request</h3>
            
            <div className="space-y-4 mb-8">
                <StepCard step={1} title="Akses Halaman Purchase Request" color="indigo">
                    <p className="text-gray-600 text-sm mb-2">
                        Navigasi ke menu <strong>Purchasing → Purchase Request</strong> dari sidebar, atau akses langsung ke:
                    </p>
                    <code className="bg-gray-100 px-2 py-1 rounded text-sm text-indigo-600">/purchase-requests</code>
                </StepCard>

                <StepCard step={2} title='Klik Tombol "Create New PR"' color="indigo">
                    <p className="text-gray-600 text-sm">
                        Pada halaman daftar Purchase Request, klik tombol <strong>"Create New PR"</strong> di bagian kanan atas untuk membuat PR baru.
                    </p>
                </StepCard>

                <StepCard step={3} title="Isi Informasi Purchase Request" color="indigo">
                    <div className="text-gray-600 text-sm space-y-2">
                        <p>Lengkapi form dengan informasi berikut:</p>
                        <ul className="list-disc list-inside space-y-1 ml-2">
                            <li><strong>Category</strong> - Pilih kategori PR (wajib)</li>
                            <li><strong>Purpose / Used For</strong> - Jelaskan tujuan pembelian (wajib, min. 10 karakter)</li>
                            <li><strong>Expected Delivery Date</strong> - Tanggal pengiriman yang diharapkan (opsional)</li>
                            <li><strong>Currency</strong> - Mata uang (IDR, USD, EUR, SGD)</li>
                            <li><strong>Supporting Document</strong> - Upload dokumen pendukung jika ada (PDF, Word, Excel)</li>
                        </ul>
                    </div>
                </StepCard>

                <StepCard step={4} title="Tambahkan Item" color="indigo">
                    <div className="text-gray-600 text-sm space-y-2">
                        <p>Klik tombol <strong>"Add Item"</strong> untuk menambahkan item yang akan dibeli:</p>
                        <ul className="list-disc list-inside space-y-1 ml-2">
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
                </StepCard>

                <StepCard step={5} title="Tentukan Approval Chain" color="indigo">
                    <div className="text-gray-600 text-sm space-y-2">
                        <p>Pilih approver yang akan menyetujui PR Anda:</p>
                        <ul className="list-disc list-inside space-y-1 ml-2">
                            <li>Klik <strong>"Add Approver"</strong> untuk menambahkan approver</li>
                            <li>Pilih approver dari dropdown (berdasarkan hierarki organisasi)</li>
                            <li>Tentukan tipe approval: <strong>Approval</strong> atau <strong>Acknowledge</strong></li>
                            <li>Urutan approver menentukan urutan approval (sequential)</li>
                        </ul>
                    </div>
                </StepCard>

                <StepCard step={6} title="Submit atau Save as Draft" color="indigo">
                    <div className="text-gray-600 text-sm space-y-2">
                        <p>Pilih aksi yang sesuai:</p>
                        <div className="flex flex-wrap gap-2 mt-2">
                            <span className="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-indigo-100 text-indigo-700">
                                <Send className="w-3 h-3 mr-1" />
                                Submit - Kirim untuk approval
                            </span>
                            <span className="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700">
                                <Bookmark className="w-3 h-3 mr-1" />
                                Save Draft - Simpan untuk dilanjutkan nanti
                            </span>
                        </div>
                    </div>
                </StepCard>
            </div>

            {/* PR Numbering */}
            <h3 className="text-lg font-semibold text-gray-900 mb-3">Format Nomor PR</h3>
            <div className="bg-gray-50 rounded-lg p-4 mb-6 border border-gray-200">
                <p className="text-gray-600 text-sm mb-2">
                    Setiap PR akan mendapatkan nomor unik dengan format:
                </p>
                <code className="bg-white px-3 py-2 rounded border border-gray-200 text-sm text-indigo-600 block">
                    PR/[BUSINESS_UNIT]/[DEPARTMENT]/[YEAR]/[SEQUENCE]
                </code>
                <p className="text-gray-500 text-xs mt-2">
                    Contoh: PR/WNS/IT/2025/00001
                </p>
            </div>

            {/* Tips */}
            <h3 className="text-lg font-semibold text-gray-900 mb-3">Tips</h3>
            <div className="bg-amber-50 rounded-lg p-4 border border-amber-200">
                <ul className="space-y-2 text-sm text-amber-800">
                    <li className="flex items-start">
                        <AlertTriangle className="w-4 h-4 mr-2 mt-0.5 text-amber-600" />
                        <span>Pastikan semua informasi sudah benar sebelum submit, karena PR yang sudah disubmit tidak dapat diedit.</span>
                    </li>
                    <li className="flex items-start">
                        <Info className="w-4 h-4 mr-2 mt-0.5 text-amber-600" />
                        <span>Gunakan fitur "Save Draft" jika Anda belum yakin dengan data yang diinput.</span>
                    </li>
                    <li className="flex items-start">
                        <CheckCircle className="w-4 h-4 mr-2 mt-0.5 text-amber-600" />
                        <span>Upload gambar item untuk mempermudah approver memahami barang yang diminta.</span>
                    </li>
                </ul>
            </div>
        </div>
    );
}


// ============================================
// Stock Request Section
// ============================================
function StockRequestSection() {
    return (
        <div className="prose prose-indigo max-w-none">
            <h2 className="text-xl font-bold text-gray-900 mb-4 flex items-center">
                <Package className="w-6 h-6 mr-2 text-emerald-600" />
                Stock Request (ST)
            </h2>

            <p className="text-gray-600 mb-6">
                Stock Request adalah fitur untuk mengajukan permintaan pembelian barang-barang <strong>consumable</strong> seperti ATK (Alat Tulis Kantor), perlengkapan kantor, dan asset yang bisa habis pakai. Berbeda dengan Purchase Request yang untuk barang/jasa umum, Stock Request khusus untuk kebutuhan operasional rutin.
            </p>

            {/* Use Cases */}
            <div className="bg-emerald-50 rounded-lg p-4 mb-6 border border-emerald-200">
                <h4 className="font-semibold text-emerald-900 mb-2">Contoh Penggunaan Stock Request:</h4>
                <div className="grid grid-cols-2 md:grid-cols-4 gap-2">
                    {['📝 Kertas A4', '🖊️ Pulpen/Pensil', '📁 Map/Folder', '🖨️ Tinta Printer', '📎 Stapler/Clip', '📒 Buku Tulis', '🧹 Alat Kebersihan', '💡 Lampu/Bohlam'].map((item) => (
                        <span key={item} className="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-white text-emerald-700 border border-emerald-200">
                            {item}
                        </span>
                    ))}
                </div>
            </div>

            {/* Workflow Status */}
            <h3 className="text-lg font-semibold text-gray-900 mb-3">Status Workflow</h3>
            <div className="grid grid-cols-2 md:grid-cols-3 gap-3 mb-6">
                <StatusBadge color="gray" label="Draft" description="Masih dapat diedit" />
                <StatusBadge color="blue" label="Submitted" description="Menunggu approval" />
                <StatusBadge color="amber" label="In Approval" description="Sedang diproses" />
                <StatusBadge color="emerald" label="Approved" description="Disetujui" />
                <StatusBadge color="red" label="Rejected" description="Ditolak" />
                <StatusBadge color="gray" label="Voided" description="Dibatalkan" darker />
            </div>

            {/* Step by Step Guide */}
            <h3 className="text-lg font-semibold text-gray-900 mb-3">Cara Membuat Stock Request</h3>
            
            <div className="space-y-4 mb-8">
                <StepCard step={1} title="Akses Halaman Stock Request" color="emerald">
                    <p className="text-gray-600 text-sm mb-2">
                        Navigasi ke menu <strong>Purchasing → Stock Request</strong> dari sidebar, atau akses langsung ke:
                    </p>
                    <code className="bg-gray-100 px-2 py-1 rounded text-sm text-emerald-600">/stock-requests</code>
                </StepCard>

                <StepCard step={2} title='Klik Tombol "Create New ST"' color="emerald">
                    <p className="text-gray-600 text-sm">
                        Pada halaman daftar Stock Request, klik tombol <strong>"Create New ST"</strong> di bagian kanan atas untuk membuat ST baru.
                    </p>
                </StepCard>

                <StepCard step={3} title="Isi Informasi Stock Request" color="emerald">
                    <div className="text-gray-600 text-sm space-y-2">
                        <p>Lengkapi form dengan informasi berikut:</p>
                        <ul className="list-disc list-inside space-y-1 ml-2">
                            <li><strong>Purpose / Used For</strong> - Jelaskan tujuan penggunaan barang (wajib)</li>
                            <li><strong>Expected Date</strong> - Tanggal kebutuhan barang (opsional)</li>
                            <li><strong>Notes</strong> - Catatan tambahan (opsional)</li>
                        </ul>
                    </div>
                </StepCard>

                <StepCard step={4} title="Tambahkan Item dari Gudang" color="emerald">
                    <div className="text-gray-600 text-sm space-y-2">
                        <p>Klik tombol <strong>"Add Item"</strong> untuk menambahkan item:</p>
                        <ul className="list-disc list-inside space-y-1 ml-2">
                            <li><strong>Item Name</strong> - Nama barang dari gudang (wajib)</li>
                            <li><strong>Description</strong> - Deskripsi/spesifikasi (opsional)</li>
                            <li><strong>Quantity</strong> - Jumlah yang diminta (wajib)</li>
                            <li><strong>Unit</strong> - Satuan barang</li>
                        </ul>
                    </div>
                </StepCard>

                <StepCard step={5} title="Tentukan Approval Chain" color="emerald">
                    <div className="text-gray-600 text-sm space-y-2">
                        <p>Pilih approver yang akan menyetujui ST Anda:</p>
                        <ul className="list-disc list-inside space-y-1 ml-2">
                            <li>Klik <strong>"Add Approver"</strong> untuk menambahkan approver</li>
                            <li>Pilih approver dari dropdown</li>
                            <li>Tentukan tipe approval</li>
                        </ul>
                    </div>
                </StepCard>

                <StepCard step={6} title="Submit atau Save as Draft" color="emerald">
                    <div className="text-gray-600 text-sm space-y-2">
                        <p>Pilih aksi yang sesuai:</p>
                        <div className="flex flex-wrap gap-2 mt-2">
                            <span className="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700">
                                <Send className="w-3 h-3 mr-1" />
                                Submit - Kirim untuk approval
                            </span>
                            <span className="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700">
                                <Bookmark className="w-3 h-3 mr-1" />
                                Save Draft - Simpan untuk dilanjutkan nanti
                            </span>
                        </div>
                    </div>
                </StepCard>
            </div>

            {/* ST Numbering */}
            <h3 className="text-lg font-semibold text-gray-900 mb-3">Format Nomor ST</h3>
            <div className="bg-gray-50 rounded-lg p-4 mb-6 border border-gray-200">
                <p className="text-gray-600 text-sm mb-2">
                    Setiap ST akan mendapatkan nomor unik dengan format:
                </p>
                <code className="bg-white px-3 py-2 rounded border border-gray-200 text-sm text-emerald-600 block">
                    ST/[BUSINESS_UNIT]/[DEPARTMENT]/[YEAR]/[SEQUENCE]
                </code>
                <p className="text-gray-500 text-xs mt-2">
                    Contoh: ST/WNS/IT/2025/00001
                </p>
            </div>

            {/* Difference with PR */}
            <h3 className="text-lg font-semibold text-gray-900 mb-3">Perbedaan dengan Purchase Request</h3>
            <div className="overflow-x-auto">
                <table className="min-w-full divide-y divide-gray-200 border border-gray-200 rounded-lg">
                    <thead className="bg-gray-50">
                        <tr>
                            <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aspek</th>
                            <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Purchase Request (PR)</th>
                            <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Stock Request (ST)</th>
                        </tr>
                    </thead>
                    <tbody className="bg-white divide-y divide-gray-200">
                        <tr>
                            <td className="px-4 py-3 text-sm font-medium text-gray-900">Tujuan</td>
                            <td className="px-4 py-3 text-sm text-gray-600">Pembelian barang/jasa umum</td>
                            <td className="px-4 py-3 text-sm text-gray-600">Pembelian ATK & barang consumable</td>
                        </tr>
                        <tr>
                            <td className="px-4 py-3 text-sm font-medium text-gray-900">Jenis Barang</td>
                            <td className="px-4 py-3 text-sm text-gray-600">Barang/jasa apapun (IT, furniture, jasa, dll)</td>
                            <td className="px-4 py-3 text-sm text-gray-600">ATK, perlengkapan kantor, barang habis pakai</td>
                        </tr>
                        <tr>
                            <td className="px-4 py-3 text-sm font-medium text-gray-900">Harga</td>
                            <td className="px-4 py-3 text-sm text-gray-600">Wajib diisi</td>
                            <td className="px-4 py-3 text-sm text-gray-600">Opsional (bisa diisi estimasi)</td>
                        </tr>
                        <tr>
                            <td className="px-4 py-3 text-sm font-medium text-gray-900">Supplier</td>
                            <td className="px-4 py-3 text-sm text-gray-600">Dapat diisi</td>
                            <td className="px-4 py-3 text-sm text-gray-600">Opsional</td>
                        </tr>
                        <tr>
                            <td className="px-4 py-3 text-sm font-medium text-gray-900">Kategori</td>
                            <td className="px-4 py-3 text-sm text-gray-600">Wajib dipilih</td>
                            <td className="px-4 py-3 text-sm text-gray-600">Tidak diperlukan</td>
                        </tr>
                        <tr>
                            <td className="px-4 py-3 text-sm font-medium text-gray-900">Contoh</td>
                            <td className="px-4 py-3 text-sm text-gray-600">Laptop, AC, Jasa Konsultan</td>
                            <td className="px-4 py-3 text-sm text-gray-600">Kertas, Pulpen, Tinta Printer</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    );
}


// ============================================
// Approvals Section
// ============================================
function ApprovalsSection() {
    return (
        <div className="prose prose-indigo max-w-none">
            <h2 className="text-xl font-bold text-gray-900 mb-4 flex items-center">
                <CheckCircle className="w-6 h-6 mr-2 text-amber-600" />
                Approvals
            </h2>

            <p className="text-gray-600 mb-6">
                Halaman Approvals menampilkan semua request (PR dan ST) yang membutuhkan persetujuan Anda. Sebagai approver, Anda dapat menyetujui atau menolak request yang masuk.
            </p>

            {/* Approval Types */}
            <h3 className="text-lg font-semibold text-gray-900 mb-3">Tipe Approval</h3>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div className="bg-indigo-50 rounded-lg p-4 border border-indigo-100">
                    <div className="flex items-center mb-2">
                        <CheckCircle className="w-5 h-5 text-indigo-600 mr-2" />
                        <h4 className="font-semibold text-indigo-900">Approval</h4>
                    </div>
                    <p className="text-sm text-indigo-700">Persetujuan penuh yang diperlukan untuk melanjutkan proses. Jika ditolak, request akan dikembalikan ke pembuat.</p>
                </div>

                <div className="bg-blue-50 rounded-lg p-4 border border-blue-100">
                    <div className="flex items-center mb-2">
                        <Eye className="w-5 h-5 text-blue-600 mr-2" />
                        <h4 className="font-semibold text-blue-900">Acknowledge</h4>
                    </div>
                    <p className="text-sm text-blue-700">Konfirmasi bahwa Anda telah melihat dan mengetahui request. Biasanya untuk informasi saja.</p>
                </div>
            </div>

            {/* How to Approve */}
            <h3 className="text-lg font-semibold text-gray-900 mb-3">Cara Melakukan Approval</h3>
            
            <div className="space-y-4 mb-8">
                <StepCard step={1} title="Akses Halaman Approvals" color="amber">
                    <p className="text-gray-600 text-sm mb-2">
                        Navigasi ke menu <strong>Purchasing → Approvals</strong> dari sidebar, atau akses langsung ke:
                    </p>
                    <code className="bg-gray-100 px-2 py-1 rounded text-sm text-amber-600">/approvals</code>
                </StepCard>

                <StepCard step={2} title="Pilih Request yang Akan Diproses" color="amber">
                    <p className="text-gray-600 text-sm">
                        Pada daftar approval, klik request yang ingin Anda proses untuk melihat detail lengkapnya.
                    </p>
                </StepCard>

                <StepCard step={3} title="Review Detail Request" color="amber">
                    <div className="text-gray-600 text-sm space-y-2">
                        <p>Periksa informasi berikut sebelum mengambil keputusan:</p>
                        <ul className="list-disc list-inside space-y-1 ml-2">
                            <li>Informasi pemohon dan departemen</li>
                            <li>Tujuan/purpose dari request</li>
                            <li>Daftar item yang diminta</li>
                            <li>Total nilai (untuk PR)</li>
                            <li>Dokumen pendukung (jika ada)</li>
                        </ul>
                    </div>
                </StepCard>

                <StepCard step={4} title="Approve atau Reject" color="amber">
                    <div className="text-gray-600 text-sm space-y-2">
                        <p>Pilih aksi yang sesuai:</p>
                        <div className="flex flex-wrap gap-2 mt-2">
                            <span className="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700">
                                <CheckCircle className="w-3 h-3 mr-1" />
                                Approve - Setujui request
                            </span>
                            <span className="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700">
                                ✕ Reject - Tolak request
                            </span>
                        </div>
                        <p className="mt-2 text-amber-600">
                            <strong>Catatan:</strong> Jika menolak, Anda wajib memberikan alasan penolakan.
                        </p>
                    </div>
                </StepCard>
            </div>

            {/* Approval via Email */}
            <h3 className="text-lg font-semibold text-gray-900 mb-3">Approval via Email</h3>
            <div className="bg-blue-50 rounded-lg p-4 mb-6 border border-blue-200">
                <div className="flex items-start">
                    <Mail className="w-5 h-5 text-blue-600 mr-3 mt-0.5" />
                    <div>
                        <h4 className="font-semibold text-blue-900 mb-1">Notifikasi Email</h4>
                        <p className="text-sm text-blue-700 mb-2">
                            Anda akan menerima email notifikasi ketika ada request yang membutuhkan approval Anda. Email berisi:
                        </p>
                        <ul className="list-disc list-inside text-sm text-blue-700 space-y-1">
                            <li>Ringkasan request (nomor, pemohon, total)</li>
                            <li>Link langsung untuk melihat detail dan melakukan approval</li>
                            <li>QR Code untuk verifikasi dokumen</li>
                        </ul>
                    </div>
                </div>
            </div>

            {/* Offline Approval */}
            <h3 className="text-lg font-semibold text-gray-900 mb-3">Offline Approval (Mark as Offline Approved)</h3>
            <div className="bg-purple-50 rounded-lg p-4 mb-6 border border-purple-200">
                <div className="flex items-start">
                    <FileText className="w-5 h-5 text-purple-600 mr-3 mt-0.5" />
                    <div className="flex-1">
                        <h4 className="font-semibold text-purple-900 mb-2">Fitur untuk Pembuat Request (User)</h4>
                        <p className="text-sm text-purple-700 mb-3">
                            Offline Approval adalah fitur yang memungkinkan <strong>pembuat request</strong> untuk menandai bahwa PR/ST telah disetujui secara manual/offline (misalnya melalui tanda tangan di dokumen fisik).
                        </p>
                        
                        <div className="bg-white rounded-lg p-3 border border-purple-200 mb-3">
                            <h5 className="font-medium text-purple-900 text-sm mb-2">Cara Menggunakan:</h5>
                            <ol className="list-decimal list-inside text-sm text-purple-700 space-y-1">
                                <li>Buka halaman detail PR/ST yang statusnya <strong>"In Approval"</strong></li>
                                <li>Klik tombol <strong>"Mark Offline Approved"</strong> di bagian atas</li>
                                <li><strong>Upload bukti approval</strong> (wajib) - format: JPG, PNG, atau PDF</li>
                                <li>Tambahkan catatan jika diperlukan (opsional)</li>
                                <li>Klik <strong>"Confirm Offline Approval"</strong></li>
                            </ol>
                        </div>

                        <div className="bg-amber-50 rounded-lg p-3 border border-amber-200">
                            <div className="flex items-start">
                                <AlertTriangle className="w-4 h-4 text-amber-600 mr-2 mt-0.5" />
                                <div>
                                    <p className="text-sm font-medium text-amber-800">Persyaratan Upload:</p>
                                    <ul className="text-xs text-amber-700 mt-1 space-y-0.5">
                                        <li>• Format file: <strong>JPG, PNG, atau PDF</strong></li>
                                        <li>• Dokumen harus berisi bukti tanda tangan approver</li>
                                        <li>• Upload bukti adalah <strong>WAJIB</strong></li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <p className="text-xs text-purple-600 mt-3">
                            <strong>Catatan:</strong> Fitur ini akan melewati proses approval digital dan langsung mengubah status menjadi "Approved".
                        </p>
                    </div>
                </div>
            </div>

            {/* Approval Flow */}
            <h3 className="text-lg font-semibold text-gray-900 mb-3">Alur Approval</h3>
            <div className="bg-gray-50 rounded-lg p-4 border border-gray-200">
                <div className="flex items-center justify-between flex-wrap gap-4">
                    <ApprovalFlowStep icon={Users} label="Requester" color="blue" />
                    <ChevronRight className="w-5 h-5 text-gray-400" />
                    <ApprovalFlowStep icon={CheckCircle} label="Approver 1" color="amber" />
                    <ChevronRight className="w-5 h-5 text-gray-400" />
                    <ApprovalFlowStep icon={CheckCircle} label="Approver 2" color="amber" />
                    <ChevronRight className="w-5 h-5 text-gray-400" />
                    <ApprovalFlowStep icon={CheckCircle} label="Approved" color="emerald" />
                </div>
                <p className="text-xs text-gray-500 mt-4 text-center">
                    Approval berjalan secara sequential - approver berikutnya baru bisa approve setelah approver sebelumnya selesai
                </p>
            </div>
        </div>
    );
}


// ============================================
// Activity Tracking Section
// ============================================
function ActivityTrackingSection() {
    return (
        <div className="prose prose-indigo max-w-none">
            <h2 className="text-xl font-bold text-gray-900 mb-4 flex items-center">
                <ClipboardList className="w-6 h-6 mr-2 text-purple-600" />
                Activity Tracking
            </h2>

            <p className="text-gray-600 mb-6">
                Activity Tracking adalah fitur untuk mencatat dan mengelola aktivitas kerja harian Anda. Fitur ini membantu Anda melacak produktivitas, berkolaborasi dengan tim, dan menganalisis pola kerja.
            </p>

            {/* Key Features */}
            <h3 className="text-lg font-semibold text-gray-900 mb-3">Fitur Utama</h3>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div className="bg-purple-50 rounded-lg p-4 border border-purple-100">
                    <div className="flex items-center mb-2">
                        <List className="w-5 h-5 text-purple-600 mr-2" />
                        <h4 className="font-semibold text-purple-900">Multiple Views</h4>
                    </div>
                    <p className="text-sm text-purple-700">Lihat task dalam berbagai tampilan: List, Kanban Board, Calendar, dan Timeline.</p>
                </div>

                <div className="bg-indigo-50 rounded-lg p-4 border border-indigo-100">
                    <div className="flex items-center mb-2">
                        <UserPlus className="w-5 h-5 text-indigo-600 mr-2" />
                        <h4 className="font-semibold text-indigo-900">Kolaborasi Tim</h4>
                    </div>
                    <p className="text-sm text-indigo-700">Tambahkan participant ke task dan bekerja bersama tim departemen Anda.</p>
                </div>

                <div className="bg-blue-50 rounded-lg p-4 border border-blue-100">
                    <div className="flex items-center mb-2">
                        <PieChart className="w-5 h-5 text-blue-600 mr-2" />
                        <h4 className="font-semibold text-blue-900">Analytics</h4>
                    </div>
                    <p className="text-sm text-blue-700">Analisis produktivitas personal dan departemen dengan grafik dan statistik.</p>
                </div>

                <div className="bg-amber-50 rounded-lg p-4 border border-amber-100">
                    <div className="flex items-center mb-2">
                        <CalendarClock className="w-5 h-5 text-amber-600 mr-2" />
                        <h4 className="font-semibold text-amber-900">Backdate System</h4>
                    </div>
                    <p className="text-sm text-amber-700">Catat aktivitas untuk tanggal kemarin atau ajukan izin backdate untuk tanggal lebih lama.</p>
                </div>
            </div>

            {/* Task Status */}
            <h3 className="text-lg font-semibold text-gray-900 mb-3">Status Task</h3>
            <div className="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
                <StatusBadge color="gray" label="Planned" description="Direncanakan" />
                <StatusBadge color="blue" label="In Progress" description="Sedang dikerjakan" />
                <StatusBadge color="emerald" label="Completed" description="Selesai" />
                <StatusBadge color="red" label="Cancelled" description="Dibatalkan" />
            </div>

            {/* Step by Step Guide */}
            <h3 className="text-lg font-semibold text-gray-900 mb-3">Cara Membuat Task</h3>
            
            <div className="space-y-4 mb-8">
                <StepCard step={1} title="Akses Halaman Activity" color="indigo">
                    <p className="text-gray-600 text-sm mb-2">
                        Navigasi ke menu <strong>Activity → Dashboard</strong> dari sidebar, atau akses langsung ke:
                    </p>
                    <code className="bg-gray-100 px-2 py-1 rounded text-sm text-purple-600">/activity</code>
                </StepCard>

                <StepCard step={2} title='Klik Tombol "New Task"' color="indigo">
                    <p className="text-gray-600 text-sm">
                        Pada halaman Activity Dashboard, klik tombol <strong>"New Task"</strong> di bagian kanan atas untuk membuat task baru.
                    </p>
                </StepCard>

                <StepCard step={3} title="Isi Informasi Task" color="indigo">
                    <div className="text-gray-600 text-sm space-y-2">
                        <p>Lengkapi form dengan informasi berikut:</p>
                        <ul className="list-disc list-inside space-y-1 ml-2">
                            <li><strong>Task Title</strong> - Judul task (wajib)</li>
                            <li><strong>Description</strong> - Deskripsi detail task (opsional)</li>
                            <li><strong>Activity Type</strong> - Pilih jenis aktivitas (wajib)</li>
                            <li><strong>Sub Activity</strong> - Pilih sub-aktivitas jika ada (opsional)</li>
                            <li><strong>Status</strong> - Status task (default: In Progress)</li>
                            <li><strong>Priority</strong> - Prioritas: Low, Medium, High</li>
                            <li><strong>Task Date</strong> - Tanggal pelaksanaan task</li>
                            <li><strong>Due Date</strong> - Tanggal deadline (opsional)</li>
                        </ul>
                    </div>
                </StepCard>

                <StepCard step={4} title="Tambahkan Participant (Opsional)" color="indigo">
                    <div className="text-gray-600 text-sm space-y-2">
                        <p>Jika task dikerjakan bersama tim:</p>
                        <ul className="list-disc list-inside space-y-1 ml-2">
                            <li>Pilih rekan kerja dari departemen yang sama</li>
                            <li>Participant dapat melihat dan update task</li>
                            <li>Task akan muncul di dashboard masing-masing participant</li>
                        </ul>
                    </div>
                </StepCard>

                <StepCard step={5} title="Simpan Task" color="indigo">
                    <p className="text-gray-600 text-sm">
                        Klik tombol <strong>"Save"</strong> untuk menyimpan task. Task akan langsung muncul di dashboard Anda.
                    </p>
                </StepCard>
            </div>

            {/* View Types */}
            <h3 className="text-lg font-semibold text-gray-900 mb-3">Tipe Tampilan</h3>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div className="bg-white border border-gray-200 rounded-lg p-4">
                    <div className="flex items-center mb-2">
                        <List className="w-5 h-5 text-gray-600 mr-2" />
                        <h4 className="font-semibold text-gray-900">List View</h4>
                    </div>
                    <p className="text-sm text-gray-600">Tampilan tabel dengan sorting dan filtering. Cocok untuk melihat banyak task sekaligus.</p>
                </div>

                <div className="bg-white border border-gray-200 rounded-lg p-4">
                    <div className="flex items-center mb-2">
                        <Columns3 className="w-5 h-5 text-gray-600 mr-2" />
                        <h4 className="font-semibold text-gray-900">Kanban Board</h4>
                    </div>
                    <p className="text-sm text-gray-600">Tampilan board dengan kolom per status. Drag & drop untuk update status.</p>
                </div>

                <div className="bg-white border border-gray-200 rounded-lg p-4">
                    <div className="flex items-center mb-2">
                        <Calendar className="w-5 h-5 text-gray-600 mr-2" />
                        <h4 className="font-semibold text-gray-900">Calendar View</h4>
                    </div>
                    <p className="text-sm text-gray-600">Tampilan kalender bulanan. Lihat task berdasarkan tanggal pelaksanaan.</p>
                </div>

                <div className="bg-white border border-gray-200 rounded-lg p-4">
                    <div className="flex items-center mb-2">
                        <Clock className="w-5 h-5 text-gray-600 mr-2" />
                        <h4 className="font-semibold text-gray-900">Timeline View</h4>
                    </div>
                    <p className="text-sm text-gray-600">Tampilan timeline kronologis. Lihat urutan task berdasarkan waktu.</p>
                </div>
            </div>

            {/* Backdate System */}
            <h3 className="text-lg font-semibold text-gray-900 mb-3">Sistem Backdate</h3>
            <div className="bg-amber-50 rounded-lg p-4 mb-6 border border-amber-200">
                <div className="flex items-start">
                    <CalendarClock className="w-5 h-5 text-amber-600 mr-3 mt-0.5" />
                    <div className="flex-1">
                        <h4 className="font-semibold text-amber-900 mb-2">Aturan Backdate</h4>
                        <ul className="text-sm text-amber-800 space-y-2">
                            <li className="flex items-start">
                                <CheckCircle className="w-4 h-4 mr-2 mt-0.5 text-amber-600" />
                                <span><strong>Default:</strong> Anda dapat membuat task untuk hari ini atau kemarin tanpa izin khusus.</span>
                            </li>
                            <li className="flex items-start">
                                <AlertTriangle className="w-4 h-4 mr-2 mt-0.5 text-amber-600" />
                                <span><strong>Tanggal lebih lama:</strong> Perlu mengajukan izin backdate ke atasan/manager.</span>
                            </li>
                            <li className="flex items-start">
                                <Clock className="w-4 h-4 mr-2 mt-0.5 text-amber-600" />
                                <span><strong>Izin terbatas:</strong> Izin backdate berlaku untuk periode tertentu (biasanya 24 jam).</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            {/* Department Tasks */}
            <h3 className="text-lg font-semibold text-gray-900 mb-3">Department Tasks</h3>
            <div className="bg-indigo-50 rounded-lg p-4 mb-6 border border-indigo-200">
                <div className="flex items-start">
                    <Users className="w-5 h-5 text-indigo-600 mr-3 mt-0.5" />
                    <div>
                        <h4 className="font-semibold text-indigo-900 mb-1">Bergabung dengan Task Departemen</h4>
                        <p className="text-sm text-indigo-700 mb-2">
                            Anda dapat melihat dan bergabung dengan task yang dibuat oleh rekan kerja di departemen yang sama.
                        </p>
                        <p className="text-sm text-indigo-700">
                            Akses melalui menu <strong>Activity → Department Tasks</strong> untuk melihat task yang bisa Anda join.
                        </p>
                    </div>
                </div>
            </div>

            {/* Analytics */}
            <h3 className="text-lg font-semibold text-gray-900 mb-3">Analytics</h3>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div className="bg-blue-50 rounded-lg p-4 border border-blue-100">
                    <div className="flex items-center mb-2">
                        <Users className="w-5 h-5 text-blue-600 mr-2" />
                        <h4 className="font-semibold text-blue-900">Personal Analytics</h4>
                    </div>
                    <p className="text-sm text-blue-700">Lihat statistik produktivitas personal: task completed, weekly progress, dan distribusi per activity type.</p>
                    <code className="text-xs bg-blue-100 px-2 py-0.5 rounded mt-2 inline-block">/activity/analytics/personal</code>
                </div>

                <div className="bg-purple-50 rounded-lg p-4 border border-purple-100">
                    <div className="flex items-center mb-2">
                        <BarChart3 className="w-5 h-5 text-purple-600 mr-2" />
                        <h4 className="font-semibold text-purple-900">Department Analytics</h4>
                    </div>
                    <p className="text-sm text-purple-700">Lihat performa tim departemen: total tasks, completion rate, dan perbandingan antar anggota.</p>
                    <code className="text-xs bg-purple-100 px-2 py-0.5 rounded mt-2 inline-block">/activity/analytics/department</code>
                </div>
            </div>
        </div>
    );
}


// ============================================
// Dashboard Section
// ============================================
function DashboardSection() {
    return (
        <div className="prose prose-indigo max-w-none">
            <h2 className="text-xl font-bold text-gray-900 mb-4 flex items-center">
                <BarChart3 className="w-6 h-6 mr-2 text-blue-600" />
                Dashboard
            </h2>

            <p className="text-gray-600 mb-6">
                Dashboard memberikan gambaran umum tentang aktivitas dan statistik request Anda. Informasi ditampilkan secara real-time dan dapat difilter berdasarkan rentang waktu.
            </p>

            {/* Dashboard Components */}
            <h3 className="text-lg font-semibold text-gray-900 mb-3">Komponen Dashboard</h3>
            
            <div className="space-y-4 mb-8">
                {/* Statistics Cards */}
                <div className="bg-white border border-gray-200 rounded-lg p-4">
                    <div className="flex items-start">
                        <div className="flex-shrink-0 w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center mr-4">
                            <BarChart3 className="w-5 h-5 text-indigo-600" />
                        </div>
                        <div className="flex-1">
                            <h4 className="font-semibold text-gray-900 mb-2">Statistics Cards</h4>
                            <p className="text-gray-600 text-sm mb-2">
                                Kartu statistik menampilkan ringkasan jumlah request berdasarkan status:
                            </p>
                            <div className="grid grid-cols-2 md:grid-cols-4 gap-2">
                                <div className="bg-blue-50 rounded px-2 py-1 text-xs text-blue-700">Total PR</div>
                                <div className="bg-amber-50 rounded px-2 py-1 text-xs text-amber-700">Pending Approval</div>
                                <div className="bg-emerald-50 rounded px-2 py-1 text-xs text-emerald-700">Approved</div>
                                <div className="bg-red-50 rounded px-2 py-1 text-xs text-red-700">Rejected</div>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Charts */}
                <div className="bg-white border border-gray-200 rounded-lg p-4">
                    <div className="flex items-start">
                        <div className="flex-shrink-0 w-10 h-10 bg-emerald-100 rounded-lg flex items-center justify-center mr-4">
                            <BarChart3 className="w-5 h-5 text-emerald-600" />
                        </div>
                        <div className="flex-1">
                            <h4 className="font-semibold text-gray-900 mb-2">Charts & Grafik</h4>
                            <p className="text-gray-600 text-sm mb-2">
                                Visualisasi data dalam bentuk grafik untuk analisis yang lebih mudah:
                            </p>
                            <ul className="list-disc list-inside text-sm text-gray-600 space-y-1">
                                <li>Trend request per bulan</li>
                                <li>Distribusi status request</li>
                                <li>Request per departemen</li>
                                <li>Total nilai request</li>
                            </ul>
                        </div>
                    </div>
                </div>

                {/* Recent Activity */}
                <div className="bg-white border border-gray-200 rounded-lg p-4">
                    <div className="flex items-start">
                        <div className="flex-shrink-0 w-10 h-10 bg-amber-100 rounded-lg flex items-center justify-center mr-4">
                            <Clock className="w-5 h-5 text-amber-600" />
                        </div>
                        <div className="flex-1">
                            <h4 className="font-semibold text-gray-900 mb-2">Recent Activity</h4>
                            <p className="text-gray-600 text-sm mb-2">
                                Daftar aktivitas terbaru yang relevan dengan Anda:
                            </p>
                            <ul className="list-disc list-inside text-sm text-gray-600 space-y-1">
                                <li>Request yang baru dibuat</li>
                                <li>Request yang diapprove/reject</li>
                                <li>Request yang menunggu approval Anda</li>
                                <li>Notifikasi sistem</li>
                            </ul>
                        </div>
                    </div>
                </div>

                {/* Quick Actions */}
                <div className="bg-white border border-gray-200 rounded-lg p-4">
                    <div className="flex items-start">
                        <div className="flex-shrink-0 w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center mr-4">
                            <Zap className="w-5 h-5 text-purple-600" />
                        </div>
                        <div className="flex-1">
                            <h4 className="font-semibold text-gray-900 mb-2">Quick Actions</h4>
                            <p className="text-gray-600 text-sm mb-2">
                                Akses cepat ke fitur yang sering digunakan:
                            </p>
                            <div className="flex flex-wrap gap-2">
                                <span className="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-indigo-100 text-indigo-700">
                                    + Create PR
                                </span>
                                <span className="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-emerald-100 text-emerald-700">
                                    + Create ST
                                </span>
                                <span className="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-amber-100 text-amber-700">
                                    View Approvals
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {/* Date Range Filter */}
            <h3 className="text-lg font-semibold text-gray-900 mb-3">Filter Rentang Waktu</h3>
            <div className="bg-gray-50 rounded-lg p-4 mb-6 border border-gray-200">
                <p className="text-gray-600 text-sm mb-3">
                    Gunakan filter rentang waktu untuk melihat data pada periode tertentu:
                </p>
                <div className="flex flex-wrap gap-2">
                    {['Today', 'This Week', 'This Month', 'This Quarter', 'This Year'].map((filter) => (
                        <span key={filter} className="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-white border border-gray-200 text-gray-700">
                            {filter}
                        </span>
                    ))}
                    <span className="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-indigo-100 text-indigo-700">
                        Custom Range
                    </span>
                </div>
            </div>

            {/* Dashboard Types */}
            <h3 className="text-lg font-semibold text-gray-900 mb-3">Tipe Dashboard</h3>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div className="bg-blue-50 rounded-lg p-4 border border-blue-100">
                    <div className="flex items-center mb-2">
                        <Users className="w-5 h-5 text-blue-600 mr-2" />
                        <h4 className="font-semibold text-blue-900">User Dashboard</h4>
                    </div>
                    <p className="text-sm text-blue-700">Dashboard untuk pengguna reguler. Menampilkan request yang dibuat dan status approval.</p>
                    <code className="text-xs bg-blue-100 px-2 py-0.5 rounded mt-2 inline-block">/dashboard</code>
                </div>

                <div className="bg-purple-50 rounded-lg p-4 border border-purple-100">
                    <div className="flex items-center mb-2">
                        <FileText className="w-5 h-5 text-purple-600 mr-2" />
                        <h4 className="font-semibold text-purple-900">Admin Dashboard</h4>
                    </div>
                    <p className="text-sm text-purple-700">Dashboard untuk Purchasing Admin. Menampilkan tasks, statistik departemen, dan audit history.</p>
                    <code className="text-xs bg-purple-100 px-2 py-0.5 rounded mt-2 inline-block">/purchasing/admin/dashboard</code>
                </div>
            </div>
        </div>
    );
}


// ============================================
// FAQ Section
// ============================================
function FAQSection() {
    const [openFaq, setOpenFaq] = useState<number | null>(null);

    const faqs = [
        {
            question: 'Bagaimana cara membuat Purchase Request?',
            answer: (
                <>
                    <p className="text-gray-600 text-sm">
                        Untuk membuat Purchase Request, ikuti langkah berikut:
                    </p>
                    <ol className="list-decimal list-inside text-sm text-gray-600 mt-2 space-y-1">
                        <li>Buka menu <strong>Purchasing → Purchase Request</strong></li>
                        <li>Klik tombol <strong>"Create New PR"</strong></li>
                        <li>Isi informasi PR (kategori, tujuan, dll)</li>
                        <li>Tambahkan item yang akan dibeli</li>
                        <li>Pilih approver</li>
                        <li>Klik <strong>Submit</strong> atau <strong>Save Draft</strong></li>
                    </ol>
                </>
            ),
        },
        {
            question: 'Apakah saya bisa mengedit PR yang sudah disubmit?',
            answer: (
                <>
                    <p className="text-gray-600 text-sm">
                        <strong>Tidak</strong>, PR yang sudah disubmit tidak dapat diedit. Namun, jika PR ditolak (rejected), Anda dapat mengedit dan submit ulang (resubmit) PR tersebut dengan perbaikan yang diperlukan.
                    </p>
                    <p className="text-gray-600 text-sm mt-2">
                        Jika Anda perlu membatalkan PR yang sudah disubmit, hubungi admin untuk melakukan void pada PR tersebut.
                    </p>
                </>
            ),
        },
        {
            question: 'Apa perbedaan Purchase Request dan Stock Request?',
            answer: (
                <>
                    <p className="text-gray-600 text-sm">
                        <strong>Purchase Request (PR)</strong> digunakan untuk mengajukan pembelian barang atau jasa secara umum, seperti laptop, furniture, jasa konsultan, peralatan IT, dll. PR memerlukan informasi harga dan kategori.
                    </p>
                    <p className="text-gray-600 text-sm mt-2">
                        <strong>Stock Request (ST)</strong> digunakan khusus untuk pembelian barang-barang consumable/habis pakai seperti ATK (kertas, pulpen, tinta printer), perlengkapan kantor, dan kebutuhan operasional rutin. ST lebih sederhana dan tidak memerlukan kategori.
                    </p>
                </>
            ),
        },
        {
            question: 'Bagaimana cara beralih antar Business Unit?',
            answer: (
                <>
                    <p className="text-gray-600 text-sm">
                        Jika Anda memiliki akses ke beberapa Business Unit, Anda dapat beralih menggunakan <strong>Business Unit Switcher</strong> yang terletak di header aplikasi (sebelah kanan atas).
                    </p>
                    <p className="text-gray-600 text-sm mt-2">
                        Klik dropdown dan pilih Business Unit yang ingin Anda akses. Semua data akan otomatis difilter sesuai Business Unit yang dipilih.
                    </p>
                </>
            ),
        },
        {
            question: 'Siapa yang bisa menjadi approver?',
            answer: (
                <>
                    <p className="text-gray-600 text-sm">
                        Approver ditentukan berdasarkan hierarki organisasi. Biasanya termasuk:
                    </p>
                    <ul className="list-disc list-inside text-sm text-gray-600 mt-2 space-y-1">
                        <li>Atasan langsung (Department Head)</li>
                        <li>Manager/Director</li>
                        <li>Finance Manager (untuk PR dengan nilai tertentu)</li>
                        <li>General Manager/CEO (untuk PR dengan nilai besar)</li>
                    </ul>
                    <p className="text-gray-600 text-sm mt-2">
                        Daftar approver yang tersedia akan muncul saat Anda membuat PR/ST.
                    </p>
                </>
            ),
        },
        {
            question: 'Bagaimana jika approver tidak merespon?',
            answer: (
                <>
                    <p className="text-gray-600 text-sm">
                        Sistem akan mengirimkan reminder email kepada approver secara berkala. Jika approver tetap tidak merespon dalam waktu yang ditentukan (sesuai SLA), Anda dapat:
                    </p>
                    <ul className="list-disc list-inside text-sm text-gray-600 mt-2 space-y-1">
                        <li>Menghubungi approver secara langsung</li>
                        <li>Meminta bantuan admin untuk melakukan <strong>Offline Approval</strong></li>
                        <li>Menghubungi atasan approver untuk eskalasi</li>
                    </ul>
                </>
            ),
        },
        {
            question: 'Apa itu Offline Approval?',
            answer: (
                <>
                    <p className="text-gray-600 text-sm">
                        <strong>Offline Approval</strong> adalah fitur yang memungkinkan <strong>pembuat request</strong> untuk menandai bahwa PR/ST telah disetujui secara manual/offline (misalnya melalui tanda tangan di dokumen fisik).
                    </p>
                    <p className="text-gray-600 text-sm mt-2">
                        Prosesnya:
                    </p>
                    <ol className="list-decimal list-inside text-sm text-gray-600 mt-1 space-y-1">
                        <li>Cetak dokumen PR/ST dan minta tanda tangan approver</li>
                        <li>Buka halaman detail request yang statusnya "In Approval"</li>
                        <li>Klik tombol "Mark Offline Approved"</li>
                        <li><strong>Upload bukti approval (WAJIB)</strong> - format: JPG, PNG, atau PDF</li>
                        <li>Konfirmasi untuk mengubah status menjadi "Approved"</li>
                    </ol>
                    <p className="text-amber-600 text-sm mt-2">
                        <strong>Catatan:</strong> Upload bukti tanda tangan adalah wajib untuk dokumentasi dan audit trail.
                    </p>
                </>
            ),
        },
        {
            question: 'Bagaimana cara melihat history request saya?',
            answer: (
                <>
                    <p className="text-gray-600 text-sm">
                        Anda dapat melihat semua request yang pernah Anda buat di:
                    </p>
                    <ul className="list-disc list-inside text-sm text-gray-600 mt-2 space-y-1">
                        <li><strong>Purchasing → Purchase Request</strong> - untuk daftar PR Anda</li>
                        <li><strong>Purchasing → Stock Request</strong> - untuk daftar ST Anda</li>
                        <li><strong>Purchasing → All Requests</strong> - untuk melihat semua request (PR & ST)</li>
                    </ul>
                    <p className="text-gray-600 text-sm mt-2">
                        Gunakan filter untuk mencari request berdasarkan status, tanggal, atau nomor request.
                    </p>
                </>
            ),
        },
        {
            question: 'Bagaimana cara download PDF request?',
            answer: (
                <>
                    <p className="text-gray-600 text-sm">
                        Untuk mendownload PDF request:
                    </p>
                    <ol className="list-decimal list-inside text-sm text-gray-600 mt-2 space-y-1">
                        <li>Buka detail request (klik pada nomor request)</li>
                        <li>Klik tombol <strong>"Download PDF"</strong> di bagian atas halaman</li>
                        <li>PDF akan otomatis terdownload</li>
                    </ol>
                    <p className="text-gray-600 text-sm mt-2">
                        PDF berisi informasi lengkap request termasuk QR Code untuk verifikasi.
                    </p>
                </>
            ),
        },
        {
            question: 'Bagaimana cara menggunakan Activity Tracking?',
            answer: (
                <>
                    <p className="text-gray-600 text-sm">
                        Activity Tracking digunakan untuk mencatat aktivitas kerja harian Anda:
                    </p>
                    <ol className="list-decimal list-inside text-sm text-gray-600 mt-2 space-y-1">
                        <li>Buka menu <strong>Activity → Dashboard</strong></li>
                        <li>Klik tombol <strong>"New Task"</strong></li>
                        <li>Isi judul, pilih activity type, dan set status</li>
                        <li>Tambahkan participant jika task dikerjakan bersama</li>
                        <li>Klik <strong>Save</strong> untuk menyimpan</li>
                    </ol>
                    <p className="text-gray-600 text-sm mt-2">
                        Anda dapat melihat task dalam berbagai tampilan: List, Board, Calendar, atau Timeline.
                    </p>
                </>
            ),
        },
        {
            question: 'Bagaimana cara mengajukan izin backdate untuk Activity?',
            answer: (
                <>
                    <p className="text-gray-600 text-sm">
                        Secara default, Anda hanya bisa membuat task untuk hari ini atau kemarin. Untuk tanggal lebih lama:
                    </p>
                    <ol className="list-decimal list-inside text-sm text-gray-600 mt-2 space-y-1">
                        <li>Buka menu <strong>Activity → Backdate Requests</strong></li>
                        <li>Klik <strong>"Request Backdate Permission"</strong></li>
                        <li>Pilih tanggal yang diinginkan dan berikan alasan</li>
                        <li>Tunggu approval dari atasan/manager</li>
                        <li>Setelah disetujui, Anda dapat membuat task untuk tanggal tersebut</li>
                    </ol>
                    <p className="text-amber-600 text-sm mt-2">
                        <strong>Catatan:</strong> Izin backdate biasanya berlaku selama 24 jam setelah disetujui.
                    </p>
                </>
            ),
        },
        {
            question: 'Bagaimana cara bergabung dengan task departemen?',
            answer: (
                <>
                    <p className="text-gray-600 text-sm">
                        Anda dapat bergabung dengan task yang dibuat oleh rekan kerja di departemen yang sama:
                    </p>
                    <ol className="list-decimal list-inside text-sm text-gray-600 mt-2 space-y-1">
                        <li>Buka menu <strong>Activity → Department Tasks</strong></li>
                        <li>Lihat daftar task yang tersedia dari departemen Anda</li>
                        <li>Klik task yang ingin Anda join</li>
                        <li>Klik tombol <strong>"Join Task"</strong></li>
                    </ol>
                    <p className="text-gray-600 text-sm mt-2">
                        Setelah bergabung, task akan muncul di dashboard Anda dan Anda dapat update statusnya.
                    </p>
                </>
            ),
        },
        {
            question: 'Siapa yang harus saya hubungi jika ada masalah?',
            answer: (
                <>
                    <p className="text-gray-600 text-sm">
                        Jika Anda mengalami masalah teknis atau membutuhkan bantuan:
                    </p>
                    <ul className="list-disc list-inside text-sm text-gray-600 mt-2 space-y-1">
                        <li>Hubungi <strong>Purchasing Admin</strong> di departemen Anda</li>
                        <li>Hubungi <strong>IT Support</strong> untuk masalah teknis sistem</li>
                        <li>Hubungi <strong>Super Admin</strong> untuk masalah akses atau permission</li>
                    </ul>
                </>
            ),
        },
    ];

    return (
        <div className="prose prose-indigo max-w-none">
            <h2 className="text-xl font-bold text-gray-900 mb-4 flex items-center">
                <HelpCircle className="w-6 h-6 mr-2 text-indigo-600" />
                Frequently Asked Questions (FAQ)
            </h2>

            <p className="text-gray-600 mb-6">
                Pertanyaan yang sering diajukan tentang penggunaan sistem Oasis.
            </p>

            {/* FAQ Items */}
            <div className="space-y-4">
                {faqs.map((faq, index) => (
                    <div key={index} className="border border-gray-200 rounded-lg overflow-hidden">
                        <button
                            onClick={() => setOpenFaq(openFaq === index ? null : index)}
                            className="w-full flex items-center justify-between px-4 py-3 bg-gray-50 hover:bg-gray-100 transition-colors text-left"
                        >
                            <span className="font-medium text-gray-900">{faq.question}</span>
                            <ChevronDown
                                className={`w-5 h-5 text-gray-500 transition-transform ${
                                    openFaq === index ? 'rotate-180' : ''
                                }`}
                            />
                        </button>
                        <AnimatePresence>
                            {openFaq === index && (
                                <motion.div
                                    initial={{ height: 0, opacity: 0 }}
                                    animate={{ height: 'auto', opacity: 1 }}
                                    exit={{ height: 0, opacity: 0 }}
                                    transition={{ duration: 0.2 }}
                                    className="overflow-hidden"
                                >
                                    <div className="px-4 py-3 bg-white">{faq.answer}</div>
                                </motion.div>
                            )}
                        </AnimatePresence>
                    </div>
                ))}
            </div>

            {/* Contact Support */}
            <div className="mt-8 bg-indigo-50 rounded-lg p-6 border border-indigo-100">
                <div className="flex items-start">
                    <HelpCircle className="w-6 h-6 text-indigo-600 mr-3 mt-0.5" />
                    <div>
                        <h4 className="font-semibold text-indigo-900 mb-1">Masih ada pertanyaan?</h4>
                        <p className="text-sm text-indigo-700">
                            Jika pertanyaan Anda tidak terjawab di FAQ ini, silakan hubungi tim support atau admin sistem untuk bantuan lebih lanjut.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    );
}


// ============================================
// Helper Components
// ============================================

interface StatusBadgeProps {
    color: 'gray' | 'blue' | 'amber' | 'emerald' | 'red';
    label: string;
    description: string;
    darker?: boolean;
}

function StatusBadge({ color, label, description, darker }: StatusBadgeProps) {
    const colorClasses = {
        gray: darker ? 'bg-gray-200 text-gray-700' : 'bg-gray-100 text-gray-700',
        blue: 'bg-blue-100 text-blue-700',
        amber: 'bg-amber-100 text-amber-700',
        emerald: 'bg-emerald-100 text-emerald-700',
        red: 'bg-red-100 text-red-700',
    };

    const dotClasses = {
        gray: darker ? 'bg-gray-500' : 'bg-gray-400',
        blue: 'bg-blue-500',
        amber: 'bg-amber-500',
        emerald: 'bg-emerald-500',
        red: 'bg-red-500',
    };

    return (
        <div className={`flex items-center space-x-2 ${colorClasses[color]} rounded-lg px-3 py-2`}>
            <span className={`w-2 h-2 ${dotClasses[color]} rounded-full`}></span>
            <span className="text-sm">
                <strong>{label}</strong> - {description}
            </span>
        </div>
    );
}

interface StepCardProps {
    step: number;
    title: string;
    color: 'indigo' | 'emerald' | 'amber';
    children: React.ReactNode;
}

function StepCard({ step, title, color, children }: StepCardProps) {
    const colorClasses = {
        indigo: 'bg-indigo-600',
        emerald: 'bg-emerald-600',
        amber: 'bg-amber-600',
    };

    return (
        <div className="bg-white border border-gray-200 rounded-lg p-4">
            <div className="flex items-start">
                <span
                    className={`flex-shrink-0 w-8 h-8 flex items-center justify-center ${colorClasses[color]} text-white rounded-full text-sm font-bold mr-4`}
                >
                    {step}
                </span>
                <div className="flex-1">
                    <h4 className="font-semibold text-gray-900 mb-2">{title}</h4>
                    {children}
                </div>
            </div>
        </div>
    );
}

interface ApprovalFlowStepProps {
    icon: React.ElementType;
    label: string;
    color: 'blue' | 'amber' | 'emerald';
}

function ApprovalFlowStep({ icon: Icon, label, color }: ApprovalFlowStepProps) {
    const colorClasses = {
        blue: 'bg-blue-100 text-blue-600',
        amber: 'bg-amber-100 text-amber-600',
        emerald: 'bg-emerald-100 text-emerald-600',
    };

    return (
        <div className="flex items-center">
            <div className={`w-10 h-10 ${colorClasses[color]} rounded-full flex items-center justify-center`}>
                <Icon className="w-5 h-5" />
            </div>
            <span className="ml-2 text-sm font-medium text-gray-700">{label}</span>
        </div>
    );
}
