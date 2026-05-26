import React, { useMemo, useState } from 'react';
import { Check, FileText, Upload, X } from 'lucide-react';
import { toast } from 'sonner';

interface OfflineApprovalUploadProps {
    value: File | null;
    onChange: (file: File | null) => void;
    notes: string;
    onNotesChange: (notes: string) => void;
    isSubmitting?: boolean;
    error?: string;
    accept?: string;
    maxSizeMb?: number;
}

const formatFileSize = (size: number) => `${(size / 1024 / 1024).toFixed(2)} MB`;

export const OfflineApprovalUpload: React.FC<OfflineApprovalUploadProps> = ({
    value,
    onChange,
    notes,
    onNotesChange,
    isSubmitting = false,
    error,
    accept = '.jpg,.jpeg,.png,.pdf',
    maxSizeMb = 10,
}) => {
    const [localError, setLocalError] = useState<string | null>(null);
    const previewUrl = useMemo(() => {
        if (!value || !value.type.startsWith('image/')) return null;

        return URL.createObjectURL(value);
    }, [value]);

    const allowedExtensions = accept.split(',').map((item) => item.trim().replace('.', '').toLowerCase());

    const handleFileChange = (event: React.ChangeEvent<HTMLInputElement>) => {
        const file = event.target.files?.[0] || null;
        setLocalError(null);

        if (!file) {
            onChange(null);
            return;
        }

        const extension = file.name.split('.').pop()?.toLowerCase() || '';
        if (!allowedExtensions.includes(extension)) {
            const message = 'Only JPG, PNG, or PDF files are allowed';
            setLocalError(message);
            toast.error(message);
            event.target.value = '';
            return;
        }

        if (file.size > maxSizeMb * 1024 * 1024) {
            const message = `File size must be less than ${maxSizeMb}MB`;
            setLocalError(message);
            toast.error(message);
            event.target.value = '';
            return;
        }

        onChange(file);
    };

    return (
        <div className="space-y-3">
            <div>
                <label htmlFor="offline_approval_document" className="block text-sm font-medium text-gray-700">
                    Bukti Approval <span className="text-red-500">*</span>
                </label>
                <p className="text-xs text-gray-500 mb-2">
                    Upload foto/scan dokumen yang sudah ditandatangani (JPG, PNG, PDF - max {maxSizeMb}MB)
                </p>
                <label className="cursor-pointer block">
                    <input
                        type="file"
                        id="offline_approval_document"
                        accept={accept}
                        required
                        className="hidden"
                        onChange={handleFileChange}
                        disabled={isSubmitting}
                    />
                    <div className={`border-2 border-dashed rounded-lg p-4 text-center transition-colors ${
                        value ? 'border-purple-400 bg-purple-50' : 'border-gray-300 hover:border-purple-400 hover:bg-purple-50'
                    }`}>
                        {!value ? (
                            <div>
                                <Upload className="mx-auto h-8 w-8 text-gray-400" />
                                <p className="mt-1 text-sm text-gray-600">
                                    <span className="font-medium text-purple-600">Klik untuk upload</span> atau drag & drop
                                </p>
                            </div>
                        ) : previewUrl ? (
                            <div className="flex items-center justify-center gap-3">
                                <img src={previewUrl} alt={value.name} className="h-14 w-14 rounded-lg object-cover border border-purple-200" />
                                <div className="text-left">
                                    <p className="text-sm text-purple-700 font-medium">{value.name}</p>
                                    <p className="text-xs text-purple-600">{formatFileSize(value.size)}</p>
                                </div>
                                <button type="button" onClick={(event) => { event.preventDefault(); onChange(null); }} className="p-1 text-red-600 hover:bg-red-50 rounded">
                                    <X className="h-4 w-4" />
                                </button>
                            </div>
                        ) : (
                            <div className="flex items-center justify-center space-x-2">
                                {value.type === 'application/pdf' ? <FileText className="h-6 w-6 text-purple-600" /> : <Check className="h-6 w-6 text-purple-600" />}
                                <span className="text-sm text-purple-700 font-medium">{value.name}</span>
                                <span className="text-xs text-purple-600">({formatFileSize(value.size)})</span>
                                <button type="button" onClick={(event) => { event.preventDefault(); onChange(null); }} className="p-1 text-red-600 hover:bg-red-50 rounded">
                                    <X className="h-4 w-4" />
                                </button>
                            </div>
                        )}
                    </div>
                </label>
                {(localError || error) && <p className="mt-1 text-sm text-red-600">{localError || error}</p>}
            </div>
            <div>
                <label htmlFor="offline_notes" className="block text-sm font-medium text-gray-700">Notes (optional)</label>
                <textarea
                    id="offline_notes"
                    rows={2}
                    value={notes}
                    onChange={(event) => onNotesChange(event.target.value)}
                    className="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 text-sm"
                    placeholder="Add any notes about the offline approval..."
                    disabled={isSubmitting}
                />
            </div>
        </div>
    );
};
