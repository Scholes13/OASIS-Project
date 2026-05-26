import React from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { AlertTriangle, Loader2 } from 'lucide-react';
import { Button } from '../../ui/button';

interface VoidConfirmModalProps {
    open: boolean;
    onClose: () => void;
    isSubmitting: boolean;
    reason: string;
    onReasonChange: (r: string) => void;
    onSubmit: () => void;
    itemNumber: string;
}

export const VoidConfirmModal: React.FC<VoidConfirmModalProps> = ({ open, onClose, isSubmitting, reason, onReasonChange, onSubmit, itemNumber }) => {
    const itemType = itemNumber.startsWith('ST') ? 'Stock' : 'Purchase';

    return (
        <AnimatePresence>
            {open && (
                <>
                    <motion.div initial={{ opacity: 0 }} animate={{ opacity: 1 }} exit={{ opacity: 0 }} className="fixed inset-0 bg-black bg-opacity-50 z-[9998]" onClick={onClose} />
                    <div className="fixed inset-0 z-[9999] flex items-center justify-center p-4">
                        <motion.div initial={{ opacity: 0, scale: 0.95, y: 20 }} animate={{ opacity: 1, scale: 1, y: 0 }} exit={{ opacity: 0, scale: 0.95, y: 20 }} className="relative w-full max-w-md transform overflow-hidden rounded-xl bg-white shadow-xl transition-all">
                            <form onSubmit={(event) => { event.preventDefault(); onSubmit(); }}>
                                <div className="bg-white px-5 py-4">
                                    <div className="flex items-start">
                                        <div className="flex-shrink-0 w-10 h-10 flex items-center justify-center rounded-full bg-red-100">
                                            <AlertTriangle className="w-5 h-5 text-red-600" />
                                        </div>
                                        <div className="ml-3">
                                            <h3 className="text-base font-semibold text-gray-900">Void {itemType} Request</h3>
                                            <p className="mt-1 text-sm text-gray-500">
                                                Are you sure you want to void <strong>{itemNumber}</strong>? This action cannot be undone.
                                            </p>
                                        </div>
                                    </div>
                                    <div className="mt-3">
                                        <label htmlFor="void_reason" className="block text-sm font-medium text-gray-700">
                                            Reason for voiding <span className="text-red-500">*</span>
                                        </label>
                                        <textarea
                                            id="void_reason"
                                            rows={3}
                                            required
                                            value={reason}
                                            onChange={(event) => onReasonChange(event.target.value)}
                                            className="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 text-sm"
                                            placeholder={`Please provide a reason for voiding this ${itemType.toLowerCase()} request...`}
                                        />
                                    </div>
                                </div>
                                <div className="bg-gray-50 px-5 py-3 flex justify-end gap-2">
                                    <Button type="button" variant="outline" onClick={onClose} disabled={isSubmitting} className="disabled:opacity-50 disabled:cursor-not-allowed">Cancel</Button>
                                    <Button type="submit" variant="destructive" disabled={isSubmitting} className="disabled:opacity-50 disabled:cursor-not-allowed">
                                        {isSubmitting ? <><Loader2 className="w-4 h-4 mr-2 animate-spin" />Voiding...</> : `Void ${itemType} Request`}
                                    </Button>
                                </div>
                            </form>
                        </motion.div>
                    </div>
                </>
            )}
        </AnimatePresence>
    );
};
