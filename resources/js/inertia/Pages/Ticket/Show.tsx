import { useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { motion } from 'framer-motion';
import { 
    ArrowLeft, 
    Clock, 
    User, 
    Building2, 
    Tag, 
    AlertCircle,
    CheckCircle,
    XCircle,
    Loader2,
    Send
} from 'lucide-react';
import AppLayout from '@/layouts/AppLayout';
import { TicketStatusBadge } from '@/components/Ticket/TicketStatusBadge';
import { TicketPriorityBadge } from '@/components/Ticket/TicketPriorityBadge';
import { SlaBadge } from '@/components/Ticket/SlaBadge';
import { CommentSection } from '@/components/Ticket/CommentSection';
import { AttachmentList } from '@/components/Ticket/AttachmentList';
import { Button } from '@/components/ui/button';
import { Select } from '@/components/ui/select';
import { toast } from '@/components/ui/toast';
import { PageProps } from '@/types';
import type { Ticket, TicketStatus, User as UserType, KnowledgeArticle } from '@/types';

interface ShowPageProps extends PageProps {
    ticket: Ticket;
    isAdmin: boolean;
    staff?: UserType[];
    articles?: KnowledgeArticle[];
}

const statusOptions: { value: TicketStatus; label: string }[] = [
    { value: 'waiting', label: 'Menunggu' },
    { value: 'in_progress', label: 'Dalam Proses' },
    { value: 'done', label: 'Selesai' },
    { value: 'cancelled', label: 'Dibatalkan' },
];

export default function Show({ ticket, isAdmin, staff = [], articles = [] }: ShowPageProps) {
    // Filter comments based on user role
    const visibleComments = isAdmin 
        ? ticket.comments 
        : ticket.comments.filter(c => !c.is_private);
    
    const [isStatusChanging, setIsStatusChanging] = useState(false);
    const [isAssigning, setIsAssigning] = useState(false);

    const { data: statusData, setData: setStatusData, put: updateStatus, processing: statusProcessing } = useForm<{
        status: TicketStatus;
    }>({
        status: ticket.status,
    });

    const { data: assignData, setData: setAssignData, post: assignTicket, processing: assignProcessing } = useForm<{
        assigned_to: number | null;
    }>({
        assigned_to: ticket.assigned_user?.id ?? null,
    });

    const handleStatusChange = (e: React.FormEvent) => {
        e.preventDefault();
        setIsStatusChanging(false);
        
        updateStatus(route('it-support.admin.tickets.changeStatus', { ticket: ticket.id }), {
            onSuccess: () => {
                toast.success('Status ticket berhasil diperbarui');
            },
            onError: () => {
                toast.error('Gagal memperbarui status');
            },
        });
    };

    const handleAssignSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        setIsAssigning(false);
        
        assignTicket(route('it-support.admin.tickets.assign', { ticket: ticket.id }), {
            onSuccess: () => {
                toast.success('Ticket berhasil ditugaskan');
            },
            onError: () => {
                toast.error('Gagal menugaskan ticket');
            },
        });
    };

    const formatDate = (dateString: string) => {
        const date = new Date(dateString);
        return date.toLocaleDateString('id-ID', {
            day: 'numeric',
            month: 'long',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    const formatShortDate = (dateString: string) => {
        const date = new Date(dateString);
        return date.toLocaleDateString('id-ID', {
            day: 'numeric',
            month: 'short',
            year: 'numeric',
        });
    };

    // Calculate processing time if resolved
    const getProcessingTime = () => {
        if (!ticket.processing_time) return null;
        const hours = parseFloat(ticket.processing_time);
        if (hours < 1) {
            return `${Math.round(hours * 60)} menit`;
        } else if (hours < 24) {
            return `${hours.toFixed(1)} jam`;
        } else {
            const days = Math.floor(hours / 24);
            const remainingHours = hours % 24;
            return `${days} hari ${remainingHours.toFixed(0)} jam`;
        }
    };

    return (
        <AppLayout title={`${ticket.ticket_number} - ${ticket.title}`}>
            <Head title={`${ticket.ticket_number} - ${ticket.title}`} />

            <div className="w-full px-4 sm:px-6 lg:px-8 py-6">
                {/* Back Navigation */}
                <div className="mb-6">
                    <Link
                        href={isAdmin ? route('it-support.admin.tickets.index') : route('it-support.my-tickets')}
                        className="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-gray-700 transition-colors"
                    >
                        <ArrowLeft className="w-4 h-4" />
                        {isAdmin ? 'Kembali ke Semua Tiket' : 'Kembali ke Tiket Saya'}
                    </Link>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Main Content - Left Side */}
                    <div className="lg:col-span-2 space-y-6">
                        {/* Ticket Header */}
                        <motion.div
                            initial={{ opacity: 0, y: 20 }}
                            animate={{ opacity: 1, y: 0 }}
                            transition={{ duration: 0.3 }}
                            className="bg-white rounded-xl shadow-sm border border-gray-200 p-6"
                        >
                            <div className="flex items-start justify-between">
                                <div className="flex-1">
                                    <div className="flex items-center gap-3 mb-2">
                                        <span className="text-lg font-mono font-semibold text-primary">
                                            {ticket.ticket_number}
                                        </span>
                                        <TicketStatusBadge status={ticket.status} />
                                        <TicketPriorityBadge priority={ticket.priority} />
                                        {ticket.sla_deadline && (
                                            <SlaBadge 
                                                slaDeadline={ticket.sla_deadline} 
                                                isBreached={ticket.is_sla_breach} 
                                            />
                                        )}
                                    </div>
                                    <h1 className="text-xl font-bold text-gray-900 mb-2">
                                        {ticket.title}
                                    </h1>
                                    {ticket.category && (
                                        <div className="flex items-center gap-2 text-sm text-gray-500">
                                            <Tag className="w-4 h-4" />
                                            {ticket.category.name}
                                        </div>
                                    )}
                                </div>
                            </div>

                            {/* Description */}
                            <div className="mt-6 pt-6 border-t border-gray-100">
                                <h3 className="text-sm font-medium text-gray-700 mb-3">Deskripsi</h3>
                                <div className="text-sm text-gray-600 whitespace-pre-wrap">
                                    {ticket.description}
                                </div>
                            </div>

                            {/* Attachments */}
                            {ticket.attachments && ticket.attachments.length > 0 && (
                                <div className="mt-6 pt-6 border-t border-gray-100">
                                    <AttachmentList attachments={ticket.attachments} />
                                </div>
                            )}
                        </motion.div>

                        {/* Comments Section */}
                        <motion.div
                            initial={{ opacity: 0, y: 20 }}
                            animate={{ opacity: 1, y: 0 }}
                            transition={{ duration: 0.3, delay: 0.1 }}
                            className="bg-white rounded-xl shadow-sm border border-gray-200 p-6"
                        >
                            <h3 className="text-lg font-semibold text-gray-900 mb-4">
                                Komentar
                            </h3>
                            <CommentSection
                                comments={visibleComments}
                                ticketId={ticket.id}
                                commentRoute={isAdmin
                                    ? route('it-support.admin.tickets.comment', { ticket: ticket.id })
                                    : route('it-support.my-tickets.comment', { ticket: ticket.id })}
                                canAddPrivateComment={isAdmin}
                            />
                        </motion.div>
                    </div>

                    {/* Sidebar - Right Side */}
                    <div className="space-y-6">
                        {/* Status Card (Admin only can change) */}
                        {isAdmin && (
                            <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                                <h3 className="text-sm font-semibold text-gray-900 mb-4">
                                    Status Tiket
                                </h3>
                                {isStatusChanging ? (
                                    <form onSubmit={handleStatusChange} className="space-y-3">
                                        <Select
                                            value={statusData.status}
                                            onChange={(value) => setStatusData('status', value as TicketStatus)}
                                            options={statusOptions.map(s => ({ value: s.value, label: s.label }))}
                                        />
                                        <div className="flex gap-2">
                                            <Button type="submit" size="sm" loading={statusProcessing}>
                                                Simpan
                                            </Button>
                                            <Button 
                                                type="button" 
                                                variant="outline" 
                                                size="sm"
                                                onClick={() => {
                                                    setIsStatusChanging(false);
                                                    setStatusData('status', ticket.status);
                                                }}
                                            >
                                                Batal
                                            </Button>
                                        </div>
                                    </form>
                                ) : (
                                    <div className="flex items-center justify-between">
                                        <TicketStatusBadge status={ticket.status} />
                                        <button
                                            onClick={() => setIsStatusChanging(true)}
                                            className="text-sm text-primary hover:text-blue-700"
                                        >
                                            Ubah Status
                                        </button>
                                    </div>
                                )}
                            </div>
                        )}

                        {/* Ticket Details */}
                        <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                            <h3 className="text-sm font-semibold text-gray-900 mb-4">
                                Detail Tiket
                            </h3>
                            <div className="space-y-4">
                                {/* Requester */}
                                <div className="flex items-start gap-3">
                                    <User className="w-4 h-4 text-gray-400 mt-0.5" />
                                    <div>
                                        <p className="text-xs text-gray-400">Pemohon</p>
                                        <p className="text-sm text-gray-900">{ticket.requester?.name || '-'}</p>
                                    </div>
                                </div>

                                {/* Department */}
                                {ticket.department && (
                                    <div className="flex items-start gap-3">
                                        <Building2 className="w-4 h-4 text-gray-400 mt-0.5" />
                                        <div>
                                            <p className="text-xs text-gray-400">Departemen</p>
                                            <p className="text-sm text-gray-900">{ticket.department.name}</p>
                                        </div>
                                    </div>
                                )}

                                {/* Priority */}
                                <div className="flex items-start gap-3">
                                    <AlertCircle className="w-4 h-4 text-gray-400 mt-0.5" />
                                    <div>
                                        <p className="text-xs text-gray-400">Prioritas</p>
                                        <TicketPriorityBadge priority={ticket.priority} />
                                    </div>
                                </div>

                                {/* Created */}
                                <div className="flex items-start gap-3">
                                    <Clock className="w-4 h-4 text-gray-400 mt-0.5" />
                                    <div>
                                        <p className="text-xs text-gray-400">Dibuat</p>
                                        <p className="text-sm text-gray-900">{formatDate(ticket.created_at)}</p>
                                    </div>
                                </div>

                                {/* Resolved */}
                                {ticket.resolved_at && (
                                    <div className="flex items-start gap-3">
                                        <CheckCircle className="w-4 h-4 text-gray-400 mt-0.5" />
                                        <div>
                                            <p className="text-xs text-gray-400">Selesai</p>
                                            <p className="text-sm text-gray-900">{formatDate(ticket.resolved_at)}</p>
                                            {ticket.processing_time && (
                                                <p className="text-xs text-gray-400">
                                                    Waktu proses: {getProcessingTime()}
                                                </p>
                                            )}
                                        </div>
                                    </div>
                                )}
                            </div>
                        </div>

                        {/* Assignment Card (Admin only) */}
                        {isAdmin && staff.length > 0 && (
                            <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                                <h3 className="text-sm font-semibold text-gray-900 mb-4">
                                    Penugasan
                                </h3>
                                {isAssigning ? (
                                    <form onSubmit={handleAssignSubmit} className="space-y-3">
                                        <Select
                                            value={assignData.assigned_to?.toString() || ''}
                                            onChange={(value) => setAssignData('assigned_to', value ? parseInt(value.toString()) : null)}
                                            options={[
                                                { value: '', label: 'Belum ditugaskan' },
                                                ...staff.map(s => ({ value: s.id.toString(), label: s.name })),
                                            ]}
                                        />
                                        <div className="flex gap-2">
                                            <Button type="submit" size="sm" loading={assignProcessing}>
                                                Simpan
                                            </Button>
                                            <Button 
                                                type="button" 
                                                variant="outline" 
                                                size="sm"
                                                onClick={() => {
                                                    setIsAssigning(false);
                                                    setAssignData('assigned_to', ticket.assigned_user?.id ?? null);
                                                }}
                                            >
                                                Batal
                                            </Button>
                                        </div>
                                    </form>
                                ) : (
                                    <div className="flex items-center justify-between">
                                        <div className="flex items-center gap-2">
                                            {ticket.assigned_user ? (
                                                <>
                                                    <div className="w-8 h-8 bg-primary/10 rounded-full flex items-center justify-center">
                                                        <User className="w-4 h-4 text-primary" />
                                                    </div>
                                                    <div>
                                                        <p className="text-sm text-gray-900">{ticket.assigned_user.name}</p>
                                                        <p className="text-xs text-gray-400">Ditugaskan</p>
                                                    </div>
                                                </>
                                            ) : (
                                                <div className="text-sm text-gray-400">
                                                    Belum ditugaskan
                                                </div>
                                            )}
                                        </div>
                                        <button
                                            onClick={() => setIsAssigning(true)}
                                            className="text-sm text-primary hover:text-blue-700"
                                        >
                                            Ubah
                                        </button>
                                    </div>
                                )}
                            </div>
                        )}

                        {/* Linked Articles */}
                        {isAdmin && ticket.knowledge_articles && ticket.knowledge_articles.length > 0 && (
                            <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                                <h3 className="text-sm font-semibold text-gray-900 mb-4">
                                    Artikel Terkait
                                </h3>
                                <div className="space-y-2">
                                    {ticket.knowledge_articles.map((article) => (
                                        <Link
                                            key={article.id}
                                            href={route('it-support.knowledge.article', { slug: article.slug })}
                                            className="block p-2 rounded-lg hover:bg-gray-50 transition-colors"
                                        >
                                            <p className="text-sm text-gray-700 line-clamp-2">
                                                {article.title}
                                            </p>
                                        </Link>
                                    ))}
                                </div>
                            </div>
                        )}

                        {/* Quick Actions */}
                        {isAdmin && (
                            <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                                <h3 className="text-sm font-semibold text-gray-900 mb-4">
                                    Aksi Cepat
                                </h3>
                                <div className="space-y-2">
<Link
                                            href={route('it-support.admin.tickets.edit', { ticket: ticket.id })}
                                            className="block w-full text-center px-4 py-2 text-sm text-gray-700 bg-gray-50 hover:bg-gray-100 rounded-lg transition-colors"
                                        >
                                            Edit Tiket
                                        </Link>
                                    {articles && articles.length > 0 && (
                                        <Link
                                            href={route('it-support.admin.knowledge.index')}
                                            className="block w-full text-center px-4 py-2 text-sm text-gray-700 bg-gray-50 hover:bg-gray-100 rounded-lg transition-colors"
                                        >
                                            Link Artikel
                                        </Link>
                                    )}
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}