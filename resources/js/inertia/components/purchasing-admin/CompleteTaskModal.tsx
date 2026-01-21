import * as React from 'react';
import { router } from '@inertiajs/react';
import { X, CheckCircle2 } from 'lucide-react';
import { cn } from '@/lib/utils';
import { showToast } from '@/components/ui/toast';
import type { AdminTask } from './types';

interface CompleteTaskModalProps {
    task: AdminTask | null;
    open: boolean;
    onClose: () => void;
    onComplete?: () => void; // Callback after successful completion
}

// Format currency
const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(amount);
};

export function CompleteTaskModal({ task, open, onClose, onComplete }: CompleteTaskModalProps) {
    const [realizedTotalPrice, setRealizedTotalPrice] = React.useState('');
    const [vendorName, setVendorName] = React.useState('');
    const [notes, setNotes] = React.useState('');
    const [isSubmitting, setIsSubmitting] = React.useState(false);
    const [errors, setErrors] = React.useState<Record<string, string>>({});

    // Reset form when modal opens
    React.useEffect(() => {
        if (open && task) {
            setRealizedTotalPrice(task.estimated_total_price?.toString() || '');
            setVendorName('');
            setNotes('');
            setErrors({});
        }
    }, [open, task]);

    if (!open || !task) return null;

    const estimatedPrice = task.estimated_total_price || 0;
    const realizedPrice = parseFloat(realizedTotalPrice) || 0;
    const savingsAmount = estimatedPrice - realizedPrice;
    const savingsPercentage = estimatedPrice > 0 ? (savingsAmount / estimatedPrice) * 100 : 0;

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        // Validation
        const newErrors: Record<string, string> = {};
        if (!realizedTotalPrice || parseFloat(realizedTotalPrice) <= 0) {
            newErrors.realizedTotalPrice = 'Realized price is required and must be greater than 0';
        }

        if (Object.keys(newErrors).length > 0) {
            setErrors(newErrors);
            return;
        }

        setIsSubmitting(true);
        router.post(route('purchasing.admin.tasks.complete', { taskId: task.id }), {
            realized_total_price: parseFloat(realizedTotalPrice),
            vendor_name: vendorName || null,
            notes: notes || null,
        }, {
            preserveScroll: true,
            onSuccess: () => {
                showToast.success('Task completed', 'Task has been marked as completed');
                onComplete?.();
                onClose();
            },
            onError: (errors) => {
                setErrors(errors as Record<string, string>);
            },
            onFinish: () => setIsSubmitting(false),
        });
    };

    return (
        <div className="fixed inset-0 z-50 overflow-y-auto">
            {/* Backdrop */}
            <div
                className="fixed inset-0 bg-gray-500/75 transition-opacity"
                onClick={onClose}
            />

            {/* Modal Container */}
            <div className="flex min-h-full items-center justify-center p-4">
                {/* Modal Panel */}
                <div className="relative bg-white rounded-xl shadow-xl max-w-lg w-full overflow-hidden">
                    {/* Header */}
                    <div className="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                        <div className="flex items-center gap-3">
                            <div className="w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center">
                                <CheckCircle2 className="w-5 h-5 text-emerald-600" />
                            </div>
                            <h3 className="text-lg font-semibold text-gray-900">Complete Task</h3>
                        </div>
                        <button
                            onClick={onClose}
                            className="p-2 text-gray-400 hover:text-gray-600 transition-colors rounded-lg hover:bg-gray-100"
                        >
                            <X className="w-5 h-5" />
                        </button>
                    </div>

                    {/* Form */}
                    <form onSubmit={handleSubmit}>
                        <div className="px-6 py-4 space-y-4">
                            {/* Realized Price Input */}
                            <div>
                                <label htmlFor="realizedTotalPrice" className="block text-sm font-medium text-gray-700 mb-1">
                                    Realized Total Price <span className="text-red-500">*</span>
                                </label>
                                <div className="relative">
                                    <span className="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">
                                        Rp
                                    </span>
                                    <input
                                        type="number"
                                        id="realizedTotalPrice"
                                        value={realizedTotalPrice}
                                        onChange={(e) => setRealizedTotalPrice(e.target.value)}
                                        step="1"
                                        min="1"
                                        className={cn(
                                            "w-full pl-10 pr-3 py-2 border rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500",
                                            errors.realizedTotalPrice ? "border-red-300" : "border-gray-300"
                                        )}
                                        placeholder="0"
                                    />
                                </div>
                                {errors.realizedTotalPrice && (
                                    <p className="mt-1 text-sm text-red-600">{errors.realizedTotalPrice}</p>
                                )}
                                <p className="mt-1 text-xs text-gray-500">
                                    Estimated: {formatCurrency(estimatedPrice)}
                                </p>
                            </div>

                            {/* Calculated Savings Preview */}
                            {realizedPrice > 0 && (
                                <div className="p-3 bg-gray-50 rounded-lg">
                                    <div className="grid grid-cols-2 gap-3 text-sm">
                                        <div>
                                            <span className="text-gray-500">Savings Amount:</span>
                                            <p className={cn(
                                                "font-semibold",
                                                savingsAmount >= 0 ? "text-emerald-600" : "text-red-600"
                                            )}>
                                                {formatCurrency(savingsAmount)}
                                            </p>
                                        </div>
                                        <div>
                                            <span className="text-gray-500">Savings Percentage:</span>
                                            <p className={cn(
                                                "font-semibold",
                                                savingsPercentage >= 0 ? "text-emerald-600" : "text-red-600"
                                            )}>
                                                {savingsPercentage.toFixed(2)}%
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            )}

                            {/* Vendor Name Input */}
                            <div>
                                <label htmlFor="vendorName" className="block text-sm font-medium text-gray-700 mb-1">
                                    Vendor/Supplier Name
                                </label>
                                <input
                                    type="text"
                                    id="vendorName"
                                    value={vendorName}
                                    onChange={(e) => setVendorName(e.target.value)}
                                    className={cn(
                                        "w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500",
                                        errors.vendor_name ? "border-red-300" : "border-gray-300"
                                    )}
                                    placeholder="Enter vendor name used for this purchase"
                                />
                                {errors.vendor_name && (
                                    <p className="mt-1 text-sm text-red-600">{errors.vendor_name}</p>
                                )}
                                <p className="mt-1 text-xs text-gray-500">
                                    This will update the vendor/supplier on the purchase request
                                </p>
                            </div>

                            {/* Notes Input */}
                            <div>
                                <label htmlFor="notes" className="block text-sm font-medium text-gray-700 mb-1">
                                    Notes (Optional)
                                </label>
                                <textarea
                                    id="notes"
                                    value={notes}
                                    onChange={(e) => setNotes(e.target.value)}
                                    rows={3}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                                    placeholder="Add any notes about this task completion..."
                                />
                                {errors.notes && (
                                    <p className="mt-1 text-sm text-red-600">{errors.notes}</p>
                                )}
                            </div>
                        </div>

                        {/* Footer */}
                        <div className="px-6 py-4 bg-gray-50 border-t border-gray-100 flex gap-3 justify-end">
                            <button
                                type="button"
                                onClick={onClose}
                                className="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                disabled={isSubmitting}
                                className="px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 transition-colors disabled:opacity-50"
                            >
                                {isSubmitting ? 'Completing...' : 'Complete Task'}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    );
}

export default CompleteTaskModal;
