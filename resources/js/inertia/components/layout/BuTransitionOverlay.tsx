import { useEffect, useRef } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { Check, AlertCircle, Loader2, ArrowRight } from 'lucide-react';
import { cn } from '../../lib/utils';
import { LazyLogo } from '../ui/LazyImage';
import {
    useBuTransitionStore,
    useBuTransitionPhase,
    useBuTransitionFrom,
    useBuTransitionTo,
    useBuTransitionSteps,
    useBuTransitionError,
    useIsTransitioning,
    type TransitionStep,
} from '../../stores/buTransitionStore';

/**
 * Logo display component with animation
 */
function AnimatedLogo({ 
    businessUnit, 
    type,
    isActive 
}: { 
    businessUnit: { code: string; name: string; logo: string | null } | null;
    type: 'from' | 'to';
    isActive: boolean;
}) {
    const isFrom = type === 'from';
    
    return (
        <motion.div
            initial={{ 
                opacity: 0, 
                scale: 0.5,
                x: isFrom ? 0 : 30 
            }}
            animate={{ 
                opacity: isActive ? (isFrom ? 0.5 : 1) : 0,
                scale: isActive ? 1 : 0.5,
                x: 0,
                filter: isFrom && isActive ? 'grayscale(0.5)' : 'none',
            }}
            transition={{ 
                type: 'spring',
                stiffness: 300,
                damping: 25,
                delay: isFrom ? 0 : 0.3,
            }}
            className={cn(
                'flex flex-col items-center gap-3',
                isFrom && 'opacity-50'
            )}
        >
            <div className={cn(
                'w-20 h-20 rounded-2xl flex items-center justify-center',
                'shadow-xl transition-all duration-300',
                businessUnit?.logo ? 'bg-white/80' : 'bg-gradient-to-br from-indigo-500 to-purple-600',
                isActive && !isFrom && 'ring-4 ring-indigo-500/30'
            )}>
                {businessUnit?.logo ? (
                    <LazyLogo
                        src={businessUnit.logo}
                        alt={businessUnit.name}
                        className="w-16 h-16 rounded-xl"
                        fallbackText={businessUnit.code}
                    />
                ) : (
                    <span className="text-2xl font-bold text-white">
                        {businessUnit?.code?.substring(0, 2) || 'BU'}
                    </span>
                )}
            </div>
            <span className={cn(
                'text-sm font-medium',
                isFrom ? 'text-gray-400' : 'text-gray-700'
            )}>
                {businessUnit?.name || 'Unknown'}
            </span>
        </motion.div>
    );
}

/**
 * Animated arrow between logos
 */
function TransitionArrow({ isActive }: { isActive: boolean }) {
    return (
        <motion.div
            initial={{ opacity: 0, scale: 0.3 }}
            animate={{ 
                opacity: isActive ? 1 : 0,
                scale: isActive ? 1 : 0.3,
            }}
            transition={{ 
                type: 'spring',
                stiffness: 400,
                damping: 25,
                delay: 0.5,
            }}
            className="mx-6"
        >
            <div className="w-12 h-12 rounded-full bg-gradient-to-r from-indigo-500 to-purple-500 flex items-center justify-center shadow-lg">
                <motion.div
                    animate={{ x: [0, 4, 0] }}
                    transition={{ 
                        repeat: Infinity, 
                        duration: 1,
                        ease: 'easeInOut',
                    }}
                >
                    <ArrowRight className="w-6 h-6 text-white" />
                </motion.div>
            </div>
        </motion.div>
    );
}

/**
 * Progress step indicator
 */
function StepIndicator({ step, index }: { step: TransitionStep; index: number }) {
    const getIcon = () => {
        switch (step.status) {
            case 'completed':
                return <Check className="w-4 h-4 text-green-600" />;
            case 'active':
                return <Loader2 className="w-4 h-4 text-indigo-600 animate-spin" />;
            case 'error':
                return <AlertCircle className="w-4 h-4 text-red-600" />;
            default:
                return <span className="w-4 h-4 rounded-full bg-gray-300" />;
        }
    };

    return (
        <motion.div
            initial={{ opacity: 0, y: 10 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: index * 0.1 }}
            className="flex items-center gap-3"
        >
            <div className={cn(
                'w-8 h-8 rounded-full flex items-center justify-center transition-colors',
                step.status === 'completed' && 'bg-green-100',
                step.status === 'active' && 'bg-indigo-100',
                step.status === 'error' && 'bg-red-100',
                step.status === 'pending' && 'bg-gray-100',
            )}>
                {getIcon()}
            </div>
            <span className={cn(
                'text-sm font-medium transition-colors',
                step.status === 'completed' && 'text-green-700',
                step.status === 'active' && 'text-indigo-700',
                step.status === 'error' && 'text-red-700',
                step.status === 'pending' && 'text-gray-400',
            )}>
                {step.label}
            </span>
        </motion.div>
    );
}

/**
 * Main BU Transition Overlay Component
 * 
 * Shows a full-screen glassmorphism overlay during business unit switch
 * with animated logos and progress steps.
 */
export default function BuTransitionOverlay() {
    const isTransitioning = useIsTransitioning();
    const phase = useBuTransitionPhase();
    const fromBu = useBuTransitionFrom();
    const toBu = useBuTransitionTo();
    const steps = useBuTransitionSteps();
    const errorMessage = useBuTransitionError();
    const overlayRef = useRef<HTMLDivElement>(null);

    // Prevent body scroll when overlay is active
    useEffect(() => {
        if (isTransitioning) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = '';
        }
        return () => {
            document.body.style.overflow = '';
        };
    }, [isTransitioning]);

    const isActive = phase !== 'idle' && phase !== 'completing';

    return (
        <AnimatePresence>
            {isTransitioning && (
                <motion.div
                    ref={overlayRef}
                    initial={{ opacity: 0 }}
                    animate={{ opacity: 1 }}
                    exit={{ opacity: 0 }}
                    transition={{ duration: 0.3 }}
                    className="fixed inset-0 z-[9999] flex items-center justify-center"
                >
                    {/* Glassmorphism backdrop */}
                    <div className="absolute inset-0 bg-white/70 backdrop-blur-xl" />
                    
                    {/* Gradient orbs for visual interest */}
                    <div className="absolute top-1/4 left-1/4 w-96 h-96 bg-indigo-400/20 rounded-full blur-3xl" />
                    <div className="absolute bottom-1/4 right-1/4 w-96 h-96 bg-purple-400/20 rounded-full blur-3xl" />
                    
                    {/* Content */}
                    <div className="relative z-10 flex flex-col items-center">
                        {/* Logo transition area */}
                        <div className="flex items-center mb-12">
                            <AnimatedLogo 
                                businessUnit={fromBu} 
                                type="from" 
                                isActive={isActive}
                            />
                            <TransitionArrow isActive={isActive} />
                            <AnimatedLogo 
                                businessUnit={toBu} 
                                type="to" 
                                isActive={isActive}
                            />
                        </div>

                        {/* Title */}
                        <motion.h2
                            initial={{ opacity: 0, y: 20 }}
                            animate={{ opacity: 1, y: 0 }}
                            transition={{ delay: 0.6 }}
                            className="text-2xl font-semibold text-gray-900 mb-2"
                        >
                            Switching to {toBu?.name || 'new context'}
                        </motion.h2>
                        
                        <motion.p
                            initial={{ opacity: 0, y: 20 }}
                            animate={{ opacity: 1, y: 0 }}
                            transition={{ delay: 0.7 }}
                            className="text-gray-500 mb-8"
                        >
                            Please wait while we prepare your workspace...
                        </motion.p>

                        {/* Progress steps */}
                        <motion.div
                            initial={{ opacity: 0, y: 20 }}
                            animate={{ opacity: 1, y: 0 }}
                            transition={{ delay: 0.8 }}
                            className="bg-white/80 backdrop-blur-sm rounded-2xl p-6 shadow-xl border border-gray-200/50 min-w-[320px]"
                        >
                            <div className="space-y-4">
                                {steps.map((step, index) => (
                                    <StepIndicator key={step.id} step={step} index={index} />
                                ))}
                            </div>

                            {/* Error message */}
                            {phase === 'error' && errorMessage && (
                                <motion.div
                                    initial={{ opacity: 0, height: 0 }}
                                    animate={{ opacity: 1, height: 'auto' }}
                                    className="mt-4 p-3 bg-red-50 border border-red-200 rounded-lg"
                                >
                                    <div className="flex items-start gap-2">
                                        <AlertCircle className="w-5 h-5 text-red-600 flex-shrink-0 mt-0.5" />
                                        <div>
                                            <p className="text-sm font-medium text-red-800">
                                                Something went wrong
                                            </p>
                                            <p className="text-sm text-red-600 mt-1">
                                                {errorMessage}
                                            </p>
                                        </div>
                                    </div>
                                    <button
                                        onClick={() => useBuTransitionStore.getState().reset()}
                                        className="mt-3 w-full py-2 px-4 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors"
                                    >
                                        Dismiss
                                    </button>
                                </motion.div>
                            )}
                        </motion.div>

                        {/* Loading bar */}
                        <motion.div
                            initial={{ opacity: 0 }}
                            animate={{ opacity: 1 }}
                            transition={{ delay: 1 }}
                            className="mt-8 w-64 h-1.5 bg-gray-200 rounded-full overflow-hidden"
                        >
                            <motion.div
                                className="h-full bg-gradient-to-r from-indigo-500 via-purple-500 to-indigo-500 rounded-full"
                                style={{ backgroundSize: '200% 100%' }}
                                animate={{
                                    backgroundPosition: ['0% 0%', '100% 0%', '0% 0%'],
                                }}
                                transition={{
                                    duration: 2,
                                    repeat: Infinity,
                                    ease: 'linear',
                                }}
                            />
                        </motion.div>
                    </div>
                </motion.div>
            )}
        </AnimatePresence>
    );
}
