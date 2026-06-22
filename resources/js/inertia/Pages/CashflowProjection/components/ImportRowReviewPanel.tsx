import { useEffect, useMemo, useState } from 'react';
import type { ImportPreviewRow } from '@/types/cashflowImport';
import type { DepartmentOption } from '../types';

type ImportRowReviewPanelProps = {
    row: ImportPreviewRow;
    departments: DepartmentOption[];
    onSave: (row: ImportPreviewRow) => void;
};

const inputClasses = 'w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-800 outline-none focus:border-primary focus:ring-1 focus:ring-primary';

export default function ImportRowReviewPanel({ row, departments, onSave }: ImportRowReviewPanelProps) {
    const [businessUnitCode, setBusinessUnitCode] = useState(row.business_unit_code);
    const [departmentId, setDepartmentId] = useState<number>(() => {
        return departments.find((department) => department.business_unit_code === row.business_unit_code && department.code === row.department_code)?.id ?? 0;
    });
    const [flowType, setFlowType] = useState<'in' | 'out'>(row.flow_type ?? 'out');
    const [actionCode, setActionCode] = useState(row.action_code ?? '');
    const [transactionDate, setTransactionDate] = useState(row.transaction_date ?? '');
    const [dueDate, setDueDate] = useState(row.due_date ?? '');
    const [description, setDescription] = useState(row.description ?? '');
    const [keterangan, setKeterangan] = useState(row.keterangan ?? '');
    const [amount, setAmount] = useState(String(row.amount ?? ''));

    useEffect(() => {
        setBusinessUnitCode(row.business_unit_code);
        setDepartmentId(departments.find((department) => department.business_unit_code === row.business_unit_code && department.code === row.department_code)?.id ?? 0);
        setFlowType(row.flow_type ?? 'out');
        setActionCode(row.action_code ?? '');
        setTransactionDate(row.transaction_date ?? '');
        setDueDate(row.due_date ?? '');
        setDescription(row.description ?? '');
        setKeterangan(row.keterangan ?? '');
        setAmount(String(row.amount ?? ''));
    }, [departments, row]);

    const businessUnits = useMemo(() => {
        return departments.reduce<Array<{ code: string; name: string }>>((options, department) => {
            if (!department.business_unit_code || options.some((option) => option.code === department.business_unit_code)) return options;
            return [...options, { code: department.business_unit_code, name: department.business_unit_name ?? department.business_unit_code }];
        }, []);
    }, [departments]);

    const filteredDepartments = useMemo(() => departments.filter((department) => department.business_unit_code === businessUnitCode), [businessUnitCode, departments]);
    const selectedDepartment = useMemo(() => filteredDepartments.find((department) => department.id === departmentId) ?? null, [departmentId, filteredDepartments]);
    const actionOptions = useMemo(() => selectedDepartment?.actions.filter((action) => action.flow_type === flowType) ?? [], [flowType, selectedDepartment]);

    useEffect(() => {
        if (filteredDepartments.length === 0) return;
        if (filteredDepartments.some((department) => department.id === departmentId)) return;
        setDepartmentId(filteredDepartments[0].id);
    }, [departmentId, filteredDepartments]);

    useEffect(() => {
        if (actionOptions.length === 0) return;
        if (actionOptions.some((action) => action.code === actionCode)) return;
        setActionCode(actionOptions[0].code);
    }, [actionCode, actionOptions]);

    const save = () => {
        const action = actionOptions.find((option) => option.code === actionCode);
        if (!selectedDepartment || !action || !transactionDate || !description || !amount) return;

        onSave({
            ...row,
            status: row.match ? 'update' : 'new',
            business_unit_code: businessUnitCode,
            department_code: selectedDepartment.code,
            action_code: action.code,
            action_label: action.label,
            flow_type: flowType,
            transaction_date: transactionDate,
            due_date: dueDate || null,
            description,
            keterangan: keterangan || null,
            amount: Number(amount),
            errors: [],
        });
    };

    return (
        <aside className="rounded-2xl border border-blue-100 bg-blue-50/40 p-4">
            <div className="mb-4">
                <p className="text-xs font-semibold uppercase tracking-[0.16em] text-blue-700">Review Needed</p>
                <h3 className="mt-1 text-lg font-semibold text-slate-900">Review Row {row.row_number}</h3>
                <p className="mt-1 text-xs leading-5 text-slate-600">Fix classification, then save this row so confirm can continue.</p>
            </div>
            <div className="grid gap-3">
                <label className="space-y-1.5 text-sm font-medium text-slate-700">
                    Business Unit
                    <select className={inputClasses} value={businessUnitCode} onChange={(event) => setBusinessUnitCode(event.target.value)}>
                        {businessUnits.map((option) => (
                            <option key={option.code} value={option.code}>{option.code} - {option.name}</option>
                        ))}
                    </select>
                </label>
                <label className="space-y-1.5 text-sm font-medium text-slate-700">
                    Review Department
                    <select aria-label="Review department" className={inputClasses} value={departmentId} onChange={(event) => setDepartmentId(Number(event.target.value))}>
                        {filteredDepartments.map((department) => (
                            <option key={department.id} value={department.id}>{department.name}</option>
                        ))}
                    </select>
                </label>
                <label className="space-y-1.5 text-sm font-medium text-slate-700">
                    Flow
                    <select className={inputClasses} value={flowType} onChange={(event) => setFlowType(event.target.value as 'in' | 'out')}>
                        <option value="in">Inflow</option>
                        <option value="out">Outflow</option>
                    </select>
                </label>
                <label className="space-y-1.5 text-sm font-medium text-slate-700">
                    Review Action
                    <select aria-label="Review action" className={inputClasses} value={actionCode} onChange={(event) => setActionCode(event.target.value)}>
                        {actionOptions.map((action) => (
                            <option key={action.code} value={action.code}>{action.label}</option>
                        ))}
                    </select>
                </label>
                <label className="space-y-1.5 text-sm font-medium text-slate-700">
                    Payment Date
                    <input className={inputClasses} type="date" value={transactionDate} onChange={(event) => setTransactionDate(event.target.value)} />
                </label>
                <label className="space-y-1.5 text-sm font-medium text-slate-700">
                    Due Date
                    <input className={inputClasses} type="date" value={dueDate} onChange={(event) => setDueDate(event.target.value)} />
                </label>
                <label className="space-y-1.5 text-sm font-medium text-slate-700">
                    Description
                    <textarea className={inputClasses} rows={4} value={description} onChange={(event) => setDescription(event.target.value)} />
                </label>
                <label className="space-y-1.5 text-sm font-medium text-slate-700">
                    Keterangan
                    <input className={inputClasses} value={keterangan} onChange={(event) => setKeterangan(event.target.value)} />
                </label>
                <label className="space-y-1.5 text-sm font-medium text-slate-700">
                    Amount
                    <input className={inputClasses} inputMode="numeric" value={amount} onChange={(event) => setAmount(event.target.value.replace(/\D/g, ''))} />
                </label>
                <button
                    type="button"
                    className="rounded-lg bg-[#16599c] px-4 py-2 text-sm font-semibold text-white hover:bg-[#124a82]"
                    onClick={save}
                >
                    Save Reviewed Row
                </button>
            </div>
        </aside>
    );
}
