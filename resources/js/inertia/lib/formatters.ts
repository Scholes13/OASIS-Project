/**
 * Formatting utilities for dates, currency, and other data
 */

/**
 * Format currency with thousand separators
 * @param amount - The amount to format
 * @param decimals - Number of decimal places (default: 0)
 * @returns Formatted currency string
 */
export function formatCurrency(amount: number | string, decimals: number = 0): string {
    const num = typeof amount === 'string' ? parseFloat(amount) : amount;
    
    if (isNaN(num)) {
        return '0';
    }
    
    return num.toLocaleString('id-ID', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals,
    });
}

/**
 * Format date to "DD MMM YYYY" format (e.g., "15 Jan 2025")
 * @param date - Date string or Date object
 * @returns Formatted date string
 */
export function formatDate(date: string | Date): string {
    if (!date) return '-';
    
    const d = typeof date === 'string' ? new Date(date) : date;
    
    if (isNaN(d.getTime())) {
        return '-';
    }
    
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    const day = d.getDate().toString().padStart(2, '0');
    const month = months[d.getMonth()];
    const year = d.getFullYear();
    
    return `${day} ${month} ${year}`;
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
export function formatDateTime(datetime: string | Date): string {
    if (!datetime) return '-';
    
    return `${formatDate(datetime)} ${formatTime(datetime)}`;
}

/**
 * Format relative time (e.g., "2 hours ago", "3 days ago")
 * @param datetime - Datetime string or Date object
 * @returns Relative time string
 */
export function formatRelativeTime(datetime: string | Date): string {
    if (!datetime) return '-';
    
    const d = typeof datetime === 'string' ? new Date(datetime) : datetime;
    
    if (isNaN(d.getTime())) {
        return '-';
    }
    
    const now = new Date();
    const diffMs = now.getTime() - d.getTime();
    const diffSecs = Math.floor(diffMs / 1000);
    const diffMins = Math.floor(diffSecs / 60);
    const diffHours = Math.floor(diffMins / 60);
    const diffDays = Math.floor(diffHours / 24);
    
    if (diffSecs < 60) {
        return 'just now';
    } else if (diffMins < 60) {
        return `${diffMins} minute${diffMins > 1 ? 's' : ''} ago`;
    } else if (diffHours < 24) {
        return `${diffHours} hour${diffHours > 1 ? 's' : ''} ago`;
    } else if (diffDays < 7) {
        return `${diffDays} day${diffDays > 1 ? 's' : ''} ago`;
    } else {
        return formatDate(d);
    }
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
export function formatFileSize(bytes: number): string {
    if (bytes === 0) return '0 Bytes';
    
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    
    return `${parseFloat((bytes / Math.pow(k, i)).toFixed(2))} ${sizes[i]}`;
}
