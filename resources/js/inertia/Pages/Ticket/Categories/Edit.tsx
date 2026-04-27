import { FormEventHandler } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card } from '@/components/ui/Card';
import { ArrowLeft, Save } from 'lucide-react';
import { toast } from 'sonner';
import type { PageProps } from '@/types';
import type { TicketCategory } from '@/types/ticket';

interface EditProps extends PageProps {
    category: TicketCategory;
    errors?: Record<string, string>;
}

interface CategoryFormData {
    name: string;
    description: string;
    color: string;
    is_active: boolean;
}

export default function Edit({ category, errors }: EditProps) {
    const { data, setData, put, processing } = useForm<CategoryFormData>({
        name: category.name,
        description: category.description || '',
        color: category.color,
        is_active: category.is_active,
    });

    const handleSubmit: FormEventHandler = (e) => {
        e.preventDefault();

        put(route('it-support.admin.categories.update', { category: category.id }), {
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

    // Predefined color options
    const colorOptions = [
        '#6366F1', // Indigo
        '#8B5CF6', // Violet
        '#EC4899', // Pink
        '#EF4444', // Red
        '#F97316', // Orange
        '#EAB308', // Yellow
        '#22C55E', // Green
        '#14B8A6', // Teal
        '#06B6D4', // Cyan
        '#3B82F6', // Blue
    ];

    return (
        <>
            <Head title={`Edit ${category.name}`} />

            <div className="p-6 space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900">Edit Kategori</h1>
                        <p className="mt-1 text-sm text-gray-500">
                            Perbarui informasi kategori
                        </p>
                    </div>
                    <Button
                        variant="outline"
                        onClick={() => router.visit(route('it-support.admin.categories.index'))}
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
                                    placeholder="e.g., Hardware, Software, Network"
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

                            {/* Color */}
                            <div className="space-y-2">
                                <Label htmlFor="color" required>
                                    Warna
                                </Label>
                                <div className="flex items-center gap-4">
                                    <div
                                        className="w-12 h-12 rounded-lg border-2 border-gray-200"
                                        style={{ backgroundColor: data.color }}
                                    />
                                    <Input
                                        id="color"
                                        type="text"
                                        value={data.color}
                                        onChange={(e) => setData('color', e.target.value)}
                                        placeholder="#6366F1"
                                        className="w-40"
                                    />
                                    <Input
                                        type="color"
                                        value={data.color}
                                        onChange={(e) => setData('color', e.target.value)}
                                        className="w-12 h-12 p-1 cursor-pointer"
                                    />
                                </div>
                                {errors?.color && (
                                    <p className="text-sm text-red-600">{errors.color}</p>
                                )}
                            </div>

                            {/* Color Presets */}
                            <div className="space-y-2">
                                <Label>Pilihan Warna</Label>
                                <div className="flex flex-wrap gap-2">
                                    {colorOptions.map((color) => (
                                        <button
                                            key={color}
                                            type="button"
                                            onClick={() => setData('color', color)}
                                            className={`w-8 h-8 rounded-full border-2 transition-transform hover:scale-110 ${
                                                data.color === color ? 'border-gray-900 scale-110' : 'border-gray-200'
                                            }`}
                                            style={{ backgroundColor: color }}
                                        />
                                    ))}
                                </div>
                            </div>

                            {/* Status */}
                            <div className="flex items-center gap-2">
                                <input
                                    type="checkbox"
                                    id="is_active"
                                    checked={data.is_active}
                                    onChange={(e) => setData('is_active', e.target.checked)}
                                    className="w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary"
                                />
                                <Label htmlFor="is_active" className="cursor-pointer">
                                    Kategori Aktif
                                </Label>
                            </div>
                        </div>
                    </Card>

                    {/* Actions */}
                    <div className="flex items-center justify-end gap-4">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => router.visit(route('it-support.admin.categories.index'))}
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