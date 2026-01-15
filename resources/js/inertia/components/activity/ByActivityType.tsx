import type { ActivityByType } from '@/types';

interface ByActivityTypeProps {
    data: ActivityByType[];
}

const defaultColors: Record<string, { bg: string; dot: string }> = {
    Meeting: { bg: 'bg-blue-100', dot: 'bg-blue-500' },
    'Web Development': { bg: 'bg-purple-100', dot: 'bg-purple-500' },
    Event: { bg: 'bg-pink-100', dot: 'bg-pink-500' },
    'Internal Meeting': { bg: 'bg-gray-100', dot: 'bg-gray-500' },
    Administrative: { bg: 'bg-yellow-100', dot: 'bg-yellow-500' },
    Training: { bg: 'bg-green-100', dot: 'bg-green-500' },
};

export default function ByActivityType({ data }: ByActivityTypeProps) {
    const safeData = data ?? [];
    
    return (
        <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
            <h3 className="font-semibold text-gray-900 mb-4">By Activity Type</h3>
            <div className="space-y-3">
                {safeData.length === 0 ? (
                    <p className="text-sm text-gray-500 text-center py-4">No data available</p>
                ) : (
                    safeData.map((item) => {
                        const colors = defaultColors[item.name] || { bg: 'bg-gray-100', dot: 'bg-gray-500' };
                        return (
                            <div
                                key={item.name}
                                className="flex items-center justify-between py-1"
                            >
                                <div className="flex items-center gap-2">
                                    <span 
                                        className={`w-2.5 h-2.5 rounded-full ${colors.dot}`}
                                        style={item.color ? { backgroundColor: item.color } : undefined}
                                    />
                                    <span className="text-sm text-gray-700">{item.name}</span>
                                </div>
                                <span className="text-sm font-medium text-gray-900">{item.count}</span>
                            </div>
                        );
                    })
                )}
            </div>
        </div>
    );
}
