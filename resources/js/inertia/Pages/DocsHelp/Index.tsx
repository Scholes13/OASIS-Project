import { Head } from '@inertiajs/react';
import { useState, useMemo } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { ChevronRight, Clock, User, FileText } from 'lucide-react';

import { categories, articles } from './data';
import type { CategoryKey, Article } from './data';
import {
    searchArticles,
    getArticlesByCategory,
    getPopularArticles,
    getCategoryCounts,
    formatArticleDate,
} from './data/search';
import {
    ArticleRenderer,
    TableOfContents,
    Breadcrumbs,
    SearchBar,
    SupportBanner,
    CategoryCard,
    ArticleListItem,
} from './components';

type View =
    | { type: 'home' }
    | { type: 'category'; key: CategoryKey }
    | { type: 'article'; article: Article };

export default function DocsHelpIndex() {
    const [view, setView] = useState<View>({ type: 'home' });
    const [searchQuery, setSearchQuery] = useState('');

    const categoryCounts = useMemo(() => getCategoryCounts(articles, categories), []);
    const popularArticles = useMemo(() => getPopularArticles(articles), []);

    const searchResults = useMemo(
        () => (searchQuery.trim().length >= 2 ? searchArticles(articles, searchQuery) : []),
        [searchQuery],
    );

    const isSearching = searchQuery.trim().length >= 2;

    const goHome = () => {
        setView({ type: 'home' });
        setSearchQuery('');
    };

    const goCategory = (key: CategoryKey) => {
        setView({ type: 'category', key });
        setSearchQuery('');
    };

    const goArticle = (article: Article) => {
        setView({ type: 'article', article });
        setSearchQuery('');
    };

    const getCategoryLabel = (key: string): string =>
        categories.find((c) => c.key === key)?.label ?? key;

    return (
        <>
            <Head title="Docs & Help" />
            <div className="w-full">
                <AnimatePresence mode="wait">
                    {view.type === 'home' && (
                        <motion.div
                            key="home"
                            initial={{ opacity: 0, y: 10 }}
                            animate={{ opacity: 1, y: 0 }}
                            exit={{ opacity: 0, y: -10 }}
                            transition={{ duration: 0.15 }}
                        >
                            <HomeView
                                searchQuery={searchQuery}
                                onSearchChange={setSearchQuery}
                                isSearching={isSearching}
                                searchResults={searchResults}
                                categoryCounts={categoryCounts}
                                popularArticles={popularArticles}
                                onCategoryClick={goCategory}
                                onArticleClick={goArticle}
                                getCategoryLabel={getCategoryLabel}
                            />
                        </motion.div>
                    )}

                    {view.type === 'category' && (
                        <motion.div
                            key={`cat-${view.key}`}
                            initial={{ opacity: 0, y: 10 }}
                            animate={{ opacity: 1, y: 0 }}
                            exit={{ opacity: 0, y: -10 }}
                            transition={{ duration: 0.15 }}
                        >
                            <CategoryView
                                categoryKey={view.key}
                                categoryLabel={getCategoryLabel(view.key)}
                                onBack={goHome}
                                onArticleClick={goArticle}
                            />
                        </motion.div>
                    )}

                    {view.type === 'article' && (
                        <motion.div
                            key={`art-${view.article.id}`}
                            initial={{ opacity: 0, y: 10 }}
                            animate={{ opacity: 1, y: 0 }}
                            exit={{ opacity: 0, y: -10 }}
                            transition={{ duration: 0.15 }}
                        >
                            <ArticleView
                                article={view.article}
                                onBack={goHome}
                                onCategoryClick={goCategory}
                                getCategoryLabel={getCategoryLabel}
                            />
                        </motion.div>
                    )}
                </AnimatePresence>
            </div>
        </>
    );
}

// ══════════════════════════════════════════════════
// HOME VIEW
// ══════════════════════════════════════════════════

interface HomeViewProps {
    searchQuery: string;
    onSearchChange: (q: string) => void;
    isSearching: boolean;
    searchResults: { article: Article; matchType: string }[];
    categoryCounts: Record<string, number>;
    popularArticles: Article[];
    onCategoryClick: (key: CategoryKey) => void;
    onArticleClick: (article: Article) => void;
    getCategoryLabel: (key: string) => string;
}

function HomeView({
    searchQuery,
    onSearchChange,
    isSearching,
    searchResults,
    categoryCounts,
    popularArticles,
    onCategoryClick,
    onArticleClick,
    getCategoryLabel,
}: HomeViewProps) {
    return (
        <div className="max-w-5xl mx-auto px-6 py-12">
            {/* Hero */}
            <div className="text-center mb-12">
                <h1 className="text-3xl font-bold text-slate-900 mb-3">How can we help you?</h1>
                <p className="text-slate-500 mb-8">Search for guides, API docs, and troubleshooting tips.</p>
                <SearchBar value={searchQuery} onChange={onSearchChange} />
            </div>

            {isSearching ? (
                <div className="mb-12">
                    <h3 className="text-lg font-semibold text-slate-900 mb-4">
                        {searchResults.length > 0
                            ? `${searchResults.length} result${searchResults.length > 1 ? 's' : ''} for "${searchQuery}"`
                            : `No results for "${searchQuery}"`}
                    </h3>
                    {searchResults.length > 0 ? (
                        <div className="bg-white border border-slate-200 rounded-xl overflow-hidden">
                            {searchResults.map(({ article }) => (
                                <ArticleListItem
                                    key={article.id}
                                    article={article}
                                    onClick={() => onArticleClick(article)}
                                    showCategory
                                    categoryLabel={getCategoryLabel(article.category)}
                                />
                            ))}
                        </div>
                    ) : (
                        <div className="text-center py-12">
                            <FileText className="w-12 h-12 text-slate-300 mx-auto mb-3" />
                            <p className="text-slate-500">Try a different search term or browse categories below.</p>
                        </div>
                    )}
                </div>
            ) : (
                <>
                    {/* Categories Grid */}
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-12">
                        {categories.map((cat) => (
                            <CategoryCard
                                key={cat.key}
                                label={cat.label}
                                description={cat.description}
                                iconName={cat.icon}
                                articleCount={categoryCounts[cat.key] ?? 0}
                                onClick={() => onCategoryClick(cat.key)}
                            />
                        ))}
                    </div>

                    {/* Popular Articles */}
                    {popularArticles.length > 0 && (
                        <div className="mb-12">
                            <h3 className="text-xl font-semibold text-slate-900 mb-4">Popular Articles</h3>
                            <div className="bg-white border border-slate-200 rounded-xl overflow-hidden">
                                {popularArticles.map((article) => (
                                    <ArticleListItem
                                        key={article.id}
                                        article={article}
                                        onClick={() => onArticleClick(article)}
                                    />
                                ))}
                            </div>
                        </div>
                    )}
                </>
            )}

            <SupportBanner />
        </div>
    );
}

// ══════════════════════════════════════════════════
// CATEGORY VIEW
// ══════════════════════════════════════════════════

interface CategoryViewProps {
    categoryKey: CategoryKey;
    categoryLabel: string;
    onBack: () => void;
    onArticleClick: (article: Article) => void;
}

function CategoryView({ categoryKey, categoryLabel, onBack, onArticleClick }: CategoryViewProps) {
    const categoryArticles = useMemo(
        () => getArticlesByCategory(articles, categoryKey),
        [categoryKey],
    );

    return (
        <div className="max-w-5xl mx-auto px-6 py-8">
            <button
                onClick={onBack}
                className="flex items-center text-sm text-slate-500 hover:text-[#16599c] mb-8 transition-colors font-medium"
            >
                <ChevronRight className="w-4 h-4 mr-1 rotate-180" />
                Back to Help Center
            </button>

            <div className="mb-8">
                <h1 className="text-2xl font-bold text-slate-900 mb-2">{categoryLabel}</h1>
                <p className="text-slate-500">
                    {categoryArticles.length} {categoryArticles.length === 1 ? 'article' : 'articles'} in this category
                </p>
            </div>

            <div className="bg-white border border-slate-200 rounded-xl overflow-hidden">
                {categoryArticles.map((article) => (
                    <div
                        key={article.id}
                        onClick={() => onArticleClick(article)}
                        className="flex items-start gap-4 p-5 border-b border-slate-100 last:border-0 hover:bg-slate-50 cursor-pointer transition-colors"
                    >
                        <FileText className="w-5 h-5 text-[#16599c] mt-0.5 flex-shrink-0" />
                        <div className="min-w-0 flex-1">
                            <h3 className="text-sm font-semibold text-slate-900 mb-1">{article.title}</h3>
                            <p className="text-sm text-slate-500 line-clamp-1">{article.description}</p>
                        </div>
                        <div className="flex items-center text-xs text-slate-400 flex-shrink-0 mt-0.5">
                            <span className="hidden sm:inline">{formatArticleDate(article.updatedAt)}</span>
                            <ChevronRight className="w-4 h-4 ml-2" />
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );
}

// ══════════════════════════════════════════════════
// ARTICLE VIEW
// ══════════════════════════════════════════════════

interface ArticleViewProps {
    article: Article;
    onBack: () => void;
    onCategoryClick: (key: CategoryKey) => void;
    getCategoryLabel: (key: string) => string;
}

function ArticleView({ article, onBack, onCategoryClick, getCategoryLabel }: ArticleViewProps) {
    return (
        <div className="w-full bg-white min-h-[calc(100vh-64px)] pb-12 pt-6">
            <Breadcrumbs
                items={[
                    { label: 'Help Center', onClick: onBack },
                    {
                        label: getCategoryLabel(article.category),
                        onClick: () => onCategoryClick(article.category),
                    },
                    { label: article.title },
                ]}
            />

            <div className="px-8 mt-10">
                <div className="max-w-6xl mx-auto flex flex-col lg:flex-row gap-16">
                    <div className="flex-1 min-w-0">
                        {/* Article Header */}
                        <div className="mb-10 border-b border-slate-100 pb-8">
                            <span className="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-[#f0f7ff] text-[#16599c] mb-5">
                                {getCategoryLabel(article.category)}
                            </span>
                            <h1 className="text-[32px] font-bold text-slate-900 mb-5 leading-tight tracking-tight">
                                {article.title}
                            </h1>
                            <div className="flex items-center text-sm text-slate-500 gap-6">
                                <div className="flex items-center gap-2">
                                    <Clock className="w-4 h-4" />
                                    {formatArticleDate(article.updatedAt)}
                                </div>
                                <div className="flex items-center gap-2">
                                    <User className="w-4 h-4" />
                                    Written by {article.author}
                                </div>
                            </div>
                        </div>

                        <ArticleRenderer blocks={article.content} />
                    </div>

                    <TableOfContents items={article.toc} />
                </div>
            </div>
        </div>
    );
}
