import React, { useState, useMemo } from 'react';
import { Head, router } from '@inertiajs/react';
import { toast } from 'sonner';
import { PageProps } from '@/types';
import { useDebouncedSearch } from '@/hooks/useDebouncedSearch';
import ActivityTypeAccordion from '@/components/admin/activity/ActivityTypeAccordion';
import ActivityConfigurationHeader from '@/components/admin/activity/ActivityConfigurationHeader';
import ActivityConfigurationFilters from '@/components/admin/activity/ActivityConfigurationFilters';
import ActivityConfigurationModals from '@/components/admin/activity/ActivityConfigurationModals';

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
    const { value: search, debouncedValue: debouncedSearch, setValue: setSearch } = useDebouncedSearch(filters.search || '');
    const [businessUnitId, setBusinessUnitId] = useState(filters.business_unit_id || '');

    const [activityTypeModalOpen, setActivityTypeModalOpen] = useState(false);
    const [editingActivityType, setEditingActivityType] = useState<ActivityType | null>(null);
    const [activityTypeForm, setActivityTypeForm] = useState({
        name: '',
    });
    const [selectedDepartmentIds, setSelectedDepartmentIds] = useState<number[]>([]);
    const [isSubmittingActivityType, setIsSubmittingActivityType] = useState(false);

    const [subActivityModalOpen, setSubActivityModalOpen] = useState(false);
    const [parentActivityType, setParentActivityType] = useState<ActivityType | null>(null);
    const [editingSubActivity, setEditingSubActivity] = useState<SubActivity | null>(null);
    const [subActivityForm, setSubActivityForm] = useState({
        name: '',
    });
    const [isSubmittingSubActivity, setIsSubmittingSubActivity] = useState(false);

    const [deleteModalOpen, setDeleteModalOpen] = useState(false);
    const [itemToDelete, setItemToDelete] = useState<{
        type: 'activityType' | 'subActivity';
        item: ActivityType | SubActivity;
        parentId?: number;
    } | null>(null);
    const [isDeleting, setIsDeleting] = useState(false);

    const [expandedIds, setExpandedIds] = useState<Set<number>>(new Set());

    const autoExpandedIds = useMemo(() => {
        if (!search.trim()) return new Set<number>();
        const ids = new Set<number>();
        activityTypes.forEach((at) => {
            if (at.name.toLowerCase().includes(search.toLowerCase())) {
                ids.add(at.id);
                return;
            }
            const matchingSub = at.sub_activities?.some((sub) =>
                sub.name.toLowerCase().includes(search.toLowerCase())
            );
            if (matchingSub) {
                ids.add(at.id);
            }
        });
        return ids;
    }, [search, activityTypes]);

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

    const handleBusinessUnitChange = (value: string | number) => {
        const stringValue = value.toString();
        setBusinessUnitId(stringValue);
        router.get(
            route('admin.activity-configuration.index'),
            { search: search || undefined, business_unit_id: stringValue || undefined },
            { preserveState: true, replace: true }
        );
    };

    const openCreateActivityTypeModal = () => {
        setEditingActivityType(null);
        setActivityTypeForm({ name: '' });
        setSelectedDepartmentIds([]);
        setActivityTypeModalOpen(true);
    };

    const openEditActivityTypeModal = (activityType: ActivityType) => {
        setEditingActivityType(activityType);
        setActivityTypeForm({
            name: activityType.name,
        });
        setSelectedDepartmentIds(activityType.assigned_department_ids || []);
        setActivityTypeModalOpen(true);
    };

    const handleActivityTypeSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        if (!activityTypeForm.name.trim()) {
            toast.error('Activity type name is required');
            return;
        }

        setIsSubmittingActivityType(true);
        setActivityTypeModalOpen(false);

        if (editingActivityType) {
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
                        setActivityTypeModalOpen(true);
                    },
                    onFinish: () => setIsSubmittingActivityType(false),
                }
            );
        } else {
            router.post(route('admin.activity-types.store'), activityTypeForm as any, {
                onSuccess: () => {
                    toast.success('Activity type created successfully');
                },
                onError: (errors: Record<string, string>) => {
                    toast.error(errors.name || 'Failed to create activity type');
                    setActivityTypeModalOpen(true);
                },
                onFinish: () => setIsSubmittingActivityType(false),
            });
        }
    };

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

    const handleDepartmentToggle = (deptId: number) => {
        setSelectedDepartmentIds((prev) =>
            prev.includes(deptId)
                ? prev.filter((id) => id !== deptId)
                : [...prev, deptId]
        );
    };

    const openCreateSubActivityModal = (parentActivityType: ActivityType) => {
        setParentActivityType(parentActivityType);
        setEditingSubActivity(null);
        setSubActivityForm({ name: '' });
        setSubActivityModalOpen(true);
    };

    const openEditSubActivityModal = (subActivity: SubActivity, parent: ActivityType) => {
        setParentActivityType(parent);
        setEditingSubActivity(subActivity);
        setSubActivityForm({ name: subActivity.name });
        setSubActivityModalOpen(true);
    };

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
        setSubActivityModalOpen(false);

        if (editingSubActivity) {
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
                        setSubActivityModalOpen(true);
                    },
                    onFinish: () => setIsSubmittingSubActivity(false),
                }
            );
        } else {
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
                        setSubActivityModalOpen(true);
                    },
                    onFinish: () => setIsSubmittingSubActivity(false),
                }
            );
        }
    };

    const openDeleteModal = (
        type: 'activityType' | 'subActivity',
        item: ActivityType | SubActivity,
        parentId?: number
    ) => {
        setItemToDelete({ type, item, parentId });
        setDeleteModalOpen(true);
    };

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

    const isExpanded = (id: number) => {
        return expandedIds.has(id) || autoExpandedIds.has(id);
    };

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
                <ActivityConfigurationHeader
                    isSuperAdmin={isSuperAdmin}
                    onCreateActivityType={openCreateActivityTypeModal}
                />

                <ActivityConfigurationFilters
                    search={search}
                    businessUnitId={businessUnitId}
                    businessUnits={businessUnits}
                    isSuperAdmin={isSuperAdmin}
                    onSearchChange={setSearch}
                    onBusinessUnitChange={handleBusinessUnitChange}
                />

                <ActivityTypeAccordion
                    activityTypes={filteredActivityTypes}
                    search={search}
                    isSuperAdmin={isSuperAdmin}
                    isExpanded={isExpanded}
                    onToggleExpansion={toggleExpansion}
                    onCreateActivityType={openCreateActivityTypeModal}
                    onEditActivityType={openEditActivityTypeModal}
                    onCreateSubActivity={openCreateSubActivityModal}
                    onEditSubActivity={openEditSubActivityModal}
                    onDelete={openDeleteModal}
                />
            </div>

            <ActivityConfigurationModals
                activityTypeModalOpen={activityTypeModalOpen}
                editingActivityType={editingActivityType}
                activityTypeForm={activityTypeForm}
                isSuperAdmin={isSuperAdmin}
                isSubmittingActivityType={isSubmittingActivityType}
                selectedDepartmentIds={selectedDepartmentIds}
                departments={departments}
                departmentsByBusinessUnit={departmentsByBusinessUnit}
                subActivityModalOpen={subActivityModalOpen}
                parentActivityType={parentActivityType}
                editingSubActivity={editingSubActivity}
                subActivityForm={subActivityForm}
                isSubmittingSubActivity={isSubmittingSubActivity}
                deleteModalOpen={deleteModalOpen}
                itemToDelete={itemToDelete}
                isDeleting={isDeleting}
                onActivityTypeClose={() => setActivityTypeModalOpen(false)}
                onActivityTypeSubmit={handleActivityTypeSubmit}
                onActivityTypeFormChange={setActivityTypeForm}
                onDepartmentToggle={handleDepartmentToggle}
                onAssignDepartments={handleAssignDepartments}
                onSubActivityClose={() => setSubActivityModalOpen(false)}
                onSubActivitySubmit={handleSubActivitySubmit}
                onSubActivityFormChange={setSubActivityForm}
                onDeleteClose={() => {
                    setDeleteModalOpen(false);
                    setItemToDelete(null);
                }}
                onDeleteConfirm={handleDeleteConfirm}
            />
        </>
    );
}

export default ActivityConfiguration;
