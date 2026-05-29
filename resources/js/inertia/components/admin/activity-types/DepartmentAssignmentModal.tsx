import { Users } from 'lucide-react';

import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { ActivityType } from '@/types/admin';

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
    assigned_department_ids?: number[];
}

interface DepartmentAssignmentModalProps {
    open: boolean;
    selectedActivityType: ExtendedActivityType | null;
    selectedDepartments: number[];
    departmentsByBusinessUnit: Array<{
        businessUnit: BusinessUnit;
        departments: Department[];
    }>;
    isAssigning: boolean;
    onAssignDepartments: () => void;
    onClose: () => void;
    onDepartmentToggle: (deptId: number) => void;
}

export function DepartmentAssignmentModal({
    open,
    selectedActivityType,
    selectedDepartments,
    departmentsByBusinessUnit,
    isAssigning,
    onAssignDepartments,
    onClose,
    onDepartmentToggle,
}: DepartmentAssignmentModalProps) {
    return (
        <Dialog open={open} onClose={onClose} className="max-w-2xl">
            <DialogHeader onClose={onClose}>
                <DialogTitle>Assign to Departments</DialogTitle>
                <DialogDescription>
                    Select departments to assign "{selectedActivityType?.name}" activity type
                </DialogDescription>
            </DialogHeader>
            <DialogContent>
                <div className="max-h-96 overflow-y-auto space-y-4">
                    {departmentsByBusinessUnit.map((group) => (
                        <div
                            key={group.businessUnit.id}
                            className="border border-gray-200 rounded-lg overflow-hidden"
                        >
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
                    onClick={onClose}
                    disabled={isAssigning}
                >
                    Cancel
                </Button>
                <Button
                    onClick={onAssignDepartments}
                    disabled={isAssigning || selectedDepartments.length === 0}
                    loading={isAssigning}
                >
                    <Users className="w-4 h-4 mr-2" />
                    Assign ({selectedDepartments.length})
                </Button>
            </DialogFooter>
        </Dialog>
    );
}

export default DepartmentAssignmentModal;
