import { useState, useEffect } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { motion } from 'framer-motion';
import { 
    Search as SearchIcon, 
    FileText, 
    Calendar, 
    Eye,
    BookOpen,
    ChevronRight,
    X
} from 'lucide-react';
import AppLayout from '@/layouts/AppLayout';
import { Input } from '@/components/ui/input';
import { PageProps, PaginatedData } from '@/types';
import type { KnowledgeArticle } from '@/types';

interface SearchPageProps extends PageProps {
    articles: PaginatedData<KnowledgeArticle>;
    query: string;
}

export default function Search({ articles, query }: SearchPageProps) {
    // Safe data access with Inertia v2 pagination
    const safeData = articles?.data ?? [];
    const safeMeta = articles?.meta ?? {
        from: (articles as any)?.from ?? 0,
        to: (articles as any)?.to ?? 0,
        total: (articles as any)?.total ?? 0,
        last_page: (articles as any)?.last_page ?? 1,
        links: (articles as any)?.links ?? [],
    };
    const safeLinks = articles?.links ?? {
        prev: (articles as any)?.prev_page_url ?? null,
        next: (articles as any)?.next_page_url ?? null,
    };

    const [searchQuery, setSearchQuery] = useState(query || '');
    const [isLoading, setIsLoading] = useState(false);

    // Handle search submission
    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        if (searchQuery.trim()) {
            router.get(route('it-support.knowledge.search'), { q: searchQuery.trim() });
        }
    };

    // Handle page change
    const handlePageChange = (url: string) => {
        router.get(url, {}, {
            preserveState: true,
            preserveScroll: true,
            only: ['articles'],
            onStart: () => setIsLoading(true),
            onFinish: () => setIsLoading(false),
        });
    };

    const formatDate = (dateString: string | null) => {
        if (!dateString) return '-';
        const date = new Date(dateString);
        return date.toLocaleDateString('id-ID', {
            day: 'numeric',
            month: 'short',
            year: 'numeric',
        });
    };

    // Strip HTML tags for excerpt
    const stripHtml = (html: string) => {
        const tmp = document.createElement('div');
        tmp.innerHTML = html;
        return tmp.textContent || tmp.innerText || '';
    };

    // Truncate text for excerpt
    const truncate = (text: string, maxLength: number) => {
        if (text.length <= maxLength) return text;
        return text.substring(0, maxLength).trim() + '...';
    };

    return (
        <AppLayout title={`Hasil Pencarian: ${query}`}>
            <Head title={`Hasil Pencarian: ${query}`} />

            <div className="w-full px-4 sm:px-6 lg:px-8 py-6">
                {/* Header */}
                <motion.div
                    initial={{ opacity: 0, y: 20 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{ duration: 0.3 }}
                    className="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6"
                >
                    {/* Breadcrumb */}
                    <nav className="flex items-center gap-2 text-sm text-gray-500 mb-4">
                        <Link
                            href={route('it-support.knowledge')}
                            className="hover:text-gray-700 transition-colors"
                        >
                            Knowledge Base
                        </Link>
                        <ChevronRight className="w-4 h-4" />
                        <span className="text-gray-900 font-medium">Hasil Pencarian</span>
                    </nav>

                    {/* Search Form */}
                    <form onSubmit={handleSearch} className="max-w-2xl">
                        <div className="relative">
                            <SearchIcon className="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
                            <Input
                                type="text"
                                value={searchQuery}
                                onChange={(e) => setSearchQuery(e.target.value)}
                                placeholder="Cari artikel..."
                                className="w-full pl-12 pr-12 py-3 text-base"
                            />
                            {searchQuery && (
                                <button
                                    type="button"
                                    onClick={() => setSearchQuery('')}
                                    className="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                                >
                                    <X className="w-5 h-5" />
                                </button>
                            )}
                        </div>
                    </form>

                    {/* Search Result Info */}
                    <p className="mt-4 text-sm text-gray-500">
                        Menampilkan hasil pencarian untuk: <span className="font-medium text-gray-900">"{query}"</span>
                        <span className="ml-2">({safeMeta.total} artikel ditemukan)</span>
                    </p>
                </motion.div>

                {/* Results */}
                <div className={`transition-opacity duration-200 ${isLoading ? 'opacity-50' : ''}`}>
                    {safeData.length > 0 ? (
                        <motion.div
                            initial={{ opacity: 0, y: 20 }}
                            animate={{ opacity: 1, y: 0 }}
                            transition={{ duration: 0.3, delay: 0.1 }}
                            className="space-y-4"
                        >
                            {safeData.map((article) => (
                                <Link
                                    key={article.id}
                                    href={route('it-support.knowledge.article', { slug: article.slug })}
                                    className="block bg-white rounded-xl shadow-sm border border-gray-200 p-5 hover:border-primary/50 hover:shadow-md transition-all group"
                                >
                                    <div className="flex items-start gap-4">
                                        <div className="w-10 h-10 bg-primary/10 rounded-lg flex items-center justify-center flex-shrink-0">
                                            <FileText className="w-5 h-5 text-primary" />
                                        </div>
                                        <div className="flex-1 min-w-0">
                                            <h3 className="text-base font-semibold text-gray-900 group-hover:text-primary transition-colors">
                                                {article.title}
                                            </h3>
                                            
                                            {article.meta_description && (
                                                <p className="text-sm text-gray-500 mt-1 line-clamp-2">
                                                    {truncate(article.meta_description, 200)}
                                                </p>
                                            )}
                                            
                                            {!article.meta_description && article.content && (
                                                <p className="text-sm text-gray-500 mt-1 line-clamp-2">
                                                    {truncate(stripHtml(article.content), 200)}
                                                </p>
                                            )}

                                            <div className="flex flex-wrap items-center gap-4 mt-3 text-xs text-gray-400">
                                                {article.category && (
                                                    <span className="flex items-center gap-1">
                                                        <BookOpen className="w-3.5 h-3.5" />
                                                        {article.category.name}
                                                    </span>
                                                )}
                                                <span className="flex items-center gap-1">
                                                    <Calendar className="w-3.5 h-3.5" />
                                                    {formatDate(article.published_at)}
                                                </span>
                                                <span className="flex items-center gap-1">
                                                    <Eye className="w-3.5 h-3.5" />
                                                    {article.views_count} dilihat
                                                </span>
                                            </div>
                                        </div>
                                        <ChevronRight className="w-5 h-5 text-gray-300 flex-shrink-0 group-hover:text-primary transition-colors" />
                                    </div>
                                </Link>
                            ))}
                        </motion.div>
                    ) : (
                        <motion.div
                            initial={{ opacity: 0, y: 20 }}
                            animate={{ opacity: 1, y: 0 }}
                            className="text-center py-16 bg-white rounded-xl shadow-sm border border-gray-200"
                        >
                            <div className="w-16 h-16 mx-auto bg-gray-50 rounded-full flex items-center justify-center mb-4">
                                <SearchIcon className="w-8 h-8 text-gray-300" />
                            </div>
                            <h3 className="text-base font-medium text-gray-600 mb-2">
                                Tidak Ada Hasil
                            </h3>
                            <p className="text-sm text-gray-400 mb-6">
                                Tidak ditemukan artikel untuk "{query}"
                            </p>
                            <Link
                                href={route('it-support.knowledge')}
                                className="inline-flex items-center gap-2 px-4 py-2 bg-primary text-white text-sm font-medium rounded-lg hover:bg-blue-600 transition-colors"
                            >
                                <BookOpen className="w-4 h-4" />
                                Jelajahi Knowledge Base
                            </Link>
                        </motion.div>
                    )}
                </div>

                {/* Pagination */}
                {safeMeta.last_page > 1 && (
                    <div className="mt-6 bg-white rounded-xl shadow-sm border border-gray-200 px-6 py-4">
                        <div className="flex items-center justify-between">
                            <p className="text-sm text-gray-400">
                                Menampilkan {safeMeta.from || 0} - {safeMeta.to || 0} dari {safeMeta.total} hasil
                            </p>

                            <nav className="flex items-center gap-1">
                                {safeLinks.prev ? (
                                    <button
                                        onClick={() => handlePageChange(safeLinks.prev!)}
                                        className="px-3 py-2 text-sm text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors"
                                    >
                                        Previous
                                    </button>
                                ) : (
                                    <span className="px-3 py-2 text-sm text-gray-300 cursor-not-allowed">
                                        Previous
                                    </span>
                                )}

                                <div className="flex items-center gap-1 mx-2">
                                    {safeMeta.links
                                        .filter(link => !link.label.includes('Previous') && !link.label.includes('Next'))
                                        .map((link, index) => (
                                            <button
                                                key={index}
                                                onClick={() => link.url && handlePageChange(link.url)}
                                                disabled={!link.url}
                                                className={`w-8 h-8 flex items-center justify-center text-sm rounded-md transition-colors ${
                                                    link.active
                                                        ? 'font-medium text-white bg-primary'
                                                        : 'text-gray-500 hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed'
                                                }`}
                                            >
                                                {link.label}
                                            </button>
                                        ))}
                                </div>

                                {safeLinks.next ? (
                                    <button
                                        onClick={() => handlePageChange(safeLinks.next!)}
                                        className="px-3 py-2 text-sm text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors"
                                    >
                                        Next
                                    </button>
                                ) : (
                                    <span className="px-3 py-2 text-sm text-gray-300 cursor-not-allowed">
                                        Next
                                    </span>
                                )}
                            </nav>
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}