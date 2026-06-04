import { formatCurrency } from '../utils';

type ProjectionChartTooltipProps = {
    active?: boolean;
    payload?: Array<{ name?: string; value?: number; payload?: { inflow?: number; outflow?: number; net?: number } }>;
    label?: string;
    minimumBalanceThreshold?: number;
};

export default function ProjectionChartTooltip({
    active,
    payload,
    label,
    minimumBalanceThreshold,
}: ProjectionChartTooltipProps) {
    if (!active || !payload?.length) return null;

    const activeRow = payload.find((entry) => entry?.payload)?.payload;
    const balancePayload = payload.find((entry) => entry.name === 'Saldo Proyeksi');
    const inflowValue = payload.find((entry) => entry.name === 'Inflow')?.value ?? activeRow?.inflow ?? 0;
    const outflowValue = payload.find((entry) => entry.name === 'Outflow')?.value ?? activeRow?.outflow ?? 0;
    const netValue = payload.find((entry) => entry.name === 'Net Cashflow')?.value ?? activeRow?.net ?? 0;
    const hasMovementData = inflowValue !== 0 || outflowValue !== 0;
    const balanceValue = Number(balancePayload?.value ?? 0);

    return (
        <div className="min-w-[240px] rounded-xl border border-slate-200/80 bg-white/95 p-4 shadow-xl backdrop-blur-md">
            <p className="mb-3 text-[11px] font-bold uppercase tracking-widest text-slate-500">{label}</p>

            {balancePayload && (
                <div className="mb-4 rounded-lg bg-emerald-50/50 p-3 ring-1 ring-emerald-100/80">
                    <p className="mb-1 text-[11px] font-semibold uppercase tracking-wider text-emerald-600">Saldo Proyeksi</p>
                    <p className="text-xl font-bold tracking-tight text-slate-900">
                        {formatCurrency(Math.abs(balanceValue))}
                    </p>
                    {minimumBalanceThreshold !== undefined && (
                        <div className="mt-2.5 flex flex-col gap-1 border-t border-emerald-100 pt-2.5 text-[11px] font-medium">
                            <div className="flex items-center justify-between text-slate-600">
                                <span className="flex items-center gap-1.5">
                                    <span className="h-1 w-1 rounded-full bg-slate-400" />
                                    Minimum Balance
                                </span>
                                <span>{formatCurrency(minimumBalanceThreshold)}</span>
                            </div>
                            <div className="flex items-center justify-between">
                                <span className="flex items-center gap-1.5 text-slate-500">
                                    <span className={`h-1 w-1 rounded-full ${balanceValue < minimumBalanceThreshold ? 'bg-rose-500' : 'bg-emerald-500'}`} />
                                    Threshold Status
                                </span>
                                <span className={balanceValue < minimumBalanceThreshold ? 'font-semibold text-rose-600' : 'font-semibold text-emerald-600'}>
                                    {balanceValue < minimumBalanceThreshold ? 'Below Limit' : 'Above Limit'}
                                </span>
                            </div>
                        </div>
                    )}
                </div>
            )}

            {!balancePayload && netValue !== 0 && (
                <div className="mb-4 rounded-lg bg-blue-50/50 p-3 ring-1 ring-blue-100/80">
                    <p className="mb-1 text-[11px] font-semibold uppercase tracking-wider text-blue-600">Net Cashflow</p>
                    <p className="text-xl font-bold tracking-tight text-slate-900">
                        {formatCurrency(Math.abs(Number(netValue ?? 0)))}
                    </p>
                </div>
            )}

            {hasMovementData && (
                <div className={`${balancePayload || netValue !== 0 ? 'mt-3 border-t border-slate-100 pt-3' : ''} space-y-1`}>
                    <div className="mb-1.5 text-[11px] font-medium text-slate-500">Cash Movement</div>
                    <div className="flex items-center justify-between text-[11px]">
                        <span className="font-medium text-slate-500">Inflow</span>
                        <span className="font-semibold text-blue-600">{formatCurrency(Math.abs(Number(inflowValue ?? 0)))}</span>
                    </div>
                    <div className="flex items-center justify-between text-[11px]">
                        <span className="font-medium text-slate-500">Outflow</span>
                        <span className="font-semibold text-rose-600">{formatCurrency(Math.abs(Number(outflowValue ?? 0)))}</span>
                    </div>
                </div>
            )}
        </div>
    );
}
