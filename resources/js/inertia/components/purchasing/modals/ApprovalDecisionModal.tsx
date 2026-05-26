import React from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { Check, Loader2, X } from 'lucide-react';
import { Button } from '../../ui/button';

interface ApprovalDecisionModalProps {
    open: boolean;
    onClose: () => void;
    mode: 'approve' | 'reject';
    isSubmitting: boolean;
    notes: string;
    onNotesChange: (n: string) => void;
    onSubmit: () => void;
    itemNumber: string;
    notesRequired?: boolean;
}

export const ApprovalDecisionModal: React.FC<ApprovalDecisionModalProps> = ({
    open,
    onClose,
    mode,
    isSubmitting,
    notes,
    onNotesChange,
    onSubmit,
    itemNumber,
    notesRequired = mode === 'reject',
}) => {
    const isApprove = mode === 'approve';
    const title = `${isApprove ? 'Approve' : 'Reject'} ${itemNumber.startsWith('ST') ? 'Stock' : 'Purchase'} Request`;

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
                                        <div className={`flex-shrink-0 w-10 h-10 flex items-center justify-center rounded-full ${isApprove ? 'bg-emerald-100' : 'bg-red-100'}`}>
                                            {isApprove ? <Check className="w-5 h-5 text-emerald-600" /> : <X className="w-5 h-5 text-red-600" />}
                                        </div>
                                        <div className="ml-3">
                                            <h3 className="text-base font-semibold text-gray-900">{title}</h3>
                                            <p className="mt-1 text-sm text-gray-500">
                                                Are you sure you want to {mode} <strong>{itemNumber}</strong>?
                                            </p>
                                        </div>
                                    </div>
                                    <div className="mt-3">
                                        <label htmlFor={`${mode}_notes`} className="block text-sm font-medium text-gray-700">
                                            {isApprove ? 'Notes (optional)' : 'Reason for rejection'} {notesRequired && <span className="text-red-500">*</span>}
                                        </label>
                                        <textarea
                                            id={`${mode}_notes`}
                                            rows={isApprove ? 2 : 3}
                                            required={notesRequired}
                                            value={notes}
                                            onChange={(event) => onNotesChange(event.target.value)}
                                            className={`mt-1 block w-full rounded-lg border-gray-300 shadow-sm text-sm ${isApprove ? 'focus:border-emerald-500 focus:ring-emerald-500' : 'focus:border-red-500 focus:ring-red-500'}`}
                                            placeholder={isApprove ? 'Add any notes about your approval...' : 'Please provide a reason for rejecting this request...'}
                                        />
                                    </div>
                                </div>
                                <div className="bg-gray-50 px-5 py-3 flex justify-end gap-2">
                                    <Button type="button" variant="outline" onClick={onClose} disabled={isSubmitting} className="disabled:opacity-50 disabled:cursor-not-allowed">Cancel</Button>
                                    <Button type="submit" variant={isApprove ? 'default' : 'destructive'} disabled={isSubmitting} className={`${isApprove ? 'bg-emerald-600 hover:bg-emerald-700' : ''} disabled:opacity-50 disabled:cursor-not-allowed`}>
                                        {isSubmitting ? <><Loader2 className="w-4 h-4 mr-2 animate-spin" />{isApprove ? 'Approving...' : 'Rejecting...'}</> : `${isApprove ? 'Approve' : 'Reject'} Request`}
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
