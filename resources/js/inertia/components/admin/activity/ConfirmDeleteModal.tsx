import { ConfirmDialog } from '@/components/ui/dialog';

interface ConfirmDeleteModalProps {
    open: boolean;
    itemName: string;
    itemType?: 'activityType' | 'subActivity';
    loading: boolean;
    onClose: () => void;
    onConfirm: () => void;
}

export function ConfirmDeleteModal({
    open,
    itemName,
    itemType,
    loading,
    onClose,
    onConfirm,
}: ConfirmDeleteModalProps) {
    return (
        <ConfirmDialog
            open={open}
            onClose={onClose}
            onConfirm={onConfirm}
            title={`Delete ${itemType === 'activityType' ? 'Activity Type' : 'Sub-Activity'}?`}
            description={itemName ? `Are you sure you want to delete "${itemName}"? This action cannot be undone.` : ''}
            confirmText="Delete"
            cancelText="Cancel"
            variant="danger"
            loading={loading}
        />
    );
}

export default ConfirmDeleteModal;
