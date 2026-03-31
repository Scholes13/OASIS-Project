import { BarChart3, TrendingUp } from 'lucide-react';
import { cn } from '@/lib/utils';

interface DistributionItem {
    name: string;
    color: string;
    value: number;
}

interface FocusBreakdownItem {
    category: string;
    subcategory: string;
    count: number;
    percentage_of_report: number;
    color?: string;
}

interface FocusBreakdown {
    total_activities: number;
    top_category: {
        name: string;
        count: number;
        percentage_of_report: number;
    };
    top_subcategory: {
        name: string;
        count: number;
        percentage_of_report: number;
    };
    items: FocusBreakdownItem[];
}

interface FocusBreakdownPanelProps {
    title: string;
    distribution: DistributionItem[];
    focusBreakdown?: FocusBreakdown | null;
    insight: string;
    isLoading?: boolean;
}

const FALLBACK_COLORS = ['#3b82f6', '#8b5cf6', '#f59e0b', '#10b981'];

export function FocusBreakdownPanel({
    title,
    distribution,
    focusBreakdown,
    insight,
    isLoading = false,
}: FocusBreakdownPanelProps) {
    const hasBreakdown = (focusBreakdown?.total_activities || 0) > 0 && (focusBreakdown?.items?.length || 0) > 0;
    const totalDistribution = distribution.reduce((sum, item) => sum + Number(item.value || 0), 0);

    return (
        <div className="flex flex-col gap-6">
            <div className="rounded-xl border border-slate-200/60 bg-white p-6 shadow-sm">
                <div className="mb-6 flex items-center justify-between">
                    <h3 className="text-base font-semibold text-slate-900">{title}</h3>
                    <span className="text-xs font-medium text-slate-500">Hybrid A breakdown</span>
                </div>

                <div className={cn('space-y-5 transition-opacity', isLoading && 'opacity-50')}>
                    {hasBreakdown ? (
                        <>
                            <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <div className="rounded-lg border border-blue-100 bg-blue-50 p-4">
                                    <p className="text-xs font-semibold uppercase tracking-wide text-blue-700">Top Category</p>
                                    <p className="mt-2 text-sm font-semibold text-slate-900">{focusBreakdown?.top_category.name}</p>
                                    <p className="mt-1 text-sm text-slate-600">
                                        {focusBreakdown?.top_category.count} activity, {focusBreakdown?.top_category.percentage_of_report}% of report
                                    </p>
                                </div>
                                <div className="rounded-lg border border-emerald-100 bg-emerald-50 p-4">
                                    <p className="text-xs font-semibold uppercase tracking-wide text-emerald-700">Top Subcategory</p>
                                    <p className="mt-2 text-sm font-semibold text-slate-900">{focusBreakdown?.top_subcategory.name}</p>
                                    <p className="mt-1 text-sm text-slate-600">
                                        {focusBreakdown?.top_subcategory.count} activity, {focusBreakdown?.top_subcategory.percentage_of_report}% of report
                                    </p>
                                </div>
                            </div>

                            <div className="rounded-lg border border-slate-200 bg-slate-50 p-4">
                                <div className="mb-3 flex items-center gap-2">
                                    <BarChart3 className="h-4 w-4 text-slate-500" />
                                    <p className="text-sm font-semibold text-slate-900">Visual Distribution</p>
                                </div>
                                <div className="overflow-hidden rounded-full bg-slate-200">
                                    <div className="flex h-4 w-full">
                                        {distribution.map((item, index) => {
                                            const percentage = totalDistribution > 0
                                                ? Math.max((Number(item.value || 0) / totalDistribution) * 100, 0)
                                                : 0;

                                            return (
                                                <div
                                                    key={`${item.name}-${index}`}
                                                    className="h-full"
                                                    style={{
                                                        width: `${percentage}%`,
                                                        backgroundColor: item.color || FALLBACK_COLORS[index % FALLBACK_COLORS.length],
                                                    }}
                                                    title={`${item.name}: ${Math.round(percentage)}%`}
                                                />
                                            );
                                        })}
                                    </div>
                                </div>
                                <div className="mt-3 flex flex-wrap gap-2">
                                    {focusBreakdown?.items.map((item, index) => (
                                        <span
                                            key={`${item.category}-${item.subcategory}-${index}`}
                                            className="rounded-full border border-slate-200 bg-white px-2.5 py-1 text-xs font-medium text-slate-600"
                                        >
                                            {item.category}: {item.percentage_of_report}%
                                        </span>
                                    ))}
                                </div>
                            </div>

                            <div className="rounded-lg border border-slate-200 overflow-hidden">
                                <div className="grid grid-cols-[minmax(0,1fr)_auto_auto] gap-3 border-b border-slate-200 bg-slate-50 px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    <span>Category / Subcategory</span>
                                    <span>Count</span>
                                    <span>% of Report</span>
                                </div>
                                <div className="divide-y divide-slate-100">
                                    {focusBreakdown?.items.map((item, index) => (
                                        <div
                                            key={`${item.category}-${item.subcategory}-${index}`}
                                            className="grid grid-cols-[minmax(0,1fr)_auto_auto] items-center gap-3 px-4 py-3"
                                        >
                                            <div className="min-w-0">
                                                <p className="truncate text-sm font-semibold text-slate-900">{item.category}</p>
                                                <p className="truncate text-xs text-slate-500">{item.subcategory}</p>
                                            </div>
                                            <span className="text-sm font-semibold text-slate-900">{item.count}</span>
                                            <span className="text-sm font-semibold text-slate-900">{item.percentage_of_report}%</span>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        </>
                    ) : (
                        <div className="rounded-lg border border-dashed border-slate-200 bg-slate-50 py-10 text-center">
                            <p className="text-sm font-medium text-slate-700">No report breakdown available yet</p>
                            <p className="mt-1 text-sm text-slate-500">Category, subcategory, count, and percentage will appear after activity data is recorded.</p>
                        </div>
                    )}
                </div>
            </div>

            <div className="rounded-xl bg-blue-50 border border-blue-100 p-5">
                <div className="flex items-start gap-3">
                    <div className="mt-0.5 text-blue-600 bg-blue-100 p-1.5 rounded-md">
                        <TrendingUp className="h-4 w-4" strokeWidth={2.5} />
                    </div>
                    <div>
                        <h4 className="text-sm font-semibold text-blue-900">Productivity Insight</h4>
                        <p className="mt-1.5 text-sm leading-relaxed text-blue-700">{insight}</p>
                    </div>
                </div>
            </div>
        </div>
    );
}
