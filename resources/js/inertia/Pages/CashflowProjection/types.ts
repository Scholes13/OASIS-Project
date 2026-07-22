import type { Money } from './money';

export type ActionOption = {
    code: string;
    label: string;
    flow_type: 'in' | 'out';
};

export type DepartmentOption = {
    id: number;
    code: string;
    name: string;
    business_unit_id?: number;
    business_unit_code?: string;
    business_unit_name?: string;
    template_type: 'acc' | 'tep' | 'hr' | 'cfc' | 'standard';
    actions: ActionOption[];
};

export type LinkedBusinessUnit = {
    id: number;
    code: string;
    name: string;
};

export type LinkedUnit = {
    id: number;
    business_unit_id: number;
    code: string;
    name: string;
};

export type LineItem = {
    id: number;
    department_id: number;
    department_code: string;
    department_name: string;
    business_unit_id?: number;
    business_unit_code?: string;
    business_unit_name?: string;
    flow_type: 'in' | 'out';
    action_code: string;
    action_label: string;
    transaction_date: string;
    due_date: string | null;
    amount: Money;
    no_dokumen: string | null;
    nama_vendor: string | null;
    description: string;
    keterangan: string | null;
    notes: string | null;
    is_estimated_date: boolean;
    creator_name?: string | null;
    creator_department_label?: string | null;
    has_edit_history?: boolean;
    updater_name?: string | null;
    updater_department_label?: string | null;
};

export type FinanceInput = {
    id: number;
    month: number;
    cash_on_hand: Money;
    receivable_estimate: Money;
    upcoming_event_revenue_estimate: Money;
    capital_injection_estimate: Money;
    other_income: Money;
    creator_name?: string | null;
    creator_department_label?: string | null;
    updater_name?: string | null;
    updater_department_label?: string | null;
};

export type DailySummaryRow = {
    date: string;
    plus: Money;
    minus: Money;
    net: Money;
};

export type MonthlySummaryRow = {
    month: number;
    plus: Money;
    minus: Money;
    finance_income: Money;
    opening_balance: Money;
    net: Money;
    closing_balance: Money;
    is_warning: boolean;
};

export type DashboardFilterMode = 'month' | 'year' | 'range';

export type DashboardFilters = {
    mode: DashboardFilterMode;
    year: number;
    month: number;
    start_date: string;
    end_date: string;
    available_years: number[];
};

export type DashboardSummary = {
    total_balance: Money;
    inflow: Money;
    outflow: Money;
    finance_income: Money;
    net_cashflow: Money;
};

export interface CashflowProjectionPageProps {
    year: number;
    selectedMonth: number;
    cycle: {
        id: number;
        status: 'draft' | 'published';
        year: number;
    };
    minimumBalanceGlobal: number;
    filters: DashboardFilters;
    summary: DashboardSummary;
    dailySummary: DailySummaryRow[];
    monthlySummary: MonthlySummaryRow[];
    departments: DepartmentOption[];
    lineItems: LineItem[];
    financeInputs: FinanceInput[];
    permissions: {
        canManageFinance: boolean;
    };
    scope: 'own' | 'consolidated';
    linkedBusinessUnits: LinkedBusinessUnit[];
}

export interface CashflowProjectionEntriesPageProps {
    year: number;
    selectedMonth: number;
    filters: {
        search: string;
    };
    departments: DepartmentOption[];
    lineItems: {
        data: LineItem[];
        meta: {
            current_page: number;
            last_page: number;
            per_page: number;
            total: number;
        };
        links: {
            first: string | null;
            last: string | null;
            prev: string | null;
            next: string | null;
        };
    };
}

export interface CashflowProjectionSettingsPageProps {
    year: number;
    selectedMonth: number;
    financeInputs: FinanceInput[];
    linkedUnits: LinkedUnit[];
    availableBusinessUnits: LinkedBusinessUnit[];
}

export type LineItemFormData = {
    year: number;
    business_unit_id: number;
    department_id: number;
    action_code: string;
    transaction_date: string;
    due_date: string;
    is_estimated_date: boolean;
    amount: Money;
    description: string;
    keterangan: string;
    notes: string;
};

export type FinanceFormData = {
    year: number;
    month: number;
    cash_on_hand: Money;
    receivable_estimate: Money;
    upcoming_event_revenue_estimate: Money;
    capital_injection_estimate: Money;
    other_income: Money;
};
