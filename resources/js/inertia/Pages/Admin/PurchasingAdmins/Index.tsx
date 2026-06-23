import { useState, useEffect } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import { Search, ShoppingCart, Building2, Users } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/Badge';
import { toast } from '@/components/ui/toast';
import { cn } from '@/lib/utils';
import type { PageProps, PaginatedData } from '@/types';

interface BusinessUnit {
    id: number;
    name: string;
    code: string;
}

interface Assignment {
    id: number;
    is_purchasing_admin: boolean;
    is_purchasing_report_access: boolean;
    is_purchasing_readonly: boolean;
    is_primary: boolean;
    user: { id: number; name: string; email: string } | null;
    business_unit: BusinessUnit | null;
    department: { id: number; name: string } | null;
    position: { id: number; name: string; access_level: string } | null;
}

interface Props extends PageProps {
    assignments: PaginatedData<Assignment>;
    businessUnits: BusinessUnit[];
    adminCounts: Record<number, number>;
    reportAccessCounts: Record<number, number>;
    readonlyCounts: Record<number, number>;
    filters: { business_unit_id: string; search: string };
}

export default function Index({ assignments, businessUnits, adminCounts, reportAccessCounts, readonlyCounts, filters }: Props) {
    const { flash } = usePage<PageProps>().props;
    const [search, setSearch] = useState(filters.search);
    const [buFilter, setBuFilter] = useState(filters.business_unit_id);
    const [togglingId, setTogglingId] = useState<number | null>(null);
    const [togglingReportId, setTogglingReportId] = useState<number | null>(null);
    const [togglingReadonlyId, setTogglingReadonlyId] = useState<number | null>(null);

    useEffect(() => {
        if (flash.success) toast.success(flash.success);
        if (flash.error) toast.error(flash.error);
    }, [flash]);

    const applyFilters = (overrides: Record<string, string> = {}) => {
        router.get(route('admin.purchasing-admins.index'), {
            business_unit_id: buFilter,
            search,
            ...overrides,
        }, { preserveState: true, preserveScroll: true });
    };

    const handleToggle = (id: number) => {
        setTogglingId(id);
        router.post(route('admin.purchasing-admins.toggle', { id }), {}, {
            preserveScroll: true,
            onFinish: () => setTogglingId(null),
        });
    };

    const handleToggleReport = (id: number) => {
        setTogglingReportId(id);
        router.post(route('admin.purchasing-admins.toggle-report', { id }), {}, {
            preserveScroll: true,
            onFinish: () => setTogglingReportId(null),
        });
    };

    const handleToggleReadonly = (id: number) => {
        setTogglingReadonlyId(id);
        router.post(route('admin.purchasing-admins.toggle-readonly', { id }), {}, {
            preserveScroll: true,
            onFinish: () => setTogglingReadonlyId(null),
        });
    };

    const totalAdmins = Object.values(adminCounts).reduce((a, b) => a + b, 0);

    return (
        <>
            <Head title="Purchasing Admin Assignment" />

            <div className="p-6 space-y-6">
                {/* Header */}
                <div>
                    <h1 className="text-2xl font-bold text-gray-900">Purchasing Admin Assignment</h1>
                    <p className="mt-1 text-sm text-gray-600">
                        Assign users sebagai Purchasing Admin per Business Unit. Purchasing Admin dapat mengelola task purchasing dan melihat consolidated report.
                    </p>
                </div>

                {/* Summary Cards */}
                <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div className="bg-white rounded-xl border border-gray-200 p-4 flex items-center gap-4">
                        <div className="p-3 bg-emerald-50 rounded-xl">
                            <ShoppingCart className="w-6 h-6 text-emerald-600" strokeWidth={1.5} />
                        </div>
                        <div>
                            <p className="text-sm text-gray-500">Total Purchasing Admins</p>
                            <p className="text-xl font-bold text-gray-900">{totalAdmins}</p>
                        </div>
                    </div>
                    <div className="bg-white rounded-xl border border-gray-200 p-4 flex items-center gap-4">
                        <div className="p-3 bg-indigo-100 rounded-xl">
                            <ShoppingCart className="w-6 h-6 text-indigo-600" strokeWidth={1.5} />
                        </div>
                        <div>
                            <p className="text-sm text-gray-500">Report Access</p>
                            <p className="text-xl font-bold text-gray-900">{Object.values(reportAccessCounts).reduce((a, b) => a + b, 0)}</p>
                        </div>
                    </div>
                    <div className="bg-white rounded-xl border border-gray-200 p-4 flex items-center gap-4">
                        <div className="p-3 bg-amber-100 rounded-xl">
                            <ShoppingCart className="w-6 h-6 text-amber-600" strokeWidth={1.5} />
                        </div>
                        <div>
                            <p className="text-sm text-gray-500">Read Only</p>
                            <p className="text-xl font-bold text-gray-900">{Object.values(readonlyCounts).reduce((a, b) => a + b, 0)}</p>
                        </div>
                    </div>
                    {businessUnits.slice(0, 1).map((bu) => (
                        <div key={bu.id} className="bg-white rounded-xl border border-gray-200 p-4 flex items-center gap-4">
                            <div className="p-3 bg-emerald-50 rounded-xl">
                                <Building2 className="w-6 h-6 text-emerald-600" strokeWidth={1.5} />
                            </div>
                            <div>
                                <p className="text-sm text-gray-500">{bu.code} Admins</p>
                                <p className="text-xl font-bold text-gray-900">{adminCounts[bu.id] || 0}</p>
                            </div>
                        </div>
                    ))}
                </div>

                {/* Filters */}
                <div className="bg-white rounded-xl border border-gray-100 p-4">
                    <div className="flex flex-wrap items-center gap-3">
                        <select
                            value={buFilter}
                            onChange={(e) => { setBuFilter(e.target.value); applyFilters({ business_unit_id: e.target.value }); }}
                            className="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                        >
                            <option value="">All Business Units</option>
                            {businessUnits.map((bu) => (
                                <option key={bu.id} value={bu.id}>{bu.name} ({bu.code})</option>
                            ))}
                        </select>
                        <div className="relative flex-1 min-w-[200px]">
                            <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                            <input
                                type="text"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                onKeyDown={(e) => e.key === 'Enter' && applyFilters()}
                                placeholder="Search by name or email..."
                                className="w-full pl-9 pr-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                            />
                        </div>
                        <Button onClick={() => applyFilters()} size="sm">Search</Button>
                    </div>
                </div>

                {/* Table */}
                <div className="bg-white rounded-xl border border-gray-200 overflow-hidden">
                    <table className="min-w-full divide-y divide-gray-200">
                        <thead className="bg-gray-50">
                            <tr>
                                {['User', 'Business Unit', 'Department', 'Position', 'Purchasing Admin', 'Report Access', 'Read Only'].map((h) => (
                                    <th key={h} className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{h}</th>
                                ))}
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100">
                            {assignments.data.length === 0 ? (
                                <tr>
                                    <td colSpan={7} className="px-4 py-12 text-center text-gray-400">
                                        <Users className="w-10 h-10 mx-auto mb-2 text-gray-300" />
                                        Tidak ada data ditemukan.
                                    </td>
                                </tr>
                            ) : assignments.data.map((a) => (
                                <tr key={a.id} className={cn('hover:bg-gray-50 transition-colors', a.is_purchasing_admin && 'bg-emerald-50')}>
                                    <td className="px-4 py-3">
                                        <div className="flex items-center gap-3">
                                            <div className="w-8 h-8 bg-emerald-600 rounded-full flex items-center justify-center text-xs font-medium text-white">
                                                {(a.user?.name || '?').charAt(0).toUpperCase()}
                                            </div>
                                            <div>
                                                <p className="text-sm font-medium text-gray-900">{a.user?.name}</p>
                                                <p className="text-xs text-gray-500">{a.user?.email}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td className="px-4 py-3">
                                        <Badge variant="default">{a.business_unit?.code}</Badge>
                                    </td>
                                    <td className="px-4 py-3 text-sm text-gray-700">{a.department?.name || '-'}</td>
                                    <td className="px-4 py-3">
                                        <div>
                                            <p className="text-sm text-gray-700">{a.position?.name || '-'}</p>
                                            <p className="text-xs text-gray-400">{a.position?.access_level}</p>
                                        </div>
                                    </td>
                                    <td className="px-4 py-3">
                                        <button
                                            onClick={() => handleToggle(a.id)}
                                            disabled={togglingId === a.id}
                                            className={cn(
                                                'relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2',
                                                a.is_purchasing_admin ? 'bg-emerald-600' : 'bg-gray-200',
                                                togglingId === a.id && 'opacity-50 cursor-wait'
                                            )}
                                        >
                                            <span
                                                className={cn(
                                                    'inline-block h-4 w-4 transform rounded-full bg-white transition-transform',
                                                    a.is_purchasing_admin ? 'translate-x-6' : 'translate-x-1'
                                                )}
                                            />
                                        </button>
                                    </td>
                                    <td className="px-4 py-3">
                                        {a.is_purchasing_admin ? (
                                            <button
                                                onClick={() => handleToggleReport(a.id)}
                                                disabled={togglingReportId === a.id}
                                                className={cn(
                                                    'relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2',
                                                    a.is_purchasing_report_access ? 'bg-indigo-600' : 'bg-gray-200',
                                                    togglingReportId === a.id && 'opacity-50 cursor-wait'
                                                )}
                                            >
                                                <span
                                                    className={cn(
                                                        'inline-block h-4 w-4 transform rounded-full bg-white transition-transform',
                                                        a.is_purchasing_report_access ? 'translate-x-6' : 'translate-x-1'
                                                    )}
                                                />
                                            </button>
                                        ) : (
                                            <div className="relative group">
                                                <button
                                                    disabled
                                                    className="relative inline-flex h-6 w-11 items-center rounded-full bg-gray-100 opacity-50 cursor-not-allowed"
                                                >
                                                    <span className="inline-block h-4 w-4 transform rounded-full bg-white translate-x-1" />
                                                </button>
                                                <div className="absolute bottom-full left-1/2 -translate-x-1/2 mb-1 px-2 py-1 bg-gray-800 text-white text-xs rounded whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none z-10">
                                                    Aktifkan Purchasing Admin terlebih dahulu
                                                </div>
                                            </div>
                                        )}
                                    </td>
                                    <td className="px-4 py-3">
                                        {a.is_purchasing_admin ? (
                                            <button
                                                onClick={() => handleToggleReadonly(a.id)}
                                                disabled={togglingReadonlyId === a.id}
                                                className={cn(
                                                    'relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2',
                                                    a.is_purchasing_readonly ? 'bg-amber-500' : 'bg-gray-200',
                                                    togglingReadonlyId === a.id && 'opacity-50 cursor-wait'
                                                )}
                                            >
                                                <span
                                                    className={cn(
                                                        'inline-block h-4 w-4 transform rounded-full bg-white transition-transform',
                                                        a.is_purchasing_readonly ? 'translate-x-6' : 'translate-x-1'
                                                    )}
                                                />
                                            </button>
                                        ) : (
                                            <button
                                                disabled
                                                className="relative inline-flex h-6 w-11 items-center rounded-full bg-gray-100 opacity-50 cursor-not-allowed"
                                            >
                                                <span className="inline-block h-4 w-4 transform rounded-full bg-white translate-x-1" />
                                            </button>
                                        )}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>

                    {/* Pagination */}
                    {assignments.meta && assignments.meta.last_page > 1 && (
                        <div className="px-4 py-3 border-t border-gray-100 flex items-center justify-between">
                            <p className="text-sm text-gray-500">
                                Showing {assignments.meta.from} to {assignments.meta.to} of {assignments.meta.total}
                            </p>
                            <div className="flex gap-1">
                                {assignments.meta.links.map((link, i) => (
                                    <button
                                        key={i}
                                        disabled={!link.url}
                                        onClick={() => link.url && router.get(link.url, {}, { preserveState: true, preserveScroll: true })}
                                        className={cn(
                                            'px-3 py-1 text-sm rounded-lg transition-colors',
                                            link.active ? 'bg-emerald-600 text-white' : 'text-gray-600 hover:bg-gray-100',
                                            !link.url && 'opacity-40 cursor-not-allowed'
                                        )}
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                    />
                                ))}
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </>
    );
}
