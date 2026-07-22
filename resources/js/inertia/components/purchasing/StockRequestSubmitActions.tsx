import { Loader2, Send } from 'lucide-react';
import { Button } from '../ui/button';

interface StockRequestSubmitActionsProps {
    processing: boolean;
    onSubmit: () => void;
}

export function StockRequestSubmitActions({
    processing,
    onSubmit,
}: StockRequestSubmitActionsProps) {
    return (
        <div className="flex items-center justify-end gap-3">
            <Button
                type="button"
                onClick={onSubmit}
                disabled={processing}
                className="disabled:opacity-50 disabled:cursor-not-allowed"
            >
                {processing ? (
                    <>
                        <Loader2 className="w-4 h-4 mr-2 animate-spin" />
                        Submitting...
                    </>
                ) : (
                    <>
                        <Send className="w-4 h-4 mr-2" />
                    Submit Stock Request
                    </>
                )}
            </Button>
        </div>
    );
}
