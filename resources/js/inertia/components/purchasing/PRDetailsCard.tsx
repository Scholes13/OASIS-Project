import React from 'react';
import { PurchaseRequest } from '@/types/purchasing';
import { format, parseISO } from 'date-fns';
import { Card } from '@/components/ui/Card';

/**
 * PRDetailsCard Component
 * 
 * Displays the basic details of a Purchase Request in a card layout.
 * 
 * Shows:
 * - Requester name
 * - Department (with code)
 * - Date of request
 * - Expected date
 * - Purpose/Used for
 * 
 * Layout: 2-column grid on desktop, single column on mobile
 * 
 * @component
 * @example
 * ```tsx
 * <PRDetailsCard purchaseRequest={purchaseRequest} />
 * ```
 */

interface PRDetailsCardProps {
    purchaseRequest: PurchaseRequest;
}

export function PRDetailsCard({ purchaseRequest }: PRDetailsCardProps) {
    const formatDate = (dateString: string | null) => {
        if (!dateString) return 'Not specified';
        try {
            return format(parseISO(dateString), 'MMMM d, yyyy');
        } catch {
            return 'Invalid date';
        }
    };

    return (
        <Card>
            <div className="px-5 py-4 border-b border-gray-100">
                <h3 className="text-base font-semibold text-gray-900">Request Details</h3>
            </div>
            <div className="p-6">
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-x-8">
                    <div className="mb-6">
                        <p className="text-sm font-medium text-gray-500">Requested By</p>
                        <p className="mt-1 text-sm text-gray-900">
                            {purchaseRequest.user?.name || 'N/A'}
                        </p>
                    </div>
                    
                    <div className="mb-6">
                        <p className="text-sm font-medium text-gray-500">Department</p>
                        <p className="mt-1 text-sm text-gray-900">
                            {purchaseRequest.department?.name || 'N/A'}
                            {purchaseRequest.department?.code && (
                                <span className="text-gray-500"> ({purchaseRequest.department.code})</span>
                            )}
                        </p>
                    </div>
                    
                    <div className="mb-6">
                        <p className="text-sm font-medium text-gray-500">Date of Request</p>
                        <p className="mt-1 text-sm text-gray-900">
                            {formatDate(purchaseRequest.date_of_request)}
                        </p>
                    </div>
                    
                    <div className="mb-6">
                        <p className="text-sm font-medium text-gray-500">Expected Date</p>
                        <p className="mt-1 text-sm text-gray-900">
                            {formatDate(purchaseRequest.expected_date)}
                        </p>
                    </div>
                    
                    <div className="sm:col-span-2">
                        <p className="text-sm font-medium text-gray-500">Purpose / Used For</p>
                        <p className="mt-1 text-sm text-gray-900">
                            {purchaseRequest.used_for || 'Not specified'}
                        </p>
                    </div>
                </div>
            </div>
        </Card>
    );
}
