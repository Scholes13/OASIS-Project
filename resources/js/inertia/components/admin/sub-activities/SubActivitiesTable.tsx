import { router } from '@inertiajs/react';
import { motion } from 'framer-motion';
import { Check, Edit2, Trash2, X } from 'lucide-react';

import { Badge } from '@/components/ui/Badge';
import { Button } from '@/components/ui/button';
import { EmptyState } from '@/components/ui/empty-state';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select } from '@/components/ui/select';
import { ActivityType, SubActivity, SubActivityFormData } from '@/types/admin';

interface LaravelPagination<T> {
    data: T[];
    current_page: number;
    last_page: number;
    total: number;
    from: number | null;
    to: number | null;
}

interface SubActivityGroup {
    activityType: ActivityType;
    subActivities: SubActivity[];
}

interface SubActivitiesTableProps {
    subActivities: LaravelPagination<SubActivity>;
    groupedSubActivities: SubActivityGroup[];
    activityTypes: ActivityType[];
    editingId: number | null;
    formData: SubActivityFormData;
    isSubmitting: boolean;
    search: string;
    activityTypeFilter: string;
    buildFilters: () => Record<string, string | undefined>;
    getColorClasses: (color: string) => string;
    onCancel: () => void;
    onDelete: (subActivity: SubActivity) => void;
    onEdit: (subActivity: SubActivity) => void;
    onFormDataChange: (formData: SubActivityFormData) => void;
    onSubmit: (event: React.FormEvent) => void;
}

export function SubActivitiesTable({
    subActivities,
    groupedSubActivities,
    activityTypes,
    editingId,
    formData,
    isSubmitting,
    search,
    activityTypeFilter,
    buildFilters,
    getColorClasses,
    onCancel,
    onDelete,
    onEdit,
    onFormDataChange,
    onSubmit,
}: SubActivitiesTableProps) {
    if (subActivities.data.length === 0) {
        return (
            <div className="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <EmptyState
                    title="No sub-activities found"
                    description={
                        search || activityTypeFilter
                            ? 'Try adjusting your search or filters'
                            : 'Get started by creating your first sub-activity'
                    }
                />
            </div>
        );
    }

    return (
        <>
            <div className="space-y-6">
                {groupedSubActivities.map((group) => (
                    <div
                        key={group.activityType.id}
                        className="bg-white rounded-xl border border-gray-200 overflow-hidden"
                    >
                        <div className="px-6 py-4 bg-gray-50 border-b border-gray-200">
                            <div className="flex items-center gap-3">
                                <Badge className={getColorClasses(group.activityType.color)}>
                                    {group.activityType.department_prefix
                                        ? `${group.activityType.name} (${group.activityType.department_prefix})`
                                        : group.activityType.name}
                                </Badge>
                                <span className="text-sm text-gray-600">
                                    {group.subActivities.length} sub-activities
                                </span>
                            </div>
                        </div>

                        <div className="divide-y divide-gray-200">
                            {group.subActivities.map((subActivity) => (
                                <motion.div
                                    key={subActivity.id}
                                    layout
                                    className="p-6 hover:bg-gray-50 transition-colors"
                                >
                                    {editingId === subActivity.id ? (
                                        <form onSubmit={onSubmit} className="space-y-4">
                                            <div>
                                                <Label htmlFor={`edit-activity-type-${subActivity.id}`}>
                                                    Activity Type *
                                                </Label>
                                                <Select
                                                    value={formData.activity_type_id.toString()}
                                                    onChange={(value) => onFormDataChange({
                                                        ...formData,
                                                        activity_type_id: parseInt(value as string),
                                                    })}
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
                                                <Label htmlFor={`edit-name-${subActivity.id}`}>Name *</Label>
                                                <Input
                                                    id={`edit-name-${subActivity.id}`}
                                                    type="text"
                                                    value={formData.name}
                                                    onChange={(event) => onFormDataChange({
                                                        ...formData,
                                                        name: event.target.value,
                                                    })}
                                                    placeholder="Enter sub-activity name"
                                                    required
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
                                                    onClick={onCancel}
                                                    disabled={isSubmitting}
                                                >
                                                    <X className="w-4 h-4 mr-2" />
                                                    Cancel
                                                </Button>
                                            </div>
                                        </form>
                                    ) : (
                                        <div className="flex items-center justify-between">
                                            <div className="flex items-center gap-4">
                                                <span className="font-medium text-gray-900">
                                                    {subActivity.name}
                                                </span>
                                                <span className="text-sm text-gray-600">
                                                    {subActivity.usage_count || 0} tasks
                                                </span>
                                            </div>

                                            <div className="flex items-center gap-2">
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={() => onEdit(subActivity)}
                                                >
                                                    <Edit2 className="w-4 h-4" />
                                                </Button>
                                                {(!subActivity.usage_count || subActivity.usage_count === 0) && (
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={() => onDelete(subActivity)}
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
                    </div>
                ))}
            </div>

            {subActivities.last_page > 1 && (
                <div className="bg-white rounded-xl border border-gray-200 px-6 py-4 flex items-center justify-between">
                    <div className="text-sm text-gray-600">
                        Showing {subActivities.from} to {subActivities.to} of {subActivities.total} results
                    </div>
                    <div className="flex items-center gap-2">
                        {subActivities.current_page > 1 && (
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() => router.get(
                                    route('admin.sub-activities.index'),
                                    {
                                        ...buildFilters(),
                                        page: subActivities.current_page - 1,
                                    },
                                    { preserveState: true }
                                )}
                            >
                                Previous
                            </Button>
                        )}
                        {subActivities.current_page < subActivities.last_page && (
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() => router.get(
                                    route('admin.sub-activities.index'),
                                    {
                                        ...buildFilters(),
                                        page: subActivities.current_page + 1,
                                    },
                                    { preserveState: true }
                                )}
                            >
                                Next
                            </Button>
                        )}
                    </div>
                </div>
            )}
        </>
    );
}

export default SubActivitiesTable;
