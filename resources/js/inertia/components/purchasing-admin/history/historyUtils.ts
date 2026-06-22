import type { AdminTask } from '@/components/purchasing-admin/types';

export interface HistoryFiltersState {
    date_from: string;
    date_to: string;
    status: string;
    type: string;
    admin?: string;
}

export interface HistoryPaginationData {
    current_page: number;
    last_page: number;
    total: number;
    from: number;
    to: number;
}

export interface HistoryStats {
    total_completed: number | string;
    avg_followup_time: number | string | null;
    avg_completion_time: number | string | null;
    total_savings: number | string | null;
    avg_savings_percentage: number | string | null;
}

export interface AdminOption {
    id: number;
    name: string;
}

export const formatHistoryCurrency = (amount: number | string | null | undefined) => {
    if (amount === null || amount === undefined) return '-';

    const val = typeof amount === 'string' ? parseFloat(amount) : amount;

    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(val);
};

export const formatHistoryTime = (minutes: number | string | null | undefined) => {
    if (minutes === null || minutes === undefined) return '-';

    const val = typeof minutes === 'string' ? parseFloat(minutes) : minutes;

    if (val >= 60) {
        return `${(val / 60).toFixed(1)} hrs`;
    }

    if (val >= 1) {
        return `${Math.round(val)} min`;
    }

    return `${Math.max(1, Math.round(val * 60))} sec`;
};

export const formatHistoryDate = (dateString: string | null | undefined) => {
    if (!dateString) return '-';

    return new Date(dateString).toLocaleString('id-ID', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
    });
};

export const getHistoryTaskNumber = (task: AdminTask) => {
    const isPR = task.taskable_type?.includes('PurchaseRequest');

    return isPR ? task.taskable?.pr_number : task.taskable?.st_number;
};
