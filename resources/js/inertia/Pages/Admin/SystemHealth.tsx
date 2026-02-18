import { Head } from '@inertiajs/react';

type HealthItem = {
    status: 'healthy' | 'warning' | 'error';
    message: string;
    response_time_ms?: number | null;
    issues?: string[];
};

type Props = {
    health: {
        database: HealthItem;
        file_permissions: HealthItem;
        cache: HealthItem;
        queue: HealthItem;
    };
};

const statusClass: Record<HealthItem['status'], string> = {
    healthy: 'bg-emerald-100 text-emerald-700',
    warning: 'bg-amber-100 text-amber-700',
    error: 'bg-red-100 text-red-700',
};

export default function SystemHealth({ health }: Props) {
    const cards = [
        { key: 'Database', value: health.database },
        { key: 'File Permissions', value: health.file_permissions },
        { key: 'Cache', value: health.cache },
        { key: 'Queue', value: health.queue },
    ];

    return (
        <>
            <Head title="System Health" />

            <div className="p-6 space-y-6">
                <div>
                    <h1 className="text-2xl font-bold text-gray-900">System Health</h1>
                    <p className="text-sm text-gray-600 mt-1">Current server health checks and service status.</p>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {cards.map(({ key, value }) => (
                        <div key={key} className="rounded-xl border border-gray-200 bg-white p-5">
                            <div className="flex items-center justify-between mb-3">
                                <h2 className="text-base font-semibold text-gray-900">{key}</h2>
                                <span className={`px-2.5 py-1 rounded-full text-xs font-semibold ${statusClass[value.status]}`}>
                                    {value.status}
                                </span>
                            </div>

                            <p className="text-sm text-gray-700">{value.message}</p>

                            {typeof value.response_time_ms === 'number' && (
                                <p className="text-sm text-gray-600 mt-2">Response time: {value.response_time_ms} ms</p>
                            )}

                            {value.issues && value.issues.length > 0 && (
                                <ul className="mt-3 space-y-1 text-sm text-red-700 list-disc list-inside">
                                    {value.issues.map((issue) => (
                                        <li key={issue}>{issue}</li>
                                    ))}
                                </ul>
                            )}
                        </div>
                    ))}
                </div>
            </div>
        </>
    );
}
