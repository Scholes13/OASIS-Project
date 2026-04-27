import { FormEventHandler } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card } from '@/components/ui/Card';
import { ArrowLeft, Save } from 'lucide-react';
import { toast } from 'sonner';
import type { PageProps } from '@/types';
import type { KnowledgeCategory } from '@/types/ticket';

interface EditProps extends PageProps {
    category: KnowledgeCategory;
    categories: KnowledgeCategory[];
    errors?: Record<string, string>;
}

interface CategoryFormData {
    name: string;
    description: string;
    icon: string;
    order: number;
    parent_id: number | null;
}

export default function Edit({ category, categories, errors }: EditProps) {
    const { data, setData, put, processing } = useForm<CategoryFormData>({
        name: category.name,
        description: category.description || '',
        icon: category.icon || '',
        order: category.order,
        parent_id: category.parent?.id || null,
    });

    const handleSubmit: FormEventHandler = (e) => {
        e.preventDefault();

        put(route('it-support.admin.knowledge.categories.update', { category: category.id }), {
            onSuccess: () => {
                toast.success('Kategori berhasil diperbarui');
            },
            onError: (errors) => {
                const errorMessage = typeof errors === 'object' && errors !== null && 'message' in errors
                    ? (errors as { message: string }).message
                    : 'Gagal memperbarui kategori';
                toast.error(errorMessage);
            },
        });
    };

    // Common icon options (emoji or lucide icon names)
    const iconOptions = [
        { value: '', label: 'None' },
        { value: '📚', label: '📚 Books' },
        { value: '💻', label: '💻 Computer' },
        { value: '🔧', label: '🔧 Tools' },
        { value: '📖', label: '📖 Documentation' },
        { value: '🎓', label: '🎓 Learning' },
        { value: '🔒', label: '🔒 Security' },
        { value: '🌐', label: '🌐 Network' },
        { value: '☁️', label: '☁️ Cloud' },
        { value: '📱', label: '📱 Mobile' },
        { value: '⚙️', label: '⚙️ Settings' },
        { value: '❓', label: '❓ FAQ' },
        { value: '📝', label: '📝 Notes' },
        { value: '🔍', label: '🔍 Search' },
    ];

    // Filter out the current category from parent options to prevent self-reference
    const availableParents = categories.filter((cat) => cat.id !== category.id);

    return (
        <>
            <Head title={`Edit ${category.name}`} />

            <div className="p-6 space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900">Edit Kategori KB</h1>
                        <p className="mt-1 text-sm text-gray-500">
                            Perbarui informasi kategori
                        </p>
                    </div>
                    <Button
                        variant="outline"
                        onClick={() => router.visit(route('it-support.admin.knowledge.categories.index'))}
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
                            <h2 className="text-lg font-semibold text-gray-900">Informasi Kategori</h2>
                        </div>
                        <div className="p-6 space-y-4">
                            {/* Name */}
                            <div className="space-y-2">
                                <Label htmlFor="name" required>
                                    Nama Kategori
                                </Label>
                                <Input
                                    id="name"
                                    type="text"
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    placeholder="e.g., Hardware, Software, FAQ"
                                    required
                                />
                                {errors?.name && (
                                    <p className="text-sm text-red-600">{errors.name}</p>
                                )}
                            </div>

                            {/* Description */}
                            <div className="space-y-2">
                                <Label htmlFor="description">
                                    Deskripsi
                                </Label>
                                <textarea
                                    id="description"
                                    value={data.description}
                                    onChange={(e) => setData('description', e.target.value)}
                                    placeholder="Deskripsi kategori (opsional)"
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary min-h-[100px] resize-y"
                                />
                                {errors?.description && (
                                    <p className="text-sm text-red-600">{errors.description}</p>
                                )}
                            </div>

                            {/* Icon */}
                            <div className="space-y-2">
                                <Label htmlFor="icon">
                                    Icon
                                </Label>
                                <div className="flex items-center gap-4">
                                    <select
                                        id="icon"
                                        value={data.icon}
                                        onChange={(e) => setData('icon', e.target.value)}
                                        className="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                                    >
                                        {iconOptions.map((opt) => (
                                            <option key={opt.value} value={opt.value}>
                                                {opt.label}
                                            </option>
                                        ))}
                                    </select>
                                    <div
                                        className="w-12 h-12 rounded-lg border-2 border-gray-200 bg-gray-50 flex items-center justify-center text-2xl"
                                    >
                                        {data.icon || '📁'}
                                    </div>
                                </div>
                                {errors?.icon && (
                                    <p className="text-sm text-red-600">{errors.icon}</p>
                                )}
                            </div>

                            {/* Parent Category */}
                            <div className="space-y-2">
                                <Label htmlFor="parent_id">
                                    Parent Category
                                </Label>
                                <select
                                    id="parent_id"
                                    value={data.parent_id || ''}
                                    onChange={(e) => setData('parent_id', e.target.value ? parseInt(e.target.value) : null)}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                                >
                                    <option value="">None (Top Level)</option>
                                    {availableParents.map((cat) => (
                                        <option key={cat.id} value={cat.id}>{cat.name}</option>
                                    ))}
                                </select>
                                <p className="text-sm text-gray-500">
                                    Pilih parent category jika ini adalah sub-category
                                </p>
                                {errors?.parent_id && (
                                    <p className="text-sm text-red-600">{errors.parent_id}</p>
                                )}
                            </div>

                            {/* Order */}
                            <div className="space-y-2">
                                <Label htmlFor="order">
                                    Order
                                </Label>
                                <Input
                                    id="order"
                                    type="number"
                                    value={data.order}
                                    onChange={(e) => setData('order', parseInt(e.target.value) || 0)}
                                    min={0}
                                />
                                <p className="text-sm text-gray-500">
                                    Urutan tampil di sidebar (lower = first)
                                </p>
                                {errors?.order && (
                                    <p className="text-sm text-red-600">{errors.order}</p>
                                )}
                            </div>
                        </div>
                    </Card>

                    {/* Actions */}
                    <div className="flex items-center justify-end gap-4">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => router.visit(route('it-support.admin.knowledge.categories.index'))}
                            disabled={processing}
                        >
                            Batal
                        </Button>
                        <Button type="submit" disabled={processing} className="flex items-center gap-2">
                            <Save className="w-4 h-4" />
                            {processing ? 'Menyimpan...' : 'Perbarui Kategori'}
                        </Button>
                    </div>
                </form>
            </div>
        </>
    );
}