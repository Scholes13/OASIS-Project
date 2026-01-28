import { Head, Link, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/Badge';
import { Building2, ArrowLeft, Edit, Trash2, Users, Briefcase, FileText, Hash, Mail, Phone, MapPin } from 'lucide-react';
import type { BusinessUnitShowProps } from '@/types/admin';
import { toast } from 'sonner';

export default function Show({ businessUnit, departments, stats }: BusinessUnitShowProps) {
  const handleDelete = () => {
    if (confirm(`Are you sure you want to delete ${businessUnit.name}?`)) {
      router.delete(route('admin.business-units.destroy', { business_unit: businessUnit.id }), {
        onSuccess: () => toast.success('Business unit deleted successfully'),
        onError: () => toast.error('Failed to delete business unit'),
      });
    }
  };

  return (
    <>
      <Head title={`Business Unit: ${businessUnit.name}`} />
      <div className="p-6 space-y-6">
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-4">
            <Link href={route('admin.business-units.index')} className="p-2 hover:bg-gray-100 rounded-lg transition-colors"><ArrowLeft className="w-5 h-5 text-gray-600" /></Link>
            <div className="flex items-center gap-3">
              {businessUnit.logo ? <img src={businessUnit.logo} alt={businessUnit.name} className="w-12 h-12 rounded-lg object-cover" /> : <div className="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center"><Building2 className="w-6 h-6 text-indigo-600" /></div>}
              <div><h1 className="text-2xl font-bold text-gray-900">{businessUnit.name}</h1><p className="text-sm text-gray-500">Code: {businessUnit.code}</p></div>
            </div>
          </div>
          <div className="flex items-center gap-2">
            <Button variant="outline" onClick={() => router.visit(route('admin.business-units.edit', { business_unit: businessUnit.id }))} className="flex items-center gap-2"><Edit className="w-4 h-4" />Edit</Button>
            <Button variant="outline" onClick={handleDelete} className="flex items-center gap-2 text-red-600 hover:text-red-700 hover:bg-red-50"><Trash2 className="w-4 h-4" />Delete</Button>
          </div>
        </div>
        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div className="bg-white rounded-lg border border-gray-200 p-4"><div className="flex items-center gap-3"><div className="p-2 bg-indigo-100 rounded-lg"><Briefcase className="w-5 h-5 text-indigo-600" /></div><div><p className="text-2xl font-bold text-gray-900">{stats.total_departments}</p><p className="text-sm text-gray-500">Departments</p></div></div></div>
          <div className="bg-white rounded-lg border border-gray-200 p-4"><div className="flex items-center gap-3"><div className="p-2 bg-emerald-100 rounded-lg"><Users className="w-5 h-5 text-emerald-600" /></div><div><p className="text-2xl font-bold text-gray-900">{stats.total_users}</p><p className="text-sm text-gray-500">Users</p></div></div></div>
          <div className="bg-white rounded-lg border border-gray-200 p-4"><div className="flex items-center gap-3"><div className="p-2 bg-blue-100 rounded-lg"><FileText className="w-5 h-5 text-blue-600" /></div><div><p className="text-2xl font-bold text-gray-900">{stats.total_purchase_requests}</p><p className="text-sm text-gray-500">Purchase Requests</p></div></div></div>
          <div className="bg-white rounded-lg border border-gray-200 p-4"><div className="flex items-center gap-3"><div className="p-2 bg-amber-100 rounded-lg"><Hash className="w-5 h-5 text-amber-600" /></div><div><p className="text-2xl font-bold text-gray-900">{stats.active_sequences}</p><p className="text-sm text-gray-500">Active Sequences</p></div></div></div>
        </div>
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <div className="bg-white rounded-lg border border-gray-200">
            <div className="px-6 py-4 border-b border-gray-200"><h2 className="text-lg font-semibold text-gray-900">Basic Information</h2></div>
            <div className="p-6 space-y-4">
              <div className="flex items-center justify-between"><span className="text-sm text-gray-500">Status</span><Badge variant={businessUnit.is_active ? 'success' : 'default'}>{businessUnit.is_active ? 'Active' : 'Inactive'}</Badge></div>
              {businessUnit.description && <div><span className="text-sm text-gray-500">Description</span><p className="mt-1 text-sm text-gray-900">{businessUnit.description}</p></div>}
              {businessUnit.parent && <div className="flex items-center justify-between"><span className="text-sm text-gray-500">Parent Unit</span><span className="text-sm text-gray-900">{businessUnit.parent.name}</span></div>}
              {businessUnit.manager && <div className="flex items-center justify-between"><span className="text-sm text-gray-500">Manager</span><span className="text-sm text-gray-900">{businessUnit.manager.name}</span></div>}
            </div>
          </div>
          <div className="bg-white rounded-lg border border-gray-200">
            <div className="px-6 py-4 border-b border-gray-200"><h2 className="text-lg font-semibold text-gray-900">Contact Information</h2></div>
            <div className="p-6 space-y-4">
              {businessUnit.email && <div className="flex items-center gap-3"><Mail className="w-4 h-4 text-gray-400" /><span className="text-sm text-gray-900">{businessUnit.email}</span></div>}
              {businessUnit.phone && <div className="flex items-center gap-3"><Phone className="w-4 h-4 text-gray-400" /><span className="text-sm text-gray-900">{businessUnit.phone}</span></div>}
              {businessUnit.address && <div className="flex items-start gap-3"><MapPin className="w-4 h-4 text-gray-400 mt-0.5" /><span className="text-sm text-gray-900">{businessUnit.address}</span></div>}
              {!businessUnit.email && !businessUnit.phone && !businessUnit.address && <p className="text-sm text-gray-500">No contact information available</p>}
            </div>
          </div>
        </div>
        <div className="bg-white rounded-lg border border-gray-200">
          <div className="px-6 py-4 border-b border-gray-200 flex items-center justify-between"><h2 className="text-lg font-semibold text-gray-900">Departments</h2><Button variant="outline" size="sm" onClick={() => router.visit(route('admin.departments.create'))}>Add Department</Button></div>
          <div className="divide-y divide-gray-200">
            {departments && departments.length > 0 ? departments.map((dept) => <div key={dept.id} className="px-6 py-4 flex items-center justify-between hover:bg-gray-50"><div><p className="text-sm font-medium text-gray-900">{dept.name}</p><p className="text-xs text-gray-500">Code: {dept.code}</p></div><Button variant="ghost" size="sm" onClick={() => router.visit(route('admin.departments.show', { department: dept.id }))}>View</Button></div>) : <div className="px-6 py-8 text-center"><Briefcase className="w-8 h-8 text-gray-400 mx-auto mb-2" /><p className="text-sm text-gray-500">No departments found</p></div>}
          </div>
        </div>
      </div>
    </>
  );
}
