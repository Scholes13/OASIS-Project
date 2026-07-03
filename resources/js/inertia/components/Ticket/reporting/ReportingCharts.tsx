import { BarChart3, PieChart, TrendingUp, Users } from 'lucide-react';
import {
    Bar,
    BarChart,
    CartesianGrid,
    Cell,
    Legend,
    Line,
    LineChart,
    Pie,
    PieChart as PieChartRecharts,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/Card';
import { EmptyState } from '@/components/ui/empty-state';
import type { ReportData } from './types';

function ChartTooltip({ active, payload, label }: any) {
    if (!active || !payload?.length) return null;
    const total = payload.reduce((sum: number, item: any) => sum + (item.value || 0), 0);

    return (
        <div className="rounded-lg border border-gray-200 bg-white px-3.5 py-2.5 shadow-xl">
            <p className="text-xs font-semibold text-gray-700 mb-1.5 border-b border-gray-100 pb-1.5">{label}</p>
            {payload.map((item: any, index: number) => (
                <div
                    key={index}
                    className="flex items-center justify-between gap-6 py-0.5"
                >
                    <div className="flex items-center gap-2">
                        <span
                            className="w-2.5 h-2.5 rounded-full flex-shrink-0"
                            style={{ backgroundColor: item.color }}
                        />
                        <span className="text-xs text-gray-600">{item.name}</span>
                    </div>
                    <div className="flex items-center gap-2">
                        <span className="text-xs font-bold text-gray-900 tabular-nums">{item.value}</span>
                        {total > 0 && (
                            <span className="text-[10px] text-gray-400 tabular-nums">
                                ({Math.round((item.value / total) * 100)}%)
                            </span>
                        )}
                    </div>
                </div>
            ))}
        </div>
    );
}

export default function ReportingCharts({ reportData }: { reportData: ReportData }) {
    const statusPieData = reportData.by_status || [];
    const priorityBarData = reportData.by_priority || [];
    const categoryBarData = reportData.by_category || [];
    const staffBarData = reportData.by_staff || [];
    const dailyTrendData = reportData.daily_trend || [];

    return (
        <>
            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <Card className="lg:col-span-2 border-gray-200 bg-white shadow-none">
                    <CardHeader className="pb-4">
                        <CardTitle className="text-base font-semibold text-gray-900">Tickets Over Time</CardTitle>
                    </CardHeader>
                    <CardContent className="flex-1">
                        {dailyTrendData.length === 0 ? (
                            <EmptyState
                                icon={<TrendingUp className="w-10 h-10" />}
                                title="No trend data"
                                description="Data trend akan muncul setelah ticket dibuat."
                                variant="compact"
                            />
                        ) : (
                            <ResponsiveContainer
                                width="100%"
                                height={300}
                            >
                                <LineChart data={dailyTrendData}>
                                    <CartesianGrid
                                        strokeDasharray="3 3"
                                        stroke="#f1f5f9"
                                        vertical={false}
                                    />
                                    <XAxis
                                        dataKey="date"
                                        tick={{ fontSize: 11 }}
                                        stroke="#94a3b8"
                                        axisLine={false}
                                        tickLine={false}
                                        dy={10}
                                    />
                                    <YAxis
                                        tick={{ fontSize: 11 }}
                                        stroke="#94a3b8"
                                        allowDecimals={false}
                                        axisLine={false}
                                        tickLine={false}
                                        dx={-10}
                                    />
                                    <Tooltip content={<ChartTooltip />} />
                                    <Legend
                                        iconType="circle"
                                        iconSize={8}
                                        wrapperStyle={{ fontSize: 12, paddingTop: 8 }}
                                    />
                                    <Line
                                        type="monotone"
                                        dataKey="total"
                                        name="Total"
                                        stroke="#16599c"
                                        strokeWidth={2}
                                        dot={false}
                                    />
                                    <Line
                                        type="monotone"
                                        dataKey="resolved"
                                        name="Resolved"
                                        stroke="#6b7280"
                                        strokeWidth={2}
                                        dot={false}
                                    />
                                </LineChart>
                            </ResponsiveContainer>
                        )}
                    </CardContent>
                </Card>

                <Card className="border-gray-200 bg-white shadow-none">
                    <CardHeader className="pb-0">
                        <CardTitle className="text-base font-semibold text-gray-900">By Status</CardTitle>
                    </CardHeader>
                    <CardContent className="flex-1 flex flex-col justify-center">
                        {statusPieData.length === 0 ? (
                            <EmptyState
                                icon={<PieChart className="w-10 h-10" />}
                                title="No status data"
                                description="Data status akan muncul setelah ticket dibuat."
                                variant="compact"
                            />
                        ) : (
                            <div className="flex items-center justify-center gap-4">
                                <div className="h-44 w-44">
                                    <ResponsiveContainer
                                        width="100%"
                                        height="100%"
                                    >
                                        <PieChartRecharts>
                                            <Pie
                                                data={statusPieData}
                                                dataKey="value"
                                                nameKey="name"
                                                cx="50%"
                                                cy="50%"
                                                innerRadius={50}
                                                outerRadius={80}
                                                paddingAngle={2}
                                                stroke="none"
                                            >
                                                {statusPieData.map((entry, index) => (
                                                    <Cell
                                                        key={index}
                                                        fill={['#94a3b8', '#16599c', '#d97706', '#dc2626'][index % 4]}
                                                    />
                                                ))}
                                            </Pie>
                                            <Tooltip content={<ChartTooltip />} />
                                        </PieChartRecharts>
                                    </ResponsiveContainer>
                                </div>
                                <div className="space-y-2">
                                    {statusPieData.map((item, index) => (
                                        <div
                                            key={index}
                                            className="flex items-center justify-between gap-3"
                                        >
                                            <div className="flex items-center gap-2">
                                                <span
                                                    className="w-2.5 h-2.5 rounded-full"
                                                    style={{ backgroundColor: item.color }}
                                                />
                                                <span className="text-sm text-gray-600">{item.name}</span>
                                            </div>
                                            <span className="text-sm font-semibold text-gray-900 tabular-nums">{item.value}</span>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <ReportBarCard
                    title="By Priority"
                    data={priorityBarData}
                    empty={priorityBarData.every((item) => item.count === 0)}
                    emptyDescription="Data priority akan muncul setelah ticket dibuat."
                    coloredBars
                />
                <ReportBarCard
                    title="By Category"
                    data={categoryBarData}
                    empty={categoryBarData.length === 0}
                    emptyDescription="Data kategori akan muncul setelah ticket dibuat."
                />
            </div>
            <ReportStaffCard data={staffBarData} />
        </>
    );
}

function ReportBarCard({ title, data, empty, emptyDescription, coloredBars = false }: any) {
    return (
        <Card className="border-gray-200 bg-white shadow-none">
            <CardHeader className="pb-4">
                <CardTitle className="text-base font-semibold text-gray-900">{title}</CardTitle>
            </CardHeader>
            <CardContent className="flex-1">
                {empty ? (
                    <EmptyState
                        icon={<BarChart3 className="w-10 h-10" />}
                        title={`No ${title.replace('By ', '').toLowerCase()} data`}
                        description={emptyDescription}
                        variant="compact"
                    />
                ) : (
                    <ResponsiveContainer
                        width="100%"
                        height={280}
                    >
                        <BarChart
                            data={data}
                            barSize={coloredBars ? 40 : 32}
                            margin={{ top: 10, right: 10, left: -15, bottom: 0 }}
                        >
                            <CartesianGrid
                                strokeDasharray="3 3"
                                stroke="#f1f5f9"
                                vertical={false}
                            />
                            <XAxis dataKey="name" tick={{ fontSize: 11, fill: '#64748b', fontWeight: 500 }} stroke="#cbd5e1" axisLine={false} tickLine={false} dy={10} />
                            <YAxis tick={{ fontSize: 11, fill: '#64748b' }} stroke="#cbd5e1" allowDecimals={false} axisLine={false} tickLine={false} dx={-10} />
                            <Tooltip content={<ChartTooltip />} />
                            <Bar
                                dataKey="count"
                                name="Tickets"
                                radius={[4, 4, 0, 0]}
                                fill="#16599c"
                            >
                                {coloredBars && data.map((entry: any, index: number) => (
                                    <Cell
                                        key={index}
                                        fill={['#94a3b8', '#16599c', '#d97706', '#dc2626'][index % 4]}
                                    />
                                ))}
                            </Bar>
                        </BarChart>
                    </ResponsiveContainer>
                )}
            </CardContent>
        </Card>
    );
}

function ReportStaffCard({ data }: any) {
    return (
        <Card className="border-gray-200 bg-white shadow-none">
            <CardHeader className="pb-4">
                <CardTitle className="text-base font-semibold text-gray-900">By Staff</CardTitle>
            </CardHeader>
            <CardContent className="flex-1">
                {data.length === 0 ? (
                    <EmptyState
                        icon={<Users className="w-10 h-10" />}
                        title="No staff data"
                        description="Belum ada ticket yang ditugaskan ke staff."
                        variant="compact"
                    />
                ) : (
                    <ResponsiveContainer
                        width="100%"
                        height={300}
                    >
                        <BarChart data={data} layout="vertical" barSize={24} margin={{ top: 10, right: 10, left: 80, bottom: 0 }}>
                            <CartesianGrid strokeDasharray="3 3" stroke="#f1f5f9" horizontal={false} />
                            <XAxis type="number" tick={{ fontSize: 11 }} stroke="#94a3b8" allowDecimals={false} axisLine={false} tickLine={false} />
                            <YAxis type="category" dataKey="name" tick={{ fontSize: 11, fill: '#64748b' }} stroke="#cbd5e1" axisLine={false} tickLine={false} width={70} />
                            <Tooltip content={<ChartTooltip />} />
                            <Bar dataKey="count" name="Tickets" radius={[0, 4, 4, 0]} fill="#16599c" />
                        </BarChart>
                    </ResponsiveContainer>
                )}
            </CardContent>
        </Card>
    );
}
