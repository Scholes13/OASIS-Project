import { motion } from 'framer-motion';
import { Activity, AlertTriangle, ArrowDownLeft, ArrowUpRight, Wallet } from 'lucide-react';

type SummaryTone = 'positive' | 'negative' | 'neutral';

export type StatsCardItem = {
    label: string;
    value: string;
    caption: string;
    tone: SummaryTone;
};

interface StatsCardsProps {
    cards: StatsCardItem[];
}

function cardClassName(tone: SummaryTone, index: number): string {
    if (tone === 'negative' && index === 0) {
        return 'rounded-xl border border-red-200 bg-gradient-to-br from-red-50/80 to-orange-50/50 p-6 shadow-sm flex flex-col ring-1 ring-red-100/60';
    }

    return 'rounded-xl border border-slate-200/60 bg-white p-6 shadow-sm flex flex-col';
}

function captionClassName(tone: SummaryTone): string {
    if (tone === 'positive') return 'text-emerald-600';
    if (tone === 'negative') return 'text-red-500/90';
    return 'text-muted-foreground';
}

function valueClassName(tone: SummaryTone): string {
    if (tone === 'negative') return 'text-red-600';
    return 'text-slate-900';
}

export default function StatsCards({ cards }: StatsCardsProps) {
    const cardTransition = { duration: 0.25, ease: 'easeOut' as const };

    const iconMap = [
        { Icon: Wallet, iconBox: 'bg-[#dbeafe] text-[#1e40af]', iconBoxNegative: 'bg-red-100 text-red-600' },
        { Icon: ArrowDownLeft, iconBox: 'bg-[#ecfdf5] text-[#047857]' },
        { Icon: ArrowUpRight, iconBox: 'bg-red-50 text-red-600' },
        { Icon: Activity, iconBox: 'bg-[#e0f2fe] text-[#0369a1]' },
    ];

    return (
        <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
            {cards.map((card, index) => {
                const iconConfig = iconMap[index] ?? iconMap[iconMap.length - 1];
                const isWarning = card.tone === 'negative' && index === 0;
                const iconBoxClass = isWarning && iconConfig.iconBoxNegative
                    ? iconConfig.iconBoxNegative
                    : iconConfig.iconBox;

                return (
                <motion.div
                    key={card.label}
                    initial={{ opacity: 0, y: 12 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{ ...cardTransition, delay: index * 0.05 }}
                    className={cardClassName(card.tone, index)}
                >
                    <div className="mb-4 flex items-center gap-3">
                        <div className={`relative flex h-10 w-10 items-center justify-center rounded-lg ${iconBoxClass}`}>
                            <iconConfig.Icon className="h-5 w-5" />
                            {isWarning && (
                                <motion.span
                                    initial={{ scale: 0 }}
                                    animate={{ scale: 1 }}
                                    transition={{ type: 'spring', stiffness: 400, damping: 15, delay: 0.2 }}
                                    className="absolute -right-1.5 -top-1.5 flex h-5 w-5 items-center justify-center rounded-full bg-red-500 shadow-sm"
                                >
                                    <AlertTriangle className="h-3 w-3 text-white" />
                                </motion.span>
                            )}
                        </div>
                        <div className="flex items-center gap-2">
                            <p className={`text-sm font-medium ${isWarning ? 'text-red-700' : 'text-slate-500'}`}>{card.label}</p>
                            {isWarning && (
                                <motion.span
                                    initial={{ opacity: 0, x: -8 }}
                                    animate={{ opacity: 1, x: 0 }}
                                    transition={{ duration: 0.3, delay: 0.15 }}
                                    className="inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-red-700"
                                >
                                    Below threshold
                                </motion.span>
                            )}
                        </div>
                    </div>
                    <p className={`mb-2 text-3xl font-bold tracking-tight ${valueClassName(card.tone)}`}>{card.value}</p>
                    <p className={`mt-auto text-sm leading-relaxed ${captionClassName(card.tone)}`}>{card.caption}</p>
                </motion.div>
                );
            })}
        </div>
    );
}
