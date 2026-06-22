import { useState, useCallback } from 'react';
import { router } from '@inertiajs/react';
import type { TaskFilters } from '@/types';

interface UseFiltersOptions {
    routeName: string;
    debounceMs?: number;
    preserveState?: boolean;
    preserveScroll?: boolean;
    only?: string[];
}

interface UseFiltersReturn {
    filters: TaskFilters;
    setFilter: (key: keyof TaskFilters, value: string) => void;
    setFilters: (newFilters: Partial<TaskFilters>) => void;
    resetFilters: () => void;
    isFiltering: boolean;
}

const defaultFilters: TaskFilters = {
    search: '',
    activity_type_id: '',
    status: '',
    date_from: '',
    date_to: '',
    member_user_id: '',
};

/**
 * Hook to manage filter state with debounced URL updates
 */
export function useFilters(
    initialFilters: TaskFilters,
    options: UseFiltersOptions
): UseFiltersReturn {
    const [filters, setFiltersState] = useState<TaskFilters>({
        ...defaultFilters,
        ...initialFilters,
    });
    const [isFiltering, setIsFiltering] = useState(false);
    const [debounceTimer, setDebounceTimer] = useState<NodeJS.Timeout | null>(null);

    const applyFilters = useCallback((newFilters: TaskFilters) => {
        if (debounceTimer) {
            clearTimeout(debounceTimer);
        }

        const timer = setTimeout(() => {
            setIsFiltering(true);
            router.get(
                route(options.routeName),
                Object.fromEntries(
                    Object.entries(newFilters).filter(([_, v]) => v !== '')
                ),
                {
                    preserveState: options.preserveState ?? true,
                    preserveScroll: options.preserveScroll ?? true,
                    only: options.only,
                    onFinish: () => setIsFiltering(false),
                }
            );
        }, options.debounceMs ?? 300);

        setDebounceTimer(timer);
    }, [options, debounceTimer]);

    const setFilter = useCallback((key: keyof TaskFilters, value: string) => {
        const newFilters = { ...filters, [key]: value };
        setFiltersState(newFilters);
        applyFilters(newFilters);
    }, [filters, applyFilters]);

    const setFilters = useCallback((newFilters: Partial<TaskFilters>) => {
        const updated = { ...filters, ...newFilters };
        setFiltersState(updated);
        applyFilters(updated);
    }, [filters, applyFilters]);

    const resetFilters = useCallback(() => {
        const threeMonthsAgo = new Date();
        threeMonthsAgo.setMonth(threeMonthsAgo.getMonth() - 3);
        
        const reset: TaskFilters = {
            search: '',
            activity_type_id: '',
            status: '',
            date_from: threeMonthsAgo.toISOString().split('T')[0],
            date_to: new Date().toISOString().split('T')[0],
            member_user_id: '',
        };
        setFiltersState(reset);
        applyFilters(reset);
    }, [applyFilters]);

    return {
        filters,
        setFilter,
        setFilters,
        resetFilters,
        isFiltering,
    };
}
