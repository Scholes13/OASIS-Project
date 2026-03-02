import * as React from "react"
import {
    ColumnDef,
    ColumnFiltersState,
    SortingState,
    VisibilityState,
    flexRender,
    getCoreRowModel,
    getFilteredRowModel,
    getPaginationRowModel,
    getSortedRowModel,
    useReactTable,
} from "@tanstack/react-table"
import {
    ChevronLeft,
    ChevronRight,
    ChevronsLeft,
    ChevronsRight,
    ArrowUpDown,
    ArrowUp,
    ArrowDown,
    Settings2,
    Search,
    X,
} from "lucide-react"
import { cn } from "@/lib/utils"
import { Button } from "./button"
import { Popover, Transition } from "@headlessui/react"

// Sortable Header Component
interface SortableHeaderProps {
    column: any
    title: string
}

export function SortableHeader({ column, title }: SortableHeaderProps) {
    return (
        <button
            className="flex items-center gap-1 hover:text-gray-900 transition-colors"
            onClick={() => column.toggleSorting(column.getIsSorted() === "asc")}
        >
            {title}
            {column.getIsSorted() === "desc" ? (
                <ArrowDown className="h-3.5 w-3.5" strokeWidth={1.5} />
            ) : column.getIsSorted() === "asc" ? (
                <ArrowUp className="h-3.5 w-3.5" strokeWidth={1.5} />
            ) : (
                <ArrowUpDown className="h-3.5 w-3.5 opacity-50" strokeWidth={1.5} />
            )}
        </button>
    )
}

// DataTable Component
interface DataTableProps<TData, TValue> {
    columns: ColumnDef<TData, TValue>[]
    data: TData[]
    searchKey?: string
    searchPlaceholder?: string
    onRowClick?: (row: TData) => void
    showPagination?: boolean
    showSearch?: boolean
    showColumnToggle?: boolean
    pageSize?: number
    emptyMessage?: string
    loading?: boolean
    className?: string
    rowClassName?: string
    meta?: Record<string, any>
}

export function DataTable<TData, TValue>({
    columns,
    data,
    searchKey,
    searchPlaceholder = "Search...",
    onRowClick,
    showPagination = true,
    showSearch = true,
    showColumnToggle = true,
    pageSize = 10,
    emptyMessage = "No results found.",
    loading = false,
    className,
    rowClassName,
    meta,
}: DataTableProps<TData, TValue>) {
    const [sorting, setSorting] = React.useState<SortingState>([])
    const [columnFilters, setColumnFilters] = React.useState<ColumnFiltersState>([])
    const [columnVisibility, setColumnVisibility] = React.useState<VisibilityState>({})
    const [globalFilter, setGlobalFilter] = React.useState("")

    const table = useReactTable({
        data,
        columns,
        getCoreRowModel: getCoreRowModel(),
        getPaginationRowModel: getPaginationRowModel(),
        getSortedRowModel: getSortedRowModel(),
        getFilteredRowModel: getFilteredRowModel(),
        onSortingChange: setSorting,
        onColumnFiltersChange: setColumnFilters,
        onColumnVisibilityChange: setColumnVisibility,
        onGlobalFilterChange: setGlobalFilter,
        globalFilterFn: "includesString",
        state: {
            sorting,
            columnFilters,
            columnVisibility,
            globalFilter,
        },
        initialState: {
            pagination: { pageSize },
        },
        meta,
    })

    const searchValue = searchKey 
        ? (table.getColumn(searchKey)?.getFilterValue() as string ?? "") 
        : globalFilter

    const handleSearchChange = (value: string) => {
        if (searchKey) {
            table.getColumn(searchKey)?.setFilterValue(value)
        } else {
            setGlobalFilter(value)
        }
    }

    return (
        <div className={cn("space-y-0", className)}>
            {/* Toolbar - Unified Header with px-6 spacing */}
            {(showSearch || showColumnToggle) && (
                <div className="flex items-center justify-between gap-4 px-6 py-4 border-b border-gray-100">
                    {showSearch && (
                        <div className="relative w-[280px]">
                            <Search 
                                className="absolute top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400 pointer-events-none" 
                                strokeWidth={1.5}
                                style={{ left: '12px' }}
                            />
                            <input
                                type="text"
                                placeholder={searchPlaceholder}
                                value={searchValue}
                                onChange={(e) => handleSearchChange(e.target.value)}
                                className="w-full py-2 text-sm border border-gray-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-colors placeholder:text-gray-400"
                                style={{ paddingLeft: '34px', paddingRight: '36px' }}
                            />
                            {searchValue && (
                                <button
                                    onClick={() => handleSearchChange("")}
                                    className="absolute top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                                    style={{ right: '12px' }}
                                >
                                    <X className="h-4 w-4" strokeWidth={1.5} />
                                </button>
                            )}
                        </div>
                    )}

                    {showColumnToggle && (
                        <Popover className="relative">
                            <Popover.Button className="inline-flex items-center gap-2 px-3 py-2 text-sm border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                                <Settings2 className="h-4 w-4" strokeWidth={1.5} />
                            </Popover.Button>
                            <Transition
                                as={React.Fragment}
                                enter="transition ease-out duration-100"
                                enterFrom="transform opacity-0 scale-95"
                                enterTo="transform opacity-100 scale-100"
                                leave="transition ease-in duration-75"
                                leaveFrom="transform opacity-100 scale-100"
                                leaveTo="transform opacity-0 scale-95"
                            >
                                <Popover.Panel className="absolute right-0 z-50 mt-2 w-48 bg-white rounded-lg shadow-lg ring-1 ring-black/5 p-2">
                                    {table.getAllColumns().filter((col) => col.getCanHide()).map((column) => (
                                        <label
                                            key={column.id}
                                            className="flex items-center px-2 py-1.5 text-sm hover:bg-gray-50 rounded cursor-pointer"
                                        >
                                            <input
                                                type="checkbox"
                                                checked={column.getIsVisible()}
                                                onChange={(e) => column.toggleVisibility(e.target.checked)}
                                                className="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary"
                                            />
                                            <span className="ml-2 capitalize">{column.id.replace(/_/g, " ")}</span>
                                        </label>
                                    ))}
                                </Popover.Panel>
                            </Transition>
                        </Popover>
                    )}
                </div>
            )}

            {/* Table - Allow overflow for dropdowns */}
            <div className="overflow-x-auto overflow-y-visible">
                <table className="w-full">
                    {/* Header - Subtle gray background */}
                    <thead>
                        {table.getHeaderGroups().map((headerGroup) => (
                            <tr key={headerGroup.id} className="bg-gray-100 border-b border-gray-200">
                                {headerGroup.headers.map((header) => (
                                    <th
                                        key={header.id}
                                        className="h-12 px-5 text-left text-sm font-semibold text-gray-700 uppercase tracking-wider"
                                    >
                                        {header.isPlaceholder ? null : (
                                            flexRender(header.column.columnDef.header, header.getContext())
                                        )}
                                    </th>
                                ))}
                            </tr>
                        ))}
                    </thead>
                    
                    {/* Body */}
                    <tbody>
                        {loading ? (
                            <tr>
                                <td colSpan={columns.length} className="h-24 text-center">
                                    <div className="flex items-center justify-center">
                                        <div className="h-6 w-6 animate-spin rounded-full border-2 border-gray-300 border-t-teal-600" />
                                        <span className="ml-2 text-gray-500">Loading...</span>
                                    </div>
                                </td>
                            </tr>
                        ) : table.getRowModel().rows?.length ? (
                            table.getRowModel().rows.map((row, index) => (
                                <tr
                                    key={row.id}
                                    onClick={() => onRowClick?.(row.original)}
                                    className={cn(
                                        "border-b border-gray-100 transition-colors hover:bg-gray-50/80",
                                        onRowClick && "cursor-pointer",
                                        rowClassName
                                    )}
                                >
                                    {row.getVisibleCells().map((cell) => (
                                        <td key={cell.id} className="px-5 py-4 text-base">
                                            {flexRender(cell.column.columnDef.cell, cell.getContext())}
                                        </td>
                                    ))}
                                </tr>
                            ))
                        ) : (
                            <tr>
                                <td colSpan={columns.length} className="h-24 text-center text-gray-500">
                                    {emptyMessage}
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>

            {/* Pagination */}
            {showPagination && (
                <div className="flex items-center justify-between px-6 py-4 border-t border-gray-100">
                    <div className="text-sm text-gray-500">
                        Showing {table.getRowModel().rows.length} of {table.getFilteredRowModel().rows.length} row(s).
                    </div>
                    <div className="flex items-center gap-1">
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => table.setPageIndex(0)}
                            disabled={!table.getCanPreviousPage()}
                        >
                            <ChevronsLeft className="h-4 w-4" strokeWidth={1.5} />
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => table.previousPage()}
                            disabled={!table.getCanPreviousPage()}
                        >
                            <ChevronLeft className="h-4 w-4" strokeWidth={1.5} />
                        </Button>
                        <span className="px-3 py-1 text-sm text-gray-600">
                            {table.getState().pagination.pageIndex + 1} / {table.getPageCount()}
                        </span>
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => table.nextPage()}
                            disabled={!table.getCanNextPage()}
                        >
                            <ChevronRight className="h-4 w-4" strokeWidth={1.5} />
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => table.setPageIndex(table.getPageCount() - 1)}
                            disabled={!table.getCanNextPage()}
                        >
                            <ChevronsRight className="h-4 w-4" strokeWidth={1.5} />
                        </Button>
                    </div>
                </div>
            )}
        </div>
    )
}

export default DataTable
