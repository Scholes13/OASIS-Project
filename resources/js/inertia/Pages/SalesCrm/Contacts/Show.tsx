import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Building2, Mail, Phone, Tag } from 'lucide-react';
import type { PageProps } from '@/types';

interface ActivitySummary {
    id: number;
    activity_date: string;
    activity_type: string;
    title: string;
    status: string;
}

interface ContactSource {
    source_type: string;
    activity_type: string | null;
    source_notes: string | null;
    source_date: string;
}

interface Contact {
    id: number;
    code: string;
    name: string;
    email: string | null;
    phone: string | null;
    mobile: string | null;
    company: string | null;
    department: string | null;
    position: string | null;
    status: string;
    category: string;
    address: string | null;
    notes: string | null;
    assigned_to?: {
        id: number;
        name: string;
        email: string;
    } | null;
    created_by?: {
        id: number;
        name: string;
        email: string;
    } | null;
    activities?: ActivitySummary[];
    source?: ContactSource | null;
}

interface ShowProps extends PageProps {
    contact: Contact;
}

function formatDate(dateString: string): string {
    return new Date(dateString).toLocaleDateString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
}

export default function Show({ contact }: ShowProps) {
    return (
        <>
            <Head title={contact.name} />

            <div className="w-full px-6 py-6 lg:px-8">
                <div className="mb-6 flex items-center gap-4">
                    <Link
                        href={route('sales-crm.contacts.index')}
                        className="inline-flex items-center gap-2 text-sm font-medium text-gray-600 hover:text-gray-900"
                    >
                        <ArrowLeft className="h-4 w-4" />
                        Back to contacts
                    </Link>
                </div>

                <div className="grid gap-6 lg:grid-cols-[minmax(0,2fr)_minmax(320px,1fr)]">
                    <section className="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                        <div className="mb-6 flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <p className="text-sm font-medium text-gray-500">Sales Contact</p>
                                <h1 className="mt-1 text-2xl font-semibold text-gray-900">{contact.name}</h1>
                                <p className="mt-1 text-sm text-gray-500">{contact.code}</p>
                            </div>
                            <div className="flex flex-wrap gap-2">
                                <span className="inline-flex items-center rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">
                                    {contact.status}
                                </span>
                                <span className="inline-flex items-center rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700">
                                    {contact.category}
                                </span>
                            </div>
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="rounded-xl border border-gray-100 bg-gray-50 p-4">
                                <div className="flex items-center gap-2 text-sm font-medium text-gray-500">
                                    <Building2 className="h-4 w-4" />
                                    Company
                                </div>
                                <p className="mt-2 text-sm text-gray-900">{contact.company || 'N/A'}</p>
                            </div>
                            <div className="rounded-xl border border-gray-100 bg-gray-50 p-4">
                                <div className="flex items-center gap-2 text-sm font-medium text-gray-500">
                                    <Tag className="h-4 w-4" />
                                    Position
                                </div>
                                <p className="mt-2 text-sm text-gray-900">{contact.position || 'N/A'}</p>
                            </div>
                            <div className="rounded-xl border border-gray-100 bg-gray-50 p-4">
                                <div className="flex items-center gap-2 text-sm font-medium text-gray-500">
                                    <Mail className="h-4 w-4" />
                                    Email
                                </div>
                                <p className="mt-2 text-sm text-gray-900">{contact.email || 'N/A'}</p>
                            </div>
                            <div className="rounded-xl border border-gray-100 bg-gray-50 p-4">
                                <div className="flex items-center gap-2 text-sm font-medium text-gray-500">
                                    <Phone className="h-4 w-4" />
                                    Phone
                                </div>
                                <p className="mt-2 text-sm text-gray-900">{contact.phone || contact.mobile || 'N/A'}</p>
                            </div>
                        </div>

                        <div className="mt-6 space-y-4">
                            <div>
                                <h2 className="text-sm font-semibold uppercase tracking-wide text-gray-500">Address</h2>
                                <p className="mt-2 text-sm leading-6 text-gray-700">
                                    {contact.address || 'No address provided.'}
                                </p>
                            </div>
                            <div>
                                <h2 className="text-sm font-semibold uppercase tracking-wide text-gray-500">Notes</h2>
                                <p className="mt-2 text-sm leading-6 text-gray-700">
                                    {contact.notes || 'No notes provided.'}
                                </p>
                            </div>
                        </div>
                    </section>

                    <aside className="space-y-6">
                        <div className="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                            <h2 className="text-sm font-semibold uppercase tracking-wide text-gray-500">Ownership</h2>
                            <dl className="mt-4 space-y-4 text-sm">
                                <div>
                                    <dt className="text-gray-500">Assigned To</dt>
                                    <dd className="mt-1 text-gray-900">{contact.assigned_to?.name || 'N/A'}</dd>
                                </div>
                                <div>
                                    <dt className="text-gray-500">Created By</dt>
                                    <dd className="mt-1 text-gray-900">{contact.created_by?.name || 'N/A'}</dd>
                                </div>
                                <div>
                                    <dt className="text-gray-500">Department</dt>
                                    <dd className="mt-1 text-gray-900">{contact.department || 'N/A'}</dd>
                                </div>
                                <div>
                                    <dt className="text-gray-500">Source</dt>
                                    <dd className="mt-1 text-gray-900">
                                        {contact.source ? `${contact.source.source_type}${contact.source.activity_type ? ` - ${contact.source.activity_type}` : ''}` : 'No source record'}
                                    </dd>
                                </div>
                            </dl>
                        </div>

                        <div className="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                            <Link
                                href={route('sales-crm.contacts.edit', { contact: contact.id })}
                                className="inline-flex w-full items-center justify-center rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-blue-700"
                            >
                                Edit Contact
                            </Link>
                        </div>

                        <div className="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                            <h2 className="text-sm font-semibold uppercase tracking-wide text-gray-500">Recent Activities</h2>
                            <div className="mt-4 space-y-3">
                                {contact.activities?.length ? (
                                    contact.activities.map((activity) => (
                                        <div key={activity.id} className="rounded-xl border border-gray-100 bg-gray-50 p-4">
                                            <div className="flex items-start justify-between gap-3">
                                                <div>
                                                    <p className="text-sm font-medium text-gray-900">{activity.title}</p>
                                                    <p className="mt-1 text-xs text-gray-500">
                                                        {activity.activity_type} • {formatDate(activity.activity_date)}
                                                    </p>
                                                </div>
                                                <span className="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700">
                                                    {activity.status}
                                                </span>
                                            </div>
                                        </div>
                                    ))
                                ) : (
                                    <div className="rounded-xl border border-dashed border-gray-200 bg-gray-50 p-4 text-sm text-gray-500">
                                        No activities recorded yet.
                                    </div>
                                )}
                            </div>
                        </div>
                    </aside>
                </div>
            </div>
        </>
    );
}
