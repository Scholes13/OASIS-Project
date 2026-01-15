import { motion } from 'framer-motion';
import { Card } from '../ui/Card';
import type { TaskStats } from '@/types';

interface StatsCardsProps {
    stats: TaskStats;
    isLoading?: boolean;
}

interface StatCardProps {
    label: string;
    value: number;
    icon: React.ReactNode;
    color: string;
    bgColor: string;
}

function StatCard({ label, value, icon, color, bgColor }: StatCardProps) {
    return (
        <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.3 }}
        >
            <Card className="hover:shadow-md transition-shadow">
                <div className="flex items-center gap-4">
                    <div className={`p-3 rounded-lg ${bgColor}`}>
                        <span className={color}>{icon}</span>
                    </div>
                    <div>
                        <p className="text-sm text-gray-500">{label}</p>
                        <p className="text-2xl font-semibold text-gray-900">{value}</p>
                    </div>
                </div>
            </Card>
        </motion.div>
    );
}

export default function StatsCards({ stats, isLoading }: StatsCardsProps) {
    // Default values if stats is undefined
    const safeStats = stats ?? { total: 0, planned: 0, in_progress: 0, completed: 0, overdue: 0 };
    
    const statItems = [
        {
            label: 'Total',
            value: safeStats.total ?? 0,
            color: 'text-gray-600',
            bgColor: 'bg-gray-100',
            icon: (
                <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
            ),
        },
        {
            label: 'Planned',
            value: safeStats.planned ?? 0,
            color: 'text-blue-600',
            bgColor: 'bg-blue-100',
            icon: (
                <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            ),
        },
        {
            label: 'In Progress',
            value: safeStats.in_progress ?? 0,
            color: 'text-yellow-600',
            bgColor: 'bg-yellow-100',
            icon: (
                <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
            ),
        },
        {
            label: 'Completed',
            value: safeStats.completed ?? 0,
            color: 'text-green-600',
            bgColor: 'bg-green-100',
            icon: (
                <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            ),
        },
        {
            label: 'Overdue',
            value: safeStats.overdue ?? 0,
            color: 'text-red-600',
            bgColor: 'bg-red-100',
            icon: (
                <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            ),
        },
    ];

    if (isLoading) {
        return (
            <div className="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
                {Array.from({ length: 5 }).map((_, i) => (
                    <div key={i} className="bg-white rounded-xl shadow-sm border border-gray-200 p-4 animate-pulse">
                        <div className="flex items-center gap-4">
                            <div className="w-12 h-12 bg-gray-200 rounded-lg" />
                            <div className="flex-1">
                                <div className="h-3 bg-gray-200 rounded w-1/2 mb-2" />
                                <div className="h-6 bg-gray-200 rounded w-1/3" />
                            </div>
                        </div>
                    </div>
                ))}
            </div>
        );
    }

    return (
        <div className="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
            {statItems.map((item, index) => (
                <motion.div
                    key={item.label}
                    initial={{ opacity: 0, y: 20 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{ duration: 0.3, delay: index * 0.05 }}
                >
                    <StatCard {...item} />
                </motion.div>
            ))}
        </div>
    );
}
