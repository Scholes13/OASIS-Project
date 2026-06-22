import { router } from '@inertiajs/react';
import { motion } from 'framer-motion';
import { Building2, Check, Edit2, Trash2, Users, X } from 'lucide-react';

import { Badge } from '@/components/ui/Badge';
import { Button } from '@/components/ui/button';
import { ColorPicker } from '@/components/admin/ColorPicker';
import { EmptyState } from '@/components/ui/empty-state';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { ActivityType, ActivityTypeFormData } from '@/types/admin';

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

interface ExtendedActivityType extends ActivityType {
    code?: string;
    departments_count?: number;
    departments?: Department[];
    assigned_department_ids?: number[];
}

interface LaravelPagination<T> {
    data: T[];
    current_page: number;
    last_page: number;
    total: number;
    from: number | null;
    to: number | null;
}

interface ActivityTypesTableProps {
    activityTypes: LaravelPagination<ExtendedActivityType>;
    editingId: number | null;
    formData: ActivityTypeFormData & { department_id?: string };
    isSubmitting: boolean;
    isSuperAdmin: boolean;
    search: string;
    buildFilters: () => Record<string, string | undefined>;
    getColorClasses: (color: string) => string;
    onCancel: () => void;
    onDelete: (activityType: ExtendedActivityType) => void;
    onEdit: (activityType: ExtendedActivityType) => void;
    onFormDataChange: (formData: ActivityTypeFormData & { department_id?: string }) => void;
    onOpenAssignModal: (activityType: ExtendedActivityType) => void;
    onSubmit: (event: React.FormEvent) => void;
}

export function ActivityTypesTable({
    activityTypes,
    editingId,
    formData,
    isSubmitting,
    isSuperAdmin,
    search,
    buildFilters,
    getColorClasses,
    onCancel,
    onDelete,
    onEdit,
    onFormDataChange,
    onOpenAssignModal,
    onSubmit,
}: ActivityTypesTableProps) {
    return (
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
                    <div className="hidden md:grid md:grid-cols-12 gap-4 px-6 py-3 bg-gray-50 border-b border-gray-200 text-sm font-medium text-gray-600">
                        <div className="col-span-4">Activity Type</div>
                        {isSuperAdmin && <div className="col-span-2 text-center">Departments</div>}
                        <div className={`${isSuperAdmin ? 'col-span-2' : 'col-span-3'} text-center`}>Sub-Activities</div>
                        <div className={`${isSuperAdmin ? 'col-span-2' : 'col-span-3'} text-center`}>Tasks</div>
                        <div className="col-span-2 text-right">Actions</div>
                    </div>

                    <div className="divide-y divide-gray-200">
                        {activityTypes.data.map((activityType) => (
                            <motion.div
                                key={activityType.id}
                                layout
                                className="p-6 hover:bg-gray-50 transition-colors"
                            >
                                {editingId === activityType.id ? (
                                    <form onSubmit={onSubmit} className="space-y-4">
                                        <div>
                                            <Label htmlFor={`edit-name-${activityType.id}`}>Name *</Label>
                                            <Input
                                                id={`edit-name-${activityType.id}`}
                                                type="text"
                                                value={formData.name}
                                                onChange={(event) => onFormDataChange({
                                                    ...formData,
                                                    name: event.target.value,
                                                })}
                                                placeholder="Enter activity type name"
                                                required
                                            />
                                        </div>

                                        <div>
                                            <Label htmlFor={`edit-color-${activityType.id}`}>Color *</Label>
                                            <ColorPicker
                                                label=""
                                                value={formData.color}
                                                onChange={(color) => onFormDataChange({
                                                    ...formData,
                                                    color,
                                                })}
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
                                    <div className="grid grid-cols-1 md:grid-cols-12 gap-4 items-center">
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

                                        {isSuperAdmin && (
                                            <div className="col-span-2 text-center">
                                                <span className="inline-flex items-center gap-1 text-sm text-gray-600">
                                                    <Building2 className="w-4 h-4" />
                                                    {activityType.departments_count || 0}
                                                </span>
                                            </div>
                                        )}

                                        <div className={`${isSuperAdmin ? 'col-span-2' : 'col-span-3'} text-center`}>
                                            <span className="text-sm text-gray-600">
                                                {activityType.sub_activities_count || 0} sub-activities
                                            </span>
                                        </div>

                                        <div className={`${isSuperAdmin ? 'col-span-2' : 'col-span-3'} text-center`}>
                                            <span className="text-sm text-gray-600">
                                                {activityType.usage_count || 0} tasks
                                            </span>
                                        </div>

                                        <div className="col-span-2 flex items-center justify-end gap-2">
                                            {isSuperAdmin && (
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={() => onOpenAssignModal(activityType)}
                                                    title="Assign to Departments"
                                                >
                                                    <Users className="w-4 h-4" />
                                                </Button>
                                            )}
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                onClick={() => onEdit(activityType)}
                                            >
                                                <Edit2 className="w-4 h-4" />
                                            </Button>
                                            {(!activityType.sub_activities_count || activityType.sub_activities_count === 0) &&
                                                (!activityType.usage_count || activityType.usage_count === 0) && (
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={() => onDelete(activityType)}
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

            {activityTypes.last_page > 1 && (
                <div className="px-6 py-4 border-t border-gray-200 flex items-center justify-between">
                    <div className="text-sm text-gray-600">
                        Showing {activityTypes.from} to {activityTypes.to} of {activityTypes.total} results
                    </div>
                    <div className="flex items-center gap-2">
                        {activityTypes.current_page > 1 && (
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() => router.get(
                                    route('admin.activity-types.index'),
                                    {
                                        ...buildFilters(),
                                        page: activityTypes.current_page - 1,
                                    },
                                    { preserveState: true }
                                )}
                            >
                                Previous
                            </Button>
                        )}
                        {activityTypes.current_page < activityTypes.last_page && (
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() => router.get(
                                    route('admin.activity-types.index'),
                                    {
                                        ...buildFilters(),
                                        page: activityTypes.current_page + 1,
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
        </div>
    );
}

export default ActivityTypesTable;
