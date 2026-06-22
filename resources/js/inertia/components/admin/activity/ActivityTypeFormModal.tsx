import { Check, Users } from 'lucide-react';

import { Badge } from '@/components/ui/Badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

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

interface ActivityTypeFormModalProps {
    open: boolean;
    editingActivityType: ActivityType | null;
    form: { name: string };
    isSuperAdmin: boolean;
    isSubmitting: boolean;
    selectedDepartmentIds: number[];
    departments: Department[];
    departmentsByBusinessUnit: Array<{
        businessUnit: BusinessUnit;
        departments: Department[];
    }>;
    onClose: () => void;
    onSubmit: (event: React.FormEvent) => void;
    onFormChange: (form: { name: string }) => void;
    onDepartmentToggle: (deptId: number) => void;
    onAssignDepartments: () => void;
}

export function ActivityTypeFormModal({
    open,
    editingActivityType,
    form,
    isSuperAdmin,
    isSubmitting,
    selectedDepartmentIds,
    departments,
    departmentsByBusinessUnit,
    onClose,
    onSubmit,
    onFormChange,
    onDepartmentToggle,
    onAssignDepartments,
}: ActivityTypeFormModalProps) {
    return (
        <Dialog open={open} onClose={onClose} className="max-w-2xl">
            <DialogHeader onClose={onClose}>
                <DialogTitle>
                    {editingActivityType ? 'Edit Activity Type' : 'Create Activity Type'}
                </DialogTitle>
                <DialogDescription>
                    {editingActivityType
                        ? 'Update the activity type details below.'
                        : 'Add a new activity type for task categorization.'}
                </DialogDescription>
            </DialogHeader>
            <form onSubmit={onSubmit}>
                <DialogContent>
                    <div className="space-y-4">
                        <div>
                            <Label htmlFor="activity-type-name">Name *</Label>
                            <Input
                                id="activity-type-name"
                                type="text"
                                value={form.name}
                                onChange={(event) => onFormChange({
                                    ...form,
                                    name: event.target.value,
                                })}
                                placeholder="Enter activity type name"
                                required
                            />
                        </div>

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
                                                    {group.businessUnit.code} - {group.businessUnit.name}
                                                </span>
                                            </div>
                                            <div className="p-3 space-y-2">
                                                {group.departments.map((dept) => {
                                                    const isAlreadyAssigned = editingActivityType.assigned_department_ids?.includes(dept.id) || false;
                                                    const isSelected = selectedDepartmentIds.includes(dept.id);

                                                    return (
                                                        <label
                                                            key={dept.id}
                                                            className={`flex items-center gap-2 p-2 rounded cursor-pointer ${
                                                                isAlreadyAssigned && !isSelected
                                                                    ? 'bg-green-50'
                                                                    : 'hover:bg-gray-50'
                                                            }`}
                                                        >
                                                            <input
                                                                type="checkbox"
                                                                checked={isSelected}
                                                                onChange={() => onDepartmentToggle(dept.id)}
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
                                {selectedDepartmentIds.length > 0 && (
                                    <div className="mt-3 flex flex-wrap gap-2">
                                        {selectedDepartmentIds.map((id) => {
                                            const dept = departments.find((department) => department.id === id);
                                            return dept ? (
                                                <Badge key={id} variant="info">
                                                    {dept.code}
                                                </Badge>
                                            ) : null;
                                        })}
                                        <span className="text-sm text-gray-600">
                                            {selectedDepartmentIds.length} selected
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
                        onClick={onClose}
                        disabled={isSubmitting}
                    >
                        Cancel
                    </Button>
                    {isSuperAdmin && editingActivityType && (
                        <Button
                            type="button"
                            onClick={onAssignDepartments}
                            disabled={isSubmitting || selectedDepartmentIds.length === 0}
                            loading={isSubmitting}
                        >
                            <Users className="w-4 h-4 mr-2" />
                            Assign ({selectedDepartmentIds.length})
                        </Button>
                    )}
                    <Button
                        type="submit"
                        disabled={isSubmitting}
                        loading={isSubmitting}
                    >
                        <Check className="w-4 h-4 mr-2" />
                        {editingActivityType ? 'Update' : 'Create'}
                    </Button>
                </DialogFooter>
            </form>
        </Dialog>
    );
}

export default ActivityTypeFormModal;
