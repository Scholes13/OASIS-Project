import { Head, router, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card } from '@/components/ui/Card';
import { ArrowLeft, Plus, Trash2, Save } from 'lucide-react';
import type { DepartmentFormProps, DepartmentFormData } from '@/types/admin';
import { toast } from 'sonner';
import { FormEventHandler, useState } from 'react';

interface PositionInput {
  id?: number;
  name: string;
  code: string;
  access_level: 'staff' | 'supervisor' | 'manager' | 'head';
}

export default function Create({ businessUnits, users, errors }: DepartmentFormProps) {
  const { data, setData, post, processing } = useForm<DepartmentFormData>({
    code: '',
    name: '',
    business_unit_id: 0,
    head_id: undefined,
    is_active: true,
    sort_order: 0,
    is_purchasing_enabled: false,
    purchasing_admin_id: undefined,
  });

  const [positions, setPositions] = useState<PositionInput[]>([
    { name: '', code: '', access_level: 'staff' },
  ]);

  const handleSubmit: FormEventHandler = (e) => {
    e.preventDefault();

    // Validate positions
    const validPositions = positions.filter(p => p.name.trim() && p.code.trim());
    
    if (validPositions.length === 0) {
      toast.error('Please add at least one position');
      return;
    }

    // Update data with positions before submitting
    setData('positions' as keyof DepartmentFormData, validPositions as any);

    post(route('admin.departments.store'), {
      onSuccess: () => {
        toast.success('Department created successfully');
      },
      onError: (errors) => {
        const errorMessage = typeof errors === 'object' && errors !== null && 'message' in errors 
          ? (errors as { message: string }).message 
          : 'Failed to create department';
        toast.error(errorMessage);
      },
    });
  };

  const addPosition = () => {
    setPositions([...positions, { name: '', code: '', access_level: 'staff' }]);
  };

  const removePosition = (index: number) => {
    if (positions.length > 1) {
      setPositions(positions.filter((_, i) => i !== index));
    }
  };

  const updatePosition = (index: number, field: keyof PositionInput, value: string) => {
    const updated = [...positions];
    updated[index] = { ...updated[index], [field]: value };
    setPositions(updated);
  };

  return (
    <>
      <Head title="Create Department" />

      <div className="p-6 space-y-6">
        {/* Header */}
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-2xl font-bold text-gray-900">Create Department</h1>
            <p className="mt-1 text-sm text-gray-500">
              Add a new department to your organization
            </p>
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

        {/* Form */}
        <form onSubmit={handleSubmit} className="space-y-6">
          {/* Basic Information */}
          <Card>
            <div className="px-6 py-4 border-b border-gray-200">
              <h2 className="text-lg font-semibold text-gray-900">Basic Information</h2>
            </div>
            <div className="p-6 space-y-4">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                {/* Code */}
                <div className="space-y-2">
                  <Label htmlFor="code" required>
                    Department Code
                  </Label>
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
                  {errors?.code && (
                    <p className="text-sm text-red-600">{errors.code}</p>
                  )}
                </div>

                {/* Name */}
                <div className="space-y-2">
                  <Label htmlFor="name" required>
                    Department Name
                  </Label>
                  <Input
                    id="name"
                    type="text"
                    value={data.name}
                    onChange={(e) => setData('name', e.target.value)}
                    placeholder="e.g., Information Technology"
                    required
                    error={errors?.name}
                  />
                  {errors?.name && (
                    <p className="text-sm text-red-600">{errors.name}</p>
                  )}
                </div>

                {/* Business Unit */}
                <div className="space-y-2">
                  <Label htmlFor="business_unit_id" required>
                    Business Unit
                  </Label>
                  <select
                    id="business_unit_id"
                    value={data.business_unit_id}
                    onChange={(e) => setData('business_unit_id', parseInt(e.target.value))}
                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                    required
                  >
                    <option value="">Select Business Unit</option>
                    {businessUnits.map((bu) => (
                      <option key={bu.id} value={bu.id}>
                        {bu.name}
                      </option>
                    ))}
                  </select>
                  {errors?.business_unit_id && (
                    <p className="text-sm text-red-600">{errors.business_unit_id}</p>
                  )}
                </div>

                {/* Department Head */}
                <div className="space-y-2">
                  <Label htmlFor="head_id">Department Head</Label>
                  <select
                    id="head_id"
                    value={data.head_id || ''}
                    onChange={(e) => setData('head_id', e.target.value ? parseInt(e.target.value) : undefined)}
                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                  >
                    <option value="">No Head Assigned</option>
                    {users.map((user) => (
                      <option key={user.id} value={user.id}>
                        {user.name}
                      </option>
                    ))}
                  </select>
                </div>
              </div>

              {/* Status */}
              <div className="flex items-center gap-2">
                <input
                  type="checkbox"
                  id="is_active"
                  checked={data.is_active}
                  onChange={(e) => setData('is_active', e.target.checked)}
                  className="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
                />
                <Label htmlFor="is_active" className="cursor-pointer">
                  Active
                </Label>
              </div>
            </div>
          </Card>

          {/* Positions */}
          <Card>
            <div className="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
              <div>
                <h2 className="text-lg font-semibold text-gray-900">Positions</h2>
                <p className="text-sm text-gray-500 mt-1">
                  Define positions within this department
                </p>
              </div>
              <Button
                type="button"
                variant="outline"
                size="sm"
                onClick={addPosition}
                className="flex items-center gap-2"
              >
                <Plus className="w-4 h-4" />
                Add Position
              </Button>
            </div>
            <div className="p-6 space-y-4">
              {positions.map((position, index) => (
                <div key={index} className="flex items-start gap-4 p-4 bg-gray-50 rounded-lg">
                  <div className="flex-1 grid grid-cols-1 md:grid-cols-3 gap-4">
                    {/* Position Code */}
                    <div className="space-y-2">
                      <Label htmlFor={`position-code-${index}`}>Code</Label>
                      <Input
                        id={`position-code-${index}`}
                        type="text"
                        value={position.code}
                        onChange={(e) => updatePosition(index, 'code', e.target.value.toUpperCase())}
                        placeholder="e.g., MGR, STAFF"
                        maxLength={10}
                      />
                    </div>

                    {/* Position Name */}
                    <div className="space-y-2">
                      <Label htmlFor={`position-name-${index}`}>Name</Label>
                      <Input
                        id={`position-name-${index}`}
                        type="text"
                        value={position.name}
                        onChange={(e) => updatePosition(index, 'name', e.target.value)}
                        placeholder="e.g., Manager, Staff"
                      />
                    </div>

                    {/* Access Level */}
                    <div className="space-y-2">
                      <Label htmlFor={`position-access-${index}`}>Access Level</Label>
                      <select
                        id={`position-access-${index}`}
                        value={position.access_level}
                        onChange={(e) => updatePosition(index, 'access_level', e.target.value)}
                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                      >
                        <option value="staff">Staff</option>
                        <option value="supervisor">Supervisor</option>
                        <option value="manager">Manager</option>
                        <option value="head">Head</option>
                      </select>
                    </div>
                  </div>

                  {/* Remove Button */}
                  {positions.length > 1 && (
                    <Button
                      type="button"
                      variant="ghost"
                      size="sm"
                      onClick={() => removePosition(index)}
                      className="text-red-600 hover:text-red-700 hover:bg-red-50 mt-7"
                    >
                      <Trash2 className="w-4 h-4" />
                    </Button>
                  )}
                </div>
              ))}
            </div>
          </Card>

          {/* Purchasing Configuration */}
          <Card>
            <div className="px-6 py-4 border-b border-gray-200">
              <h2 className="text-lg font-semibold text-gray-900">Purchasing Configuration</h2>
            </div>
            <div className="p-6 space-y-4">
              {/* Enable Purchasing */}
              <div className="flex items-center gap-2">
                <input
                  type="checkbox"
                  id="is_purchasing_enabled"
                  checked={data.is_purchasing_enabled}
                  onChange={(e) => setData('is_purchasing_enabled', e.target.checked)}
                  className="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
                />
                <Label htmlFor="is_purchasing_enabled" className="cursor-pointer">
                  Enable Purchasing for this Department
                </Label>
              </div>

              {/* Purchasing Admin */}
              {data.is_purchasing_enabled && (
                <div className="space-y-2">
                  <Label htmlFor="purchasing_admin_id">Default Purchasing Admin</Label>
                  <select
                    id="purchasing_admin_id"
                    value={data.purchasing_admin_id || ''}
                    onChange={(e) => setData('purchasing_admin_id', e.target.value ? parseInt(e.target.value) : undefined)}
                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                  >
                    <option value="">No Admin Assigned</option>
                    {users.map((user) => (
                      <option key={user.id} value={user.id}>
                        {user.name}
                      </option>
                    ))}
                  </select>
                  <p className="text-sm text-gray-500">
                    This user will be assigned purchase request tasks by default
                  </p>
                </div>
              )}
            </div>
          </Card>

          {/* Actions */}
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
              {processing ? 'Creating...' : 'Create Department'}
            </Button>
          </div>
        </form>
      </div>
    </>
  );
}
