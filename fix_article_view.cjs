const fs = require('fs');
const path = require('path');

const filePath = path.join(__dirname, 'resources', 'js', 'inertia', 'Pages', 'DocsHelp', 'Index.tsx');
let content = fs.readFileSync(filePath, 'utf8');

const newComponent = `export default function DocsHelpIndex() {
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
                <div className="max-w-5xl mx-auto flex flex-col lg:flex-row gap-12 mt-6">
                    <div className="flex-1 min-w-0">
                        <div className="mb-8">
                            <span className="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-50 text-blue-700 mb-4">
                                Activity Tracking
                            </span>
                            <h1 className="text-3xl font-bold text-slate-900 mb-4">How to create a backdated task</h1>
                            <div className="flex items-center text-sm text-slate-500 gap-6 border-b border-slate-200 pb-6">
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

                        <div className="prose prose-slate max-w-none">
                            <p className="text-slate-600 text-base leading-relaxed mb-6" id="overview">
                                Sometimes you might need to log work that was completed in the past. OASIS allows you to create tasks with a past date, subject to your department's configuration and approval policies.
                            </p>

                            <div className="bg-blue-50 border-l-4 border-blue-500 rounded-r-lg p-5 mb-8">
                                <div className="flex items-start">
                                    <Info className="w-5 h-5 text-blue-600 mr-3 mt-0.5" />
                                    <div>
                                        <h4 className="font-semibold text-blue-900 mb-1">Important Note</h4>
                                        <p className="text-sm text-blue-800">
                                            By default, you can only backdate tasks up to 3 working days. If you need to log activity older than that, you will be required to submit a Request Backdate Approval form.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <h2 className="text-xl font-bold text-slate-900 mb-4 mt-8" id="prerequisites">Prerequisites</h2>
                            <ul className="list-disc list-inside text-slate-600 space-y-2 mb-8 ml-2">
                                <li>You must have an active employee account.</li>
                                <li>You must be assigned to the relevant department for the task category.</li>
                            </ul>

                            <h2 className="text-xl font-bold text-slate-900 mb-4 mt-8" id="step-by-step-guide">Step-by-step Guide</h2>
                            <p className="text-slate-600 mb-4">Follow these steps to log a task that has already been completed:</p>
                            <ol className="list-decimal list-outside text-slate-600 space-y-3 mb-8 ml-5">
                                <li className="pl-2">Navigate to My Tasks or the Activity Dashboard.</li>
                                <li className="pl-2">Click on the Create Task button in the top right corner.</li>
                                <li className="pl-2">In the Task Form, fill out the Basic Info (Title, Description).</li>
                                <li className="pl-2">Locate the Task Date field. Click the calendar icon and select the past date.</li>
                                <li className="pl-2">Save the task. If within 3 days, it will be automatically approved.</li>
                            </ol>

                            <h2 className="text-xl font-bold text-slate-900 mb-4 mt-8" id="troubleshooting">Troubleshooting</h2>
                            <p className="text-slate-600 mb-4">If you cannot select a past date, check with your department admin if your permissions have been restricted or if you have pending unresolved backdated requests.</p>
                        </div>
                    </div>

                    {/* Table of Contents - Right Sidebar */}
                    <div className="hidden lg:block w-64 flex-shrink-0">
                        <div className="sticky top-6">
                            <h4 className="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4">ON THIS PAGE</h4>
                            <nav className="flex flex-col space-y-3">
                                <a href="#overview" className="text-sm text-[#16599c] font-medium border-l-2 border-[#16599c] pl-3">Overview</a>
                                <a href="#prerequisites" className="text-sm text-slate-500 hover:text-slate-900 border-l-2 border-transparent pl-3 transition-colors">Prerequisites</a>
                                <a href="#step-by-step-guide" className="text-sm text-slate-500 hover:text-slate-900 border-l-2 border-transparent pl-3 transition-colors">Step-by-step Guide</a>
                                <a href="#troubleshooting" className="text-sm text-slate-500 hover:text-slate-900 border-l-2 border-transparent pl-3 transition-colors">Troubleshooting</a>
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
                    <div className="max-w-7xl mx-auto bg-white min-h-[calc(100vh-64px)] shadow-sm">
                        {/* Breadcrumbs */}
                        <div className="flex items-center text-sm text-slate-500 py-4 px-8 border-b border-slate-200 bg-white">
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
                        
                        <div className="px-8 pb-12">
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
                                                    className={\`w-full text-left px-3 py-2.5 rounded-lg text-sm transition-colors flex items-center gap-3 \${
                                                        activeSection === section.key
                                                            ? 'bg-blue-50 text-[#16599c] font-medium'
                                                            : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'
                                                    }\`}
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
}`;

content = content.replace(/export default function DocsHelpIndex\(\) \{[\s\S]*?(?=\/\/\s*============================================)/, newComponent + '\n\n');

if (!content.includes('User,')) {
    content = content.replace(/import \{ Head \} from '@inertiajs\/react';/, 'import { Head } from \'@inertiajs/react\';\nimport { User } from \'lucide-react\';');
}

fs.writeFileSync(filePath, content);
console.log('Fixed DocsHelp/Index.tsx for article view');
