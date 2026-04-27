import { useState, useRef } from 'react';
import { useForm } from '@inertiajs/react';
import { Paperclip, X, Save } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select } from '@/components/ui/select';
import { toast } from '@/components/ui/toast';
import { cn } from '@/lib/utils';
import type { TicketFormData, TicketPriority, TicketCategory } from '@/types';

interface TicketFormProps {
    onSuccess?: () => void;
    onCancel?: () => void;
    initialData?: Partial<TicketFormData>;
    categories: TicketCategory[];
    priorities?: TicketPriority[];
    isLoading?: boolean;
    submitLabel?: string;
    className?: string;
}

const defaultPriorities: TicketPriority[] = ['low', 'medium', 'high', 'critical'];

const priorityLabels: Record<TicketPriority, string> = {
    low: 'Rendah',
    medium: 'Sedang',
    high: 'Tinggi',
    critical: 'Kritis',
};

export function TicketForm({
    onSuccess,
    onCancel,
    initialData,
    categories,
    priorities = defaultPriorities,
    isLoading = false,
    submitLabel = 'Simpan',
    className,
}: TicketFormProps) {
    const [attachments, setAttachments] = useState<File[]>([]);
    const fileInputRef = useRef<HTMLInputElement>(null);

    const { data, setData, post, processing, errors } = useForm<TicketFormData>({
        title: initialData?.title || '',
        description: initialData?.description || '',
        priority: initialData?.priority || 'medium',
        category_id: initialData?.category_id || null,
        department_id: initialData?.department_id || null,
        assigned_user_id: initialData?.assigned_user_id || null,
        attachments: [],
    });

    const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const files = Array.from(e.target.files || []);
        
        // Validate files: max 5 files, 10MB each
        const validFiles: File[] = [];
        for (const file of files) {
            if (attachments.length + validFiles.length >= 5) {
                toast.warning('Maksimal 5 lampiran diperbolehkan');
                break;
            }
            if (file.size > 10 * 1024 * 1024) {
                toast.warning(`File ${file.name} melebihi 10MB`);
                continue;
            }
            validFiles.push(file);
        }
        
        setAttachments(prev => [...prev, ...validFiles]);
        setData('attachments', [...attachments, ...validFiles]);
        
        // Reset input
        if (fileInputRef.current) {
            fileInputRef.current.value = '';
        }
    };

    const removeAttachment = (index: number) => {
        const newAttachments = attachments.filter((_, i) => i !== index);
        setAttachments(newAttachments);
        setData('attachments', newAttachments);
    };

    const formatFileSize = (bytes: number): string => {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        
        post(route('it-support.submit.store'), {
            onSuccess: () => {
                toast.success('Ticket berhasil dibuat');
                onSuccess?.();
            },
            onError: () => {
                toast.error('Gagal membuat ticket');
            },
        });
    };

    return (
        <form onSubmit={handleSubmit} className={cn('space-y-4', className)}>
            {/* Title */}
            <div>
                <Label htmlFor="title">Judul</Label>
                <Input
                    id="title"
                    value={data.title}
                    onChange={(e) => setData('title', e.target.value)}
                    placeholder="Masukkan judul ticket"
                    error={errors.title}
                    required
                />
            </div>

            {/* Description */}
            <div>
                <Label htmlFor="description">Deskripsi</Label>
                <Textarea
                    id="description"
                    value={data.description}
                    onChange={(e) => setData('description', e.target.value)}
                    placeholder="Jelaskan masalah atau permintaan Anda secara detail"
                    rows={5}
                    error={errors.description}
                    required
                />
            </div>

            {/* Priority & Category */}
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <Label>Prioritas</Label>
                    <Select
                        value={data.priority}
                        onChange={(value) => setData('priority', value as TicketPriority)}
                        options={priorities.map(p => ({
                            value: p,
                            label: priorityLabels[p],
                        }))}
                        error={errors.priority}
                    />
                </div>
                <div>
                    <Label>Kategori</Label>
                    <Select
                        value={data.category_id?.toString() || ''}
                        onChange={(value) => setData('category_id', value ? parseInt(value.toString()) : null)}
                        options={[
                            { value: '', label: 'Pilih kategori...' },
                            ...categories.map(c => ({
                                value: c.id.toString(),
                                label: c.name,
                            })),
                        ]}
                        error={errors.category_id}
                    />
                </div>
            </div>

            {/* Attachments */}
            <div>
                <Label>Lampiran</Label>
                <input
                    ref={fileInputRef}
                    type="file"
                    multiple
                    accept="*/*"
                    onChange={handleFileChange}
                    className="hidden"
                />
                <div className="mt-1 space-y-2">
                    {attachments.length > 0 && (
                        <div className="space-y-1">
                            {attachments.map((file, index) => (
                                <div
                                    key={index}
                                    className="flex items-center justify-between px-3 py-2 bg-gray-50 rounded-lg text-sm"
                                >
                                    <div className="flex items-center gap-2 truncate">
                                        <Paperclip className="w-4 h-4 text-gray-400 flex-shrink-0" />
                                        <span className="truncate">{file.name}</span>
                                        <span className="text-gray-400 text-xs">({formatFileSize(file.size)})</span>
                                    </div>
                                    <button
                                        type="button"
                                        onClick={() => removeAttachment(index)}
                                        className="text-gray-400 hover:text-red-500 ml-2"
                                    >
                                        <X className="w-4 h-4" />
                                    </button>
                                </div>
                            ))}
                        </div>
                    )}
                    <button
                        type="button"
                        onClick={() => fileInputRef.current?.click()}
                        className="flex items-center gap-2 px-4 py-2 border border-dashed border-gray-300 rounded-lg text-sm text-gray-600 hover:bg-gray-50 transition-colors"
                    >
                        <Paperclip className="w-4 h-4" />
                        Tambah Lampiran ({attachments.length}/5)
                    </button>
                    <p className="text-xs text-gray-400">Maksimal 5 file, masing-masing max 10MB</p>
                </div>
            </div>

            {/* Actions */}
            <div className="flex justify-end gap-2 pt-4">
                {onCancel && (
                    <Button type="button" variant="outline" onClick={onCancel}>
                        Batal
                    </Button>
                )}
                <Button type="submit" loading={processing || isLoading}>
                    <Save className="w-4 h-4 mr-2" />
                    {submitLabel}
                </Button>
            </div>
        </form>
    );
}