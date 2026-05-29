import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { motion, AnimatePresence } from 'framer-motion';
import { Plus, Search, X, Check, Filter, Building2 } from 'lucide-react';
import { toast } from 'sonner';
import { SubActivity, SubActivityFormData, ActivityType } from '@/types/admin';
import { PageProps } from '@/types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select } from '@/components/ui/select';
import SubActivitiesTable from '@/components/admin/sub-activities/SubActivitiesTable';

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

interface BusinessUnit {
    id: number;
    code: string;
    name: string;
}

interface Props extends PageProps {
    subActivities: LaravelPagination<SubActivity>;
    activityTypes: ActivityType[];
    businessUnits: BusinessUnit[];
    isSuperAdmin: boolean;
    filters: {
        search?: string;
        activity_type_id?: number;
        business_unit_id?: number;
        status?: string;
    };
}

function Index({ subActivities, activityTypes, businessUnits, isSuperAdmin, filters }: Props) {
    const [search, setSearch] = useState(filters.search || '');
    const [activityTypeFilter, setActivityTypeFilter] = useState(
        filters.activity_type_id ? filters.activity_type_id.toString() : ''
    );
    const [businessUnitFilter, setBusinessUnitFilter] = useState(
        filters.business_unit_id ? filters.business_unit_id.toString() : ''
    );
    const [editingId, setEditingId] = useState<number | null>(null);
    const [isCreating, setIsCreating] = useState(false);
    const [formData, setFormData] = useState<SubActivityFormData>({
        name: '',
        activity_type_id: 0,
    });
    const [isSubmitting, setIsSubmitting] = useState(false);

    // Build current filters for navigation
    const buildFilters = (overrides: Record<string, string | undefined> = {}) => {
        const currentFilters: Record<string, string | undefined> = {
            search: search || undefined,
            activity_type_id: activityTypeFilter || undefined,
            business_unit_id: businessUnitFilter || undefined,
        };
        return { ...currentFilters, ...overrides };
    };

    // Debounced search
    React.useEffect(() => {
        const timer = setTimeout(() => {
            router.get(
                route('admin.sub-activities.index'),
                buildFilters(),
                { preserveState: true, replace: true }
            );
        }, 300);

        return () => clearTimeout(timer);
    }, [search]);

    // Handle activity type filter change
    const handleActivityTypeFilterChange = (value: string | number) => {
        const stringValue = value.toString();
        setActivityTypeFilter(stringValue);
        router.get(
            route('admin.sub-activities.index'),
            buildFilters({ activity_type_id: stringValue || undefined }),
            { preserveState: true, replace: true }
        );
    };

    // Handle business unit filter change (super admin only)
    const handleBusinessUnitFilterChange = (value: string | number) => {
        const stringValue = value.toString();
        setBusinessUnitFilter(stringValue);
        // Reset activity type filter when business unit changes
        setActivityTypeFilter('');
        router.get(
            route('admin.sub-activities.index'),
            { search: search || undefined, business_unit_id: stringValue || undefined },
            { preserveState: true, replace: true }
        );
    };

    const handleCreate = () => {
        setIsCreating(true);
        setEditingId(null);
        setFormData({
            name: '',
            activity_type_id: activityTypeFilter ? parseInt(activityTypeFilter) : 0,
        });
    };

    const handleEdit = (subActivity: SubActivity) => {
        if (!subActivity.activity_type) return;
        
        setEditingId(subActivity.id);
        setIsCreating(false);
        setFormData({
            name: subActivity.name,
            activity_type_id: subActivity.activity_type.id,
        });
    };

    const handleCancel = () => {
        setIsCreating(false);
        setEditingId(null);
        setFormData({ name: '', activity_type_id: 0 });
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        if (!formData.name.trim()) {
            toast.error('Sub-activity name is required');
            return;
        }

        if (!formData.activity_type_id) {
            toast.error('Activity type is required');
            return;
        }

        setIsSubmitting(true);

        if (isCreating) {
            router.post(route('admin.sub-activities.store'), formData as Record<string, any>, {
                onSuccess: () => {
                    toast.success('Sub-activity created successfully');
                    handleCancel();
                },
                onError: (errors: any) => {
                    toast.error(errors.name || 'Failed to create sub-activity');
                },
                onFinish: () => setIsSubmitting(false),
            });
        } else if (editingId) {
            router.put(route('admin.sub-activities.update', { sub_activity: editingId }), formData as Record<string, any>, {
                onSuccess: () => {
                    toast.success('Sub-activity updated successfully');
                    handleCancel();
                },
                onError: (errors: any) => {
                    toast.error(errors.name || 'Failed to update sub-activity');
                },
                onFinish: () => setIsSubmitting(false),
            });
        }
    };

    const handleDelete = (subActivity: SubActivity) => {
        if (subActivity.usage_count && subActivity.usage_count > 0) {
            toast.error(
                'Cannot delete sub-activity that is being used by tasks. Consider deactivating it instead.'
            );
            return;
        }

        if (!confirm('Are you sure you want to delete this sub-activity?')) {
            return;
        }

        router.delete(route('admin.sub-activities.destroy', { sub_activity: subActivity.id }), {
            onSuccess: () => {
                toast.success('Sub-activity deleted successfully');
            },
            onError: () => {
                toast.error('Failed to delete sub-activity');
            },
        });
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

    // Group sub-activities by activity type
    const groupedSubActivities = React.useMemo(() => {
        const groups: Record<number, { activityType: ActivityType; subActivities: SubActivity[] }> =
            {};

        subActivities.data.forEach((subActivity) => {
            if (!subActivity.activity_type) return;
            
            const typeId = subActivity.activity_type.id;
            if (!groups[typeId]) {
                groups[typeId] = {
                    activityType: subActivity.activity_type,
                    subActivities: [],
                };
            }
            groups[typeId].subActivities.push(subActivity);
        });

        return Object.values(groups);
    }, [subActivities.data]);

    return (
        <>
            <Head title="Sub-Activities" />

            <div className="p-6 space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900">Sub-Activities</h1>
                        <p className="text-sm text-gray-600 mt-1">
                            Manage sub-activities for detailed task categorization
                        </p>
                    </div>
                    <Button onClick={handleCreate} disabled={isCreating}>
                        <Plus className="w-4 h-4 mr-2" />
                        Add Sub-Activity
                    </Button>
                </div>

                {/* Search and Filters */}
                <div className="bg-white rounded-xl border border-gray-200 p-4">
                    <div className={`grid grid-cols-1 gap-4 ${isSuperAdmin ? 'md:grid-cols-3' : 'md:grid-cols-2'}`}>
                        <div className="relative">
                            <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                            <Input
                                type="text"
                                placeholder="Search sub-activities..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                className="pl-10"
                            />
                        </div>
                        {isSuperAdmin && businessUnits.length > 0 && (
                            <div className="relative">
                                <Building2 className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none z-10" />
                                <Select
                                    value={businessUnitFilter}
                                    onChange={handleBusinessUnitFilterChange}
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
                        <div className="relative">
                            <Filter className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none z-10" />
                            <Select
                                value={activityTypeFilter}
                                onChange={handleActivityTypeFilterChange}
                                options={[
                                    { value: '', label: 'All Activity Types' },
                                    ...activityTypes.map((type) => ({
                                        value: type.id.toString(),
                                        label: type.department_prefix 
                                            ? `${type.name} (${type.department_prefix})` 
                                            : type.name,
                                    })),
                                ]}
                                placeholder="All Activity Types"
                                className="pl-10"
                            />
                        </div>
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
                                    Create Sub-Activity
                                </h3>
                                <form onSubmit={handleSubmit} className="space-y-4">
                                    <div>
                                        <Label htmlFor="activity_type_id">Activity Type *</Label>
                                        <Select
                                            value={formData.activity_type_id.toString()}
                                            onChange={(value) =>
                                                setFormData({
                                                    ...formData,
                                                    activity_type_id: parseInt(value as string),
                                                })
                                            }
                                            options={activityTypes.map((type) => ({
                                                value: type.id.toString(),
                                                label: type.department_prefix
                                                    ? `${type.name} (${type.department_prefix})`
                                                    : type.name,
                                            }))}
                                            placeholder="Select activity type"
                                        />
                                    </div>

                                    <div>
                                        <Label htmlFor="name">Name *</Label>
                                        <Input
                                            id="name"
                                            type="text"
                                            value={formData.name}
                                            onChange={(e) =>
                                                setFormData({ ...formData, name: e.target.value })
                                            }
                                            placeholder="Enter sub-activity name"
                                            required
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

                <SubActivitiesTable
                    subActivities={subActivities}
                    groupedSubActivities={groupedSubActivities}
                    activityTypes={activityTypes}
                    editingId={editingId}
                    formData={formData}
                    isSubmitting={isSubmitting}
                    search={search}
                    activityTypeFilter={activityTypeFilter}
                    buildFilters={buildFilters}
                    getColorClasses={getColorClasses}
                    onCancel={handleCancel}
                    onDelete={handleDelete}
                    onEdit={handleEdit}
                    onFormDataChange={setFormData}
                    onSubmit={handleSubmit}
                />
            </div>
        </>
    );
}

export default Index;
