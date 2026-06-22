import { useMemo } from 'react';
import { Link, router } from '@inertiajs/react';
import { Menu, Transition } from '@headlessui/react';
import { ColumnDef } from '@tanstack/react-table';
import { format } from 'date-fns';
import { ChevronDown, MoreHorizontal, UserPlus } from 'lucide-react';
import { Card } from '@/components/ui/Card';
import { Badge } from '@/components/ui/Badge';
import { DataTable } from '@/components/ui/data-table';
import { TicketPriorityBadge } from '@/components/Ticket/TicketPriorityBadge';
import { TicketStatusBadge } from '@/components/Ticket/TicketStatusBadge';
import { SlaBadge } from '@/components/Ticket/SlaBadge';
import { toast } from '@/components/ui/toast';
import { cn } from '@/lib/utils';
import type { Ticket } from '@/types';

interface TicketIndexTableProps {
    tickets: Ticket[];
    isFiltering: boolean;
    currentUserId?: number;
    onAssignClick: (ticketId: number) => void;
}

export function TicketIndexTable({
    tickets,
    isFiltering,
    currentUserId,
    onAssignClick,
}: TicketIndexTableProps) {
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
        { accessorKey: 'requester', header: 'Requester', cell: ({ row }) => row.original.requester?.name || '-' },
        {
            accessorKey: 'category',
            header: 'Category',
            cell: ({ row }) => row.original.category ? (
                <Badge
                    variant="default"
                    className="text-xs"
                >
                    {row.original.category.name}
                </Badge>
            ) : '-',
        },
        { accessorKey: 'priority', header: 'Priority', cell: ({ row }) => <TicketPriorityBadge priority={row.original.priority} /> },
        {
            accessorKey: 'status',
            header: 'Status',
            cell: ({ row }) => <TicketStatusMenu ticket={row.original} />,
        },
        {
            accessorKey: 'assigned_user',
            header: 'Assigned To',
            cell: ({ row }) => row.original.assigned_user ? (
                <span className="text-gray-900">{row.original.assigned_user.name}</span>
            ) : (
                <button
                    onClick={(event) => {
                        event.stopPropagation();
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
            ),
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
        {
            id: 'actions',
            header: '',
            cell: ({ row }) => (
                <TicketRowActions
                    ticketId={row.original.id}
                    onAssignClick={onAssignClick}
                />
            ),
        },
    ], [currentUserId, onAssignClick]);

    return (
        <Card className="border border-gray-200 rounded-lg overflow-hidden">
            <DataTable
                columns={columns}
                data={tickets}
                searchKey="title"
                searchPlaceholder="Search by title..."
                showSearch={false}
                showColumnToggle={false}
                pageSize={20}
                emptyMessage="No tickets found."
                loading={isFiltering}
            />
        </Card>
    );
}

function TicketStatusMenu({ ticket }: { ticket: Ticket }) {
    const isDone = ticket.status === 'done';
    const isCancelled = ticket.status === 'cancelled';

    if (isDone || isCancelled) return <TicketStatusBadge status={ticket.status} />;

    const nextStatuses = ticket.status === 'waiting'
        ? [{ value: 'in_progress', label: 'Mulai Proses' }, { value: 'cancelled', label: 'Batalkan' }]
        : [{ value: 'done', label: 'Tandai Selesai' }, { value: 'cancelled', label: 'Batalkan' }];

    return (
        <Menu
            as="div"
            className="relative inline-block text-left"
        >
            <Menu.Button className="cursor-pointer focus:outline-none inline-flex items-center gap-1">
                <TicketStatusBadge status={ticket.status} />
                <ChevronDown className="w-3 h-3 text-gray-400" />
            </Menu.Button>
            <Transition
                enter="transition ease-out duration-100"
                enterFrom="transform opacity-0 scale-95"
                enterTo="transform opacity-100 scale-100"
                leave="transition ease-in duration-75"
                leaveFrom="transform opacity-100 scale-100"
                leaveTo="transform opacity-0 scale-95"
            >
                <Menu.Items className="fixed z-[9999] mt-1 w-44 rounded-lg bg-white border border-gray-200 shadow-lg focus:outline-none py-1">
                    {nextStatuses.map((nextStatus) => (
                        <Menu.Item key={nextStatus.value}>
                            {({ active }) => (
                                <button
                                    type="button"
                                    className={cn('w-full text-left px-4 py-2 text-sm', active ? 'bg-gray-50 text-gray-900' : 'text-gray-700')}
                                    onClick={() => router.put(route('it-support.admin.tickets.changeStatus', { ticket: ticket.id }), { status: nextStatus.value }, {
                                        preserveScroll: true,
                                        onSuccess: () => toast.success(`Status diubah ke "${nextStatus.label}"`),
                                        onError: () => toast.error('Gagal mengubah status'),
                                    })}
                                >
                                    {nextStatus.label}
                                </button>
                            )}
                        </Menu.Item>
                    ))}
                </Menu.Items>
            </Transition>
        </Menu>
    );
}

function TicketRowActions({ ticketId, onAssignClick }: { ticketId: number; onAssignClick: (ticketId: number) => void }) {
    return (
        <Menu
            as="div"
            className="relative inline-block text-left"
        >
            <Menu.Button className="h-8 w-8 p-0 inline-flex items-center justify-center rounded-md text-gray-500 hover:bg-gray-100 hover:text-gray-700 transition-colors">
                <MoreHorizontal className="h-4 w-4" />
            </Menu.Button>
            <Transition
                enter="transition ease-out duration-100"
                enterFrom="transform opacity-0 scale-95"
                enterTo="transform opacity-100 scale-100"
                leave="transition ease-in duration-75"
                leaveFrom="transform opacity-100 scale-100"
                leaveTo="transform opacity-0 scale-95"
            >
                <Menu.Items className="fixed z-[9999] mt-1 w-44 rounded-lg bg-white border border-gray-200 shadow-lg focus:outline-none py-1">
                    <Menu.Item>
                        {({ active }) => (
                            <button
                                type="button"
                                className={cn('w-full text-left px-4 py-2 text-sm', active ? 'bg-gray-50 text-gray-900' : 'text-gray-700')}
                                onClick={() => router.visit(route('it-support.admin.tickets.show', { ticket: ticketId }))}
                            >
                                Lihat Detail
                            </button>
                        )}
                    </Menu.Item>
                    <Menu.Item>
                        {({ active }) => (
                            <button
                                type="button"
                                className={cn('w-full text-left px-4 py-2 text-sm', active ? 'bg-gray-50 text-gray-900' : 'text-gray-700')}
                                onClick={() => onAssignClick(ticketId)}
                            >
                                Tugaskan ke Staff
                            </button>
                        )}
                    </Menu.Item>
                </Menu.Items>
            </Transition>
        </Menu>
    );
}
