import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Building2, Globe } from 'lucide-react';
import { BusinessUnitComparisonTable } from '@/components/purchasing-admin/reports/BusinessUnitComparisonTable';
import { ReportMetricCards } from '@/components/purchasing-admin/reports/ReportMetricCards';
import { SavingsTrendComparison } from '@/components/purchasing-admin/reports/SavingsTrendComparison';
import type { BusinessUnitMetric, ComparativeTrendData, OverallMetrics } from '@/components/purchasing-admin/reports/reportTypes';
import type { PageProps } from '@/types';

interface ConsolidatedReportProps extends PageProps {
    childBusinessUnits: { id: number; code: string; name: string }[];
    businessUnitMetrics: BusinessUnitMetric[];
    overallMetrics: OverallMetrics;
    comparativeTrendData: ComparativeTrendData;
}

export default function ConsolidatedReport({
    childBusinessUnits = [],
    businessUnitMetrics = [],
    overallMetrics = {
        total_tasks: 0,
        total_savings: 0,
        avg_savings_percentage: 0,
        avg_followup_time: 0,
        avg_completion_time: 0,
    },
    comparativeTrendData = { labels: [], datasets: [] },
}: ConsolidatedReportProps) {
    const hasChildBUs = childBusinessUnits.length > 0;

    return (
        <>
            <Head title="Consolidated Report" />
            <div className="py-6">
                <div className="w-full px-4 sm:px-6 lg:px-8">
                    <div className="mb-6">
                        <Link
                            href={route('purchasing.admin.dashboard')}
                            className="inline-flex items-center text-gray-600 hover:text-gray-900 mb-4"
                        >
                            <ArrowLeft className="w-4 h-4 mr-2" />
                            Back to Dashboard
                        </Link>
                        <div className="flex items-center gap-3">
                            <div className="w-10 h-10 rounded-lg bg-purple-100 flex items-center justify-center">
                                <Globe className="w-5 h-5 text-purple-600" />
                            </div>
                            <div>
                                <h1 className="text-2xl font-bold text-gray-900">Consolidated Report</h1>
                                <p className="text-gray-500">Cross-business unit performance overview</p>
                            </div>
                        </div>
                    </div>

                    {!hasChildBUs ? (
                        <div className="bg-white rounded-xl border border-gray-100 p-8 text-center">
                            <Building2 className="w-12 h-12 mx-auto mb-4 text-gray-400" />
                            <h3 className="text-lg font-medium text-gray-900 mb-2">No Child Business Units</h3>
                            <p className="text-gray-500">
                                Consolidated reports are only available for parent business units with child units.
                            </p>
                        </div>
                    ) : (
                        <>
                            <ReportMetricCards metrics={overallMetrics} />
                            <BusinessUnitComparisonTable metrics={businessUnitMetrics} />
                            <SavingsTrendComparison trendData={comparativeTrendData} />
                        </>
                    )}
                </div>
            </div>
        </>
    );
}
