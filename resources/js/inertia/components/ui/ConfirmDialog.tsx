import { Fragment } from 'react';
import { Dialog, Transition } from '@headlessui/react';
import { AlertTriangle, Trash2, UserX, X } from 'lucide-react';
import { Button } from './button';

interface ConfirmDialogProps {
    isOpen: boolean;
    onClose: () => void;
    onConfirm: () => void;
    title: string;
    message: string;
    confirmText?: string;
    cancelText?: string;
    variant?: 'danger' | 'warning' | 'info';
    isLoading?: boolean;
}

const variantStyles = {
    danger: {
        icon: Trash2,
        iconBg: 'bg-red-100',
        iconColor: 'text-red-600',
        buttonClass: 'bg-red-600 hover:bg-red-700 focus:ring-red-500',
    },
    warning: {
        icon: UserX,
        iconBg: 'bg-amber-100',
        iconColor: 'text-amber-600',
        buttonClass: 'bg-amber-600 hover:bg-amber-700 focus:ring-amber-500',
    },
    info: {
        icon: AlertTriangle,
        iconBg: 'bg-blue-100',
        iconColor: 'text-blue-600',
        buttonClass: 'bg-blue-600 hover:bg-blue-700 focus:ring-blue-500',
    },
};

export function ConfirmDialog({
    isOpen,
    onClose,
    onConfirm,
    title,
    message,
    confirmText = 'Confirm',
    cancelText = 'Cancel',
    variant = 'danger',
    isLoading = false,
}: ConfirmDialogProps) {
    const styles = variantStyles[variant];
    const Icon = styles.icon;

    return (
        <Transition appear show={isOpen} as={Fragment}>
            <Dialog as="div" className="relative z-50" onClose={onClose}>
                <Transition.Child
                    as={Fragment}
                    enter="ease-out duration-300"
                    enterFrom="opacity-0"
                    enterTo="opacity-100"
                    leave="ease-in duration-200"
                    leaveFrom="opacity-100"
                    leaveTo="opacity-0"
                >
                    <div className="fixed inset-0 bg-black/25 backdrop-blur-sm" />
                </Transition.Child>

                <div className="fixed inset-0 overflow-y-auto">
                    <div className="flex min-h-full items-center justify-center p-4 text-center">
                        <Transition.Child
                            as={Fragment}
                            enter="ease-out duration-300"
                            enterFrom="opacity-0 scale-95"
                            enterTo="opacity-100 scale-100"
                            leave="ease-in duration-200"
                            leaveFrom="opacity-100 scale-100"
                            leaveTo="opacity-0 scale-95"
                        >
                            <Dialog.Panel className="w-full max-w-md transform overflow-hidden rounded-2xl bg-white p-6 text-left align-middle shadow-xl transition-all">
                                <div className="flex items-start gap-4">
                                    <div className={`flex-shrink-0 flex items-center justify-center w-12 h-12 rounded-full ${styles.iconBg}`}>
                                        <Icon className={`w-6 h-6 ${styles.iconColor}`} />
                                    </div>
                                    <div className="flex-1">
                                        <Dialog.Title as="h3" className="text-lg font-semibold text-gray-900">
                                            {title}
                                        </Dialog.Title>
                                        <p className="mt-2 text-sm text-gray-500">
                                            {message}
                                        </p>
                                    </div>
                                    <button
                                        onClick={onClose}
                                        className="flex-shrink-0 text-gray-400 hover:text-gray-500"
                                    >
                                        <X className="w-5 h-5" />
                                    </button>
                                </div>

                                <div className="mt-6 flex justify-end gap-3">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={onClose}
                                        disabled={isLoading}
                                    >
                                        {cancelText}
                                    </Button>
                                    <button
                                        type="button"
                                        className={`inline-flex items-center px-4 py-2 text-sm font-medium text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed ${styles.buttonClass}`}
                                        onClick={onConfirm}
                                        disabled={isLoading}
                                    >
                                        {isLoading ? (
                                            <>
                                                <svg className="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                                    <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                                                    <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                                                </svg>
                                                Processing...
                                            </>
                                        ) : confirmText}
                                    </button>
                                </div>
                            </Dialog.Panel>
                        </Transition.Child>
                    </div>
                </div>
            </Dialog>
        </Transition>
    );
}
