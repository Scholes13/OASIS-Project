import React, { useState, useCallback, useEffect } from 'react';
import { useForm } from '@inertiajs/react';
import { Plus, Save, Send, Loader2, Upload, X } from 'lucide-react';
import { Button } from '../ui/button';
import { Input } from '../ui/input';
import { PRItemRow } from './PRItemRow';
import { ApprovalWorkflowBuilder } from './ApprovalWorkflowBuilder';
import { PRFormData, PRItemFormData, PRCategory, Department, BusinessUnit, Approver, CustomApprovalStep } from '../../types/purchasing';
import { toast } from 'sonner';

interface PurchaseRequestFormProps {
    categories: PRCategory[];
    departments: Department[];
    businessUnits: BusinessUnit[];
    availableApprovers: Approver[];
    initialData?: Partial<PRFormData>;
    isEdit?: boolean;
    onSubmit: (data: PRFormData, isDraft: boolean) => void;
}

export const PurchaseRequestForm: React.FC<PurchaseRequestFormProps> = ({
    categories,
    departments,
    businessUnits,
    availableApprovers,
    initialData,
    isEdit = false,
    onSubmit,
}) => {
    const [items, setItems] = useState<PRItemFormData[]>(
        initialData?.items || [
            {
                item_name: '',
                quantity: 1,
                unit: 'pcs',
                unit_price: 0,
                currency: 'IDR',
                expense_department_id: initialData?.department_id ? Number(initialData.department_id) : undefined,
            },
        ]
    );

    const [customApprovalList, setCustomApprovalList] = useState<CustomApprovalStep[]>([
        { approver_id: '', task_type: 'approval' },
    ]);

    const [supportingDocument, setSupportingDocument] = useState<File | null>(null);
    const [supportingDocumentPreview, setSupportingDocumentPreview] = useState<string | null>(null);

    const { data, setData, errors, processing } = useForm<PRFormData>({
        business_unit_id: initialData?.business_unit_id || '',
        department_id: initialData?.department_id || '',
        category_id: initialData?.category_id || '',
        used_for: initialData?.used_for || '',
        request_date: initialData?.request_date || new Date().toISOString().split('T')[0],
        expected_date: initialData?.expected_date || '',
        currency: initialData?.currency || 'IDR',
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
                quantity: 1,
                unit: 'pcs',
                unit_price: 0,
                currency: data.currency,
                expense_department_id: data.department_id ? Number(data.department_id) : undefined,
            },
        ]);
    }, [data.currency, data.department_id]);

    // Remove item
    const handleRemoveItem = useCallback((index: number) => {
        if (items.length > 1) {
            setItems((prev) => prev.filter((_, i) => i !== index));
        }
    }, [items.length]);

    // Update item field
    const handleUpdateItem = useCallback((index: number, field: keyof PRItemFormData, value: any) => {
        setItems((prev) => {
            const newItems = [...prev];
            newItems[index] = { ...newItems[index], [field]: value };
            return newItems;
        });
    }, []);

    // Calculate grand total
    const calculateGrandTotal = useCallback(() => {
        return items.reduce((total, item) => {
            return total + (item.quantity * item.unit_price);
        }, 0);
    }, [items]);

    // Format currency
    const formatCurrency = (value: number) => {
        return new Intl.NumberFormat('id-ID').format(value);
    };

    // Handle currency change
    const handleCurrencyChange = (newCurrency: string) => {
        setData('currency', newCurrency);
        setItems((prev) =>
            prev.map((item) => ({ ...item, currency: newCurrency }))
        );
    };

    // Handle supporting document upload
    const handleSupportingDocumentUpload = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (file) {
            // Validate file size (max 5MB)
            if (file.size > 5 * 1024 * 1024) {
                toast.error('File size must be less than 5MB');
                return;
            }

            setSupportingDocument(file);
            setSupportingDocumentPreview(file.name);
        }
    };

    // Remove supporting document
    const handleRemoveSupportingDocument = () => {
        setSupportingDocument(null);
        setSupportingDocumentPreview(null);
    };

    // Add approval step
    const handleAddApprovalStep = () => {
        setCustomApprovalList((prev) => [
            ...prev,
            { approver_id: '', task_type: 'approval' },
        ]);
    };

    // Remove approval step
    const handleRemoveApprovalStep = (index: number) => {
        if (customApprovalList.length > 1) {
            setCustomApprovalList((prev) => prev.filter((_, i) => i !== index));
        }
    };

    // Update approval step
    const handleUpdateApprovalStep = (index: number, field: keyof CustomApprovalStep, value: any) => {
        setCustomApprovalList((prev) => {
            const newList = [...prev];
            newList[index] = { ...newList[index], [field]: value };
            return newList;
        });
    };

    // Handle form submission
    const handleSubmit = (isDraft: boolean) => {
        // Validate items
        if (items.length === 0 || items.every((item) => !item.item_name)) {
            toast.error('Please add at least one item');
            return;
        }

        // Validate approval workflow if not draft
        if (!isDraft) {
            const validApprovals = customApprovalList.filter((step) => step.approver_id);
            if (validApprovals.length === 0) {
                toast.error('Please select at least one approver');
                return;
            }

            // Check for duplicate approvers
            const approverIds = validApprovals.map((step) => step.approver_id);
            if (new Set(approverIds).size !== approverIds.length) {
                toast.error('Cannot select the same approver for multiple steps');
                return;
            }
        }

        const formData: PRFormData = {
            ...data,
            items,
            supporting_document: supportingDocument || undefined,
            approval_workflow: customApprovalList.filter((step) => step.approver_id),
        };

        onSubmit(formData, isDraft);
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

                        {/* Category */}
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                Category
                            </label>
                            <select
                                value={data.category_id}
                                onChange={(e) => setData('category_id', e.target.value)}
                                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary text-sm"
                            >
                                <option value="">Select Category</option>
                                {categories.map((cat) => (
                                    <option key={cat.id} value={cat.id}>
                                        {cat.name}
                                    </option>
                                ))}
                            </select>
                        </div>

                        {/* Currency */}
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                Currency <span className="text-red-500">*</span>
                            </label>
                            <select
                                value={data.currency}
                                onChange={(e) => handleCurrencyChange(e.target.value)}
                                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary text-sm"
                            >
                                <option value="IDR">IDR</option>
                                <option value="USD">USD</option>
                                <option value="EUR">EUR</option>
                            </select>
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

                    {/* Purpose / Used For */}
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">
                            Purpose / Used For <span className="text-red-500">*</span>
                        </label>
                        <textarea
                            value={data.used_for}
                            onChange={(e) => setData('used_for', e.target.value)}
                            placeholder="Describe the purpose of this purchase request (minimum 10 characters)"
                            rows={3}
                            className={`w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-primary text-sm ${errors.used_for ? 'border-red-500' : 'border-gray-300'
                                }`}
                        />
                        {errors.used_for && (
                            <p className="mt-1 text-sm text-red-600">{errors.used_for}</p>
                        )}
                        <p className="mt-1 text-xs text-gray-500">
                            {data.used_for.length} / 1000 characters (minimum 10)
                        </p>
                    </div>

                    {/* Supporting Document */}
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">
                            Supporting Document
                        </label>

                        {supportingDocumentPreview ? (
                            <div className="flex items-center justify-between p-3 bg-gray-50 border border-gray-300 rounded-lg">
                                <span className="text-sm text-gray-700">{supportingDocumentPreview}</span>
                                <button
                                    type="button"
                                    onClick={handleRemoveSupportingDocument}
                                    className="p-1 text-red-600 hover:text-red-700 hover:bg-red-50 rounded transition-colors"
                                >
                                    <X className="w-4 h-4" />
                                </button>
                            </div>
                        ) : (
                            <div>
                                <input
                                    type="file"
                                    accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png"
                                    onChange={handleSupportingDocumentUpload}
                                    className="hidden"
                                    id="supporting-document-upload"
                                />
                                <label
                                    htmlFor="supporting-document-upload"
                                    className="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 cursor-pointer transition-colors"
                                >
                                    <Upload className="w-4 h-4 mr-2" />
                                    Upload Document
                                </label>
                                <p className="mt-1 text-xs text-gray-500">
                                    Max 5MB, PDF, DOC, DOCX, XLS, XLSX, JPG, PNG
                                </p>
                            </div>
                        )}
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
                <div className="overflow-x-auto">
                    <table className="w-full text-sm text-left">
                        <thead className="text-xs text-gray-500 uppercase bg-gray-50 border-b">
                            <tr>
                                <th className="px-4 py-3 w-10 text-center">No</th>
                                <th className="px-4 py-3 min-w-[150px]">Item Name</th>
                                <th className="px-4 py-3 min-w-[100px]">Brand</th>
                                <th className="px-4 py-3 min-w-[150px]">Description</th>
                                <th className="px-4 py-3 min-w-[100px]">Supplier</th>
                                <th className="px-4 py-3 w-16 text-center">Qty</th>
                                <th className="px-4 py-3 w-20 text-center">Unit</th>
                                <th className="px-4 py-3 w-28 text-right">Price</th>
                                <th className="px-4 py-3 w-28 text-right">Total</th>
                                <th className="px-4 py-3 w-16 text-center">Image</th>
                                <th className="px-4 py-3 w-12 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100">
                            {items.map((item, index) => (
                                <PRItemRow
                                    key={index}
                                    item={item}
                                    index={index}
                                    currency={data.currency}
                                    onUpdate={handleUpdateItem}
                                    onRemove={handleRemoveItem}
                                    canRemove={items.length > 1}
                                    errors={errors}
                                />
                            ))}
                        </tbody>
                    </table>
                </div>

                {/* Grand Total */}
                <div className="px-6 py-4 flex justify-between items-center bg-gray-50 border-t border-gray-100">
                    <div className="flex items-center">
                        <span className="font-bold text-gray-900">Total Amount:</span>
                    </div>
                    <div className="text-right">
                        <p className="text-xl font-bold text-blue-600">
                            {formatCurrency(calculateGrandTotal())}
                        </p>
                    </div>
                </div>
            </div>

            <ApprovalWorkflowBuilder
                approvers={customApprovalList}
                availableApprovers={availableApprovers}
                onAdd={handleAddApprovalStep}
                onRemove={handleRemoveApprovalStep}
                onUpdate={(index, field, value) => handleUpdateApprovalStep(index, field, value as 'approval' | 'paraf')}
                disabled={processing}
            />

            {/* Form Actions */}
            <div className="flex items-center justify-end gap-3">
                <Button
                    type="button"
                    variant="outline"
                    onClick={() => handleSubmit(true)}
                    disabled={processing}
                    className="disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    {processing ? (
                        <>
                            <Loader2 className="w-4 h-4 mr-2 animate-spin" />
                            Saving...
                        </>
                    ) : (
                        <>
                            <Save className="w-4 h-4 mr-2" />
                            Save as Draft
                        </>
                    )}
                </Button>
                <Button
                    type="button"
                    onClick={() => handleSubmit(false)}
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
                            Submit for Approval
                        </>
                    )}
                </Button>
            </div>
        </div>
    );
};
