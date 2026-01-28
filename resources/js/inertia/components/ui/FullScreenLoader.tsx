/**
 * FullScreenLoader Component
 * 
 * Modern full-screen loading overlay with Framer Motion animations.
 * Designed for business unit switching, page transitions, and heavy operations.
 * 
 * Features:
 * - Smooth entrance/exit animations with Framer Motion
 * - Staged animation sequence for logo transitions
 * - Progress steps with animated checkmarks
 * - Glassmorphism backdrop
 * - Customizable messages and logos
 */

import { useState, useEffect } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { Check, Building2, ArrowRight } from 'lucide-react';
import { cn } from '@/lib/utils';

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

interface FullScreenLoaderProps {
    isOpen: boolean;
    title?: string;
    subtitle?: string;
    fromBu?: BusinessUnit | null;
    toBu?: BusinessUnit | null;
    steps?: LoadingStep[];
    onComplete?: () => void;
}

// Spring animation config for bouncy feel
const springConfig = { type: "spring" as const, stiffness: 400, damping: 30 };
const smoothSpring = { type: "spring" as const, stiffness: 300, damping: 35 };

// Logo component with fallback to initials
function BuLogo({ bu, className, isFrom = false }: { bu?: BusinessUnit | null; className?: string; isFrom?: boolean }) {
    if (!bu) return null;
    
    const gradientClass = isFrom 
        ? "from-indigo-500 to-purple-600" 
        : "from-emerald-500 to-teal-600";
    
    if (bu.logo) {
        return (
            <div className={cn("relative overflow-hidden rounded-2xl", className)}>
                <img 
                    src={`/storage/${bu.logo}`} 
                    alt={bu.code || 'BU'} 
                    className="w-full h-full object-contain"
                />
            </div>
        );
    }
    
    return (
        <div className={cn(
            "flex items-center justify-center rounded-2xl bg-gradient-to-br shadow-lg",
            gradientClass,
            className
        )}>
            <span className="text-2xl font-bold text-white">
                {(bu.code || 'BU').substring(0, 2)}
            </span>
        </div>
    );
}

// Animated checkmark for completed steps
function AnimatedCheck({ delay = 0 }: { delay?: number }) {
    return (
        <motion.div
            initial={{ scale: 0, opacity: 0 }}
            animate={{ scale: 1, opacity: 1 }}
            transition={{ ...springConfig, delay }}
            className="w-5 h-5 rounded-full bg-emerald-500 flex items-center justify-center"
        >
            <motion.div
                initial={{ pathLength: 0 }}
                animate={{ pathLength: 1 }}
                transition={{ duration: 0.3, delay: delay + 0.1 }}
            >
                <Check className="w-3 h-3 text-white" strokeWidth={3} />
            </motion.div>
        </motion.div>
    );
}

// Pulsing dot for loading state
function PulsingDot({ delay = 0 }: { delay?: number }) {
    return (
        <motion.div
            className="w-2 h-2 rounded-full bg-indigo-500"
            animate={{
                scale: [1, 1.3, 1],
                opacity: [0.5, 1, 0.5],
            }}
            transition={{
                duration: 1,
                repeat: Infinity,
                delay,
                ease: "easeInOut",
            }}
        />
    );
}

// Loading spinner with modern design
function ModernSpinner() {
    return (
        <div className="relative w-12 h-12">
            {/* Outer ring */}
            <motion.div
                className="absolute inset-0 rounded-full border-2 border-gray-200"
            />
            {/* Spinning gradient ring */}
            <motion.div
                className="absolute inset-0 rounded-full border-2 border-transparent border-t-indigo-500 border-r-indigo-400"
                animate={{ rotate: 360 }}
                transition={{ duration: 1, repeat: Infinity, ease: "linear" }}
            />
            {/* Center icon */}
            <div className="absolute inset-0 flex items-center justify-center">
                <Building2 className="w-5 h-5 text-indigo-600" />
            </div>
        </div>
    );
}

// Progress bar with shimmer effect
function ShimmerProgress() {
    return (
        <div className="w-48 h-1.5 bg-gray-200 rounded-full overflow-hidden">
            <motion.div
                className="h-full bg-gradient-to-r from-indigo-500 via-purple-500 to-indigo-500"
                style={{ backgroundSize: '200% 100%' }}
                animate={{ backgroundPosition: ['0% 0%', '200% 0%'] }}
                transition={{ duration: 1.5, repeat: Infinity, ease: "linear" }}
            />
        </div>
    );
}

export function FullScreenLoader({
    isOpen,
    title = "Switching Context",
    subtitle,
    fromBu,
    toBu,
    steps = [],
    onComplete,
}: FullScreenLoaderProps) {
    const [animationStage, setAnimationStage] = useState(0);
    
    // Progress through animation stages
    useEffect(() => {
        if (!isOpen) {
            setAnimationStage(0);
            return;
        }
        
        const timers = [
            setTimeout(() => setAnimationStage(1), 100),   // From logo appears
            setTimeout(() => setAnimationStage(2), 500),   // From slides left
            setTimeout(() => setAnimationStage(3), 800),   // Arrow + To appears
            setTimeout(() => setAnimationStage(4), 1200),  // From fades
        ];
        
        return () => timers.forEach(clearTimeout);
    }, [isOpen]);
    
    // Call onComplete when all steps are done
    useEffect(() => {
        if (steps.length > 0 && steps.every(s => s.completed) && onComplete) {
            const timer = setTimeout(onComplete, 500);
            return () => clearTimeout(timer);
        }
    }, [steps, onComplete]);

    return (
        <AnimatePresence>
            {isOpen && (
                <motion.div
                    initial={{ opacity: 0 }}
                    animate={{ opacity: 1 }}
                    exit={{ opacity: 0 }}
                    transition={{ duration: 0.3 }}
                    className="fixed inset-0 z-[99999] flex items-center justify-center"
                >
                    {/* Backdrop with blur */}
                    <motion.div 
                        className="absolute inset-0 bg-white/80 backdrop-blur-xl"
                        initial={{ opacity: 0 }}
                        animate={{ opacity: 1 }}
                        exit={{ opacity: 0 }}
                    />
                    
                    {/* Content */}
                    <motion.div
                        initial={{ opacity: 0, y: 20, scale: 0.95 }}
                        animate={{ opacity: 1, y: 0, scale: 1 }}
                        exit={{ opacity: 0, y: -20, scale: 0.95 }}
                        transition={smoothSpring}
                        className="relative z-10 flex flex-col items-center text-center px-6"
                    >
                        {/* Logo Transition Area */}
                        {(fromBu || toBu) && (
                            <div className="relative h-24 w-80 mb-8 flex items-center justify-center">
                                {/* From Logo */}
                                <motion.div
                                    initial={{ opacity: 0, scale: 0.5, x: 0 }}
                                    animate={{
                                        opacity: animationStage >= 4 ? 0.4 : animationStage >= 1 ? 1 : 0,
                                        scale: animationStage >= 4 ? 0.85 : animationStage >= 1 ? 1 : 0.5,
                                        x: animationStage >= 2 ? -100 : 0,
                                        filter: animationStage >= 4 ? 'grayscale(0.5)' : 'grayscale(0)',
                                    }}
                                    transition={smoothSpring}
                                    className="absolute"
                                >
                                    <BuLogo bu={fromBu} className="w-20 h-20" isFrom />
                                </motion.div>
                                
                                {/* Arrow Animation */}
                                <motion.div
                                    initial={{ opacity: 0, scale: 0.3 }}
                                    animate={{
                                        opacity: animationStage >= 3 ? 1 : 0,
                                        scale: animationStage >= 3 ? 1 : 0.3,
                                    }}
                                    transition={springConfig}
                                    className="absolute flex items-center gap-1"
                                >
                                    <PulsingDot delay={0} />
                                    <PulsingDot delay={0.15} />
                                    <PulsingDot delay={0.3} />
                                    <motion.div
                                        animate={{ x: [0, 5, 0] }}
                                        transition={{ duration: 1, repeat: Infinity }}
                                    >
                                        <ArrowRight className="w-6 h-6 text-indigo-500" strokeWidth={2.5} />
                                    </motion.div>
                                </motion.div>
                                
                                {/* To Logo */}
                                <motion.div
                                    initial={{ opacity: 0, scale: 0.7, x: 120 }}
                                    animate={{
                                        opacity: animationStage >= 3 ? 1 : 0,
                                        scale: animationStage >= 3 ? 1 : 0.7,
                                        x: animationStage >= 3 ? 100 : 120,
                                    }}
                                    transition={smoothSpring}
                                    className="absolute"
                                >
                                    <motion.div
                                        animate={animationStage >= 3 ? {
                                            boxShadow: [
                                                '0 0 0 0 rgba(99, 102, 241, 0)',
                                                '0 0 0 8px rgba(99, 102, 241, 0.15)',
                                                '0 0 0 0 rgba(99, 102, 241, 0)',
                                            ],
                                        } : {}}
                                        transition={{ duration: 2, repeat: Infinity }}
                                        className="rounded-2xl"
                                    >
                                        <BuLogo bu={toBu} className="w-20 h-20" />
                                    </motion.div>
                                </motion.div>
                            </div>
                        )}
                        
                        {/* Spinner (when no BU transition) */}
                        {!fromBu && !toBu && (
                            <motion.div
                                initial={{ opacity: 0, scale: 0.8 }}
                                animate={{ opacity: 1, scale: 1 }}
                                transition={springConfig}
                                className="mb-8"
                            >
                                <ModernSpinner />
                            </motion.div>
                        )}
                        
                        {/* Title */}
                        <motion.h2
                            initial={{ opacity: 0, y: 10 }}
                            animate={{ opacity: 1, y: 0 }}
                            transition={{ delay: 0.1, ...smoothSpring }}
                            className="text-2xl font-semibold text-gray-900 tracking-tight"
                        >
                            {title}
                        </motion.h2>
                        
                        {/* Subtitle */}
                        {subtitle && (
                            <motion.p
                                initial={{ opacity: 0, y: 10 }}
                                animate={{ opacity: 1, y: 0 }}
                                transition={{ delay: 0.15, ...smoothSpring }}
                                className="mt-2 text-base text-gray-500"
                            >
                                {subtitle}
                            </motion.p>
                        )}
                        
                        {/* To BU Name */}
                        {toBu?.name && (
                            <motion.p
                                initial={{ opacity: 0, y: 10 }}
                                animate={{ opacity: 1, y: 0 }}
                                transition={{ delay: 0.2, ...smoothSpring }}
                                className="mt-2 text-base text-gray-500"
                            >
                                Loading <span className="font-semibold text-indigo-600">{toBu.name}</span>...
                            </motion.p>
                        )}
                        
                        {/* Progress Steps */}
                        {steps.length > 0 && (
                            <motion.div
                                initial={{ opacity: 0, y: 20 }}
                                animate={{ opacity: 1, y: 0 }}
                                transition={{ delay: 0.3, ...smoothSpring }}
                                className="mt-8 space-y-3"
                            >
                                {steps.map((step, index) => (
                                    <motion.div
                                        key={step.id}
                                        initial={{ opacity: 0, x: -20 }}
                                        animate={{ opacity: 1, x: 0 }}
                                        transition={{ delay: 0.3 + index * 0.1, ...smoothSpring }}
                                        className="flex items-center gap-3"
                                    >
                                        {step.completed ? (
                                            <AnimatedCheck delay={0} />
                                        ) : (
                                            <motion.div
                                                className="w-5 h-5 rounded-full border-2 border-gray-300 flex items-center justify-center"
                                                animate={{ borderColor: ['#d1d5db', '#6366f1', '#d1d5db'] }}
                                                transition={{ duration: 1.5, repeat: Infinity }}
                                            >
                                                <motion.div
                                                    className="w-2 h-2 rounded-full bg-indigo-500"
                                                    animate={{ scale: [0.8, 1.2, 0.8] }}
                                                    transition={{ duration: 1, repeat: Infinity }}
                                                />
                                            </motion.div>
                                        )}
                                        <span className={cn(
                                            "text-sm transition-colors duration-300",
                                            step.completed ? "text-gray-900 font-medium" : "text-gray-500"
                                        )}>
                                            {step.label}
                                        </span>
                                    </motion.div>
                                ))}
                            </motion.div>
                        )}
                        
                        {/* Progress Bar */}
                        <motion.div
                            initial={{ opacity: 0, y: 10 }}
                            animate={{ opacity: 1, y: 0 }}
                            transition={{ delay: 0.4, ...smoothSpring }}
                            className="mt-8"
                        >
                            <ShimmerProgress />
                        </motion.div>
                    </motion.div>
                </motion.div>
            )}
        </AnimatePresence>
    );
}

export default FullScreenLoader;
