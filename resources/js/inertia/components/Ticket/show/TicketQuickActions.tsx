import { Link } from '@inertiajs/react';
import type { KnowledgeArticle, Ticket } from '@/types';

interface TicketQuickActionsProps {
    ticket: Ticket;
    articles: KnowledgeArticle[];
}

export function TicketQuickActions({ ticket, articles }: TicketQuickActionsProps) {
    return (
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
                {articles.length > 0 && (
                    <Link
                        href={route('it-support.admin.knowledge.index')}
                        className="block w-full text-center px-4 py-2 text-sm text-gray-700 bg-gray-50 hover:bg-gray-100 rounded-lg transition-colors"
                    >
                        Link Artikel
                    </Link>
                )}
            </div>
        </div>
    );
}
