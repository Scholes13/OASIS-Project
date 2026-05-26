/**
 * Formatting utilities for dates, currency, and other data.
 */

/**
 * Format currency with thousand separators
 * @param amount - The amount to format
 * @param decimalsOrCurrency - Number of decimal places (default: 0) or currency code (ignored, for backward compatibility)
 * @returns Formatted currency string
 */
export function formatCurrency(
    amount: number | string | null | undefined,
    options: { currency?: string; locale?: string } | number | string = {}
): string {
    if (amount === null || amount === undefined || amount === '') return '0';

    const numericAmount = typeof amount === 'string' ? Number(amount) : amount;

    if (!Number.isFinite(numericAmount)) return '0';

    const legacyDecimals = typeof options === 'number' ? options : 0;
    const locale = typeof options === 'object' ? options.locale ?? 'id-ID' : 'id-ID';
    const currency = typeof options === 'object' ? options.currency ?? 'IDR' : options;

    return new Intl.NumberFormat(locale, {
        style: 'decimal',
        minimumFractionDigits: Math.max(0, Math.min(20, legacyDecimals)),
        maximumFractionDigits: Math.max(0, Math.min(20, legacyDecimals)),
        currency: typeof currency === 'string' ? currency : 'IDR',
    }).format(numericAmount);
}

/**
 * Format date to "DD MMM YYYY" format (e.g., "15 Jan 2025")
 * @param date - Date string or Date object
 * @returns Formatted date string
 */
export function formatDate(date: string | Date | null | undefined, options: { locale?: string; format?: 'short' | 'medium' | 'long' } = {}): string {
    if (!date) return '-';
    
    const d = typeof date === 'string' ? new Date(date) : date;
    
    if (isNaN(d.getTime())) {
        return '-';
    }
    
    const monthFormat: 'numeric' | '2-digit' | 'long' | 'short' | 'narrow' = options.format === 'short' ? 'short' : 'long';

    return new Intl.DateTimeFormat(options.locale ?? 'id-ID', {
        day: '2-digit',
        month: monthFormat,
        year: 'numeric',
    }).format(d);
}

/**
 * Format time to "HH:MM" format (e.g., "14:30")
 * @param datetime - Datetime string or Date object
 * @returns Formatted time string
 */
export function formatTime(datetime: string | Date): string {
    if (!datetime) return '-';
    
    const d = typeof datetime === 'string' ? new Date(datetime) : datetime;
    
    if (isNaN(d.getTime())) {
        return '-';
    }
    
    const hours = d.getHours().toString().padStart(2, '0');
    const minutes = d.getMinutes().toString().padStart(2, '0');
    
    return `${hours}:${minutes}`;
}

/**
 * Format datetime to "DD MMM YYYY HH:MM" format
 * @param datetime - Datetime string or Date object
 * @returns Formatted datetime string
 */
export function formatDateTime(datetime: string | Date | null | undefined, options: { locale?: string } = {}): string {
    if (!datetime) return '-';

    const d = typeof datetime === 'string' ? new Date(datetime) : datetime;
    if (isNaN(d.getTime())) return '-';

    return new Intl.DateTimeFormat(options.locale ?? 'id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(d);
}

/**
 * Format relative time (e.g., "2 hours ago", "3 days ago")
 * @param datetime - Datetime string or Date object
 * @returns Relative time string
 */
export function formatRelativeTime(datetime: string | Date | null | undefined): string {
    if (!datetime) return '-';
    
    const d = typeof datetime === 'string' ? new Date(datetime) : datetime;
    
    if (isNaN(d.getTime())) {
        return '-';
    }
    
    const now = new Date();
    const diffSeconds = Math.round((d.getTime() - now.getTime()) / 1000);
    const divisions: Array<{ amount: number; unit: Intl.RelativeTimeFormatUnit }> = [
        { amount: 60, unit: 'second' },
        { amount: 60, unit: 'minute' },
        { amount: 24, unit: 'hour' },
        { amount: 7, unit: 'day' },
        { amount: 4.34524, unit: 'week' },
        { amount: 12, unit: 'month' },
        { amount: Number.POSITIVE_INFINITY, unit: 'year' },
    ];
    const formatter = new Intl.RelativeTimeFormat('en', { numeric: 'auto' });
    let duration = diffSeconds;

    for (const division of divisions) {
        if (Math.abs(duration) < division.amount) {
            return formatter.format(Math.round(duration), division.unit);
        }
        duration /= division.amount;
    }

    return formatter.format(Math.round(duration), 'year');
}

export function formatPercent(value: number | null | undefined, options: { fractionDigits?: number } = {}): string {
    if (value === null || value === undefined || !Number.isFinite(value)) return '0%';

    const fractionDigits = options.fractionDigits ?? 0;

    return new Intl.NumberFormat('id-ID', {
        style: 'percent',
        minimumFractionDigits: fractionDigits,
        maximumFractionDigits: fractionDigits,
    }).format(value / 100);
}

/**
 * Truncate text to specified length with ellipsis
 * @param text - Text to truncate
 * @param length - Maximum length
 * @returns Truncated text
 */
export function truncate(text: string, length: number = 50): string {
    if (!text) return '';
    
    if (text.length <= length) {
        return text;
    }
    
    return `${text.substring(0, length)}...`;
}

/**
 * Format file size to human-readable format
 * @param bytes - File size in bytes
 * @returns Formatted file size string
 */
export function formatFileSize(bytes: number | null | undefined): string {
    if (!bytes || bytes < 0) return '0 Bytes';
    
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    
    return `${parseFloat((bytes / Math.pow(k, i)).toFixed(2))} ${sizes[i]}`;
}
