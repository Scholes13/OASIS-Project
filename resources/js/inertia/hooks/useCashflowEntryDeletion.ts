import { router } from '@inertiajs/react';
import { useState } from 'react';
import { showToast } from '@/components/ui/toast';
import type { PageProps } from '@/types';

type DeleteDialogState = {
    isOpen: boolean;
    lineItemId: number | null;
    label: string;
};

type UseCashflowEntryDeletionParams = {
    year: number;
    selectedMonth: number;
};

export function useCashflowEntryDeletion({ year, selectedMonth }: UseCashflowEntryDeletionParams) {
    const [isDeleting, setIsDeleting] = useState(false);
    const [bulkDeleteIds, setBulkDeleteIds] = useState<number[]>([]);
    const [isBulkDeleteDialogOpen, setIsBulkDeleteDialogOpen] = useState(false);
    const [deleteDialogState, setDeleteDialogState] = useState<DeleteDialogState>({
        isOpen: false,
        lineItemId: null,
        label: '',
    });

    const openDeleteDialog = (lineItemId: number, label: string) => {
        setDeleteDialogState({
            isOpen: true,
            lineItemId,
            label,
        });
    };

    const closeDeleteDialog = () => {
        setIsDeleting(false);
        setDeleteDialogState({
            isOpen: false,
            lineItemId: null,
            label: '',
        });
    };

    const confirmDelete = () => {
        if (!deleteDialogState.lineItemId || isDeleting) {
            return;
        }

        setIsDeleting(true);
        router.delete(route('cashflow-projection.line-items.destroy', { lineItem: deleteDialogState.lineItemId }), {
            preserveScroll: true,
            onSuccess: (page) => {
                closeDeleteDialog();
                const pageProps = page.props as unknown as PageProps;
                showToast.success(pageProps.flash?.success ?? 'Line item cashflow berhasil dihapus.');
            },
            onFinish: () => setIsDeleting(false),
        });
    };

    const openBulkDeleteDialog = (lineItemIds: number[]) => {
        if (lineItemIds.length === 0) {
            return;
        }

        setBulkDeleteIds(lineItemIds);
        setIsBulkDeleteDialogOpen(true);
    };

    const closeBulkDeleteDialog = () => {
        setIsDeleting(false);
        setIsBulkDeleteDialogOpen(false);
        setBulkDeleteIds([]);
    };

    const confirmBulkDelete = () => {
        if (bulkDeleteIds.length === 0 || isDeleting) {
            return;
        }

        setIsDeleting(true);
        router.delete(route('cashflow-projection.line-items.bulk-destroy'), {
            data: { line_item_ids: bulkDeleteIds, year, month: selectedMonth },
            preserveScroll: true,
            onSuccess: (page) => {
                closeBulkDeleteDialog();
                const pageProps = page.props as unknown as PageProps;
                showToast.success(pageProps.flash?.success ?? `${bulkDeleteIds.length} line item cashflow berhasil dihapus.`);
            },
            onFinish: () => setIsDeleting(false),
        });
    };

    return {
        bulkDeleteIds,
        closeBulkDeleteDialog,
        closeDeleteDialog,
        confirmBulkDelete,
        confirmDelete,
        deleteDialogState,
        isBulkDeleteDialogOpen,
        isDeleting,
        openBulkDeleteDialog,
        openDeleteDialog,
    };
}
