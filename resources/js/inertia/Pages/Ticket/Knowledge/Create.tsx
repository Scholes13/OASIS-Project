import { FormEventHandler, useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card } from '@/components/ui/Card';
import { ArrowLeft, Save, X, Plus } from 'lucide-react';
import { toast } from 'sonner';
import type { PageProps } from '@/types';
import type { KnowledgeCategory } from '@/types/ticket';

interface CreateProps extends PageProps {
    categories: KnowledgeCategory[];
    errors?: Record<string, string>;
}

interface ArticleFormData {
    title: string;
    content: string;
    category_id: number | null;
    tags: string[];
    meta_description: string;
    is_published: boolean;
}

export default function Create({ categories, errors }: CreateProps) {
    const [tagInput, setTagInput] = useState('');

    const { data, setData, post, processing } = useForm<ArticleFormData>({
        title: '',
        content: '',
        category_id: null,
        tags: [],
        meta_description: '',
        is_published: true,
    });

    const handleAddTag = () => {
        if (tagInput.trim() && !data.tags.includes(tagInput.trim())) {
            setData('tags', [...data.tags, tagInput.trim()]);
            setTagInput('');
        }
    };

    const handleRemoveTag = (tag: string) => {
        setData('tags', data.tags.filter((t) => t !== tag));
    };

    const handleTagKeyDown = (e: React.KeyboardEvent) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            handleAddTag();
        }
    };

    const handleSubmit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('it-support.admin.knowledge.store'), {
            onSuccess: () => {
                toast.success('Artikel berhasil dibuat');
            },
            onError: (errors) => {
                const errorMessage = typeof errors === 'object' && errors !== null && 'message' in errors
                    ? (errors as { message: string }).message
                    : 'Gagal membuat artikel';
                toast.error(errorMessage);
            },
        });
    };

    return (
        <>
            <Head title="Tambah Artikel" />

            <div className="p-6 space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900">Tambah Artikel</h1>
                        <p className="mt-1 text-sm text-gray-500">
                            Tambahkan artikel baru ke knowledge base
                        </p>
                    </div>
                    <Button
                        variant="outline"
                        onClick={() => router.visit(route('it-support.admin.knowledge.index'))}
                        className="flex items-center gap-2"
                    >
                        <ArrowLeft className="w-4 h-4" />
                        Kembali ke Daftar
                    </Button>
                </div>

                {/* Form */}
                <form onSubmit={handleSubmit} className="space-y-6">
                    <Card>
                        <div className="px-6 py-4 border-b border-gray-200">
                            <h2 className="text-lg font-semibold text-gray-900">Informasi Artikel</h2>
                        </div>
                        <div className="p-6 space-y-4">
                            {/* Title */}
                            <div className="space-y-2">
                                <Label htmlFor="title" required>
                                    Judul Artikel
                                </Label>
                                <Input
                                    id="title"
                                    type="text"
                                    value={data.title}
                                    onChange={(e) => setData('title', e.target.value)}
                                    placeholder="e.g., Cara Reset Password"
                                    required
                                />
                                {errors?.title && (
                                    <p className="text-sm text-red-600">{errors.title}</p>
                                )}
                            </div>

                            {/* Category */}
                            <div className="space-y-2">
                                <Label htmlFor="category_id">
                                    Kategori
                                </Label>
                                <select
                                    id="category_id"
                                    value={data.category_id || ''}
                                    onChange={(e) => setData('category_id', e.target.value ? parseInt(e.target.value) : null)}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                                >
                                    <option value="">Pilih Kategori</option>
                                    {categories.map((cat) => (
                                        <option key={cat.id} value={cat.id}>{cat.name}</option>
                                    ))}
                                </select>
                                {errors?.category_id && (
                                    <p className="text-sm text-red-600">{errors.category_id}</p>
                                )}
                            </div>

                            {/* Content */}
                            <div className="space-y-2">
                                <Label htmlFor="content" required>
                                    Konten
                                </Label>
                                <textarea
                                    id="content"
                                    value={data.content}
                                    onChange={(e) => setData('content', e.target.value)}
                                    placeholder="Tulis konten artikel di sini... ( Mendukung format Markdown )"
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary min-h-[300px] resize-y font-mono text-sm"
                                    required
                                />
                                {errors?.content && (
                                    <p className="text-sm text-red-600">{errors.content}</p>
                                )}
                            </div>

                            {/* Meta Description */}
                            <div className="space-y-2">
                                <Label htmlFor="meta_description">
                                    Meta Description
                                </Label>
                                <textarea
                                    id="meta_description"
                                    value={data.meta_description}
                                    onChange={(e) => setData('meta_description', e.target.value)}
                                    placeholder="Deskripsi singkat untuk SEO (opsional)"
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary min-h-[80px] resize-y"
                                />
                                {errors?.meta_description && (
                                    <p className="text-sm text-red-600">{errors.meta_description}</p>
                                )}
                            </div>

                            {/* Tags */}
                            <div className="space-y-2">
                                <Label>Tags</Label>
                                <div className="flex items-center gap-2">
                                    <Input
                                        type="text"
                                        value={tagInput}
                                        onChange={(e) => setTagInput(e.target.value)}
                                        onKeyDown={handleTagKeyDown}
                                        placeholder="Ketik tag dan tekan Enter"
                                        className="flex-1"
                                    />
                                    <Button type="button" variant="outline" onClick={handleAddTag}>
                                        <Plus className="w-4 h-4" />
                                    </Button>
                                </div>
                                {data.tags.length > 0 && (
                                    <div className="flex flex-wrap gap-2 mt-2">
                                        {data.tags.map((tag) => (
                                            <span
                                                key={tag}
                                                className="inline-flex items-center gap-1 px-2 py-1 bg-gray-100 text-sm text-gray-700 rounded-full"
                                            >
                                                {tag}
                                                <button
                                                    type="button"
                                                    onClick={() => handleRemoveTag(tag)}
                                                    className="hover:text-red-600"
                                                >
                                                    <X className="w-3 h-3" />
                                                </button>
                                            </span>
                                        ))}
                                    </div>
                                )}
                                {errors?.tags && (
                                    <p className="text-sm text-red-600">{errors.tags}</p>
                                )}
                            </div>

                            {/* Published Toggle */}
                            <div className="flex items-center gap-2">
                                <input
                                    type="checkbox"
                                    id="is_published"
                                    checked={data.is_published}
                                    onChange={(e) => setData('is_published', e.target.checked)}
                                    className="w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary"
                                />
                                <Label htmlFor="is_published" className="cursor-pointer">
                                    Publikasikan Artikel
                                </Label>
                            </div>
                        </div>
                    </Card>

                    {/* Actions */}
                    <div className="flex items-center justify-end gap-4">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => router.visit(route('it-support.admin.knowledge.index'))}
                            disabled={processing}
                        >
                            Batal
                        </Button>
                        <Button type="submit" disabled={processing} className="flex items-center gap-2">
                            <Save className="w-4 h-4" />
                            {processing ? 'Menyimpan...' : 'Simpan Artikel'}
                        </Button>
                    </div>
                </form>
            </div>
        </>
    );
}