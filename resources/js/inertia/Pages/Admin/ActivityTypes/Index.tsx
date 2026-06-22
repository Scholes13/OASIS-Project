import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { motion, AnimatePresence } from 'framer-motion';
import { Plus, Search, X, Check, Building2, Filter } from 'lucide-react';
import { toast } from 'sonner';
import { ActivityType, ActivityTypeFormData } from '@/types/admin';
import { PageProps } from '@/types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { ColorPicker } from '@/components/admin/ColorPicker';
import { Select } from '@/components/ui/select';
import ActivityTypesTable from '@/components/admin/activity-types/ActivityTypesTable';
import DepartmentAssignmentModal from '@/components/admin/activity-types/DepartmentAssignmentModal';

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

                <ActivityTypesTable
                    activityTypes={activityTypes}
                    editingId={editingId}
                    formData={formData}
                    isSubmitting={isSubmitting}
                    isSuperAdmin={isSuperAdmin}
                    search={search}
                    buildFilters={buildFilters}
                    getColorClasses={getColorClasses}
                    onCancel={handleCancel}
                    onDelete={handleDelete}
                    onEdit={handleEdit}
                    onFormDataChange={setFormData}
                    onOpenAssignModal={handleOpenAssignModal}
                    onSubmit={handleSubmit}
                />
            </div>

            <DepartmentAssignmentModal
                open={assignModalOpen}
                selectedActivityType={selectedActivityType}
                selectedDepartments={selectedDepartments}
                departmentsByBusinessUnit={departmentsByBusinessUnit}
                isAssigning={isAssigning}
                onAssignDepartments={handleAssignDepartments}
                onClose={() => setAssignModalOpen(false)}
                onDepartmentToggle={handleDepartmentToggle}
            />
        </>
    );
}

export default Index;
