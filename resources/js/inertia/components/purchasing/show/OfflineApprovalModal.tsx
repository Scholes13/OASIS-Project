import React from 'react';
import { AnimatePresence, motion } from 'framer-motion';
import { AlertTriangle, Loader2, Shield } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { OfflineApprovalUpload } from '@/components/purchasing/OfflineApprovalUpload';

interface OfflineApprovalModalProps {
    open: boolean;
    isSubmitting: boolean;
    notes: string;
    document: File | null;
    onClose: () => void;
    onNotesChange: (value: string) => void;
    onDocumentChange: (file: File | null) => void;
    onSubmit: (event?: React.FormEvent) => void;
}

export function OfflineApprovalModal({
    open,
    isSubmitting,
    notes,
    document,
    onClose,
    onNotesChange,
    onDocumentChange,
    onSubmit,
}: OfflineApprovalModalProps) {
    return (
        <AnimatePresence>
            {open && (
                <>
                    <motion.div
                        initial={{ opacity: 0 }}
                        animate={{ opacity: 1 }}
                        exit={{ opacity: 0 }}
                        className="fixed inset-0 bg-black bg-opacity-50 z-[9998]"
                        onClick={onClose}
                    />
                    <div className="fixed inset-0 z-[9999] flex items-center justify-center p-4">
                        <motion.div
                            initial={{ opacity: 0, scale: 0.95, y: 20 }}
                            animate={{ opacity: 1, scale: 1, y: 0 }}
                            exit={{ opacity: 0, scale: 0.95, y: 20 }}
                            className="relative w-full max-w-md transform overflow-hidden rounded-xl bg-white shadow-xl transition-all"
                        >
                            <form onSubmit={onSubmit}>
                                <div className="bg-white px-5 py-4">
                                    <div className="flex items-start">
                                        <div className="flex-shrink-0 w-10 h-10 flex items-center justify-center rounded-full bg-purple-100">
                                            <Shield className="w-5 h-5 text-purple-600" />
                                        </div>
                                        <div className="ml-3">
                                            <h3 className="text-base font-semibold text-gray-900">
                                                Mark as Offline Approved
                                            </h3>
                                            <p className="mt-1 text-sm text-gray-500">
                                                Use this when PR has been approved manually/offline (e.g., signed paper copy).
                                            </p>
                                        </div>
                                    </div>
                                    <div className="mt-3 rounded-lg bg-amber-50 border border-amber-200 p-2.5">
                                        <div className="flex">
                                            <AlertTriangle className="w-4 h-4 flex-shrink-0 text-amber-500 mt-0.5" />
                                            <p className="ml-2 text-xs text-amber-700">
                                                <strong>Note:</strong> This will skip digital approval workflow. PR status will show as "Approved".
                                            </p>
                                        </div>
                                    </div>
                                    <div className="mt-3">
                                        <OfflineApprovalUpload
                                            value={document}
                                            onChange={onDocumentChange}
                                            notes={notes}
                                            onNotesChange={onNotesChange}
                                            isSubmitting={isSubmitting}
                                        />
                                    </div>
                                </div>
                                <div className="bg-gray-50 px-5 py-3 flex justify-end gap-2">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={onClose}
                                        disabled={isSubmitting}
                                        className="disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        Cancel
                                    </Button>
                                    <Button
                                        type="submit"
                                        className="bg-purple-600 hover:bg-purple-700 disabled:opacity-50 disabled:cursor-not-allowed"
                                        disabled={isSubmitting}
                                    >
                                        {isSubmitting ? (
                                            <>
                                                <Loader2 className="w-4 h-4 mr-2 animate-spin" />
                                                Submitting...
                                            </>
                                        ) : 'Mark as Offline Approved'}
                                    </Button>
                                </div>
                            </form>
                        </motion.div>
                    </div>
                </>
            )}
        </AnimatePresence>
    );
}
