import React from 'react';
import { LucideIcon } from 'lucide-react';
import { cn } from '@/lib/utils';

interface StatCardProps {
    title: string;
    value: number | string;
    icon: LucideIcon;
    trend?: {
        value: number;
        direction: 'up' | 'down';
    };
    color?: 'indigo' | 'emerald' | 'amber' | 'red' | 'blue' | 'purple';
    loading?: boolean;
}

const colorClasses = {
    indigo: 'bg-blue-50 text-blue-700',
    emerald: 'bg-emerald-100 text-emerald-600',
    amber: 'bg-amber-100 text-amber-600',
    red: 'bg-red-100 text-red-600',
    blue: 'bg-blue-100 text-blue-600',
    purple: 'bg-purple-100 text-purple-600',
};

const trendColorClasses = {
    up: 'text-emerald-600',
    down: 'text-red-600',
};

export function StatCard({
    title,
    value,
    icon: Icon,
    trend,
    color = 'indigo',
    loading = false,
}: StatCardProps) {
    if (loading) {
        return (
            <div className="bg-white rounded-xl border border-gray-100 p-6 animate-pulse">
                <div className="flex items-center justify-between">
                    <div className="flex-1">
                        <div className="h-4 bg-gray-200 rounded w-24 mb-2"></div>
                        <div className="h-8 bg-gray-200 rounded w-16"></div>
                    </div>
                    <div className="w-12 h-12 bg-gray-200 rounded-lg"></div>
                </div>
            </div>
        );
    }

    return (
        <div className="bg-white rounded-xl border border-gray-100 p-6 hover:shadow-md transition-shadow">
            <div className="flex items-center justify-between">
                <div>
                    <p className="text-sm text-gray-600 mb-1">{title}</p>
                    <p className="text-2xl font-bold text-gray-900">{value}</p>
                    {trend && (
                        <p className={cn('text-sm mt-2 flex items-center gap-1', trendColorClasses[trend.direction])}>
                            {trend.direction === 'up' ? '↑' : '↓'} {trend.value}% from last month
                        </p>
                    )}
                </div>
                <div className={cn('p-3 rounded-lg', colorClasses[color])}>
                    <Icon className="w-6 h-6" strokeWidth={1.5} />
                </div>
            </div>
        </div>
    );
}
