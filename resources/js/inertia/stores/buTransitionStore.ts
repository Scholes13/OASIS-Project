import { create } from 'zustand';
import type { BusinessUnit } from './layoutStore';

/**
 * Transition phases for BU switch animation
 */
export type TransitionPhase = 
    | 'idle'           // No transition
    | 'prepare'        // Show overlay, display FROM logo
    | 'switching'      // API call in progress
    | 'loading'        // Inertia reload in progress
    | 'completing'     // Fade out animation
    | 'error';         // Error state

/**
 * Progress steps shown to user during transition
 */
export interface TransitionStep {
    id: string;
    label: string;
    status: 'pending' | 'active' | 'completed' | 'error';
}

interface BuTransitionState {
    // Core state
    phase: TransitionPhase;
    fromBusinessUnit: BusinessUnit | null;
    toBusinessUnit: BusinessUnit | null;
    
    // Progress tracking
    steps: TransitionStep[];
    currentStepIndex: number;
    
    // Error handling
    errorMessage: string | null;
    
    // Timing
    startTime: number | null;
    minDisplayTime: number; // Minimum time to show overlay (ms)
}

interface BuTransitionActions {
    // Start transition
    startTransition: (from: BusinessUnit | null, to: BusinessUnit) => void;
    
    // Phase progression
    setPhase: (phase: TransitionPhase) => void;
    advanceStep: () => void;
    completeStep: (stepId: string) => void;
    
    // Error handling
    setError: (message: string) => void;
    
    // Complete/Reset
    completeTransition: () => void;
    reset: () => void;
}

type BuTransitionStore = BuTransitionState & BuTransitionActions;

const createDefaultSteps = (): TransitionStep[] => [
    { id: 'prepare', label: 'Preparing switch...', status: 'pending' },
    { id: 'switch', label: 'Switching context...', status: 'pending' },
    { id: 'load', label: 'Loading data...', status: 'pending' },
    { id: 'complete', label: 'Almost done...', status: 'pending' },
];

const INITIAL_STATE: BuTransitionState = {
    phase: 'idle',
    fromBusinessUnit: null,
    toBusinessUnit: null,
    steps: [],
    currentStepIndex: -1,
    errorMessage: null,
    startTime: null,
    minDisplayTime: 1200, // 1.2 seconds minimum for smooth UX
};

export const useBuTransitionStore = create<BuTransitionStore>((set, get) => ({
    ...INITIAL_STATE,

    startTransition: (from, to) => {
        // Create fresh steps array
        const steps = createDefaultSteps();
        steps[0].status = 'active';
        
        set({
            phase: 'prepare',
            fromBusinessUnit: from,
            toBusinessUnit: to,
            steps,
            currentStepIndex: 0,
            errorMessage: null,
            startTime: Date.now(),
        });
    },

    setPhase: (phase) => {
        const { currentStepIndex, steps } = get();
        
        // Map phase to step index
        const phaseToStep: Record<TransitionPhase, number> = {
            'idle': -1,
            'prepare': 0,
            'switching': 1,
            'loading': 2,
            'completing': 3,
            'error': currentStepIndex,
        };
        
        const newStepIndex = phaseToStep[phase];
        const newSteps = [...steps];
        
        // Mark previous steps as completed
        for (let i = 0; i < newStepIndex; i++) {
            newSteps[i] = { ...newSteps[i], status: 'completed' };
        }
        
        // Mark current step as active (unless error)
        if (phase !== 'error' && newStepIndex >= 0 && newStepIndex < newSteps.length) {
            newSteps[newStepIndex] = { ...newSteps[newStepIndex], status: 'active' };
        }
        
        set({ phase, currentStepIndex: newStepIndex, steps: newSteps });
    },

    advanceStep: () => {
        const { currentStepIndex, steps } = get();
        const nextIndex = currentStepIndex + 1;
        
        if (nextIndex < steps.length) {
            const newSteps = [...steps];
            
            // Complete current step
            if (currentStepIndex >= 0) {
                newSteps[currentStepIndex] = { ...newSteps[currentStepIndex], status: 'completed' };
            }
            
            // Activate next step
            newSteps[nextIndex] = { ...newSteps[nextIndex], status: 'active' };
            
            set({ currentStepIndex: nextIndex, steps: newSteps });
        }
    },

    completeStep: (stepId) => {
        const { steps } = get();
        const newSteps = steps.map(step => 
            step.id === stepId ? { ...step, status: 'completed' as const } : step
        );
        set({ steps: newSteps });
    },

    setError: (message) => {
        const { currentStepIndex, steps } = get();
        const newSteps = [...steps];
        
        // Mark current step as error
        if (currentStepIndex >= 0) {
            newSteps[currentStepIndex] = { ...newSteps[currentStepIndex], status: 'error' };
        }
        
        set({ 
            phase: 'error', 
            errorMessage: message,
            steps: newSteps,
        });
    },

    completeTransition: () => {
        const { startTime, minDisplayTime, steps } = get();
        const elapsed = startTime ? Date.now() - startTime : 0;
        const remaining = Math.max(0, minDisplayTime - elapsed);
        
        // Mark all steps as completed
        const completedSteps = steps.map(step => ({ ...step, status: 'completed' as const }));
        set({ steps: completedSteps, phase: 'completing' });
        
        // Delay reset to ensure minimum display time
        setTimeout(() => {
            get().reset();
        }, remaining + 300); // Add 300ms for fade out animation
    },

    reset: () => {
        set(INITIAL_STATE);
    },
}));

// Selectors
export const useBuTransitionPhase = () => useBuTransitionStore(state => state.phase);
export const useBuTransitionFrom = () => useBuTransitionStore(state => state.fromBusinessUnit);
export const useBuTransitionTo = () => useBuTransitionStore(state => state.toBusinessUnit);
export const useBuTransitionSteps = () => useBuTransitionStore(state => state.steps);
export const useBuTransitionError = () => useBuTransitionStore(state => state.errorMessage);
export const useIsTransitioning = () => useBuTransitionStore(state => state.phase !== 'idle');
