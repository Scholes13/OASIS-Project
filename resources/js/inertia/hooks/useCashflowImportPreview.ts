import { router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { showToast } from '@/components/ui/toast';
import type { ImportPreviewPayload, ImportPreviewRow } from '@/types/cashflowImport';

function csrfToken(): string | null {
    return document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? null;
}

async function responseMessage(response: Response, fallback: string): Promise<string> {
    try {
        const payload = await response.json();
        return typeof payload.message === 'string' ? payload.message : fallback;
    } catch {
        return fallback;
    }
}

export function useCashflowImportPreview(year: number, selectedMonth: number) {
    const [preview, setPreview] = useState<ImportPreviewPayload | null>(null);
    const [previewError, setPreviewError] = useState<string | null>(null);
    const [previewProcessing, setPreviewProcessing] = useState(false);
    const [confirmProcessing, setConfirmProcessing] = useState(false);
    const form = useForm<{ file: File | null }>({ file: null });
    const { data, setData, processing, errors } = form;

    const reset = () => {
        setData({ file: null });
        setPreview(null);
        setPreviewError(null);
    };

    const previewImport = async () => {
        if (!data.file) return;

        setPreviewProcessing(true);
        setPreviewError(null);
        const formData = new FormData();
        formData.append('file', data.file);
        formData.append('context_year', String(year));
        formData.append('context_month', String(selectedMonth));
        const token = csrfToken();

        if (!token) {
            setPreviewError('Session expired. Reload page and try again.');
            setPreviewProcessing(false);
            return;
        }

        try {
            const response = await fetch(route('cashflow-projection.entries.import-preview'), {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': token },
                body: formData,
            });

            if (!response.ok) throw new Error(await responseMessage(response, 'Preview import gagal. Periksa file lalu coba lagi.'));

            setPreview(await response.json());
        } catch (error) {
            setPreviewError(error instanceof Error ? error.message : 'Preview import gagal.');
        } finally {
            setPreviewProcessing(false);
        }
    };

    const confirmImport = async () => {
        if (!preview) return;

        setConfirmProcessing(true);
        setPreviewError(null);
        const token = csrfToken();
        if (!token) {
            setPreviewError('Session expired. Reload page and try again.');
            setConfirmProcessing(false);
            return false;
        }

        try {
            const response = await fetch(route('cashflow-projection.entries.import-confirm'), {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token },
                body: JSON.stringify({ context_year: year, context_month: selectedMonth, preview_token: preview.preview_token, rows: preview.rows }),
            });

            if (!response.ok) throw new Error(await responseMessage(response, 'Confirm import gagal. Selesaikan row yang perlu review.'));

            const payload = await response.json();
            showToast.success(`Import berhasil: ${payload.summary.created_rows} dibuat, ${payload.summary.updated_rows} diperbarui, ${payload.summary.skipped_rows} tanpa perubahan.`);
            reset();
            router.reload({ only: ['lineItems'] });
            return true;
        } catch (error) {
            setPreviewError(error instanceof Error ? error.message : 'Confirm import gagal.');
            return false;
        } finally {
            setConfirmProcessing(false);
        }
    };

    const updatePreviewRow = async (rowNumber: number, row: ImportPreviewRow) => {
        if (!preview) return false;
        const token = csrfToken();
        if (!token) {
            setPreviewError('Session expired. Reload page and try again.');
            return false;
        }

        setPreviewProcessing(true);
        setPreviewError(null);
        const candidateRows = preview.rows.map((previewRow) => previewRow.row_number === rowNumber ? row : previewRow);

        try {
            const response = await fetch(route('cashflow-projection.entries.import-review'), {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token },
                body: JSON.stringify({
                    context_year: year,
                    context_month: selectedMonth,
                    preview_token: preview.preview_token,
                    signed_rows: preview.rows,
                    rows: candidateRows,
                }),
            });

            if (!response.ok) throw new Error(await responseMessage(response, 'Review row gagal. Periksa data lalu coba lagi.'));

            setPreview(await response.json());
            return true;
        } catch (error) {
            setPreviewError(error instanceof Error ? error.message : 'Review row gagal.');
            return false;
        } finally {
            setPreviewProcessing(false);
        }
    };

    return {
        file: data.file,
        errors,
        preview,
        previewError,
        processing: processing || previewProcessing || confirmProcessing,
        setFile: (file: File | null) => setData('file', file),
        reset,
        updatePreviewRow,
        previewImport,
        confirmImport,
    };
}
