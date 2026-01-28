/**
 * useFullScreenLoader Hook
 * 
 * Hook for managing full-screen loading states with step tracking.
 * Perfect for business unit switching, heavy operations, or multi-step processes.
 * 
 * Usage:
 * ```tsx
 * const { 
 *   isLoading, 
 *   steps, 
 *   startLoading, 
 *   completeStep, 
 *   stopLoading,
 *   loaderProps 
 * } = useFullScreenLoader();
 * 
 * // Start loading with steps
 * startLoading({
 *   title: "Switching Business Unit",
 *   fromBu: currentBu,
 *   toBu: targetBu,
 *   steps: [
 *     { id: 'prepare', label: 'Preparing switch...' },
 *     { id: 'context', label: 'Switching context...' },
 *     { id: 'data', label: 'Loading data...' },
 *     { id: 'done', label: 'Almost done...' },
 *   ]
 * });
 * 
 * // Complete steps as they finish
 * completeStep('prepare');
 * completeStep('context');
 * 
 * // In your JSX
 * <FullScreenLoader {...loaderProps} />
 * ```
 */

import { useState, useCallback, useMemo } from 'react';

interface BusinessUnit {
    id?: number | null;
    code?: string;
    name?: string;
    logo?: string | null;
}

interface LoadingStep {
    id: string;
    label: string;
    completed: boolean;
}

interface LoaderConfig {
    title?: string;
    subtitle?: string;
    fromBu?: BusinessUnit | null;
    toBu?: BusinessUnit | null;
    steps?: Array<{ id: string; label: string }>;
}

interface LoaderState {
    isOpen: boolean;
    title: string;
    subtitle?: string;
    fromBu?: BusinessUnit | null;
    toBu?: BusinessUnit | null;
    steps: LoadingStep[];
}

export function useFullScreenLoader() {
    const [state, setState] = useState<LoaderState>({
        isOpen: false,
        title: 'Loading...',
        steps: [],
    });

    const startLoading = useCallback((config: LoaderConfig = {}) => {
        setState({
            isOpen: true,
            title: config.title || 'Loading...',
            subtitle: config.subtitle,
            fromBu: config.fromBu,
            toBu: config.toBu,
            steps: (config.steps || []).map(s => ({ ...s, completed: false })),
        });
    }, []);

    const completeStep = useCallback((stepId: string) => {
        setState(prev => ({
            ...prev,
            steps: prev.steps.map(s => 
                s.id === stepId ? { ...s, completed: true } : s
            ),
        }));
    }, []);

    const completeAllSteps = useCallback(() => {
        setState(prev => ({
            ...prev,
            steps: prev.steps.map(s => ({ ...s, completed: true })),
        }));
    }, []);

    const stopLoading = useCallback(() => {
        setState(prev => ({ ...prev, isOpen: false }));
    }, []);

    const updateTitle = useCallback((title: string, subtitle?: string) => {
        setState(prev => ({ ...prev, title, subtitle }));
    }, []);

    // Props to spread directly to FullScreenLoader component
    const loaderProps = useMemo(() => ({
        isOpen: state.isOpen,
        title: state.title,
        subtitle: state.subtitle,
        fromBu: state.fromBu,
        toBu: state.toBu,
        steps: state.steps,
        onComplete: stopLoading,
    }), [state, stopLoading]);

    return {
        isLoading: state.isOpen,
        steps: state.steps,
        startLoading,
        completeStep,
        completeAllSteps,
        stopLoading,
        updateTitle,
        loaderProps,
    };
}

/**
 * Preset configurations for common loading scenarios
 */
export const loaderPresets = {
    businessUnitSwitch: (fromBu: BusinessUnit | null, toBu: BusinessUnit | null) => ({
        title: 'Switching Business Unit',
        fromBu,
        toBu,
        steps: [
            { id: 'prepare', label: 'Preparing switch...' },
            { id: 'context', label: 'Switching context...' },
            { id: 'data', label: 'Loading data...' },
            { id: 'done', label: 'Almost done...' },
        ],
    }),
    
    pageLoad: (pageName: string) => ({
        title: `Loading ${pageName}`,
        steps: [
            { id: 'fetch', label: 'Fetching data...' },
            { id: 'render', label: 'Preparing view...' },
        ],
    }),
    
    formSubmit: (action: string) => ({
        title: action,
        steps: [
            { id: 'validate', label: 'Validating...' },
            { id: 'submit', label: 'Submitting...' },
            { id: 'complete', label: 'Completing...' },
        ],
    }),
    
    dataExport: () => ({
        title: 'Exporting Data',
        steps: [
            { id: 'prepare', label: 'Preparing data...' },
            { id: 'generate', label: 'Generating file...' },
            { id: 'download', label: 'Starting download...' },
        ],
    }),
};

export default useFullScreenLoader;
