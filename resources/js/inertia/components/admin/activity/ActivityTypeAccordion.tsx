import { AnimatePresence, motion } from 'framer-motion';
import {
    Building2,
    ChevronDown,
    ChevronRight,
    ClipboardList,
    Edit2,
    Folder,
    Plus,
    Trash2,
} from 'lucide-react';

import { Button } from '@/components/ui/button';
import { EmptyState } from '@/components/ui/empty-state';

interface Department {
    id: number;
    name: string;
    code: string;
    business_unit_id: number;
    business_unit?: { id: number; code: string; name: string };
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

interface ActivityTypeAccordionProps {
    activityTypes: ActivityType[];
    search: string;
    isSuperAdmin: boolean;
    isExpanded: (id: number) => boolean;
    onToggleExpansion: (id: number) => void;
    onCreateActivityType: () => void;
    onEditActivityType: (activityType: ActivityType) => void;
    onCreateSubActivity: (activityType: ActivityType) => void;
    onEditSubActivity: (subActivity: SubActivity, parent: ActivityType) => void;
    onDelete: (
        type: 'activityType' | 'subActivity',
        item: ActivityType | SubActivity,
        parentId?: number
    ) => void;
}

export function ActivityTypeAccordion({
    activityTypes,
    search,
    isSuperAdmin,
    isExpanded,
    onToggleExpansion,
    onCreateActivityType,
    onEditActivityType,
    onCreateSubActivity,
    onEditSubActivity,
    onDelete,
}: ActivityTypeAccordionProps) {
    if (activityTypes.length === 0) {
        return (
            <div className="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <EmptyState
                    icon={<Folder className="h-12 w-12" />}
                    title={search ? 'No activity types match your search.' : 'No activity types configured yet.'}
                    description={search ? 'Try adjusting your search query.' : 'Create your first activity type to start organizing tasks.'}
                    action={!search
                        ? {
                            label: 'Add Activity Type',
                            onClick: onCreateActivityType,
                        }
                        : undefined}
                />
            </div>
        );
    }

    return (
        <div className="space-y-2">
            <AnimatePresence>
                {activityTypes.map((activityType) => (
                    <motion.div
                        key={activityType.id}
                        initial={{ opacity: 0, y: -10 }}
                        animate={{ opacity: 1, y: 0 }}
                        exit={{ opacity: 0, y: -10 }}
                        className="bg-white rounded-xl border border-gray-200 overflow-hidden"
                    >
                        <div
                            className={`p-4 hover:bg-gray-50 transition-colors ${
                                isExpanded(activityType.id) ? 'bg-gray-50' : ''
                            }`}
                        >
                            <div className="flex items-center justify-between">
                                <div className="flex items-center gap-3">
                                    <button
                                        onClick={() => onToggleExpansion(activityType.id)}
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

                                <div className="hidden md:flex items-center gap-6 text-sm text-gray-600">
                                    <span className="flex items-center gap-1">
                                        <Building2 className="w-4 h-4" />
                                        {activityType.departments?.length || 0} depts
                                    </span>
                                    <span className="flex items-center gap-1">
                                        <Folder className="w-4 h-4" />
                                        {activityType.sub_activities_count || 0} sub-activities
                                    </span>
                                    <span className="flex items-center gap-1">
                                        <ClipboardList className="w-4 h-4" />
                                        {activityType.tasks_count || 0} tasks
                                    </span>
                                </div>

                                <div className="flex items-center gap-1">
                                    {isSuperAdmin && (
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            onClick={() => onEditActivityType(activityType)}
                                            title="Edit"
                                        >
                                            <Edit2 className="w-4 h-4" />
                                        </Button>
                                    )}
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => onCreateSubActivity(activityType)}
                                        title="Add Sub-Activity"
                                    >
                                        <Plus className="w-4 h-4" />
                                    </Button>
                                    {isSuperAdmin &&
                                        (!activityType.sub_activities_count || activityType.sub_activities_count === 0) &&
                                        (!activityType.tasks_count || activityType.tasks_count === 0) && (
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                onClick={() => onDelete('activityType', activityType)}
                                                className="text-red-600 hover:text-red-700 hover:bg-red-50"
                                                title="Delete"
                                            >
                                                <Trash2 className="w-4 h-4" />
                                            </Button>
                                        )}
                                </div>
                            </div>

                            <AnimatePresence>
                                {isExpanded(activityType.id) && (
                                    <motion.div
                                        initial={{ opacity: 0, height: 0 }}
                                        animate={{ opacity: 1, height: 'auto' }}
                                        exit={{ opacity: 0, height: 0 }}
                                        className="mt-4 pt-4 border-t border-gray-200"
                                    >
                                        <div className="space-y-2 pl-7">
                                            {activityType.sub_activities?.map((subActivity) => (
                                                <div
                                                    key={subActivity.id}
                                                    className="flex items-center justify-between p-3 rounded-lg hover:bg-gray-50 transition-colors"
                                                >
                                                    <div className="flex items-center gap-3">
                                                        <span className="text-gray-900">{subActivity.name}</span>
                                                        <span className="text-sm text-gray-500">
                                                            {subActivity.tasks_count || 0} tasks
                                                        </span>
                                                    </div>
                                                    <div className="flex items-center gap-1">
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            onClick={() => onEditSubActivity(subActivity, activityType)}
                                                            title="Edit"
                                                        >
                                                            <Edit2 className="w-4 h-4" />
                                                        </Button>
                                                        {(!subActivity.tasks_count || subActivity.tasks_count === 0) && (
                                                            <Button
                                                                variant="ghost"
                                                                size="sm"
                                                                onClick={() => onDelete('subActivity', subActivity, activityType.id)}
                                                                className="text-red-600 hover:text-red-700 hover:bg-red-50"
                                                                title="Delete"
                                                            >
                                                                <Trash2 className="w-4 h-4" />
                                                            </Button>
                                                        )}
                                                    </div>
                                                </div>
                                            ))}

                                            {(!activityType.sub_activities || activityType.sub_activities.length === 0) && (
                                                <div className="text-sm text-gray-500 py-2">
                                                    No sub-activities yet.
                                                </div>
                                            )}
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                onClick={() => onCreateSubActivity(activityType)}
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
    );
}

export default ActivityTypeAccordion;
