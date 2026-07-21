import { Head } from '@inertiajs/react';
import { Clock, Info } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/Card';
import { cn } from '@/lib/utils';
import type { PageProps, TicketPriority, TicketSlaSettings } from '@/types';

interface SlaSettingsProps extends PageProps {
    settings: TicketSlaSettings[];
}

const priorityLabels: Record<TicketPriority, string> = {
    low: 'Rendah',
    medium: 'Sedang',
    high: 'Tinggi',
    critical: 'Kritis',
};

const priorityDescriptions: Record<TicketPriority, string> = {
    low: 'Low-impact support request with a 2 x 24 hour resolution target',
    medium: 'Standard support request with a 2 x 24 hour resolution target',
    high: 'High-impact support request with a 2 x 24 hour resolution target',
    critical: 'Critical disruption with a 2 x 24 hour resolution target',
};

const priorityColors: Record<TicketPriority, string> = {
    low: 'bg-slate-100 text-slate-700 border-slate-200',
    medium: 'bg-blue-100 text-blue-700 border-blue-200',
    high: 'bg-orange-100 text-orange-700 border-orange-200',
    critical: 'bg-red-100 text-red-700 border-red-200',
};

export default function TicketSlaSettings(_props: SlaSettingsProps) {
    const priorities: TicketPriority[] = ['low', 'medium', 'high', 'critical'];

    return (
        <>
            <Head title="SLA Settings" />

            <div className="w-full space-y-6 px-6 py-6 lg:px-8">
                <div className="flex flex-col gap-1.5">
                    <h1 className="text-2xl font-bold tracking-tight text-gray-900">SLA Settings</h1>
                    <p className="text-sm text-gray-500">View the Service Level Agreement resolution policy</p>
                </div>

                <Card className="border-blue-200 bg-blue-50">
                    <div className="flex gap-3 p-4">
                        <Info className="mt-0.5 h-5 w-5 flex-shrink-0 text-blue-600" />
                        <div>
                            <h3 className="mb-2 text-sm font-medium text-blue-900">About SLA Settings</h3>
                            <div className="space-y-1 text-sm text-blue-800">
                                <p>The current SLA policy is fixed at 2 x 24 hours for every ticket priority.</p>
                                <p>Tickets approaching or exceeding the deadline show visual warnings throughout IT Support.</p>
                            </div>
                        </div>
                    </div>
                </Card>

                <Card className="rounded-lg border border-gray-200">
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Clock className="h-5 w-5" />
                            Resolution Time by Priority
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="overflow-x-auto">
                            <table className="w-full">
                                <thead>
                                    <tr className="border-b border-gray-200 bg-gray-100">
                                        <th className="h-12 w-48 px-5 text-left text-sm font-semibold text-gray-700">Priority</th>
                                        <th className="h-12 px-5 text-left text-sm font-semibold text-gray-700">Description</th>
                                        <th className="h-12 w-48 px-5 text-left text-sm font-semibold text-gray-700">Resolution Target</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {priorities.map((priority) => (
                                        <tr key={priority} className="border-b border-gray-100 hover:bg-gray-50/80">
                                            <td className="px-5 py-4">
                                                <span className={cn(
                                                    'inline-flex items-center rounded-full border px-2.5 py-1 text-sm font-medium',
                                                    priorityColors[priority]
                                                )}>
                                                    {priorityLabels[priority]}
                                                </span>
                                            </td>
                                            <td className="px-5 py-4 text-sm text-gray-600">
                                                {priorityDescriptions[priority]}
                                            </td>
                                            <td className="px-5 py-4">
                                                <span className="inline-flex min-w-24 justify-center rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm font-semibold text-gray-900">
                                                    48 hours
                                                </span>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>

                <Card className="border-gray-200 bg-gray-50">
                    <div className="flex gap-3 p-4">
                        <Info className="mt-0.5 h-5 w-5 flex-shrink-0 text-gray-600" />
                        <div>
                            <h3 className="mb-2 text-sm font-medium text-gray-900">Current SLA Policy</h3>
                            <p className="text-sm text-gray-700">
                                <strong>2 x 24 hours:</strong> Low, Medium, High, and Critical tickets share the same 48-hour resolution target.
                            </p>
                        </div>
                    </div>
                </Card>
            </div>
        </>
    );
}
