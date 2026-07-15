export type Money = string;

export function moneyToMinor(value: Money | number): bigint {
    const normalized = String(value || 0);
    const match = normalized.match(/^(\d+)(?:\.(\d{0,2}))?$/);
    if (!match) return 0n;

    return BigInt(match[1]) * 100n + BigInt((match[2] ?? '').padEnd(2, '0'));
}

export function minorToMoney(value: bigint): Money {
    const sign = value < 0n ? '-' : '';
    const absolute = value < 0n ? -value : value;

    return `${sign}${absolute / 100n}.${String(absolute % 100n).padStart(2, '0')}`;
}

export function addMoney(...values: Array<Money | number>): Money {
    return minorToMoney(values.reduce((total, value) => total + moneyToMinor(value), 0n));
}

export function moneyToChartNumber(value: Money | number): number {
    return Number(value);
}
