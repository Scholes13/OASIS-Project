import { motion } from 'framer-motion';
import { Pencil, PlusCircle, X } from 'lucide-react';
import type { LineItemFormData } from '../types';

type FlowType = 'in' | 'out';

interface CategoryOption {
    value: string;
    label: string;
}

interface BusinessUnitOption {
    id: number;
    code: string;
    name: string;
}

interface DepartmentSelectOption {
    id: number;
    name: string;
}

interface AddProjectionCardProps {
    lineItemData: LineItemFormData;
    lineItemProcessing: boolean;
    flowType: FlowType;
    businessUnitOptions: BusinessUnitOption[];
    departmentOptions: DepartmentSelectOption[];
    categoryOptions: CategoryOption[];
    isEditing: boolean;
    selectedDepartmentName?: string;
    selectedBusinessUnitCode?: string;
    fieldErrors: Partial<Record<keyof LineItemFormData, string>>;
    onFlowTypeChange: (flowType: FlowType) => void;
    onBusinessUnitChange: (businessUnitId: number) => void;
    onDepartmentChange: (departmentId: number) => void;
    onCategoryChange: (categoryCode: string) => void;
    onCancelEdit: () => void;
    onFieldChange: <K extends keyof LineItemFormData>(field: K, value: LineItemFormData[K]) => void;
    onSubmit: (event: React.FormEvent<HTMLFormElement>) => void;
}

const inputClasses =
    'w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-foreground outline-none transition-colors focus:border-primary focus:ring-1 focus:ring-primary [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none';

const selectClasses =
    'w-full rounded-lg border border-slate-200 bg-white px-3 py-2 pr-8 text-sm text-foreground outline-none transition-colors focus:border-primary focus:ring-1 focus:ring-primary appearance-none bg-[length:16px_16px] bg-[position:right_8px_center] bg-no-repeat bg-[url("data:image/svg+xml,%3Csvg%20xmlns%3D%27http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%27%20viewBox%3D%270%200%2020%2020%27%20fill%3D%27%2364748b%27%3E%3Cpath%20fill-rule%3D%27evenodd%27%20d%3D%27M5.23%207.21a.75.75%200%20011.06.02L10%2011.168l3.71-3.938a.75.75%200%20111.08%201.04l-4.25%204.5a.75.75%200%2001-1.08%200l-4.25-4.5a.75.75%200%2001.02-1.06z%27%20clip-rule%3D%27evenodd%27%2F%3E%3C%2Fsvg%3E")]';

function formatAmountInput(amount: number): string {
    if (!Number.isFinite(amount) || amount <= 0) {
        return amount === 0 ? '0' : '';
    }

    return new Intl.NumberFormat('id-ID', {
        maximumFractionDigits: 0,
    }).format(amount);
}

function parseAmountInput(value: string): number {
    const digitsOnly = value.replace(/\D/g, '');

    return digitsOnly === '' ? 0 : Number(digitsOnly);
}

export default function AddProjectionCard({
    lineItemData,
    lineItemProcessing,
    flowType,
    businessUnitOptions,
    departmentOptions,
    categoryOptions,
    isEditing,
    selectedDepartmentName,
    selectedBusinessUnitCode,
    fieldErrors,
    onFlowTypeChange,
    onBusinessUnitChange,
    onDepartmentChange,
    onCategoryChange,
    onCancelEdit,
    onFieldChange,
    onSubmit,
}: AddProjectionCardProps) {
    const showEmptyDepartmentState = businessUnitOptions.length > 0 && departmentOptions.length === 0;

    return (
        <motion.section
            className="rounded-2xl border border-border bg-card p-6 shadow-[0_1px_2px_rgba(15,23,42,0.04)]"
            initial={{ opacity: 0, y: 12 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.25, ease: 'easeOut', delay: 0.12 }}
        >
            <div className="mb-5 flex items-start justify-between gap-4">
                <div>
                    <h2 className="text-xl font-semibold text-foreground">{isEditing ? 'Edit Projection' : 'Add Projection'}</h2>
                    <p className="mt-1 text-sm text-muted-foreground">
                        {isEditing ? 'Update an existing projection entry' : 'Simulate future cashflows'}
                    </p>
                </div>
                {isEditing && (
                    <button
                        type="button"
                        onClick={onCancelEdit}
                        className="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 px-3 py-1.5 text-sm font-medium text-slate-600 transition hover:bg-slate-50"
                    >
                        <X className="h-4 w-4" />
                        Cancel
                    </button>
                )}
            </div>

            <form onSubmit={onSubmit} className="space-y-4">
                {isEditing && selectedDepartmentName && selectedBusinessUnitCode && (
                    <div className="rounded-lg border border-blue-200 bg-blue-50/80 px-4 py-3 text-sm text-blue-800">
                        Editing {selectedDepartmentName} entry for {selectedBusinessUnitCode}
                    </div>
                )}

                <div className="grid grid-cols-2 gap-3">
                    <div>
                        <label htmlFor="transaction-date" className="mb-1.5 block text-sm font-medium text-slate-700">Date</label>
                        <input
                            id="transaction-date"
                            type="date"
                            className={inputClasses}
                            value={lineItemData.transaction_date}
                            onChange={(event) => onFieldChange('transaction_date', event.target.value)}
                        />
                        {fieldErrors.transaction_date && <p className="mt-1 text-xs text-red-500">{fieldErrors.transaction_date}</p>}
                    </div>

                    <div>
                        <label htmlFor="flow-type" className="mb-1.5 block text-sm font-medium text-slate-700">Type</label>
                        <select
                            id="flow-type"
                            className={selectClasses}
                            value={flowType}
                            onChange={(event) => onFlowTypeChange(event.target.value as FlowType)}
                        >
                            <option value="in">Inflow</option>
                            <option value="out">Outflow</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label htmlFor="business-unit" className="mb-1.5 block text-sm font-medium text-slate-700">Business Unit</label>
                    <select
                        id="business-unit"
                        className={selectClasses}
                        value={lineItemData.business_unit_id}
                        disabled={businessUnitOptions.length === 0}
                        onChange={(event) => onBusinessUnitChange(Number(event.target.value))}
                    >
                        {businessUnitOptions.length === 0 && <option value={0}>No business unit available</option>}
                        {businessUnitOptions.map((option) => (
                            <option key={option.id} value={option.id}>
                                {option.code} - {option.name}
                            </option>
                        ))}
                    </select>
                </div>

                <div>
                    <label htmlFor="department" className="mb-1.5 block text-sm font-medium text-slate-700">Department</label>
                    <select
                        id="department"
                        className={selectClasses}
                        value={lineItemData.department_id}
                        disabled={departmentOptions.length === 0}
                        onChange={(event) => onDepartmentChange(Number(event.target.value))}
                    >
                        {departmentOptions.length === 0 && <option value={0}>No department available</option>}
                        {departmentOptions.map((option) => (
                            <option key={option.id} value={option.id}>
                                {option.name}
                            </option>
                        ))}
                    </select>
                    {showEmptyDepartmentState && (
                        <p className="mt-1 text-xs text-amber-600">No departments available for this business unit.</p>
                    )}
                </div>

                <div>
                    <label htmlFor="category" className="mb-1.5 block text-sm font-medium text-slate-700">Category</label>
                    <select
                        id="category"
                        className={selectClasses}
                        value={lineItemData.action_code}
                        disabled={categoryOptions.length === 0}
                        onChange={(event) => onCategoryChange(event.target.value)}
                    >
                        {categoryOptions.length === 0 && <option value="">No category available</option>}
                        {categoryOptions.map((option) => (
                            <option key={option.value} value={option.value}>
                                {option.label}
                            </option>
                        ))}
                    </select>
                    {fieldErrors.action_code && <p className="mt-1 text-xs text-red-500">{fieldErrors.action_code}</p>}
                </div>

                <div>
                    <label htmlFor="entry-name" className="mb-1.5 block text-sm font-medium text-slate-700">Entry Name</label>
                    <input
                        id="entry-name"
                        type="text"
                        className={inputClasses}
                        placeholder="e.g. Q4 Server Costs"
                        value={lineItemData.description}
                        onChange={(event) => onFieldChange('description', event.target.value)}
                    />
                    {fieldErrors.description && <p className="mt-1 text-xs text-red-500">{fieldErrors.description}</p>}
                </div>

                <div>
                    <label htmlFor="amount" className="mb-1.5 block text-sm font-medium text-slate-700">Amount</label>
                    <input
                        id="amount"
                        type="text"
                        inputMode="numeric"
                        className={inputClasses}
                        placeholder="0"
                        value={formatAmountInput(lineItemData.amount)}
                        onChange={(event) => onFieldChange('amount', parseAmountInput(event.target.value))}
                    />
                    {fieldErrors.amount && <p className="mt-1 text-xs text-red-500">{fieldErrors.amount}</p>}
                </div>

                <div>
                    <label htmlFor="due-date" className="mb-1.5 block text-sm font-medium text-slate-700">Due Date (Optional)</label>
                    <input
                        id="due-date"
                        type="date"
                        className={inputClasses}
                        value={lineItemData.due_date}
                        onChange={(event) => onFieldChange('due_date', event.target.value)}
                    />
                    {fieldErrors.due_date && <p className="mt-1 text-xs text-red-500">{fieldErrors.due_date}</p>}
                </div>

                <div>
                    <label htmlFor="notes" className="mb-1.5 block text-sm font-medium text-slate-700">Notes (Optional)</label>
                    <textarea
                        id="notes"
                        className="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-foreground outline-none transition-colors focus:border-primary focus:ring-1 focus:ring-primary"
                        rows={3}
                        value={lineItemData.notes}
                        onChange={(event) => onFieldChange('notes', event.target.value)}
                    />
                    {fieldErrors.notes && <p className="mt-1 text-xs text-red-500">{fieldErrors.notes}</p>}
                </div>

                <div className="flex items-center justify-between">
                    <span className="text-sm font-medium text-slate-700">Estimated Date</span>
                    <button
                        type="button"
                        className={`cfp-switch ${lineItemData.is_estimated_date ? 'active' : ''}`}
                        onClick={() => onFieldChange('is_estimated_date', !lineItemData.is_estimated_date)}
                    />
                </div>

                <button
                    type="submit"
                    disabled={lineItemProcessing}
                    className="mt-2 flex w-full items-center justify-center gap-2 rounded-lg bg-[#16599c] px-5 py-2.5 text-sm font-medium text-white transition-colors hover:bg-[#124a82] disabled:opacity-50"
                >
                    {isEditing ? <Pencil className="h-4 w-4" /> : <PlusCircle className="h-4 w-4" />}
                    {lineItemProcessing ? 'Saving...' : isEditing ? 'Save Changes' : 'Add to Projection'}
                </button>
            </form>
        </motion.section>
    );
}
