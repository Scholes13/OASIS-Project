import { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card } from '@/components/ui/Card';
import { Badge } from '@/components/ui/Badge';
import { Plus, Search, Edit, Trash2, FolderTree, ArrowUp, ArrowDown } from 'lucide-react';
import type { KnowledgeCategory } from '@/types/ticket';
import type { PageProps } from '@/types';
import { toast } from 'sonner';

interface KnowledgeCategoriesIndexProps extends PageProps {
    categories: KnowledgeCategory[];
}

export default function Index({ categories }: KnowledgeCategoriesIndexProps) {
    const [search, setSearch] = useState('');

    const filteredCategories = categories.filter(
        (cat) =>
            cat.name.toLowerCase().includes(search.toLowerCase()) ||
            cat.slug.toLowerCase().includes(search.toLowerCase()) ||
            (cat.description?.toLowerCase() || '').includes(search.toLowerCase())
    );

    const handleDelete = (category: KnowledgeCategory) => {
        if (confirm(`Are you sure you want to delete category "${category.name}"?`)) {
            router.delete(route('it-support.admin.knowledge.categories.destroy', { category: category.id }), {
                onSuccess: () => {
                    toast.success('Category deleted successfully');
                },
                onError: () => {
                    toast.error('Failed to delete category');
                },
            });
        }
    };

    return (
        <>
            <Head title="Kategori Knowledge Base" />

            <div className="p-6 space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900">Kategori Knowledge Base</h1>
                        <p className="mt-1 text-sm text-gray-500">
                            Kelola kategori untuk artikel knowledge base
                        </p>
                    </div>
                    <Button
                        onClick={() => router.visit(route('it-support.admin.knowledge.categories.create'))}
                        className="flex items-center gap-2"
                    >
                        <Plus className="w-4 h-4" />
                        Tambah Kategori
                    </Button>
                </div>

                {/* Search */}
                <Card padding="md">
                    <div className="space-y-2">
                        <Label htmlFor="search">Pencarian</Label>
                        <div className="relative">
                            <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                            <Input
                                id="search"
                                type="text"
                                placeholder="Cari berdasarkan nama, slug, atau deskripsi..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                className="pl-9"
                            />
                        </div>
                    </div>
                </Card>

                {/* Table */}
                <Card padding="none">
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Nama
                                    </th>
                                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Slug
                                    </th>
                                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Deskripsi
                                    </th>
                                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Parent
                                    </th>
                                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Order
                                    </th>
                                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Articles
                                    </th>
                                    <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Aksi
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="bg-white divide-y divide-gray-200">
                                {filteredCategories.length === 0 ? (
                                    <tr>
                                        <td colSpan={7} className="px-4 py-12 text-center text-gray-400">
                                            <FolderTree className="w-10 h-10 mx-auto mb-2 text-gray-300" />
                                            {search ? 'Tidak ada kategori yang cocok dengan pencarian.' : 'Belum ada kategori. Tambahkan kategori pertama.'}
                                        </td>
                                    </tr>
                                ) : (
                                    filteredCategories.map((category) => (
                                        <tr key={category.id} className="hover:bg-gray-50 transition-colors">
                                            <td className="px-4 py-3">
                                                <div className="flex items-center gap-2">
                                                    {category.icon && (
                                                        <span className="text-lg">{category.icon}</span>
                                                    )}
                                                    <span className="font-medium text-gray-900">{category.name}</span>
                                                </div>
                                            </td>
                                            <td className="px-4 py-3">
                                                <code className="text-sm text-gray-500 bg-gray-100 px-1 py-0.5 rounded">
                                                    {category.slug}
                                                </code>
                                            </td>
                                            <td className="px-4 py-3 text-sm text-gray-600 max-w-xs truncate">
                                                {category.description || '-'}
                                            </td>
                                            <td className="px-4 py-3">
                                                {category.parent ? (
                                                    <Badge variant="default">{category.parent.name}</Badge>
                                                ) : (
                                                    <span className="text-sm text-gray-400">-</span>
                                                )}
                                            </td>
                                            <td className="px-4 py-3">
                                                <div className="flex items-center gap-1 text-sm text-gray-600">
                                                    <ArrowUp className="w-3 h-3" />
                                                    <ArrowDown className="w-3 h-3" />
                                                    {category.order}
                                                </div>
                                            </td>
                                            <td className="px-4 py-3">
                                                <Badge variant="info">{category.articles_count || 0}</Badge>
                                            </td>
                                            <td className="px-4 py-3 text-right">
                                                <div className="flex items-center justify-end gap-2">
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={() => router.visit(route('it-support.admin.knowledge.categories.edit', { category: category.id }))}
                                                        className="h-8 w-8 p-0"
                                                    >
                                                        <Edit className="w-4 h-4" />
                                                    </Button>
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={() => handleDelete(category)}
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
                </Card>
            </div>
        </>
    );
}