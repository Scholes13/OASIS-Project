import { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { DataTable } from '@/components/admin/DataTable';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select } from '@/components/ui/select';
import { Badge } from '@/components/ui/Badge';
import { BusinessUnitWithStats, PaginationData } from '@/types/admin';
import { ColumnDef } from '@tanstack/react-table';
import { Plus, Edit, Eye, Power, Trash2, Image as ImageIcon } from 'lucide-react';
import { toast } from 'sonner';

interface BusinessUnitsIndexProps {
  businessUnits: {
    data: (BusinessUnitWithStats & { logo_url?: string; user_count?: number; department_count?: number })[];
    pagination: PaginationData;
  };
  filters: {
    search?: string;
    status?: string;
  };
}

export default function Index({ businessUnits, filters }: BusinessUnitsIndexProps) {
  const [search, setSearch] = useState(filters.search || '');
  const [status, setStatus] = useState(filters.status || '');

  const handleSearch = (value: string) => {
    setSearch(value);
    router.get(
      route('admin.business-units.index'),
      { search: value, status },
      { preserveState: true, replace: true }
    );
  };

  const handleStatusFilter = (value: string) => {
    setStatus(value);
    router.get(
      route('admin.business-units.index'),
      { search, status: value },
      { preserveState: true, replace: true }
    );
  };

  const handleToggleStatus = (businessUnit: BusinessUnitWithStats) => {
    if (confirm(`Are you sure you want to ${businessUnit.is_active ? 'deactivate' : 'activate'} ${businessUnit.name}?`)) {
      router.post(
        route('admin.business-units.toggle-status', { business_unit: businessUnit.id }),
        {},
        {
          onSuccess: () => {
            toast.success(`Business unit ${businessUnit.is_active ? 'deactivated' : 'activated'} successfully`);
          },
          onError: () => {
            toast.error('Failed to toggle business unit status');
          },
        }
      );
    }
  };

  const handleDelete = (businessUnit: BusinessUnitWithStats) => {
    if (businessUnit.code === 'WG') {
      toast.error('Cannot delete the parent company (Werkudara Group)');
      return;
    }

    if (confirm(`Are you sure you want to delete ${businessUnit.name}? This action cannot be undone.`)) {
      router.delete(route('admin.business-units.destroy', { business_unit: businessUnit.id }), {
        onSuccess: () => {
          toast.success('Business unit deleted successfully');
        },
        onError: (errors: any) => {
          toast.error(errors.message || 'Failed to delete business unit');
        },
      });
    }
  };

  type BusinessUnitRow = BusinessUnitWithStats & { logo_url?: string; user_count?: number; department_count?: number };

  const columns: ColumnDef<BusinessUnitRow>[] = [
    {
      accessorKey: 'logo_url',
      header: 'Logo',
      cell: ({ row }) => (
        <div className="flex items-center justify-center w-12 h-12 bg-gray-100 rounded-lg overflow-hidden">
          {row.original.logo_url ? (
            <img
              src={row.original.logo_url}
              alt={row.original.name}
              className="w-full h-full object-cover"
            />
          ) : (
            <ImageIcon className="w-6 h-6 text-gray-400" />
          )}
        </div>
      ),
    },
    {
      accessorKey: 'code',
      header: 'Code',
      cell: ({ row }) => (
        <span className="font-mono text-sm font-medium text-gray-900">
          {row.original.code}
        </span>
      ),
    },
    {
      accessorKey: 'name',
      header: 'Name',
      cell: ({ row }) => (
        <div>
          <div className="font-medium text-gray-900">{row.original.name}</div>
          {row.original.parent && (
            <div className="text-sm text-gray-500">
              Parent: {row.original.parent.name}
            </div>
          )}
        </div>
      ),
    },
    {
      accessorKey: 'user_count',
      header: 'Users',
      cell: ({ row }) => (
        <span className="text-sm text-gray-600">{row.original.users_count || row.original.user_count || 0}</span>
      ),
    },
    {
      accessorKey: 'department_count',
      header: 'Departments',
      cell: ({ row }) => (
        <span className="text-sm text-gray-600">{row.original.departments_count || row.original.department_count || 0}</span>
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
          <Button
            variant="ghost"
            size="sm"
            onClick={() => router.visit(route('admin.business-units.show', { business_unit: row.original.id }))}
            aria-label={`View ${row.original.name}`}
          >
            <Eye className="w-4 h-4" />
          </Button>
          <Button
            variant="ghost"
            size="sm"
            onClick={() => router.visit(route('admin.business-units.edit', { business_unit: row.original.id }))}
            aria-label={`Edit ${row.original.name}`}
          >
            <Edit className="w-4 h-4" />
          </Button>
          <Button
            variant="ghost"
            size="sm"
            onClick={() => handleToggleStatus(row.original)}
            aria-label={`${row.original.is_active ? 'Deactivate' : 'Activate'} ${row.original.name}`}
          >
            <Power className="w-4 h-4" />
          </Button>
          {row.original.code !== 'WG' && (
            <Button
              variant="ghost"
              size="sm"
              onClick={() => handleDelete(row.original)}
              className="text-red-600 hover:text-red-700 hover:bg-red-50"
              aria-label={`Delete ${row.original.name}`}
            >
              <Trash2 className="w-4 h-4" />
            </Button>
          )}
        </div>
      ),
    },
  ];

  return (
    <>
      <Head title="Business Units" />

      <div className="p-6 space-y-6">
        {/* Header */}
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-2xl font-bold text-gray-900">Business Units</h1>
            <p className="mt-1 text-sm text-gray-600">
              Manage organizational business units and their hierarchies
            </p>
          </div>
          <Button onClick={() => router.visit(route('admin.business-units.create'))}>
            <Plus className="w-4 h-4 mr-2" />
            Add Business Unit
          </Button>
        </div>

        {/* Filters */}
        <div className="flex items-center gap-4">
          <div className="flex-1">
            <Input
              placeholder="Search business units..."
              value={search}
              onChange={(e) => handleSearch(e.target.value)}
              className="max-w-md"
            />
          </div>
          <Select
            value={status}
            onChange={(value) => handleStatusFilter(value?.toString() || '')}
            options={[
              { value: '', label: 'All Status' },
              { value: 'active', label: 'Active' },
              { value: 'inactive', label: 'Inactive' },
            ]}
            className="w-40"
          />
        </div>

        {/* Table */}
        <DataTable
          data={businessUnits.data}
          columns={columns}
          pagination={businessUnits.pagination}
          onPageChange={(page) => {
            router.get(
              route('admin.business-units.index'),
              { search, status, page },
              { preserveState: true, replace: true }
            );
          }}
          emptyMessage="No business units found"
        />
      </div>
    </>
  );
}


