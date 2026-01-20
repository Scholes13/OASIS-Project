import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { PurchaseRequestForm } from '../../../components/purchasing/PurchaseRequestForm';
import { PRCreateProps, PRFormData } from '../../../types/purchasing';
import { PageProps } from '../../../types';
import { ArrowLeft, Home, ChevronRight } from 'lucide-react';
import { toast } from 'sonner';

interface CreatePageProps extends PageProps, PRCreateProps {}

export default function Create({
    categories,
    departments,
    businessUnits,
    availableApprovers,
    errors,
    currentBusinessUnit,
}: CreatePageProps) {
    const [isSubmitting, setIsSubmitting] = useState(false);

    // Handle form submission
    const handleSubmit = (data: PRFormData, isDraft: boolean) => {
        // Prevent double submission
        if (isSubmitting) {
            return;
        }
        
        setIsSubmitting(true);
        // Create FormData for file upload
        const formData = new FormData();
        
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
        router.post(route('purchase-requests.store'), formData, {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Purchase request submitted for approval successfully');
            },
            onError: (errors) => {
                console.error('Form submission errors:', errors);
                
                // Show first error as toast
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

    return (
        <>
            <Head title="Create Purchase Request" />

            <div className="py-6 px-4 sm:px-6 lg:px-8">
                {/* Header */}
                <div className="mb-6">
                    <div className="flex items-center justify-between">
                        <div>
                            <h1 className="text-2xl font-bold text-gray-900">
                                Create Purchase Request
                            </h1>
                            <p className="text-sm text-gray-600 mt-1">
                                Create a new purchase request for {currentBusinessUnit?.name || 'your business unit'}
                            </p>
                        </div>
                        <div className="flex items-center space-x-3">
                            <Link
                                href={route('purchase-requests.index')}
                                className="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200"
                            >
                                <ArrowLeft className="w-5 h-5 mr-2" />
                                Back to List
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
                                    <span className="ml-2 text-sm font-medium text-gray-500">
                                        Create New
                                    </span>
                                </div>
                            </li>
                        </ol>
                    </nav>
                </div>

                {/* Form */}
                <div className="max-w-7xl">
                    <PurchaseRequestForm
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
