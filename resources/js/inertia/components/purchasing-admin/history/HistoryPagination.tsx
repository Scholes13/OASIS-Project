import { ChevronLeft, ChevronRight } from 'lucide-react';
import { cn } from '@/lib/utils';
import type { HistoryPaginationData } from './historyUtils';

interface HistoryPaginationProps {
    pagination: HistoryPaginationData;
    onPageChange: (page: number) => void;
}

export function HistoryPagination({ pagination, onPageChange }: HistoryPaginationProps) {
    if (pagination.last_page <= 1) return null;

    return (
        <div className="px-5 py-4 border-t border-gray-100 flex items-center justify-between">
            <p className="text-sm text-gray-500">
                Showing {pagination.from} to {pagination.to} of {pagination.total} results
            </p>
            <div className="flex items-center gap-2">
                <button
                    onClick={() => onPageChange(pagination.current_page - 1)}
                    disabled={pagination.current_page === 1}
                    className="p-2 text-gray-400 hover:text-gray-600 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <ChevronLeft className="w-5 h-5" />
                </button>
                {Array.from({ length: Math.min(5, pagination.last_page) }, (_, i) => {
                    let page = i + 1;

                    if (pagination.current_page > 3 && pagination.last_page > 5) {
                        page = pagination.current_page - 2 + i;
                        if (page > pagination.last_page) page = pagination.last_page - 4 + i;
                    }

                    return (
                        <button
                            key={page}
                            onClick={() => onPageChange(page)}
                            className={cn(
                                'px-3 py-1 text-sm rounded-md',
                                page === pagination.current_page
                                    ? 'bg-primary text-white'
                                    : 'text-gray-600 hover:bg-gray-100'
                            )}
                        >
                            {page}
                        </button>
                    );
                })}
                <button
                    onClick={() => onPageChange(pagination.current_page + 1)}
                    disabled={pagination.current_page === pagination.last_page}
                    className="p-2 text-gray-400 hover:text-gray-600 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <ChevronRight className="w-5 h-5" />
                </button>
            </div>
        </div>
    );
}
