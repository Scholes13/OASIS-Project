import React from 'react';
import { Plus, X } from 'lucide-react';
import { Button } from '../ui/button';
import { ApprovalEntry, AvailableApprover } from '../../types/purchasing';

interface ApprovalWorkflowBuilderProps {
    approvers: ApprovalEntry[];
    availableApprovers: AvailableApprover[];
    onAdd: (approver: AvailableApprover) => void;
    onRemove: (index: number) => void;
    onReorder?: (from: number, to: number) => void;
    onUpdate?: (index: number, field: 'approver_id' | 'task_type', value: string) => void;
    disabled?: boolean;
    errors?: Record<number, string>;
}

const blankApprover: AvailableApprover = { id: 0, name: '' };
const defaultStageLabels = ['Internal Department', 'Purchasing Approval', 'Management / BOD'];

export const ApprovalWorkflowBuilder: React.FC<ApprovalWorkflowBuilderProps> = ({
    approvers,
    availableApprovers,
    onAdd,
    onRemove,
    onReorder,
    onUpdate,
    disabled = false,
    errors,
}) => {
    return (
        <div className="bg-white rounded-xl border border-gray-100 overflow-hidden">
            <div className="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <div>
                    <h3 className="text-base font-semibold text-gray-900">Approval Workflow</h3>
                    <p className="text-sm text-gray-500 mt-1">Default order: Internal Department → Purchasing Approval → Management / BOD → Purchasing Follow-up → Done / Barang Sampai</p>
                </div>
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    onClick={() => onAdd(blankApprover)}
                    disabled={disabled}
                >
                    <Plus className="w-4 h-4 mr-2" />
                    Add approver
                </Button>
            </div>
            <div className="p-6 space-y-3">
                {approvers.map((step, index) => (
                    <div key={index} className="space-y-1">
                        <div className="flex items-center gap-3">
                            <div className="flex-shrink-0 w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center text-sm font-medium">
                                {index + 1}
                            </div>
                            <div className="w-44 flex-shrink-0">
                                <p className="text-sm font-medium text-gray-900">{defaultStageLabels[index] || `Extra approval ${index - defaultStageLabels.length + 1}`}</p>
                                <p className="text-xs text-gray-500">Approval step</p>
                            </div>
                            <div className="flex-1">
                                <select
                                    value={step.approver_id || step.user_id || ''}
                                    onChange={(event) => onUpdate?.(index, 'approver_id', event.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary text-sm"
                                    disabled={disabled}
                                >
                                    <option value="">Select Approver</option>
                                    {availableApprovers.map((approver) => (
                                        <option key={approver.id} value={approver.id}>
                                            {approver.name} {approver.position ? `- ${approver.position}` : ''}
                                        </option>
                                    ))}
                                </select>
                            </div>
                            <div className="flex-shrink-0 w-32">
                                <select
                                    value={step.task_type || 'approval'}
                                    onChange={(event) => onUpdate?.(index, 'task_type', event.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary text-sm"
                                    disabled={disabled}
                                >
                                    <option value="approval">Approval</option>
                                    <option value="paraf">Paraf</option>
                                </select>
                            </div>
                            {onReorder && index > 0 && (
                                <Button type="button" variant="ghost" size="sm" onClick={() => onReorder(index, index - 1)} disabled={disabled}>
                                    ↑
                                </Button>
                            )}
                            {approvers.length > 1 && (
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    onClick={() => onRemove(index)}
                                    disabled={disabled}
                                    className="text-red-600 hover:text-red-700 hover:bg-red-50"
                                >
                                    <X className="w-4 h-4" />
                                </Button>
                            )}
                        </div>
                        {errors?.[index] && <p className="ml-11 text-sm text-red-600">{errors[index]}</p>}
                    </div>
                ))}
            </div>
        </div>
    );
};
