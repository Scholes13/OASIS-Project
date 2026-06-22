import { Check } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

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
}

interface SubActivityFormModalProps {
    open: boolean;
    parentActivityType: ActivityType | null;
    editingSubActivity: SubActivity | null;
    form: { name: string };
    isSubmitting: boolean;
    onClose: () => void;
    onSubmit: (event: React.FormEvent) => void;
    onFormChange: (form: { name: string }) => void;
}

export function SubActivityFormModal({
    open,
    parentActivityType,
    editingSubActivity,
    form,
    isSubmitting,
    onClose,
    onSubmit,
    onFormChange,
}: SubActivityFormModalProps) {
    return (
        <Dialog open={open} onClose={onClose}>
            <DialogHeader onClose={onClose}>
                <DialogTitle>
                    {editingSubActivity ? 'Edit Sub-Activity' : 'Create Sub-Activity'}
                </DialogTitle>
                <DialogDescription>
                    {parentActivityType
                        ? `Adding sub-activity to "${parentActivityType.name}"`
                        : 'Add a new sub-activity for detailed task categorization.'}
                </DialogDescription>
            </DialogHeader>
            <form onSubmit={onSubmit}>
                <DialogContent>
                    <div className="space-y-4">
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

                        <div>
                            <Label htmlFor="sub-activity-name">Name *</Label>
                            <Input
                                id="sub-activity-name"
                                type="text"
                                value={form.name}
                                onChange={(event) => onFormChange({
                                    ...form,
                                    name: event.target.value,
                                })}
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
                        onClick={onClose}
                        disabled={isSubmitting}
                    >
                        Cancel
                    </Button>
                    <Button
                        type="submit"
                        disabled={isSubmitting}
                        loading={isSubmitting}
                    >
                        <Check className="w-4 h-4 mr-2" />
                        {editingSubActivity ? 'Update' : 'Create'}
                    </Button>
                </DialogFooter>
            </form>
        </Dialog>
    );
}

export default SubActivityFormModal;
