import { useState, useEffect } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select } from '@/components/ui/select';
import { Card } from '@/components/ui/Card';
import { toast } from 'sonner';
import { ArrowLeft, Plus, Trash2 } from 'lucide-react';
import { User } from '@/types/admin';

interface BusinessUnit {
  id: number;
  name: string;
  code: string;
  departments: Department[];
}

interface Department {
  id: number;
  name: string;
  positions: Position[];
}

interface Position {
  id: number;
  name: string;
}

interface UserOption {
  id: number;
  name: string;
  email: string;
}

interface EditProps {
  user: User;
  businessUnits: BusinessUnit[];
  users: UserOption[];
}

const userFormSchema = z.object({
  name: z.string().min(1, 'Name is required'),
  email: z.string().min(1, 'Email is required').email('Invalid email address'),
  phone_number: z.string().optional(),
  password: z.string().min(8, 'Password must be at least 8 characters').optional().or(z.literal('')),
  password_confirmation: z.string().optional().or(z.literal('')),
  global_role: z.enum(['super_admin', 'user']),
  supervisor_id: z.number().nullable().optional(),
  is_active: z.boolean(),
  business_units: z.array(z.object({
    business_unit_id: z.number(),
    department_id: z.number(),
    position_id: z.number(),
  })).min(1, 'At least one business unit assignment is required'),
  primary_business_unit: z.number().min(0),
}).refine((data) => {
  if (data.password && data.password_confirmation) {
    return data.password === data.password_confirmation;
  }
  return true;
}, {
  message: "Passwords don't match",
  path: ['password_confirmation'],
});

type UserFormData = z.infer<typeof userFormSchema>;

export default function Edit({ user, businessUnits, users }: EditProps) {
  const [assignments, setAssignments] = useState<Array<{
    business_unit_id: number;
    department_id: number;
    position_id: number;
    departments: Department[];
    positions: Position[];
  }>>([]);

  const [primaryIndex, setPrimaryIndex] = useState(0);
  const [isSubmitting, setIsSubmitting] = useState(false);

  const {
    register,
    handleSubmit,
    formState: { errors },
    setValue,
    watch,
  } = useForm<UserFormData>({
    resolver: zodResolver(userFormSchema),
    defaultValues: {
      name: user.name,
      email: user.email,
      phone_number: user.phone_number || '',
      global_role: user.global_role as 'super_admin' | 'user',
      supervisor_id: user.supervisor_id,
      is_active: user.is_active,
      business_units: [],
      primary_business_unit: 0,
    },
  });

  // Initialize assignments from user data
  useEffect(() => {
    if (user.business_units && user.business_units.length > 0) {
      const initialAssignments = user.business_units.map((bu, index) => {
        const businessUnit = businessUnits.find(b => b.id === bu.business_unit.id);
        const departments = businessUnit?.departments || [];
        const department = departments.find(d => d.id === bu.department.id);
        const positions = department?.positions || [];

        if (bu.is_primary) {
          setPrimaryIndex(index);
        }

        return {
          business_unit_id: bu.business_unit.id,
          department_id: bu.department.id,
          position_id: bu.position.id,
          departments,
          positions,
        };
      });
      setAssignments(initialAssignments);
      
      // Update form value for validation
      updateBusinessUnitsFormValue(initialAssignments);
    }
  }, [user, businessUnits]);

  const loadDepartments = async (businessUnitId: number, index: number) => {
    try {
      const bu = businessUnits.find(b => b.id === businessUnitId);
      if (bu) {
        const newAssignments = [...assignments];
        newAssignments[index].departments = bu.departments;
        newAssignments[index].business_unit_id = businessUnitId;
        newAssignments[index].department_id = 0;
        newAssignments[index].position_id = 0;
        newAssignments[index].positions = [];
        setAssignments(newAssignments);
        
        // Update form value for validation
        updateBusinessUnitsFormValue(newAssignments);
      }
    } catch (error) {
      toast.error('Failed to load departments');
    }
  };

  const loadPositions = async (departmentId: number, index: number) => {
    try {
      const dept = assignments[index].departments.find(d => d.id === departmentId);
      if (dept) {
        const newAssignments = [...assignments];
        newAssignments[index].positions = dept.positions;
        newAssignments[index].department_id = departmentId;
        newAssignments[index].position_id = 0;
        setAssignments(newAssignments);
        
        // Update form value for validation
        updateBusinessUnitsFormValue(newAssignments);
      }
    } catch (error) {
      toast.error('Failed to load positions');
    }
  };

  const updatePositionId = (positionId: number, index: number) => {
    const newAssignments = [...assignments];
    newAssignments[index].position_id = positionId;
    setAssignments(newAssignments);
    
    // Update form value for validation
    updateBusinessUnitsFormValue(newAssignments);
  };

  const updateBusinessUnitsFormValue = (currentAssignments: typeof assignments) => {
    // Filter out incomplete assignments (where any ID is 0)
    const validAssignments = currentAssignments.filter(a => 
      a.business_unit_id > 0 && a.department_id > 0 && a.position_id > 0
    );
    
    setValue('business_units', validAssignments.map(a => ({
      business_unit_id: a.business_unit_id,
      department_id: a.department_id,
      position_id: a.position_id,
    })), { shouldValidate: true });
  };

  const addAssignment = () => {
    setAssignments([...assignments, {
      business_unit_id: 0,
      department_id: 0,
      position_id: 0,
      departments: [],
      positions: [],
    }]);
  };

  const removeAssignment = (index: number) => {
    if (assignments.length === 1) {
      toast.error('At least one business unit assignment is required');
      return;
    }
    const newAssignments = assignments.filter((_, i) => i !== index);
    setAssignments(newAssignments);
    if (primaryIndex === index) {
      setPrimaryIndex(0);
    } else if (primaryIndex > index) {
      setPrimaryIndex(primaryIndex - 1);
    }
  };

  const onSubmit = (data: UserFormData) => {
    setIsSubmitting(true);

    // Build business units array from assignments
    const businessUnitsData = assignments.map(a => ({
      business_unit_id: a.business_unit_id,
      department_id: a.department_id,
      position_id: a.position_id,
    }));

    const formData: any = {
      ...data,
      business_units: businessUnitsData,
      primary_business_unit: primaryIndex,
    };

    // Remove password fields if empty
    if (!formData.password) {
      delete formData.password;
      delete formData.password_confirmation;
    }

    router.put(route('admin.users.update', { user: user.id }), formData, {
      onSuccess: () => {
        toast.success('User updated successfully');
      },
      onError: (errors) => {
        toast.error('Failed to update user');
        console.error(errors);
      },
      onFinish: () => {
        setIsSubmitting(false);
      },
    });
  };

  return (
    <>
      <Head title={`Edit User - ${user.name}`} />

      <div className="p-6 space-y-6">
        {/* Header */}
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-2xl font-bold text-gray-900">Edit User</h1>
            <p className="mt-1 text-sm text-gray-600">
              Update user information and assignments
            </p>
          </div>
          <Link href={route('admin.users.index')}>
            <Button variant="outline">
              <ArrowLeft className="w-4 h-4 mr-2" />
              Back to Users
            </Button>
          </Link>
        </div>

        {/* Form */}
        <form onSubmit={handleSubmit(onSubmit)} className="space-y-6">
          {/* Basic Information */}
          <Card className="p-6">
            <h2 className="text-lg font-semibold text-gray-900 mb-4">
              Basic Information
            </h2>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <Label htmlFor="name">Name *</Label>
                <Input
                  id="name"
                  {...register('name')}
                  className={errors.name ? 'border-red-500' : ''}
                />
                {errors.name && (
                  <p className="mt-1 text-sm text-red-600">{errors.name.message}</p>
                )}
              </div>

              <div>
                <Label htmlFor="email">Email *</Label>
                <Input
                  id="email"
                  type="email"
                  {...register('email')}
                  className={errors.email ? 'border-red-500' : ''}
                />
                {errors.email && (
                  <p className="mt-1 text-sm text-red-600">{errors.email.message}</p>
                )}
              </div>

              <div>
                <Label htmlFor="phone_number">Phone Number</Label>
                <Input
                  id="phone_number"
                  {...register('phone_number')}
                />
              </div>

              <div>
                <Select
                  label="Role *"
                  value={watch('global_role') || user.global_role}
                  onChange={(value) => setValue('global_role', value as 'super_admin' | 'user')}
                  options={[
                    { value: 'user', label: 'User' },
                    { value: 'super_admin', label: 'Super Admin' },
                  ]}
                  placeholder="Select role"
                  error={errors.global_role?.message}
                  required
                />
              </div>

              <div>
                <Label htmlFor="password">Password (leave blank to keep current)</Label>
                <Input
                  id="password"
                  type="password"
                  {...register('password')}
                  className={errors.password ? 'border-red-500' : ''}
                />
                {errors.password && (
                  <p className="mt-1 text-sm text-red-600">{errors.password.message}</p>
                )}
              </div>

              <div>
                <Label htmlFor="password_confirmation">Confirm Password</Label>
                <Input
                  id="password_confirmation"
                  type="password"
                  {...register('password_confirmation')}
                  className={errors.password_confirmation ? 'border-red-500' : ''}
                />
                {errors.password_confirmation && (
                  <p className="mt-1 text-sm text-red-600">{errors.password_confirmation.message}</p>
                )}
              </div>

              <div>
                <Select
                  label="Supervisor"
                  value={watch('supervisor_id') ? String(watch('supervisor_id')) : (user.supervisor_id ? String(user.supervisor_id) : '')}
                  onChange={(value) => setValue('supervisor_id', value ? parseInt(String(value)) : null)}
                  options={[
                    { value: '', label: 'No Supervisor' },
                    ...users.map((u) => ({
                      value: u.id,
                      label: `${u.name} (${u.email})`,
                    })),
                  ]}
                  placeholder="Select supervisor (optional)"
                />
              </div>

              <div className="flex items-center space-x-2">
                <input
                  type="checkbox"
                  id="is_active"
                  {...register('is_active')}
                  className="rounded border-gray-300"
                />
                <Label htmlFor="is_active" className="font-normal">
                  Active
                </Label>
              </div>
            </div>
          </Card>

          {/* Business Unit Assignments */}
          <Card className="p-6">
            <div className="flex items-center justify-between mb-4">
              <h2 className="text-lg font-semibold text-gray-900">
                Business Unit Assignments *
              </h2>
              <Button type="button" variant="outline" size="sm" onClick={addAssignment}>
                <Plus className="w-4 h-4 mr-2" />
                Add Assignment
              </Button>
            </div>

            <div className="space-y-4">
              {assignments.map((assignment, index) => (
                <div key={index} className="border border-gray-200 rounded-lg p-4">
                  <div className="flex items-start justify-between mb-4">
                    <div className="flex items-center space-x-2">
                      <input
                        type="radio"
                        name="primary_business_unit"
                        checked={primaryIndex === index}
                        onChange={() => setPrimaryIndex(index)}
                        className="mt-1"
                      />
                      <Label className="font-medium">
                        Assignment {index + 1}
                        {primaryIndex === index && (
                          <span className="ml-2 text-xs text-primary">(Primary)</span>
                        )}
                      </Label>
                    </div>
                    {assignments.length > 1 && (
                      <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        onClick={() => removeAssignment(index)}
                      >
                        <Trash2 className="w-4 h-4 text-red-600" />
                      </Button>
                    )}
                  </div>

                  <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                      <Select
                        label="Business Unit *"
                        value={assignment.business_unit_id || ''}
                        onChange={(value) => loadDepartments(parseInt(String(value)), index)}
                        options={businessUnits.map((bu) => ({
                          value: bu.id,
                          label: `${bu.name} (${bu.code})`,
                        }))}
                        placeholder="Select business unit"
                      />
                    </div>

                    <div>
                      <Select
                        label="Department *"
                        value={assignment.department_id || ''}
                        onChange={(value) => loadPositions(parseInt(String(value)), index)}
                        options={assignment.departments.map((dept) => ({
                          value: dept.id,
                          label: dept.name,
                        }))}
                        placeholder="Select department"
                        disabled={!assignment.business_unit_id}
                      />
                    </div>

                    <div>
                      <Select
                        label="Position *"
                        value={assignment.position_id || ''}
                        onChange={(value) => updatePositionId(parseInt(String(value)), index)}
                        options={assignment.positions.map((pos) => ({
                          value: pos.id,
                          label: pos.name,
                        }))}
                        placeholder="Select position"
                        disabled={!assignment.department_id}
                      />
                    </div>
                  </div>
                </div>
              ))}
            </div>
            {errors.business_units && (
              <p className="mt-2 text-sm text-red-600">{errors.business_units.message}</p>
            )}
          </Card>

          {/* Submit Buttons */}
          <div className="flex items-center justify-end space-x-4">
            <Link href={route('admin.users.index')}>
              <Button type="button" variant="outline">
                Cancel
              </Button>
            </Link>
            <Button type="submit" disabled={isSubmitting}>
              {isSubmitting ? 'Updating...' : 'Update User'}
            </Button>
          </div>
        </form>
      </div>
    </>
  );
}


