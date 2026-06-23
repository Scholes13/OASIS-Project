import { Head, router, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card } from '@/components/ui/Card';
import { ArrowLeft, Save, Check, Search } from 'lucide-react';
import type { DepartmentFormData, DepartmentWithStats, Position, User, BusinessUnit } from '@/types/admin';
import { toast } from 'sonner';
import { FormEventHandler, useState, useMemo } from 'react';

interface SubActivity {
  id: number;
  code: string;
  name: string;
  activity_type: {
    id: number;
    name: string;
    color: string;
  } | null;
}

interface EditProps {
  department: DepartmentWithStats & { positions?: Position[]; sub_activity_ids?: number[] };
  businessUnits: BusinessUnit[];
  users: User[];
  subActivities: SubActivity[];
  errors?: Record<string, string>;
}

function Edit({ department, businessUnits, users, subActivities, errors }: EditProps) {
  const { data, setData, put, processing } = useForm<
    DepartmentFormData & { sub_activity_ids: number[] }
  >({
    code: department.code,
    name: department.name,
    business_unit_id: department.business_unit_id || department.business_unit?.id || 0,
    head_id: department.head_id || department.head?.id,
    is_active: department.is_active,
    sort_order: department.sort_order || 0,
    is_purchasing_enabled: department.is_purchasing_enabled || false,
    is_ga_stock_review_enabled: department.is_ga_stock_review_enabled || false,
    purchasing_admin_id: department.purchasing_admin_id || department.purchasing_admin?.id,
    sub_activity_ids: department.sub_activity_ids || [],
  });

  const [subActivitySearch, setSubActivitySearch] = useState('');

  const groupedSubActivities = useMemo(() => {
    const filtered = subActivities.filter(
      (sub) =>
        sub.name.toLowerCase().includes(subActivitySearch.toLowerCase()) ||
        sub.activity_type?.name.toLowerCase().includes(subActivitySearch.toLowerCase())
    );

    const groups: Record<string, SubActivity[]> = {};
    filtered.forEach((sub) => {
      const typeName = sub.activity_type?.name || 'Other';
      if (!groups[typeName]) {
        groups[typeName] = [];
      }
      groups[typeName].push(sub);
    });

    return groups;
  }, [subActivities, subActivitySearch]);

  const handleSubmit: FormEventHandler = (e) => {
    e.preventDefault();

    put(route('admin.departments.update', { department: department.id }), {
      onSuccess: () => {
        toast.success('Department updated successfully');
      },
      onError: (errors) => {
        const errorMessage =
          typeof errors === 'object' && errors !== null && 'message' in errors
            ? (errors as { message: string }).message
            : 'Failed to update department';
        toast.error(errorMessage);
      },
    });
  };

  const toggleSubActivity = (subId: number) => {
    const current = data.sub_activity_ids;
    if (current.includes(subId)) {
      setData('sub_activity_ids', current.filter((id) => id !== subId));
    } else {
      setData('sub_activity_ids', [...current, subId]);
    }
  };

  return (
    <>
      <Head title={`Edit ${department.name}`} />

      <div className="p-6 space-y-6">
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-2xl font-bold text-gray-900">Edit Department</h1>
            <p className="mt-1 text-sm text-gray-500">Update department information</p>
          </div>
          <Button
            variant="outline"
            onClick={() => router.visit(route('admin.departments.index'))}
            className="flex items-center gap-2"
          >
            <ArrowLeft className="w-4 h-4" />
            Back to Departments
          </Button>
        </div>

        <form onSubmit={handleSubmit} className="space-y-6">
          <Card>
            <div className="px-6 py-4 border-b border-gray-200">
              <h2 className="text-lg font-semibold text-gray-900">Basic Information</h2>
            </div>
            <div className="p-6 space-y-4">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label htmlFor="code" required>Department Code</Label>
                  <Input
                    id="code"
                    type="text"
                    value={data.code}
                    onChange={(e) => setData('code', e.target.value.toUpperCase())}
                    placeholder="e.g., IT, HR, FIN"
                    maxLength={10}
                    required
                    error={errors?.code}
                  />
                  {errors?.code && <p className="text-sm text-red-600">{errors.code}</p>}
                </div>

                <div className="space-y-2">
                  <Label htmlFor="name" required>Department Name</Label>
                  <Input
                    id="name"
                    type="text"
                    value={data.name}
                    onChange={(e) => setData('name', e.target.value)}
                    placeholder="e.g., Information Technology"
                    required
                    error={errors?.name}
                  />
                  {errors?.name && <p className="text-sm text-red-600">{errors.name}</p>}
                </div>

                <div className="space-y-2">
                  <Label htmlFor="business_unit_id" required>Business Unit</Label>
                  <select
                    id="business_unit_id"
                    value={data.business_unit_id}
                    onChange={(e) => setData('business_unit_id', parseInt(e.target.value))}
                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                    required
                  >
                    <option value="">Select Business Unit</option>
                    {businessUnits.map((bu) => (
                      <option key={bu.id} value={bu.id}>{bu.name}</option>
                    ))}
                  </select>
                  {errors?.business_unit_id && (
                    <p className="text-sm text-red-600">{errors.business_unit_id}</p>
                  )}
                </div>

                <div className="space-y-2">
                  <Label htmlFor="head_id">Department Head</Label>
                  <select
                    id="head_id"
                    value={data.head_id || ''}
                    onChange={(e) => setData('head_id', e.target.value ? parseInt(e.target.value) : undefined)}
                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                  >
                    <option value="">No Head Assigned</option>
                    {users.map((user) => (
                      <option key={user.id} value={user.id}>{user.name}</option>
                    ))}
                  </select>
                </div>
              </div>

              <div className="flex items-center gap-2">
                <input
                  type="checkbox"
                  id="is_active"
                  checked={data.is_active}
                  onChange={(e) => setData('is_active', e.target.checked)}
                  className="h-4 w-4 text-primary border-gray-300 rounded focus:ring-primary"
                />
                <Label htmlFor="is_active" className="cursor-pointer">Active</Label>
              </div>
            </div>
          </Card>

          <Card>
            <div className="px-6 py-4 border-b border-gray-200">
              <h2 className="text-lg font-semibold text-gray-900">Sub Activities</h2>
              <p className="text-sm text-gray-500 mt-1">
                Pilih sub activities yang relevan untuk department ini
              </p>
            </div>
            <div className="p-6 space-y-4">
              <div className="relative">
                <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                <Input
                  type="text"
                  placeholder="Search sub activities..."
                  value={subActivitySearch}
                  onChange={(e) => setSubActivitySearch(e.target.value)}
                  className="pl-10"
                />
              </div>

              {data.sub_activity_ids.length > 0 && (
                <p className="text-sm text-primary font-medium">
                  {data.sub_activity_ids.length} sub activity(s) selected
                </p>
              )}

              <div className="space-y-4 max-h-96 overflow-y-auto">
                {Object.entries(groupedSubActivities).map(([typeName, subs]) => (
                  <div key={typeName} className="space-y-2">
                    <h4 className="text-sm font-medium text-gray-700 sticky top-0 bg-white py-1">
                      {typeName}
                    </h4>
                    <div className="space-y-1 pl-2">
                      {subs.map((sub) => {
                        const isSelected = data.sub_activity_ids.includes(sub.id);
                        return (
                          <button
                            key={sub.id}
                            type="button"
                            onClick={() => toggleSubActivity(sub.id)}
                            className={`w-full flex items-center gap-3 p-2 rounded-lg text-left transition-all ${
                              isSelected
                                ? 'bg-primary border border-primary'
                                : 'hover:bg-gray-50 border border-transparent'
                            }`}
                          >
                            <div
                              className="w-3 h-3 rounded-full flex-shrink-0"
                              style={{ backgroundColor: sub.activity_type?.color || '#6B7280' }}
                            />
                            <span className={`text-sm flex-1 ${isSelected ? 'text-primary font-medium' : 'text-gray-700'}`}>
                              {sub.name}
                            </span>
                            {isSelected && <Check className="w-4 h-4 text-primary" />}
                          </button>
                        );
                      })}
                    </div>
                  </div>
                ))}

                {Object.keys(groupedSubActivities).length === 0 && (
                  <div className="text-center py-8 text-gray-500">
                    {subActivities.length === 0
                      ? 'Belum ada activity types yang di-assign ke department ini. Assign activity types terlebih dahulu melalui menu Activity Types.'
                      : 'No sub activities found matching your search'}
                  </div>
                )}
              </div>
            </div>
          </Card>

          <Card>
            <div className="px-6 py-4 border-b border-gray-200">
              <h2 className="text-lg font-semibold text-gray-900">Purchasing Configuration</h2>
            </div>
            <div className="p-6 space-y-4">
              <div className="flex items-center gap-2">
                <input
                  type="checkbox"
                  id="is_purchasing_enabled"
                  checked={data.is_purchasing_enabled}
                  onChange={(e) => setData('is_purchasing_enabled', e.target.checked)}
                  className="h-4 w-4 text-primary border-gray-300 rounded focus:ring-primary"
                />
                <Label htmlFor="is_purchasing_enabled" className="cursor-pointer">
                  Enable Purchasing for this Department
                </Label>
              </div>

              <div className="flex items-center gap-2">
                <input
                  type="checkbox"
                  id="is_ga_stock_review_enabled"
                  checked={data.is_ga_stock_review_enabled}
                  onChange={(e) => setData('is_ga_stock_review_enabled', e.target.checked)}
                  className="h-4 w-4 text-primary border-gray-300 rounded focus:ring-primary"
                />
                <Label htmlFor="is_ga_stock_review_enabled" className="cursor-pointer">
                  Enable General Affair Stock Review for this Department
                </Label>
              </div>

              {data.is_purchasing_enabled && (
                <div className="space-y-2">
                  <Label htmlFor="purchasing_admin_id">Default Purchasing Admin</Label>
                  <select
                    id="purchasing_admin_id"
                    value={data.purchasing_admin_id || ''}
                    onChange={(e) => setData('purchasing_admin_id', e.target.value ? parseInt(e.target.value) : undefined)}
                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                  >
                    <option value="">No Admin Assigned</option>
                    {users.map((user) => (
                      <option key={user.id} value={user.id}>{user.name}</option>
                    ))}
                  </select>
                  <p className="text-sm text-gray-500">
                    This user will be assigned purchase request tasks by default
                  </p>
                </div>
              )}
            </div>
          </Card>

          <div className="flex items-center justify-end gap-4">
            <Button
              type="button"
              variant="outline"
              onClick={() => router.visit(route('admin.departments.index'))}
              disabled={processing}
            >
              Cancel
            </Button>
            <Button type="submit" disabled={processing} className="flex items-center gap-2">
              <Save className="w-4 h-4" />
              {processing ? 'Updating...' : 'Update Department'}
            </Button>
          </div>
        </form>
      </div>
    </>
  );
}

export default Edit;
