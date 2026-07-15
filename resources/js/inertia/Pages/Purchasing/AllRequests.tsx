import { FormEvent, useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { ArrowRight, PackageSearch, Search, X } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select } from '@/components/ui/select';
import { formatCurrency, formatDate } from '@/lib/formatters';
import type { PageProps } from '@/types';
import type { AllPurchasingRequest, PaginatedData } from '@/types/purchasing';

interface DepartmentOption {
    id: number;
    business_unit_id: number;
    name: string;
    code: string;
    business_unit?: { code: string };
}

interface AllRequestsProps extends PageProps {
    requests: PaginatedData<AllPurchasingRequest>;
    filters: {
        search?: string;
        type?: string;
        status?: string;
        department_id?: string;
        date_from?: string;
        date_to?: string;
        per_page?: string;
    };
    departments: DepartmentOption[];
}

const statusOptions = [
    'draft',
    'submitted',
    'in_approval',
    'approved',
    'ga_review',
    'ga_rejected',
    'ready_for_purchasing',
    'rejected',
    'voided',
    'done',
].map((status) => ({
    value: status,
    label: status.replace(/_/g, ' ').replace(/\b\w/g, (character: string) => character.toUpperCase()),
}));

const statusLabel = (status: string) => status
    .replace(/_/g, ' ')
    .replace(/^\w/, (character) => character.toUpperCase());

const displayRequestNumber = (number: string) => number.replace('.', '/');

export default function AllRequests({ requests, filters, departments, currentBusinessUnit }: AllRequestsProps) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [type, setType] = useState(filters.type ?? '');
    const [status, setStatus] = useState(filters.status ?? '');
    const [departmentId, setDepartmentId] = useState(filters.department_id ?? '');
    const [dateFrom, setDateFrom] = useState(filters.date_from ?? '');
    const [dateTo, setDateTo] = useState(filters.date_to ?? '');
    const [perPage, setPerPage] = useState(filters.per_page ?? '15');
    const hasActiveFilters = Boolean(search || type || status || departmentId || dateFrom || dateTo);
    const dateRangeInvalid = Boolean(dateFrom && dateTo && dateFrom > dateTo);

    const submit = (event?: FormEvent) => {
        event?.preventDefault();
        if (dateRangeInvalid) return;

        router.get(route('purchasing.all-requests'), {
            search: search || undefined,
            type: type || undefined,
            status: status || undefined,
            department_id: departmentId || undefined,
            date_from: dateFrom || undefined,
            date_to: dateTo || undefined,
            per_page: perPage,
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const clearFilters = () => {
        setSearch('');
        setType('');
        setStatus('');
        setDepartmentId('');
        setDateFrom('');
        setDateTo('');
        setPerPage('15');
        router.get(route('purchasing.all-requests'));
    };

    return (
        <>
            <Head title="All Purchasing Requests" />
            <div className="min-h-[calc(100vh-4.5rem)] min-w-0 overflow-x-hidden px-4 py-7 lg:px-8">
                <div className="mx-auto min-w-0 max-w-[93.75rem]">
                    <header className="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <h1 className="text-[1.625rem] font-semibold tracking-[-0.025em] text-slate-950">All Requests</h1>
                            <p className="mt-1.5 max-w-3xl text-sm leading-6 text-slate-500">
                                Purchase and stock requests in {currentBusinessUnit?.name ?? 'the selected business unit'} and permitted business units.
                            </p>
                        </div>
                        <p className="shrink-0 text-sm text-slate-500">
                            <span className="font-semibold tabular-nums text-slate-900">{requests.meta.total}</span> requests
                        </p>
                    </header>

                    <section className="w-full min-w-0 max-w-full overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-[0_1px_2px_rgba(15,23,42,0.03),0_8px_24px_rgba(15,23,42,0.025)]">
                        <form onSubmit={submit} className="border-b border-slate-200/80 bg-zinc-50/60 p-3 sm:p-4">
                            <div className="grid min-w-0 gap-2.5 md:grid-cols-2 2xl:grid-cols-[minmax(13rem,1fr)_8rem_8.5rem_minmax(11rem,1fr)_24rem_auto] [&_[role=button]]:shadow-none">
                                <div className="relative min-w-0">
                                    <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                                    <Input
                                        aria-label="Search requests"
                                        value={search}
                                        onChange={(event) => setSearch(event.target.value)}
                                        placeholder="Number, purpose, or requester"
                                        className="border-zinc-300 bg-white pl-9 shadow-none"
                                    />
                                </div>
                                <Select
                                    ariaLabel="Request type"
                                    value={type}
                                    onChange={(value) => setType(String(value))}
                                    options={[
                                        { value: '', label: 'All request types' },
                                        { value: 'purchase_request', label: 'Purchase Request' },
                                        { value: 'stock_request', label: 'Stock Request' },
                                    ]}
                                />
                                <Select
                                    ariaLabel="Request status"
                                    value={status}
                                    onChange={(value) => setStatus(String(value))}
                                    options={[{ value: '', label: 'All statuses' }, ...statusOptions]}
                                />
                                <Select
                                    ariaLabel="Department"
                                    value={departmentId}
                                    onChange={(value) => setDepartmentId(String(value))}
                                    options={[
                                        { value: '', label: 'All departments' },
                                        ...departments.map((department) => ({
                                            value: String(department.id),
                                            label: `${department.business_unit?.code ?? currentBusinessUnit?.code ?? ''} / ${department.code} - ${department.name}`,
                                        })),
                                    ]}
                                />
                                <div
                                    role="group"
                                    aria-labelledby="requested-date-range-label"
                                    className="grid min-w-0 grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] items-center rounded-md border border-zinc-300 bg-white shadow-sm focus-within:border-primary focus-within:ring-1 focus-within:ring-primary md:col-span-2 2xl:col-span-1"
                                >
                                    <span id="requested-date-range-label" className="sr-only">Requested date range</span>
                                    <label className="grid min-w-0 grid-cols-[auto_minmax(0,1fr)] items-center pl-2.5">
                                        <span className="text-[0.6875rem] font-semibold uppercase tracking-[0.04em] text-zinc-400">From</span>
                                        <Input
                                            aria-label="Date from"
                                            type="date"
                                            value={dateFrom}
                                            max={dateTo || undefined}
                                            onChange={(event) => setDateFrom(event.target.value)}
                                            className="border-0 bg-transparent px-2 shadow-none focus-visible:ring-0"
                                        />
                                    </label>
                                    <ArrowRight className="h-3.5 w-3.5 text-zinc-300" aria-hidden="true" />
                                    <label className="grid min-w-0 grid-cols-[auto_minmax(0,1fr)] items-center pl-2">
                                        <span className="text-[0.6875rem] font-semibold uppercase tracking-[0.04em] text-zinc-400">To</span>
                                        <Input
                                            aria-label="Date to"
                                            type="date"
                                            value={dateTo}
                                            min={dateFrom || undefined}
                                            onChange={(event) => setDateTo(event.target.value)}
                                            aria-invalid={dateRangeInvalid}
                                            aria-describedby={dateRangeInvalid ? 'date-range-error' : undefined}
                                            className="border-0 bg-transparent px-2 shadow-none focus-visible:ring-0"
                                        />
                                    </label>
                                </div>
                                <div className="flex min-w-0 gap-2 md:col-span-2 2xl:col-span-1">
                                    {hasActiveFilters && (
                                        <button
                                            type="button"
                                            onClick={clearFilters}
                                            aria-label="Clear filters"
                                            title="Clear filters"
                                            className="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-md border border-zinc-300 bg-white text-zinc-400 transition-colors hover:border-zinc-400 hover:text-zinc-700 focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-primary"
                                        >
                                            <X className="h-4 w-4" />
                                        </button>
                                    )}
                                    <Button
                                        type="submit"
                                        disabled={dateRangeInvalid}
                                        className="min-w-[4.5rem] flex-1 rounded-md shadow-none 2xl:flex-none"
                                    >
                                        Apply
                                    </Button>
                                </div>
                            </div>
                            {dateRangeInvalid && (
                                <p id="date-range-error" className="mt-2 text-xs font-medium text-red-600">From date must be before To date.</p>
                            )}
                        </form>

                        <div
                            role="region"
                            aria-label="Purchasing requests"
                            className="w-full min-w-0"
                        >
                            <div className="hidden grid-cols-[minmax(11.25rem,1fr)_minmax(16.25rem,2.2fr)_minmax(10rem,1fr)_5rem_8.125rem_7.5rem_9.375rem] gap-4 border-b border-zinc-200 bg-zinc-50 px-5 py-3 text-xs font-medium text-zinc-500 2xl:grid">
                                <span>Request</span>
                                <span>Purpose</span>
                                <span>Requester</span>
                                <span>Dept.</span>
                                <span className="text-right">Amount</span>
                                <span>Requested</span>
                                <span>Status</span>
                            </div>
                            <div className="divide-y divide-slate-100/80">
                                {requests.data.map((request) => (
                                    <article
                                        key={`${request.type}-${request.id}`}
                                        className="group relative min-w-0 px-4 py-4 transition-colors hover:bg-slate-50/70 2xl:grid 2xl:grid-cols-[minmax(11.25rem,1fr)_minmax(16.25rem,2.2fr)_minmax(10rem,1fr)_5rem_8.125rem_7.5rem_9.375rem] 2xl:items-center 2xl:gap-4 2xl:px-5 2xl:py-3.5"
                                    >
                                        <div className="pr-40 2xl:pr-0">
                                            <div>
                                                <Link
                                                    href={request.show_url}
                                                    className="text-sm font-semibold tracking-[-0.01em] text-slate-950 transition-colors group-hover:text-blue-700"
                                                >
                                                    {displayRequestNumber(request.number)}
                                                </Link>
                                                <div className="mt-0.5 text-xs text-slate-400">
                                                    {request.items_count} {request.items_count === 1 ? 'item' : 'items'}
                                                </div>
                                            </div>
                                        </div>
                                        <p className="mt-3 line-clamp-2 text-sm leading-5 text-slate-700 2xl:mt-0">{request.summary}</p>
                                        <div className="mt-3 grid grid-cols-1 gap-x-4 gap-y-2 text-sm sm:grid-cols-2 2xl:contents">
                                            <div>
                                                <span className="text-xs text-slate-400 2xl:hidden">Requester</span>
                                                <p className="truncate text-slate-700">{request.requester_name}</p>
                                            </div>
                                            <div>
                                                <span className="text-xs text-slate-400 2xl:hidden">Department</span>
                                                <p className="text-slate-600">{request.department_code}</p>
                                            </div>
                                            <p className="whitespace-nowrap font-medium tabular-nums text-slate-800 2xl:text-right">
                                                <span className="mr-1 text-xs font-normal text-slate-400 2xl:hidden">Amount</span>
                                                {request.currency} {formatCurrency(request.total_amount, request.currency === 'IDR' ? 0 : 2)}
                                            </p>
                                            <p className="whitespace-nowrap text-slate-600">
                                                <span className="mr-1 text-xs text-slate-400 2xl:hidden">Requested</span>
                                                {formatDate(request.date_of_request)}
                                            </p>
                                        </div>
                                        <span className="absolute right-4 top-4 inline-flex w-fit max-w-[9rem] truncate rounded-md bg-slate-100/80 px-2 py-1 text-xs font-medium text-slate-600 ring-1 ring-inset ring-slate-200/70 2xl:static 2xl:max-w-none">
                                            {statusLabel(request.status)}
                                        </span>
                                    </article>
                                ))}
                            </div>
                        </div>

                        {requests.data.length === 0 && (
                            <div className="px-6 py-14 text-center">
                                <PackageSearch className="mx-auto h-8 w-8 text-slate-300" />
                                <h2 className="mt-3 text-sm font-semibold text-slate-800">No purchasing requests found</h2>
                                <p className="mt-1 text-sm text-slate-500">Change filters or search terms.</p>
                            </div>
                        )}

                        {requests.meta.total > 0 && (
                            <footer className="flex flex-col gap-3 border-t border-zinc-200 bg-zinc-50 px-4 py-3 lg:flex-row lg:items-center lg:justify-between">
                                <p className="text-sm text-slate-500">
                                    Showing <span className="font-medium tabular-nums text-slate-700">{requests.meta.from ?? 0}–{requests.meta.to ?? 0}</span> of {requests.meta.total}
                                </p>
                                <div className="flex flex-wrap items-center gap-3">
                                    <Select
                                        ariaLabel="Rows per page"
                                        value={perPage}
                                        onChange={(value) => {
                                            setPerPage(String(value));
                                            router.get(route('purchasing.all-requests'), {
                                                search: filters.search || undefined,
                                                type: filters.type || undefined,
                                                status: filters.status || undefined,
                                                department_id: filters.department_id || undefined,
                                                date_from: filters.date_from || undefined,
                                                date_to: filters.date_to || undefined,
                                                per_page: String(value),
                                            }, { preserveState: true, preserveScroll: true });
                                        }}
                                        className="w-32"
                                        options={[
                                            { value: '15', label: '15 rows' },
                                            { value: '25', label: '25 rows' },
                                            { value: '50', label: '50 rows' },
                                        ]}
                                    />
                                    {requests.links.prev ? (
                                        <Link
                                            preserveScroll
                                            href={requests.links.prev}
                                            className="rounded-md border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-600 hover:border-slate-300 hover:text-slate-900"
                                        >
                                            Previous
                                        </Link>
                                    ) : null}
                                    <span className="text-sm tabular-nums text-slate-500">Page {requests.meta.current_page} of {requests.meta.last_page}</span>
                                    {requests.links.next ? (
                                        <Link
                                            preserveScroll
                                            href={requests.links.next}
                                            className="rounded-md border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-600 hover:border-slate-300 hover:text-slate-900"
                                        >
                                            Next
                                        </Link>
                                    ) : null}
                                </div>
                            </footer>
                        )}
                    </section>
                </div>
            </div>
        </>
    );
}
