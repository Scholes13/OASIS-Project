import { Head, Link } from '@inertiajs/react';
import { motion } from 'framer-motion';
import { 
    ArrowLeft, 
    Calendar, 
    User, 
    Eye, 
    Tag,
    BookOpen,
    ChevronRight,
    Clock
} from 'lucide-react';
import AppLayout from '@/layouts/AppLayout';
import { PageProps } from '@/types';
import type { KnowledgeArticle } from '@/types';

interface ArticlePageProps extends PageProps {
    article: KnowledgeArticle;
    relatedArticles: KnowledgeArticle[];
}

export default function Article({ article, relatedArticles }: ArticlePageProps) {
    const formatDate = (dateString: string | null) => {
        if (!dateString) return '-';
        const date = new Date(dateString);
        return date.toLocaleDateString('id-ID', {
            day: 'numeric',
            month: 'long',
            year: 'numeric',
        });
    };

    return (
        <AppLayout title={article.title}>
            <Head title={article.title} />

            <div className="w-full px-4 sm:px-6 lg:px-8 py-6">
                {/* Breadcrumb */}
                <div className="mb-6">
                    <nav className="flex items-center gap-2 text-sm text-gray-500">
                        <Link
                            href={route('it-support.knowledge')}
                            className="hover:text-gray-700 transition-colors"
                        >
                            Knowledge Base
                        </Link>
                        {article.category && (
                            <>
                                <ChevronRight className="w-4 h-4" />
                                <Link
                                    href={route('it-support.knowledge.search')}
                                    className="hover:text-gray-700 transition-colors"
                                >
                                    {article.category.name}
                                </Link>
                            </>
                        )}
                        <ChevronRight className="w-4 h-4" />
                        <span className="text-gray-900 font-medium truncate max-w-[200px]">
                            {article.title}
                        </span>
                    </nav>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-4 gap-6">
                    {/* Main Content */}
                    <div className="lg:col-span-3">
                        <motion.article
                            initial={{ opacity: 0, y: 20 }}
                            animate={{ opacity: 1, y: 0 }}
                            transition={{ duration: 0.3 }}
                            className="bg-white rounded-xl shadow-sm border border-gray-200 p-6 md:p-8"
                        >
                            {/* Article Header */}
                            <header className="mb-8 pb-8 border-b border-gray-100">
                                {/* Back Link */}
                                <Link
                                    href={route('it-support.knowledge')}
                                    className="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-gray-700 transition-colors mb-4"
                                >
                                    <ArrowLeft className="w-4 h-4" />
                                    Kembali ke Knowledge Base
                                </Link>

                                {/* Title */}
                                <h1 className="text-2xl md:text-3xl font-bold text-gray-900 mb-4">
                                    {article.title}
                                </h1>

                                {/* Meta Info */}
                                <div className="flex flex-wrap items-center gap-4 text-sm text-gray-500">
                                    {article.author && (
                                        <div className="flex items-center gap-2">
                                            <div className="w-8 h-8 bg-primary/10 rounded-full flex items-center justify-center">
                                                <User className="w-4 h-4 text-primary" />
                                            </div>
                                            <span>{article.author.name}</span>
                                        </div>
                                    )}
                                    <div className="flex items-center gap-1">
                                        <Calendar className="w-4 h-4" />
                                        <span>{formatDate(article.published_at)}</span>
                                    </div>
                                    <div className="flex items-center gap-1">
                                        <Eye className="w-4 h-4" />
                                        <span>{article.views_count} dilihat</span>
                                    </div>
                                </div>
                            </header>

                            {/* Article Content */}
                            <div 
                                className="prose prose-sm md:prose-base max-w-none prose-headings:font-semibold prose-a:text-primary prose-a:no-underline hover:prose-a:underline"
                                dangerouslySetInnerHTML={{ __html: article.content }}
                            />

                            {/* Tags */}
                            {article.tags && article.tags.length > 0 && (
                                <div className="mt-8 pt-8 border-t border-gray-100">
                                    <div className="flex items-center gap-2 flex-wrap">
                                        <Tag className="w-4 h-4 text-gray-400" />
                                        {article.tags.map((tag, index) => (
                                            <span
                                                key={index}
                                                className="px-2 py-1 bg-gray-100 text-gray-600 text-xs rounded-md"
                                            >
                                                {tag}
                                            </span>
                                        ))}
                                    </div>
                                </div>
                            )}
                        </motion.article>
                    </div>

                    {/* Sidebar - Related Articles */}
                    <div className="lg:col-span-1">
                        {relatedArticles.length > 0 && (
                            <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6 sticky top-6">
                                <h3 className="text-sm font-semibold text-gray-900 mb-4 flex items-center gap-2">
                                    <BookOpen className="w-4 h-4 text-primary" />
                                    Artikel Terkait
                                </h3>
                                <div className="space-y-3">
                                    {relatedArticles.map((related) => (
                                        <Link
                                            key={related.id}
                                            href={route('it-support.knowledge.article', { slug: related.slug })}
                                            className="block p-3 rounded-lg hover:bg-gray-50 transition-colors"
                                        >
                                            <h4 className="text-sm font-medium text-gray-900 line-clamp-2">
                                                {related.title}
                                            </h4>
                                            <div className="flex items-center gap-2 mt-1 text-xs text-gray-400">
                                                <Eye className="w-3 h-3" />
                                                <span>{related.views_count}</span>
                                            </div>
                                        </Link>
                                    ))}
                                </div>
                            </div>
                        )}

                        {/* Quick Links */}
                        <div className="mt-4 bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                            <h3 className="text-sm font-semibold text-gray-900 mb-4">
                                Aksi Cepat
                            </h3>
                            <div className="space-y-2">
                                <Link
                                    href={route('it-support.knowledge')}
                                    className="flex items-center gap-2 p-2 text-sm text-gray-600 hover:bg-gray-50 rounded-lg transition-colors"
                                >
                                    <BookOpen className="w-4 h-4" />
                                    Semua Artikel
                                </Link>
                                <Link
                                    href={route('it-support.submit')}
                                    className="flex items-center gap-2 p-2 text-sm text-gray-600 hover:bg-gray-50 rounded-lg transition-colors"
                                >
                                    <Clock className="w-4 h-4" />
                                    Ajukan Tiket
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}