import { describe, expect, it } from 'vitest';
import { buildTaskFiltersQuery } from '@/components/purchasing-admin/PurchasingTaskList';

describe('Purchasing task filter query', () => {
    it('keeps only meaningful filters in the URL', () => {
        expect(buildTaskFiltersQuery({
            status: 'completed',
            type: '',
            date: 'all',
            search: '',
        })).toEqual({ status: 'completed' });
    });

    it('omits every default filter', () => {
        expect(buildTaskFiltersQuery({
            status: 'pending',
            type: '',
            date: 'all',
            search: '   ',
        })).toEqual({});
    });
});
