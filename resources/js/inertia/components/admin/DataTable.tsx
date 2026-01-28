import React, { useState } from 'react';
import {
    flexRender,
    getCoreRowModel,
    getSortedRowModel,
    useReactTable,
    type ColumnDef,
    type SortingState,
    type RowSelectionState,
} from '@tanstack/react-table';
import { ChevronUp, ChevronDown, ChevronsUpDown } from 'lucide-react';
import { cn } from '@/lib/utils';

export interface PaginationData {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
}

interface DataTableProps<TData> {
    data: TData[];
    columns: ColumnDef<TData, any>[];
    pagination?: PaginationData;
    onPageChange?: (page: number) => void;
    onSort?: (column: string, direction: 'asc' | 'desc') => void;
    loading?: boolean;
    emptyMessage?: string;
    selectable?: boolean;
    onSelectionChange?: (selectedRows: TData[]) => void;
}

export function DataTable<TData>({
    data,
    columns,
    pagination,
    onPageChange,
    onSort,
    loading = false,
    emptyMessage = 'No data available',
    selectable = false,
    onSelectionChange,
}: DataTableProps<TData>) {
    const [sorting, setSorting] = useState<SortingState>([]);
    const [rowSelection, setRowSelection] = useState<RowSelectionState>({});

    const table = useReactTable({
        data,
        columns,
        getCoreRowModel: getCoreRowModel(),
        getSortedRowModel: getSortedRowModel(),
        onSortingChange: setSorting,
        onRowSelectionChange: setRowSelection,
        state: {
            sorting,
            rowSelection,
        },
        enableRowSelection: selectable,
        manualPagination: !!pagination,
        manualSorting: !!onSort,
    });

    // Handle sorting change
    React.useEffect(() => {
        if (onSort && sorting.length > 0) {
            const sort = sorting[0];
            onSort(sort.id, sort.desc ? 'desc' : 'asc');
        }
    }, [sorting, onSort]);

    // Handle selection change
    React.useEffect(() => {
        if (onSelectionChange && selectable) {
            const selectedRows = table.getSelectedRowModel().rows.map(row => row.original);
            onSelectionChange(selectedRows);
        }
    }, [rowSelection, onSelectionChange, selectable, table]);

    // Loading skeleton
    if (loading) {
        return (
            <div className="bg-white rounded-xl border border-gray-100 overflow-hidden">
                <div className="overflow-x-auto">
                    <table className="min-w-full divide-y divide-gray-200">
                        <thead className="bg-gray-50">
                            <tr>
                                {columns.map((_, index) => (
                                    <th
                                        key={index}
                                        className="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >
                                        <div className="h-4 bg-gray-200 rounded w-24 animate-pulse"></div>
                                    </th>
                                ))}
                            </tr>
                        </thead>
                        <tbody className="bg-white divide-y divide-gray-100">
                            {[...Array(5)].map((_, rowIndex) => (
                                <tr key={rowIndex}>
                                    {columns.map((_, colIndex) => (
                                        <td key={colIndex} className="px-5 py-4 whitespace-nowrap">
                                            <div className="h-4 bg-gray-200 rounded w-full animate-pulse"></div>
                                        </td>
                                    ))}
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        );
    }

    // Empty state
    if (data.length === 0) {
        return (
            <div className="bg-white rounded-xl border border-gray-100 overflow-hidden">
                <div className="overflow-x-auto">
                    <table className="min-w-full divide-y divide-gray-200">
                        <thead className="bg-gray-50">
                            <tr>
                                {table.getHeaderGroups()[0]?.headers.map((header) => (
                                    <th
                                        key={header.id}
                                        className="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >
                                        {header.isPlaceholder
                                            ? null
                                            : flexRender(
                                                  header.column.columnDef.header,
                                                  header.getContext()
                                              )}
                                    </th>
                                ))}
                            </tr>
                        </thead>
                    </table>
                </div>
                <div className="text-center py-12">
                    <svg
                        className="mx-auto h-12 w-12 text-gray-400"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        aria-hidden="true"
                    >
                        <path
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            strokeWidth={1.5}
                            d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"
                        />
                    </svg>
                    <h3 className="mt-2 text-sm font-medium text-gray-900">No data</h3>
                    <p className="mt-1 text-sm text-gray-500">{emptyMessage}</p>
                </div>
            </div>
        );
    }

    return (
        <div className="bg-white rounded-xl border border-gray-100 overflow-hidden">
            <div className="overflow-x-auto">
                <table className="min-w-full divide-y divide-gray-200">
                    <thead className="bg-gray-50">
                        {table.getHeaderGroups().map((headerGroup) => (
                            <tr key={headerGroup.id}>
                                {headerGroup.headers.map((header) => (
                                    <th
                                        key={header.id}
                                        className={cn(
                                            'px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider',
                                            header.column.getCanSort() && 'cursor-pointer select-none hover:bg-gray-100'
                                        )}
                                        onClick={header.column.getToggleSortingHandler()}
                                    >
                                        {header.isPlaceholder ? null : (
                                            <div className="flex items-center gap-2">
                                                {flexRender(
                                                    header.column.columnDef.header,
                                                    header.getContext()
                                                )}
                                                {header.column.getCanSort() && (
                                                    <span className="text-gray-400">
                                                        {header.column.getIsSorted() === 'asc' ? (
                                                            <ChevronUp className="w-4 h-4" />
                                                        ) : header.column.getIsSorted() === 'desc' ? (
                                                            <ChevronDown className="w-4 h-4" />
                                                        ) : (
                                                            <ChevronsUpDown className="w-4 h-4" />
                                                        )}
                                                    </span>
                                                )}
                                            </div>
                                        )}
                                    </th>
                                ))}
                            </tr>
                        ))}
                    </thead>
                    <tbody className="bg-white divide-y divide-gray-100">
                        {table.getRowModel().rows.map((row) => (
                            <tr
                                key={row.id}
                                className={cn(
                                    'hover:bg-gray-50 transition-colors',
                                    row.getIsSelected() && 'bg-indigo-50'
                                )}
                            >
                                {row.getVisibleCells().map((cell) => (
                                    <td
                                        key={cell.id}
                                        className="px-5 py-4 whitespace-nowrap text-sm text-gray-900"
                                    >
                                        {flexRender(cell.column.columnDef.cell, cell.getContext())}
                                    </td>
                                ))}
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            {/* Pagination */}
            {pagination && (
                <div className="px-5 py-4 border-t border-gray-100 flex items-center justify-between">
                    <div className="text-sm text-gray-700">
                        Showing <span className="font-medium">{pagination.from}</span> to{' '}
                        <span className="font-medium">{pagination.to}</span> of{' '}
                        <span className="font-medium">{pagination.total}</span> results
                    </div>
                    <div className="flex items-center gap-2">
                        <button
                            onClick={() => onPageChange?.(pagination.current_page - 1)}
                            disabled={pagination.current_page === 1}
                            className={cn(
                                'px-3 py-1.5 text-sm rounded-md border transition-colors',
                                pagination.current_page === 1
                                    ? 'border-gray-200 text-gray-400 cursor-not-allowed'
                                    : 'border-gray-300 text-gray-700 hover:bg-gray-50'
                            )}
                        >
                            Previous
                        </button>

                        <div className="flex items-center gap-1">
                            {generatePageNumbers(pagination.current_page, pagination.last_page).map(
                                (page, index) =>
                                    page === '...' ? (
                                        <span
                                            key={`ellipsis-${index}`}
                                            className="px-3 py-1.5 text-sm text-gray-500"
                                        >
                                            ...
                                        </span>
                                    ) : (
                                        <button
                                            key={page}
                                            onClick={() => onPageChange?.(Number(page))}
                                            className={cn(
                                                'px-3 py-1.5 text-sm rounded-md border transition-colors',
                                                pagination.current_page === page
                                                    ? 'bg-indigo-600 text-white border-indigo-600'
                                                    : 'border-gray-300 text-gray-700 hover:bg-gray-50'
                                            )}
                                        >
                                            {page}
                                        </button>
                                    )
                            )}
                        </div>

                        <button
                            onClick={() => onPageChange?.(pagination.current_page + 1)}
                            disabled={pagination.current_page === pagination.last_page}
                            className={cn(
                                'px-3 py-1.5 text-sm rounded-md border transition-colors',
                                pagination.current_page === pagination.last_page
                                    ? 'border-gray-200 text-gray-400 cursor-not-allowed'
                                    : 'border-gray-300 text-gray-700 hover:bg-gray-50'
                            )}
                        >
                            Next
                        </button>
                    </div>
                </div>
            )}

            {/* Selection info */}
            {selectable && Object.keys(rowSelection).length > 0 && (
                <div className="px-5 py-3 bg-indigo-50 border-t border-indigo-100">
                    <p className="text-sm text-indigo-700">
                        {Object.keys(rowSelection).length} row(s) selected
                    </p>
                </div>
            )}
        </div>
    );
}

/**
 * Generate page numbers for pagination
 * Shows first page, last page, current page, and pages around current
 * Uses ellipsis for gaps
 */
function generatePageNumbers(currentPage: number, lastPage: number): (number | string)[] {
    const pages: (number | string)[] = [];
    const delta = 2; // Number of pages to show on each side of current page

    if (lastPage <= 7) {
        // Show all pages if total is 7 or less
        for (let i = 1; i <= lastPage; i++) {
            pages.push(i);
        }
    } else {
        // Always show first page
        pages.push(1);

        // Calculate range around current page
        const rangeStart = Math.max(2, currentPage - delta);
        const rangeEnd = Math.min(lastPage - 1, currentPage + delta);

        // Add ellipsis after first page if needed
        if (rangeStart > 2) {
            pages.push('...');
        }

        // Add pages around current page
        for (let i = rangeStart; i <= rangeEnd; i++) {
            pages.push(i);
        }

        // Add ellipsis before last page if needed
        if (rangeEnd < lastPage - 1) {
            pages.push('...');
        }

        // Always show last page
        pages.push(lastPage);
    }

    return pages;
}
