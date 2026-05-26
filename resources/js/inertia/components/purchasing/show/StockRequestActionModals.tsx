import React from 'react';
import { AnimatePresence, motion } from 'framer-motion';
import { Loader2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { OfflineApprovalUpload } from '@/components/purchasing/OfflineApprovalUpload';
import { ApprovalDecisionModal } from '@/components/purchasing/modals/ApprovalDecisionModal';
import { VoidConfirmModal } from '@/components/purchasing/modals/VoidConfirmModal';

interface StockRequestActionModalsProps {
    itemNumber: string;
    showVoidModal: boolean;
    showOfflineModal: boolean;
    showApproveModal: boolean;
    showRejectModal: boolean;
    isSubmitting: boolean;
    isDecisionSubmitting: boolean;
    voidReason: string;
    offlineNotes: string;
    offlineDocument: File | null;
    approvalNotes: string;
    onVoidClose: () => void;
    onOfflineClose: () => void;
    onApproveClose: () => void;
    onRejectClose: () => void;
    onVoidReasonChange: (value: string) => void;
    onOfflineNotesChange: (value: string) => void;
    onOfflineDocumentChange: (file: File | null) => void;
    onApprovalNotesChange: (value: string) => void;
    onVoidSubmit: () => void;
    onOfflineSubmit: (event?: React.FormEvent) => void;
    onApproveSubmit: () => void;
    onRejectSubmit: () => void;
}

export function StockRequestActionModals(props: StockRequestActionModalsProps) {
    return (
        <>
            <VoidConfirmModal open={props.showVoidModal} onClose={props.onVoidClose} isSubmitting={props.isSubmitting} reason={props.voidReason} onReasonChange={props.onVoidReasonChange} onSubmit={props.onVoidSubmit} itemNumber={props.itemNumber} />
            <AnimatePresence>
                {props.showOfflineModal && (
                    <motion.div initial={{ opacity: 0 }} animate={{ opacity: 1 }} exit={{ opacity: 0 }} className="fixed inset-0 bg-black/50 flex items-center justify-center z-50" onClick={props.onOfflineClose}>
                        <motion.div initial={{ scale: 0.95, opacity: 0 }} animate={{ scale: 1, opacity: 1 }} exit={{ scale: 0.95, opacity: 0 }} className="bg-white rounded-xl p-6 max-w-md w-full mx-4" onClick={(event) => event.stopPropagation()}>
                            <h3 className="text-lg font-semibold text-gray-900 mb-4">Mark as Offline Approved</h3>
                            <form onSubmit={props.onOfflineSubmit}>
                                <div className="mb-4">
                                    <OfflineApprovalUpload value={props.offlineDocument} onChange={props.onOfflineDocumentChange} notes={props.offlineNotes} onNotesChange={props.onOfflineNotesChange} isSubmitting={props.isSubmitting} />
                                </div>
                                <div className="flex justify-end space-x-3">
                                    <Button type="button" variant="ghost" onClick={props.onOfflineClose}>Cancel</Button>
                                    <Button type="submit" disabled={props.isSubmitting} className="bg-purple-600 hover:bg-purple-700 text-white">
                                        {props.isSubmitting ? <Loader2 className="w-4 h-4 animate-spin mr-2" /> : null}
                                        Mark Approved
                                    </Button>
                                </div>
                            </form>
                        </motion.div>
                    </motion.div>
                )}
            </AnimatePresence>
            <ApprovalDecisionModal open={props.showApproveModal} onClose={props.onApproveClose} mode="approve" isSubmitting={props.isDecisionSubmitting} notes={props.approvalNotes} onNotesChange={props.onApprovalNotesChange} onSubmit={props.onApproveSubmit} itemNumber={props.itemNumber} />
            <ApprovalDecisionModal open={props.showRejectModal} onClose={props.onRejectClose} mode="reject" isSubmitting={props.isDecisionSubmitting} notes={props.approvalNotes} onNotesChange={props.onApprovalNotesChange} onSubmit={props.onRejectSubmit} itemNumber={props.itemNumber} notesRequired />
        </>
    );
}
