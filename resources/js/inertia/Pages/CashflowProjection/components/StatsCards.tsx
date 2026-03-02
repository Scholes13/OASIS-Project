import { motion } from 'framer-motion';
import { Activity, ArrowDownLeft, ArrowUpRight, Wallet } from 'lucide-react';

type TrendTone = 'up' | 'down' | 'neutral';

interface StatsCardsProps {
    totalBalanceLabel: string;
    dailyInflowLabel: string;
    dailyOutflowLabel: string;
    netCashflowLabel: string;
    totalTrendLabel: string;
    totalTrendTone: TrendTone;
    inflowTrendLabel: string;
    inflowTrendTone: TrendTone;
    outflowTrendLabel: string;
    outflowTrendTone: TrendTone;
    netTrendLabel: string;
}

function trendClassName(tone: TrendTone): string {
    if (tone === 'up') return 'text-emerald-600';
    if (tone === 'down') return 'text-red-500';
    return 'text-muted-foreground';
}

export default function StatsCards({
    totalBalanceLabel,
    dailyInflowLabel,
    dailyOutflowLabel,
    netCashflowLabel,
    totalTrendLabel,
    totalTrendTone,
    inflowTrendLabel,
    inflowTrendTone,
    outflowTrendLabel,
    outflowTrendTone,
    netTrendLabel,
}: StatsCardsProps) {
    const cardTransition = { duration: 0.25, ease: 'easeOut' as const };

    const cards = [
        {
            label: 'Total Balance',
            value: totalBalanceLabel,
            trend: totalTrendLabel,
            tone: totalTrendTone,
            Icon: Wallet,
            iconBox: 'bg-[#dbeafe] text-[#1e40af]',
            delay: 0,
        },
        {
            label: 'Daily Inflow',
            value: dailyInflowLabel,
            trend: inflowTrendLabel,
            tone: inflowTrendTone,
            Icon: ArrowDownLeft,
            iconBox: 'bg-[#ecfdf5] text-[#047857]',
            delay: 0.05,
        },
        {
            label: 'Daily Outflow',
            value: dailyOutflowLabel,
            trend: outflowTrendLabel,
            tone: outflowTrendTone,
            Icon: ArrowUpRight,
            iconBox: 'bg-red-50 text-red-600',
            delay: 0.1,
        },
        {
            label: 'Net Cashflow',
            value: netCashflowLabel,
            trend: netTrendLabel,
            tone: 'neutral' as TrendTone,
            Icon: Activity,
            iconBox: 'bg-[#e0f2fe] text-[#0369a1]',
            delay: 0.15,
        },
    ];

    return (
        <div className="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-4">
            {cards.map((card) => (
                <motion.div
                    key={card.label}
                    initial={{ opacity: 0, y: 12 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{ ...cardTransition, delay: card.delay }}
                    className="rounded-2xl border border-border bg-card p-5 shadow-[0_1px_2px_rgba(15,23,42,0.04)]"
                >
                    <div className="mb-4 flex h-10 w-10 items-center justify-center rounded-lg text-base">
                        <div className={`flex h-10 w-10 items-center justify-center rounded-lg ${card.iconBox}`}>
                            <card.Icon className="h-5 w-5" />
                        </div>
                    </div>
                    <p className="text-sm font-medium text-slate-500">{card.label}</p>
                    <p className="mt-1 text-3xl font-bold text-foreground">{card.value}</p>
                    <p className={`mt-2 text-xs ${trendClassName(card.tone)}`}>{card.trend}</p>
                </motion.div>
            ))}
        </div>
    );
}
