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

interface SwitchBusinessUnitResponse {
    success: boolean;
    businessUnit: BusinessUnit;
    message?: string;
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
 * 
 * @param reloadOnly - Optional array of props to reload after BU switch
 * @returns Business unit state and actions
 * 
 * @example
 * ```tsx
 * const { currentBusinessUnit, switchBusinessUnit, isSwitching } = useBusinessUnit();
 * 
 * // Switch to a different BU with animated transition
 * await switchBusinessUnit(2);
 * ```
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
     * 
     * Flow:
     * 1. Show transition overlay with FROM logo
     * 2. Make API call to switch context
     * 3. Update progress steps
     * 4. Trigger Inertia reload
     * 5. Complete transition with animation
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

        try {
            // Phase: Switching (API call)
            await new Promise(resolve => setTimeout(resolve, 200)); // Brief delay for animation
            transitionStore.setPhase('switching');

            const response = await fetch('/api/business-unit/switch', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify({ business_unit_id: businessUnitId }),
            });

            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));

                if (response.status === 403) {
                    throw new Error(errorData.message || 'You do not have access to this business unit');
                } else if (response.status === 404) {
                    throw new Error(errorData.message || 'Business unit not found');
                } else {
                    throw new Error(errorData.message || 'Failed to switch business unit');
                }
            }

            const data: SwitchBusinessUnitResponse = await response.json();

            if (data.success) {
                // Phase: Loading (Inertia reload)
                transitionStore.setPhase('loading');

                // Trigger Inertia reload to refresh all page data
                // Only include 'only' option when reloadOnly has values (prevents undefined.length error)
                const reloadOptions: { only?: string[]; onFinish: () => void } = {
                    onFinish: () => {
                        // Complete transition with animation
                        transitionStore.completeTransition();
                        showToast.success(`Switched to ${data.businessUnit.name}`);
                    },
                };

                if (reloadOnly && reloadOnly.length > 0) {
                    reloadOptions.only = reloadOnly;
                }

                router.reload(reloadOptions);
            } else {
                throw new Error(data.message || 'Failed to switch business unit');
            }
        } catch (error) {
            const transitionStore = useBuTransitionStore.getState();
            const message = error instanceof Error
                ? error.message
                : 'Failed to switch business unit. Please try again.';

            transitionStore.setError(message);
            throw error;
        }
    }, [currentBusinessUnit, availableBusinessUnits, isTransitioning, reloadOnly]);

    return {
        currentBusinessUnit: currentBusinessUnit ?? null,
        availableBusinessUnits: availableBusinessUnits ?? [],
        isSwitching: isTransitioning,
        switchBusinessUnit,
        reload,
    };
}
