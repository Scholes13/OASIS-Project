import type { ReactNode } from 'react';
import { AlertTriangle, CheckCircle2, Clock, FileText } from 'lucide-react';
import { Card } from '@/components/ui/Card';
import { cn } from '@/lib/utils';
import type { ReportData } from './types';

export default function ReportingMetricCards({ reportData }: { reportData: ReportData }) {
    const sla = reportData.sla_compliance;
    const complianceRate = sla?.rate ?? 0;

    return (
        <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-6">
            <MetricCard title="Total Tickets" value={reportData.total_tickets} subLabel="Selected period" tone="neutral" icon={<FileText className="h-4 w-4" />} />
            <MetricCard title="SLA Target" value="48 hrs" subLabel="2 × 24h per case" tone="neutral" icon={<Clock className="h-4 w-4" />} />
            <MetricCard title="Avg per Case" value={`${Number(reportData.avg_resolution_hours || 0).toFixed(1)} hrs`} subLabel="Resolved tickets" tone="neutral" icon={<Clock className="h-4 w-4" />} />
            <MetricCard title="Resolved" value={reportData.resolved_tickets} subLabel="Closed tickets" tone="success" icon={<CheckCircle2 className="h-4 w-4" />} />
            <MetricCard title="SLA Compliance" value={`${complianceRate}%`} subLabel={`${sla?.within_sla ?? 0} within SLA`} tone={complianceRate >= 90 ? 'success' : complianceRate >= 70 ? 'warning' : 'danger'} icon={<CheckCircle2 className="h-4 w-4" />} />
            <MetricCard title="SLA Breached" value={sla?.breached ?? 0} subLabel="Past 48h target" tone={(sla?.breached ?? 0) > 0 ? 'danger' : 'neutral'} icon={<AlertTriangle className="h-4 w-4" />} />
        </div>
    );
}

function MetricCard({ title, value, subLabel, tone, icon }: { title: string; value: number | string; subLabel: string; tone: 'neutral' | 'success' | 'warning' | 'danger'; icon: ReactNode }) {
    const toneClass = {
        neutral: 'text-gray-600 bg-gray-50 border-gray-200',
        success: 'text-emerald-700 bg-emerald-50 border-emerald-100',
        warning: 'text-amber-700 bg-amber-50 border-amber-100',
        danger: 'text-red-700 bg-red-50 border-red-100',
    }[tone];

    return (
        <Card className="border-gray-200 bg-white p-4 shadow-none">
            <div className="flex items-start justify-between gap-3">
                <div>
                    <p className="text-xs font-semibold uppercase tracking-wide text-gray-500">{title}</p>
                    <p className="mt-3 text-2xl font-semibold tracking-tight text-gray-900">{value}</p>
                    <p className="mt-1 text-xs text-gray-500">{subLabel}</p>
                </div>
                <span className={cn('flex h-8 w-8 items-center justify-center rounded-lg border', toneClass)}>{icon}</span>
            </div>
        </Card>
    );
}
