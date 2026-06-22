import { motion } from 'framer-motion';
import { Tag } from 'lucide-react';
import { AttachmentList } from '@/components/Ticket/AttachmentList';
import { SlaBadge } from '@/components/Ticket/SlaBadge';
import { TicketPriorityBadge } from '@/components/Ticket/TicketPriorityBadge';
import { TicketStatusBadge } from '@/components/Ticket/TicketStatusBadge';
import type { Ticket } from '@/types';

interface TicketHeaderProps {
    ticket: Ticket;
}

export function TicketHeader({ ticket }: TicketHeaderProps) {
    return (
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

            <div className="mt-6 pt-6 border-t border-gray-100">
                <h3 className="text-sm font-medium text-gray-700 mb-3">Deskripsi</h3>
                <div className="text-sm text-gray-600 whitespace-pre-wrap">
                    {ticket.description}
                </div>
            </div>

            {ticket.attachments && ticket.attachments.length > 0 && (
                <div className="mt-6 pt-6 border-t border-gray-100">
                    <AttachmentList attachments={ticket.attachments} />
                </div>
            )}
        </motion.div>
    );
}
