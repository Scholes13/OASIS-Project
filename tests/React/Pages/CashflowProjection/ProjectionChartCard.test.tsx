import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi } from 'vitest';
import ProjectionChartCard from '@/Pages/CashflowProjection/components/ProjectionChartCard';

const yAxisProps: Array<Record<string, unknown>> = [];
const referenceLineProps: Array<Record<string, unknown>> = [];
const lineProps: Array<Record<string, unknown>> = [];
const barProps: Array<Record<string, unknown>> = [];
const legendProps: Array<Record<string, unknown>> = [];
const tooltipProps: Array<Record<string, unknown>> = [];

vi.mock('recharts', () => {
    const React = require('react');

    const createComponent = (name: string, capture?: (props: Record<string, unknown>) => void) => {
        return ({ children, ...props }: Record<string, unknown>) => {
            capture?.(props);

            return React.createElement('div', { 'data-testid': name }, children);
        };
    };

    return {
        ResponsiveContainer: createComponent('ResponsiveContainer'),
        ComposedChart: createComponent('ComposedChart'),
        LineChart: createComponent('LineChart'),
        BarChart: createComponent('BarChart'),
        CartesianGrid: createComponent('CartesianGrid'),
        Tooltip: createComponent('Tooltip', (props) => tooltipProps.push(props)),
        XAxis: createComponent('XAxis'),
        YAxis: createComponent('YAxis', (props) => yAxisProps.push(props)),
        ReferenceLine: createComponent('ReferenceLine', (props) => referenceLineProps.push(props)),
        Bar: createComponent('Bar', (props) => barProps.push(props)),
        Line: createComponent('Line', (props) => lineProps.push(props)),
        Legend: createComponent('Legend', (props) => legendProps.push(props)),
        Cell: createComponent('Cell'),
    };
});

describe('ProjectionChartCard', () => {
    it('lets users switch from balance trend to inflow and red outflow volume', async () => {
        yAxisProps.length = 0;
        referenceLineProps.length = 0;
        lineProps.length = 0;
        barProps.length = 0;
        legendProps.length = 0;
        tooltipProps.length = 0;

        const user = userEvent.setup();

        render(
            <ProjectionChartCard
                title="Cashflow Projection"
                subtitle="Granular trend for Apr 2026."
                chartData={[
                    { key: '2026-04-05', label: '05', inflow: 113_000_000, outflow: 0, closingBalance: 1_024_400_000 },
                    { key: '2026-04-19', label: '19', inflow: 0, outflow: 85_000_000, closingBalance: 940_400_000 },
                ]}
                viewMode="day"
                onViewModeChange={() => {}}
                dayPills={[
                    { key: '2026-04-05', label: 'Min, 05' },
                    { key: '2026-04-19', label: 'Min, 19' },
                ]}
                selectedDayKey="all"
                onDayFilterChange={() => {}}
                minimumBalanceThreshold={200_000_000}
            />
        );

        expect(screen.getByRole('button', { name: 'Saldo Proyeksi' })).toHaveAttribute('aria-pressed', 'true');

        await user.click(screen.getByRole('button', { name: 'Inflow / Outflow' }));

        expect(screen.getByRole('button', { name: 'Inflow / Outflow' })).toHaveAttribute('aria-pressed', 'true');
        expect(lineProps).toHaveLength(1);
        expect(barProps).toHaveLength(2);
        expect(barProps[0]).toMatchObject({ dataKey: 'inflow', name: 'Inflow', fill: '#3b82f6' });
        expect(barProps[1]).toMatchObject({ dataKey: 'outflow', name: 'Outflow', fill: '#ef4444' });
        expect(legendProps.at(-1)).toMatchObject({ iconSize: 10 });

        const tooltipContent = tooltipProps.at(-1)?.content as ((props: Record<string, unknown>) => React.ReactNode) | undefined;

        render(
            <>{tooltipContent?.({
                active: true,
                label: '19 Apr 2026',
                payload: [
                    {
                        name: 'Outflow',
                        value: -85_000_000,
                        payload: {
                            inflow: 0,
                            outflow: -85_000_000,
                            net: -85_000_000,
                        },
                    },
                ],
            })}</>,
        );

        expect(screen.getByText('Net Cashflow')).toBeInTheDocument();
        expect(screen.queryByText('Threshold Status')).not.toBeInTheDocument();
    });

    it('keeps the balance chart focused while showing balance guardrails', () => {
        yAxisProps.length = 0;
        referenceLineProps.length = 0;
        lineProps.length = 0;
        barProps.length = 0;
        legendProps.length = 0;
        tooltipProps.length = 0;

        render(
            <ProjectionChartCard
                title="Cashflow Projection"
                subtitle="Granular trend for Apr 2026."
                chartData={[
                    { key: '2026-04-05', label: '05', inflow: 113_000_000, outflow: 0, closingBalance: 1_024_400_000 },
                    { key: '2026-04-17', label: '17', inflow: 1_000_000, outflow: 0, closingBalance: 1_025_400_000 },
                    { key: '2026-04-19', label: '19', inflow: 0, outflow: 85_000_000, closingBalance: 940_400_000 },
                ]}
                viewMode="day"
                onViewModeChange={() => {}}
                dayPills={[
                    { key: '2026-04-05', label: 'Min, 05' },
                    { key: '2026-04-17', label: 'Jum, 17' },
                    { key: '2026-04-19', label: 'Min, 19' },
                ]}
                selectedDayKey="all"
                onDayFilterChange={() => {}}
                minimumBalanceThreshold={200_000_000}
            />
        );

        const movementAxis = yAxisProps.find((props) => !('yAxisId' in props));
        const balanceAxis = yAxisProps.find((props) => props.yAxisId === 'balance');
        const minimumLine = referenceLineProps.find((props) => props.yAxisId === 'balance' && props.y === 200_000_000);
        const zeroLine = referenceLineProps.find((props) => props.yAxisId === 'balance' && props.y === 0);

        expect(movementAxis?.hide).toBe(true);
        expect(balanceAxis).toBeTruthy();
        expect(minimumLine).toMatchObject({ yAxisId: 'balance', y: 200_000_000, stroke: '#f59e0b' });
        expect(zeroLine).toMatchObject({ yAxisId: 'balance', y: 0, stroke: '#ef4444' });
        expect(Array.isArray(balanceAxis?.domain)).toBe(true);
        expect((balanceAxis?.domain as [number, number])[0]).toBeLessThanOrEqual(0);
        expect(lineProps).toHaveLength(1);
        expect(lineProps[0]).toMatchObject({
            dataKey: 'closingBalance',
            name: 'Saldo Proyeksi',
            yAxisId: 'balance',
        });
        expect(barProps).toHaveLength(0);
        expect(legendProps.at(-1)).toMatchObject({ iconSize: 10 });

        const tooltipContent = tooltipProps.at(-1)?.content as ((props: Record<string, unknown>) => React.ReactNode) | undefined;

        expect(tooltipContent).toBeTypeOf('function');

        render(
            <>{tooltipContent?.({
                active: true,
                label: '05 Apr 2026',
                payload: [
                    {
                        name: 'Saldo Proyeksi',
                        value: 1_024_400_000,
                        payload: {
                            inflow: 113_000_000,
                            outflow: -85_000_000,
                            net: 28_000_000,
                        },
                    },
                ],
            })}</>,
        );

        expect(screen.getByText('Minimum Balance')).toBeInTheDocument();
        expect(screen.getByText('Threshold Status')).toBeInTheDocument();
        expect(screen.getByText('Above Limit')).toBeInTheDocument();
        expect(screen.getByText('Cash Movement')).toBeInTheDocument();
        expect(screen.getByText('Inflow')).toBeInTheDocument();
        expect(screen.getByText('Outflow')).toBeInTheDocument();
    });

    it('shows the real minimum balance line and red zero balance line on the balance axis', () => {
        yAxisProps.length = 0;
        referenceLineProps.length = 0;
        lineProps.length = 0;
        barProps.length = 0;
        legendProps.length = 0;
        tooltipProps.length = 0;

        render(
            <ProjectionChartCard
                title="Cashflow Projection"
                subtitle="Granular trend for Apr 2026."
                chartData={[
                    { key: '2026-04-05', label: '05', inflow: 20_000_000, outflow: 0, closingBalance: 390_000_000 },
                    { key: '2026-04-17', label: '17', inflow: 5_000_000, outflow: 0, closingBalance: 340_000_000 },
                    { key: '2026-04-19', label: '19', inflow: 0, outflow: 85_000_000, closingBalance: 260_000_000 },
                ]}
                viewMode="day"
                onViewModeChange={() => {}}
                dayPills={[
                    { key: '2026-04-05', label: 'Min, 05' },
                    { key: '2026-04-17', label: 'Jum, 17' },
                    { key: '2026-04-19', label: 'Min, 19' },
                ]}
                selectedDayKey="all"
                onDayFilterChange={() => {}}
                minimumBalanceThreshold={200_000_000}
            />
        );

        const balanceAxis = yAxisProps.find((props) => props.yAxisId === 'balance');
        const minimumLine = referenceLineProps.find((props) => props.yAxisId === 'balance' && props.y === 200_000_000);
        const zeroLine = referenceLineProps.find((props) => props.yAxisId === 'balance' && props.y === 0);

        expect(minimumLine).toMatchObject({
            yAxisId: 'balance',
            y: 200_000_000,
            stroke: '#f59e0b',
        });
        expect(zeroLine).toMatchObject({
            yAxisId: 'balance',
            y: 0,
            stroke: '#ef4444',
        });
        expect((balanceAxis?.domain as [number, number])[0]).toBeLessThanOrEqual(0);
    });
});
