import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { PurchaseRequestForm } from '../../../components/purchasing/PurchaseRequestForm';
import { PRFormData, PurchaseRequest, PRCategory, Approver, Department, BusinessUnit } from '../../../types/purchasing';
import { PageProps } from '../../../types';
import { ArrowLeft, Home, ChevronRight } from 'lucide-react';
import { toast } from 'sonner';

interface FormPageProps extends PageProps {
    mode: 'edit';
    purchaseRequest: PurchaseRequest & {
        approval_workflow?: Array<{ approver_id: number; task_type: string }>;
    };
    categories: PRCategory[];
    departments: Department[];
    businessUnits: BusinessUnit[];
    availableApprovers: Approver[];
    currentBusinessUnitId: number;
    currentDepartmentId: number;
}

export default function Form({
    mode,
    purchaseRequest,
    categories,
    departments,
    businessUnits,
    availableApprovers,
    currentBusinessUnit,
    currentBusinessUnitId,
    currentDepartmentId,
}: FormPageProps) {
    const [isSubmitting, setIsSubmitting] = useState(false);

    // Handle form submission for edit
    const handleSubmit = (data: PRFormData, isDraft: boolean) => {
        if (isSubmitting) {
            return;
        }

        setIsSubmitting(true);

        const formData = new FormData();

        // Add method override for PUT
        formData.append('_method', 'PUT');

        // Append basic fields
        formData.append('business_unit_id', data.business_unit_id);
        formData.append('department_id', data.department_id);
        formData.append('category_id', data.category_id);
        formData.append('used_for', data.used_for);
        formData.append('date_of_request', data.request_date);
        formData.append('currency', data.currency);

        if (data.expected_date) {
            formData.append('expected_date', data.expected_date);
        }

        if (data.approval_notes) {
            formData.append('approval_notes', data.approval_notes);
        }

        // Append approval workflow
        if (data.approval_workflow && data.approval_workflow.length > 0) {
            data.approval_workflow.forEach((step, index) => {
                formData.append(`approval_workflow[${index}][approver_id]`, String(step.approver_id));
                formData.append(`approval_workflow[${index}][task_type]`, step.task_type);
            });
        }

        // Append items
        data.items.forEach((item, index) => {
            formData.append(`items[${index}][item_name]`, item.item_name);
            formData.append(`items[${index}][quantity]`, String(item.quantity));
            formData.append(`items[${index}][unit]`, item.unit);
            formData.append(`items[${index}][unit_price]`, String(item.unit_price));
            formData.append(`items[${index}][currency]`, item.currency);

            if (item.brand_name) {
                formData.append(`items[${index}][brand_name]`, item.brand_name);
            }
            if (item.item_description) {
                formData.append(`items[${index}][item_description]`, item.item_description);
            }
            if (item.supplier_name) {
                formData.append(`items[${index}][supplier_name]`, item.supplier_name);
            }
            if (item.expense_department_id) {
                formData.append(`items[${index}][expense_department_id]`, String(item.expense_department_id));
            }
            if (item.image_file) {
                formData.append(`items[${index}][image]`, item.image_file);
            }
        });

        // Append supporting document
        if (data.supporting_document) {
            formData.append('supporting_document', data.supporting_document);
        }

        // Submit with Inertia
        router.post(route('purchase-requests.update', { purchaseRequest: purchaseRequest.id }), formData, {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Purchase request updated successfully');
            },
            onError: (errors) => {
                console.error('Form submission errors:', errors);

                const firstError = Object.values(errors)[0];
                if (typeof firstError === 'string') {
                    toast.error(firstError);
                } else {
                    toast.error('Please check the form for errors');
                }
            },
            onFinish: () => {
                setIsSubmitting(false);
            },
        });
    };

    // Transform purchaseRequest to initial form data
    const initialData: Partial<PRFormData> = {
        business_unit_id: String(purchaseRequest.business_unit_id),
        department_id: String(purchaseRequest.department_id),
        category_id: String(purchaseRequest.category_id),
        used_for: purchaseRequest.used_for,
        request_date: purchaseRequest.date_of_request,
        expected_date: purchaseRequest.expected_date || '',
        currency: purchaseRequest.currency,
        approval_notes: purchaseRequest.approval_notes || '',
        items: purchaseRequest.items?.map(item => ({
            item_name: item.item_name,
            quantity: item.quantity,
            unit: item.unit,
            unit_price: item.unit_price,
            currency: item.currency || 'IDR',
            brand_name: item.brand_name || '',
            item_description: item.item_description || '',
            supplier_name: item.supplier_name || '',
            expense_department_id: item.expense_department_id || undefined,
        })) || [],
        approval_workflow: purchaseRequest.approval_workflow || [],
    };

    return (
        <>
            <Head title={`Edit Purchase Request - ${purchaseRequest.pr_number}`} />

            <div className="w-full px-6 py-6 lg:px-8">
                {/* Header */}
                <div className="mb-6">
                    <div className="flex items-center justify-between">
                        <div>
                            <h1 className="text-2xl font-bold text-gray-900">
                                Edit Purchase Request
                            </h1>
                            <p className="text-sm text-gray-600 mt-1">
                                Editing {purchaseRequest.pr_number} for {currentBusinessUnit?.name || 'your business unit'}
                            </p>
                        </div>
                        <div className="flex items-center space-x-3">
                            <Link
                                href={route('purchase-requests.show', { purchaseRequest: purchaseRequest.id })}
                                className="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors duration-200"
                            >
                                <ArrowLeft className="w-5 h-5 mr-2" />
                                Back to Details
                            </Link>
                        </div>
                    </div>

                    {/* Breadcrumbs */}
                    <nav className="flex mt-4" aria-label="Breadcrumb">
                        <ol className="flex items-center space-x-2">
                            <li className="flex">
                                <div className="flex items-center">
                                    <Link
                                        href={route('dashboard')}
                                        className="text-gray-400 hover:text-gray-500"
                                    >
                                        <Home className="flex-shrink-0 h-5 w-5" />
                                        <span className="sr-only">Dashboard</span>
                                    </Link>
                                </div>
                            </li>
                            <li className="flex">
                                <div className="flex items-center">
                                    <ChevronRight className="flex-shrink-0 h-5 w-5 text-gray-300" />
                                    <Link
                                        href={route('purchase-requests.index')}
                                        className="ml-2 text-sm font-medium text-gray-500 hover:text-gray-700"
                                    >
                                        Purchase Requests
                                    </Link>
                                </div>
                            </li>
                            <li className="flex">
                                <div className="flex items-center">
                                    <ChevronRight className="flex-shrink-0 h-5 w-5 text-gray-300" />
                                    <Link
                                        href={route('purchase-requests.show', { purchaseRequest: purchaseRequest.id })}
                                        className="ml-2 text-sm font-medium text-gray-500 hover:text-gray-700"
                                    >
                                        {purchaseRequest.pr_number}
                                    </Link>
                                </div>
                            </li>
                            <li className="flex">
                                <div className="flex items-center">
                                    <ChevronRight className="flex-shrink-0 h-5 w-5 text-gray-300" />
                                    <span className="ml-2 text-sm font-medium text-gray-500">
                                        Edit
                                    </span>
                                </div>
                            </li>
                        </ol>
                    </nav>
                </div>

                {/* Form */}
                <div className="max-w-7xl">
                    <PurchaseRequestForm
                        initialData={initialData}
                        categories={categories}
                        departments={departments}
                        businessUnits={businessUnits}
                        availableApprovers={availableApprovers}
                        onSubmit={handleSubmit}
                    />
                </div>
            </div>
        </>
    );
}
