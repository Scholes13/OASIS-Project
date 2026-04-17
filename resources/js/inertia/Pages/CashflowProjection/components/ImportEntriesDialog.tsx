import { Upload } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import type { CashflowImportFlash } from '@/types';

type ImportEntriesDialogProps = {
    open: boolean;
    processing: boolean;
    selectedFileName: string | null;
    flashImport?: CashflowImportFlash;
    fileError?: string;
    onClose: () => void;
    onFileChange: (file: File | null) => void;
    onSubmit: () => void;
};

export default function ImportEntriesDialog({
    open,
    processing,
    selectedFileName,
    flashImport,
    fileError,
    onClose,
    onFileChange,
    onSubmit,
}: ImportEntriesDialogProps) {
    return (
        <Dialog open={open} onClose={onClose}>
            <DialogHeader onClose={onClose}>
                <div>
                    <DialogTitle>Import Excel</DialogTitle>
                    <DialogDescription>
                        Upload file `.xlsx` yang mengikuti template resmi. Import bersifat all-or-nothing dan update wajib memakai `line_item_id`.
                    </DialogDescription>
                </div>
            </DialogHeader>

            <DialogContent className="space-y-4">
                <div className="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
                    <p>Gunakan kode persis dari sheet `Reference` dan jangan ubah urutan kolom pada sheet `Template`.</p>
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
                    Upload Import
                </Button>
            </DialogFooter>
        </Dialog>
    );
}
