import { afterEach, describe, expect, it, vi } from 'vitest';
import {
    formatDateTimeWib,
    getDatePart,
    getTimePart,
    getTodayWibDate,
    isOverdueWib,
    isTodayWib,
    isTomorrowWib,
} from '@/lib/activityDateTime';

describe('activityDateTime WIB helpers', () => {
    afterEach(() => {
        vi.useRealTimers();
    });

    it('converts utc timestamps to WIB date and 24-hour time', () => {
        expect(getDatePart('2026-04-13T01:30:00.000000Z')).toBe('2026-04-13');
        expect(getTimePart('2026-04-13T01:30:00.000000Z')).toBe('08:30');
        expect(formatDateTimeWib('2026-04-13T17:05:00.000000Z')).toContain('00.05');
    });

    it('uses WIB calendar day for today, tomorrow, and overdue checks', () => {
        vi.useFakeTimers();
        vi.setSystemTime(new Date('2026-04-13T18:00:00.000000Z'));

        expect(getTodayWibDate()).toBe('2026-04-14');
        expect(isTodayWib('2026-04-14')).toBe(true);
        expect(isTomorrowWib('2026-04-15')).toBe(true);
        expect(isOverdueWib('2026-04-13', 'planned')).toBe(true);
        expect(isOverdueWib('2026-04-13', 'completed')).toBe(false);
    });
});
