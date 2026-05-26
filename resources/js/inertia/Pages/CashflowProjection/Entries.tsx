import { Head, router, useForm, usePage } from '@inertiajs/react';
import { type FormEvent, useEffect, useMemo, useState } from 'react';
import './cashflow-dashboard.css';
import AddProjectionCard from './components/AddProjectionCard';
import EntriesPageHeader from './components/EntriesPageHeader';
import EntriesTable from './components/EntriesTable';
import ImportEntriesDialog from './components/ImportEntriesDialog';
import type { CashflowProjectionEntriesPageProps, LineItemFormData } from './types';
import { ConfirmDialog } from '@/components/ui/ConfirmDialog';
import { showToast } from '@/components/ui/toast';
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
    departments,
    lineItems,
}: CashflowProjectionEntriesPageProps) {
    const pageProps = usePage<PageProps>().props;
    const { currentBusinessUnit: activeBusinessUnit, flash } = pageProps;
    const cashflowImportFlash = flash?.cashflow_import;
    const [flowType, setFlowType] = useState<FlowType>('in');
    const [editingLineItemId, setEditingLineItemId] = useState<number | null>(null);
    const [isDeleting, setIsDeleting] = useState(false);
    const [isImportDialogOpen, setIsImportDialogOpen] = useState(false);
    const [deleteDialogState, setDeleteDialogState] = useState<{
        isOpen: boolean;
        lineItemId: number | null;
        label: string;
    }>({
        isOpen: false,
        lineItemId: null,
        label: '',
    });

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
        notes: '',
    });
    const { data, setData, post: submitLineItem, patch: updateExistingLineItem, processing, errors } = lineItemForm;

    const importForm = useForm<{
        file: File | null;
        context_year: number;
        context_month: number;
    }>({
        file: null,
        context_year: year,
        context_month: selectedMonth,
    });
    const {
        data: importData,
        setData: setImportData,
        post: submitImport,
        processing: importProcessing,
        errors: importErrors,
    } = importForm;

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

    useEffect(() => {
        setImportData('context_year', year);
        setImportData('context_month', selectedMonth);
    }, [selectedMonth, setImportData, year]);

    const handleLineItemSubmit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        if (editingLineItemId !== null) {
            updateExistingLineItem(route('cashflow-projection.line-items.update', { lineItem: editingLineItemId }), {
                preserveScroll: true,
            });

            return;
        }

        submitLineItem(route('cashflow-projection.line-items.store'), { preserveScroll: true });
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
        setImportData({
            file: null,
            context_year: year,
            context_month: selectedMonth,
        });
    };

    const closeImportDialog = () => {
        setIsImportDialogOpen(false);
        resetImportForm();
    };

    const handleImportSubmit = () => {
        if (!importData.file) {
            return;
        }

        submitImport(route('cashflow-projection.entries.import'), {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => {
                setIsImportDialogOpen(false);
                resetImportForm();
            },
        });
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
            notes: '',
        });
    };

    const beginEdit = (lineItemId: number) => {
        const lineItem = lineItems.find((item) => item.id === lineItemId);
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
            notes: lineItem.notes ?? '',
        });
    };

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

    return (
        <>
            <Head title="Cashflow Entries" />

            <div className="w-full px-6 py-8 lg:px-8 2xl:px-10">
                <div className="mx-auto w-full max-w-screen-2xl space-y-8">
                    <EntriesPageHeader
                        year={year}
                        selectedMonth={selectedMonth}
                        cashflowImportFlash={cashflowImportFlash}
                        onImportClick={() => setIsImportDialogOpen(true)}
                    />

                    <div className="grid grid-cols-1 gap-6 xl:grid-cols-3">
                        {/* Left column — Add Projection Form */}
                        <div className="space-y-6">
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
                                onCancelEdit={resetForm}
                                onFieldChange={setData}
                                onSubmit={handleLineItemSubmit}
                            />
                        </div>

                        <EntriesTable
                            lineItems={lineItems}
                            year={year}
                            selectedMonth={selectedMonth}
                            activeBusinessUnitId={activeBusinessUnit?.id}
                            formatCategoryLabel={formatCategoryLabel}
                            onEdit={beginEdit}
                            onDelete={openDeleteDialog}
                        />
                    </div>
                </div>
            </div>

            <ConfirmDialog
                isOpen={deleteDialogState.isOpen}
                onClose={closeDeleteDialog}
                onConfirm={confirmDelete}
                title="Delete Entry"
                message={deleteDialogState.label ? `Delete this entry "${deleteDialogState.label}"? This action cannot be undone.` : 'Delete this entry? This action cannot be undone.'}
                confirmText="Delete"
                variant="danger"
                isLoading={isDeleting}
            />

            <ImportEntriesDialog
                open={isImportDialogOpen}
                processing={importProcessing}
                selectedFileName={importData.file?.name ?? null}
                flashImport={cashflowImportFlash}
                fileError={importErrors.file}
                onClose={closeImportDialog}
                onFileChange={(file) => setImportData('file', file)}
                onSubmit={handleImportSubmit}
            />
        </>
    );
}
