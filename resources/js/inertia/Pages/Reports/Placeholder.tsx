import { Head } from '@inertiajs/react';
import { BarChart3, Clock } from 'lucide-react';

type ReportPlaceholderProps = {
    title: string;
    description: string;
    message: string;
};

export default function ReportPlaceholder({ title, description, message }: ReportPlaceholderProps) {
    return (
        <>
            <Head title={title} />

            <div className="mx-auto flex max-w-5xl flex-col gap-6 px-6 py-8">
                <div>
                    <p className="text-sm font-medium text-slate-500">Reports</p>
                    <h1 className="mt-2 text-3xl font-bold tracking-tight text-slate-950">{title}</h1>
                    <p className="mt-2 max-w-2xl text-sm leading-6 text-slate-500">{description}</p>
                </div>

                <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div className="border-b border-slate-200 bg-slate-50 px-6 py-4">
                        <div className="flex items-center gap-3">
                            <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-700 ring-1 ring-blue-100">
                                <BarChart3 className="h-5 w-5" />
                            </div>
                            <div>
                                <h2 className="text-base font-semibold text-slate-950">Report builder coming soon</h2>
                                <p className="text-sm text-slate-500">This page has been migrated to Inertia.</p>
                            </div>
                        </div>
                    </div>

                    <div className="px-6 py-8">
                        <div className="flex items-start gap-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-4 text-amber-900">
                            <Clock className="mt-0.5 h-5 w-5 flex-none" />
                            <div>
                                <p className="text-sm font-semibold">Feature unavailable</p>
                                <p className="mt-1 text-sm leading-6">{message}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}
