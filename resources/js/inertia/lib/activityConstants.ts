import * as React from 'react';
import { CheckCircle2, Circle, PlayCircle, XCircle } from 'lucide-react';

export type ActivityStatus = 'planned' | 'in_progress' | 'completed' | 'cancelled';

export interface ActivityStatusConfig {
    label: string;
    bg: string;
    text: string;
    border: string;
    ring: string;
    icon?: React.ReactNode;
}

export const ACTIVITY_STATUS_CONFIG: Record<ActivityStatus, ActivityStatusConfig> = {
    planned: {
        label: 'Planned',
        icon: React.createElement(Circle, { className: 'h-3.5 w-3.5' }),
        bg: 'bg-slate-50',
        text: 'text-slate-600',
        border: 'border-slate-200',
        ring: 'ring-slate-200',
    },
    in_progress: {
        label: 'In Progress',
        icon: React.createElement(PlayCircle, { className: 'h-3.5 w-3.5' }),
        bg: 'bg-blue-50',
        text: 'text-blue-700',
        border: 'border-blue-200',
        ring: 'ring-blue-200',
    },
    completed: {
        label: 'Completed',
        icon: React.createElement(CheckCircle2, { className: 'h-3.5 w-3.5' }),
        bg: 'bg-emerald-50',
        text: 'text-emerald-700',
        border: 'border-emerald-200',
        ring: 'ring-emerald-200',
    },
    cancelled: {
        label: 'Cancelled',
        icon: React.createElement(XCircle, { className: 'h-3.5 w-3.5' }),
        bg: 'bg-gray-50',
        text: 'text-gray-500',
        border: 'border-gray-200',
        ring: 'ring-gray-200',
    },
};

export const ACTIVITY_TYPE_COLORS: Record<string, { bg: string; text: string }> = {
    blue: { bg: 'bg-blue-50 ring-blue-200', text: 'text-blue-700' },
    indigo: { bg: 'bg-blue-50 ring-primary', text: 'text-blue-700' },
    purple: { bg: 'bg-purple-50 ring-purple-200', text: 'text-purple-700' },
    pink: { bg: 'bg-pink-50 ring-pink-200', text: 'text-pink-700' },
    red: { bg: 'bg-red-50 ring-red-200', text: 'text-red-700' },
    orange: { bg: 'bg-orange-50 ring-orange-200', text: 'text-orange-700' },
    amber: { bg: 'bg-amber-50 ring-amber-200', text: 'text-amber-700' },
    yellow: { bg: 'bg-yellow-50 ring-yellow-200', text: 'text-yellow-700' },
    lime: { bg: 'bg-lime-50 ring-lime-200', text: 'text-lime-700' },
    green: { bg: 'bg-green-50 ring-green-200', text: 'text-green-700' },
    emerald: { bg: 'bg-emerald-50 ring-emerald-200', text: 'text-emerald-700' },
    teal: { bg: 'bg-teal-50 ring-teal-200', text: 'text-teal-700' },
    cyan: { bg: 'bg-cyan-50 ring-cyan-200', text: 'text-cyan-700' },
    gray: { bg: 'bg-gray-50 ring-gray-200', text: 'text-gray-600' },
};

export const AVATAR_COLORS = [
    'bg-blue-50 text-blue-700',
    'bg-emerald-100 text-emerald-700',
    'bg-amber-100 text-amber-700',
    'bg-rose-100 text-rose-700',
    'bg-violet-100 text-violet-700',
];

export const STATUS_VERB_MAP: Record<string, string> = {
    in_progress: 'Working on',
    planned: 'Planning',
    completed: 'Completed',
};

export const PERIOD_LABELS: Record<'day' | 'week' | 'month', string> = {
    day: 'Day',
    week: 'Week',
    month: 'Month',
};
