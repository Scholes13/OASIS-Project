import { Head, useForm, usePage } from '@inertiajs/react';
import { type FormEvent, useEffect, useMemo, useState } from 'react';
import './cashflow-dashboard.css';
import AddProjectionCard from './components/AddProjectionCard';
import EntriesPageHeader from './components/EntriesPageHeader';
import EntriesTable from './components/EntriesTable';
import ImportEntriesDialog from './components/ImportEntriesDialog';
import type { CashflowProjectionEntriesPageProps, LineItemFormData } from './types';
import { ConfirmDialog } from '@/components/ui/ConfirmDialog';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { useCashflowEntryDeletion } from '@/hooks/useCashflowEntryDeletion';
import { useCashflowImportPreview } from '@/hooks/useCashflowImportPreview';
import type { PageProps } from '@/types';

type FlowType = 'in' | 'out';

type CategoryOption = {
    value: string;
    label: string;
    flow_type: FlowType;
    department_id: number;
};

type BusinessUnitOption = {
    id: number;
    code: string;
    name: string;
};

function extractDepartmentCode(actionCode: string, fallbackCode: string): string {
    const match = actionCode.match(/^[A-Z]+_([A-Z0-9]+)_/);
    return match?.[1] ?? fallbackCode;
}

function normalizeActionLabel(actionLabel: string): string {
    const prefixedParts = actionLabel.split(' - ');
    const coreLabel = prefixedParts.length >= 2
        ? prefixedParts.slice(prefixedParts.length >= 3 ? 2 : 1).join(' - ')
        : actionLabel;

    if (coreLabel.toLowerCase().startsWith('operational department')) {
        return 'Operational';
    }

    if (prefixedParts.length >= 2) {
        return coreLabel;
    }

    return actionLabel;
}

function formatCategoryLabel(
    actionCode: string,
    actionLabel: string,
    departmentCode: string,
    businessUnitCode?: string,
    isLinkedBusinessUnit: boolean = false
): string {
    const normalizedDepartmentCode = extractDepartmentCode(actionCode, departmentCode);
    const normalizedLabel = normalizeActionLabel(actionLabel);

    const labelParts = [normalizedDepartmentCode];

    if (isLinkedBusinessUnit && businessUnitCode) {
        labelParts.push(businessUnitCode);
    }

    labelParts.push(normalizedLabel);

    return labelParts.join(' - ');
}

function toIsoDate(year: number, month: number, day: number): string {
    const safeMonth = String(Math.max(1, Math.min(12, month))).padStart(2, '0');
    const safeDay = String(Math.max(1, day)).padStart(2, '0');
    return `${year}-${safeMonth}-${safeDay}`;
}

export default function CashflowProjectionEntries({
    year,
    selectedMonth,
    filters,
    departments,
    lineItems,
}: CashflowProjectionEntriesPageProps) {
    const pageProps = usePage<PageProps>().props;
    const { currentBusinessUnit: activeBusinessUnit, flash } = pageProps;
    const cashflowImportFlash = flash?.cashflow_import;
    const [flowType, setFlowType] = useState<FlowType>('in');
    const [editingLineItemId, setEditingLineItemId] = useState<number | null>(null);
    const [isImportDialogOpen, setIsImportDialogOpen] = useState(false);
    const [isProjectionDialogOpen, setIsProjectionDialogOpen] = useState(false);

    const importPreview = useCashflowImportPreview(year, selectedMonth);
    const entryDeletion = useCashflowEntryDeletion({ year, selectedMonth });
    const lineItemRows = lineItems.data;

    const defaultTransactionDate = useMemo(() => {
        const now = new Date();
        if (now.getFullYear() === year && now.getMonth() + 1 === selectedMonth) {
            return toIsoDate(year, selectedMonth, now.getDate());
        }
        return toIsoDate(year, selectedMonth, 1);
    }, [year, selectedMonth]);

    const businessUnitOptions = useMemo<BusinessUnitOption[]>(() => {
        return departments.reduce<BusinessUnitOption[]>((options, department) => {
            if (!department.business_unit_id || !department.business_unit_code) {
                return options;
            }

            if (options.some((option) => option.id === department.business_unit_id)) {
                return options;
            }

            return [
                ...options,
                {
                    id: department.business_unit_id,
                    code: department.business_unit_code,
                    name: department.business_unit_name ?? department.business_unit_code,
                },
            ];
        }, []);
    }, [departments]);

    const [selectedBusinessUnitId, setSelectedBusinessUnitId] = useState<number>(() => businessUnitOptions[0]?.id ?? 0);

    const departmentsForSelectedBusinessUnit = useMemo(() => {
        return departments.filter((department) => department.business_unit_id === selectedBusinessUnitId);
    }, [departments, selectedBusinessUnitId]);

    const selectedBusinessUnitOption = useMemo(() => {
        return businessUnitOptions.find((option) => option.id === selectedBusinessUnitId) ?? null;
    }, [businessUnitOptions, selectedBusinessUnitId]);

    const linkedBusinessUnitNotice = useMemo(() => {
        if (!activeBusinessUnit || !selectedBusinessUnitOption || selectedBusinessUnitOption.id === activeBusinessUnit.id) {
            return null;
        }

        return `This entry will be saved to linked business unit ${selectedBusinessUnitOption.code} - ${selectedBusinessUnitOption.name}, not to the active business unit ${activeBusinessUnit.code} - ${activeBusinessUnit.name}.`;
    }, [activeBusinessUnit, selectedBusinessUnitOption]);

    const lineItemForm = useForm<LineItemFormData>({
        year,
        business_unit_id: selectedBusinessUnitId,
        department_id: departmentsForSelectedBusinessUnit[0]?.id ?? 0,
        action_code: '',
        transaction_date: defaultTransactionDate,
        due_date: '',
        is_estimated_date: false,
        amount: 0,
        description: '',
        keterangan: '',
        notes: '',
    });
    const { data, setData, post: submitLineItem, patch: updateExistingLineItem, processing, errors } = lineItemForm;

    const selectedDepartment = useMemo(() => {
        return departments.find((department) => department.id === data.department_id) ?? null;
    }, [data.department_id, departments]);

    const filteredCategoryOptions = useMemo<CategoryOption[]>(() => {
        if (!selectedDepartment) {
            return [];
        }

        const uniqueActions = selectedDepartment.actions.reduce<typeof selectedDepartment.actions>((actions, action) => {
            if (actions.some((existingAction) => existingAction.code === action.code)) {
                return actions;
            }

            return [...actions, action];
        }, []);

        return uniqueActions
            .filter((action) => action.flow_type === flowType)
            .sort((left, right) => {
                const leftPrefix = extractDepartmentCode(left.code, selectedDepartment.code);
                const rightPrefix = extractDepartmentCode(right.code, selectedDepartment.code);
                const prefixComparison = leftPrefix.localeCompare(rightPrefix);

                if (prefixComparison !== 0) {
                    return prefixComparison;
                }

                const leftLabel = normalizeActionLabel(left.label);
                const rightLabel = normalizeActionLabel(right.label);
                const labelComparison = leftLabel.localeCompare(rightLabel);

                if (labelComparison !== 0) {
                    return labelComparison;
                }

                return left.code.localeCompare(right.code);
            })
            .map((action) => ({
                value: action.code,
                label: formatCategoryLabel(
                    action.code,
                    action.label,
                    selectedDepartment.code,
                    selectedDepartment.business_unit_code,
                    Boolean(activeBusinessUnit && selectedDepartment.business_unit_id && selectedDepartment.business_unit_id !== activeBusinessUnit.id)
                ),
                flow_type: action.flow_type,
                department_id: selectedDepartment.id,
            }));
    }, [activeBusinessUnit, flowType, selectedDepartment]);

    useEffect(() => {
        if (!businessUnitOptions.length) {
            return;
        }

        if (businessUnitOptions.some((option) => option.id === selectedBusinessUnitId)) {
            return;
        }

        const fallbackBusinessUnitId = businessUnitOptions[0].id;
        setSelectedBusinessUnitId(fallbackBusinessUnitId);
        setData('business_unit_id', fallbackBusinessUnitId);
    }, [businessUnitOptions, selectedBusinessUnitId, setData]);

    useEffect(() => {
        setData('business_unit_id', selectedBusinessUnitId);

        if (!departmentsForSelectedBusinessUnit.length) {
            setData('department_id', 0);
            setData('action_code', '');
            return;
        }

        if (departmentsForSelectedBusinessUnit.some((department) => department.id === data.department_id)) {
            return;
        }

        setData('department_id', departmentsForSelectedBusinessUnit[0].id);
    }, [data.department_id, departmentsForSelectedBusinessUnit, selectedBusinessUnitId, setData]);

    useEffect(() => {
        if (!filteredCategoryOptions.length) {
            setData('action_code', '');
            return;
        }

        if (filteredCategoryOptions.some((option) => option.value === data.action_code)) {
            return;
        }

        setData('action_code', filteredCategoryOptions[0].value);
    }, [data.action_code, filteredCategoryOptions, setData]);

    useEffect(() => {
        setData('year', year);
        setData('transaction_date', defaultTransactionDate);
    }, [defaultTransactionDate, selectedMonth, setData, year]);

    const handleLineItemSubmit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        if (editingLineItemId !== null) {
            updateExistingLineItem(route('cashflow-projection.line-items.update', { lineItem: editingLineItemId }), {
                preserveScroll: true,
                onSuccess: () => {
                    setIsProjectionDialogOpen(false);
                    resetForm();
                },
            });

            return;
        }

        submitLineItem(route('cashflow-projection.line-items.store'), {
            preserveScroll: true,
            onSuccess: () => {
                setIsProjectionDialogOpen(false);
                resetForm();
            },
        });
    };

    const handleCategoryChange = (categoryCode: string) => {
        const category = filteredCategoryOptions.find((option) => option.value === categoryCode);
        if (!category) return;
        setData('action_code', category.value);
        setData('department_id', category.department_id);
    };

    const handleBusinessUnitChange = (businessUnitId: number) => {
        setSelectedBusinessUnitId(businessUnitId);
    };

    const handleDepartmentChange = (departmentId: number) => {
        setData('department_id', departmentId);
    };

    const resetImportForm = () => {
        importPreview.reset();
    };

    const closeImportDialog = () => {
        setIsImportDialogOpen(false);
        resetImportForm();
    };

    const handleImportConfirm = async () => {
        if (await importPreview.confirmImport()) {
            setIsImportDialogOpen(false);
        }
    };

    const resetForm = () => {
        setEditingLineItemId(null);
        setFlowType('in');
        setSelectedBusinessUnitId(businessUnitOptions[0]?.id ?? 0);
        setData({
            year,
            business_unit_id: businessUnitOptions[0]?.id ?? 0,
            department_id: departments.filter((department) => department.business_unit_id === (businessUnitOptions[0]?.id ?? 0))[0]?.id ?? 0,
            action_code: '',
            transaction_date: defaultTransactionDate,
            due_date: '',
            is_estimated_date: false,
            amount: 0,
            description: '',
            keterangan: '',
            notes: '',
        });
    };

    const openProjectionDialog = () => {
        resetForm();
        setIsProjectionDialogOpen(true);
    };

    const closeProjectionDialog = () => {
        setIsProjectionDialogOpen(false);
        resetForm();
    };

    const beginEdit = (lineItemId: number) => {
        const lineItem = lineItemRows.find((item) => item.id === lineItemId);
        if (!lineItem) {
            return;
        }

        setEditingLineItemId(lineItemId);
        setFlowType(lineItem.flow_type);
        setSelectedBusinessUnitId(lineItem.business_unit_id ?? 0);
        setData({
            year,
            business_unit_id: lineItem.business_unit_id ?? 0,
            department_id: lineItem.department_id,
            action_code: lineItem.action_code,
            transaction_date: lineItem.transaction_date,
            due_date: lineItem.due_date ?? '',
            is_estimated_date: lineItem.is_estimated_date,
            amount: lineItem.amount,
            description: lineItem.description,
            keterangan: lineItem.keterangan ?? '',
            notes: lineItem.notes ?? '',
        });
        setIsProjectionDialogOpen(true);
    };

    return (
        <>
            <Head title="Cashflow Entries" />

            <div className="w-full px-6 py-8 lg:px-8 2xl:px-10">
                <div className="mx-auto w-full max-w-screen-2xl space-y-8">
                    <EntriesPageHeader
                        year={year}
                        selectedMonth={selectedMonth}
                        cashflowImportFlash={cashflowImportFlash}
                        onAddClick={openProjectionDialog}
                        onImportClick={() => setIsImportDialogOpen(true)}
                    />

                    <EntriesTable
                        lineItems={lineItemRows}
                        pagination={lineItems}
                        year={year}
                        selectedMonth={selectedMonth}
                        search={filters.search}
                        activeBusinessUnitId={activeBusinessUnit?.id}
                        formatCategoryLabel={formatCategoryLabel}
                        onEdit={beginEdit}
                        onDelete={entryDeletion.openDeleteDialog}
                        onBulkDelete={entryDeletion.openBulkDeleteDialog}
                    />
                </div>
            </div>

            <Dialog open={isProjectionDialogOpen} onClose={closeProjectionDialog} className="max-w-3xl p-0">
                <DialogHeader className="border-b border-slate-100 px-6 py-5" onClose={closeProjectionDialog}>
                    <div>
                        <DialogTitle>{editingLineItemId !== null ? 'Edit Projection' : 'Add Projection'}</DialogTitle>
                        <p className="mt-1 text-sm text-slate-500">
                            Match the import experience: classify type, department, action, description, keterangan, and amount before saving.
                        </p>
                    </div>
                </DialogHeader>
                <DialogContent className="mt-0 max-h-[calc(100vh-9rem)] overflow-y-auto px-6 pb-6 pt-5">
                    <AddProjectionCard
                        lineItemData={data}
                        lineItemProcessing={processing}
                        flowType={flowType}
                        businessUnitOptions={businessUnitOptions}
                        departmentOptions={departmentsForSelectedBusinessUnit.map((department) => ({
                            id: department.id,
                            name: department.name,
                        }))}
                        categoryOptions={filteredCategoryOptions}
                        isEditing={editingLineItemId !== null}
                        selectedDepartmentName={selectedDepartment?.name}
                        selectedBusinessUnitCode={selectedBusinessUnitOption?.code}
                        businessUnitNotice={linkedBusinessUnitNotice}
                        fieldErrors={errors}
                        onFlowTypeChange={setFlowType}
                        onBusinessUnitChange={handleBusinessUnitChange}
                        onDepartmentChange={handleDepartmentChange}
                        onCategoryChange={handleCategoryChange}
                        onCancelEdit={closeProjectionDialog}
                        onFieldChange={setData}
                        onSubmit={handleLineItemSubmit}
                    />
                </DialogContent>
            </Dialog>

            <ConfirmDialog
                isOpen={entryDeletion.deleteDialogState.isOpen}
                onClose={entryDeletion.closeDeleteDialog}
                onConfirm={entryDeletion.confirmDelete}
                title="Delete Entry"
                message={entryDeletion.deleteDialogState.label
                    ? `Delete this entry "${entryDeletion.deleteDialogState.label}"? This action cannot be undone. Perubahan ini akan mempengaruhi data cashflow dan dashboard.`
                    : 'Delete this entry? This action cannot be undone. Perubahan ini akan mempengaruhi data cashflow dan dashboard.'}
                confirmText="Delete"
                variant="danger"
                isLoading={entryDeletion.isDeleting}
            />

            <ConfirmDialog
                isOpen={entryDeletion.isBulkDeleteDialogOpen}
                onClose={entryDeletion.closeBulkDeleteDialog}
                onConfirm={entryDeletion.confirmBulkDelete}
                title="Delete Selected Entries"
                message={`Delete ${entryDeletion.bulkDeleteIds.length} selected entries? This action cannot be undone. Perubahan ini akan mempengaruhi data cashflow dan dashboard.`}
                confirmText="Delete"
                variant="danger"
                isLoading={entryDeletion.isDeleting}
            />

            <ImportEntriesDialog
                open={isImportDialogOpen}
                processing={importPreview.processing}
                selectedFileName={importPreview.file?.name ?? null}
                preview={importPreview.preview}
                departments={departments}
                flashImport={cashflowImportFlash}
                fileError={importPreview.errors.file ?? importPreview.previewError ?? undefined}
                onClose={closeImportDialog}
                onFileChange={importPreview.setFile}
                onSubmit={importPreview.previewImport}
                onConfirm={handleImportConfirm}
                onReviewRow={importPreview.updatePreviewRow}
            />
        </>
    );
}
