import { Head, Link } from '@inertiajs/react';

type Props = {
    stats: {
        total_sent: number;
        total_failed: number;
        success_rate: number;
        last_email_sent: string | null;
    };
    settings: {
        email_enabled: boolean;
        fallback_to_database: boolean;
        retry_failed_emails: boolean;
        link_expiry_days: number;
        smtp_host: string;
        smtp_port: number;
        smtp_encryption: string;
        mail_from_address: string;
        mail_from_name: string;
    };
};

export default function NotificationStatistics({ stats, settings }: Props) {
    return (
        <>
            <Head title="Notification Statistics" />

            <div className="p-6 space-y-6">
                <div className="flex items-start justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900">Notification Statistics</h1>
                        <p className="text-sm text-gray-600 mt-1">Delivery metrics and current mail configuration summary.</p>
                    </div>

                    <Link
                        href={route('admin.notification-settings.index')}
                        className="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-700"
                    >
                        Back to Settings
                    </Link>
                </div>

                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div className="rounded-xl border border-gray-200 bg-white p-4">
                        <p className="text-sm text-gray-500">Total Sent</p>
                        <p className="text-2xl font-bold text-gray-900 mt-1">{stats.total_sent.toLocaleString()}</p>
                    </div>

                    <div className="rounded-xl border border-gray-200 bg-white p-4">
                        <p className="text-sm text-gray-500">Total Failed</p>
                        <p className="text-2xl font-bold text-gray-900 mt-1">{stats.total_failed.toLocaleString()}</p>
                    </div>

                    <div className="rounded-xl border border-gray-200 bg-white p-4">
                        <p className="text-sm text-gray-500">Success Rate</p>
                        <p className="text-2xl font-bold text-gray-900 mt-1">{stats.success_rate}%</p>
                    </div>

                    <div className="rounded-xl border border-gray-200 bg-white p-4">
                        <p className="text-sm text-gray-500">Last Email Sent</p>
                        <p className="text-sm font-semibold text-gray-900 mt-2">
                            {stats.last_email_sent ? new Date(stats.last_email_sent).toLocaleString() : 'Never'}
                        </p>
                    </div>
                </div>

                <div className="rounded-xl border border-gray-200 bg-white p-5">
                    <h2 className="text-base font-semibold text-gray-900 mb-3">Current Mail Configuration</h2>
                    <dl className="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                        <div>
                            <dt className="text-gray-500">SMTP Host</dt>
                            <dd className="text-gray-900 font-medium">{settings.smtp_host}:{settings.smtp_port}</dd>
                        </div>
                        <div>
                            <dt className="text-gray-500">Encryption</dt>
                            <dd className="text-gray-900 font-medium">{settings.smtp_encryption}</dd>
                        </div>
                        <div>
                            <dt className="text-gray-500">From Address</dt>
                            <dd className="text-gray-900 font-medium">{settings.mail_from_address}</dd>
                        </div>
                        <div>
                            <dt className="text-gray-500">From Name</dt>
                            <dd className="text-gray-900 font-medium">{settings.mail_from_name}</dd>
                        </div>
                        <div>
                            <dt className="text-gray-500">Email Enabled</dt>
                            <dd className="text-gray-900 font-medium">{settings.email_enabled ? 'Yes' : 'No'}</dd>
                        </div>
                        <div>
                            <dt className="text-gray-500">Fallback to Database</dt>
                            <dd className="text-gray-900 font-medium">{settings.fallback_to_database ? 'Yes' : 'No'}</dd>
                        </div>
                        <div>
                            <dt className="text-gray-500">Retry Failed Emails</dt>
                            <dd className="text-gray-900 font-medium">{settings.retry_failed_emails ? 'Yes' : 'No'}</dd>
                        </div>
                        <div>
                            <dt className="text-gray-500">Link Expiry Days</dt>
                            <dd className="text-gray-900 font-medium">{settings.link_expiry_days}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </>
    );
}
