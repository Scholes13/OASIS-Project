import { Upload } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import type { CashflowImportFlash } from '@/types';
import type { ImportPreviewPayload, ImportPreviewRow } from '@/types/cashflowImport';
import ImportRowReviewPanel from './ImportRowReviewPanel';
import type { DepartmentOption } from '../types';

type ImportEntriesDialogProps = {
    open: boolean;
    processing: boolean;
    selectedFileName: string | null;
    preview?: ImportPreviewPayload | null;
    departments: DepartmentOption[];
    flashImport?: CashflowImportFlash;
    fileError?: string;
    onClose: () => void;
    onFileChange: (file: File | null) => void;
    onSubmit: () => void;
    onConfirm: () => void;
    onReviewRow: (rowNumber: number, row: ImportPreviewRow) => void;
};

export default function ImportEntriesDialog({
    open,
    processing,
    selectedFileName,
    preview,
    departments,
    flashImport,
    fileError,
    onClose,
    onFileChange,
    onSubmit,
    onConfirm,
    onReviewRow,
}: ImportEntriesDialogProps) {
    const hasBlockingRows = Boolean(preview && (preview.summary.need_review_rows > 0 || preview.summary.invalid_rows > 0));
    const firstBlockingRow = preview?.rows.find((row) => row.status === 'need_review' || row.status === 'invalid') ?? null;
    const [selectedReviewRowNumber, setSelectedReviewRowNumber] = useState<number | null>(null);
    const reviewRow = preview?.rows.find((row) => row.row_number === (selectedReviewRowNumber ?? firstBlockingRow?.row_number)) ?? null;

    return (
        <Dialog open={open} onClose={onClose} className="max-w-[min(1280px,calc(100vw-32px))]">
            <DialogHeader onClose={onClose}>
                <div>
                    <DialogTitle>Import Excel</DialogTitle>
                    <DialogDescription>
                        Upload finance sheet or friendly template, preview every row, then confirm only clean rows.
                    </DialogDescription>
                </div>
            </DialogHeader>

            <DialogContent className="max-h-[calc(100vh-9rem)] space-y-4 overflow-y-auto">
                <div className="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
                    <p>Preview will classify department, action, flow, and update candidates before any row is saved.</p>
                    <p className="mt-2 font-medium text-slate-700">Confirm import akan mempengaruhi data cashflow dan dashboard.</p>
                </div>

                <label className="block space-y-2">
                    <span className="text-sm font-medium text-slate-700">Excel file</span>
                    <input
                        type="file"
                        accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                        onChange={(event) => onFileChange(event.target.files?.[0] ?? null)}
                        className="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-medium file:text-slate-700 hover:file:bg-slate-200"
                    />
                </label>

                {selectedFileName && (
                    <div className="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                        Ready to import: <span className="font-semibold">{selectedFileName}</span>
                    </div>
                )}

                {fileError && (
                    <div className="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                        {fileError}
                    </div>
                )}

                {preview && (
                    <section className="space-y-4 rounded-2xl border border-slate-200 bg-white p-4">
                        <div className="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                            <div>
                                <p className="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Preview Import Results</p>
                                <h3 className="mt-1 text-lg font-semibold text-slate-900">
                                    {preview.summary.ready_rows} ready from {preview.summary.total_rows} rows
                                </h3>
                            </div>
                            <div className="grid grid-cols-3 gap-2 text-center text-xs sm:grid-cols-6">
                                <PreviewStat label="New" value={preview.summary.new_rows} />
                                <PreviewStat label="Update" value={preview.summary.update_rows} />
                                <PreviewStat label="No Change" value={preview.summary.no_change_rows} />
                                <PreviewStat label="Need Review" value={preview.summary.need_review_rows} />
                                <PreviewStat label="Invalid" value={preview.summary.invalid_rows} />
                                <PreviewStat label="Total" value={preview.summary.total_rows} />
                            </div>
                        </div>

                        <div className="grid gap-4 xl:grid-cols-[minmax(0,1fr)_360px]">
                        <div className="max-h-[54vh] overflow-auto rounded-xl border border-slate-100">
                            <table className="min-w-full divide-y divide-slate-100 text-sm">
                                <thead className="sticky top-0 bg-slate-50 text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">
                                    <tr>
                                        <th className="px-3 py-2 text-left">Row</th>
                                        <th className="px-3 py-2 text-left">Status</th>
                                        <th className="px-3 py-2 text-left">Department</th>
                                        <th className="px-3 py-2 text-left">Action</th>
                                        <th className="px-3 py-2 text-left">Description</th>
                                        <th className="px-3 py-2 text-left">Review</th>
                                        <th className="px-3 py-2 text-right">Amount</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100 bg-white">
                                    {preview.rows.map((row) => (
                                        <tr key={`${row.row_number}-${row.description}`}>
                                            <td className="px-3 py-2 text-slate-500">{row.row_number}</td>
                                            <td className="px-3 py-2"><StatusBadge status={row.status} /></td>
                                            <td className="px-3 py-2 text-slate-700">{row.department_code ?? 'Review'}</td>
                                            <td className="px-3 py-2 text-slate-700">{row.action_label ?? 'Review'}</td>
                                            <td className="px-3 py-2">
                                                <p className="max-w-md truncate font-medium text-slate-900">{row.description}</p>
                                                {row.keterangan && <p className="text-xs text-slate-500">{row.keterangan}</p>}
                                                {row.errors.length > 0 && (
                                                    <p className="mt-1 text-xs text-amber-700">{row.errors.map((error) => `${error.field}: ${error.message}`).join('; ')}</p>
                                                )}
                                            </td>
                                            <td className="px-3 py-2">
                                                {(row.status === 'need_review' || row.status === 'invalid') && (
                                                    <button
                                                        type="button"
                                                        className="rounded-lg border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-700 hover:bg-amber-100"
                                                        onClick={() => setSelectedReviewRowNumber(row.row_number)}
                                                    >
                                                        Review Row {row.row_number}
                                                    </button>
                                                )}
                                            </td>
                                            <td className="px-3 py-2 text-right font-medium text-slate-900">{row.amount}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        {reviewRow && <ImportRowReviewPanel row={reviewRow} departments={departments} onSave={(row) => {
                            onReviewRow(reviewRow.row_number, row);
                            setSelectedReviewRowNumber(null);
                        }} />}
                        </div>
                    </section>
                )}

                {flashImport?.status === 'failed' && flashImport.errors.length > 0 && (
                    <div className="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                        <p className="font-semibold">{flashImport.summary}</p>
                        <p className="mt-1 text-amber-800">
                            {flashImport.failed_rows} failed row{flashImport.failed_rows === 1 ? '' : 's'} from {flashImport.total_rows} uploaded row{flashImport.total_rows === 1 ? '' : 's'}.
                        </p>
                    </div>
                )}
            </DialogContent>

            <DialogFooter>
                <Button variant="outline" onClick={onClose} disabled={processing}>
                    Cancel
                </Button>
                <Button
                    type="button"
                    variant="primary"
                    loading={processing}
                    leftIcon={<Upload className="h-4 w-4" />}
                    onClick={onSubmit}
                >
                    Preview Import
                </Button>
                <Button
                    type="button"
                    variant="primary"
                    loading={processing}
                    disabled={!preview || hasBlockingRows}
                    onClick={onConfirm}
                >
                    Confirm Ready Rows
                </Button>
            </DialogFooter>
        </Dialog>
    );
}

function PreviewStat({ label, value }: { label: string; value: number }) {
    return (
        <div className="rounded-xl border border-slate-100 bg-slate-50 px-3 py-2">
            <p className="font-semibold text-slate-900">{value}</p>
            <p className="mt-0.5 text-[10px] uppercase tracking-[0.12em] text-slate-500">{label}</p>
        </div>
    );
}

function StatusBadge({ status }: { status: string }) {
    const tone = status === 'new' || status === 'update' || status === 'no_change'
        ? 'bg-emerald-50 text-emerald-700 ring-emerald-200'
        : status === 'invalid'
            ? 'bg-rose-50 text-rose-700 ring-rose-200'
            : 'bg-amber-50 text-amber-700 ring-amber-200';

    return <span className={`inline-flex rounded-full px-2 py-0.5 text-xs font-semibold capitalize ring-1 ${tone}`}>{status.replace('_', ' ')}</span>;
}
