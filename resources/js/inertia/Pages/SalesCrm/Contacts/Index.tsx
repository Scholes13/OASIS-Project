import { useState, useEffect } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import { Plus, Users, UserCheck, TrendingUp, Building } from 'lucide-react';
import { DataTable, type PaginationData } from '@/components/admin/DataTable';
import { StatCard } from '@/components/admin/StatCard';
import { Badge } from '@/components/ui/Badge';
import { Button } from '@/components/ui/button';
import { toast } from '@/components/ui/toast';
import type { ColumnDef } from '@tanstack/react-table';
import type { PageProps, User } from '@/types';

// Types
interface Contact {
    id: number;
    business_unit_id: number;
    code: string;
    name: string;
    email: string | null;
    phone: string | null;
    mobile: string | null;
    company: string | null;
    department: string | null;
    position: string | null;
    status: 'active' | 'inactive' | 'archived';
    category: 'lead' | 'prospect' | 'customer' | 'partner';
    assigned_to: number;
    created_at: string;
    updated_at: string;
    assignedTo?: User;
}

interface ContactStats {
    total: number;
    active: number;
    leads: number;
    customers: number;
}

interface ContactsIndexProps extends PageProps {
    contacts: {
        data: Contact[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number;
        to: number;
    };
    stats: ContactStats;
    filters: {
        search: string;
        status: string;
        category: string;
        company: string;
    };
}

// Category Badge Component
function CategoryBadge({ category }: { category: Contact['category'] }) {
    const config = {
        lead: { variant: 'info' as const, label: 'Lead' },
        prospect: { variant: 'warning' as const, label: 'Prospect' },
        customer: { variant: 'success' as const, label: 'Customer' },
        partner: { variant: 'default' as const, label: 'Partner' },
    };

    const { variant, label } = config[category] || config.lead;

    return <Badge variant={variant}>{label}</Badge>;
}

// Status Badge Component
function StatusBadge({ status }: { status: Contact['status'] }) {
    const config = {
        active: { variant: 'success' as const, label: 'Active' },
        inactive: { variant: 'warning' as const, label: 'Inactive' },
        archived: { variant: 'default' as const, label: 'Archived' },
    };

    const { variant, label } = config[status] || config.active;

    return <Badge variant={variant}>{label}</Badge>;
}

export default function Index({ contacts, stats, filters }: ContactsIndexProps) {
    const { flash } = usePage<PageProps>().props;
    const [searchTerm, setSearchTerm] = useState(filters.search || '');
    const [statusFilter, setStatusFilter] = useState(filters.status || '');
    const [categoryFilter, setCategoryFilter] = useState(filters.category || '');

    // Handle flash messages
    useEffect(() => {
        if (flash.success) toast.success(flash.success);
        if (flash.error) toast.error(flash.error);
    }, [flash]);

    // Handle filter changes
    const handleFilterChange = () => {
        router.get(
            route('sales-crm.contacts.index'),
            {
                search: searchTerm,
                status: statusFilter,
                category: categoryFilter,
            },
            {
                preserveState: true,
                preserveScroll: true,
            }
        );
    };

    // Handle page change
    const handlePageChange = (page: number) => {
        router.get(
            route('sales-crm.contacts.index'),
            {
                page,
                search: searchTerm,
                status: statusFilter,
                category: categoryFilter,
            },
            {
                preserveState: true,
                preserveScroll: true,
            }
        );
    };

    // Table columns
    const columns: ColumnDef<Contact>[] = [
        {
            accessorKey: 'code',
            header: 'Code',
            cell: ({ row }) => (
                <span className="font-mono text-sm text-gray-600">{row.original.code}</span>
            ),
        },
        {
            accessorKey: 'name',
            header: 'Name',
            cell: ({ row }) => (
                <div>
                    <div className="font-medium text-gray-900">{row.original.name}</div>
                    {row.original.position && (
                        <div className="text-xs text-gray-500">{row.original.position}</div>
                    )}
                </div>
            ),
        },
        {
            accessorKey: 'company',
            header: 'Company',
            cell: ({ row }) => (
                <div>
                    {row.original.company && (
                        <div className="text-sm text-gray-900">{row.original.company}</div>
                    )}
                    {row.original.department && (
                        <div className="text-xs text-gray-500">{row.original.department}</div>
                    )}
                </div>
            ),
        },
        {
            accessorKey: 'email',
            header: 'Contact Info',
            cell: ({ row }) => (
                <div className="text-sm">
                    {row.original.email && (
                        <div className="text-gray-900">{row.original.email}</div>
                    )}
                    {row.original.mobile && (
                        <div className="text-gray-500">{row.original.mobile}</div>
                    )}
                </div>
            ),
        },
        {
            accessorKey: 'category',
            header: 'Category',
            cell: ({ row }) => <CategoryBadge category={row.original.category} />,
        },
        {
            accessorKey: 'status',
            header: 'Status',
            cell: ({ row }) => <StatusBadge status={row.original.status} />,
        },
        {
            id: 'actions',
            header: 'Actions',
            cell: ({ row }) => (
                <div className="flex items-center gap-2">
                    <Button
                        variant="ghost"
                        size="sm"
                        onClick={() => router.visit(route('sales-crm.contacts.show', row.original.id))}
                        className="text-primary hover:text-primary"
                    >
                        View
                    </Button>
                    <Button
                        variant="ghost"
                        size="sm"
                        onClick={() => router.visit(route('sales-crm.contacts.edit', row.original.id))}
                        className="text-gray-600 hover:text-gray-900"
                    >
                        Edit
                    </Button>
                </div>
            ),
        },
    ];

    // Pagination data
    const pagination: PaginationData = {
        current_page: contacts.current_page,
        last_page: contacts.last_page,
        per_page: contacts.per_page,
        total: contacts.total,
        from: contacts.from,
        to: contacts.to,
    };

    return (
        <>
            <Head title="Contacts" />

            <div className="w-full px-6 py-6 lg:px-8">
                    {/* Page Header */}
                    <div className="mb-6 flex items-center justify-between">
                        <div>
                            <h1 className="text-2xl font-bold text-gray-900">Contacts</h1>
                            <p className="mt-1 text-sm text-gray-600">
                                Manage your business contacts and leads
                            </p>
                        </div>
                        <Button
                            onClick={() => router.visit(route('sales-crm.contacts.create'))}
                            className="bg-blue-600 hover:bg-blue-700 text-white"
                        >
                            <Plus className="w-4 h-4 mr-2" />
                            New Contact
                        </Button>
                    </div>

                    {/* Stats Cards */}
                    <div className="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                        <StatCard
                            title="Total Contacts"
                            value={stats.total}
                            icon={Users}
                            color="blue"
                        />
                        <StatCard
                            title="Active"
                            value={stats.active}
                            icon={UserCheck}
                            color="emerald"
                        />
                        <StatCard
                            title="Leads"
                            value={stats.leads}
                            icon={TrendingUp}
                            color="amber"
                        />
                        <StatCard
                            title="Customers"
                            value={stats.customers}
                            icon={Building}
                            color="indigo"
                        />
                    </div>

                    {/* Filters */}
                    <div className="bg-white rounded-xl border border-gray-100 p-4 mb-6">
                        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                            {/* Search */}
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Search
                                </label>
                                <input
                                    type="text"
                                    value={searchTerm}
                                    onChange={(e) => setSearchTerm(e.target.value)}
                                    placeholder="Search contacts..."
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                />
                            </div>

                            {/* Category */}
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Category
                                </label>
                                <select
                                    value={categoryFilter}
                                    onChange={(e) => setCategoryFilter(e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                >
                                    <option value="">All Categories</option>
                                    <option value="lead">Lead</option>
                                    <option value="prospect">Prospect</option>
                                    <option value="customer">Customer</option>
                                    <option value="partner">Partner</option>
                                </select>
                            </div>

                            {/* Status */}
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Status
                                </label>
                                <select
                                    value={statusFilter}
                                    onChange={(e) => setStatusFilter(e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                >
                                    <option value="">All Status</option>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                    <option value="archived">Archived</option>
                                </select>
                            </div>

                            {/* Filter Button */}
                            <div className="flex items-end">
                                <Button
                                    onClick={handleFilterChange}
                                    className="w-full bg-blue-600 hover:bg-blue-700 text-white"
                                >
                                    Apply Filters
                                </Button>
                            </div>
                        </div>
                    </div>

                    {/* Contacts Table */}
                    <div className="bg-white rounded-xl border border-gray-100 overflow-hidden">
                        <div className="px-5 py-4 border-b border-gray-100">
                            <h3 className="text-base font-semibold text-gray-900">Contacts</h3>
                        </div>
                        <DataTable
                            data={contacts.data}
                            columns={columns}
                            pagination={pagination}
                            onPageChange={handlePageChange}
                            emptyMessage="No contacts found. Create your first contact to get started."
                        />
                    </div>
            </div>
        </>
    );
}
