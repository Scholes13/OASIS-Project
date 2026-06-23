import { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { DataTable } from '@/components/admin/DataTable';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/Badge';
import { Building2, Plus, Search, X, Eye, Edit, Trash2, ShoppingCart, Users, Briefcase } from 'lucide-react';
import type { DepartmentWithStats, PaginationData } from '@/types/admin';
import type { BusinessUnit, PageProps } from '@/types';
import { ColumnDef } from '@tanstack/react-table';
import { toast } from 'sonner';

interface DepartmentIndexProps extends PageProps {
  departments: { data: DepartmentWithStats[]; pagination: PaginationData; };
  businessUnits: BusinessUnit[];
  filters: { search?: string; business_unit_id?: number; };
}

export default function Index({ departments, businessUnits, filters }: DepartmentIndexProps) {
  const [search, setSearch] = useState(filters.search || '');
  const [businessUnitId, setBusinessUnitId] = useState<string>(filters.business_unit_id?.toString() || '');

  const handleSearch = (value: string) => {
    setSearch(value);
    setTimeout(() => {
      router.get(route('admin.departments.index'), { search: value || undefined, business_unit_id: businessUnitId || undefined }, { preserveState: true, preserveScroll: true });
    }, 300);
  };

  const handleBusinessUnitFilter = (value: string) => {
    setBusinessUnitId(value);
    router.get(route('admin.departments.index'), { search: search || undefined, business_unit_id: value || undefined }, { preserveState: true, preserveScroll: true });
  };

  const handleClearFilters = () => {
    setSearch('');
    setBusinessUnitId('');
    router.get(route('admin.departments.index'));
  };

  const handleDelete = (department: DepartmentWithStats) => {
    if (confirm(`Are you sure you want to delete ${department.name}?`)) {
      router.delete(route('admin.departments.destroy', { department: department.id }), {
        onSuccess: () => toast.success('Department deleted successfully'),
        onError: () => toast.error('Failed to delete department'),
      });
    }
  };

  const columns: ColumnDef<DepartmentWithStats>[] = [
    { accessorKey: 'code', header: 'Code', cell: ({ row }) => <span className="font-mono text-sm text-gray-900">{row.original.code}</span> },
    { accessorKey: 'name', header: 'Department Name', cell: ({ row }) => <div className="flex flex-col"><span className="font-medium text-gray-900">{row.original.name}</span>{row.original.business_unit && <span className="text-xs text-gray-500">{row.original.business_unit.name}</span>}</div> },
    { accessorKey: 'business_unit', header: 'Business Unit', cell: ({ row }) => <div className="flex items-center gap-2"><Building2 className="w-4 h-4 text-gray-400" /><span className="text-sm text-gray-900">{row.original.business_unit?.name || 'N/A'}</span></div> },
    { accessorKey: 'positions_count', header: 'Positions', cell: ({ row }) => <div className="flex items-center gap-2"><Briefcase className="w-4 h-4 text-gray-400" /><span className="text-sm text-gray-900">{row.original.positions_count || 0}</span></div> },
    { accessorKey: 'users_count', header: 'Users', cell: ({ row }) => <div className="flex items-center gap-2"><Users className="w-4 h-4 text-gray-400" /><span className="text-sm text-gray-900">{row.original.users_count || 0}</span></div> },
    { accessorKey: 'is_purchasing_enabled', header: 'Purchasing', cell: ({ row }) => row.original.is_purchasing_enabled ? <Badge variant="success" className="flex items-center gap-1 w-fit"><ShoppingCart className="w-3 h-3" />Enabled</Badge> : <Badge variant="default" className="w-fit">Disabled</Badge> },
    { accessorKey: 'is_ga_stock_review_enabled', header: 'GA Review', cell: ({ row }) => row.original.is_ga_stock_review_enabled ? <Badge variant="success" className="w-fit">Enabled</Badge> : <Badge variant="default" className="w-fit">Disabled</Badge> },
    { accessorKey: 'is_active', header: 'Status', cell: ({ row }) => <Badge variant={row.original.is_active ? 'success' : 'default'}>{row.original.is_active ? 'Active' : 'Inactive'}</Badge> },
    { id: 'actions', header: 'Actions', cell: ({ row }) => <div className="flex items-center gap-2"><Button variant="ghost" size="sm" onClick={() => router.visit(route('admin.departments.show', { department: row.original.id }))} className="h-8 w-8 p-0"><Eye className="w-4 h-4" /></Button><Button variant="ghost" size="sm" onClick={() => router.visit(route('admin.departments.edit', { department: row.original.id }))} className="h-8 w-8 p-0"><Edit className="w-4 h-4" /></Button><Button variant="ghost" size="sm" onClick={() => handleDelete(row.original)} className="h-8 w-8 p-0 text-red-600 hover:text-red-700 hover:bg-red-50"><Trash2 className="w-4 h-4" /></Button></div> },
  ];

  const hasActiveFilters = search || businessUnitId;

  return (
    <>
      <Head title="Departments" />
      <div className="p-6 space-y-6">
        <div className="flex items-center justify-between">
          <div><h1 className="text-2xl font-bold text-gray-900">Departments</h1><p className="mt-1 text-sm text-gray-500">Manage departments and their organizational structure</p></div>
          <Button onClick={() => router.visit(route('admin.departments.create'))} className="flex items-center gap-2"><Plus className="w-4 h-4" />Add Department</Button>
        </div>
        <div className="bg-white rounded-lg border border-gray-200 p-4">
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div className="space-y-2"><Label htmlFor="search">Search</Label><div className="relative"><Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" /><Input id="search" type="text" placeholder="Search departments..." value={search} onChange={(e) => handleSearch(e.target.value)} className="pl-9" /></div></div>
            <div className="space-y-2"><Label htmlFor="business-unit">Business Unit</Label><select id="business-unit" value={businessUnitId} onChange={(e) => handleBusinessUnitFilter(e.target.value)} className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"><option value="">All Business Units</option>{businessUnits.map((bu) => <option key={bu.id} value={bu.id}>{bu.name}</option>)}</select></div>
            <div className="flex items-end">{hasActiveFilters && <Button variant="outline" onClick={handleClearFilters} className="w-full flex items-center justify-center gap-2"><X className="w-4 h-4" />Clear Filters</Button>}</div>
          </div>
        </div>
        <div className="bg-white rounded-lg border border-gray-200"><DataTable columns={columns} data={departments.data} pagination={departments.pagination} onPageChange={(page) => { router.get(route('admin.departments.index'), { page, search: search || undefined, business_unit_id: businessUnitId || undefined }, { preserveState: true, preserveScroll: true }); }} emptyMessage="No departments found." /></div>
      </div>
    </>
  );
}
