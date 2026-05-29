import { Link } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/Card';
import { Button } from '@/components/ui/button';
import { TicketPriorityBadge } from '@/components/Ticket/TicketPriorityBadge';
import { TicketStatusBadge } from '@/components/Ticket/TicketStatusBadge';
import { SlaBadge } from '@/components/Ticket/SlaBadge';
import type { Ticket } from '@/types';

interface RecentTicketsTableProps {
    tickets: Ticket[];
}

export function RecentTicketsTable({ tickets }: RecentTicketsTableProps) {
    return (
        <Card className="border border-gray-200 rounded-lg">
            <CardHeader className="pb-4 border-b border-gray-100">
                <div className="flex items-center justify-between">
                    <CardTitle className="text-base font-semibold text-gray-900">Recent Tickets</CardTitle>
                    <Link href={route('it-support.admin.tickets.index')}>
                        <Button
                            variant="ghost"
                            size="sm"
                            className="text-primary"
                        >
                            View All <ArrowRight className="w-4 h-4 ml-1" />
                        </Button>
                    </Link>
                </div>
            </CardHeader>
            <CardContent className="p-0">
                <div className="overflow-x-auto">
                    <table className="w-full">
                        <thead>
                            <tr className="bg-gray-100 border-b border-gray-200">
                                <th className="h-12 px-5 text-left text-sm font-semibold text-gray-700">Ticket</th>
                                <th className="h-12 px-5 text-left text-sm font-semibold text-gray-700">Title</th>
                                <th className="h-12 px-5 text-left text-sm font-semibold text-gray-700">Requester</th>
                                <th className="h-12 px-5 text-left text-sm font-semibold text-gray-700">Status</th>
                                <th className="h-12 px-5 text-left text-sm font-semibold text-gray-700">Priority</th>
                                <th className="h-12 px-5 text-left text-sm font-semibold text-gray-700">SLA</th>
                            </tr>
                        </thead>
                        <tbody>
                            {tickets.length === 0 ? (
                                <tr>
                                    <td
                                        colSpan={6}
                                        className="px-5 py-8 text-center text-gray-500"
                                    >
                                        No recent tickets
                                    </td>
                                </tr>
                            ) : (
                                tickets.slice(0, 10).map((ticket) => (
                                    <tr
                                        key={ticket.id}
                                        className="border-b border-gray-100 hover:bg-gray-50/80"
                                    >
                                        <td className="px-5 py-4 text-sm font-medium text-primary">
                                            <Link href={route('it-support.admin.tickets.show', { ticket: ticket.id })}>
                                                {ticket.ticket_number}
                                            </Link>
                                        </td>
                                        <td className="px-5 py-4 text-sm text-gray-900 max-w-xs truncate">
                                            {ticket.title}
                                        </td>
                                        <td className="px-5 py-4 text-sm text-gray-600">
                                            {ticket.requester?.name || '-'}
                                        </td>
                                        <td className="px-5 py-4">
                                            <TicketStatusBadge status={ticket.status} />
                                        </td>
                                        <td className="px-5 py-4">
                                            <TicketPriorityBadge priority={ticket.priority} />
                                        </td>
                                        <td className="px-5 py-4">
                                            <SlaBadge
                                                slaDeadline={ticket.sla_deadline}
                                                isBreached={ticket.is_sla_breach}
                                            />
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>
    );
}
