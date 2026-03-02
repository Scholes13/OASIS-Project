/**
 * DataTable Component - Usage Examples
 * 
 * This file demonstrates how to use the DataTable component with TanStack Table
 */

import React from 'react';
import { DataTable, PaginationData } from './DataTable';
import { ColumnDef } from '@tanstack/react-table';
import { Pencil, Trash2, Eye } from 'lucide-react';

// Example 1: Basic User Table
interface User {
    id: number;
    name: string;
    email: string;
    role: string;
    is_active: boolean;
}

const userColumns: ColumnDef<User>[] = [
    {
        accessorKey: 'name',
        header: 'Name',
        enableSorting: true,
    },
    {
        accessorKey: 'email',
        header: 'Email',
        enableSorting: true,
    },
    {
        accessorKey: 'role',
        header: 'Role',
        cell: ({ row }) => (
            <span className="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-blue-50 text-blue-700">
                {row.original.role}
            </span>
        ),
    },
    {
        accessorKey: 'is_active',
        header: 'Status',
        cell: ({ row }) => (
            <span
                className={`inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium ${
                    row.original.is_active
                        ? 'bg-emerald-100 text-emerald-700'
                        : 'bg-red-100 text-red-700'
                }`}
            >
                {row.original.is_active ? 'Active' : 'Inactive'}
            </span>
        ),
    },
    {
        id: 'actions',
        header: 'Actions',
        cell: ({ row }) => (
            <div className="flex items-center gap-2">
                <button
                    className="p-1.5 text-gray-600 hover:text-primary hover:bg-blue-600 rounded transition-colors"
                    aria-label="View user"
                >
                    <Eye className="w-4 h-4" />
                </button>
                <button
                    className="p-1.5 text-gray-600 hover:text-primary hover:bg-blue-600 rounded transition-colors"
                    aria-label="Edit user"
                >
                    <Pencil className="w-4 h-4" />
                </button>
                <button
                    className="p-1.5 text-gray-600 hover:text-red-600 hover:bg-red-50 rounded transition-colors"
                    aria-label="Delete user"
                >
                    <Trash2 className="w-4 h-4" />
                </button>
            </div>
        ),
    },
];

export function BasicUserTableExample() {
    const users: User[] = [
        { id: 1, name: 'John Doe', email: 'john@example.com', role: 'Admin', is_active: true },
        { id: 2, name: 'Jane Smith', email: 'jane@example.com', role: 'User', is_active: true },
        { id: 3, name: 'Bob Johnson', email: 'bob@example.com', role: 'User', is_active: false },
    ];

    return (
        <DataTable
            data={users}
            columns={userColumns}
            emptyMessage="No users found"
        />
    );
}

// Example 2: Table with Pagination
export function PaginatedTableExample() {
    const users: User[] = [
        { id: 1, name: 'John Doe', email: 'john@example.com', role: 'Admin', is_active: true },
        { id: 2, name: 'Jane Smith', email: 'jane@example.com', role: 'User', is_active: true },
    ];

    const pagination: PaginationData = {
        current_page: 1,
        last_page: 10,
        per_page: 15,
        total: 150,
        from: 1,
        to: 15,
    };

    const handlePageChange = (page: number) => {
        console.log('Navigate to page:', page);
        // Use Inertia router to navigate with page parameter
        // router.get('/admin/users', { page }, { preserveState: true });
    };

    return (
        <DataTable
            data={users}
            columns={userColumns}
            pagination={pagination}
            onPageChange={handlePageChange}
            emptyMessage="No users found"
        />
    );
}

// Example 3: Table with Sorting
export function SortableTableExample() {
    const users: User[] = [
        { id: 1, name: 'John Doe', email: 'john@example.com', role: 'Admin', is_active: true },
        { id: 2, name: 'Jane Smith', email: 'jane@example.com', role: 'User', is_active: true },
    ];

    const handleSort = (column: string, direction: 'asc' | 'desc') => {
        console.log('Sort by:', column, direction);
        // Use Inertia router to navigate with sort parameters
        // router.get('/admin/users', { sort: column, direction }, { preserveState: true });
    };

    return (
        <DataTable
            data={users}
            columns={userColumns}
            onSort={handleSort}
            emptyMessage="No users found"
        />
    );
}

// Example 4: Table with Row Selection
export function SelectableTableExample() {
    const users: User[] = [
        { id: 1, name: 'John Doe', email: 'john@example.com', role: 'Admin', is_active: true },
        { id: 2, name: 'Jane Smith', email: 'jane@example.com', role: 'User', is_active: true },
    ];

    // Add selection column
    const selectableColumns: ColumnDef<User>[] = [
        {
            id: 'select',
            header: ({ table }) => (
                <input
                    type="checkbox"
                    checked={table.getIsAllRowsSelected()}
                    onChange={table.getToggleAllRowsSelectedHandler()}
                    className="rounded border-gray-300 text-primary focus:ring-primary"
                />
            ),
            cell: ({ row }) => (
                <input
                    type="checkbox"
                    checked={row.getIsSelected()}
                    onChange={row.getToggleSelectedHandler()}
                    className="rounded border-gray-300 text-primary focus:ring-primary"
                />
            ),
        },
        ...userColumns,
    ];

    const handleSelectionChange = (selectedRows: User[]) => {
        console.log('Selected users:', selectedRows);
    };

    return (
        <DataTable
            data={users}
            columns={selectableColumns}
            selectable={true}
            onSelectionChange={handleSelectionChange}
            emptyMessage="No users found"
        />
    );
}

// Example 5: Loading State
export function LoadingTableExample() {
    return (
        <DataTable
            data={[]}
            columns={userColumns}
            loading={true}
        />
    );
}

// Example 6: Empty State
export function EmptyTableExample() {
    return (
        <DataTable
            data={[]}
            columns={userColumns}
            emptyMessage="No users have been created yet. Click 'Create User' to get started."
        />
    );
}

// Example 7: Complete Example with Inertia
export function CompleteInertiaExample() {
    // This would be in a Page component receiving props from Inertia
    interface PageProps {
        users: {
            data: User[];
            current_page: number;
            last_page: number;
            per_page: number;
            total: number;
            from: number;
            to: number;
        };
        filters: {
            search?: string;
            role?: string;
        };
    }

    // Example usage in a page component:
    /*
    import { router } from '@inertiajs/react';
    
    export default function UsersIndex({ users, filters }: PageProps) {
        const handlePageChange = (page: number) => {
            router.get('/admin/users', { ...filters, page }, { preserveState: true });
        };

        const handleSort = (column: string, direction: 'asc' | 'desc') => {
            router.get('/admin/users', { ...filters, sort: column, direction }, { preserveState: true });
        };

        return (
            <div className="p-6">
                <h1 className="text-2xl font-bold text-gray-900 mb-6">User Management</h1>
                
                <DataTable
                    data={users.data}
                    columns={userColumns}
                    pagination={{
                        current_page: users.current_page,
                        last_page: users.last_page,
                        per_page: users.per_page,
                        total: users.total,
                        from: users.from,
                        to: users.to,
                    }}
                    onPageChange={handlePageChange}
                    onSort={handleSort}
                    emptyMessage="No users found. Try adjusting your filters."
                />
            </div>
        );
    }
    */

    return null;
}
