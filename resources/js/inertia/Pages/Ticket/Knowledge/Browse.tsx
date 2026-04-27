import { useState, useEffect } from 'react';
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
    Star
} from 'lucide-react';
import AppLayout from '@/layouts/AppLayout';
import { Input } from '@/components/ui/input';
import { PageProps } from '@/types';
import type { KnowledgeCategory, KnowledgeArticle } from '@/types';

interface BrowsePageProps extends PageProps {
    categories: KnowledgeCategory[];
    popularArticles: KnowledgeArticle[];
    recentArticles: KnowledgeArticle[];
}

export default function Browse({ categories, popularArticles, recentArticles }: BrowsePageProps) {
    const [searchQuery, setSearchQuery] = useState('');
    const [isLoading, setIsLoading] = useState(false);

    // Handle search submission
    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        if (searchQuery.trim()) {
            router.get(route('it-support.knowledge.search'), { q: searchQuery.trim() });
        }
    };

    // Handle search on Enter key
    const handleSearchInput = (e: React.KeyboardEvent<HTMLInputElement>) => {
        if (e.key === 'Enter' && searchQuery.trim()) {
            router.get(route('it-support.knowledge.search'), { q: searchQuery.trim() });
        }
    };

    const formatDate = (dateString: string) => {
        const date = new Date(dateString);
        return date.toLocaleDateString('id-ID', {
            day: 'numeric',
            month: 'short',
            year: 'numeric',
        });
    };

    return (
        <AppLayout title="Knowledge Base">
            <Head title="Knowledge Base" />

            <div className="w-full px-4 sm:px-6 lg:px-8 py-6">
                {/* Header with Search */}
                <motion.div
                    initial={{ opacity: 0, y: 20 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{ duration: 0.3 }}
                    className="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6"
                >
                    <div className="text-center mb-6">
                        <h1 className="text-2xl font-bold text-gray-900 mb-2">
                            Knowledge Base
                        </h1>
                        <p className="text-sm text-gray-500">
                            Temukan jawaban untuk pertanyaan Anda di artikel berikut
                        </p>
                    </div>

                    {/* Search Bar */}
                    <form onSubmit={handleSearch} className="max-w-2xl mx-auto">
                        <div className="relative">
                            <Search className="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
                            <Input
                                type="text"
                                value={searchQuery}
                                onChange={(e) => setSearchQuery(e.target.value)}
                                onKeyDown={handleSearchInput}
                                placeholder="Cari artikel atau topik..."
                                className="w-full pl-12 pr-4 py-3 text-base"
                            />
                            <button
                                type="submit"
                                className="absolute right-2 top-1/2 -translate-y-1/2 px-4 py-1.5 bg-primary text-white text-sm font-medium rounded-lg hover:bg-blue-600 transition-colors"
                            >
                                Cari
                            </button>
                        </div>
                    </form>
                </motion.div>

                {/* Categories Grid */}
                {categories.length > 0 && (
                    <motion.div
                        initial={{ opacity: 0, y: 20 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{ duration: 0.3, delay: 0.1 }}
                        className="mb-8"
                    >
                        <h2 className="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                            <BookOpen className="w-5 h-5 text-primary" />
                            Kategori
                        </h2>
                        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                            {categories.map((category) => (
                                <Link
                                    key={category.id}
                                    href={route('it-support.knowledge.search')}
                                    className="bg-white rounded-xl shadow-sm border border-gray-200 p-5 hover:border-primary/50 hover:shadow-md transition-all group"
                                >
                                    <div className="flex items-start justify-between">
                                        <div className="flex-1">
                                            <h3 className="font-medium text-gray-900 group-hover:text-primary transition-colors">
                                                {category.name}
                                            </h3>
                                            {category.description && (
                                                <p className="text-sm text-gray-500 mt-1 line-clamp-2">
                                                    {category.description}
                                                </p>
                                            )}
                                        </div>
                                        {category.articles_count !== undefined && (
                                            <span className="ml-3 px-2 py-1 bg-gray-100 text-gray-600 text-xs font-medium rounded-full">
                                                {category.articles_count}
                                            </span>
                                        )}
                                    </div>
                                    <div className="mt-3 flex items-center text-sm text-primary opacity-0 group-hover:opacity-100 transition-opacity">
                                        <span>Lihat semua</span>
                                        <ChevronRight className="w-4 h-4 ml-1" />
                                    </div>
                                </Link>
                            ))}
                        </div>
                    </motion.div>
                )}

                {/* Popular & Recent Articles */}
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {/* Popular Articles */}
                    <motion.div
                        initial={{ opacity: 0, y: 20 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{ duration: 0.3, delay: 0.2 }}
                    >
                        <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                            <h2 className="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                                <Zap className="w-5 h-5 text-amber-500" />
                                Artikel Populer
                            </h2>
                            {popularArticles.length > 0 ? (
                                <div className="space-y-4">
                                    {popularArticles.map((article) => (
                                        <Link
                                            key={article.id}
                                            href={route('it-support.knowledge.article', { slug: article.slug })}
                                            className="block p-3 rounded-lg hover:bg-gray-50 transition-colors"
                                        >
                                            <h3 className="text-sm font-medium text-gray-900 line-clamp-2">
                                                {article.title}
                                            </h3>
                                            <div className="flex items-center gap-3 mt-2 text-xs text-gray-400">
                                                <span className="flex items-center gap-1">
                                                    <Eye className="w-3.5 h-3.5" />
                                                    {article.views_count} dilihat
                                                </span>
                                                {article.category && (
                                                    <span className="flex items-center gap-1">
                                                        <BookOpen className="w-3.5 h-3.5" />
                                                        {article.category.name}
                                                    </span>
                                                )}
                                            </div>
                                        </Link>
                                    ))}
                                </div>
                            ) : (
                                <p className="text-sm text-gray-400 text-center py-4">
                                    Belum ada artikel populer
                                </p>
                            )}
                        </div>
                    </motion.div>

                    {/* Recent Articles */}
                    <motion.div
                        initial={{ opacity: 0, y: 20 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{ duration: 0.3, delay: 0.3 }}
                    >
                        <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                            <h2 className="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                                <Clock className="w-5 h-5 text-blue-500" />
                                Artikel Terbaru
                            </h2>
                            {recentArticles.length > 0 ? (
                                <div className="space-y-4">
                                    {recentArticles.map((article) => (
                                        <Link
                                            key={article.id}
                                            href={route('it-support.knowledge.article', { slug: article.slug })}
                                            className="block p-3 rounded-lg hover:bg-gray-50 transition-colors"
                                        >
                                            <h3 className="text-sm font-medium text-gray-900 line-clamp-2">
                                                {article.title}
                                            </h3>
                                            <div className="flex items-center gap-3 mt-2 text-xs text-gray-400">
                                                <span className="flex items-center gap-1">
                                                    <Clock className="w-3.5 h-3.5" />
                                                    {formatDate(article.published_at || article.created_at)}
                                                </span>
                                                {article.category && (
                                                    <span className="flex items-center gap-1">
                                                        <BookOpen className="w-3.5 h-3.5" />
                                                        {article.category.name}
                                                    </span>
                                                )}
                                            </div>
                                        </Link>
                                    ))}
                                </div>
                            ) : (
                                <p className="text-sm text-gray-400 text-center py-4">
                                    Belum ada artikel terbaru
                                </p>
                            )}
                        </div>
                    </motion.div>
                </div>

                {/* Empty State */}
                {categories.length === 0 && popularArticles.length === 0 && recentArticles.length === 0 && (
                    <motion.div
                        initial={{ opacity: 0, y: 20 }}
                        animate={{ opacity: 1, y: 0 }}
                        className="text-center py-16 bg-white rounded-xl shadow-sm border border-gray-200"
                    >
                        <div className="w-16 h-16 mx-auto bg-gray-50 rounded-full flex items-center justify-center mb-4">
                            <BookOpen className="w-8 h-8 text-gray-300" />
                        </div>
                        <h3 className="text-base font-medium text-gray-600 mb-2">
                            Knowledge Base Kosong
                        </h3>
                        <p className="text-sm text-gray-400 mb-6">
                            Belum ada artikel pengetahuan yang tersedia
                        </p>
                    </motion.div>
                )}
            </div>
        </AppLayout>
    );
}