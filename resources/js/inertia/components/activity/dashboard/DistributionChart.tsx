import { PieChart, Pie, Cell, ResponsiveContainer, Tooltip as RechartsTooltip } from 'recharts';

const COLORS = ['#6366f1', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899'];

interface DistributionItem {
    name: string;
    color: string;
    value: number;
    [key: string]: string | number; // Index signature for recharts compatibility
}

interface DistributionChartProps {
    data: DistributionItem[];
    total: number;
}

export function DistributionChart({ data, total }: DistributionChartProps) {
    return (
        <div className="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
            <h3 className="text-base font-bold text-gray-900 mb-4">Task Distribution</h3>
            <div className="h-[180px] relative">
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
