import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { motion, AnimatePresence } from 'framer-motion';
import { toast } from 'sonner';
import { Search, Plus, Edit2, Trash2, X, Check } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/Badge';
import { DataTable } from '@/components/admin/DataTable';
import type { PrCategory, PaginationData } from '@/types/admin';
import type { ColumnDef } from '@tanstack/react-table';

interface PrCategoriesIndexProps {
  categories: {
    data: PrCategory[];
    pagination: PaginationData;
  };
}

interface CategoryFormData {
  name: string;
  code: string;
  description: string;
  color: string;
  is_active: boolean;
  sort_order: number;
}

const COLORS = [
  { value: 'blue', label: 'Blue', class: 'bg-blue-100 text-blue-800' },
  { value: 'green', label: 'Green', class: 'bg-green-100 text-green-800' },
  { value: 'purple', label: 'Purple', class: 'bg-purple-100 text-purple-800' },
  { value: 'pink', label: 'Pink', class: 'bg-pink-100 text-pink-800' },
  { value: 'yellow', label: 'Yellow', class: 'bg-yellow-100 text-yellow-800' },
  { value: 'red', label: 'Red', class: 'bg-red-100 text-red-800' },
  { value: 'gray', label: 'Gray', class: 'bg-gray-100 text-gray-800' },
  { value: 'indigo', label: 'Indigo', class: 'bg-indigo-100 text-indigo-800' },
];

function Index({ categories }: PrCategoriesIndexProps) {
  const [search, setSearch] = useState('');
  const [isCreating, setIsCreating] = useState(false);
  const [editingId, setEditingId] = useState<number | null>(null);
  const [formData, setFormData] = useState<CategoryFormData>({
    name: '',
    code: '',
    description: '',
    color: 'blue',
    is_active: true,
    sort_order: 0,
  });
  const [errors, setErrors] = useState<Record<string, string>>({});

  const filteredData = categories.data.filter((category) =>
    category.name.toLowerCase().includes(search.toLowerCase()) ||
    category.code.toLowerCase().includes(search.toLowerCase())
  );

  const handleSearch = (value: string) => {
    setSearch(value);
  };

  const resetForm = () => {
    setFormData({
      name: '',
      code: '',
      description: '',
      color: 'blue',
      is_active: true,
      sort_order: 0,
    });
    setErrors({});
    setIsCreating(false);
    setEditingId(null);
  };

  const handleCreate = () => {
    setIsCreating(true);
    resetForm();
  };

  const handleEdit = (category: PrCategory) => {
    setEditingId(category.id);
    setFormData({
      name: category.name,
      code: category.code,
      description: category.description || '',
      color: category.color,
      is_active: category.is_active,
      sort_order: category.sort_order,
    });
    setErrors({});
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    setErrors({});

    if (editingId) {
      router.put(
        route('admin.pr-categories.update', { pr_category: editingId }),
        formData as unknown as Record<string, string | number | boolean>,
        {
          onSuccess: () => {
            toast.success('Category updated successfully');
            resetForm();
          },
          onError: (errors) => {
            setErrors(errors as Record<string, string>);
            toast.error('Failed to update category');
          },
        }
      );
    } else {
      router.post(route('admin.pr-categories.store'), formData as unknown as Record<string, string | number | boolean>, {
        onSuccess: () => {
          toast.success('Category created successfully');
          resetForm();
        },
        onError: (errors) => {
          setErrors(errors as Record<string, string>);
          toast.error('Failed to create category');
        },
      });
    }
  };

  const handleDelete = (category: PrCategory) => {
    if (category.usage_count && category.usage_count > 0) {
      toast.error(
        `Cannot delete category that is being used by ${category.usage_count} purchase request(s)`
      );
      return;
    }

    if (
      confirm(
        `Are you sure you want to delete "${category.name}"? This action cannot be undone.`
      )
    ) {
      router.delete(route('admin.pr-categories.destroy', { pr_category: category.id }), {
        onSuccess: () => {
          toast.success('Category deleted successfully');
        },
        onError: () => {
          toast.error('Failed to delete category');
        },
      });
    }
  };

  const getColorClass = (color: string) => {
    const colorObj = COLORS.find((c) => c.value === color);
    return colorObj?.class || 'bg-gray-100 text-gray-800';
  };

  const columns: ColumnDef<PrCategory>[] = [
    {
      accessorKey: 'sort_order',
      header: 'Order',
      cell: ({ row }) => (
        <span className="text-sm text-gray-500">{row.original.sort_order}</span>
      ),
    },
    {
      accessorKey: 'code',
      header: 'Code',
      cell: ({ row }) => (
        <Badge variant="default" className="font-mono">
          {row.original.code}
        </Badge>
      ),
    },
    {
      accessorKey: 'name',
      header: 'Name',
      cell: ({ row }) => (
        <span className="font-medium text-gray-900">{row.original.name}</span>
      ),
    },
    {
      accessorKey: 'description',
      header: 'Description',
      cell: ({ row }) => (
        <span className="text-sm text-gray-500 max-w-xs truncate block">
          {row.original.description || '-'}
        </span>
      ),
    },
    {
      accessorKey: 'color',
      header: 'Color',
      cell: ({ row }) => (
        <Badge className={getColorClass(row.original.color)}>
          {row.original.color.charAt(0).toUpperCase() + row.original.color.slice(1)}
        </Badge>
      ),
    },
    {
      accessorKey: 'usage_count',
      header: 'Usage',
      cell: ({ row }) => (
        <span className="text-sm text-gray-600">
          {row.original.usage_count || 0} PR{row.original.usage_count !== 1 ? 's' : ''}
        </span>
      ),
    },
    {
      accessorKey: 'is_active',
      header: 'Status',
      cell: ({ row }) => (
        <Badge variant={row.original.is_active ? 'success' : 'danger'}>
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
            onClick={() => handleEdit(row.original)}
            disabled={editingId === row.original.id || isCreating}
          >
            <Edit2 className="w-4 h-4" />
          </Button>
          <Button
            variant="ghost"
            size="sm"
            onClick={() => handleDelete(row.original)}
            disabled={editingId !== null || isCreating}
            className="text-red-600 hover:text-red-700 hover:bg-red-50"
          >
            <Trash2 className="w-4 h-4" />
          </Button>
        </div>
      ),
    },
  ];

  return (
    <>
      <Head title="PR Categories" />

      <div className="p-6 space-y-6">
        {/* Header with Search and Add Button */}
        <div className="flex items-center justify-between gap-4">
          <div className="relative flex-1 max-w-md">
            <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
            <Input
              type="text"
              placeholder="Search categories..."
              value={search}
              onChange={(e) => handleSearch(e.target.value)}
              className="pl-10"
            />
          </div>
          <Button
            onClick={handleCreate}
            disabled={isCreating || editingId !== null}
          >
            <Plus className="w-4 h-4 mr-2" />
            Add Category
          </Button>
        </div>

        {/* Inline Create/Edit Form */}
        <AnimatePresence>
          {(isCreating || editingId !== null) && (
            <motion.div
              initial={{ opacity: 0, height: 0 }}
              animate={{ opacity: 1, height: 'auto' }}
              exit={{ opacity: 0, height: 0 }}
              className="bg-white rounded-lg border border-gray-200 p-6"
            >
              <div className="flex items-center justify-between mb-4">
                <h3 className="text-lg font-semibold text-gray-900">
                  {editingId ? 'Edit Category' : 'Create Category'}
                </h3>
                <Button variant="ghost" size="sm" onClick={resetForm}>
                  <X className="w-4 h-4" />
                </Button>
              </div>

              <form onSubmit={handleSubmit} className="space-y-4">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <Label htmlFor="name">
                      Name <span className="text-red-500">*</span>
                    </Label>
                    <Input
                      id="name"
                      value={formData.name}
                      onChange={(e) =>
                        setFormData({ ...formData, name: e.target.value })
                      }
                      className={errors.name ? 'border-red-500' : ''}
                    />
                    {errors.name && (
                      <p className="text-sm text-red-600 mt-1">{errors.name}</p>
                    )}
                  </div>

                  <div>
                    <Label htmlFor="code">
                      Code <span className="text-red-500">*</span>
                    </Label>
                    <Input
                      id="code"
                      value={formData.code}
                      onChange={(e) =>
                        setFormData({ ...formData, code: e.target.value })
                      }
                      className={errors.code ? 'border-red-500' : ''}
                    />
                    {errors.code && (
                      <p className="text-sm text-red-600 mt-1">{errors.code}</p>
                    )}
                  </div>

                  <div className="md:col-span-2">
                    <Label htmlFor="description">Description</Label>
                    <Input
                      id="description"
                      value={formData.description}
                      onChange={(e) =>
                        setFormData({ ...formData, description: e.target.value })
                      }
                    />
                  </div>

                  <div>
                    <Label htmlFor="color">
                      Color <span className="text-red-500">*</span>
                    </Label>
                    <select
                      id="color"
                      value={formData.color}
                      onChange={(e) =>
                        setFormData({ ...formData, color: e.target.value })
                      }
                      className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                    >
                      {COLORS.map((color) => (
                        <option key={color.value} value={color.value}>
                          {color.label}
                        </option>
                      ))}
                    </select>
                  </div>

                  <div>
                    <Label htmlFor="sort_order">Sort Order</Label>
                    <Input
                      id="sort_order"
                      type="number"
                      min="0"
                      value={formData.sort_order}
                      onChange={(e) =>
                        setFormData({
                          ...formData,
                          sort_order: parseInt(e.target.value) || 0,
                        })
                      }
                    />
                  </div>

                  <div className="md:col-span-2">
                    <label className="flex items-center gap-2">
                      <input
                        type="checkbox"
                        checked={formData.is_active}
                        onChange={(e) =>
                          setFormData({ ...formData, is_active: e.target.checked })
                        }
                        className="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
                      />
                      <span className="text-sm font-medium text-gray-700">
                        Active
                      </span>
                    </label>
                  </div>
                </div>

                <div className="flex items-center justify-end gap-2 pt-4 border-t">
                  <Button type="button" variant="outline" onClick={resetForm}>
                    Cancel
                  </Button>
                  <Button type="submit">
                    <Check className="w-4 h-4 mr-2" />
                    {editingId ? 'Update' : 'Create'}
                  </Button>
                </div>
              </form>
            </motion.div>
          )}
        </AnimatePresence>

        {/* Data Table */}
        <DataTable
          data={filteredData}
          columns={columns}
          pagination={categories.pagination}
          emptyMessage="No categories found. Click 'Add Category' to create one."
        />
      </div>
    </>
  );
}

export default Index;
