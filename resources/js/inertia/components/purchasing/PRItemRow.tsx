import React, { useCallback, useRef } from 'react';
import { X, Upload, Image as ImageIcon } from 'lucide-react';
import { Button } from '../ui/Button';
import { Input } from '../ui/input';
import { PRItemFormData } from '../../types/purchasing';
import { motion } from 'framer-motion';
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
        <motion.div
            initial={{ opacity: 0, y: -10 }}
            animate={{ opacity: 1, y: 0 }}
            exit={{ opacity: 0, x: -10 }}
            transition={{ duration: 0.2 }}
            className="border border-gray-200 rounded-lg p-4 bg-white"
        >
            <div className="flex items-start justify-between mb-4">
                <h4 className="text-sm font-medium text-gray-900">Item #{index + 1}</h4>
                {canRemove && (
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        onClick={() => onRemove(index)}
                        className="text-red-600 hover:text-red-700 hover:bg-red-50"
                    >
                        <X className="w-4 h-4" />
                    </Button>
                )}
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                {/* Item Name */}
                <div className="md:col-span-2">
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                        Item Name <span className="text-red-500">*</span>
                    </label>
                    <Input
                        type="text"
                        value={item.item_name}
                        onChange={(e) => onUpdate(index, 'item_name', e.target.value)}
                        placeholder="Enter item name"
                        className={errors[`items.${index}.item_name`] ? 'border-red-500' : ''}
                    />
                    {errors[`items.${index}.item_name`] && (
                        <p className="mt-1 text-sm text-red-600">{errors[`items.${index}.item_name`]}</p>
                    )}
                </div>

                {/* Brand Name */}
                <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                        Brand
                    </label>
                    <Input
                        type="text"
                        value={item.brand_name || ''}
                        onChange={(e) => onUpdate(index, 'brand_name', e.target.value)}
                        placeholder="Enter brand name"
                    />
                </div>

                {/* Supplier Name */}
                <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                        Supplier
                    </label>
                    <Input
                        type="text"
                        value={item.supplier_name || ''}
                        onChange={(e) => onUpdate(index, 'supplier_name', e.target.value)}
                        placeholder="Enter supplier name"
                    />
                </div>

                {/* Description */}
                <div className="md:col-span-2">
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                        Description
                    </label>
                    <textarea
                        value={item.item_description || ''}
                        onChange={(e) => onUpdate(index, 'item_description', e.target.value)}
                        placeholder="Enter item description"
                        rows={2}
                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm"
                    />
                </div>

                {/* Quantity */}
                <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                        Quantity <span className="text-red-500">*</span>
                    </label>
                    <Input
                        type="number"
                        min="0.01"
                        step="0.01"
                        value={item.quantity}
                        onChange={(e) => onUpdate(index, 'quantity', parseFloat(e.target.value) || 0)}
                        placeholder="0"
                        className={errors[`items.${index}.quantity`] ? 'border-red-500' : ''}
                    />
                    {errors[`items.${index}.quantity`] && (
                        <p className="mt-1 text-sm text-red-600">{errors[`items.${index}.quantity`]}</p>
                    )}
                </div>

                {/* Unit */}
                <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                        Unit <span className="text-red-500">*</span>
                    </label>
                    <Input
                        type="text"
                        value={item.unit}
                        onChange={(e) => onUpdate(index, 'unit', e.target.value)}
                        placeholder="e.g., pcs, box, kg"
                        className={errors[`items.${index}.unit`] ? 'border-red-500' : ''}
                    />
                    {errors[`items.${index}.unit`] && (
                        <p className="mt-1 text-sm text-red-600">{errors[`items.${index}.unit`]}</p>
                    )}
                </div>

                {/* Unit Price */}
                <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                        Unit Price ({currency}) <span className="text-red-500">*</span>
                    </label>
                    <Input
                        type="number"
                        min="0"
                        step="1"
                        value={item.unit_price}
                        onChange={(e) => onUpdate(index, 'unit_price', parseFloat(e.target.value) || 0)}
                        placeholder="0"
                        className={errors[`items.${index}.unit_price`] ? 'border-red-500' : ''}
                    />
                    {errors[`items.${index}.unit_price`] && (
                        <p className="mt-1 text-sm text-red-600">{errors[`items.${index}.unit_price`]}</p>
                    )}
                </div>

                {/* Total */}
                <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                        Total ({currency})
                    </label>
                    <div className="px-3 py-2 bg-gray-50 border border-gray-300 rounded-lg text-sm font-medium text-gray-900">
                        {formatCurrency(calculateTotal())}
                    </div>
                </div>

                {/* Image Upload */}
                <div className="md:col-span-2">
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                        Item Image
                    </label>
                    
                    {item.image_path ? (
                        <div className="relative inline-block">
                            <LazyImage
                                src={item.image_path}
                                alt="Item preview"
                                className="w-32 h-32 object-cover rounded-lg border border-gray-300"
                            />
                            <button
                                type="button"
                                onClick={handleRemoveImage}
                                className="absolute -top-2 -right-2 p-1 bg-red-500 text-white rounded-full hover:bg-red-600 transition-colors"
                            >
                                <X className="w-4 h-4" />
                            </button>
                        </div>
                    ) : (
                        <div>
                            <input
                                ref={fileInputRef}
                                type="file"
                                accept="image/*"
                                onChange={handleImageUpload}
                                className="hidden"
                                id={`image-upload-${index}`}
                            />
                            <label
                                htmlFor={`image-upload-${index}`}
                                className="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 cursor-pointer transition-colors"
                            >
                                <Upload className="w-4 h-4 mr-2" />
                                Upload Image
                            </label>
                            <p className="mt-1 text-xs text-gray-500">
                                Max 2MB, JPG, PNG, or GIF
                            </p>
                        </div>
                    )}
                </div>
            </div>
        </motion.div>
    );
};
