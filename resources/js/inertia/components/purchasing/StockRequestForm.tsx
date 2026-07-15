import React, { useState, useCallback, useEffect } from 'react';
import { useForm } from '@inertiajs/react';
import { Plus, Upload, X, Trash2 } from 'lucide-react';
import { Button } from '../ui/button';
import { Input } from '../ui/input';
import { Department, BusinessUnit, Approver } from '../../types/purchasing';
import { OfflineApprovalUpload } from './OfflineApprovalUpload';
import { motion } from 'framer-motion';
import { toast } from 'sonner';
import { StockRequestSubmitActions } from './StockRequestSubmitActions';
import type { StockRequestFormData, StockRequestItemFormData } from '../../types/stockRequestForm';

export type { StockRequestFormData as STFormData } from '../../types/stockRequestForm';

interface StockRequestFormProps {
    departments: Department[];
    businessUnits: BusinessUnit[];
    availableApprovers: Approver[];
    initialData?: Partial<StockRequestFormData>;
    requiresSupervisorApproval?: boolean;
    routesDirectlyToPurchasing?: boolean;
    isEdit?: boolean;
    errors?: Record<string, string>;
    processing?: boolean;
    onSubmit: (data: StockRequestFormData) => void;
}

const localDateString = (): string => {
    const now = new Date();
    const localTime = new Date(now.getTime() - now.getTimezoneOffset() * 60_000);

    return localTime.toISOString().split('T')[0];
};

export const StockRequestForm: React.FC<StockRequestFormProps> = ({
    departments,
    businessUnits,
    initialData,
    requiresSupervisorApproval = false,
    routesDirectlyToPurchasing = false,
    isEdit = false,
    errors: serverErrors = {},
    processing = false,
    onSubmit,
}) => {
    const [items, setItems] = useState<StockRequestItemFormData[]>(
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

    const { data, setData } = useForm<StockRequestFormData>({
        business_unit_id: initialData?.business_unit_id || '',
        department_id: initialData?.department_id || '',
        purpose: initialData?.purpose || '',
        request_date: initialData?.request_date || localDateString(),
        expected_date: initialData?.expected_date || '',
        items: items,
        approval_notes: initialData?.approval_notes || '',
    });

    useEffect(() => {
        setData('items', items);
    }, [items]);

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

    const handleRemoveItem = useCallback((index: number) => {
        if (items.length > 1) {
            setItems((prev) => prev.filter((_, i) => i !== index));
        }
    }, [items.length]);

    const handleUpdateItem = useCallback((index: number, field: keyof StockRequestItemFormData, value: any) => {
        setItems((prev) => {
            const newItems = [...prev];
            newItems[index] = { ...newItems[index], [field]: value };
            return newItems;
        });
    }, []);

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

    const handleRemoveItemImage = (index: number) => {
        handleUpdateItem(index, 'image_file', undefined);
        handleUpdateItem(index, 'image_path', undefined);
    };

    const handleSubmit = () => {
        if (items.length === 0 || items.every((item) => !item.item_name)) {
            toast.error('Please add at least one item');
            return;
        }

        if (data.expected_date && data.expected_date < data.request_date) {
            toast.error('Expected date must be on or after the request date.');
            return;
        }

        const formData: StockRequestFormData = {
            ...data,
            items,
            offline_approval_document: offlineDocument || undefined,
        };

        onSubmit(formData);
    };

    return (
        <div className="space-y-6">
            <div className="bg-white rounded-xl border border-gray-100 overflow-hidden">
                <div className="px-5 py-4 border-b border-gray-100">
                    <h3 className="text-base font-semibold text-gray-900">Basic Information</h3>
                </div>
                <div className="p-6 space-y-4">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
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
                            {serverErrors.business_unit_id && (
                                <p className="mt-1 text-sm text-red-600">{serverErrors.business_unit_id}</p>
                            )}
                        </div>

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
                            {serverErrors.department_id && (
                                <p className="mt-1 text-sm text-red-600">{serverErrors.department_id}</p>
                            )}
                        </div>

                        <div className="md:col-span-2 relative">
                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                Expected Delivery Date
                            </label>
                            <Input
                                type="date"
                                value={data.expected_date || ''}
                                min={data.request_date}
                                onChange={(e) => setData('expected_date', e.target.value)}
                                className={`w-full cursor-pointer ${serverErrors.expected_date ? 'border-red-500' : ''}`}
                            />
                            {serverErrors.expected_date && (
                                <p className="mt-1 text-sm text-red-600">{serverErrors.expected_date}</p>
                            )}
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
                                serverErrors.purpose ? 'border-red-500' : 'border-gray-300'
                            }`}
                        />
                        {serverErrors.purpose && (
                            <p className="mt-1 text-sm text-red-600">{serverErrors.purpose}</p>
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
                {routesDirectlyToPurchasing
                    ? 'This request will skip department approval and Stock Review, then go directly to Purchasing Admin.'
                    : requiresSupervisorApproval
                    ? 'This request will go to HOD / Leader approval before Stock Review.'
                    : 'This request will go directly to Stock Review.'}
            </div>

            <StockRequestSubmitActions processing={processing} onSubmit={handleSubmit} />
        </div>
    );
};
