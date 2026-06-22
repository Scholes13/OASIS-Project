export type CashflowChartRow = {
    key: string;
    label: string;
    inflow: number;
    outflow: number;
    closingBalance?: number;
};

export type CashflowLineSeriesRow = {
    key: string;
    label: string;
    baseline: 0;
    inflow?: number;
    outflow?: number;
    net?: number;
    closingBalance?: number;
};

export type CashflowLineViewMode = 'day' | 'week' | 'month';

export function buildLineSeries(rows: CashflowChartRow[], viewMode: CashflowLineViewMode = 'day'): CashflowLineSeriesRow[] {
    return rows.map((row) => ({
        key: row.key,
        label: row.label,
        inflow: row.inflow,
        outflow: row.outflow * -1,
        net: row.inflow - row.outflow,
        closingBalance: row.closingBalance,
        baseline: 0,
    }));
}

export function resolveLineChartDomain(rows: CashflowChartRow[], viewMode: CashflowLineViewMode = 'day'): [number, number] {
    if (rows.length === 0) {
        return [-1, 1];
    }

    const values = viewMode === 'week'
        ? rows.map((row) => row.inflow - row.outflow)
        : rows.flatMap((row) => [row.inflow, row.outflow * -1]);

    const minValue = Math.min(0, ...values);
    const maxValue = Math.max(0, ...values);

    if (minValue === 0 && maxValue === 0) {
        return [-1, 1];
    }

    const padding = Math.max((maxValue - minValue) * 0.08, 1);

    return [Math.floor(minValue - padding), Math.ceil(maxValue + padding)];
}

export function resolveAggregateGradientOffset(domain: [number, number]): number {
    const [minValue, maxValue] = domain;

    if (minValue >= 0) {
        return 1;
    }

    if (maxValue <= 0) {
        return 0;
    }

    return maxValue / (maxValue - minValue);
}
