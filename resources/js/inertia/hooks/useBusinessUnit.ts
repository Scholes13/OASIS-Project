import { useCallback } from 'react';
import { router, usePage } from '@inertiajs/react';
import { showToast } from '@/components/ui/toast';
import {
    useBuTransitionStore,
    useIsTransitioning,
} from '@/stores/buTransitionStore';

export interface BusinessUnit {
    id: number;
    code: string;
    name: string;
    logo: string | null;
}

interface PagePropsWithBU {
    currentBusinessUnit: BusinessUnit | null;
    availableBusinessUnits: BusinessUnit[];
    [key: string]: unknown;
}

interface UseBusinessUnitReturn {
    /** Current active business unit */
    currentBusinessUnit: BusinessUnit | null;
    /** List of business units the user has access to */
    availableBusinessUnits: BusinessUnit[];
    /** Whether a BU switch is in progress (includes animation) */
    isSwitching: boolean;
    /** Switch to a different business unit with smooth transition */
    switchBusinessUnit: (businessUnitId: number) => Promise<void>;
    /** Reload current page data */
    reload: (only?: string[]) => void;
}

/**
 * Hook to manage business unit context and switching.
 * Uses Inertia page props for state and provides smooth transition animation.
 * Uses Inertia router.post for CSRF-safe API calls.
 * 
 * @param reloadOnly - Optional array of props to reload after BU switch
 * @returns Business unit state and actions
 */
export function useBusinessUnit(reloadOnly?: string[]): UseBusinessUnitReturn {
    // Get BU data from Inertia page props (single source of truth)
    const { currentBusinessUnit, availableBusinessUnits } = usePage<PagePropsWithBU>().props;
    const isTransitioning = useIsTransitioning();

    /**
     * Reload the current page with optional prop filtering
     */
    const reload = useCallback((only?: string[]) => {
        router.reload({
            only: only || reloadOnly,
        });
    }, [reloadOnly]);

    /**
     * Switch to a different business unit with smooth transition overlay
     * Uses Inertia router.post which automatically handles CSRF tokens
     */
    const switchBusinessUnit = useCallback(async (businessUnitId: number): Promise<void> => {
        // Don't switch if already on this BU or already transitioning
        if (currentBusinessUnit?.id === businessUnitId || isTransitioning) {
            return;
        }

        // Validate user has access to this BU (from Inertia props)
        const targetBU = availableBusinessUnits?.find(bu => bu.id === businessUnitId);
        if (!targetBU) {
            showToast.error('You do not have access to this business unit');
            return;
        }

        // Get transition store actions
        const transitionStore = useBuTransitionStore.getState();

        // Start transition animation
        transitionStore.startTransition(currentBusinessUnit, targetBU);

        // Brief delay for animation to start
        await new Promise(resolve => setTimeout(resolve, 200));
        transitionStore.setPhase('switching');

        // Use Inertia router.post - automatically handles CSRF token
        router.post(
            '/api/business-unit/switch',
            { business_unit_id: businessUnitId },
            {
                preserveState: false,
                preserveScroll: false,
                onSuccess: (page) => {
                    // Phase: Loading complete
                    transitionStore.setPhase('loading');
                    
                    // Complete transition with animation
                    setTimeout(() => {
                        transitionStore.completeTransition();
                        showToast.success(`Switched to ${targetBU.name}`);
                    }, 300);
                },
                onError: (errors) => {
                    const message = errors.message || 'Failed to switch business unit';
                    transitionStore.setError(message);
                    showToast.error(message);
                },
                onFinish: () => {
                    // Ensure transition completes even if there's an issue
                    if (transitionStore.phase === 'switching') {
                        transitionStore.completeTransition();
                    }
                },
            }
        );
    }, [currentBusinessUnit, availableBusinessUnits, isTransitioning]);

    return {
        currentBusinessUnit: currentBusinessUnit ?? null,
        availableBusinessUnits: availableBusinessUnits ?? [],
        isSwitching: isTransitioning,
        switchBusinessUnit,
        reload,
    };
}
