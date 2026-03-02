export type ActionOption = {
    code: string;
    label: string;
    flow_type: 'in' | 'out';
};

export type DepartmentOption = {
    id: number;
    code: string;
    name: string;
    template_type: 'acc' | 'hr' | 'cfc' | 'standard';
    actions: ActionOption[];
};

export type LineItem = {
    id: number;
    department_id: number;
    department_code: string;
    department_name: string;
    flow_type: 'in' | 'out';
    action_code: string;
    action_label: string;
    transaction_date: string;
    due_date: string | null;
    amount: number;
    description: string;
    notes: string | null;
    is_estimated_date: boolean;
};

export type FinanceInput = {
    id: number;
    month: number;
    cash_on_hand: number;
    receivable_estimate: number;
    upcoming_event_revenue_estimate: number;
    capital_injection_estimate: number;
    other_income: number;
};

export type DailySummaryRow = {
    date: string;
    plus: number;
    minus: number;
    net: number;
};

export type MonthlySummaryRow = {
    month: number;
    plus: number;
    minus: number;
    finance_income: number;
    opening_balance: number;
    net: number;
    closing_balance: number;
    is_warning: boolean;
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
    dailySummary: DailySummaryRow[];
    monthlySummary: MonthlySummaryRow[];
    departments: DepartmentOption[];
    lineItems: LineItem[];
    financeInputs: FinanceInput[];
    permissions: {
        canManageFinance: boolean;
    };
}

export interface CashflowProjectionEntriesPageProps {
    year: number;
    selectedMonth: number;
    departments: DepartmentOption[];
    lineItems: LineItem[];
}

export interface CashflowProjectionSettingsPageProps {
    year: number;
    selectedMonth: number;
    financeInputs: FinanceInput[];
}

export type LineItemFormData = {
    year: number;
    department_id: number;
    action_code: string;
    transaction_date: string;
    due_date: string;
    is_estimated_date: boolean;
    amount: number;
    description: string;
    notes: string;
};

export type FinanceFormData = {
    year: number;
    month: number;
    cash_on_hand: number;
    receivable_estimate: number;
    upcoming_event_revenue_estimate: number;
    capital_injection_estimate: number;
    other_income: number;
};
