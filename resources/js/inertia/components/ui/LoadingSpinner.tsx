export function LoadingSpinner({ size = 'md', className = '' }: { size?: 'sm' | 'md' | 'lg'; className?: string }) {
    const sizeStyles = {
        sm: 'h-4 w-4',
        md: 'h-8 w-8',
        lg: 'h-12 w-12',
    };

    return (
        <svg
            className={`animate-spin text-indigo-600 ${sizeStyles[size]} ${className}`}
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
        >
            <circle
                className="opacity-25"
                cx="12"
                cy="12"
                r="10"
                stroke="currentColor"
                strokeWidth="4"
            />
            <path
                className="opacity-75"
                fill="currentColor"
                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
            />
        </svg>
    );
}

export function LoadingOverlay({ message = 'Loading...' }: { message?: string }) {
    return (
        <div className="absolute inset-0 bg-white/75 flex items-center justify-center z-10">
            <div className="flex flex-col items-center gap-2">
                <LoadingSpinner size="lg" />
                <span className="text-sm text-gray-600">{message}</span>
            </div>
        </div>
    );
}

export function LoadingCard() {
    return (
        <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6 animate-pulse">
            <div className="flex items-center gap-4">
                <div className="w-12 h-12 bg-gray-200 rounded-lg" />
                <div className="flex-1">
                    <div className="h-4 bg-gray-200 rounded w-1/4 mb-2" />
                    <div className="h-6 bg-gray-200 rounded w-1/3" />
                </div>
            </div>
        </div>
    );
}

export function LoadingTable({ rows = 5 }: { rows?: number }) {
    return (
        <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div className="animate-pulse">
                {/* Header */}
                <div className="border-b border-gray-200 bg-gray-50 px-4 py-3">
                    <div className="flex gap-4">
                        <div className="h-4 bg-gray-200 rounded w-1/4" />
                        <div className="h-4 bg-gray-200 rounded w-1/6" />
                        <div className="h-4 bg-gray-200 rounded w-1/6" />
                        <div className="h-4 bg-gray-200 rounded w-1/6" />
                    </div>
                </div>
                {/* Rows */}
                {Array.from({ length: rows }).map((_, i) => (
                    <div key={i} className="border-b border-gray-100 px-4 py-4">
                        <div className="flex gap-4">
                            <div className="h-4 bg-gray-100 rounded w-1/3" />
                            <div className="h-4 bg-gray-100 rounded w-1/6" />
                            <div className="h-4 bg-gray-100 rounded w-1/6" />
                            <div className="h-4 bg-gray-100 rounded w-1/6" />
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );
}
