import { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card } from '@/components/ui/Card';
import { Badge } from '@/components/ui/Badge';
import { Plus, Search, Edit, Trash2, Eye, BookOpen, Filter } from 'lucide-react';
import type { KnowledgeArticle, KnowledgeCategory } from '@/types/ticket';
import type { PageProps, PaginatedData } from '@/types';
import { toast } from 'sonner';
import { formatDate } from '@/lib/formatters';

interface KnowledgeIndexProps extends PageProps {
    articles: PaginatedData<KnowledgeArticle>;
    categories: KnowledgeCategory[];
    filters: {
        search?: string;
        category_id?: number;
        status?: string;
    };
}

export default function Index({ articles, categories, filters }: KnowledgeIndexProps) {
    const [search, setSearch] = useState(filters.search || '');
    const [categoryId, setCategoryId] = useState<string>(filters.category_id?.toString() || '');
    const [status, setStatus] = useState(filters.status || '');

    const applyFilters = (overrides: Record<string, string | number | undefined> = {}) => {
        router.get(route('it-support.admin.knowledge.index'), {
            search: search || undefined,
            category_id: categoryId || undefined,
            status: status || undefined,
            ...overrides,
        }, { preserveState: true, preserveScroll: true });
    };

    const handleSearch = (value: string) => {
        setSearch(value);
        applyFilters({ search: value || undefined });
    };

    const handleDelete = (article: KnowledgeArticle) => {
        if (confirm(`Are you sure you want to delete article "${article.title}"?`)) {
            router.delete(route('it-support.admin.knowledge.destroy', { article: article.id }), {
                onSuccess: () => {
                    toast.success('Article deleted successfully');
                },
                onError: () => {
                    toast.error('Failed to delete article');
                },
            });
        }
    };

    const hasActiveFilters = search || categoryId || status;

    return (
        <>
            <Head title="Manage Knowledge Base" />

            <div className="p-6 space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900">Manage Knowledge Base</h1>
                        <p className="mt-1 text-sm text-gray-500">
                            Kelola artikel knowledge base untuk helpdesk
                        </p>
                    </div>
                    <Button
                        onClick={() => router.visit(route('it-support.admin.knowledge.create'))}
                        className="flex items-center gap-2"
                    >
                        <Plus className="w-4 h-4" />
                        Tambah Artikel
                    </Button>
                </div>

                {/* Filters */}
                <Card padding="md">
                    <div className="flex flex-wrap items-end gap-4">
                        <div className="flex-1 min-w-[200px] space-y-2">
                            <Label htmlFor="search">Pencarian</Label>
                            <div className="relative">
                                <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                                <Input
                                    id="search"
                                    type="text"
                                    placeholder="Cari judul artikel..."
                                    value={search}
                                    onChange={(e) => handleSearch(e.target.value)}
                                    className="pl-9"
                                />
                            </div>
                        </div>

                        <div className="w-48 space-y-2">
                            <Label htmlFor="category">Kategori</Label>
                            <select
                                id="category"
                                value={categoryId}
                                onChange={(e) => {
                                    setCategoryId(e.target.value);
                                    applyFilters({ category_id: e.target.value || undefined });
                                }}
                                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                            >
                                <option value="">Semua Kategori</option>
                                {categories.map((cat) => (
                                    <option key={cat.id} value={cat.id}>{cat.name}</option>
                                ))}
                            </select>
                        </div>

                        <div className="w-40 space-y-2">
                            <Label htmlFor="status">Status</Label>
                            <select
                                id="status"
                                value={status}
                                onChange={(e) => {
                                    setStatus(e.target.value);
                                    applyFilters({ status: e.target.value || undefined });
                                }}
                                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                            >
                                <option value="">Semua Status</option>
                                <option value="published">Published</option>
                                <option value="draft">Draft</option>
                            </select>
                        </div>

                        {hasActiveFilters && (
                            <Button
                                variant="outline"
                                onClick={() => {
                                    setSearch('');
                                    setCategoryId('');
                                    setStatus('');
                                    router.get(route('it-support.admin.knowledge.index'));
                                }}
                                className="flex items-center gap-2"
                            >
                                <Filter className="w-4 h-4" />
                                Clear
                            </Button>
                        )}
                    </div>
                </Card>

                {/* Table */}
                <Card padding="none">
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Judul
                                    </th>
                                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Kategori
                                    </th>
                                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Views
                                    </th>
                                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Author
                                    </th>
                                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Tanggal
                                    </th>
                                    <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Aksi
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="bg-white divide-y divide-gray-200">
                                {articles.data.length === 0 ? (
                                    <tr>
                                        <td colSpan={7} className="px-4 py-12 text-center text-gray-400">
                                            <BookOpen className="w-10 h-10 mx-auto mb-2 text-gray-300" />
                                            {hasActiveFilters ? 'Tidak ada artikel yang cocok dengan filter.' : 'Belum ada artikel knowledge base. Tambahkan artikel pertama.'}
                                        </td>
                                    </tr>
                                ) : (
                                    articles.data.map((article) => (
                                        <tr key={article.id} className="hover:bg-gray-50 transition-colors">
                                            <td className="px-4 py-3">
                                                <span className="font-medium text-gray-900">{article.title}</span>
                                            </td>
                                            <td className="px-4 py-3">
                                                {article.category ? (
                                                    <Badge variant="default">{article.category.name}</Badge>
                                                ) : (
                                                    <span className="text-sm text-gray-400">-</span>
                                                )}
                                            </td>
                                            <td className="px-4 py-3">
                                                <Badge variant={article.is_published ? 'success' : 'warning'}>
                                                    {article.is_published ? 'Published' : 'Draft'}
                                                </Badge>
                                            </td>
                                            <td className="px-4 py-3">
                                                <div className="flex items-center gap-1 text-sm text-gray-600">
                                                    <Eye className="w-4 h-4" />
                                                    {article.views_count}
                                                </div>
                                            </td>
                                            <td className="px-4 py-3 text-sm text-gray-600">
                                                {article.author?.name || '-'}
                                            </td>
                                            <td className="px-4 py-3 text-sm text-gray-600">
                                                {formatDate(article.created_at)}
                                            </td>
                                            <td className="px-4 py-3 text-right">
                                                <div className="flex items-center justify-end gap-2">
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={() => router.visit(route('it-support.admin.knowledge.edit', { article: article.id }))}
                                                        className="h-8 w-8 p-0"
                                                    >
                                                        <Edit className="w-4 h-4" />
                                                    </Button>
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={() => handleDelete(article)}
                                                        className="h-8 w-8 p-0 text-red-600 hover:text-red-700 hover:bg-red-50"
                                                    >
                                                        <Trash2 className="w-4 h-4" />
                                                    </Button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>

                    {/* Pagination */}
                    {articles.meta && articles.meta.last_page > 1 && (
                        <div className="px-4 py-3 border-t border-gray-100 flex items-center justify-between">
                            <p className="text-sm text-gray-500">
                                Showing {articles.meta.from} to {articles.meta.to} of {articles.meta.total}
                            </p>
                            <div className="flex gap-1">
                                {articles.meta.links.map((link, i) => (
                                    <button
                                        key={i}
                                        disabled={!link.url}
                                        onClick={() => link.url && router.get(link.url, {}, { preserveState: true, preserveScroll: true })}
                                        className={`px-3 py-1 text-sm rounded-lg transition-colors ${
                                            link.active ? 'bg-primary text-white' : 'text-gray-600 hover:bg-gray-100'
                                        } ${!link.url && 'opacity-40 cursor-not-allowed'}`}
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                    />
                                ))}
                            </div>
                        </div>
                    )}
                </Card>
            </div>
        </>
    );
}