import { motion } from 'framer-motion';
import { ChevronDown, PlusCircle } from 'lucide-react';
import type { LineItemFormData } from '../types';

type FlowType = 'in' | 'out';

interface CategoryOption {
    value: string;
    label: string;
}

interface AddProjectionCardProps {
    lineItemData: LineItemFormData;
    lineItemProcessing: boolean;
    flowType: FlowType;
    categoryOptions: CategoryOption[];
    fieldErrors: Partial<Record<keyof LineItemFormData, string>>;
    onFlowTypeChange: (flowType: FlowType) => void;
    onCategoryChange: (categoryCode: string) => void;
    onFieldChange: <K extends keyof LineItemFormData>(field: K, value: LineItemFormData[K]) => void;
    onSubmit: (event: React.FormEvent<HTMLFormElement>) => void;
}

const inputClasses =
    'w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-foreground outline-none transition-colors focus:border-primary focus:ring-1 focus:ring-primary [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none';

export default function AddProjectionCard({
    lineItemData,
    lineItemProcessing,
    flowType,
    categoryOptions,
    fieldErrors,
    onFlowTypeChange,
    onCategoryChange,
    onFieldChange,
    onSubmit,
}: AddProjectionCardProps) {
    return (
        <motion.section
            className="rounded-2xl border border-border bg-card p-6 shadow-[0_1px_2px_rgba(15,23,42,0.04)]"
            initial={{ opacity: 0, y: 12 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.25, ease: 'easeOut', delay: 0.12 }}
        >
            <div className="mb-5">
                <h2 className="text-xl font-semibold text-foreground">Add Projection</h2>
                <p className="mt-1 text-sm text-muted-foreground">Simulate future cashflows</p>
            </div>

            <form onSubmit={onSubmit} className="space-y-4">
                <div>
                    <label className="mb-1.5 block text-sm font-medium text-slate-700">Entry Name</label>
                    <input
                        type="text"
                        className={inputClasses}
                        placeholder="e.g. Q4 Server Costs"
                        value={lineItemData.description}
                        onChange={(event) => onFieldChange('description', event.target.value)}
                    />
                    {fieldErrors.description && <p className="mt-1 text-xs text-red-500">{fieldErrors.description}</p>}
                </div>

                <div>
                    <label className="mb-1.5 block text-sm font-medium text-slate-700">Amount</label>
                    <input
                        type="number"
                        min={0}
                        className={inputClasses}
                        placeholder="0.00"
                        value={lineItemData.amount}
                        onChange={(event) => onFieldChange('amount', Number(event.target.value))}
                    />
                    {fieldErrors.amount && <p className="mt-1 text-xs text-red-500">{fieldErrors.amount}</p>}
                </div>

                <div className="grid grid-cols-2 gap-3">
                    <div>
                        <label className="mb-1.5 block text-sm font-medium text-slate-700">Date</label>
                        <input
                            type="date"
                            className={inputClasses}
                            value={lineItemData.transaction_date}
                            onChange={(event) => onFieldChange('transaction_date', event.target.value)}
                        />
                        {fieldErrors.transaction_date && <p className="mt-1 text-xs text-red-500">{fieldErrors.transaction_date}</p>}
                    </div>

                    <div>
                        <label className="mb-1.5 block text-sm font-medium text-slate-700">Type</label>
                        <div className="relative">
                            <select
                                className={`${inputClasses} appearance-none pr-8`}
                                value={flowType}
                                onChange={(event) => onFlowTypeChange(event.target.value as FlowType)}
                            >
                                <option value="in">Inflow</option>
                                <option value="out">Outflow</option>
                            </select>
                            <ChevronDown className="pointer-events-none absolute right-2.5 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        </div>
                    </div>
                </div>

                <div>
                    <label className="mb-1.5 block text-sm font-medium text-slate-700">Category</label>
                    <div className="relative">
                        <select
                            className={`${inputClasses} appearance-none pr-8`}
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
                        <ChevronDown className="pointer-events-none absolute right-2.5 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    </div>
                    {fieldErrors.action_code && <p className="mt-1 text-xs text-red-500">{fieldErrors.action_code}</p>}
                </div>

                <div>
                    <label className="mb-1.5 block text-sm font-medium text-slate-700">Due Date (Optional)</label>
                    <input
                        type="date"
                        className={inputClasses}
                        value={lineItemData.due_date}
                        onChange={(event) => onFieldChange('due_date', event.target.value)}
                    />
                    {fieldErrors.due_date && <p className="mt-1 text-xs text-red-500">{fieldErrors.due_date}</p>}
                </div>

                <div>
                    <label className="mb-1.5 block text-sm font-medium text-slate-700">Notes (Optional)</label>
                    <textarea
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
                    <PlusCircle className="h-4 w-4" />
                    {lineItemProcessing ? 'Saving...' : 'Add to Projection'}
                </button>
            </form>
        </motion.section>
    );
}
