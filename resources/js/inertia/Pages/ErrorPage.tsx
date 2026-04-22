import { Head, router } from '@inertiajs/react';
import { Home, ArrowLeft, RefreshCw } from 'lucide-react';
import type { ReactNode } from 'react';

interface ErrorPageProps {
    status: number;
    message?: string;
}

/* ─── Inline SVG Illustrations ─────────────────────────────────────────────── */

function Illustration404() {
    return (
        <svg viewBox="0 0 280 200" fill="none" xmlns="http://www.w3.org/2000/svg" className="w-full max-w-[280px]">
            {/* Floating document with question mark */}
            <rect x="90" y="30" width="100" height="130" rx="8" fill="#EFF6FF" stroke="#BFDBFE" strokeWidth="2" />
            <rect x="110" y="50" width="60" height="6" rx="3" fill="#BFDBFE" />
            <rect x="110" y="64" width="45" height="6" rx="3" fill="#DBEAFE" />
            <rect x="110" y="78" width="55" height="6" rx="3" fill="#DBEAFE" />
            <rect x="110" y="92" width="35" height="6" rx="3" fill="#DBEAFE" />
            {/* Question mark */}
            <circle cx="140" cy="125" r="18" fill="#2563EB" opacity="0.1" />
            <text x="140" y="132" textAnchor="middle" fontSize="22" fontWeight="700" fill="#2563EB" fontFamily="system-ui">?</text>
            {/* Floating dots */}
            <circle cx="55" cy="60" r="4" fill="#93C5FD" opacity="0.6">
                <animate attributeName="cy" values="60;54;60" dur="3s" repeatCount="indefinite" />
            </circle>
            <circle cx="230" cy="80" r="3" fill="#BFDBFE" opacity="0.5">
                <animate attributeName="cy" values="80;74;80" dur="2.5s" repeatCount="indefinite" />
            </circle>
            <circle cx="45" cy="140" r="5" fill="#DBEAFE" opacity="0.4">
                <animate attributeName="cy" values="140;134;140" dur="3.5s" repeatCount="indefinite" />
            </circle>
            <circle cx="240" cy="45" r="3.5" fill="#93C5FD" opacity="0.3">
                <animate attributeName="cy" values="45;39;45" dur="2.8s" repeatCount="indefinite" />
            </circle>
            {/* Magnifying glass searching */}
            <circle cx="210" cy="140" r="16" stroke="#2563EB" strokeWidth="3" fill="none" opacity="0.3" />
            <line x1="222" y1="152" x2="235" y2="165" stroke="#2563EB" strokeWidth="3" strokeLinecap="round" opacity="0.3" />
        </svg>
    );
}

function Illustration403() {
    return (
        <svg viewBox="0 0 280 200" fill="none" xmlns="http://www.w3.org/2000/svg" className="w-full max-w-[280px]">
            {/* Shield */}
            <path d="M140 25 L195 50 L195 110 C195 145 170 170 140 180 C110 170 85 145 85 110 L85 50 Z" fill="#FFF7ED" stroke="#FED7AA" strokeWidth="2" />
            <path d="M140 45 L180 63 L180 108 C180 135 162 155 140 163 C118 155 100 135 100 108 L100 63 Z" fill="#FFEDD5" opacity="0.5" />
            {/* Lock icon inside shield */}
            <rect x="127" y="95" width="26" height="22" rx="4" fill="#EA580C" opacity="0.2" />
            <rect x="127" y="95" width="26" height="22" rx="4" stroke="#EA580C" strokeWidth="2" fill="none" />
            <path d="M133 95 L133 87 C133 83 136 80 140 80 C144 80 147 83 147 87 L147 95" stroke="#EA580C" strokeWidth="2" fill="none" strokeLinecap="round" />
            <circle cx="140" cy="106" r="3" fill="#EA580C" />
            {/* X marks */}
            <g opacity="0.3">
                <line x1="50" y1="55" x2="62" y2="67" stroke="#FB923C" strokeWidth="2" strokeLinecap="round" />
                <line x1="62" y1="55" x2="50" y2="67" stroke="#FB923C" strokeWidth="2" strokeLinecap="round" />
            </g>
            <g opacity="0.2">
                <line x1="220" y1="70" x2="230" y2="80" stroke="#FB923C" strokeWidth="2" strokeLinecap="round" />
                <line x1="230" y1="70" x2="220" y2="80" stroke="#FB923C" strokeWidth="2" strokeLinecap="round" />
            </g>
            {/* Floating dots */}
            <circle cx="60" cy="140" r="4" fill="#FDBA74" opacity="0.4">
                <animate attributeName="cy" values="140;134;140" dur="3s" repeatCount="indefinite" />
            </circle>
            <circle cx="225" cy="130" r="3" fill="#FED7AA" opacity="0.5">
                <animate attributeName="cy" values="130;124;130" dur="2.5s" repeatCount="indefinite" />
            </circle>
        </svg>
    );
}

function Illustration500() {
    return (
        <svg viewBox="0 0 280 200" fill="none" xmlns="http://www.w3.org/2000/svg" className="w-full max-w-[280px]">
            {/* Server rack */}
            <rect x="95" y="35" width="90" height="130" rx="8" fill="#FEF2F2" stroke="#FECACA" strokeWidth="2" />
            {/* Server slots */}
            <rect x="107" y="50" width="66" height="20" rx="4" fill="white" stroke="#FCA5A5" strokeWidth="1.5" />
            <circle cx="160" cy="60" r="4" fill="#EF4444" opacity="0.6">
                <animate attributeName="opacity" values="0.6;0.2;0.6" dur="1.5s" repeatCount="indefinite" />
            </circle>
            <rect x="115" y="57" width="25" height="3" rx="1.5" fill="#FECACA" />
            <rect x="107" y="80" width="66" height="20" rx="4" fill="white" stroke="#FCA5A5" strokeWidth="1.5" />
            <circle cx="160" cy="90" r="4" fill="#F97316" opacity="0.5">
                <animate attributeName="opacity" values="0.5;0.2;0.5" dur="2s" repeatCount="indefinite" />
            </circle>
            <rect x="115" y="87" width="30" height="3" rx="1.5" fill="#FECACA" />
            <rect x="107" y="110" width="66" height="20" rx="4" fill="white" stroke="#FCA5A5" strokeWidth="1.5" />
            <circle cx="160" cy="120" r="4" fill="#EF4444" opacity="0.4">
                <animate attributeName="opacity" values="0.4;0.1;0.4" dur="1.8s" repeatCount="indefinite" />
            </circle>
            <rect x="115" y="117" width="20" height="3" rx="1.5" fill="#FECACA" />
            {/* Lightning bolt (crash) */}
            <path d="M55 50 L70 80 L60 80 L72 115" stroke="#EF4444" strokeWidth="3" strokeLinecap="round" strokeLinejoin="round" fill="none" opacity="0.4">
                <animate attributeName="opacity" values="0.4;0.1;0.4" dur="2s" repeatCount="indefinite" />
            </path>
            {/* Gear with problem */}
            <circle cx="220" cy="70" r="18" stroke="#FCA5A5" strokeWidth="2" fill="none" strokeDasharray="4 3" opacity="0.5">
                <animateTransform attributeName="transform" type="rotate" from="0 220 70" to="360 220 70" dur="8s" repeatCount="indefinite" />
            </circle>
            <circle cx="220" cy="70" r="7" fill="#FCA5A5" opacity="0.3" />
            {/* Floating particles */}
            <circle cx="50" cy="150" r="4" fill="#FECACA" opacity="0.4">
                <animate attributeName="cy" values="150;144;150" dur="3s" repeatCount="indefinite" />
            </circle>
            <circle cx="235" cy="140" r="3" fill="#FCA5A5" opacity="0.3">
                <animate attributeName="cy" values="140;134;140" dur="2.5s" repeatCount="indefinite" />
            </circle>
        </svg>
    );
}

function Illustration503() {
    return (
        <svg viewBox="0 0 280 200" fill="none" xmlns="http://www.w3.org/2000/svg" className="w-full max-w-[280px]">
            {/* Wrench and gear — maintenance */}
            <circle cx="140" cy="90" r="30" stroke="#D1D5DB" strokeWidth="2.5" fill="#F9FAFB" strokeDasharray="6 4">
                <animateTransform attributeName="transform" type="rotate" from="0 140 90" to="360 140 90" dur="12s" repeatCount="indefinite" />
            </circle>
            <circle cx="140" cy="90" r="12" fill="#E5E7EB" />
            <circle cx="140" cy="90" r="5" fill="#9CA3AF" />
            {/* Wrench */}
            <g transform="translate(170, 55) rotate(45)">
                <rect x="0" y="8" width="50" height="8" rx="4" fill="#9CA3AF" opacity="0.5" />
                <circle cx="0" cy="12" r="10" stroke="#9CA3AF" strokeWidth="3" fill="none" opacity="0.5" />
            </g>
            {/* Progress bar */}
            <rect x="80" y="150" width="120" height="8" rx="4" fill="#E5E7EB" />
            <rect x="80" y="150" width="60" height="8" rx="4" fill="#9CA3AF" opacity="0.6">
                <animate attributeName="width" values="20;100;20" dur="3s" repeatCount="indefinite" />
            </rect>
            {/* Floating dots */}
            <circle cx="55" cy="70" r="4" fill="#D1D5DB" opacity="0.4">
                <animate attributeName="cy" values="70;64;70" dur="3s" repeatCount="indefinite" />
            </circle>
            <circle cx="230" cy="100" r="3" fill="#E5E7EB" opacity="0.5">
                <animate attributeName="cy" values="100;94;100" dur="2.5s" repeatCount="indefinite" />
            </circle>
        </svg>
    );
}

function Illustration419() {
    return (
        <svg viewBox="0 0 280 200" fill="none" xmlns="http://www.w3.org/2000/svg" className="w-full max-w-[280px]">
            {/* Hourglass */}
            <path d="M115 40 L165 40 L145 90 L165 140 L115 140 L135 90 Z" fill="#FFFBEB" stroke="#FDE68A" strokeWidth="2" />
            {/* Sand top */}
            <path d="M122 50 L158 50 L143 80 L137 80 Z" fill="#FCD34D" opacity="0.4">
                <animate attributeName="opacity" values="0.4;0.1;0.4" dur="3s" repeatCount="indefinite" />
            </path>
            {/* Sand bottom */}
            <path d="M125 130 L155 130 L148 110 L132 110 Z" fill="#FCD34D" opacity="0.6" />
            {/* Sand stream */}
            <line x1="140" y1="85" x2="140" y2="108" stroke="#F59E0B" strokeWidth="2" opacity="0.4">
                <animate attributeName="opacity" values="0.4;0.1;0.4" dur="1.5s" repeatCount="indefinite" />
            </line>
            {/* Clock arrows around */}
            <circle cx="140" cy="90" r="55" stroke="#FDE68A" strokeWidth="1.5" fill="none" strokeDasharray="8 6" opacity="0.4">
                <animateTransform attributeName="transform" type="rotate" from="0 140 90" to="360 140 90" dur="20s" repeatCount="indefinite" />
            </circle>
            {/* Floating dots */}
            <circle cx="55" cy="60" r="4" fill="#FDE68A" opacity="0.5">
                <animate attributeName="cy" values="60;54;60" dur="3s" repeatCount="indefinite" />
            </circle>
            <circle cx="230" cy="120" r="3" fill="#FCD34D" opacity="0.3">
                <animate attributeName="cy" values="120;114;120" dur="2.5s" repeatCount="indefinite" />
            </circle>
        </svg>
    );
}

/* ─── Error Configuration ──────────────────────────────────────────────────── */

const ERROR_CONFIG: Record<number, {
    title: string;
    description: string;
    illustration: () => React.JSX.Element;
    accent: string;
    accentLight: string;
    accentBorder: string;
    buttonBg: string;
    buttonHover: string;
}> = {
    403: {
        title: 'Access Denied',
        description: 'You don\'t have permission to access this page. If you believe this is a mistake, please contact your administrator.',
        illustration: Illustration403,
        accent: 'text-orange-600',
        accentLight: 'bg-orange-50',
        accentBorder: 'border-orange-100',
        buttonBg: 'bg-orange-600',
        buttonHover: 'hover:bg-orange-700',
    },
    404: {
        title: 'Page Not Found',
        description: 'The page you\'re looking for doesn\'t exist or has been moved to a different location.',
        illustration: Illustration404,
        accent: 'text-blue-600',
        accentLight: 'bg-blue-50',
        accentBorder: 'border-blue-100',
        buttonBg: 'bg-blue-600',
        buttonHover: 'hover:bg-blue-700',
    },
    419: {
        title: 'Session Expired',
        description: 'Your session has timed out for security reasons. Please log in again to continue where you left off.',
        illustration: Illustration419,
        accent: 'text-amber-600',
        accentLight: 'bg-amber-50',
        accentBorder: 'border-amber-100',
        buttonBg: 'bg-amber-600',
        buttonHover: 'hover:bg-amber-700',
    },
    500: {
        title: 'Something Went Wrong',
        description: 'We encountered an unexpected error. Our team has been notified and is working on a fix. Please try again in a moment.',
        illustration: Illustration500,
        accent: 'text-red-600',
        accentLight: 'bg-red-50',
        accentBorder: 'border-red-100',
        buttonBg: 'bg-red-600',
        buttonHover: 'hover:bg-red-700',
    },
    503: {
        title: 'Under Maintenance',
        description: 'We\'re performing scheduled maintenance to improve your experience. We\'ll be back shortly.',
        illustration: Illustration503,
        accent: 'text-gray-600',
        accentLight: 'bg-gray-50',
        accentBorder: 'border-gray-100',
        buttonBg: 'bg-gray-600',
        buttonHover: 'hover:bg-gray-700',
    },
};

/* ─── Error Page Component ─────────────────────────────────────────────────── */

export default function ErrorPage({ status, message }: ErrorPageProps) {
    const config = ERROR_CONFIG[status] ?? {
        title: 'Error',
        description: message ?? 'An unexpected error occurred.',
        illustration: Illustration500,
        accent: 'text-red-600',
        accentLight: 'bg-red-50',
        accentBorder: 'border-red-100',
        buttonBg: 'bg-red-600',
        buttonHover: 'hover:bg-red-700',
    };

    const Illustration = config.illustration;

    return (
        <>
            <Head title={`${status} - ${config.title}`} />
            <div className="flex min-h-screen items-center justify-center bg-gradient-to-br from-slate-50 via-white to-slate-100 px-4 py-12">
                <div className="w-full max-w-md">
                    {/* Main Card */}
                    <div className="overflow-hidden rounded-2xl bg-white shadow-xl shadow-slate-200/50 ring-1 ring-slate-100">
                        {/* Illustration Area */}
                        <div className={`flex flex-col items-center px-8 pb-2 pt-10 ${config.accentLight}`}>
                            <Illustration />
                        </div>

                        {/* Content */}
                        <div className="px-8 pb-8 pt-6 text-center">
                            {/* Status Code */}
                            <div className={`mb-1 text-sm font-semibold uppercase tracking-widest ${config.accent} opacity-70`}>
                                Error {status}
                            </div>

                            {/* Title */}
                            <h1 className="mb-3 text-2xl font-bold tracking-tight text-slate-900">
                                {config.title}
                            </h1>

                            {/* Description */}
                            <p className="mb-8 text-sm leading-relaxed text-slate-500">
                                {message ?? config.description}
                            </p>

                            {/* Action Buttons */}
                            <div className="flex flex-col gap-3 sm:flex-row sm:justify-center">
                                <button
                                    onClick={() => window.history.back()}
                                    className="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 shadow-sm transition-all hover:border-slate-300 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-200 focus:ring-offset-2"
                                >
                                    <ArrowLeft className="h-4 w-4" />
                                    Go Back
                                </button>
                                <button
                                    onClick={() => router.visit('/dashboard')}
                                    className={`inline-flex items-center justify-center gap-2 rounded-lg border border-transparent px-5 py-2.5 text-sm font-medium text-white shadow-sm transition-all focus:outline-none focus:ring-2 focus:ring-offset-2 ${config.buttonBg} ${config.buttonHover}`}
                                >
                                    <Home className="h-4 w-4" />
                                    Dashboard
                                </button>
                                {(status === 419 || status === 500) && (
                                    <button
                                        onClick={() => status === 419 ? (window.location.href = '/login') : window.location.reload()}
                                        className="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 shadow-sm transition-all hover:border-slate-300 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-200 focus:ring-offset-2"
                                    >
                                        <RefreshCw className="h-4 w-4" />
                                        {status === 419 ? 'Log In' : 'Retry'}
                                    </button>
                                )}
                            </div>
                        </div>
                    </div>

                    {/* Footer */}
                    <div className="mt-8 text-center">
                        <p className="text-xs text-slate-400">
                            If this problem persists, please contact{' '}
                            <a href="/docs-help" className="text-slate-500 underline decoration-slate-300 underline-offset-2 hover:text-slate-700">
                                IT Support
                            </a>
                        </p>
                    </div>
                </div>
            </div>
        </>
    );
}

// No layout for error page — renders standalone
ErrorPage.layout = (page: ReactNode) => page;
