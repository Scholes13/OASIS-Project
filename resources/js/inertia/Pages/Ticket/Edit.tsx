import { useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useForm } from '@inertiajs/react';
import {
    ArrowLeft, Save, Calendar, User, Tag, AlertTriangle,
    Clock, MessageSquare, Paperclip, Send,
} from 'lucide-react';
import { format } from 'date-fns';
import { Card, CardHeader, CardTitle, CardContent, CardFooter } from '@/components/ui/Card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select } from '@/components/ui/select';
import { Badge } from '@/components/ui/Badge';
import { TicketStatusBadge } from '@/components/Ticket/TicketStatusBadge';
import { TicketPriorityBadge } from '@/components/Ticket/TicketPriorityBadge';
import { SlaBadge } from '@/components/Ticket/SlaBadge';
import { CommentSection } from '@/components/Ticket/CommentSection';
import { AttachmentList } from '@/components/Ticket/AttachmentList';
import { toast } from '@/components/ui/toast';
import { cn } from '@/lib/utils';
import type { PageProps, Ticket, TicketCategory, Department, TicketPriority, TicketStatus } from '@/types';

// ── Types ──────────────────────────────────────────────────────────────
interface EditProps extends PageProps {
    ticket: Ticket;
    categories: TicketCategory[];
    departments: Department[];
    priority?: TicketPriority[];
    status?: TicketStatus[];
}

// ── Priority & Status labels ──────────────────────────────────────────────
const priorityLabels: Record<TicketPriority, string> = {
    low: 'Rendah',
    medium: 'Sedang',
    high: 'Tinggi',
    critical: 'Kritis',
};

const statusLabels: Record<TicketStatus, string> = {
    waiting: 'Menunggu',
    in_progress: 'Dalam Proses',
    done: 'Selesai',
    cancelled: 'Dibatalkan',
};

const defaultPriorities: TicketPriority[] = ['low', 'medium', 'high', 'critical'];
const defaultStatuses: TicketStatus[] = ['waiting', 'in_progress', 'done', 'cancelled'];

export default function TicketEdit({ ticket, categories, departments, priority = defaultPriorities, status = defaultStatuses }: EditProps) {
    const { flash } = usePage<PageProps>().props;

    // Form for editing ticket
    const { data, setData, put, processing, errors } = useForm({
        title: ticket.title,
        description: ticket.description,
        priority: ticket.priority,
        category_id: ticket.category?.id || null,
        department_id: ticket.department?.id || null,
        follow_up_at: ticket.follow_up_at || '',
    });

    // Status form (separate for quick status change)
    const [currentStatus, setCurrentStatus] = useState<TicketStatus>(ticket.status);
    const { post: statusPost, setData: setStatusData, processing: statusProcessing } = useForm({
        status: ticket.status,
    });

    // Handlers
    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        put(route('it-support.admin.tickets.update', { ticket: ticket.id }), {
            onSuccess: () => {
                toast.success('Ticket updated successfully');
            },
            onError: () => {
                toast.error('Failed to update ticket');
            },
        });
    };

    const handleStatusChange = (newStatus: string) => {
        const statusVal = newStatus as TicketStatus;
        setCurrentStatus(statusVal);
        setStatusData('status', statusVal);

        statusPost(route('it-support.admin.tickets.update-status', { ticket: ticket.id }), {
            onSuccess: () => {
                toast.success('Status updated successfully');
            },
            onError: () => {
                toast.error('Failed to update status');
            },
        });
    };

    // Category options
    const categoryOptions = [
        { value: '', label: 'Select category...' },
        ...categories.map(c => ({ value: c.id.toString(), label: c.name })),
    ];

    // Department options
    const departmentOptions = [
        { value: '', label: 'Select department...' },
        ...departments.map(d => ({ value: d.id.toString(), label: d.name })),
    ];

    // Priority options
    const priorityOptions = priority.map(p => ({
        value: p,
        label: priorityLabels[p],
    }));

    // Status options
    const statusOptions = status.map(s => ({
        value: s,
        label: statusLabels[s],
    }));

    return (
        <>
            <Head title={`Edit Ticket: ${ticket.ticket_number}`} />

            <div className="w-full px-6 py-6 lg:px-8 space-y-6">
                {/* ── Header ──────────────────────────────────────────────── */}
                <div className="flex flex-col xl:flex-row xl:items-end justify-between gap-4">
                    <div className="flex flex-col gap-1.5">
                        <div className="flex items-center gap-2">
                            <Link href={route('it-support.admin.tickets.index')}>
                                <Button variant="ghost" size="sm" className="-ml-2">
                                    <ArrowLeft className="w-4 h-4 mr-1" />
                                    Back
                                </Button>
                            </Link>
                        </div>
                        <h1 className="text-2xl font-bold text-gray-900 tracking-tight">
                            Edit Ticket: {ticket.ticket_number}
                        </h1>
                        <div className="flex items-center gap-2 mt-1">
                            <TicketStatusBadge status={ticket.status} />
                            <TicketPriorityBadge priority={ticket.priority} />
                            {ticket.sla_deadline && (
                                <SlaBadge
                                    slaDeadline={ticket.sla_deadline}
                                    isBreached={ticket.is_sla_breach}
                                />
                            )}
                        </div>
                    </div>

                    <div className="flex items-center gap-2">
                        {/* Quick Status Change */}
                        <Select
                            value={currentStatus}
                            onChange={(val: string | number) => handleStatusChange(String(val))}
                            options={statusOptions}
                        />
                        <Button
                            type="submit"
                            form="ticket-form"
                            loading={processing || statusProcessing}
                        >
                            <Save className="w-4 h-4 mr-2" />
                            Save Changes
                        </Button>
                    </div>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* ── Main Form ──────────────────────────────────────────────── */}
                    <div className="lg:col-span-2 space-y-6">
                        <Card className="border border-gray-200 rounded-lg">
                            <CardHeader>
                                <CardTitle>Ticket Details</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <form id="ticket-form" onSubmit={handleSubmit} className="space-y-4">
                                    {/* Title */}
                                    <div>
                                        <Label htmlFor="title">Title</Label>
                                        <Input
                                            id="title"
                                            value={data.title}
                                            onChange={(e) => setData('title', e.target.value)}
                                            error={errors.title}
                                            required
                                        />
                                    </div>

                                    {/* Description */}
                                    <div>
                                        <Label htmlFor="description">Description</Label>
                                        <Textarea
                                            id="description"
                                            value={data.description}
                                            onChange={(e) => setData('description', e.target.value)}
                                            rows={6}
                                            error={errors.description}
                                            required
                                        />
                                    </div>

                                    {/* Priority & Category */}
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <Label>Priority</Label>
                                            <Select
                                                value={data.priority}
                                                onChange={(val: string | number) => setData('priority', String(val) as TicketPriority)}
                                                options={priorityOptions}
                                                error={errors.priority}
                                            />
                                        </div>
                                        <div>
                                            <Label>Category</Label>
                                            <Select
                                                value={data.category_id?.toString() || ''}
                                                onChange={(val: string | number) => setData('category_id', val ? parseInt(String(val)) : null)}
                                                options={categoryOptions}
                                                error={errors.category_id}
                                            />
                                        </div>
                                    </div>

                                    {/* Department & Follow-up */}
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <Label>Department</Label>
                                            <Select
                                                value={data.department_id?.toString() || ''}
                                                onChange={(val: string | number) => setData('department_id', val ? parseInt(String(val)) : null)}
                                                options={departmentOptions}
                                                error={errors.department_id}
                                            />
                                        </div>
                                        <div>
                                            <Label htmlFor="follow_up_at">Follow-up Date</Label>
                                            <Input
                                                id="follow_up_at"
                                                type="datetime-local"
                                                value={data.follow_up_at}
                                                onChange={(e) => setData('follow_up_at', e.target.value)}
                                                error={errors.follow_up_at}
                                            />
                                        </div>
                                    </div>
                                </form>
                            </CardContent>
                        </Card>

                        {/* Comments Section */}
                        <Card className="border border-gray-200 rounded-lg">
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <MessageSquare className="w-5 h-5" />
                                    Comments
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <CommentSection comments={ticket.comments} ticketId={ticket.id} />
                            </CardContent>
                        </Card>

                        {/* Attachments */}
                        <Card className="border border-gray-200 rounded-lg">
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <Paperclip className="w-5 h-5" />
                                    Attachments
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <AttachmentList attachments={ticket.attachments} />
                            </CardContent>
                        </Card>
                    </div>

                    {/* ── Sidebar ──────────────────────────────────────────────── */}
                    <div className="space-y-6">
                        {/* Ticket Info */}
                        <Card className="border border-gray-200 rounded-lg">
                            <CardHeader>
                                <CardTitle>Ticket Information</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div>
                                    <p className="text-xs text-gray-500">Requester</p>
                                    <p className="text-sm font-medium text-gray-900">
                                        {ticket.requester?.name || '-'}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-xs text-gray-500">Assigned To</p>
                                    <p className="text-sm font-medium text-gray-900">
                                        {ticket.assigned_user?.name || 'Unassigned'}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-xs text-gray-500">Created</p>
                                    <p className="text-sm text-gray-900">
                                        {format(new Date(ticket.created_at), 'dd MMM yyyy HH:mm')}
                                    </p>
                                </div>
                                {ticket.resolved_at && (
                                    <div>
                                        <p className="text-xs text-gray-500">Resolved</p>
                                        <p className="text-sm text-gray-900">
                                            {format(new Date(ticket.resolved_at), 'dd MMM yyyy HH:mm')}
                                        </p>
                                    </div>
                                )}
                                {ticket.processing_time && (
                                    <div>
                                        <p className="text-xs text-gray-500">Processing Time</p>
                                        <p className="text-sm font-medium text-gray-900">
                                            {ticket.processing_time}
                                        </p>
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        {/* SLA Info */}
                        {ticket.sla_deadline && (
                            <Card className={cn(
                                'border rounded-lg',
                                ticket.is_sla_breach ? 'border-red-200 bg-red-50' : 'border-gray-200'
                            )}>
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2">
                                        <AlertTriangle className={cn(
                                            'w-5 h-5',
                                            ticket.is_sla_breach ? 'text-red-600' : 'text-amber-600'
                                        )} />
                                        SLA Status
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div>
                                        <p className="text-xs text-gray-500">Deadline</p>
                                        <p className="text-sm font-medium text-gray-900">
                                            {format(new Date(ticket.sla_deadline), 'dd MMM yyyy HH:mm')}
                                        </p>
                                    </div>
                                    {ticket.is_sla_breach && (
                                        <div className="mt-2 p-2 bg-red-100 rounded-lg">
                                            <p className="text-sm font-medium text-red-700">
                                                SLA Breach!
                                            </p>
                                        </div>
                                    )}
                                </CardContent>
                            </Card>
                        )}
                    </div>
                </div>
            </div>
        </>
    );
}