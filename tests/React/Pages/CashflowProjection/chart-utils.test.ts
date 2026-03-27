import { describe, expect, it } from 'vitest';
import { buildLineSeries, resolveAggregateGradientOffset, resolveLineChartDomain } from '@/Pages/CashflowProjection/chart-utils';

describe('cashflow chart utils', () => {
    it('maps outflow values below the zero baseline for the line chart', () => {
        const series = buildLineSeries([
            { key: '2026-03-01', label: '01 Mar', inflow: 250000, outflow: 100000 },
            { key: '2026-03-02', label: '02 Mar', inflow: 0, outflow: 325000 },
        ]);

        expect(series).toEqual([
            { key: '2026-03-01', label: '01 Mar', inflow: 250000, outflow: -100000, baseline: 0 },
            { key: '2026-03-02', label: '02 Mar', inflow: 0, outflow: -325000, baseline: 0 },
        ]);
    });

    it('always returns a y-axis domain that includes zero and the negative outflow range', () => {
        const domain = resolveLineChartDomain([
            { key: '2026-03-01', label: '01 Mar', inflow: 250000, outflow: 100000 },
            { key: '2026-03-02', label: '02 Mar', inflow: 0, outflow: 325000 },
        ]);

        expect(domain[0]).toBeLessThanOrEqual(-325000);
        expect(domain[1]).toBeGreaterThanOrEqual(250000);
        expect(domain[0]).toBeLessThanOrEqual(0);
        expect(domain[1]).toBeGreaterThanOrEqual(0);
    });

    it('builds a single net cashflow line for aggregate week view', () => {
        const series = buildLineSeries([
            { key: '2026-w10', label: '01 Mar - 07 Mar', inflow: 550000, outflow: 325000 },
            { key: '2026-w11', label: '08 Mar - 14 Mar', inflow: 100000, outflow: 280000 },
        ], 'week');

        expect(series).toEqual([
            { key: '2026-w10', label: '01 Mar - 07 Mar', net: 225000, baseline: 0 },
            { key: '2026-w11', label: '08 Mar - 14 Mar', net: -180000, baseline: 0 },
        ]);
    });

    it('builds separate inflow and outflow lines for month view', () => {
        const series = buildLineSeries([
            { key: '1', label: 'Jan', inflow: 550000, outflow: 325000 },
            { key: '2', label: 'Feb', inflow: 100000, outflow: 280000 },
        ], 'month');

        expect(series).toEqual([
            { key: '1', label: 'Jan', inflow: 550000, outflow: -325000, baseline: 0 },
            { key: '2', label: 'Feb', inflow: 100000, outflow: -280000, baseline: 0 },
        ]);
    });

    it('positions the aggregate gradient stop at the zero baseline', () => {
        const offset = resolveAggregateGradientOffset([-180000, 225000]);

        expect(offset).toBeCloseTo(0.5556, 3);
    });
});
