import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Calendar, MapPin, Tag, User } from 'lucide-react';
import type { PageProps } from '@/types';

interface Contact {
    id: number;
    code: string;
    name: string;
    company: string | null;
}

interface BusinessUnit {
    id: number;
    name: string;
    code: string;
}

interface Activity {
    id: number;
    activity_date: string;
    activity_type: string;
    title: string;
    department: string | null;
    pic_name: string | null;
    pic_phone: string | null;
    office_address: string | null;
    description: string | null;
    location: string | null;
    notes: string | null;
    status: string;
    user?: {
        id: number;
        name: string;
        email: string;
    };
    contact?: Contact | null;
    business_unit?: BusinessUnit;
}

interface ShowProps extends PageProps {
    activity: Activity;
}

function formatDate(dateString: string): string {
    return new Date(dateString).toLocaleDateString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
}

export default function Show({ activity }: ShowProps) {
    return (
        <>
            <Head title={activity.title} />

            <div className="w-full px-6 py-6 lg:px-8">
                <div className="mb-6 flex items-center gap-4">
                    <Link
                        href={route('sales-crm.activities.index')}
                        className="inline-flex items-center gap-2 text-sm font-medium text-gray-600 hover:text-gray-900"
                    >
                        <ArrowLeft className="h-4 w-4" />
                        Back to activities
                    </Link>
                </div>

                <div className="grid gap-6 lg:grid-cols-[minmax(0,2fr)_minmax(320px,1fr)]">
                    <section className="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                        <div className="mb-6 flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <p className="text-sm font-medium text-gray-500">Sales Activity</p>
                                <h1 className="mt-1 text-2xl font-semibold text-gray-900">{activity.title}</h1>
                            </div>
                            <span className="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">
                                {activity.status}
                            </span>
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="rounded-xl border border-gray-100 bg-gray-50 p-4">
                                <div className="flex items-center gap-2 text-sm font-medium text-gray-500">
                                    <Calendar className="h-4 w-4" />
                                    Activity Date
                                </div>
                                <p className="mt-2 text-sm text-gray-900">{formatDate(activity.activity_date)}</p>
                            </div>
                            <div className="rounded-xl border border-gray-100 bg-gray-50 p-4">
                                <div className="flex items-center gap-2 text-sm font-medium text-gray-500">
                                    <Tag className="h-4 w-4" />
                                    Type
                                </div>
                                <p className="mt-2 text-sm text-gray-900">{activity.activity_type}</p>
                            </div>
                            <div className="rounded-xl border border-gray-100 bg-gray-50 p-4">
                                <div className="flex items-center gap-2 text-sm font-medium text-gray-500">
                                    <User className="h-4 w-4" />
                                    Contact Person
                                </div>
                                <p className="mt-2 text-sm text-gray-900">{activity.pic_name || 'N/A'}</p>
                                {activity.pic_phone && <p className="text-xs text-gray-500">{activity.pic_phone}</p>}
                            </div>
                            <div className="rounded-xl border border-gray-100 bg-gray-50 p-4">
                                <div className="flex items-center gap-2 text-sm font-medium text-gray-500">
                                    <MapPin className="h-4 w-4" />
                                    Location
                                </div>
                                <p className="mt-2 text-sm text-gray-900">{activity.location || 'N/A'}</p>
                            </div>
                        </div>

                        <div className="mt-6 space-y-4">
                            <div>
                                <h2 className="text-sm font-semibold uppercase tracking-wide text-gray-500">Description</h2>
                                <p className="mt-2 text-sm leading-6 text-gray-700">
                                    {activity.description || 'No description provided.'}
                                </p>
                            </div>
                            <div>
                                <h2 className="text-sm font-semibold uppercase tracking-wide text-gray-500">Notes</h2>
                                <p className="mt-2 text-sm leading-6 text-gray-700">
                                    {activity.notes || 'No notes provided.'}
                                </p>
                            </div>
                        </div>
                    </section>

                    <aside className="space-y-6">
                        <div className="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                            <h2 className="text-sm font-semibold uppercase tracking-wide text-gray-500">Details</h2>
                            <dl className="mt-4 space-y-4 text-sm">
                                <div>
                                    <dt className="text-gray-500">Business Unit</dt>
                                    <dd className="mt-1 text-gray-900">
                                    {activity.business_unit?.name || 'N/A'} {activity.business_unit?.code ? `(${activity.business_unit.code})` : ''}
                                </dd>
                            </div>
                                <div>
                                    <dt className="text-gray-500">Department</dt>
                                    <dd className="mt-1 text-gray-900">{activity.department || 'N/A'}</dd>
                                </div>
                                <div>
                                    <dt className="text-gray-500">Created By</dt>
                                    <dd className="mt-1 text-gray-900">{activity.user?.name || 'N/A'}</dd>
                                </div>
                                <div>
                                    <dt className="text-gray-500">Contact Link</dt>
                                    <dd className="mt-1 text-gray-900">
                                        {activity.contact ? `${activity.contact.name}${activity.contact.company ? ` (${activity.contact.company})` : ''}` : 'No linked contact'}
                                    </dd>
                                </div>
                            </dl>
                        </div>

                        <div className="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                            <Link
                                href={route('sales-crm.activities.edit', { activity: activity.id })}
                                className="inline-flex w-full items-center justify-center rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-blue-700"
                            >
                                Edit Activity
                            </Link>
                        </div>
                    </aside>
                </div>
            </div>
        </>
    );
}
