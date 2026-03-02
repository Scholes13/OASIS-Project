import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { StockRequestForm, STFormData } from '../../../components/purchasing/StockRequestForm';
import { Department, BusinessUnit, Approver } from '../../../types/purchasing';
import { PageProps } from '../../../types';
import { ArrowLeft, Home, ChevronRight } from 'lucide-react';
import { toast } from 'sonner';

interface CreatePageProps extends PageProps {
    departments: Department[];
    businessUnits: BusinessUnit[];
    availableApprovers: Approver[];
    currentBusinessUnitId: number;
    currentDepartmentId: number;
}

export default function Create({
    departments,
    businessUnits,
    availableApprovers,
    errors,
    currentBusinessUnit,
    currentBusinessUnitId,
    currentDepartmentId,
}: CreatePageProps) {
    const [isSubmitting, setIsSubmitting] = useState(false);

    // Initial data with auto-fill
    const initialData: Partial<STFormData> = {
        business_unit_id: currentBusinessUnitId ? String(currentBusinessUnitId) : '',
        department_id: currentDepartmentId ? String(currentDepartmentId) : '',
    };

    // Handle form submission
    const handleSubmit = (data: STFormData) => {
        if (isSubmitting) {
            return;
        }

        setIsSubmitting(true);

        // Create FormData for file upload
        const formData = new FormData();

        // Append basic fields
        formData.append('business_unit_id', data.business_unit_id);
        formData.append('department_id', data.department_id);
        formData.append('purpose', data.purpose);
        formData.append('date_of_request', data.request_date);

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

            if (item.item_description) {
                formData.append(`items[${index}][item_description]`, item.item_description);
            }
            if (item.image_file) {
                formData.append(`items[${index}][image]`, item.image_file);
            }
        });

        // Append offline approval document
        if (data.offline_approval_document) {
            formData.append('offline_approval_document', data.offline_approval_document);
        }

        // Submit with Inertia
        router.post(route('stock-requests.store'), formData, {
            preserveScroll: true,
            onSuccess: () => {
                // Success handled by global inertia:success listener
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
            <Head title="Create Stock Request" />

            <div className="w-full px-6 py-6 lg:px-8">
                {/* Header */}
                <div className="mb-6">
                    <div className="flex items-center justify-between">
                        <div>
                            <h1 className="text-2xl font-bold text-gray-900">
                                Create Stock Request
                            </h1>
                            <p className="text-sm text-gray-600 mt-1">
                                Create a new stock request for {currentBusinessUnit?.name || 'your business unit'}
                            </p>
                        </div>
                        <div className="flex items-center space-x-3">
                            <Link
                                href={route('stock-requests.index')}
                                className="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors duration-200"
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
                                        href={route('stock-requests.index')}
                                        className="ml-2 text-sm font-medium text-gray-500 hover:text-gray-700"
                                    >
                                        Stock Requests
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
                <div className="w-full max-w-none block">
                    <StockRequestForm
                        departments={departments}
                        businessUnits={businessUnits}
                        availableApprovers={availableApprovers}
                        onSubmit={handleSubmit}
                        initialData={initialData}
                    />
                </div>
            </div>
        </>
    );
}
