import { useState, useMemo } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useForm } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';
import {
    Search, Filter, Plus, Download, RefreshCw,
    Eye, UserPlus, MoreHorizontal,
} from 'lucide-react';
import { format } from 'date-fns';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/Card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select } from '@/components/ui/select';
import { Badge } from '@/components/ui/Badge';
import { DataTable } from '@/components/ui/data-table';
import { TicketStatusBadge } from '@/components/Ticket/TicketStatusBadge';
import { TicketPriorityBadge } from '@/components/Ticket/TicketPriorityBadge';
import { SlaBadge } from '@/components/Ticket/SlaBadge';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { toast } from '@/components/ui/toast';
import { cn } from '@/lib/utils';
import type { PageProps, Ticket, TicketCategory, User, PaginatedData, TicketStatus, TicketPriority, TicketFilters } from '@/types';

// ── Types ──────────────────────────────────────────────────────────────
interface IndexProps extends PageProps {
    tickets: PaginatedData<Ticket>;
    categories: TicketCategory[];
    staff: User[];
    filters: TicketFilters;
}

// ── Status options ──────────────────────────────────────────────────────
const statusTabs = [
    { id: '', label: 'All' },
    { id: 'waiting', label: 'Menunggu' },
    { id: 'in_progress', label: 'Dalam Proses' },
    { id: 'done', label: 'Selesai' },
    { id: 'cancelled', label: 'Dibatalkan' },
] as const;

const priorityOptions: { value: TicketPriority | ''; label: string }[] = [
    { value: '', label: 'All Priority' },
    { value: 'low', label: 'Rendah' },
    { value: 'medium', label: 'Sedang' },
    { value: 'high', label: 'Tinggi' },
    { value: 'critical', label: 'Kritis' },
];

export default function TicketIndex({ tickets, categories, staff, filters }: IndexProps) {
    const { flash, auth } = usePage<PageProps>().props;
    const currentUserId = (auth as any)?.user?.id;

    // Filters state
    const [search, setSearch] = useState(filters.search);
    const [status, setStatus] = useState<TicketStatus | ''>(filters.status as TicketStatus | '');
    const [priority, setPriority] = useState<TicketPriority | ''>(filters.priority as TicketPriority | '');
    const [categoryId, setCategoryId] = useState<string>(filters.category_id?.toString() || '');
    const [assignedUserId, setAssignedUserId] = useState<string>(filters.assigned_user_id?.toString() || '');
    const [isFiltering, setIsFiltering] = useState(false);

    // Assignment dialog
    const [assignDialogOpen, setAssignDialogOpen] = useState(false);
    const [selectedTicketId, setSelectedTicketId] = useState<number | null>(null);
    const [assigneeId, setAssigneeId] = useState<string>('');

    const applyFilters = (overrides: Record<string, string> = {}) => {
        setIsFiltering(true);
        router.get(route('it-support.admin.tickets.index'), {
            search: overrides.search ?? search,
            status: overrides.status ?? status,
            priority: overrides.priority ?? priority,
            category_id: overrides.category_id ?? categoryId,
            assigned_user_id: overrides.assigned_user_id ?? assignedUserId,
        }, {
            preserveState: true,
            preserveScroll: true,
            onFinish: () => setIsFiltering(false),
        });
    };

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        applyFilters();
    };

    const handleStatusChange = (newStatus: TicketStatus | '') => {
        setStatus(newStatus);
        applyFilters({ status: newStatus });
    };

    const handlePriorityChange = (value: string) => {
        setPriority(value as TicketPriority | '');
        applyFilters({ priority: value });
    };

    const handleCategoryChange = (value: string) => {
        setCategoryId(value);
        applyFilters({ category_id: value });
    };

    const handleAssignedChange = (value: string) => {
        setAssignedUserId(value);
        applyFilters({ assigned_user_id: value });
    };

    // Handle assign ticket
    const handleAssignClick = (ticketId: number) => {
        setSelectedTicketId(ticketId);
        setAssignDialogOpen(true);
    };

    const { post, setData, processing: isAssigning } = useForm({
        assigned_to: 0,
    });

    const submitAssignment = (e: React.FormEvent) => {
        e.preventDefault();
        if (!selectedTicketId || !assigneeId) return;

        setData('assigned_to', parseInt(assigneeId));

        post(route('it-support.admin.tickets.assign', { ticket: selectedTicketId }), {
            onSuccess: () => {
                toast.success('Ticket berhasil ditugaskan');
                setAssignDialogOpen(false);
                setAssigneeId('');
            },
            onError: () => {
                toast.error('Gagal menugaskan ticket');
            },
        });
    };

    // Table columns
    const columns: ColumnDef<Ticket>[] = useMemo(() => [
        {
            accessorKey: 'ticket_number',
            header: 'Ticket Number',
            cell: ({ row }) => (
                <Link href={route('it-support.admin.tickets.show', { ticket: row.original.id })}>
                    <span className="font-medium text-primary hover:underline">
                        {row.original.ticket_number}
                    </span>
                </Link>
            ),
        },
        {
            accessorKey: 'title',
            header: 'Title',
            cell: ({ row }) => (
                <span className="text-gray-900 max-w-xs truncate block">
                    {row.original.title}
                </span>
            ),
        },
        {
            accessorKey: 'requester',
            header: 'Requester',
            cell: ({ row }) => row.original.requester?.name || '-',
        },
        {
            accessorKey: 'category',
            header: 'Category',
            cell: ({ row }) => (
                row.original.category ? (
                    <Badge variant="default" className="text-xs">
                        {row.original.category.name}
                    </Badge>
                ) : '-'
            ),
        },
        {
            accessorKey: 'priority',
            header: 'Priority',
            cell: ({ row }) => <TicketPriorityBadge priority={row.original.priority} />,
        },
        {
            accessorKey: 'status',
            header: 'Status',
            cell: ({ row }) => <TicketStatusBadge status={row.original.status} />,
        },
        {
            accessorKey: 'assigned_user',
            header: 'Assigned To',
            cell: ({ row }) => {
                if (row.original.assigned_user) {
                    return <span className="text-gray-900">{row.original.assigned_user.name}</span>;
                }
                return (
                    <button
                        onClick={(e) => {
                            e.stopPropagation();
                            router.post(route('it-support.admin.tickets.assign', { ticket: row.original.id }), {
                                assigned_to: currentUserId,
                            }, {
                                preserveScroll: true,
                                onSuccess: () => toast.success('Ticket berhasil di-claim'),
                                onError: () => toast.error('Gagal claim ticket'),
                            });
                        }}
                        className="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium text-primary bg-primary/5 border border-primary/20 rounded-md hover:bg-primary/10 transition-colors"
                    >
                        <UserPlus className="w-3.5 h-3.5" />
                        Claim
                    </button>
                );
            },
        },
        {
            accessorKey: 'sla_deadline',
            header: 'SLA',
            cell: ({ row }) => (
                <SlaBadge
                    slaDeadline={row.original.sla_deadline}
                    isBreached={row.original.is_sla_breach}
                />
            ),
        },
        {
            accessorKey: 'created_at',
            header: 'Created At',
            cell: ({ row }) => (
                <span className="text-gray-600 text-sm">
                    {format(new Date(row.original.created_at), 'dd MMM yyyy HH:mm')}
                </span>
            ),
        },
        {id: 'actions',
            header: '',
            cell: ({ row }) => (
                <DropdownMenu>
                    <DropdownMenuTrigger>
                        <Button variant="ghost" size="sm" className="h-8 w-8 p-0">
                            <MoreHorizontal className="h-4 w-4" />
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end">
                        <DropdownMenuItem onClick={() => window.location.href = route('it-support.admin.tickets.show', { ticket: row.original.id })}>
                            <Eye className="w-4 h-4 mr-2" />
                            View
                        </DropdownMenuItem>
                        <DropdownMenuItem onClick={() => handleAssignClick(row.original.id)}>
                            <UserPlus className="w-4 h-4 mr-2" />
                            Assign
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            ),
        },
    ], []);

    // Category options
    const categoryOptions = useMemo(() => [
        { value: '', label: 'All Categories' },
        ...categories.map(c => ({ value: c.id.toString(), label: c.name })),
    ], [categories]);

    // Staff options
    const staffOptions = useMemo(() => [
        { value: '', label: 'Assign to...' },
        ...staff.map(s => ({ value: s.id.toString(), label: s.name })),
    ], [staff]);

    return (
        <>
            <Head title="Semua Tiket" />

            <div className="w-full px-6 py-6 lg:px-8 space-y-6">
                {/* ── Header ──────────────────────────────────────────────── */}
                <div className="flex flex-col xl:flex-row xl:items-end justify-between gap-4">
                    <div className="flex flex-col gap-1.5">
                        <h1 className="text-2xl font-bold text-gray-900 tracking-tight">Semua Tiket</h1>
                        <p className="text-sm text-gray-500">Kelola semua ticket dukungan IT</p>
                    </div>

                    <div className="flex items-center gap-2">
                        <Button
                            size="sm"
                            onClick={() => window.location.reload()}
                        >
                            <RefreshCw className="w-4 h-4 mr-2" />
                            Refresh
                        </Button>
                    </div>
                </div>

                {/* ── Filter Bar ─────────────────────────────────────── */}
                <Card className="p-4 shadow-sm border-gray-200/80">
                    <div className="flex flex-col lg:flex-row gap-4">
                        {/* Search */}
                        <form onSubmit={handleSearch} className="flex-1 flex gap-2">
                            <div className="relative flex-1">
                                <Search className="absolute top-1/2 left-3 -translate-y-1/2 w-4 h-4 text-gray-400" />
                                <Input
                                    placeholder="Search tickets..."
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    className="pl-10"
                                />
                            </div>
                            <Button type="submit" size="sm">
                                Search
                            </Button>
                        </form>

                        {/* Status Tabs */}
                        <div className="flex items-center gap-1 bg-gray-100 rounded-lg p-1">
                            {statusTabs.map((tab) => (
                                <button
                                    key={tab.id}
                                    onClick={() => handleStatusChange(tab.id as TicketStatus | '')}
                                    className={cn(
                                        'px-3 py-1.5 text-sm font-medium rounded-md transition-all',
                                        status === tab.id
                                            ? 'bg-white text-primary shadow-sm'
                                            : 'text-gray-600 hover:text-gray-900'
                                    )}
                                >
                                    {tab.label}
                                </button>
                            ))}
                        </div>

                        {/* Filters */}
                        <div className="flex items-center gap-2 flex-wrap">
                            <Select
                                value={priority}
                                onChange={(val: string | number) => handlePriorityChange(String(val))}
                                options={priorityOptions}
                                placeholder="Priority"
                                className="w-32"
                            />
                            <Select
                                value={categoryId}
                                onChange={(val: string | number) => handleCategoryChange(String(val))}
                                options={categoryOptions}
                                placeholder="Category"
                                className="w-40"
                            />
                            <Select
                                value={assignedUserId}
                                onChange={(val: string | number) => handleAssignedChange(String(val))}
                                options={staffOptions}
                                placeholder="Assigned"
                                className="w-40"
                            />
                        </div>
                    </div>
                </Card>

                {/* ── Tickets Table ────────────────────────────────────── */}
                <Card className="border border-gray-200 rounded-lg overflow-hidden">
                    <DataTable
                        columns={columns}
                        data={tickets.data}
                        searchKey="title"
                        searchPlaceholder="Search by title..."
                        showSearch={false}
                        showColumnToggle={false}
                        pageSize={20}
                        emptyMessage="No tickets found."
                        loading={isFiltering}
                    />
                </Card>

                {/* ── Assign Dialog ────────────────────────────────────── */}
                <Dialog open={assignDialogOpen} onClose={() => setAssignDialogOpen(false)}>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Assign Ticket</DialogTitle>
                        </DialogHeader>
                        <form onSubmit={submitAssignment}>
                            <div className="py-4">
                                <Select
                                    value={assigneeId}
                                    onChange={(val: string | number) => setAssigneeId(String(val))}
                                    options={staffOptions}
                                    placeholder="Select staff..."
                                />
                            </div>
                            <DialogFooter>
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => setAssignDialogOpen(false)}
                                >
                                    Cancel
                                </Button>
                                <Button type="submit" loading={isAssigning}>
                                    Assign
                                </Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>
        </>
    );
}