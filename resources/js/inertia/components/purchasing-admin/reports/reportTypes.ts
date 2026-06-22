export interface BusinessUnitMetric {
    id: number;
    code: string;
    name: string;
    total_tasks: number;
    total_savings: number;
    avg_savings_percentage: number;
    avg_followup_time: number;
    avg_completion_time: number;
}

export interface OverallMetrics {
    total_tasks: number;
    total_savings: number;
    avg_savings_percentage: number;
    avg_followup_time: number;
    avg_completion_time: number;
}

export interface ComparativeTrendData {
    labels: string[];
    datasets: {
        label: string;
        data: number[];
        borderColor: string;
        backgroundColor: string;
    }[];
}

export const formatReportCurrency = (amount: number | string | null | undefined) => {
    if (amount === null || amount === undefined) return 'Rp 0';

    const val = typeof amount === 'string' ? parseFloat(amount) : amount;

    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(val);
};

export const formatReportTime = (minutes: number | string | null | undefined) => {
    if (minutes === null || minutes === undefined || minutes === 0) return '0 min';

    const val = typeof minutes === 'string' ? parseFloat(minutes) : minutes;

    if (val >= 1440) {
        const days = Math.floor(val / 1440);
        const hours = Math.floor((val % 1440) / 60);

        return hours > 0 ? `${days}d ${hours}h` : `${days}d`;
    }

    if (val >= 60) {
        const hours = Math.floor(val / 60);
        const mins = Math.round(val % 60);

        return mins > 0 ? `${hours}h ${mins}m` : `${hours}h`;
    }

    return `${Math.round(val)} min`;
};
