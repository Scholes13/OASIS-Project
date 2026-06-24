import React, { useState, useCallback, useEffect } from 'react';
import { useForm } from '@inertiajs/react';
import { Plus, Send, Loader2, Upload, X, Trash2 } from 'lucide-react';
import { Button } from '../ui/button';
import { Input } from '../ui/input';
import { Department, BusinessUnit, Approver } from '../../types/purchasing';
import { OfflineApprovalUpload } from './OfflineApprovalUpload';
import { motion } from 'framer-motion';
import { toast } from 'sonner';

// Stock Request specific types
export interface STItemFormData {
    id?: number;
    item_name: string;
    item_description?: string;
    quantity: number;
    unit: string;
    image_path?: string;
    image_file?: File;
}

export interface STFormData {
    business_unit_id: string;
    department_id: string;
    purpose: string;
    request_date: string;
    expected_date?: string;
    items: STItemFormData[];
    offline_approval_document?: File;
    approval_notes?: string;
}

interface StockRequestFormProps {
    departments: Department[];
    businessUnits: BusinessUnit[];
    availableApprovers: Approver[];
    initialData?: Partial<STFormData>;
    requiresSupervisorApproval?: boolean;
    isEdit?: boolean;
    onSubmit: (data: STFormData) => void;
}

export const StockRequestForm: React.FC<StockRequestFormProps> = ({
    departments,
    businessUnits,
    initialData,
    requiresSupervisorApproval = false,
    isEdit = false,
    onSubmit,
}) => {
    const [items, setItems] = useState<STItemFormData[]>(
        initialData?.items || [
            {
                item_name: '',
                item_description: '',
                quantity: 1,
                unit: 'pcs',
            },
        ]
    );

    const [offlineDocument, setOfflineDocument] = useState<File | null>(null);

    const { data, setData, errors, processing } = useForm<STFormData>({
        business_unit_id: initialData?.business_unit_id || '',
        department_id: initialData?.department_id || '',
        purpose: initialData?.purpose || '',
        request_date: initialData?.request_date || new Date().toISOString().split('T')[0],
        expected_date: initialData?.expected_date || '',
        items: items,
        approval_notes: initialData?.approval_notes || '',
    });

    // Update form data when items change
    useEffect(() => {
        setData('items', items);
    }, [items]);

    // Add new item
    const handleAddItem = useCallback(() => {
        setItems((prev) => [
            ...prev,
            {
                item_name: '',
                item_description: '',
                quantity: 1,
                unit: 'pcs',
            },
        ]);
    }, []);

    // Remove item
    const handleRemoveItem = useCallback((index: number) => {
        if (items.length > 1) {
            setItems((prev) => prev.filter((_, i) => i !== index));
        }
    }, [items.length]);

    // Update item field
    const handleUpdateItem = useCallback((index: number, field: keyof STItemFormData, value: any) => {
        setItems((prev) => {
            const newItems = [...prev];
            newItems[index] = { ...newItems[index], [field]: value };
            return newItems;
        });
    }, []);

    // Handle item image upload
    const handleItemImageUpload = (index: number, e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (file) {
            if (file.size > 2 * 1024 * 1024) {
                toast.error('Image size must be less than 2MB');
                return;
            }
            handleUpdateItem(index, 'image_file', file);
        }
    };

    // Remove item image
    const handleRemoveItemImage = (index: number) => {
        handleUpdateItem(index, 'image_file', undefined);
        handleUpdateItem(index, 'image_path', undefined);
    };

    // Handle form submission
    const handleSubmit = () => {
        // Validate items
        if (items.length === 0 || items.every((item) => !item.item_name)) {
            toast.error('Please add at least one item');
            return;
        }

        const formData: STFormData = {
            ...data,
            items,
            offline_approval_document: offlineDocument || undefined,
        };

        onSubmit(formData);
    };

    return (
        <div className="space-y-6">
            {/* Basic Information */}
            <div className="bg-white rounded-xl border border-gray-100 overflow-hidden">
                <div className="px-5 py-4 border-b border-gray-100">
                    <h3 className="text-base font-semibold text-gray-900">Basic Information</h3>
                </div>
                <div className="p-6 space-y-4">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        {/* Business Unit */}
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                Business Unit <span className="text-red-500">*</span>
                            </label>
                            <select
                                value={data.business_unit_id}
                                onChange={(e) => setData('business_unit_id', e.target.value)}
                                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary text-sm bg-gray-100 cursor-not-allowed"
                                disabled={true}
                            >
                                <option value="">Select Business Unit</option>
                                {businessUnits.map((bu) => (
                                    <option key={bu.id} value={bu.id}>
                                        {bu.name}
                                    </option>
                                ))}
                            </select>
                            {errors.business_unit_id && (
                                <p className="mt-1 text-sm text-red-600">{errors.business_unit_id}</p>
                            )}
                        </div>

                        {/* Department */}
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                Department <span className="text-red-500">*</span>
                            </label>
                            <select
                                value={data.department_id}
                                onChange={(e) => setData('department_id', e.target.value)}
                                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary text-sm bg-gray-100 cursor-not-allowed"
                                disabled={true}
                            >
                                <option value="">Select Department</option>
                                {departments.map((dept) => (
                                    <option key={dept.id} value={dept.id}>
                                        {dept.name}
                                    </option>
                                ))}
                            </select>
                            {errors.department_id && (
                                <p className="mt-1 text-sm text-red-600">{errors.department_id}</p>
                            )}
                        </div>

                        {/* Expected Date */}
                        <div className="md:col-span-2 relative">
                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                Expected Delivery Date
                            </label>
                            <Input
                                type="date"
                                value={data.expected_date || ''}
                                onChange={(e) => setData('expected_date', e.target.value)}
                                className="w-full cursor-pointer"
                            />
                        </div>
                    </div>

                    {/* Purpose */}
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">
                            Purpose <span className="text-red-500">*</span>
                        </label>
                        <textarea
                            value={data.purpose}
                            onChange={(e) => setData('purpose', e.target.value)}
                            placeholder="Describe the purpose of this stock request (minimum 10 characters)"
                            rows={3}
                            className={`w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-primary text-sm ${
                                errors.purpose ? 'border-red-500' : 'border-gray-300'
                            }`}
                        />
                        {errors.purpose && (
                            <p className="mt-1 text-sm text-red-600">{errors.purpose}</p>
                        )}
                        <p className="mt-1 text-xs text-gray-500">
                            {data.purpose.length} / 1000 characters (minimum 10)
                        </p>
                    </div>

                    {/* Offline Approval Document */}
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">
                            Offline Approval Document (Optional)
                        </label>
                        <p className="text-xs text-gray-500 mb-2">
                            Upload if you have pre-approved document for faster processing
                        </p>

                        <OfflineApprovalUpload
                            value={offlineDocument}
                            onChange={setOfflineDocument}
                            notes={data.approval_notes || ''}
                            onNotesChange={(notes) => setData('approval_notes', notes)}
                            isSubmitting={processing}
                        />
                    </div>
                </div>
            </div>

            {/* Items Section */}
            <div className="bg-white rounded-xl border border-gray-100 overflow-hidden">
                <div className="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h3 className="text-base font-semibold text-gray-900">Items</h3>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        onClick={handleAddItem}
                    >
                        <Plus className="w-4 h-4 mr-2" />
                        Add Item
                    </Button>
                </div>
                <div className="p-6 space-y-4">
                    {items.map((item, index) => (
                        <motion.div
                            key={index}
                            initial={{ opacity: 0, y: -10 }}
                            animate={{ opacity: 1, y: 0 }}
                            className="p-4 border border-gray-200 rounded-lg space-y-4"
                        >
                            <div className="flex items-center justify-between">
                                <span className="text-sm font-medium text-gray-700">Item #{index + 1}</span>
                                {items.length > 1 && (
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => handleRemoveItem(index)}
                                        className="text-red-600 hover:text-red-700 hover:bg-red-50"
                                    >
                                        <Trash2 className="w-4 h-4" />
                                    </Button>
                                )}
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                {/* Item Name */}
                                <div className="md:col-span-2">
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Item Name <span className="text-red-500">*</span>
                                    </label>
                                    <Input
                                        type="text"
                                        value={item.item_name}
                                        onChange={(e) => handleUpdateItem(index, 'item_name', e.target.value)}
                                        placeholder="Enter item name"
                                    />
                                </div>

                                {/* Quantity & Unit */}
                                <div className="grid grid-cols-2 gap-2">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Qty <span className="text-red-500">*</span>
                                        </label>
                                        <Input
                                            type="number"
                                            min="1"
                                            value={item.quantity}
                                            onChange={(e) => handleUpdateItem(index, 'quantity', parseInt(e.target.value) || 1)}
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Unit <span className="text-red-500">*</span>
                                        </label>
                                        <select
                                            value={item.unit}
                                            onChange={(e) => handleUpdateItem(index, 'unit', e.target.value)}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary text-sm"
                                        >
                                            <option value="pcs">pcs</option>
                                            <option value="box">box</option>
                                            <option value="pack">pack</option>
                                            <option value="set">set</option>
                                            <option value="unit">unit</option>
                                            <option value="kg">kg</option>
                                            <option value="liter">liter</option>
                                            <option value="meter">meter</option>
                                            <option value="roll">roll</option>
                                            <option value="rim">rim</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            {/* Description */}
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Description / Specifications
                                </label>
                                <textarea
                                    value={item.item_description || ''}
                                    onChange={(e) => handleUpdateItem(index, 'item_description', e.target.value)}
                                    placeholder="Enter item description or specifications"
                                    rows={2}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary text-sm"
                                />
                            </div>

                            {/* Item Image */}
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Item Image (Optional)
                                </label>
                                {item.image_file || item.image_path ? (
                                    <div className="flex items-center justify-between p-3 bg-gray-50 border border-gray-300 rounded-lg">
                                        <span className="text-sm text-gray-700">
                                            {item.image_file?.name || item.image_path}
                                        </span>
                                        <button
                                            type="button"
                                            onClick={() => handleRemoveItemImage(index)}
                                            className="p-1 text-red-600 hover:text-red-700 hover:bg-red-50 rounded transition-colors"
                                        >
                                            <X className="w-4 h-4" />
                                        </button>
                                    </div>
                                ) : (
                                    <div>
                                        <input
                                            type="file"
                                            accept=".jpg,.jpeg,.png"
                                            onChange={(e) => handleItemImageUpload(index, e)}
                                            className="hidden"
                                            id={`item-image-${index}`}
                                        />
                                        <label
                                            htmlFor={`item-image-${index}`}
                                            className="inline-flex items-center px-3 py-1.5 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 cursor-pointer transition-colors"
                                        >
                                            <Upload className="w-4 h-4 mr-2" />
                                            Upload Image
                                        </label>
                                    </div>
                                )}
                            </div>
                        </motion.div>
                    ))}
                </div>
            </div>

            <div className="rounded-lg border border-blue-100 bg-blue-50 px-4 py-3 text-sm text-blue-800">
                {requiresSupervisorApproval
                    ? 'This request will go to HOD / Leader approval before Stock Review.'
                    : 'This request will go directly to Stock Review.'}
            </div>

            {/* Form Actions */}
            <div className="flex items-center justify-end gap-3">
                <Button
                    type="button"
                    onClick={handleSubmit}
                    disabled={processing}
                    className="disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    {processing ? (
                        <>
                            <Loader2 className="w-4 h-4 mr-2 animate-spin" />
                            Submitting...
                        </>
                    ) : (
                        <>
                            <Send className="w-4 h-4 mr-2" />
                            Submit for Stock Review
                        </>
                    )}
                </Button>
            </div>
        </div>
    );
};
