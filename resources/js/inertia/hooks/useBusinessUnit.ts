import { useState, useEffect, useCallback } from 'react';
import { router, usePage } from '@inertiajs/react';
import type { PageProps } from '@/types';

interface UseBusinessUnitReturn {
    currentBusinessUnit: PageProps['currentBusinessUnit'];
    isLoading: boolean;
    reload: (only?: string[]) => void;
}

/**
 * Hook to handle business unit context and auto-reload on BU switch
 * Listens to Livewire's business-unit-switched event from BU Switcher component
 */
export function useBusinessUnit(reloadOnly?: string[]): UseBusinessUnitReturn {
    const { currentBusinessUnit } = usePage<PageProps>().props;
    const [isLoading, setIsLoading] = useState(false);

    const reload = useCallback((only?: string[]) => {
        setIsLoading(true);
        router.reload({
            only: only || reloadOnly,
            onFinish: () => setIsLoading(false),
        });
    }, [reloadOnly]);

    useEffect(() => {
        const handleBuSwitch = (event: CustomEvent) => {
            console.log('[useBusinessUnit] BU switched:', event.detail);
            reload();
        };

        // Listen for BU switch event from Livewire (bridged via layout)
        window.addEventListener('bu-switched', handleBuSwitch as EventListener);

        return () => {
            window.removeEventListener('bu-switched', handleBuSwitch as EventListener);
        };
    }, [reload]);

    return {
        currentBusinessUnit,
        isLoading,
        reload,
    };
}
