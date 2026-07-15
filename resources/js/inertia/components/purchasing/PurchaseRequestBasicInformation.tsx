import type { ChangeEvent } from 'react';
import { Upload, X } from 'lucide-react';
import { Input } from '../ui/input';
import type { BusinessUnit, Department, PRCategory, PRFormData } from '../../types/purchasing';

interface PurchaseRequestBasicInformationProps {
    data: PRFormData;
    errors: Partial<Record<keyof PRFormData, string>>;
    categories: PRCategory[];
    departments: Department[];
    businessUnits: BusinessUnit[];
    supportingDocumentPreview: string | null;
    onBusinessUnitChange: (value: string) => void;
    onDepartmentChange: (value: string) => void;
    onCategoryChange: (value: string) => void;
    onCurrencyChange: (value: string) => void;
    onExpectedDateChange: (value: string) => void;
    onUsedForChange: (value: string) => void;
    onSupportingDocumentUpload: (event: ChangeEvent<HTMLInputElement>) => void;
    onRemoveSupportingDocument: () => void;
}

export function PurchaseRequestBasicInformation({
    data,
    errors,
    categories,
    departments,
    businessUnits,
    supportingDocumentPreview,
    onBusinessUnitChange,
    onDepartmentChange,
    onCategoryChange,
    onCurrencyChange,
    onExpectedDateChange,
    onUsedForChange,
    onSupportingDocumentUpload,
    onRemoveSupportingDocument,
}: PurchaseRequestBasicInformationProps) {
    return (
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
                            onChange={(event) => onBusinessUnitChange(event.target.value)}
                            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary text-sm bg-gray-100 cursor-not-allowed"
                            disabled={true}
                        >
                            <option value="">Select Business Unit</option>
                            {businessUnits.map((businessUnit) => (
                                <option key={businessUnit.id} value={businessUnit.id}>
                                    {businessUnit.name}
                                </option>
                            ))}
                        </select>
                        {errors.business_unit_id && (
                            <p className="mt-1 text-sm text-red-600">{errors.business_unit_id}</p>
                        )}
                    </div>

                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">
                            Department <span className="text-red-500">*</span>
                        </label>
                        <select
                            value={data.department_id}
                            onChange={(event) => onDepartmentChange(event.target.value)}
                            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary text-sm bg-gray-100 cursor-not-allowed"
                            disabled={true}
                        >
                            <option value="">Select Department</option>
                            {departments.map((department) => (
                                <option key={department.id} value={department.id}>
                                    {department.name}
                                </option>
                            ))}
                        </select>
                        {errors.department_id && (
                            <p className="mt-1 text-sm text-red-600">{errors.department_id}</p>
                        )}
                    </div>

                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Category</label>
                        <select
                            value={data.category_id}
                            onChange={(event) => onCategoryChange(event.target.value)}
                            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary text-sm"
                        >
                            <option value="">Select Category</option>
                            {categories.map((category) => (
                                <option key={category.id} value={category.id}>
                                    {category.name}
                                </option>
                            ))}
                        </select>
                    </div>

                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">
                            Currency <span className="text-red-500">*</span>
                        </label>
                        <select
                            value={data.currency}
                            onChange={(event) => onCurrencyChange(event.target.value)}
                            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary text-sm"
                        >
                            <option value="IDR">IDR</option>
                            <option value="USD">USD</option>
                            <option value="EUR">EUR</option>
                        </select>
                    </div>

                    <div className="md:col-span-2 relative">
                        <label className="block text-sm font-medium text-gray-700 mb-1">Expected Delivery Date</label>
                        <Input
                            type="date"
                            value={data.expected_date || ''}
                            onChange={(event) => onExpectedDateChange(event.target.value)}
                            className="w-full cursor-pointer"
                        />
                    </div>
                </div>

                <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                        Purpose / Used For <span className="text-red-500">*</span>
                    </label>
                    <textarea
                        value={data.used_for}
                        onChange={(event) => onUsedForChange(event.target.value)}
                        placeholder="Describe the purpose of this purchase request (minimum 10 characters)"
                        rows={3}
                        className={`w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-primary text-sm ${errors.used_for ? 'border-red-500' : 'border-gray-300'}`}
                    />
                    {errors.used_for && (
                        <p className="mt-1 text-sm text-red-600">{errors.used_for}</p>
                    )}
                    <p className="mt-1 text-xs text-gray-500">
                        {data.used_for.length} / 1000 characters (minimum 10)
                    </p>
                </div>

                <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Supporting Document</label>
                    {supportingDocumentPreview ? (
                        <div className="flex items-center justify-between p-3 bg-gray-50 border border-gray-300 rounded-lg">
                            <span className="text-sm text-gray-700">{supportingDocumentPreview}</span>
                            <button
                                type="button"
                                onClick={onRemoveSupportingDocument}
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
                                onChange={onSupportingDocumentUpload}
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
    );
}
