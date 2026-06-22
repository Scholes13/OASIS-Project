import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { motion } from 'framer-motion';
import {
    Search,
    BookOpen,
    Clock,
    Eye,
    ChevronRight,
    FileText,
    Zap,
    HelpCircle,
    Headphones,
} from 'lucide-react';
import { PageProps } from '@/types';
import type { KnowledgeCategory, KnowledgeArticle } from '@/types';

interface BrowsePageProps extends PageProps {
    categories: KnowledgeCategory[];
    popularArticles: KnowledgeArticle[];
    recentArticles: KnowledgeArticle[];
}

export default function Browse({ categories, popularArticles, recentArticles }: BrowsePageProps) {
    const [searchQuery, setSearchQuery] = useState('');

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        if (searchQuery.trim()) {
            router.get(route('it-support.knowledge.search'), { q: searchQuery.trim() });
        }
    };

    const formatDate = (dateString: string) => {
        const date = new Date(dateString);
        return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
    };

    const hasContent = categories.length > 0 || popularArticles.length > 0 || recentArticles.length > 0;

    return (
        <>
            <Head title="Knowledge Base" />

            <div className="max-w-5xl mx-auto px-6 py-12">
                {/* Hero */}
                <div className="text-center mb-12">
                    <div className="w-16 h-16 bg-blue-50 rounded-2xl flex items-center justify-center mx-auto mb-5">
                        <Headphones className="w-8 h-8 text-[#16599c]" />
                    </div>
                    <h1 className="text-3xl font-bold text-slate-900 mb-3">
                        Knowledge Base
                    </h1>
                    <p className="text-slate-500 mb-8 max-w-lg mx-auto">
                        Temukan jawaban untuk pertanyaan Anda, panduan troubleshooting, dan tips IT.
                    </p>

                    {/* Search Bar — matches DocsHelp style */}
                    <form onSubmit={handleSearch} className="max-w-2xl mx-auto relative">
                        <div className="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <Search className="h-5 w-5 text-slate-400" />
                        </div>
                        <input
                            type="text"
                            value={searchQuery}
                            onChange={(e) => setSearchQuery(e.target.value)}
                            placeholder="Cari artikel, panduan, atau topik..."
                            className="block w-full pl-11 pr-4 py-3 border border-slate-200 rounded-xl text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-[#16599c] focus:border-transparent shadow-sm transition-shadow"
                        />
                    </form>
                </div>

                {hasContent ? (
                    <>
                        {/* Categories Grid — matches DocsHelp CategoryCard style */}
                        {categories.length > 0 && (
                            <motion.div
                                initial={{ opacity: 0, y: 10 }}
                                animate={{ opacity: 1, y: 0 }}
                                transition={{ duration: 0.15 }}
                                className="mb-12"
                            >
                                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                    {categories.map((category) => (
                                        <Link
                                            key={category.id}
                                            href={route('it-support.knowledge.search', { category: category.slug })}
                                            className="bg-white border border-slate-200 rounded-xl p-6 cursor-pointer hover:shadow-md hover:border-[#16599c] transition-all group"
                                        >
                                            <div className="w-12 h-12 bg-blue-50 rounded-lg flex items-center justify-center mb-4 text-[#16599c] group-hover:scale-110 transition-transform">
                                                <BookOpen className="w-6 h-6" />
                                            </div>
                                            <h3 className="text-lg font-semibold text-slate-900 mb-1 group-hover:text-[#16599c] transition-colors">
                                                {category.name}
                                            </h3>
                                            {category.description && (
                                                <p className="text-sm text-slate-500 line-clamp-2 mb-3">
                                                    {category.description}
                                                </p>
                                            )}
                                            <span className="text-xs text-slate-400">
                                                {category.articles_count ?? 0} artikel
                                            </span>
                                        </Link>
                                    ))}
                                </div>
                            </motion.div>
                        )}

                        {/* Popular Articles — matches DocsHelp ArticleListItem style */}
                        {popularArticles.length > 0 && (
                            <motion.div
                                initial={{ opacity: 0, y: 10 }}
                                animate={{ opacity: 1, y: 0 }}
                                transition={{ duration: 0.15, delay: 0.05 }}
                                className="mb-12"
                            >
                                <h3 className="text-xl font-semibold text-slate-900 mb-4 flex items-center gap-2">
                                    <Zap className="w-5 h-5 text-amber-500" />
                                    Artikel Populer
                                </h3>
                                <div className="bg-white border border-slate-200 rounded-xl overflow-hidden">
                                    {popularArticles.map((article, idx) => (
                                        <Link
                                            key={article.id}
                                            href={route('it-support.knowledge.article', { slug: article.slug })}
                                            className={`flex items-center gap-4 px-5 py-4 hover:bg-slate-50 transition-colors group ${
                                                idx < popularArticles.length - 1 ? 'border-b border-slate-100' : ''
                                            }`}
                                        >
                                            <div className="flex-shrink-0 w-8 h-8 bg-amber-50 rounded-lg flex items-center justify-center text-amber-500">
                                                <FileText className="w-4 h-4" />
                                            </div>
                                            <div className="flex-1 min-w-0">
                                                <h4 className="text-sm font-medium text-slate-900 group-hover:text-[#16599c] transition-colors truncate">
                                                    {article.title}
                                                </h4>
                                                <div className="flex items-center gap-3 mt-1 text-xs text-slate-400">
                                                    <span className="flex items-center gap-1">
                                                        <Eye className="w-3 h-3" />
                                                        {article.views_count} dilihat
                                                    </span>
                                                    {article.category && (
                                                        <span>{article.category.name}</span>
                                                    )}
                                                </div>
                                            </div>
                                            <ChevronRight className="w-4 h-4 text-slate-300 group-hover:text-[#16599c] transition-colors flex-shrink-0" />
                                        </Link>
                                    ))}
                                </div>
                            </motion.div>
                        )}

                        {/* Recent Articles */}
                        {recentArticles.length > 0 && (
                            <motion.div
                                initial={{ opacity: 0, y: 10 }}
                                animate={{ opacity: 1, y: 0 }}
                                transition={{ duration: 0.15, delay: 0.1 }}
                                className="mb-12"
                            >
                                <h3 className="text-xl font-semibold text-slate-900 mb-4 flex items-center gap-2">
                                    <Clock className="w-5 h-5 text-blue-500" />
                                    Artikel Terbaru
                                </h3>
                                <div className="bg-white border border-slate-200 rounded-xl overflow-hidden">
                                    {recentArticles.map((article, idx) => (
                                        <Link
                                            key={article.id}
                                            href={route('it-support.knowledge.article', { slug: article.slug })}
                                            className={`flex items-center gap-4 px-5 py-4 hover:bg-slate-50 transition-colors group ${
                                                idx < recentArticles.length - 1 ? 'border-b border-slate-100' : ''
                                            }`}
                                        >
                                            <div className="flex-shrink-0 w-8 h-8 bg-blue-50 rounded-lg flex items-center justify-center text-blue-500">
                                                <FileText className="w-4 h-4" />
                                            </div>
                                            <div className="flex-1 min-w-0">
                                                <h4 className="text-sm font-medium text-slate-900 group-hover:text-[#16599c] transition-colors truncate">
                                                    {article.title}
                                                </h4>
                                                <div className="flex items-center gap-3 mt-1 text-xs text-slate-400">
                                                    <span className="flex items-center gap-1">
                                                        <Clock className="w-3 h-3" />
                                                        {formatDate(article.published_at || article.created_at)}
                                                    </span>
                                                    {article.category && (
                                                        <span>{article.category.name}</span>
                                                    )}
                                                </div>
                                            </div>
                                            <ChevronRight className="w-4 h-4 text-slate-300 group-hover:text-[#16599c] transition-colors flex-shrink-0" />
                                        </Link>
                                    ))}
                                </div>
                            </motion.div>
                        )}

                        {/* Support Banner — like DocsHelp */}
                        <motion.div
                            initial={{ opacity: 0, y: 10 }}
                            animate={{ opacity: 1, y: 0 }}
                            transition={{ duration: 0.15, delay: 0.15 }}
                            className="bg-gradient-to-r from-slate-50 to-blue-50 border border-slate-200 rounded-xl p-8 text-center"
                        >
                            <HelpCircle className="w-10 h-10 text-[#16599c] mx-auto mb-3" />
                            <h3 className="text-lg font-semibold text-slate-900 mb-2">
                                Tidak menemukan jawaban?
                            </h3>
                            <p className="text-sm text-slate-500 mb-4 max-w-md mx-auto">
                                Ajukan tiket IT Support dan tim kami akan membantu Anda.
                            </p>
                            <Link
                                href={route('it-support.submit')}
                                className="inline-flex items-center gap-2 px-5 py-2.5 bg-[#16599c] text-white text-sm font-medium rounded-lg hover:bg-[#124a83] transition-colors"
                            >
                                <Headphones className="w-4 h-4" />
                                Ajukan Tiket
                            </Link>
                        </motion.div>
                    </>
                ) : (
                    /* Empty State */
                    <motion.div
                        initial={{ opacity: 0, y: 10 }}
                        animate={{ opacity: 1, y: 0 }}
                        className="text-center py-20"
                    >
                        <div className="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-5">
                            <BookOpen className="w-10 h-10 text-slate-300" />
                        </div>
                        <h3 className="text-lg font-semibold text-slate-700 mb-2">
                            Knowledge Base Kosong
                        </h3>
                        <p className="text-sm text-slate-400 mb-8 max-w-sm mx-auto">
                            Belum ada artikel pengetahuan yang tersedia. Hubungi IT Support jika Anda membutuhkan bantuan.
                        </p>
                        <Link
                            href={route('it-support.submit')}
                            className="inline-flex items-center gap-2 px-5 py-2.5 bg-[#16599c] text-white text-sm font-medium rounded-lg hover:bg-[#124a83] transition-colors"
                        >
                            <Headphones className="w-4 h-4" />
                            Ajukan Tiket
                        </Link>
                    </motion.div>
                )}
            </div>
        </>
    );
}
