import React, { useRef, useCallback } from 'react';
import { X, Upload, Trash2 } from 'lucide-react';
import { Input } from '../ui/input';
import { PRItemFormData } from '../../types/purchasing';
import { LazyImage } from '../ui/LazyImage';

interface PRItemRowProps {
    item: PRItemFormData;
    index: number;
    currency: string;
    onUpdate: (index: number, field: keyof PRItemFormData, value: any) => void;
    onRemove: (index: number) => void;
    canRemove: boolean;
    errors?: Record<string, string>;
}

const UNIT_OPTIONS = ['pcs', 'Unit', 'set', 'pack', 'box', 'kg', 'meter', 'liter'];

export const PRItemRow: React.FC<PRItemRowProps> = ({
    item,
    index,
    currency,
    onUpdate,
    onRemove,
    canRemove,
    errors = {},
}) => {
    const fileInputRef = useRef<HTMLInputElement>(null);

    const handleImageUpload = useCallback((e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (file) {
            // Validate file type
            if (!file.type.startsWith('image/')) {
                alert('Please upload an image file');
                return;
            }

            // Validate file size (max 2MB)
            if (file.size > 2 * 1024 * 1024) {
                alert('Image size must be less than 2MB');
                return;
            }

            onUpdate(index, 'image_file', file);

            // Create preview URL
            const reader = new FileReader();
            reader.onloadend = () => {
                onUpdate(index, 'image_path', reader.result as string);
            };
            reader.readAsDataURL(file);
        }
    }, [index, onUpdate]);

    const handleRemoveImage = useCallback(() => {
        onUpdate(index, 'image_file', undefined);
        onUpdate(index, 'image_path', undefined);
        if (fileInputRef.current) {
            fileInputRef.current.value = '';
        }
    }, [index, onUpdate]);

    const calculateTotal = useCallback(() => {
        return item.quantity * item.unit_price;
    }, [item.quantity, item.unit_price]);

    const formatCurrency = (value: number) => {
        return new Intl.NumberFormat('id-ID').format(value);
    };

    return (
        <tr className="bg-white border-b hover:bg-gray-50 transition-colors align-middle">
            {/* No */}
            <td className="px-4 py-3 text-center text-gray-900 font-medium whitespace-nowrap">
                {index + 1}
            </td>

            {/* Item Name */}
            <td className="px-4 py-3">
                <Input
                    type="text"
                    value={item.item_name}
                    onChange={(e) => onUpdate(index, 'item_name', e.target.value)}
                    placeholder="Enter item name"
                    className={`min-w-[150px] ${errors[`items.${index}.item_name`] ? 'border-red-500' : ''}`}
                />
                {errors[`items.${index}.item_name`] && (
                    <p className="mt-1 text-xs text-red-600">{errors[`items.${index}.item_name`]}</p>
                )}
            </td>

            {/* Brand */}
            <td className="px-4 py-3">
                <Input
                    type="text"
                    value={item.brand_name || ''}
                    onChange={(e) => onUpdate(index, 'brand_name', e.target.value)}
                    placeholder="Brand name"
                    className="min-w-[100px]"
                />
            </td>

            {/* Description */}
            <td className="px-4 py-3">
                <Input
                    type="text"
                    value={item.item_description || ''}
                    onChange={(e) => onUpdate(index, 'item_description', e.target.value)}
                    placeholder="Description"
                    className="min-w-[150px]"
                />
            </td>

            {/* Supplier */}
            <td className="px-4 py-3">
                <Input
                    type="text"
                    value={item.supplier_name || ''}
                    onChange={(e) => onUpdate(index, 'supplier_name', e.target.value)}
                    placeholder="Supplier"
                    className="min-w-[100px]"
                />
            </td>

            {/* Qty */}
            <td className="px-4 py-3">
                <Input
                    type="number"
                    min="0.01"
                    step="0.01"
                    value={item.quantity}
                    onChange={(e) => onUpdate(index, 'quantity', parseFloat(e.target.value) || 0)}
                    className={`w-16 text-center ${errors[`items.${index}.quantity`] ? 'border-red-500' : ''}`}
                />
            </td>

            {/* Unit */}
            <td className="px-4 py-3">
                <select
                    value={item.unit}
                    onChange={(e) => onUpdate(index, 'unit', e.target.value)}
                    className="w-20 px-2 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary text-sm bg-white"
                >
                    {UNIT_OPTIONS.map(opt => (
                        <option key={opt} value={opt}>{opt}</option>
                    ))}
                </select>
            </td>

            {/* Price */}
            <td className="px-4 py-3">
                <Input
                    type="number"
                    min="0"
                    value={item.unit_price}
                    onChange={(e) => onUpdate(index, 'unit_price', parseFloat(e.target.value) || 0)}
                    className={`w-28 text-right ${errors[`items.${index}.unit_price`] ? 'border-red-500' : ''}`}
                />
            </td>

            {/* Total */}
            <td className="px-4 py-3 text-right font-medium whitespace-nowrap">
                {formatCurrency(calculateTotal())}
            </td>

            {/* Image */}
            <td className="px-4 py-3 text-center">
                {item.image_path ? (
                    <div className="relative inline-block group">
                        <LazyImage
                            src={item.image_path}
                            alt="Preview"
                            className="w-10 h-10 object-cover rounded border"
                        />
                        <button
                            type="button"
                            onClick={handleRemoveImage}
                            className="absolute -top-2 -right-2 bg-red-500 text-white rounded-full p-0.5 shadow-sm opacity-0 group-hover:opacity-100 transition-opacity"
                        >
                            <X className="w-3 h-3" />
                        </button>
                    </div>
                ) : (
                    <button
                        type="button"
                        onClick={() => fileInputRef.current?.click()}
                        className="w-10 h-10 border border-dashed border-gray-300 rounded flex items-center justify-center text-gray-400 hover:text-gray-600 hover:border-gray-400 transition-colors"
                    >
                        <Upload className="w-4 h-4" />
                    </button>
                )}
                <input
                    ref={fileInputRef}
                    type="file"
                    className="hidden"
                    accept="image/*"
                    onChange={handleImageUpload}
                />
            </td>

            {/* Actions */}
            <td className="px-4 py-3 text-center">
                {canRemove && (
                    <button
                        type="button"
                        onClick={() => onRemove(index)}
                        className="text-red-500 hover:text-red-700 transition-colors"
                    >
                        <Trash2 className="w-5 h-5" />
                    </button>
                )}
            </td>
        </tr>
    );
};
