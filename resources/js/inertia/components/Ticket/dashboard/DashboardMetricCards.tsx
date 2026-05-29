import { AnimatePresence, motion } from 'framer-motion';

interface DashboardMetricCardsProps {
    isFiltering: boolean;
    total: number;
    waiting: number;
    inProgress: number;
    done: number;
    slaBreachCount: number;
}

const fadeUp = { hidden: { opacity: 0, y: 16 }, show: { opacity: 1, y: 0, transition: { duration: 0.4 } } };
const stagger = { show: { transition: { staggerChildren: 0.06 } } };

export function DashboardMetricCards({
    isFiltering,
    total,
    waiting,
    inProgress,
    done,
    slaBreachCount,
}: DashboardMetricCardsProps) {
    return (
        <AnimatePresence mode="wait">
            {isFiltering ? (
                <motion.div
                    initial={{ opacity: 0 }}
                    animate={{ opacity: 1 }}
                    exit={{ opacity: 0 }}
                    className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3"
                >
                    {Array.from({ length: 5 }).map((_, index) => (
                        <div
                            key={index}
                            className="h-24 bg-gray-100 animate-pulse rounded-lg"
                        />
                    ))}
                </motion.div>
            ) : (
                <motion.div
                    variants={stagger}
                    initial="hidden"
                    animate="show"
                    className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3"
                >
                    <MetricCard
                        label="Total Tickets"
                        value={total}
                        tone="bg-slate-50/80 text-gray-900"
                    />
                    <MetricCard
                        label="Menunggu"
                        value={waiting}
                        tone="bg-amber-50/60 text-amber-600"
                    />
                    <MetricCard
                        label="Dalam Proses"
                        value={inProgress}
                        tone="bg-blue-50/60 text-blue-600"
                    />
                    <MetricCard
                        label="Selesai"
                        value={done}
                        tone="bg-emerald-50/60 text-emerald-600"
                    />
                    <MetricCard
                        label="SLA Breach"
                        value={slaBreachCount}
                        tone="bg-red-50/60 text-red-600"
                    />
                </motion.div>
            )}
        </AnimatePresence>
    );
}

function MetricCard({ label, value, tone }: { label: string; value: number; tone: string }) {
    const [backgroundClass, valueClass] = tone.split(' ');

    return (
        <motion.div variants={fadeUp}>
            <div className={`border border-gray-200/80 rounded-lg p-4 ${backgroundClass}`}>
                <p className="text-[13px] font-medium text-gray-500">{label}</p>
                <p className={`mt-1 text-3xl font-bold tabular-nums ${valueClass}`}>
                    {value}
                </p>
            </div>
        </motion.div>
    );
}
