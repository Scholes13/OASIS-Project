import { router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { showToast } from '@/components/ui/toast';

type ImportPreviewSummary = {
    total_rows: number;
    ready_rows: number;
    new_rows: number;
    update_rows: number;
    no_change_rows: number;
    need_review_rows: number;
    invalid_rows: number;
};

export type ImportPreviewRow = {
    row_number: number;
    status: 'new' | 'update' | 'no_change' | 'need_review' | 'invalid';
    business_unit_code: string;
    department_code: string | null;
    action_code: string | null;
    action_label: string | null;
    flow_type: 'in' | 'out' | null;
    transaction_date: string | null;
    due_date: string | null;
    amount: number | string | null;
    description: string | null;
    keterangan: string | null;
    notes: string | null;
    match: { line_item_id: number } | null;
    changes: Array<{ field: string; old: unknown; new: unknown }>;
    errors: Array<{ field: string; message: string }>;
};

export type ImportPreviewPayload = {
    summary: ImportPreviewSummary;
    rows: ImportPreviewRow[];
};

function buildSummary(rows: ImportPreviewRow[]) {
    return rows.reduce((summary, row) => ({
        ...summary,
        ready_rows: summary.ready_rows + (['new', 'update', 'no_change'].includes(row.status) ? 1 : 0),
        new_rows: summary.new_rows + (row.status === 'new' ? 1 : 0),
        update_rows: summary.update_rows + (row.status === 'update' ? 1 : 0),
        no_change_rows: summary.no_change_rows + (row.status === 'no_change' ? 1 : 0),
        need_review_rows: summary.need_review_rows + (row.status === 'need_review' ? 1 : 0),
        invalid_rows: summary.invalid_rows + (row.status === 'invalid' ? 1 : 0),
    }), { total_rows: rows.length, ready_rows: 0, new_rows: 0, update_rows: 0, no_change_rows: 0, need_review_rows: 0, invalid_rows: 0 });
}

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

            if (!response.ok) {
                throw new Error(await responseMessage(response, 'Preview import gagal. Periksa file lalu coba lagi.'));
            }

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
                body: JSON.stringify({ context_year: year, context_month: selectedMonth, rows: preview.rows }),
            });

            if (!response.ok) {
                throw new Error(await responseMessage(response, 'Confirm import gagal. Selesaikan row yang perlu review.'));
            }

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

    const updatePreviewRow = (rowNumber: number, row: ImportPreviewRow) => {
        setPreview((current) => {
            if (!current) return current;
            const rows = current.rows.map((previewRow) => previewRow.row_number === rowNumber ? row : previewRow);
            return { rows, summary: buildSummary(rows) };
        });
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
