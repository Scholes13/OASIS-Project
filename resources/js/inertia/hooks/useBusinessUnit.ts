import { useCallback } from 'react';
import { router } from '@inertiajs/react';
import { showToast } from '@/components/ui/toast';
import {
    useLayoutStore,
    useCurrentBusinessUnit,
    useAvailableBusinessUnits,
    useIsSwitchingBU,
    type BusinessUnit,
} from '@/stores/layoutStore';

export type { BusinessUnit };

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
    /** Whether a BU switch is in progress */
    isSwitching: boolean;
    /** Switch to a different business unit */
    switchBusinessUnit: (businessUnitId: number) => Promise<void>;
    /** Reload current page data */
    reload: (only?: string[]) => void;
}

/**
 * Hook to manage business unit context and switching.
 * Uses Zustand store for state management and integrates with BU switch API.
 * 
 * @param reloadOnly - Optional array of props to reload after BU switch
 * @returns Business unit state and actions
 * 
 * @example
 * ```tsx
 * const { currentBusinessUnit, switchBusinessUnit, isSwitching } = useBusinessUnit();
 * 
 * // Switch to a different BU
 * await switchBusinessUnit(2);
 * ```
 */
export function useBusinessUnit(reloadOnly?: string[]): UseBusinessUnitReturn {
    // Use selective subscriptions for performance
    const currentBusinessUnit = useCurrentBusinessUnit();
    const availableBusinessUnits = useAvailableBusinessUnits();
    const isSwitching = useIsSwitchingBU();
    
    // Get actions from store
    const { setSwitchingBU, setCurrentBusinessUnit } = useLayoutStore.getState();

    /**
     * Reload the current page with optional prop filtering
     */
    const reload = useCallback((only?: string[]) => {
        router.reload({
            only: only || reloadOnly,
        });
    }, [reloadOnly]);

    /**
     * Switch to a different business unit
     * - Validates user has access to the BU
     * - Updates server session
     * - Triggers Inertia reload to refresh page data
     */
    const switchBusinessUnit = useCallback(async (businessUnitId: number): Promise<void> => {
        // Don't switch if already on this BU
        if (currentBusinessUnit?.id === businessUnitId) {
            return;
        }

        // Validate user has access to this BU
        const targetBU = availableBusinessUnits.find(bu => bu.id === businessUnitId);
        if (!targetBU) {
            showToast.error('You do not have access to this business unit');
            return;
        }

        setSwitchingBU(true);

        try {
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
                // Update store with new BU
                setCurrentBusinessUnit(data.businessUnit);
                
                // Trigger Inertia reload to refresh all page data
                router.reload({
                    only: reloadOnly,
                    onFinish: () => {
                        setSwitchingBU(false);
                        showToast.success(`Switched to ${data.businessUnit.name}`);
                    },
                });
            } else {
                throw new Error(data.message || 'Failed to switch business unit');
            }
        } catch (error) {
            setSwitchingBU(false);
            
            const message = error instanceof Error 
                ? error.message 
                : 'Failed to switch business unit. Please try again.';
            
            showToast.error(message);
            throw error;
        }
    }, [currentBusinessUnit?.id, availableBusinessUnits, reloadOnly, setSwitchingBU, setCurrentBusinessUnit]);

    return {
        currentBusinessUnit,
        availableBusinessUnits,
        isSwitching,
        switchBusinessUnit,
        reload,
    };
}
