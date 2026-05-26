import React, { useState, useMemo } from 'react';
import { Head, router } from '@inertiajs/react';
import { motion, AnimatePresence } from 'framer-motion';
import {
    Plus,
    Search,
    Edit2,
    Trash2,
    Check,
    X,
    ChevronDown,
    ChevronRight,
    Building2,
    Users,
    Folder,
    ClipboardList,
} from 'lucide-react';
import { toast } from 'sonner';
import { PageProps } from '@/types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/Badge';
import { EmptyState } from '@/components/ui/empty-state';
import { Select } from '@/components/ui/select';
import { useDebouncedSearch } from '@/hooks/useDebouncedSearch';
import {
    Dialog,
    DialogHeader,
    DialogTitle,
    DialogDescription,
    DialogContent,
    DialogFooter,
    ConfirmDialog,
} from '@/components/ui/dialog';

// Types
interface Department {
    id: number;
    name: string;
    code: string;
    business_unit_id: number;
    business_unit?: { id: number; code: string; name: string };
}

interface BusinessUnit {
    id: number;
    name: string;
    code: string;
}

interface SubActivity {
    id: number;
    name: string;
    activity_type_id: number;
    tasks_count: number;
}

interface ActivityType {
    id: number;
    name: string;
    code: string;
    color: string;
    sub_activities_count: number;
    tasks_count: number;
    assigned_department_ids: number[];
    departments: Department[];
    sub_activities: SubActivity[];
}

interface Props extends PageProps {
    activityTypes: ActivityType[];
    departments: Department[];
    businessUnits: BusinessUnit[];
    isSuperAdmin: boolean;
    filters: { search: string; business_unit_id: string };
}

function ActivityConfiguration({
    activityTypes,
    departments,
    businessUnits,
    isSuperAdmin,
    filters,
}: Props) {
    // Search and filter state
    const { value: search, debouncedValue: debouncedSearch, setValue: setSearch } = useDebouncedSearch(filters.search || '');
    const [businessUnitId, setBusinessUnitId] = useState(filters.business_unit_id || '');

    // Activity Type modal state
    const [activityTypeModalOpen, setActivityTypeModalOpen] = useState(false);
    const [editingActivityType, setEditingActivityType] = useState<ActivityType | null>(null);
    const [activityTypeForm, setActivityTypeForm] = useState({
        name: '',
    });
    const [selectedDepartmentIds, setSelectedDepartmentIds] = useState<number[]>([]);
    const [isSubmittingActivityType, setIsSubmittingActivityType] = useState(false);

    // Sub-Activity modal state
    const [subActivityModalOpen, setSubActivityModalOpen] = useState(false);
    const [parentActivityType, setParentActivityType] = useState<ActivityType | null>(null);
    const [editingSubActivity, setEditingSubActivity] = useState<SubActivity | null>(null);
    const [subActivityForm, setSubActivityForm] = useState({
        name: '',
    });
    const [isSubmittingSubActivity, setIsSubmittingSubActivity] = useState(false);

    // Delete confirmation modal state
    const [deleteModalOpen, setDeleteModalOpen] = useState(false);
    const [itemToDelete, setItemToDelete] = useState<{
        type: 'activityType' | 'subActivity';
        item: ActivityType | SubActivity;
        parentId?: number;
    } | null>(null);
    const [isDeleting, setIsDeleting] = useState(false);

    // Expanded state for accordion
    const [expandedIds, setExpandedIds] = useState<Set<number>>(new Set());

    // Compute which groups to auto-expand based on search
    const autoExpandedIds = useMemo(() => {
        if (!search.trim()) return new Set<number>();
        const ids = new Set<number>();
        activityTypes.forEach((at) => {
            // Check if activity type name matches
            if (at.name.toLowerCase().includes(search.toLowerCase())) {
                ids.add(at.id);
                return;
            }
            // Check if any sub-activity matches
            const matchingSub = at.sub_activities?.some((sub) =>
                sub.name.toLowerCase().includes(search.toLowerCase())
            );
            if (matchingSub) {
                ids.add(at.id);
            }
        });
        return ids;
    }, [search, activityTypes]);

    // Toggle expansion
    const toggleExpansion = (id: number) => {
        setExpandedIds((prev) => {
            const next = new Set(prev);
            if (next.has(id)) {
                next.delete(id);
            } else {
                next.add(id);
            }
            return next;
        });
    };

    // Debounced search
    React.useEffect(() => {
        router.get(
            route('admin.activity-configuration.index'),
            {
                search: debouncedSearch || undefined,
                business_unit_id: businessUnitId || undefined,
            },
            { preserveState: true, replace: true }
        );
    }, [debouncedSearch]);

    // Handle business unit filter change
    const handleBusinessUnitChange = (value: string | number) => {
        const stringValue = value.toString();
        setBusinessUnitId(stringValue);
        router.get(
            route('admin.activity-configuration.index'),
            { search: search || undefined, business_unit_id: stringValue || undefined },
            { preserveState: true, replace: true }
        );
    };

    // Open create activity type modal
    const openCreateActivityTypeModal = () => {
        setEditingActivityType(null);
        setActivityTypeForm({ name: '' });
        setSelectedDepartmentIds([]);
        setActivityTypeModalOpen(true);
    };

    // Open edit activity type modal
    const openEditActivityTypeModal = (activityType: ActivityType) => {
        setEditingActivityType(activityType);
        setActivityTypeForm({
            name: activityType.name,
        });
        setSelectedDepartmentIds(activityType.assigned_department_ids || []);
        setActivityTypeModalOpen(true);
    };

    // Handle activity type form submit
    const handleActivityTypeSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        if (!activityTypeForm.name.trim()) {
            toast.error('Activity type name is required');
            return;
        }

        setIsSubmittingActivityType(true);
        // Close modal immediately to prevent headlessui portal persistence during Inertia redirect
        setActivityTypeModalOpen(false);

        if (editingActivityType) {
            // Update
            router.put(
                route('admin.activity-types.update', { activity_type: editingActivityType.id }),
                activityTypeForm as any,
                {
                    onSuccess: () => {
                        toast.success('Activity type updated successfully');
                        setEditingActivityType(null);
                    },
                    onError: (errors: Record<string, string>) => {
                        toast.error(errors.name || 'Failed to update activity type');
                        setActivityTypeModalOpen(true); // Re-open on error
                    },
                    onFinish: () => setIsSubmittingActivityType(false),
                }
            );
        } else {
            // Create
            router.post(route('admin.activity-types.store'), activityTypeForm as any, {
                onSuccess: () => {
                    toast.success('Activity type created successfully');
                },
                onError: (errors: Record<string, string>) => {
                    toast.error(errors.name || 'Failed to create activity type');
                    setActivityTypeModalOpen(true); // Re-open on error
                },
                onFinish: () => setIsSubmittingActivityType(false),
            });
        }
    };

    // Handle assign departments
    const handleAssignDepartments = () => {
        if (!editingActivityType) return;

        if (selectedDepartmentIds.length === 0) {
            toast.error('Please select at least one department');
            return;
        }

        setIsSubmittingActivityType(true);

        router.post(
            route('admin.activity-types.assign-departments', {
                activity_type: editingActivityType.id,
            }),
            { department_ids: selectedDepartmentIds },
            {
                onSuccess: () => {
                    toast.success(
                        `Assigned to ${selectedDepartmentIds.length} department(s)`
                    );
                    setActivityTypeModalOpen(false);
                    setEditingActivityType(null);
                    setSelectedDepartmentIds([]);
                },
                onError: () => {
                    toast.error('Failed to assign departments');
                },
                onFinish: () => setIsSubmittingActivityType(false),
            }
        );
    };

    // Department toggle for assignment
    const handleDepartmentToggle = (deptId: number) => {
        setSelectedDepartmentIds((prev) =>
            prev.includes(deptId)
                ? prev.filter((id) => id !== deptId)
                : [...prev, deptId]
        );
    };

    // Open create sub-activity modal
    const openCreateSubActivityModal = (parentActivityType: ActivityType) => {
        setParentActivityType(parentActivityType);
        setEditingSubActivity(null);
        setSubActivityForm({ name: '' });
        setSubActivityModalOpen(true);
    };

    // Open edit sub-activity modal
    const openEditSubActivityModal = (subActivity: SubActivity, parent: ActivityType) => {
        setParentActivityType(parent);
        setEditingSubActivity(subActivity);
        setSubActivityForm({ name: subActivity.name });
        setSubActivityModalOpen(true);
    };

    // Handle sub-activity form submit
    const handleSubActivitySubmit = (e: React.FormEvent) => {
        e.preventDefault();

        if (!subActivityForm.name.trim()) {
            toast.error('Sub-activity name is required');
            return;
        }

        if (!parentActivityType) {
            toast.error('Parent activity type is required');
            return;
        }

        setIsSubmittingSubActivity(true);
        // Close modal immediately to prevent headlessui portal persistence during Inertia redirect
        setSubActivityModalOpen(false);

        if (editingSubActivity) {
            // Update
            router.put(
                route('admin.sub-activities.update', {
                    sub_activity: editingSubActivity.id,
                }),
                { name: subActivityForm.name } as any,
                {
                    onSuccess: () => {
                        toast.success('Sub-activity updated successfully');
                        setEditingSubActivity(null);
                    },
                    onError: (errors: Record<string, string>) => {
                        toast.error(errors.name || 'Failed to update sub-activity');
                        setSubActivityModalOpen(true); // Re-open on error
                    },
                    onFinish: () => setIsSubmittingSubActivity(false),
                }
            );
        } else {
            // Create
            const parentId = parentActivityType.id;
            router.post(
                route('admin.sub-activities.store'),
                {
                    name: subActivityForm.name,
                    activity_type_id: parentId,
                } as any,
                {
                    onSuccess: () => {
                        toast.success('Sub-activity created successfully');
                        setExpandedIds((prev) => new Set([...prev, parentId]));
                    },
                    onError: (errors: Record<string, string>) => {
                        toast.error(errors.name || 'Failed to create sub-activity');
                        setSubActivityModalOpen(true); // Re-open on error
                    },
                    onFinish: () => setIsSubmittingSubActivity(false),
                }
            );
        }
    };

    // Open delete confirmation
    const openDeleteModal = (
        type: 'activityType' | 'subActivity',
        item: ActivityType | SubActivity,
        parentId?: number
    ) => {
        setItemToDelete({ type, item, parentId });
        setDeleteModalOpen(true);
    };

    // Handle delete confirmation
    const handleDeleteConfirm = () => {
        if (!itemToDelete) return;

        setIsDeleting(true);

        if (itemToDelete.type === 'activityType') {
            const activityType = itemToDelete.item as ActivityType;

            if (activityType.sub_activities_count && activityType.sub_activities_count > 0) {
                toast.error(
                    'Cannot delete activity type with sub-activities. Delete sub-activities first.'
                );
                setIsDeleting(false);
                return;
            }

            if (activityType.tasks_count && activityType.tasks_count > 0) {
                toast.error(
                    'Cannot delete activity type that is being used by tasks. Consider deactivating it instead.'
                );
                setIsDeleting(false);
                return;
            }

            router.delete(
                route('admin.activity-types.destroy', {
                    activity_type: activityType.id,
                }),
                {
                    onSuccess: () => {
                        toast.success('Activity type deleted successfully');
                        setDeleteModalOpen(false);
                        setItemToDelete(null);
                    },
                    onError: () => {
                        toast.error('Failed to delete activity type');
                    },
                    onFinish: () => setIsDeleting(false),
                }
            );
        } else {
            const subActivity = itemToDelete.item as SubActivity;

            if (subActivity.tasks_count && subActivity.tasks_count > 0) {
                toast.error(
                    'Cannot delete sub-activity that is being used by tasks. Consider deactivating it instead.'
                );
                setIsDeleting(false);
                return;
            }

            router.delete(
                route('admin.sub-activities.destroy', {
                    sub_activity: subActivity.id,
                }),
                {
                    onSuccess: () => {
                        toast.success('Sub-activity deleted successfully');
                        setDeleteModalOpen(false);
                        setItemToDelete(null);
                    },
                    onError: () => {
                        toast.error('Failed to delete sub-activity');
                    },
                    onFinish: () => setIsDeleting(false),
                }
            );
        }
    };

    // Group departments by business unit
    const departmentsByBusinessUnit = useMemo(() => {
        const groups: Record<string, { businessUnit: BusinessUnit; departments: Department[] }> =
            {};

        departments.forEach((dept) => {
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
    }, [departments]);

    // Determine if any activity type should be expanded (based on search or explicit toggle)
    const isExpanded = (id: number) => {
        return expandedIds.has(id) || autoExpandedIds.has(id);
    };

    // Filtered activity types for display
    const filteredActivityTypes = useMemo(() => {
        if (!search.trim()) return activityTypes;
        return activityTypes.filter((at) => {
            const searchLower = search.toLowerCase();
            if (at.name.toLowerCase().includes(searchLower)) return true;
            if (
                at.sub_activities?.some((sub) =>
                    sub.name.toLowerCase().includes(searchLower)
                )
            ) {
                return true;
            }
            return false;
        });
    }, [activityTypes, search]);

    return (
        <>
            <Head title="Activity Configuration" />

            <div className="p-6 space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900">
                            Activity Configuration
                        </h1>
                        <p className="text-sm text-gray-600 mt-1">
                            {isSuperAdmin
                                ? 'Manage activity types, sub-activities, and department assignments'
                                : 'Manage activity types and sub-activities for your department'}
                        </p>
                    </div>
                    <Button onClick={openCreateActivityTypeModal}>
                        <Plus className="w-4 h-4 mr-2" />
                        Add Activity Type
                    </Button>
                </div>

                {/* Filter Bar */}
                <div className="bg-white rounded-xl border border-gray-200 p-4">
                    <div
                        className={`grid grid-cols-1 gap-4 ${
                            isSuperAdmin ? 'md:grid-cols-2' : 'md:grid-cols-1'
                        }`}
                    >
                        <div className="relative">
                            <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                            <Input
                                type="text"
                                placeholder="Search activity types or sub-activities..."
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
                    </div>
                </div>

                {/* Activity Types Tree/Accordion */}
                {filteredActivityTypes.length === 0 ? (
                    <div className="bg-white rounded-xl border border-gray-200 overflow-hidden">
                        <EmptyState
                            icon={<Folder className="h-12 w-12" />}
                            title={
                                search
                                    ? 'No activity types match your search.'
                                    : 'No activity types configured yet.'
                            }
                            description={
                                search
                                    ? 'Try adjusting your search query.'
                                    : 'Create your first activity type to start organizing tasks.'
                            }
                            action={
                                !search
                                    ? {
                                        label: 'Add Activity Type',
                                        onClick: openCreateActivityTypeModal,
                                    }
                                    : undefined
                            }
                        />
                    </div>
                ) : (
                    <div className="space-y-2">
                        <AnimatePresence>
                            {filteredActivityTypes.map((activityType) => (
                                <motion.div
                                    key={activityType.id}
                                    initial={{ opacity: 0, y: -10 }}
                                    animate={{ opacity: 1, y: 0 }}
                                    exit={{ opacity: 0, y: -10 }}
                                    className="bg-white rounded-xl border border-gray-200 overflow-hidden"
                                >
                                    {/* Activity Type Row */}
                                    <div
                                        className={`p-4 hover:bg-gray-50 transition-colors ${
                                            isExpanded(activityType.id)
                                                ? 'bg-gray-50'
                                                : ''
                                        }`}
                                    >
                                        <div className="flex items-center justify-between">
                                            {/* Left: Expand button + Color dot + Name + Stats */}
                                            <div className="flex items-center gap-3">
                                                <button
                                                    onClick={() =>
                                                        toggleExpansion(
                                                            activityType.id
                                                        )
                                                    }
                                                    className="p-1 hover:bg-gray-200 rounded transition-colors"
                                                >
                                                    {isExpanded(activityType.id) ? (
                                                        <ChevronDown className="w-4 h-4 text-gray-500" />
                                                    ) : (
                                                        <ChevronRight className="w-4 h-4 text-gray-500" />
                                                    )}
                                                </button>
                                                <span className="font-medium text-gray-900">
                                                    {activityType.name}
                                                </span>
                                                {activityType.code && (
                                                    <span className="text-[11px] text-gray-400 bg-gray-100 px-1.5 py-0.5 rounded">
                                                        {activityType.code}
                                                    </span>
                                                )}
                                            </div>

                                            {/* Center: Stats */}
                                            <div className="hidden md:flex items-center gap-6 text-sm text-gray-600">
                                                <span className="flex items-center gap-1">
                                                    <Building2 className="w-4 h-4" />
                                                    {activityType.departments
                                                        ?.length || 0}{' '}
                                                    depts
                                                </span>
                                                <span className="flex items-center gap-1">
                                                    <Folder className="w-4 h-4" />
                                                    {activityType.sub_activities_count ||
                                                        0}{' '}
                                                    sub-activities
                                                </span>
                                                <span className="flex items-center gap-1">
                                                    <ClipboardList className="w-4 h-4" />
                                                    {activityType.tasks_count ||
                                                        0}{' '}
                                                    tasks
                                                </span>
                                            </div>

                                            {/* Right: Actions */}
                                            <div className="flex items-center gap-1">
                                                {isSuperAdmin && (
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={() =>
                                                            openEditActivityTypeModal(
                                                                activityType
                                                            )
                                                        }
                                                        title="Edit"
                                                    >
                                                        <Edit2 className="w-4 h-4" />
                                                    </Button>
                                                )}
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={() =>
                                                        openCreateSubActivityModal(
                                                            activityType
                                                        )
                                                    }
                                                    title="Add Sub-Activity"
                                                >
                                                    <Plus className="w-4 h-4" />
                                                </Button>
                                                {isSuperAdmin &&
                                                    (!activityType.sub_activities_count ||
                                                        activityType.sub_activities_count ===
                                                            0) &&
                                                    (!activityType.tasks_count ||
                                                        activityType.tasks_count ===
                                                            0) && (
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            onClick={() =>
                                                                openDeleteModal(
                                                                    'activityType',
                                                                    activityType
                                                                )
                                                            }
                                                            className="text-red-600 hover:text-red-700 hover:bg-red-50"
                                                            title="Delete"
                                                        >
                                                            <Trash2 className="w-4 h-4" />
                                                        </Button>
                                                    )}
                                            </div>
                                        </div>

                                        {/* Expanded: Sub-activities */}
                                        <AnimatePresence>
                                            {isExpanded(activityType.id) && (
                                                <motion.div
                                                    initial={{
                                                        opacity: 0,
                                                        height: 0,
                                                    }}
                                                    animate={{
                                                        opacity: 1,
                                                        height: 'auto',
                                                    }}
                                                    exit={{
                                                        opacity: 0,
                                                        height: 0,
                                                    }}
                                                    className="mt-4 pt-4 border-t border-gray-200"
                                                >
                                                    <div className="space-y-2 pl-7">
                                                        {activityType.sub_activities?.map(
                                                            (subActivity) => (
                                                                <div
                                                                    key={
                                                                        subActivity.id
                                                                    }
                                                                    className="flex items-center justify-between p-3 rounded-lg hover:bg-gray-50 transition-colors"
                                                                >
                                                                    <div className="flex items-center gap-3">
                                                                        <span className="text-gray-900">
                                                                            {subActivity.name}
                                                                        </span>
                                                                        <span className="text-sm text-gray-500">
                                                                            {subActivity.tasks_count ||
                                                                                0}{' '}
                                                                            tasks
                                                                        </span>
                                                                    </div>
                                                                    <div className="flex items-center gap-1">
                                                                        <Button
                                                                            variant="ghost"
                                                                            size="sm"
                                                                            onClick={() =>
                                                                                openEditSubActivityModal(
                                                                                    subActivity,
                                                                    activityType
                                                                                )
                                                                            }
                                                                            title="Edit"
                                                                        >
                                                                            <Edit2 className="w-4 h-4" />
                                                                        </Button>
                                                                        {(!subActivity.tasks_count ||
                                                                            subActivity.tasks_count ===
                                                                                0) && (
                                                                            <Button
                                                                                variant="ghost"
                                                                                size="sm"
                                                                                onClick={() =>
                                                                                    openDeleteModal(
                                                                                        'subActivity',
                                                                                        subActivity,
                                                                                        activityType.id
                                                                                    )
                                                                                }
                                                                                className="text-red-600 hover:text-red-700 hover:bg-red-50"
                                                                                title="Delete"
                                                                            >
                                                                                <Trash2 className="w-4 h-4" />
                                                                            </Button>
                                                                        )}
                                                                    </div>
                                                                </div>
                                                            )
                                                        )}

                                                        {/* Add Sub-Activity Button */}
                                                        {(!activityType.sub_activities ||
                                                            activityType.sub_activities.length ===
                                                                0) && (
                                                            <div className="text-sm text-gray-500 py-2">
                                                                No sub-activities yet.
                                                            </div>
                                                        )}
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            onClick={() =>
                                                                openCreateSubActivityModal(
                                                                    activityType
                                                                )
                                                            }
                                                            className="text-primary hover:text-primary"
                                                        >
                                                            <Plus className="w-4 h-4 mr-1" />
                                                            Add Sub-Activity
                                                        </Button>
                                                    </div>
                                                </motion.div>
                                            )}
                                        </AnimatePresence>
                                    </div>
                                </motion.div>
                            ))}
                        </AnimatePresence>
                    </div>
                )}
            </div>

            {/* Create/Edit Activity Type Modal */}
            <Dialog
                open={activityTypeModalOpen}
                onClose={() => setActivityTypeModalOpen(false)}
                className="max-w-2xl"
            >
                <DialogHeader onClose={() => setActivityTypeModalOpen(false)}>
                    <DialogTitle>
                        {editingActivityType
                            ? 'Edit Activity Type'
                            : 'Create Activity Type'}
                    </DialogTitle>
                    <DialogDescription>
                        {editingActivityType
                            ? 'Update the activity type details below.'
                            : 'Add a new activity type for task categorization.'}
                    </DialogDescription>
                </DialogHeader>
                <form onSubmit={handleActivityTypeSubmit}>
                    <DialogContent>
                        <div className="space-y-4">
                            {/* Name */}
                            <div>
                                <Label htmlFor="activity-type-name">Name *</Label>
                                <Input
                                    id="activity-type-name"
                                    type="text"
                                    value={activityTypeForm.name}
                                    onChange={(e) =>
                                        setActivityTypeForm({
                                            ...activityTypeForm,
                                            name: e.target.value,
                                        })
                                    }
                                    placeholder="Enter activity type name"
                                    required
                                />
                            </div>

                            {/* Department Assignment (Super Admin only) */}
                            {isSuperAdmin && editingActivityType && (
                                <div>
                                    <Label>Department Assignment</Label>
                                    <div className="max-h-64 overflow-y-auto mt-2 space-y-4">
                                        {departmentsByBusinessUnit.map((group) => (
                                            <div
                                                key={group.businessUnit.id}
                                                className="border border-gray-200 rounded-lg overflow-hidden"
                                            >
                                                <div className="bg-gray-50 px-3 py-2 border-b border-gray-200">
                                                    <span className="font-medium text-sm text-gray-900">
                                                        {group.businessUnit.code} -{' '}
                                                        {group.businessUnit.name}
                                                    </span>
                                                </div>
                                                <div className="p-3 space-y-2">
                                                    {group.departments.map(
                                                        (dept) => {
                                                            const isAlreadyAssigned =
                                                                editingActivityType.assigned_department_ids?.includes(
                                                                    dept.id
                                                                ) || false;
                                                            const isSelected =
                                                                selectedDepartmentIds.includes(
                                                                    dept.id
                                                                );

                                                            return (
                                                                <label
                                                                    key={dept.id}
                                                                    className={`flex items-center gap-2 p-2 rounded cursor-pointer ${
                                                                        isAlreadyAssigned &&
                                                                        !isSelected
                                                                            ? 'bg-green-50'
                                                                            : 'hover:bg-gray-50'
                                                                    }`}
                                                                >
                                                                    <input
                                                                        type="checkbox"
                                                                        checked={
                                                                            isSelected
                                                                        }
                                                                        onChange={() =>
                                                                            handleDepartmentToggle(
                                                                                dept.id
                                                                            )
                                                                        }
                                                                        className="w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary"
                                                                    />
                                                                    <span className="text-sm text-gray-900">
                                                                        {dept.code} -{' '}
                                                                        {dept.name}
                                                                    </span>
                                                                    {isAlreadyAssigned && (
                                                                        <span className="ml-auto text-xs text-green-600 font-medium">
                                                                            Already
                                                                            assigned
                                                                        </span>
                                                                    )}
                                                                </label>
                                                            );
                                                        }
                                                    )}
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                    {selectedDepartmentIds.length > 0 && (
                                        <div className="mt-3 flex flex-wrap gap-2">
                                            {selectedDepartmentIds.map((id) => {
                                                const dept = departments.find(
                                                    (d) => d.id === id
                                                );
                                                return dept ? (
                                                    <Badge
                                                        key={id}
                                                        variant="info"
                                                    >
                                                        {dept.code}
                                                    </Badge>
                                                ) : null;
                                            })}
                                            <span className="text-sm text-gray-600">
                                                {selectedDepartmentIds.length}{' '}
                                                selected
                                            </span>
                                        </div>
                                    )}
                                </div>
                            )}
                        </div>
                    </DialogContent>
                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => setActivityTypeModalOpen(false)}
                            disabled={isSubmittingActivityType}
                        >
                            Cancel
                        </Button>
                        {isSuperAdmin && editingActivityType && (
                            <Button
                                type="button"
                                onClick={handleAssignDepartments}
                                disabled={
                                    isSubmittingActivityType ||
                                    selectedDepartmentIds.length === 0
                                }
                                loading={isSubmittingActivityType}
                            >
                                <Users className="w-4 h-4 mr-2" />
                                Assign ({selectedDepartmentIds.length})
                            </Button>
                        )}
                        <Button
                            type="submit"
                            disabled={isSubmittingActivityType}
                            loading={isSubmittingActivityType}
                        >
                            <Check className="w-4 h-4 mr-2" />
                            {editingActivityType ? 'Update' : 'Create'}
                        </Button>
                    </DialogFooter>
                </form>
            </Dialog>

            {/* Create/Edit Sub-Activity Modal */}
            <Dialog
                open={subActivityModalOpen}
                onClose={() => setSubActivityModalOpen(false)}
            >
                <DialogHeader onClose={() => setSubActivityModalOpen(false)}>
                    <DialogTitle>
                        {editingSubActivity
                            ? 'Edit Sub-Activity'
                            : 'Create Sub-Activity'}
                    </DialogTitle>
                    <DialogDescription>
                        {parentActivityType
                            ? `Adding sub-activity to "${parentActivityType.name}"`
                            : 'Add a new sub-activity for detailed task categorization.'}
                    </DialogDescription>
                </DialogHeader>
                <form onSubmit={handleSubActivitySubmit}>
                    <DialogContent>
                        <div className="space-y-4">
                            {/* Parent Activity Type (read-only) */}
                            {parentActivityType && (
                                <div>
                                    <Label>Parent Activity Type</Label>
                                    <div className="flex items-center gap-2 mt-1 p-2 bg-gray-50 rounded-lg">
                                        <span className="text-gray-900">
                                            {parentActivityType.name}
                                        </span>
                                        {parentActivityType.code && (
                                            <span className="text-[11px] text-gray-400 bg-gray-100 px-1.5 py-0.5 rounded">
                                                {parentActivityType.code}
                                            </span>
                                        )}
                                    </div>
                                </div>
                            )}

                            {/* Name */}
                            <div>
                                <Label htmlFor="sub-activity-name">Name *</Label>
                                <Input
                                    id="sub-activity-name"
                                    type="text"
                                    value={subActivityForm.name}
                                    onChange={(e) =>
                                        setSubActivityForm({
                                            ...subActivityForm,
                                            name: e.target.value,
                                        })
                                    }
                                    placeholder="Enter sub-activity name"
                                    required
                                />
                            </div>
                        </div>
                    </DialogContent>
                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => setSubActivityModalOpen(false)}
                            disabled={isSubmittingSubActivity}
                        >
                            Cancel
                        </Button>
                        <Button
                            type="submit"
                            disabled={isSubmittingSubActivity}
                            loading={isSubmittingSubActivity}
                        >
                            <Check className="w-4 h-4 mr-2" />
                            {editingSubActivity ? 'Update' : 'Create'}
                        </Button>
                    </DialogFooter>
                </form>
            </Dialog>

            {/* Delete Confirmation Modal */}
            <ConfirmDialog
                open={deleteModalOpen}
                onClose={() => {
                    setDeleteModalOpen(false);
                    setItemToDelete(null);
                }}
                onConfirm={handleDeleteConfirm}
                title={`Delete ${
                    itemToDelete?.type === 'activityType'
                        ? 'Activity Type'
                        : 'Sub-Activity'
                }?`}
                description={
                    itemToDelete
                        ? `Are you sure you want to delete "${
                              itemToDelete.item as ActivityType | SubActivity
                          }.name"? This action cannot be undone.`
                        : ''
                }
                confirmText="Delete"
                cancelText="Cancel"
                variant="danger"
                loading={isDeleting}
            />
        </>
    );
}

export default ActivityConfiguration;
