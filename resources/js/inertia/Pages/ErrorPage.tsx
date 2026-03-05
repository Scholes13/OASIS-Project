import { Head, router } from '@inertiajs/react';
import { AlertTriangle, Home, ArrowLeft, ShieldX, FileQuestion, ServerCrash, Clock } from 'lucide-react';

interface ErrorPageProps {
    status: number;
    message?: string;
}

const ERROR_CONFIG: Record<number, {
    title: string;
    description: string;
    icon: typeof AlertTriangle;
    color: string;
    bgColor: string;
    borderColor: string;
}> = {
    403: {
        title: 'Access Denied',
        description: 'You do not have permission to access this page. If you believe this is a mistake, please contact your administrator.',
        icon: ShieldX,
        color: 'text-orange-600',
        bgColor: 'bg-orange-100',
        borderColor: 'border-orange-200',
    },
    404: {
        title: 'Page Not Found',
        description: 'The page you are looking for does not exist or has been moved.',
        icon: FileQuestion,
        color: 'text-blue-600',
        bgColor: 'bg-blue-100',
        borderColor: 'border-blue-200',
    },
    419: {
        title: 'Page Expired',
        description: 'Your session has expired. Please refresh the page and try again.',
        icon: Clock,
        color: 'text-yellow-600',
        bgColor: 'bg-yellow-100',
        borderColor: 'border-yellow-200',
    },
    500: {
        title: 'Server Error',
        description: 'An unexpected error occurred on our servers. Our team has been notified. Please try again later.',
        icon: ServerCrash,
        color: 'text-red-600',
        bgColor: 'bg-red-100',
        borderColor: 'border-red-200',
    },
    503: {
        title: 'Service Unavailable',
        description: 'We are currently performing maintenance. Please check back soon.',
        icon: ServerCrash,
        color: 'text-gray-600',
        bgColor: 'bg-gray-100',
        borderColor: 'border-gray-200',
    },
};

export default function ErrorPage({ status, message }: ErrorPageProps) {
    const config = ERROR_CONFIG[status] ?? {
        title: 'Error',
        description: message ?? 'An unexpected error occurred.',
        icon: AlertTriangle,
        color: 'text-red-600',
        bgColor: 'bg-red-100',
        borderColor: 'border-red-200',
    };

    const Icon = config.icon;

    return (
        <>
            <Head title={`${status} - ${config.title}`} />
            <div className="min-h-screen bg-gray-50 flex items-center justify-center p-4">
                <div className="max-w-lg w-full">
                    {/* Error Card */}
                    <div className={`bg-white rounded-2xl shadow-lg border ${config.borderColor} overflow-hidden`}>
                        {/* Icon Section */}
                        <div className={`${config.bgColor} px-8 py-10 flex flex-col items-center`}>
                            <div className={`rounded-full bg-white/80 p-5 shadow-sm mb-4`}>
                                <Icon className={`h-12 w-12 ${config.color}`} strokeWidth={1.5} />
                            </div>
                            <div className={`text-6xl font-bold ${config.color} mb-2`}>
                                {status}
                            </div>
                            <h1 className="text-xl font-semibold text-gray-900">
                                {config.title}
                            </h1>
                        </div>

                        {/* Description Section */}
                        <div className="px-8 py-6">
                            <p className="text-gray-600 text-center leading-relaxed">
                                {message ?? config.description}
                            </p>
                        </div>

                        {/* Action Buttons */}
                        <div className="px-8 pb-8 flex flex-col sm:flex-row gap-3 justify-center">
                            <button
                                onClick={() => window.history.back()}
                                className="inline-flex items-center justify-center gap-2 px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors"
                            >
                                <ArrowLeft className="h-4 w-4" />
                                Go Back
                            </button>
                            <button
                                onClick={() => router.visit('/dashboard')}
                                className="inline-flex items-center justify-center gap-2 px-5 py-2.5 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors"
                            >
                                <Home className="h-4 w-4" />
                                Go to Dashboard
                            </button>
                            {status === 419 && (
                                <button
                                    onClick={() => window.location.reload()}
                                    className="inline-flex items-center justify-center gap-2 px-5 py-2.5 text-sm font-medium text-white bg-yellow-600 border border-transparent rounded-lg hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500 transition-colors"
                                >
                                    Refresh Page
                                </button>
                            )}
                        </div>
                    </div>

                    {/* Footer Text */}
                    <p className="text-center text-xs text-gray-400 mt-6">
                        If this problem persists, please contact IT support.
                    </p>
                </div>
            </div>
        </>
    );
}

// No layout for error page - it renders standalone
ErrorPage.layout = (page: React.ReactNode) => page;
