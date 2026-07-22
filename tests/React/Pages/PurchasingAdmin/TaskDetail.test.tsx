import { describe, expect, it } from 'vitest';
import { formatPercentage } from '@/Pages/PurchasingAdmin/TaskDetail';

describe('Purchasing admin task detail formatting', () => {
    it('formats decimal strings returned by Laravel without throwing', () => {
        expect(formatPercentage('0.00')).toBe('0.00%');
        expect(formatPercentage('-12.5')).toBe('-12.50%');
    });

    it('handles missing or invalid percentage values safely', () => {
        expect(formatPercentage(null)).toBe('-');
        expect(formatPercentage('not-a-number')).toBe('-');
    });
});
