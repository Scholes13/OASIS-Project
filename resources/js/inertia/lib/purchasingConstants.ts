import type { LucideIcon } from 'lucide-react';
import { Ban, Check, Clock, Edit, X } from 'lucide-react';

export type PrStatus = 'draft' | 'submitted' | 'in_approval' | 'approved' | 'rejected' | 'voided';
export type StStatus = PrStatus | 'ga_review' | 'ga_rejected' | 'ready_for_purchasing';

export interface PurchasingStatusConfig {
    label: string;
    bg: string;
    text: string;
    border: string;
    icon?: LucideIcon;
}

export const PR_STATUS_CONFIG: Record<PrStatus, PurchasingStatusConfig> = {
    draft: { bg: 'bg-gray-100', text: 'text-gray-700', border: 'border-gray-200', icon: Edit, label: 'Draft' },
    submitted: { bg: 'bg-blue-100', text: 'text-blue-700', border: 'border-blue-200', icon: Clock, label: 'Submitted' },
    in_approval: { bg: 'bg-amber-100', text: 'text-amber-700', border: 'border-amber-200', icon: Clock, label: 'In Approval' },
    approved: { bg: 'bg-emerald-100', text: 'text-emerald-700', border: 'border-emerald-200', icon: Check, label: 'Approved' },
    rejected: { bg: 'bg-red-100', text: 'text-red-700', border: 'border-red-200', icon: X, label: 'Rejected' },
    voided: { bg: 'bg-gray-100', text: 'text-gray-500', border: 'border-gray-200', icon: Ban, label: 'Voided' },
};

export const ST_STATUS_CONFIG: Record<StStatus, PurchasingStatusConfig> = {
    ...PR_STATUS_CONFIG,
    ga_review: { bg: 'bg-sky-100', text: 'text-sky-700', border: 'border-sky-200', icon: Clock, label: 'GA Review' },
    ga_rejected: { bg: 'bg-red-100', text: 'text-red-700', border: 'border-red-200', icon: X, label: 'GA Rejected' },
    ready_for_purchasing: { bg: 'bg-indigo-100', text: 'text-indigo-700', border: 'border-indigo-200', icon: Check, label: 'Ready for Purchasing' },
};

export const APPROVAL_BADGE_COLORS: Record<'pending' | 'approved' | 'rejected' | 'voided' | 'offline', { bg: string; text: string }> = {
    pending: { bg: 'bg-amber-100', text: 'text-amber-600' },
    approved: { bg: 'bg-emerald-100', text: 'text-emerald-600' },
    rejected: { bg: 'bg-red-100', text: 'text-red-600' },
    voided: { bg: 'bg-gray-100', text: 'text-gray-600' },
    offline: { bg: 'bg-purple-100', text: 'text-purple-600' },
};
