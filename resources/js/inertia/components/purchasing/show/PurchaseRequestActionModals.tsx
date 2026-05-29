import React from 'react';
import { ApprovalDecisionModal } from '@/components/purchasing/modals/ApprovalDecisionModal';
import { VoidConfirmModal } from '@/components/purchasing/modals/VoidConfirmModal';
import { OfflineApprovalModal } from './OfflineApprovalModal';

interface PurchaseRequestActionModalsProps {
    itemNumber: string;
    showVoidModal: boolean;
    showOfflineModal: boolean;
    showApproveModal: boolean;
    showRejectModal: boolean;
    isSubmitting: boolean;
    voidReason: string;
    offlineNotes: string;
    offlineDocument: File | null;
    approvalNotes: string;
    rejectionNotes: string;
    onVoidClose: () => void;
    onOfflineClose: () => void;
    onApproveClose: () => void;
    onRejectClose: () => void;
    onVoidReasonChange: (value: string) => void;
    onOfflineNotesChange: (value: string) => void;
    onOfflineDocumentChange: (file: File | null) => void;
    onApprovalNotesChange: (value: string) => void;
    onRejectionNotesChange: (value: string) => void;
    onVoidSubmit: () => void;
    onOfflineSubmit: (event?: React.FormEvent) => void;
    onApproveSubmit: () => void;
    onRejectSubmit: () => void;
}

export function PurchaseRequestActionModals({
    itemNumber,
    showVoidModal,
    showOfflineModal,
    showApproveModal,
    showRejectModal,
    isSubmitting,
    voidReason,
    offlineNotes,
    offlineDocument,
    approvalNotes,
    rejectionNotes,
    onVoidClose,
    onOfflineClose,
    onApproveClose,
    onRejectClose,
    onVoidReasonChange,
    onOfflineNotesChange,
    onOfflineDocumentChange,
    onApprovalNotesChange,
    onRejectionNotesChange,
    onVoidSubmit,
    onOfflineSubmit,
    onApproveSubmit,
    onRejectSubmit,
}: PurchaseRequestActionModalsProps) {
    return (
        <>
            <VoidConfirmModal
                open={showVoidModal}
                onClose={onVoidClose}
                isSubmitting={isSubmitting}
                reason={voidReason}
                onReasonChange={onVoidReasonChange}
                onSubmit={onVoidSubmit}
                itemNumber={itemNumber}
            />

            <OfflineApprovalModal
                open={showOfflineModal}
                isSubmitting={isSubmitting}
                notes={offlineNotes}
                document={offlineDocument}
                onClose={onOfflineClose}
                onNotesChange={onOfflineNotesChange}
                onDocumentChange={onOfflineDocumentChange}
                onSubmit={onOfflineSubmit}
            />

            <ApprovalDecisionModal
                open={showApproveModal}
                onClose={onApproveClose}
                mode="approve"
                isSubmitting={isSubmitting}
                notes={approvalNotes}
                onNotesChange={onApprovalNotesChange}
                onSubmit={onApproveSubmit}
                itemNumber={itemNumber}
            />
            <ApprovalDecisionModal
                open={showRejectModal}
                onClose={onRejectClose}
                mode="reject"
                isSubmitting={isSubmitting}
                notes={rejectionNotes}
                onNotesChange={onRejectionNotesChange}
                onSubmit={onRejectSubmit}
                itemNumber={itemNumber}
                notesRequired
            />
        </>
    );
}
