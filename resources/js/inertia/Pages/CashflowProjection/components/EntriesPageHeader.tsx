import { Link } from '@inertiajs/react';
import { ArrowLeft, Download, Upload } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { formatMonthLabel } from '../utils';

type CashflowImportFlash = {
    status: 'success' | 'failed';
    summary: string;
    file_name: string;
    processed_rows: number;
    created_rows: number;
    updated_rows: number;
    failed_rows: number;
    errors: Array<{
        row?: number | null;
        column: string;
        message: string;
        value?: string | number | boolean | null;
    }>;
    truncated: boolean;
};

interface EntriesPageHeaderProps {
    year: number;
    selectedMonth: number;
    cashflowImportFlash?: CashflowImportFlash | null;
    onImportClick: () => void;
}

function ImportStat({ label, value, tone }: { label: string; value: number; tone?: string }) {
    return (
        <div className="rounded-2xl border border-white/70 bg-white/80 px-4 py-3">
            <p className="text-[11px] uppercase tracking-[0.16em] text-slate-500">{label}</p>
            <p className={`mt-1 text-lg font-semibold ${tone ?? 'text-slate-900'}`}>{value}</p>
        </div>
    );
}

export default function EntriesPageHeader({
    year,
    selectedMonth,
    cashflowImportFlash,
    onImportClick,
}: EntriesPageHeaderProps) {
    return (
        <>
            <div className="flex items-start justify-between">
                <div className="space-y-1">
                    <Link
                        href={route('cashflow-projection.index')}
                        className="mb-2 inline-flex items-center gap-1.5 text-sm text-muted-foreground transition-colors hover:text-primary"
                    >
                        <ArrowLeft className="h-4 w-4" />
                        Back to Dashboard
                    </Link>
                    <h1 className="text-[2rem] font-bold text-foreground">Cashflow Entries</h1>
                    <p className="text-sm text-muted-foreground">
                        {formatMonthLabel(selectedMonth)} {year} &mdash; Add and manage projection entries.
                    </p>
                </div>

                <div className="flex items-center gap-3">
                    <a
                        href={route('cashflow-projection.entries.import-template', { year, month: selectedMonth })}
                        className="inline-flex h-9 items-center justify-center gap-2 rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition-colors hover:bg-gray-50"
                    >
                        <Download className="h-4 w-4" />
                        Download Template
                    </a>
                    <Button type="button" variant="primary" onClick={onImportClick}>
                        <Upload className="h-4 w-4" />
                        Import Excel
                    </Button>
                </div>
            </div>

            {cashflowImportFlash && (
                <section
                    className={`rounded-2xl border p-5 shadow-[0_1px_2px_rgba(15,23,42,0.04)] ${
                        cashflowImportFlash.status === 'success'
                            ? 'border-emerald-200 bg-emerald-50/80'
                            : 'border-amber-200 bg-amber-50/90'
                    }`}
                >
                    <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div className="space-y-1">
                            <p className="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Import Summary</p>
                            <h2 className="text-lg font-semibold text-slate-900">{cashflowImportFlash.summary}</h2>
                            <p className="text-sm text-slate-600">
                                Source file: <span className="font-medium text-slate-800">{cashflowImportFlash.file_name}</span>
                            </p>
                        </div>
                        <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
                            <ImportStat label="Processed" value={cashflowImportFlash.processed_rows} />
                            <ImportStat label="Created" value={cashflowImportFlash.created_rows} tone="text-emerald-700" />
                            <ImportStat label="Updated" value={cashflowImportFlash.updated_rows} tone="text-blue-700" />
                            <ImportStat label="Failed Rows" value={cashflowImportFlash.failed_rows} tone="text-amber-700" />
                        </div>
                    </div>

                    {cashflowImportFlash.errors.length > 0 && (
                        <div className="mt-5 rounded-2xl border border-amber-200/80 bg-white/85 p-4">
                            <p className="text-sm font-semibold text-slate-900">Validation details</p>
                            <ul className="mt-3 space-y-2 text-sm text-slate-700">
                                {cashflowImportFlash.errors.map((error, index) => {
                                    const prefix = error.row ? `Row ${error.row}` : 'Template';
                                    const valueText = error.value !== undefined && error.value !== null && `${error.value}` !== ''
                                        ? ` (${error.value})`
                                        : '';

                                    return (
                                        <li key={`${error.column}-${error.row ?? 'template'}-${index}`}>
                                            {prefix} - {error.column}: {error.message}{valueText}
                                        </li>
                                    );
                                })}
                            </ul>

                            {cashflowImportFlash.truncated && (
                                <p className="mt-3 text-xs font-medium uppercase tracking-[0.14em] text-amber-700">
                                    Additional errors were truncated for readability.
                                </p>
                            )}
                        </div>
                    )}
                </section>
            )}
        </>
    );
}
