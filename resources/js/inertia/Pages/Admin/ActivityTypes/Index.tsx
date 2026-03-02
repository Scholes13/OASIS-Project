import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { motion, AnimatePresence } from 'framer-motion';
import { Plus, Search, Edit2, Trash2, X, Check, Building2, Users, Filter } from 'lucide-react';
import { toast } from 'sonner';
import { ActivityType, ActivityTypeFormData } from '@/types/admin';
import { PageProps } from '@/types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { ColorPicker } from '@/components/admin/ColorPicker';
import { Badge } from '@/components/ui/Badge';
import { EmptyState } from '@/components/ui/empty-state';
import { Select } from '@/components/ui/select';
import {
    Dialog,
    DialogHeader,
    DialogTitle,
    DialogDescription,
    DialogContent,
    DialogFooter,
} from '@/components/ui/dialog';

// Laravel pagination structure (without meta wrapper)
interface LaravelPagination<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
}

interface Department {
    id: number;
    code: string;
    name: string;
    business_unit_id?: number;
    business_unit?: {
        id: number;
        code: string;
        name: string;
    };
}

interface BusinessUnit {
    id: number;
    code: string;
    name: string;
}

interface ExtendedActivityType extends ActivityType {
    code?: string;
    departments_count?: number;
    departments?: Department[];
    assigned_department_ids?: number[];
}

interface Props extends PageProps {
    activityTypes: LaravelPagination<ExtendedActivityType>;
    departments: Department[];
    businessUnits: BusinessUnit[];
    isSuperAdmin: boolean;
    filters: {
        search?: string;
        department_id?: string;
        business_unit_id?: string;
    };
}

function Index({ activityTypes, departments, businessUnits, isSuperAdmin, filters }: Props) {
    const [search, setSearch] = useState(filters.search || '');
    const [departmentId, setDepartmentId] = useState(filters.department_id || '');
    const [businessUnitId, setBusinessUnitId] = useState(filters.business_unit_id || '');
    const [editingId, setEditingId] = useState<number | null>(null);
    const [isCreating, setIsCreating] = useState(false);
    const [formData, setFormData] = useState<ActivityTypeFormData & { department_id?: string }>({
        name: '',
        color: 'blue',
        department_id: '',
    });
    const [isSubmitting, setIsSubmitting] = useState(false);

    // Department assignment modal state
    const [assignModalOpen, setAssignModalOpen] = useState(false);
    const [selectedActivityType, setSelectedActivityType] = useState<ExtendedActivityType | null>(null);
    const [selectedDepartments, setSelectedDepartments] = useState<number[]>([]);
    const [isAssigning, setIsAssigning] = useState(false);

    // Build current filters for navigation
    const buildFilters = (overrides: Record<string, string | undefined> = {}) => {
        const currentFilters: Record<string, string | undefined> = {
            search: search || undefined,
            department_id: departmentId || undefined,
            business_unit_id: businessUnitId || undefined,
        };
        return { ...currentFilters, ...overrides };
    };

    // Debounced search
    React.useEffect(() => {
        const timer = setTimeout(() => {
            router.get(
                route('admin.activity-types.index'),
                buildFilters(),
                { preserveState: true, replace: true }
            );
        }, 300);

        return () => clearTimeout(timer);
    }, [search]);

    // Business unit filter change (super admin only)
    const handleBusinessUnitChange = (value: string | number) => {
        const stringValue = value.toString();
        setBusinessUnitId(stringValue);
        setDepartmentId(''); // Reset department filter when BU changes
        router.get(
            route('admin.activity-types.index'),
            { search: search || undefined, business_unit_id: stringValue || undefined },
            { preserveState: true, replace: true }
        );
    };

    // Department filter change
    const handleDepartmentChange = (value: string | number) => {
        const stringValue = value.toString();
        setDepartmentId(stringValue);
        router.get(
            route('admin.activity-types.index'),
            buildFilters({ department_id: stringValue || undefined }),
            { preserveState: true, replace: true }
        );
    };

    const handleCreate = () => {
        setIsCreating(true);
        setEditingId(null);
        setFormData({ name: '', color: 'blue', department_id: departmentId || '' });
    };

    const handleEdit = (activityType: ExtendedActivityType) => {
        setEditingId(activityType.id);
        setIsCreating(false);
        setFormData({
            name: activityType.name,
            color: activityType.color,
        });
    };

    const handleCancel = () => {
        setIsCreating(false);
        setEditingId(null);
        setFormData({ name: '', color: 'blue', department_id: '' });
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        
        if (!formData.name.trim()) {
            toast.error('Activity type name is required');
            return;
        }

        // For non-super admin, department is required
        if (!isSuperAdmin && isCreating && !formData.department_id) {
            toast.error('Please select a department');
            return;
        }

        setIsSubmitting(true);

        if (isCreating) {
            router.post(route('admin.activity-types.store'), formData as any, {
                onSuccess: () => {
                    toast.success('Activity type created successfully');
                    handleCancel();
                },
                onError: (errors: any) => {
                    toast.error(errors.name || 'Failed to create activity type');
                },
                onFinish: () => setIsSubmitting(false),
            });
        } else if (editingId) {
            router.put(route('admin.activity-types.update', { activity_type: editingId }), formData as any, {
                onSuccess: () => {
                    toast.success('Activity type updated successfully');
                    handleCancel();
                },
                onError: (errors: any) => {
                    toast.error(errors.name || 'Failed to update activity type');
                },
                onFinish: () => setIsSubmitting(false),
            });
        }
    };

    const handleDelete = (activityType: ExtendedActivityType) => {
        if (activityType.sub_activities_count && activityType.sub_activities_count > 0) {
            toast.error('Cannot delete activity type with sub-activities. Delete sub-activities first.');
            return;
        }

        if (activityType.usage_count && activityType.usage_count > 0) {
            toast.error('Cannot delete activity type that is being used by tasks. Consider deactivating it instead.');
            return;
        }

        if (!confirm('Are you sure you want to delete this activity type?')) {
            return;
        }

        router.delete(route('admin.activity-types.destroy', { activity_type: activityType.id }), {
            onSuccess: () => {
                toast.success('Activity type deleted successfully');
            },
            onError: () => {
                toast.error('Failed to delete activity type');
            },
        });
    };

    // Open department assignment modal
    const handleOpenAssignModal = (activityType: ExtendedActivityType) => {
        setSelectedActivityType(activityType);
        // Pre-select already assigned departments
        setSelectedDepartments(activityType.assigned_department_ids || []);
        setAssignModalOpen(true);
    };

    // Handle department checkbox toggle
    const handleDepartmentToggle = (deptId: number) => {
        setSelectedDepartments(prev => 
            prev.includes(deptId) 
                ? prev.filter(id => id !== deptId)
                : [...prev, deptId]
        );
    };

    // Submit department assignments
    const handleAssignDepartments = () => {
        if (!selectedActivityType || selectedDepartments.length === 0) {
            toast.error('Please select at least one department');
            return;
        }

        setIsAssigning(true);

        router.post(
            route('admin.activity-types.assign-departments', { activity_type: selectedActivityType.id }),
            { department_ids: selectedDepartments },
            {
                onSuccess: () => {
                    toast.success(`Activity type assigned to ${selectedDepartments.length} department(s)`);
                    setAssignModalOpen(false);
                    setSelectedActivityType(null);
                    setSelectedDepartments([]);
                },
                onError: () => {
                    toast.error('Failed to assign departments');
                },
                onFinish: () => setIsAssigning(false),
            }
        );
    };

    const getColorClasses = (color: string) => {
        const colorMap: Record<string, string> = {
            blue: 'bg-blue-100 text-blue-800',
            green: 'bg-green-100 text-green-800',
            purple: 'bg-purple-100 text-purple-800',
            pink: 'bg-pink-100 text-pink-800',
            yellow: 'bg-yellow-100 text-yellow-800',
            red: 'bg-red-100 text-red-800',
            gray: 'bg-gray-100 text-gray-800',
            indigo: 'bg-blue-50 text-blue-700',
            amber: 'bg-amber-100 text-amber-800',
            emerald: 'bg-emerald-100 text-emerald-800',
            cyan: 'bg-cyan-100 text-cyan-800',
            rose: 'bg-rose-100 text-rose-800',
        };
        return colorMap[color] || 'bg-gray-100 text-gray-800';
    };

    // Filter departments by selected business unit for assignment modal
    const filteredDepartmentsForAssignment = React.useMemo(() => {
        return departments;
    }, [departments]);

    // Group departments by business unit for assignment modal
    const departmentsByBusinessUnit = React.useMemo(() => {
        const groups: Record<string, { businessUnit: BusinessUnit; departments: Department[] }> = {};
        
        filteredDepartmentsForAssignment.forEach(dept => {
            const buKey = dept.business_unit?.id?.toString() || 'unknown';
            if (!groups[buKey]) {
                groups[buKey] = {
                    businessUnit: dept.business_unit || { id: 0, code: 'N/A', name: 'Unknown' },
                    departments: [],
                };
            }
            groups[buKey].departments.push(dept);
        });

        return Object.values(groups);
    }, [filteredDepartmentsForAssignment]);

    return (
        <>
            <Head title="Activity Types" />

            <div className="p-6 space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900">Activity Types</h1>
                        <p className="text-sm text-gray-600 mt-1">
                            {isSuperAdmin 
                                ? 'Manage master activity types globally and assign to departments'
                                : 'Manage activity types for employee task tracking'
                            }
                        </p>
                    </div>
                    <Button onClick={handleCreate} disabled={isCreating}>
                        <Plus className="w-4 h-4 mr-2" />
                        Add Activity Type
                    </Button>
                </div>

                {/* Search & Filter */}
                <div className="bg-white rounded-xl border border-gray-200 p-4">
                    <div className={`grid grid-cols-1 gap-4 ${isSuperAdmin ? 'md:grid-cols-3' : 'md:grid-cols-2'}`}>
                        <div className="relative">
                            <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                            <Input
                                type="text"
                                placeholder="Search activity types..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                className="pl-10"
                            />
                        </div>
                        {isSuperAdmin && businessUnits.length > 0 && (
                            <div className="relative">
                                <Building2 className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none z-10" />
                                <Select
                                    value={businessUnitId}
                                    onChange={handleBusinessUnitChange}
                                    options={[
                                        { value: '', label: 'All Business Units' },
                                        ...businessUnits.map((bu) => ({
                                            value: bu.id.toString(),
                                            label: `${bu.code} - ${bu.name}`,
                                        })),
                                    ]}
                                    placeholder="All Business Units"
                                    className="pl-10"
                                />
                            </div>
                        )}
                        {!isSuperAdmin && (
                            <div className="relative">
                                <Filter className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none z-10" />
                                <Select
                                    value={departmentId}
                                    onChange={handleDepartmentChange}
                                    options={[
                                        { value: '', label: 'All Departments' },
                                        ...departments.map((dept) => ({
                                            value: dept.id.toString(),
                                            label: `${dept.code} - ${dept.name}`,
                                        })),
                                    ]}
                                    placeholder="All Departments"
                                    className="pl-10"
                                />
                            </div>
                        )}
                    </div>
                </div>

                {/* Create Form */}
                <AnimatePresence>
                    {isCreating && (
                        <motion.div
                            initial={{ opacity: 0, height: 0 }}
                            animate={{ opacity: 1, height: 'auto' }}
                            exit={{ opacity: 0, height: 0 }}
                            className="bg-white rounded-xl border border-gray-200 overflow-hidden"
                        >
                            <div className="p-6">
                                <h3 className="text-lg font-semibold text-gray-900 mb-4">
                                    Create Activity Type
                                </h3>
                                <form onSubmit={handleSubmit} className="space-y-4">
                                    {/* Department selection only for non-super admin */}
                                    {!isSuperAdmin && (
                                        <div>
                                            <Label htmlFor="department_id">Department *</Label>
                                            <select
                                                id="department_id"
                                                value={formData.department_id}
                                                onChange={(e) =>
                                                    setFormData({ ...formData, department_id: e.target.value })
                                                }
                                                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                                                required
                                            >
                                                <option value="">Select Department</option>
                                                {departments.map((dept) => (
                                                    <option key={dept.id} value={dept.id}>
                                                        {dept.code} - {dept.name}
                                                    </option>
                                                ))}
                                            </select>
                                        </div>
                                    )}

                                    <div>
                                        <Label htmlFor="name">Name *</Label>
                                        <Input
                                            id="name"
                                            type="text"
                                            value={formData.name}
                                            onChange={(e) =>
                                                setFormData({ ...formData, name: e.target.value })
                                            }
                                            placeholder="Enter activity type name"
                                            required
                                        />
                                    </div>

                                    <div>
                                        <Label htmlFor="color">Color *</Label>
                                        <ColorPicker
                                            label=""
                                            value={formData.color}
                                            onChange={(color) =>
                                                setFormData({ ...formData, color })
                                            }
                                        />
                                    </div>

                                    <div className="flex items-center gap-2">
                                        <Button type="submit" disabled={isSubmitting}>
                                            <Check className="w-4 h-4 mr-2" />
                                            Create
                                        </Button>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            onClick={handleCancel}
                                            disabled={isSubmitting}
                                        >
                                            <X className="w-4 h-4 mr-2" />
                                            Cancel
                                        </Button>
                                    </div>
                                </form>
                            </div>
                        </motion.div>
                    )}
                </AnimatePresence>

                {/* Activity Types List */}
                <div className="bg-white rounded-xl border border-gray-200 overflow-hidden">
                    {activityTypes.data.length === 0 ? (
                        <EmptyState
                            title="No activity types found"
                            description={
                                search
                                    ? 'Try adjusting your search query'
                                    : 'Get started by creating your first activity type'
                            }
                        />
                    ) : (
                        <>
                            {/* Table Header */}
                            <div className="hidden md:grid md:grid-cols-12 gap-4 px-6 py-3 bg-gray-50 border-b border-gray-200 text-sm font-medium text-gray-600">
                                <div className="col-span-4">Activity Type</div>
                                {isSuperAdmin && <div className="col-span-2 text-center">Departments</div>}
                                <div className={`${isSuperAdmin ? 'col-span-2' : 'col-span-3'} text-center`}>Sub-Activities</div>
                                <div className={`${isSuperAdmin ? 'col-span-2' : 'col-span-3'} text-center`}>Tasks</div>
                                <div className={`${isSuperAdmin ? 'col-span-2' : 'col-span-2'} text-right`}>Actions</div>
                            </div>

                            <div className="divide-y divide-gray-200">
                                {activityTypes.data.map((activityType) => (
                                    <motion.div
                                        key={activityType.id}
                                        layout
                                        className="p-6 hover:bg-gray-50 transition-colors"
                                    >
                                        {editingId === activityType.id ? (
                                            <form onSubmit={handleSubmit} className="space-y-4">
                                                <div>
                                                    <Label htmlFor={`edit-name-${activityType.id}`}>
                                                        Name *
                                                    </Label>
                                                    <Input
                                                        id={`edit-name-${activityType.id}`}
                                                        type="text"
                                                        value={formData.name}
                                                        onChange={(e) =>
                                                            setFormData({
                                                                ...formData,
                                                                name: e.target.value,
                                                            })
                                                        }
                                                        placeholder="Enter activity type name"
                                                        required
                                                    />
                                                </div>

                                                <div>
                                                    <Label htmlFor={`edit-color-${activityType.id}`}>
                                                        Color *
                                                    </Label>
                                                    <ColorPicker
                                                        label=""
                                                        value={formData.color}
                                                        onChange={(color) =>
                                                            setFormData({ ...formData, color })
                                                        }
                                                    />
                                                </div>

                                                <div className="flex items-center gap-2">
                                                    <Button type="submit" disabled={isSubmitting}>
                                                        <Check className="w-4 h-4 mr-2" />
                                                        Save
                                                    </Button>
                                                    <Button
                                                        type="button"
                                                        variant="outline"
                                                        onClick={handleCancel}
                                                        disabled={isSubmitting}
                                                    >
                                                        <X className="w-4 h-4 mr-2" />
                                                        Cancel
                                                    </Button>
                                                </div>
                                            </form>
                                        ) : (
                                            <div className="grid grid-cols-1 md:grid-cols-12 gap-4 items-center">
                                                {/* Activity Type Name */}
                                                <div className="col-span-4 flex items-center gap-3">
                                                    <Badge className={getColorClasses(activityType.color)}>
                                                        {activityType.name}
                                                    </Badge>
                                                    {activityType.code && (
                                                        <span className="text-xs text-gray-500 font-mono">
                                                            {activityType.code}
                                                        </span>
                                                    )}
                                                </div>

                                                {/* Department Count (Super Admin only) */}
                                                {isSuperAdmin && (
                                                    <div className="col-span-2 text-center">
                                                        <span className="inline-flex items-center gap-1 text-sm text-gray-600">
                                                            <Building2 className="w-4 h-4" />
                                                            {activityType.departments_count || 0}
                                                        </span>
                                                    </div>
                                                )}

                                                {/* Sub-Activities Count */}
                                                <div className={`${isSuperAdmin ? 'col-span-2' : 'col-span-3'} text-center`}>
                                                    <span className="text-sm text-gray-600">
                                                        {activityType.sub_activities_count || 0} sub-activities
                                                    </span>
                                                </div>

                                                {/* Tasks Count */}
                                                <div className={`${isSuperAdmin ? 'col-span-2' : 'col-span-3'} text-center`}>
                                                    <span className="text-sm text-gray-600">
                                                        {activityType.usage_count || 0} tasks
                                                    </span>
                                                </div>

                                                {/* Actions */}
                                                <div className={`${isSuperAdmin ? 'col-span-2' : 'col-span-2'} flex items-center justify-end gap-2`}>
                                                    {isSuperAdmin && (
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            onClick={() => handleOpenAssignModal(activityType)}
                                                            title="Assign to Departments"
                                                        >
                                                            <Users className="w-4 h-4" />
                                                        </Button>
                                                    )}
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={() => handleEdit(activityType)}
                                                    >
                                                        <Edit2 className="w-4 h-4" />
                                                    </Button>
                                                    {(!activityType.sub_activities_count || activityType.sub_activities_count === 0) &&
                                                        (!activityType.usage_count || activityType.usage_count === 0) && (
                                                            <Button
                                                                variant="ghost"
                                                                size="sm"
                                                                onClick={() => handleDelete(activityType)}
                                                                className="text-red-600 hover:text-red-700 hover:bg-red-50"
                                                            >
                                                                <Trash2 className="w-4 h-4" />
                                                            </Button>
                                                        )}
                                                </div>
                                            </div>
                                        )}
                                    </motion.div>
                                ))}
                            </div>
                        </>
                    )}

                    {/* Pagination */}
                    {activityTypes.last_page > 1 && (
                        <div className="px-6 py-4 border-t border-gray-200 flex items-center justify-between">
                            <div className="text-sm text-gray-600">
                                Showing {activityTypes.from} to {activityTypes.to} of{' '}
                                {activityTypes.total} results
                            </div>
                            <div className="flex items-center gap-2">
                                {activityTypes.current_page > 1 && (
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={() =>
                                            router.get(
                                                route('admin.activity-types.index'),
                                                {
                                                    ...buildFilters(),
                                                    page: activityTypes.current_page - 1,
                                                },
                                                { preserveState: true }
                                            )
                                        }
                                    >
                                        Previous
                                    </Button>
                                )}
                                {activityTypes.current_page < activityTypes.last_page && (
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={() =>
                                            router.get(
                                                route('admin.activity-types.index'),
                                                {
                                                    ...buildFilters(),
                                                    page: activityTypes.current_page + 1,
                                                },
                                                { preserveState: true }
                                            )
                                        }
                                    >
                                        Next
                                    </Button>
                                )}
                            </div>
                        </div>
                    )}
                </div>
            </div>

            {/* Department Assignment Modal */}
            <Dialog open={assignModalOpen} onClose={() => setAssignModalOpen(false)} className="max-w-2xl">
                <DialogHeader onClose={() => setAssignModalOpen(false)}>
                    <DialogTitle>Assign to Departments</DialogTitle>
                    <DialogDescription>
                        Select departments to assign "{selectedActivityType?.name}" activity type
                    </DialogDescription>
                </DialogHeader>
                <DialogContent>
                    <div className="max-h-96 overflow-y-auto space-y-4">
                        {departmentsByBusinessUnit.map((group) => (
                            <div key={group.businessUnit.id} className="border border-gray-200 rounded-lg overflow-hidden">
                                <div className="bg-gray-50 px-4 py-2 border-b border-gray-200">
                                    <span className="font-medium text-gray-900">
                                        {group.businessUnit.code} - {group.businessUnit.name}
                                    </span>
                                </div>
                                <div className="p-4 space-y-2">
                                    {group.departments.map((dept) => {
                                        const isAlreadyAssigned = selectedActivityType?.assigned_department_ids?.includes(dept.id) || false;
                                        const isSelected = selectedDepartments.includes(dept.id);
                                        
                                        return (
                                            <label
                                                key={dept.id}
                                                className={`flex items-center gap-3 p-2 rounded-lg cursor-pointer ${
                                                    isAlreadyAssigned && !isSelected 
                                                        ? 'bg-green-50 hover:bg-green-100' 
                                                        : 'hover:bg-gray-50'
                                                }`}
                                            >
                                                <input
                                                    type="checkbox"
                                                    checked={isSelected}
                                                    onChange={() => handleDepartmentToggle(dept.id)}
                                                    className="w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary"
                                                />
                                                <span className="text-sm text-gray-900">
                                                    {dept.code} - {dept.name}
                                                </span>
                                                {isAlreadyAssigned && (
                                                    <span className="ml-auto text-xs text-green-600 font-medium">
                                                        Already assigned
                                                    </span>
                                                )}
                                            </label>
                                        );
                                    })}
                                </div>
                            </div>
                        ))}
                    </div>
                    {selectedDepartments.length > 0 && (
                        <div className="mt-4 p-3 bg-primary rounded-lg">
                            <span className="text-sm text-primary">
                                {selectedDepartments.length} department(s) selected
                                {selectedActivityType?.assigned_department_ids && selectedActivityType.assigned_department_ids.length > 0 && (
                                    <span className="ml-2 text-gray-500">
                                        ({selectedActivityType.assigned_department_ids.length} already assigned)
                                    </span>
                                )}
                            </span>
                        </div>
                    )}
                </DialogContent>
                <DialogFooter>
                    <Button
                        variant="outline"
                        onClick={() => setAssignModalOpen(false)}
                        disabled={isAssigning}
                    >
                        Cancel
                    </Button>
                    <Button
                        onClick={handleAssignDepartments}
                        disabled={isAssigning || selectedDepartments.length === 0}
                        loading={isAssigning}
                    >
                        <Users className="w-4 h-4 mr-2" />
                        Assign ({selectedDepartments.length})
                    </Button>
                </DialogFooter>
            </Dialog>
        </>
    );
}

export default Index;
