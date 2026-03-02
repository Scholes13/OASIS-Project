import { PieChart, Pie, Cell, ResponsiveContainer, Tooltip as RechartsTooltip } from 'recharts';
import { useState } from 'react';
import { ChevronDown } from 'lucide-react';

const COLORS = ['#6366f1', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899'];

type PeriodFilter = 'today' | 'week' | 'month' | 'year' | 'all';

const periodLabels: Record<PeriodFilter, string> = {
    today: 'Today',
    week: 'This Week',
    month: 'This Month',
    year: 'This Year',
    all: 'All Time',
};

interface DistributionItem {
    name: string;
    color: string;
    value: number;
    [key: string]: string | number; // Index signature for recharts compatibility
}

interface DistributionChartProps {
    data: DistributionItem[];
    total: number;
    onPeriodChange?: (period: PeriodFilter) => void;
    currentPeriod?: PeriodFilter;
    isLoading?: boolean;
}

export function DistributionChart({ 
    data, 
    total, 
    onPeriodChange, 
    currentPeriod = 'all',
    isLoading = false 
}: DistributionChartProps) {
    const [isOpen, setIsOpen] = useState(false);

    const handlePeriodSelect = (period: PeriodFilter) => {
        setIsOpen(false);
        onPeriodChange?.(period);
    };

    return (
        <div className="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
            <div className="flex items-center justify-between mb-4">
                <h3 className="text-base font-bold text-gray-900">Task Distribution</h3>
                {onPeriodChange && (
                    <div className="relative">
                        <button
                            onClick={() => setIsOpen(!isOpen)}
                            className="flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium text-gray-600 bg-gray-50 hover:bg-gray-100 rounded-lg border border-gray-200 transition-colors"
                        >
                            {periodLabels[currentPeriod]}
                            <ChevronDown className={`h-3.5 w-3.5 transition-transform ${isOpen ? 'rotate-180' : ''}`} />
                        </button>
                        {isOpen && (
                            <>
                                <div 
                                    className="fixed inset-0 z-10" 
                                    onClick={() => setIsOpen(false)}
                                />
                                <div className="absolute right-0 mt-1 w-32 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-20">
                                    {(Object.keys(periodLabels) as PeriodFilter[]).map((period) => (
                                        <button
                                            key={period}
                                            onClick={() => handlePeriodSelect(period)}
                                            className={`w-full px-3 py-1.5 text-left text-xs font-medium transition-colors ${
                                                currentPeriod === period
                                                    ? 'bg-blue-50 text-blue-700'
                                                    : 'text-gray-600 hover:bg-gray-50'
                                            }`}
                                        >
                                            {periodLabels[period]}
                                        </button>
                                    ))}
                                </div>
                            </>
                        )}
                    </div>
                )}
            </div>
            <div className={`h-[180px] relative ${isLoading ? 'opacity-50' : ''}`}>
                <ResponsiveContainer width="100%" height="100%">
                    <PieChart>
                        <Pie 
                            data={data} 
                            cx="50%" 
                            cy="50%" 
                            innerRadius={55} 
                            outerRadius={75} 
                            paddingAngle={4} 
                            dataKey="value" 
                            cornerRadius={4}
                        >
                            {data?.map((entry, index) => (
                                <Cell 
                                    key={`cell-${index}`} 
                                    fill={entry.color || COLORS[index % COLORS.length]} 
                                    strokeWidth={0} 
                                />
                            ))}
                        </Pie>
                        <RechartsTooltip 
                            contentStyle={{ 
                                borderRadius: '8px', 
                                border: 'none', 
                                fontSize: '12px', 
                                boxShadow: '0 4px 6px -1px rgb(0 0 0 / 0.1)' 
                            }} 
                        />
                    </PieChart>
                </ResponsiveContainer>
                <div className="absolute inset-0 flex items-center justify-center pointer-events-none flex-col">
                    <span className="text-3xl font-bold text-gray-900 leading-none">{total}</span>
                    <span className="text-sm text-gray-500 mt-1">Total</span>
                </div>
                {isLoading && (
                    <div className="absolute inset-0 flex items-center justify-center">
                        <div className="w-6 h-6 border-2 border-primary border-t-transparent rounded-full animate-spin" />
                    </div>
                )}
            </div>
            <div className="mt-4 space-y-2">
                {data?.slice(0, 4).map((d, i) => (
                    <div key={i} className="flex items-center justify-between text-sm">
                        <div className="flex items-center text-gray-600">
                            <div 
                                className="w-2.5 h-2.5 rounded-full mr-2" 
                                style={{ background: d.color || COLORS[i % COLORS.length] }}
                            />
                            <span className="truncate max-w-[120px]">{d.name}</span>
                        </div>
                        <span className="font-medium text-gray-900 bg-gray-50 px-1.5 py-0.5 rounded">
                            {total > 0 ? Math.round((d.value / total) * 100) : 0}%
                        </span>
                    </div>
                ))}
            </div>
        </div>
    );
}


export type { PeriodFilter, DistributionItem };
