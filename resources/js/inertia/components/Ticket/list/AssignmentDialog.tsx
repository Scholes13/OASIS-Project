import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Select } from '@/components/ui/select';

interface AssignmentDialogProps {
    open: boolean;
    assigneeId: string;
    staffOptions: { value: string; label: string }[];
    isAssigning: boolean;
    onClose: () => void;
    onAssigneeChange: (value: string) => void;
    onSubmit: (event: React.FormEvent) => void;
}

export function AssignmentDialog({
    open,
    assigneeId,
    staffOptions,
    isAssigning,
    onClose,
    onAssigneeChange,
    onSubmit,
}: AssignmentDialogProps) {
    return (
        <Dialog
            open={open}
            onClose={onClose}
        >
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Assign Ticket</DialogTitle>
                </DialogHeader>
                <form onSubmit={onSubmit}>
                    <div className="py-4">
                        <Select
                            value={assigneeId}
                            onChange={(value: string | number) => onAssigneeChange(String(value))}
                            options={staffOptions}
                            placeholder="Select staff..."
                        />
                    </div>
                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={onClose}
                        >
                            Cancel
                        </Button>
                        <Button
                            type="submit"
                            loading={isAssigning}
                        >
                            Assign
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
