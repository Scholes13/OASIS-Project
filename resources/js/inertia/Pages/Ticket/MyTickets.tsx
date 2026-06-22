import { useState, useEffect } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { motion } from 'framer-motion';
import { Plus, Search, FileText, ChevronLeft, ChevronRight } from 'lucide-react';
import { TicketStatusBadge } from '@/components/Ticket/TicketStatusBadge';
import { TicketPriorityBadge } from '@/components/Ticket/TicketPriorityBadge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { PageProps, PaginatedData } from '@/types';
import type { Ticket, TicketStatus, TicketPriority } from '@/types';

interface MyTicketsPageProps extends PageProps {
    tickets: PaginatedData<Ticket>;
    filters: {
        search: string;
        status: TicketStatus | '';
        priority: TicketPriority | '';
    };
}

const statusOptions = [
    { value: '', label: 'Semua Status' },
    { value: 'waiting', label: 'Menunggu' },
    { value: 'in_progress', label: 'Dalam Proses' },
    { value: 'done', label: 'Selesai' },
    { value: 'cancelled', label: 'Dibatalkan' },
];

const priorityOptions = [
    { value: '', label: 'Semua Prioritas' },
    { value: 'low', label: 'Rendah' },
    { value: 'medium', label: 'Sedang' },
    { value: 'high', label: 'Tinggi' },
    { value: 'critical', label: 'Kritis' },
];

export default function MyTickets({
    tickets,
    filters,
    currentBusinessUnit
}: MyTicketsPageProps) {
    // Safe data access with Inertia v2 pagination
    const safeData = tickets?.data ?? [];
    const safeMeta = tickets?.meta ?? {
        from: (tickets as any)?.from ?? 0,
        to: (tickets as any)?.to ?? 0,
        total: (tickets as any)?.total ?? 0,
        last_page: (tickets as any)?.last_page ?? 1,
        links: (tickets as any)?.links ?? [],
    };
    const safeLinks = tickets?.links ?? {
        prev: (tickets as any)?.prev_page_url ?? null,
        next: (tickets as any)?.next_page_url ?? null,
    };

    const [search, setSearch] = useState(filters?.search || '');
    const [selectedStatus, setSelectedStatus] = useState(filters?.status || '');
    const [selectedPriority, setSelectedPriority] = useState(filters?.priority || '');
    const [isLoading, setIsLoading] = useState(false);
    const [isInitialLoad, setIsInitialLoad] = useState(true);

    useEffect(() => {
        setIsInitialLoad(false);
    }, []);

    // Debounce search
    useEffect(() => {
        if (isInitialLoad) return;

        const timer = setTimeout(() => {
            handleFilter();
        }, 300);

        return () => clearTimeout(timer);
    }, [search, selectedStatus, selectedPriority]);

    const handleFilter = () => {
        const params: Record<string, string> = {};

        if (search) params.search = search;
        if (selectedStatus) params.status = selectedStatus;
        if (selectedPriority) params.priority = selectedPriority;

        router.get(route('it-support.my-tickets'), params, {
            preserveState: true,
            preserveScroll: true,
            only: ['tickets'],
            onStart: () => setIsLoading(true),
            onFinish: () => setIsLoading(false),
        });
    };

    const handlePageChange = (url: string) => {
        router.get(url, {}, {
            preserveState: true,
            preserveScroll: true,
            only: ['tickets'],
            onStart: () => setIsLoading(true),
            onFinish: () => setIsLoading(false),
        });
    };

    const handleRowClick = (ticket: Ticket) => {
        router.visit(route('it-support.my-tickets.show', { ticket: ticket.id }));
    };

    const formatDate = (dateString: string) => {
        const date = new Date(dateString);
        return date.toLocaleDateString('id-ID', {
            day: 'numeric',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    return (
        <>
            <Head title="Tiket Saya" />

            <div className="w-full px-4 sm:px-6 lg:px-8 py-6">
                {/* Header */}
                <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
                    <div className="flex items-center justify-between">
                        <div>
                            <h1 className="text-xl font-semibold text-gray-900">
                                Tiket Saya
                            </h1>
                            <p className="text-sm text-gray-500 mt-0.5">
                                Kelola dan pantau ticket dukungan IT Anda
                            </p>
                        </div>
                        <div className="flex items-center space-x-3">
                            <Link href={route('it-support.submit')}>
                                <Button className="bg-primary hover:bg-blue-600">
                                    <Plus className="w-4 h-4 mr-2" />
                                    Ajukan Tiket Baru
                                </Button>
                            </Link>
                        </div>
                    </div>
                </div>

                {/* Filters */}
                <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
                    <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div className="md:col-span-2">
                            <div className="relative">
                                <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" />
                                <Input
                                    type="text"
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    placeholder="Cari nomor ticket atau judul..."
                                    className="pl-10"
                                />
                            </div>
                        </div>

                        <div>
                            <select
                                value={selectedStatus}
                                onChange={(e) => setSelectedStatus(e.target.value as TicketStatus | '')}
                                className="w-full px-3 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary bg-white"
                            >
                                {statusOptions.map(opt => (
                                    <option key={opt.value} value={opt.value}>{opt.label}</option>
                                ))}
                            </select>
                        </div>

                        <div>
                            <select
                                value={selectedPriority}
                                onChange={(e) => setSelectedPriority(e.target.value as TicketPriority | '')}
                                className="w-full px-3 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary bg-white"
                            >
                                {priorityOptions.map(opt => (
                                    <option key={opt.value} value={opt.value}>{opt.label}</option>
                                ))}
                            </select>
                        </div>
                    </div>
                </div>

                {/* Table */}
                <div className={`bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden transition-opacity duration-200 ${isLoading ? 'opacity-50' : ''}`}>
                    {isInitialLoad ? (
                        <div className="p-6">
                            <div className="animate-pulse space-y-4">
                                {[...Array(5)].map((_, i) => (
                                    <div key={i} className="h-16 bg-gray-100 rounded-lg"></div>
                                ))}
                            </div>
                        </div>
                    ) : safeData.length > 0 ? (
                        <>
                            <div className="overflow-x-auto">
                                <table className="w-full">
                                    <thead className="bg-gray-50 border-b border-gray-200">
                                        <tr>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                No. Tiket
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Judul
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Kategori
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Prioritas
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Status
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Dibuat
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody className="bg-white divide-y divide-gray-200">
                                        {safeData.map((ticket) => (
                                            <tr
                                                key={ticket.id}
                                                onClick={() => handleRowClick(ticket)}
                                                className="hover:bg-gray-50 cursor-pointer transition-colors"
                                            >
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <span className="text-sm font-medium text-primary">
                                                        {ticket.ticket_number}
                                                    </span>
                                                </td>
                                                <td className="px-6 py-4">
                                                    <div className="text-sm text-gray-900 line-clamp-1">
                                                        {ticket.title}
                                                    </div>
                                                    {ticket.category && (
                                                        <div className="text-xs text-gray-400 mt-0.5">
                                                            {ticket.category.name}
                                                        </div>
                                                    )}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    {ticket.category ? (
                                                        <span className="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-gray-100 text-gray-700">
                                                            {ticket.category.name}
                                                        </span>
                                                    ) : (
                                                        <span className="text-sm text-gray-400">-</span>
                                                    )}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <TicketPriorityBadge priority={ticket.priority} />
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <TicketStatusBadge status={ticket.status} />
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    {formatDate(ticket.created_at)}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>

                            {/* Pagination */}
                            {safeMeta.last_page > 1 && (
                                <div className="px-6 py-4 border-t border-gray-100">
                                    <div className="flex items-center justify-between">
                                        <p className="text-sm text-gray-400">
                                            Menampilkan {safeMeta.from || 0} - {safeMeta.to || 0} dari {safeMeta.total} hasil
                                        </p>

                                        <nav className="flex items-center gap-1">
                                            {safeLinks.prev ? (
                                                <button
                                                    onClick={() => handlePageChange(safeLinks.prev!)}
                                                    className="p-2 text-gray-400 hover:text-gray-600 transition-colors"
                                                >
                                                    <ChevronLeft className="w-5 h-5" />
                                                </button>
                                            ) : (
                                                <span className="p-2 text-gray-300 cursor-not-allowed">
                                                    <ChevronLeft className="w-5 h-5" />
                                                </span>
                                            )}

                                            <div className="flex items-center gap-1 mx-2">
                                                {safeMeta.links
                                                    .filter(link => !link.label.includes('Previous') && !link.label.includes('Next'))
                                                    .map((link, index) => (
                                                        <button
                                                            key={index}
                                                            onClick={() => link.url && handlePageChange(link.url)}
                                                            disabled={!link.url}
                                                            className={`w-8 h-8 flex items-center justify-center text-sm rounded-md transition-colors ${
                                                                link.active
                                                                    ? 'font-medium text-white bg-primary'
                                                                    : 'text-gray-500 hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed'
                                                            }`}
                                                        >
                                                            {link.label}
                                                        </button>
                                                    ))}
                                            </div>

                                            {safeLinks.next ? (
                                                <button
                                                    onClick={() => handlePageChange(safeLinks.next!)}
                                                    className="p-2 text-gray-400 hover:text-gray-600 transition-colors"
                                                >
                                                    <ChevronRight className="w-5 h-5" />
                                                </button>
                                            ) : (
                                                <span className="p-2 text-gray-300 cursor-not-allowed">
                                                    <ChevronRight className="w-5 h-5" />
                                                </span>
                                            )}
                                        </nav>
                                    </div>
                                </div>
                            )}
                        </>
                    ) : (
                        <motion.div
                            initial={{ opacity: 0, y: 20 }}
                            animate={{ opacity: 1, y: 0 }}
                        >
                            <div className="text-center py-16">
                                <div className="w-16 h-16 mx-auto bg-gray-50 rounded-full flex items-center justify-center mb-4">
                                    <FileText className="w-8 h-8 text-gray-300" />
                                </div>
                                <h3 className="text-base font-medium text-gray-600 mb-2">
                                    Tidak Ada Tiket
                                </h3>
                                <p className="text-sm text-gray-400 mb-6">
                                    Anda belum memiliki ticket dukungan IT
                                </p>
                                <Link href={route('it-support.submit')}>
                                    <Button>
                                        <Plus className="w-4 h-4 mr-2" />
                                        Ajukan Tiket Pertama Anda
                                    </Button>
                                </Link>
                            </div>
                        </motion.div>
                    )}
                </div>
            </div>
        </>
    );
}