import { AnimatePresence, motion } from 'framer-motion';
import { StatsCardSkeleton } from '@/components/ui/skeleton';
import { cn } from '@/lib/utils';

interface MetricCard {
    label: string;
    value: number | string;
    valueClass: string;
    bgClass: string;
}

interface AdminMetricCardsProps {
    isFiltering: boolean;
    metricCards: MetricCard[];
}

const fadeUp = {
    hidden: { opacity: 0, y: 16 },
    show: { opacity: 1, y: 0, transition: { duration: 0.4 } },
};

const stagger = {
    show: { transition: { staggerChildren: 0.06 } },
};

export default function AdminMetricCards({
    isFiltering,
    metricCards,
}: AdminMetricCardsProps) {
    return (
        <AnimatePresence mode="wait">
            {isFiltering ? (
                <motion.div
                    key="skeleton"
                    initial={{ opacity: 0 }}
                    animate={{ opacity: 1 }}
                    exit={{ opacity: 0 }}
                    className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3"
                >
                    {Array.from({ length: 6 }).map((_, index) => (
                        <StatsCardSkeleton key={index} />
                    ))}
                </motion.div>
            ) : (
                <motion.div
                    key="data"
                    variants={stagger}
                    initial="hidden"
                    animate="show"
                    className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3"
                >
                    {metricCards.map((metric) => (
                        <motion.div
                            key={metric.label}
                            variants={fadeUp}
                        >
                            <div className={cn('border border-gray-200/80 rounded-lg p-4', metric.bgClass)}>
                                <p className="text-[13px] font-medium text-gray-500">{metric.label}</p>
                                <p className={cn('mt-1 text-3xl font-bold tabular-nums', metric.valueClass)}>
                                    {metric.value}
                                </p>
                            </div>
                        </motion.div>
                    ))}
                </motion.div>
            )}
        </AnimatePresence>
    );
}
