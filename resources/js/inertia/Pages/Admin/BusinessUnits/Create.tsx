import { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select } from '@/components/ui/select';
import { FileUpload } from '@/components/admin/FileUpload';
import { SelectOption } from '@/types/admin';
import { toast } from 'sonner';
import { z } from 'zod';
import { zodResolver } from '@hookform/resolvers/zod';
import { useForm as useReactHookForm } from 'react-hook-form';

interface CreateProps {
  parentBusinessUnits: SelectOption[];
  managers: SelectOption[];
}

const businessUnitFormSchema = z.object({
  name: z.string().min(1, 'Name is required'),
  code: z.string().min(2, 'Code must be at least 2 characters').max(10, 'Code must not exceed 10 characters'),
  description: z.string().optional(),
  address: z.string().optional(),
  phone: z.string().optional(),
  email: z.string().email('Invalid email address').optional().or(z.literal('')),
  parent_id: z.number().nullable().optional(),
  manager_id: z.number().nullable().optional(),
  is_active: z.boolean(),
});

type BusinessUnitFormData = z.infer<typeof businessUnitFormSchema>;

export default function Create({ parentBusinessUnits, managers }: CreateProps) {
  const [logoFile, setLogoFile] = useState<File | null>(null);
  const [logoPreview, setLogoPreview] = useState<string>('');
  const [isSubmitting, setIsSubmitting] = useState(false);

  const {
    register,
    handleSubmit,
    formState: { errors },
    setValue,
    watch,
  } = useReactHookForm<BusinessUnitFormData>({
    resolver: zodResolver(businessUnitFormSchema),
    defaultValues: {
      is_active: true,
      parent_id: null,
      manager_id: null,
    },
  });

  const handleFileSelect = (file: File | null) => {
    if (!file) {
      setLogoFile(null);
      setLogoPreview('');
      return;
    }
    setLogoFile(file);
    const reader = new FileReader();
    reader.onloadend = () => {
      setLogoPreview(reader.result as string);
    };
    reader.readAsDataURL(file);
  };

  const onSubmit = (data: BusinessUnitFormData) => {
    setIsSubmitting(true);

    const formData = new FormData();
    formData.append('name', data.name);
    formData.append('code', data.code.toUpperCase());
    if (data.description) formData.append('description', data.description);
    if (data.address) formData.append('address', data.address);
    if (data.phone) formData.append('phone', data.phone);
    if (data.email) formData.append('email', data.email);
    if (data.parent_id) formData.append('parent_id', data.parent_id.toString());
    if (data.manager_id) formData.append('manager_id', data.manager_id.toString());
    formData.append('is_active', data.is_active ? '1' : '0');
    if (logoFile) formData.append('logo', logoFile);

    router.post(route('admin.business-units.store'), formData, {
      onSuccess: () => {
        toast.success('Business unit created successfully');
      },
      onError: (errors) => {
        toast.error('Failed to create business unit');
        setIsSubmitting(false);
      },
      onFinish: () => {
        setIsSubmitting(false);
      },
    });
  };

  return (
    <>
      <Head title="Create Business Unit" />

      <div className="p-6 space-y-6">
        {/* Header */}
        <div>
          <h1 className="text-2xl font-bold text-gray-900">Create Business Unit</h1>
          <p className="mt-1 text-sm text-gray-600">
            Add a new business unit to the organization
          </p>
        </div>

        {/* Form Card */}
        <div className="bg-white rounded-xl border border-gray-100 overflow-hidden">
          <form onSubmit={handleSubmit(onSubmit)} className="p-6 space-y-6">
            {/* Basic Information */}
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <Label htmlFor="name" required>
                  Business Unit Name
                </Label>
                <Input
                  id="name"
                  {...register('name')}
                  error={errors.name?.message}
                  placeholder="e.g., Werkudara Nirwana Sakti"
                />
              </div>

              <div>
                <Label htmlFor="code" required>
                  Code
                </Label>
                <Input
                  id="code"
                  {...register('code')}
                  error={errors.code?.message}
                  placeholder="e.g., WNS"
                  className="uppercase"
                />
              </div>
            </div>

            {/* Logo Upload */}
            <div>
              <Label>Logo</Label>
              <FileUpload
                label="Upload Logo"
                accept="image/jpeg,image/png,image/jpg,image/svg+xml"
                maxSize={2 * 1024 * 1024}
                onFileSelect={handleFileSelect}
                preview={logoPreview}
              />
              <p className="mt-1 text-sm text-gray-500">
                Accepted formats: JPG, PNG, SVG. Maximum size: 2MB
              </p>
            </div>

            {/* Description */}
            <div>
              <Label htmlFor="description">Description</Label>
              <textarea
                id="description"
                {...register('description')}
                rows={3}
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                placeholder="Brief description of the business unit"
              />
              {errors.description && (
                <p className="mt-1 text-sm text-red-600">{errors.description.message}</p>
              )}
            </div>

            {/* Contact Information */}
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <Label htmlFor="phone">Phone</Label>
                <Input
                  id="phone"
                  {...register('phone')}
                  error={errors.phone?.message}
                  placeholder="+62 21 1234567"
                />
              </div>

              <div>
                <Label htmlFor="email">Email</Label>
                <Input
                  id="email"
                  type="email"
                  {...register('email')}
                  error={errors.email?.message}
                  placeholder="contact@example.com"
                />
              </div>
            </div>

            {/* Address */}
            <div>
              <Label htmlFor="address">Address</Label>
              <textarea
                id="address"
                {...register('address')}
                rows={2}
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                placeholder="Full address of the business unit"
              />
              {errors.address && (
                <p className="mt-1 text-sm text-red-600">{errors.address.message}</p>
              )}
            </div>

            {/* Hierarchy and Management */}
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <Label htmlFor="parent_id">Parent Business Unit</Label>
                <Select
                  value={watch('parent_id')?.toString() || ''}
                  onChange={(value) => setValue('parent_id', value ? parseInt(value.toString()) : null)}
                  options={[
                    { value: '', label: 'None (Top Level)' },
                    ...parentBusinessUnits,
                  ]}
                />
                <p className="mt-1 text-sm text-gray-500">
                  Select a parent for hierarchical structure
                </p>
              </div>

              <div>
                <Label htmlFor="manager_id">Manager</Label>
                <Select
                  value={watch('manager_id')?.toString() || ''}
                  onChange={(value) => setValue('manager_id', value ? parseInt(value.toString()) : null)}
                  options={[
                    { value: '', label: 'No Manager' },
                    ...managers,
                  ]}
                />
              </div>
            </div>

            {/* Status */}
            <div className="flex items-center gap-2">
              <input
                type="checkbox"
                id="is_active"
                {...register('is_active')}
                className="h-4 w-4 text-primary border-gray-300 rounded focus:ring-primary"
              />
              <Label htmlFor="is_active" className="mb-0">
                Active
              </Label>
            </div>

            {/* Actions */}
            <div className="flex items-center justify-end gap-3 pt-6 border-t border-gray-100">
              <Button
                type="button"
                variant="outline"
                onClick={() => router.visit(route('admin.business-units.index'))}
                disabled={isSubmitting}
              >
                Cancel
              </Button>
              <Button type="submit" disabled={isSubmitting}>
                {isSubmitting ? 'Creating...' : 'Create Business Unit'}
              </Button>
            </div>
          </form>
        </div>
      </div>
    </>
  );
}
