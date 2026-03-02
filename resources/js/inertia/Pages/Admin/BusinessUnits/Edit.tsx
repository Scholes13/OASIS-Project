import { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select } from '@/components/ui/select';
import { FileUpload } from '@/components/admin/FileUpload';
import { BusinessUnitWithStats, SelectOption } from '@/types/admin';
import { toast } from 'sonner';
import { z } from 'zod';
import { zodResolver } from '@hookform/resolvers/zod';
import { useForm as useReactHookForm } from 'react-hook-form';
import { X } from 'lucide-react';

interface EditProps {
  businessUnit: BusinessUnitWithStats & { logo_url?: string };
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
  remove_logo: z.boolean().optional(),
});

type BusinessUnitFormData = z.infer<typeof businessUnitFormSchema>;

export default function Edit({ businessUnit, parentBusinessUnits, managers }: EditProps) {
  const [logoFile, setLogoFile] = useState<File | null>(null);
  const [logoPreview, setLogoPreview] = useState<string>(businessUnit.logo_url || '');
  const [removeLogo, setRemoveLogo] = useState(false);
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
      name: businessUnit.name,
      code: businessUnit.code,
      description: businessUnit.description || '',
      address: businessUnit.address || '',
      phone: businessUnit.phone || '',
      email: businessUnit.email || '',
      parent_id: businessUnit.parent_id || null,
      manager_id: businessUnit.manager_id || null,
      is_active: businessUnit.is_active,
      remove_logo: false,
    },
  });

  const handleFileSelect = (file: File | null) => {
    if (!file) {
      setLogoFile(null);
      setLogoPreview('');
      return;
    }
    setLogoFile(file);
    setRemoveLogo(false);
    const reader = new FileReader();
    reader.onloadend = () => {
      setLogoPreview(reader.result as string);
    };
    reader.readAsDataURL(file);
  };

  const handleRemoveLogo = () => {
    setRemoveLogo(true);
    setLogoFile(null);
    setLogoPreview('');
    setValue('remove_logo', true);
  };

  const onSubmit = (data: BusinessUnitFormData) => {
    setIsSubmitting(true);

    const formData = new FormData();
    formData.append('_method', 'PUT');
    formData.append('name', data.name);
    formData.append('code', data.code.toUpperCase());
    if (data.description) formData.append('description', data.description);
    if (data.address) formData.append('address', data.address);
    if (data.phone) formData.append('phone', data.phone);
    if (data.email) formData.append('email', data.email);
    if (data.parent_id) formData.append('parent_id', data.parent_id.toString());
    if (data.manager_id) formData.append('manager_id', data.manager_id.toString());
    formData.append('is_active', data.is_active ? '1' : '0');
    if (removeLogo) formData.append('remove_logo', '1');
    if (logoFile) formData.append('logo', logoFile);

    router.post(route('admin.business-units.update', { business_unit: businessUnit.id }), formData, {
      onSuccess: () => {
        toast.success('Business unit updated successfully');
      },
      onError: (errors) => {
        toast.error('Failed to update business unit');
        setIsSubmitting(false);
      },
      onFinish: () => {
        setIsSubmitting(false);
      },
    });
  };

  return (
    <>
      <Head title={`Edit ${businessUnit.name}`} />

      <div className="p-6 max-w-3xl">
        <div className="bg-white rounded-xl border border-gray-100 overflow-hidden">
          <div className="px-6 py-4 border-b border-gray-100">
            <h2 className="text-lg font-semibold text-gray-900">Edit Business Unit</h2>
            <p className="mt-1 text-sm text-gray-600">
              Update business unit information
            </p>
          </div>

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
              {logoPreview && !removeLogo ? (
                <div className="relative inline-block">
                  <img
                    src={logoPreview}
                    alt="Logo preview"
                    className="w-32 h-32 object-cover rounded-lg border border-gray-200"
                  />
                  <button
                    type="button"
                    onClick={handleRemoveLogo}
                    className="absolute -top-2 -right-2 p-1 bg-red-600 text-white rounded-full hover:bg-red-700 transition-colors"
                  >
                    <X className="w-4 h-4" />
                  </button>
                </div>
              ) : (
                <FileUpload
                  label="Upload Logo"
                  accept="image/jpeg,image/png,image/jpg,image/svg+xml"
                  maxSize={2 * 1024 * 1024}
                  onFileSelect={handleFileSelect}
                  preview={logoPreview}
                />
              )}
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
                className="w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary"
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
                onClick={() => router.visit(route('admin.business-units.show', { business_unit: businessUnit.id }))}
                disabled={isSubmitting}
              >
                Cancel
              </Button>
              <Button type="submit" disabled={isSubmitting}>
                {isSubmitting ? 'Updating...' : 'Update Business Unit'}
              </Button>
            </div>
          </form>
        </div>
      </div>
    </>
  );
}
