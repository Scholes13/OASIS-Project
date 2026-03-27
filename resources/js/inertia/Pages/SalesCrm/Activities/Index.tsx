import { useState, useEffect } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import { Plus, Calendar, Phone, Users, MapPin, CheckCircle, Clock, XCircle } from 'lucide-react';
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
    code: string;
    name: string;
    company: string | null;
    email: string | null;
    phone: string | null;
}

interface Activity {
    id: number;
    business_unit_id: number;
    user_id: number;
    contact_id: number | null;
    activity_date: string;
    activity_type: 'call' | 'visit' | 'meeting' | 'blitz' | 'follow_up' | 'other';
    title: string;
    department: string | null;
    pic_name: string | null;
    pic_phone: string | null;
    office_address: string | null;
    description: string | null;
    status: 'planned' | 'completed' | 'cancelled';
    created_at: string;
    updated_at: string;
    user?: User;
    contact?: Contact;
}

interface ActivityStats {
    total: number;
    completed: number;
    planned: number;
    today: number;
}

interface ActivitiesIndexProps extends PageProps {
    activities: {
        data: Activity[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number;
        to: number;
    };
    stats: ActivityStats;
    filters: {
        search: string;
        activity_type: string;
        status: string;
        date_from: string;
        date_to: string;
    };
}

// Activity Type Badge Component
function ActivityTypeBadge({ type }: { type: Activity['activity_type'] }) {
    const config = {
        call: { icon: Phone, label: 'Phone Call', color: 'bg-blue-100 text-blue-700' },
        visit: { icon: MapPin, label: 'Site Visit', color: 'bg-purple-100 text-purple-700' },
        meeting: { icon: Users, label: 'Meeting', color: 'bg-blue-50 text-blue-700' },
        blitz: { icon: Calendar, label: 'Blitz', color: 'bg-amber-100 text-amber-700' },
        follow_up: { icon: CheckCircle, label: 'Follow Up', color: 'bg-emerald-100 text-emerald-700' },
        other: { icon: Calendar, label: 'Other', color: 'bg-gray-100 text-gray-700' },
    };

    const { icon: Icon, label, color } = config[type] || config.other;

    return (
        <span className={`inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium ${color}`}>
            <Icon className="w-3.5 h-3.5" />
            {label}
        </span>
    );
}

// Status Badge Component
function ActivityStatusBadge({ status }: { status: Activity['status'] }) {
    const config = {
        planned: { variant: 'warning' as const, icon: Clock, label: 'Planned' },
        completed: { variant: 'success' as const, icon: CheckCircle, label: 'Completed' },
        cancelled: { variant: 'default' as const, icon: XCircle, label: 'Cancelled' },
    };

    const { variant, icon: Icon, label } = config[status] || config.planned;

    return (
        <Badge variant={variant} className="inline-flex items-center gap-1">
            <Icon className="w-3.5 h-3.5" />
            {label}
        </Badge>
    );
}

export default function Index({ activities, stats, filters }: ActivitiesIndexProps) {
    const { flash } = usePage<PageProps>().props;
    const [searchTerm, setSearchTerm] = useState(filters.search || '');
    const [activityType, setActivityType] = useState(filters.activity_type || '');
    const [statusFilter, setStatusFilter] = useState(filters.status || '');

    // Handle flash messages
    useEffect(() => {
        if (flash.success) toast.success(flash.success);
        if (flash.error) toast.error(flash.error);
    }, [flash]);

    // Format date helper
    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString('en-GB', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
        });
    };

    // Handle filter changes
    const handleFilterChange = () => {
        router.get(
            route('sales-crm.activities.index'),
            {
                search: searchTerm,
                activity_type: activityType,
                status: statusFilter,
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
            route('sales-crm.activities.index'),
            {
                page,
                search: searchTerm,
                activity_type: activityType,
                status: statusFilter,
            },
            {
                preserveState: true,
                preserveScroll: true,
            }
        );
    };

    // Table columns
    const columns: ColumnDef<Activity>[] = [
        {
            accessorKey: 'activity_date',
            header: 'Date',
            cell: ({ row }) => (
                <span className="font-medium text-gray-900">
                    {formatDate(row.original.activity_date)}
                </span>
            ),
        },
        {
            accessorKey: 'activity_type',
            header: 'Type',
            cell: ({ row }) => <ActivityTypeBadge type={row.original.activity_type} />,
        },
        {
            accessorKey: 'title',
            header: 'Company',
            cell: ({ row }) => (
                <div>
                    <div className="font-medium text-gray-900">{row.original.title}</div>
                    {row.original.department && (
                        <div className="text-xs text-gray-500">{row.original.department}</div>
                    )}
                </div>
            ),
        },
        {
            accessorKey: 'pic_name',
            header: 'PIC',
            cell: ({ row }) => (
                <div>
                    {row.original.pic_name && (
                        <div className="text-sm text-gray-900">{row.original.pic_name}</div>
                    )}
                    {row.original.pic_phone && (
                        <div className="text-xs text-gray-500">{row.original.pic_phone}</div>
                    )}
                </div>
            ),
        },
        {
            accessorKey: 'contact',
            header: 'Contact',
            cell: ({ row }) => (
                <div>
                    {row.original.contact ? (
                        <>
                            <div className="text-sm text-gray-900">{row.original.contact.name}</div>
                            {row.original.contact.company && (
                                <div className="text-xs text-gray-500">{row.original.contact.company}</div>
                            )}
                        </>
                    ) : (
                        <span className="text-xs text-gray-400">No contact</span>
                    )}
                </div>
            ),
        },
        {
            accessorKey: 'status',
            header: 'Status',
            cell: ({ row }) => <ActivityStatusBadge status={row.original.status} />,
        },
        {
            id: 'actions',
            header: 'Actions',
            cell: ({ row }) => (
                <div className="flex items-center gap-2">
                    <Button
                        variant="ghost"
                        size="sm"
                        onClick={() => router.visit(route('sales-crm.activities.show', { activity: row.original.id }))}
                        className="text-primary hover:text-primary"
                    >
                        View
                    </Button>
                    <Button
                        variant="ghost"
                        size="sm"
                        onClick={() => router.visit(route('sales-crm.activities.edit', { activity: row.original.id }))}
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
        current_page: activities.current_page,
        last_page: activities.last_page,
        per_page: activities.per_page,
        total: activities.total,
        from: activities.from,
        to: activities.to,
    };

    return (
        <>
            <Head title="Sales Activities" />

            <div className="w-full px-6 py-6 lg:px-8">
                    {/* Page Header */}
                    <div className="mb-6 flex items-center justify-between">
                        <div>
                            <h1 className="text-2xl font-bold text-gray-900">Sales Activities</h1>
                            <p className="mt-1 text-sm text-gray-600">
                                Track and manage your sales activities
                            </p>
                        </div>
                        <Button
                            onClick={() => router.visit(route('sales-crm.activities.create'))}
                            className="bg-primary hover:bg-blue-600 text-white"
                        >
                            <Plus className="w-4 h-4 mr-2" />
                            New Activity
                        </Button>
                    </div>

                    {/* Stats Cards */}
                    <div className="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                        <StatCard
                            title="Total Activities"
                            value={stats.total}
                            icon={Calendar}
                            color="indigo"
                        />
                        <StatCard
                            title="Completed"
                            value={stats.completed}
                            icon={CheckCircle}
                            color="emerald"
                        />
                        <StatCard
                            title="Planned"
                            value={stats.planned}
                            icon={Clock}
                            color="amber"
                        />
                        <StatCard
                            title="Today"
                            value={stats.today}
                            icon={Calendar}
                            color="blue"
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
                                    placeholder="Search activities..."
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                                />
                            </div>

                            {/* Activity Type */}
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Activity Type
                                </label>
                                <select
                                    value={activityType}
                                    onChange={(e) => setActivityType(e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                                >
                                    <option value="">All Types</option>
                                    <option value="call">Phone Call</option>
                                    <option value="visit">Site Visit</option>
                                    <option value="meeting">Meeting</option>
                                    <option value="blitz">Blitz</option>
                                    <option value="follow_up">Follow Up</option>
                                    <option value="other">Other</option>
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
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                                >
                                    <option value="">All Status</option>
                                    <option value="planned">Planned</option>
                                    <option value="completed">Completed</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </div>

                            {/* Filter Button */}
                            <div className="flex items-end">
                                <Button
                                    onClick={handleFilterChange}
                                    className="w-full bg-primary hover:bg-blue-600 text-white"
                                >
                                    Apply Filters
                                </Button>
                            </div>
                        </div>
                    </div>

                    {/* Activities Table */}
                    <div className="bg-white rounded-xl border border-gray-100 overflow-hidden">
                        <div className="px-5 py-4 border-b border-gray-100">
                            <h3 className="text-base font-semibold text-gray-900">Activities</h3>
                        </div>
                        <DataTable
                            data={activities.data}
                            columns={columns}
                            pagination={pagination}
                            onPageChange={handlePageChange}
                            emptyMessage="No activities found. Create your first activity to get started."
                        />
                    </div>
            </div>
        </>
    );
}
