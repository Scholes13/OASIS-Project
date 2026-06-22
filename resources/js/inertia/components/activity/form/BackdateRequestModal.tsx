import { FormEvent } from 'react';

interface BackdateRequestData {
    requested_date: string;
    reason: string;
}

interface BackdateRequestModalProps {
    data: BackdateRequestData;
    errors: Partial<Record<keyof BackdateRequestData, string>>;
    processing: boolean;
    onChange: (key: keyof BackdateRequestData, value: string) => void;
    onSubmit: (event: FormEvent) => void;
    onClose: () => void;
}

export default function BackdateRequestModal({
    data,
    errors,
    processing,
    onChange,
    onSubmit,
    onClose,
}: BackdateRequestModalProps) {
    const minReasonLength = 10;
    const maxRequestedDate = new Date(Date.now() - 2 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];

    return (
        <div className="fixed inset-0 z-[9999] overflow-y-auto">
            <div className="flex items-center justify-center min-h-screen px-4 text-center">
                <button
                    type="button"
                    aria-label="Close backdate request modal"
                    className="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75"
                    onClick={onClose}
                />

                <div className="relative bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:max-w-lg sm:w-full z-[10000]">
                    <form onSubmit={onSubmit}>
                        <div className="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div className="sm:flex sm:items-start">
                                <div className="mt-3 text-center sm:mt-0 sm:text-left w-full">
                                    <h3 className="text-lg leading-6 font-medium text-gray-900">
                                        Request Backdate Access
                                    </h3>
                                    <p className="mt-2 text-sm text-gray-600">
                                        Request permission to enter tasks with older dates. Your department head will review and approve your request.
                                    </p>

                                    <div className="mt-4 space-y-4">
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                                Backdate To <span className="text-red-500">*</span>
                                            </label>
                                            <input
                                                type="date"
                                                value={data.requested_date}
                                                onChange={(event) => onChange('requested_date', event.target.value)}
                                                max={maxRequestedDate}
                                                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                                            />
                                            {errors.requested_date && (
                                                <p className="mt-1 text-sm text-red-600">{errors.requested_date}</p>
                                            )}
                                            <p className="mt-1 text-xs text-gray-500">
                                                Select the earliest date you need to create tasks for
                                            </p>
                                        </div>

                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                                Reason <span className="text-red-500">*</span>
                                            </label>
                                            <textarea
                                                value={data.reason}
                                                onChange={(event) => onChange('reason', event.target.value)}
                                                rows={4}
                                                placeholder="Explain why you need backdate access..."
                                                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                                            />
                                            {errors.reason && (
                                                <p className="mt-1 text-sm text-red-600">{errors.reason}</p>
                                            )}
                                            <div className="mt-1 flex justify-between text-xs text-gray-500">
                                                <span>Minimum 10 characters</span>
                                                <span>{data.reason.trim().length} characters</span>
                                            </div>
                                        </div>

                                        <div className="bg-blue-50 border border-blue-200 rounded-lg p-3">
                                            <p className="text-sm text-blue-700">
                                                Once approved, you'll be able to enter tasks from the selected date until today. The permission will be valid until the end of the approval day.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button
                                type="submit"
                                disabled={processing || data.reason.trim().length < minReasonLength || !data.requested_date}
                                className="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-primary text-base font-medium text-white hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                {processing ? 'Submitting...' : 'Submit Request'}
                            </button>
                            <button
                                type="button"
                                onClick={onClose}
                                className="mt-3 w-full inline-flex justify-center rounded-lg border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary sm:mt-0 sm:w-auto sm:text-sm"
                            >
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    );
}
