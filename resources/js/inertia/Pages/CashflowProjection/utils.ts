import { monthOptions } from './constants';

export function formatCurrency(value: number | string): string {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(Number(value));
}

export function formatMonthLabel(month: number): string {
    return monthOptions.find((item) => item.value === month)?.label ?? `M${month}`;
}
