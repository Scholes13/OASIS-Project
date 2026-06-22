import ActivityTypeFormModal from './ActivityTypeFormModal';
import ConfirmDeleteModal from './ConfirmDeleteModal';
import SubActivityFormModal from './SubActivityFormModal';

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
}

interface ActivityConfigurationModalsProps {
    activityTypeModalOpen: boolean;
    editingActivityType: ActivityType | null;
    activityTypeForm: { name: string };
    isSuperAdmin: boolean;
    isSubmittingActivityType: boolean;
    selectedDepartmentIds: number[];
    departments: Department[];
    departmentsByBusinessUnit: Array<{
        businessUnit: BusinessUnit;
        departments: Department[];
    }>;
    subActivityModalOpen: boolean;
    parentActivityType: ActivityType | null;
    editingSubActivity: SubActivity | null;
    subActivityForm: { name: string };
    isSubmittingSubActivity: boolean;
    deleteModalOpen: boolean;
    itemToDelete: { type: 'activityType' | 'subActivity'; item: ActivityType | SubActivity; parentId?: number } | null;
    isDeleting: boolean;
    onActivityTypeClose: () => void;
    onActivityTypeSubmit: (event: React.FormEvent) => void;
    onActivityTypeFormChange: (form: { name: string }) => void;
    onDepartmentToggle: (deptId: number) => void;
    onAssignDepartments: () => void;
    onSubActivityClose: () => void;
    onSubActivitySubmit: (event: React.FormEvent) => void;
    onSubActivityFormChange: (form: { name: string }) => void;
    onDeleteClose: () => void;
    onDeleteConfirm: () => void;
}

export function ActivityConfigurationModals(props: ActivityConfigurationModalsProps) {
    return (
        <>
            <ActivityTypeFormModal
                open={props.activityTypeModalOpen}
                editingActivityType={props.editingActivityType}
                form={props.activityTypeForm}
                isSuperAdmin={props.isSuperAdmin}
                isSubmitting={props.isSubmittingActivityType}
                selectedDepartmentIds={props.selectedDepartmentIds}
                departments={props.departments}
                departmentsByBusinessUnit={props.departmentsByBusinessUnit}
                onClose={props.onActivityTypeClose}
                onSubmit={props.onActivityTypeSubmit}
                onFormChange={props.onActivityTypeFormChange}
                onDepartmentToggle={props.onDepartmentToggle}
                onAssignDepartments={props.onAssignDepartments}
            />

            <SubActivityFormModal
                open={props.subActivityModalOpen}
                parentActivityType={props.parentActivityType}
                editingSubActivity={props.editingSubActivity}
                form={props.subActivityForm}
                isSubmitting={props.isSubmittingSubActivity}
                onClose={props.onSubActivityClose}
                onSubmit={props.onSubActivitySubmit}
                onFormChange={props.onSubActivityFormChange}
            />

            <ConfirmDeleteModal
                open={props.deleteModalOpen}
                itemName={props.itemToDelete?.item.name ?? ''}
                itemType={props.itemToDelete?.type}
                loading={props.isDeleting}
                onClose={props.onDeleteClose}
                onConfirm={props.onDeleteConfirm}
            />
        </>
    );
}

export default ActivityConfigurationModals;
