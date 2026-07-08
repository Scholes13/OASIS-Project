const pad2 = (value: number): string => String(value).padStart(2, '0');

export const ACTIVITY_TIME_ZONE = 'Asia/Jakarta';

const hasExplicitTimezone = (value: string): boolean => /(?:Z|[+-]\d{2}:?\d{2})$/i.test(value);

const parseActivityDate = (value: string): Date => {
    if (!value.includes('T')) {
        return new Date(`${value.substring(0, 10)}T00:00:00+07:00`);
    }

    return new Date(hasExplicitTimezone(value) ? value : `${value}+07:00`);
};

const getWibDateParts = (date: Date) => {
    const parts = new Intl.DateTimeFormat('en-CA', {
        timeZone: ACTIVITY_TIME_ZONE,
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
    }).formatToParts(date);

    return {
        year: parts.find((part) => part.type === 'year')?.value,
        month: parts.find((part) => part.type === 'month')?.value,
        day: parts.find((part) => part.type === 'day')?.value,
    };
};

export const getDatePart = (value?: string | null): string => {
    if (!value) {
        return '';
    }

    if (!value.includes('T')) {
        return value.substring(0, 10);
    }

    if (!hasExplicitTimezone(value)) {
        return value.split('T')[0];
    }

    const date = parseActivityDate(value);
    if (Number.isNaN(date.getTime())) {
        return value.split('T')[0];
    }

    const { year, month, day } = getWibDateParts(date);

    return year && month && day ? `${year}-${month}-${day}` : value.split('T')[0];
};

export const getTimePart = (value?: string | null): string => {
    if (!value || !value.includes('T')) {
        return '';
    }

    if (!hasExplicitTimezone(value)) {
        return value.split('T')[1]?.substring(0, 5) || '';
    }

    const date = parseActivityDate(value);
    if (Number.isNaN(date.getTime())) {
        return value.split('T')[1]?.substring(0, 5) || '';
    }

    const parts = new Intl.DateTimeFormat('en-GB', {
        timeZone: ACTIVITY_TIME_ZONE,
        hour: '2-digit',
        minute: '2-digit',
        hourCycle: 'h23',
        hour12: false,
    }).formatToParts(date);

    const hour = parts.find((part) => part.type === 'hour')?.value;
    const minute = parts.find((part) => part.type === 'minute')?.value;

    return hour && minute ? `${hour}:${minute}` : value.split('T')[1]?.substring(0, 5) || '';
};

export const getTodayWibDate = (): string => getDatePart(new Date().toISOString());

export const toWibDate = (value?: string | null): Date | null => {
    const datePart = getDatePart(value);

    return datePart ? new Date(`${datePart}T00:00:00+07:00`) : null;
};

export const getWibDateDiffInDays = (value?: string | null): number | null => {
    const date = toWibDate(value);
    const today = toWibDate(getTodayWibDate());

    if (!date || !today) {
        return null;
    }

    return Math.round((date.getTime() - today.getTime()) / 86400000);
};

export const isTodayWib = (value?: string | null): boolean => getWibDateDiffInDays(value) === 0;

export const isTomorrowWib = (value?: string | null): boolean => getWibDateDiffInDays(value) === 1;

export const isPastWibDate = (value?: string | null): boolean => {
    const diff = getWibDateDiffInDays(value);

    return diff !== null && diff < 0;
};

export const isOverdueWib = (value?: string | null, status?: string): boolean => {
    if (!value || status === 'completed' || status === 'cancelled') {
        return false;
    }

    return isPastWibDate(value);
};

export const formatDateWib = (
    value?: string | null,
    options: Intl.DateTimeFormatOptions = { day: '2-digit', month: 'short', year: 'numeric' },
    locale = 'id-ID'
): string => {
    if (!value) {
        return '-';
    }

    const date = parseActivityDate(value);
    if (Number.isNaN(date.getTime())) {
        return '-';
    }

    return new Intl.DateTimeFormat(locale, {
        timeZone: ACTIVITY_TIME_ZONE,
        ...options,
    }).format(date);
};

export const formatDateTimeWib = (
    value?: string | null,
    options: Intl.DateTimeFormatOptions = { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' },
    locale = 'id-ID'
): string => {
    if (!value) {
        return '-';
    }

    const date = parseActivityDate(value);
    if (Number.isNaN(date.getTime())) {
        return '-';
    }

    return new Intl.DateTimeFormat(locale, {
        timeZone: ACTIVITY_TIME_ZONE,
        hourCycle: 'h23',
        hour12: false,
        ...options,
    }).format(date);
};

export const addDaysWibDate = (days: number): string => {
    const date = new Date();
    date.setUTCDate(date.getUTCDate() + days);

    return getDatePart(date.toISOString());
};
