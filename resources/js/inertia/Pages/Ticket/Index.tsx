import { useState, useMemo } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import { useForm } from '@inertiajs/react';
import { RefreshCw } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { toast } from '@/components/ui/toast';
import { AssignmentDialog } from '@/components/Ticket/list/AssignmentDialog';
import { TicketIndexFilters } from '@/components/Ticket/list/TicketIndexFilters';
import { TicketIndexTable } from '@/components/Ticket/list/TicketIndexTable';
import type { PageProps, Ticket, TicketCategory, User, PaginatedData, TicketStatus, TicketPriority, TicketFilters } from '@/types';

// ── Types ──────────────────────────────────────────────────────────────
interface IndexProps extends PageProps {
    tickets: PaginatedData<Ticket>;
    categories: TicketCategory[];
    staff: User[];
    filters: TicketFilters;
}

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

                <TicketIndexFilters
                    search={search}
                    status={status}
                    priority={priority}
                    categoryId={categoryId}
                    assignedUserId={assignedUserId}
                    categoryOptions={categoryOptions}
                    staffOptions={staffOptions}
                    onSearchChange={setSearch}
                    onSearchSubmit={handleSearch}
                    onStatusChange={handleStatusChange}
                    onPriorityChange={handlePriorityChange}
                    onCategoryChange={handleCategoryChange}
                    onAssignedChange={handleAssignedChange}
                />

                <TicketIndexTable
                    tickets={tickets.data}
                    isFiltering={isFiltering}
                    currentUserId={currentUserId}
                    onAssignClick={handleAssignClick}
                />

                <AssignmentDialog
                    open={assignDialogOpen}
                    assigneeId={assigneeId}
                    staffOptions={staffOptions}
                    isAssigning={isAssigning}
                    onClose={() => setAssignDialogOpen(false)}
                    onAssigneeChange={setAssigneeId}
                    onSubmit={submitAssignment}
                />
            </div>
        </>
    );
}
