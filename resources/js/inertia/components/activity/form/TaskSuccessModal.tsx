import { Eye, Plus } from 'lucide-react';

interface TaskSuccessModalProps {
    onCreateAnother: () => void;
    onViewTask: () => void;
    onGoToList: () => void;
    onClose: () => void;
}

export default function TaskSuccessModal({
    onCreateAnother,
    onViewTask,
    onGoToList,
    onClose,
}: TaskSuccessModalProps) {
    return (
        <div className="fixed inset-0 z-[9999] overflow-y-auto">
            <div className="flex items-center justify-center min-h-screen px-4 text-center">
                <button
                    type="button"
                    aria-label="Close success modal"
                    className="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75"
                    onClick={onClose}
                />

                <div className="relative bg-white rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:max-w-md sm:w-full z-[10000]">
                    <div className="bg-white px-6 pt-6 pb-4">
                        <div className="text-center">
                            <div className="mx-auto flex items-center justify-center h-14 w-14 rounded-full bg-emerald-100 mb-4">
                                <svg
                                    className="h-7 w-7 text-emerald-600"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        strokeWidth="2"
                                        d="M5 13l4 4L19 7"
                                    />
                                </svg>
                            </div>
                            <h3 className="text-xl font-semibold text-gray-900 mb-2">
                                Task Berhasil Dibuat!
                            </h3>
                            <p className="text-sm text-gray-500">
                                Apa yang ingin Anda lakukan selanjutnya?
                            </p>
                        </div>
                    </div>

                    <div className="bg-gray-50 px-6 py-4 space-y-3">
                        <button
                            onClick={onCreateAnother}
                            className="w-full inline-flex items-center justify-center gap-2 rounded-lg border border-transparent shadow-sm px-4 py-2.5 bg-primary text-base font-medium text-white hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors"
                        >
                            <Plus className="h-5 w-5" />
                            Buat Task Lagi
                        </button>
                        <button
                            onClick={onViewTask}
                            className="w-full inline-flex items-center justify-center gap-2 rounded-lg border border-gray-300 shadow-sm px-4 py-2.5 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors"
                        >
                            <Eye className="h-5 w-5" />
                            Lihat Task
                        </button>
                        <button
                            onClick={onGoToList}
                            className="w-full inline-flex items-center justify-center gap-2 rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-500 hover:text-gray-700 hover:bg-gray-100 transition-colors"
                        >
                            Kembali ke Daftar Task
                        </button>
                    </div>
                </div>
            </div>
        </div>
    );
}
