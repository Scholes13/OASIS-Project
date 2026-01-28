import { useState, useCallback, useEffect } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { DataTable } from '@/components/admin/DataTable';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select } from '@/components/ui/select';
import { Badge } from '@/components/ui/Badge';
import { User, PaginationData, SelectOption } from '@/types/admin';
import { ColumnDef } from '@tanstack/react-table';
import { UserPlus, Edit, Eye, UserX } from 'lucide-react';
import { toast } from 'sonner';

interface UsersIndexProps {
  users: {
    data: User[];
    pagination: PaginationData;
  };
  filters: {
    businessUnits: SelectOption[];
    departments: SelectOption[];
    roles: SelectOption[];
  };
  queryParams: {
    search?: string;
    business_unit?: string;
    department?: string;
    global_role?: string;
    page?: number;
  };
}

export default function Index({ users, filters, queryParams }: UsersIndexProps) {
  const [search, setSearch] = useState(queryParams.search || '');
  const [businessUnit, setBusinessUnit] = useState(queryParams.business_unit || '');
  const [department, setDepartment] = useState(queryParams.department || '');
  const [role, setRole] = useState(queryParams.global_role || '');

  // Debounced search with 300ms delay
  useEffect(() => {
    const timer = setTimeout(() => {
      handleFilterChange();
    }, 300);

    return () => clearTimeout(timer);
  }, [search]);

  const handleFilterChange = useCallback(() => {
    const params: Record<string, any> = {};
    
    if (search) params.search = search;
    if (businessUnit) params.business_unit = businessUnit;
    if (department) params.department = department;
    if (role) params.global_role = role;

    router.get(route('admin.users.index'), params, {
      preserveState: true,
      preserveScroll: true,
    });
  }, [search, businessUnit, department, role]);

  const handleDeactivate = (userId: number, userName: string) => {
    if (confirm(`Are you sure you want to deactivate user "${userName}"?`)) {
      router.delete(route('admin.users.destroy', { user: userId }), {
        onSuccess: () => {
          toast.success('User deactivated successfully');
        },
        onError: () => {
          toast.error('Failed to deactivate user');
        },
      });
    }
  };

  const columns: ColumnDef<User>[] = [
    {
      accessorKey: 'name',
      header: 'Name',
      cell: ({ row }) => (
        <div>
          <div className="font-medium text-gray-900">{row.original.name}</div>
          <div className="text-sm text-gray-500">{row.original.email}</div>
        </div>
      ),
    },
    {
      accessorKey: 'primary_business_unit',
      header: 'Primary Business Unit',
      cell: ({ row }) => (
        <div>
          <div className="text-sm text-gray-900">
            {row.original.primary_business_unit?.name || 'N/A'}
          </div>
          <div className="text-xs text-gray-500">
            {row.original.primary_business_unit?.code || ''}
          </div>
        </div>
      ),
    },
    {
      accessorKey: 'business_units',
      header: 'Assignments',
      cell: ({ row }) => (
        <div className="text-sm text-gray-600">
          {row.original.business_units?.length || 0} business unit(s)
        </div>
      ),
    },
    {
      accessorKey: 'is_active',
      header: 'Status',
      cell: ({ row }) => (
        <Badge variant={row.original.is_active ? 'success' : 'default'}>
          {row.original.is_active ? 'Active' : 'Inactive'}
        </Badge>
      ),
    },
    {
      id: 'actions',
      header: 'Actions',
      cell: ({ row }) => (
        <div className="flex items-center gap-2">
          <Link
            href={route('admin.users.show', { user: row.original.id })}
            className="inline-flex items-center text-sm text-gray-600 hover:text-gray-900"
            aria-label={`View ${row.original.name}`}
          >
            <Eye className="w-4 h-4" />
          </Link>
          <Link
            href={route('admin.users.edit', { user: row.original.id })}
            className="inline-flex items-center text-sm text-indigo-600 hover:text-indigo-900"
            aria-label={`Edit ${row.original.name}`}
          >
            <Edit className="w-4 h-4" />
          </Link>
          {row.original.is_active && !row.original.is_super_admin && (
            <button
              onClick={() => handleDeactivate(row.original.id, row.original.name)}
              className="inline-flex items-center text-sm text-red-600 hover:text-red-900"
              aria-label={`Deactivate ${row.original.name}`}
            >
              <UserX className="w-4 h-4" />
            </button>
          )}
        </div>
      ),
    },
  ];

  return (
    <>
      <Head title="User Management" />

      <div className="p-6 space-y-6">
        {/* Header with Create Button */}
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-2xl font-bold text-gray-900">Users</h1>
            <p className="mt-1 text-sm text-gray-600">
              Manage system users and their business unit assignments
            </p>
          </div>
          <Link href={route('admin.users.create')}>
            <Button>
              <UserPlus className="w-4 h-4 mr-2" />
              Create User
            </Button>
          </Link>
        </div>

        {/* Filters */}
        <div className="bg-white rounded-xl border border-gray-100 p-6">
          <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
            {/* Search */}
            <div>
              <Label htmlFor="search">Search</Label>
              <Input
                id="search"
                type="text"
                placeholder="Search by name or email..."
                value={search}
                onChange={(e) => setSearch(e.target.value)}
              />
            </div>

            {/* Business Unit Filter */}
            <div>
              <Select
                label="Business Unit"
                value={businessUnit}
                onChange={(value) => {
                  setBusinessUnit(String(value));
                  handleFilterChange();
                }}
                options={[
                  { value: '', label: 'All Business Units' },
                  ...filters.businessUnits,
                ]}
                placeholder="All Business Units"
              />
            </div>

            {/* Department Filter */}
            <div>
              <Select
                label="Department"
                value={department}
                onChange={(value) => {
                  setDepartment(String(value));
                  handleFilterChange();
                }}
                options={[
                  { value: '', label: 'All Departments' },
                  ...filters.departments,
                ]}
                placeholder="All Departments"
              />
            </div>

            {/* Role Filter */}
            <div>
              <Select
                label="Role"
                value={role}
                onChange={(value) => {
                  setRole(String(value));
                  handleFilterChange();
                }}
                options={[
                  { value: '', label: 'All Roles' },
                  ...filters.roles,
                ]}
                placeholder="All Roles"
              />
            </div>
          </div>
        </div>

        {/* Data Table */}
        <DataTable
          data={users.data}
          columns={columns}
          pagination={users.pagination}
          onPageChange={(page) => {
            router.get(
              route('admin.users.index'),
              { ...queryParams, page },
              { preserveState: true, preserveScroll: true }
            );
          }}
          emptyMessage="No users found. Create your first user to get started."
        />
      </div>
    </>
  );
}

