/**
 * BuTransitionOverlay Component
 * 
 * Modern full-screen overlay with animated background.
 * Clean design without cards - just logos and minimal text.
 */

import { useEffect, useState, useMemo } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { cn } from '@/lib/utils';
import {
    useBuTransitionStore,
    useBuTransitionPhase,
    useBuTransitionFrom,
    useBuTransitionTo,
    useIsTransitioning,
    useBuTransitionError,
} from '@/stores/buTransitionStore';

interface BusinessUnitDisplay {
    code: string;
    name: string;
    logo: string | null;
}

// Floating orb component for background animation
function FloatingOrb({ 
    delay = 0, 
    duration = 20,
    size = 300,
    color = 'indigo',
    initialX = 0,
    initialY = 0,
}: { 
    delay?: number;
    duration?: number;
    size?: number;
    color?: 'indigo' | 'purple' | 'blue' | 'cyan' | 'pink';
    initialX?: number;
    initialY?: number;
}) {
    const colorMap = {
        indigo: 'from-[#16599c]/30 to-[#16599c]/20',
        purple: 'from-[#3b8ed0]/30 to-[#3b8ed0]/20',
        blue: 'from-[#0e3d6b]/30 to-[#0e3d6b]/20',
        cyan: 'from-[#5ba3d9]/30 to-[#5ba3d9]/20',
        pink: 'from-[#eef6ff]/50 to-[#eef6ff]/30',
    };

    return (
        <motion.div
            className={cn(
                "absolute rounded-full bg-gradient-to-br blur-3xl",
                colorMap[color]
            )}
            style={{ width: size, height: size }}
            initial={{ x: initialX, y: initialY, scale: 0.8, opacity: 0 }}
            animate={{
                x: [initialX, initialX + 100, initialX - 50, initialX],
                y: [initialY, initialY - 80, initialY + 60, initialY],
                scale: [0.8, 1.1, 0.9, 0.8],
                opacity: [0.4, 0.7, 0.5, 0.4],
            }}
            transition={{
                duration,
                delay,
                repeat: Infinity,
                ease: 'easeInOut',
            }}
        />
    );
}

// Animated grid lines for tech feel
function GridLines() {
    return (
        <div className="absolute inset-0 overflow-hidden opacity-[0.03]">
            <div 
                className="absolute inset-0"
                style={{
                    backgroundImage: `
                        linear-gradient(rgba(22, 89, 156, 0.5) 1px, transparent 1px),
                        linear-gradient(90deg, rgba(22, 89, 156, 0.5) 1px, transparent 1px)
                    `,
                    backgroundSize: '60px 60px',
                }}
            />
        </div>
    );
}

// Particle dots floating
function ParticleDots() {
    const particles = useMemo(() => 
        Array.from({ length: 30 }, (_, i) => ({
            id: i,
            x: Math.random() * 100,
            y: Math.random() * 100,
            size: Math.random() * 4 + 2,
            duration: Math.random() * 10 + 15,
            delay: Math.random() * 5,
        })), []
    );

    return (
        <div className="absolute inset-0 overflow-hidden">
            {particles.map((p) => (
                <motion.div
                    key={p.id}
                    className="absolute rounded-full bg-blue-500/20"
                    style={{
                        width: p.size,
                        height: p.size,
                        left: `${p.x}%`,
                        top: `${p.y}%`,
                    }}
                    animate={{
                        y: [0, -30, 0],
                        opacity: [0.2, 0.6, 0.2],
                    }}
                    transition={{
                        duration: p.duration,
                        delay: p.delay,
                        repeat: Infinity,
                        ease: 'easeInOut',
                    }}
                />
            ))}
        </div>
    );
}

// Clean logo without background box
function TransitionLogo({ 
    bu, 
    variant = 'default',
    size = 'lg'
}: { 
    bu: BusinessUnitDisplay | null;
    variant?: 'default' | 'faded';
    size?: 'md' | 'lg' | 'xl';
}) {
    if (!bu) return null;
    
    const sizeClasses = {
        md: 'w-16 h-16 text-xl',
        lg: 'w-24 h-24 text-3xl',
        xl: 'w-32 h-32 text-4xl',
    };
    
    const gradients = [
        'from-[#16599c] via-[#3b8ed0] to-[#5ba3d9]',
        'from-[#0e3d6b] via-[#16599c] to-[#3b8ed0]',
        'from-[#3b8ed0] via-[#5ba3d9] to-[#16599c]',
        'from-[#124b85] via-[#16599c] to-[#3b8ed0]',
        'from-[#0e3d6b] via-[#124b85] to-[#16599c]',
    ];
    const gradientIndex = bu.code.charCodeAt(0) % gradients.length;
    
    const baseClasses = cn(
        "flex items-center justify-center flex-shrink-0 transition-all duration-500",
        sizeClasses[size],
        variant === 'faded' && "opacity-30 grayscale scale-90"
    );
    
    if (bu.logo) {
        return (
            <motion.div 
                className={cn(baseClasses, "rounded-3xl overflow-hidden")}
                whileHover={{ scale: 1.05 }}
            >
                <img 
                    src={`/storage/${bu.logo}`} 
                    alt={bu.code} 
                    className="w-full h-full object-contain drop-shadow-2xl"
                />
            </motion.div>
        );
    }
    
    return (
        <motion.div 
            className={cn(
                baseClasses, 
                "rounded-3xl bg-gradient-to-br shadow-2xl",
                gradients[gradientIndex]
            )}
        >
            <span className="font-bold text-white drop-shadow-lg">
                {bu.code.substring(0, 2)}
            </span>
        </motion.div>
    );
}

// Animated connection line between logos
function ConnectionLine({ isActive }: { isActive: boolean }) {
    return (
        <div className="relative w-32 h-1 mx-8">
            {/* Base line */}
            <div className="absolute inset-0 bg-gray-200/50 rounded-full" />
            
            {/* Animated progress */}
            <motion.div
                className="absolute inset-y-0 left-0 bg-gradient-to-r from-[#16599c] to-[#3b8ed0] rounded-full"
                initial={{ width: '0%' }}
                animate={{ width: isActive ? '100%' : '0%' }}
                transition={{ duration: 1.5, ease: 'easeInOut' }}
            />
            
            {/* Glowing dot */}
            <motion.div
                className="absolute top-1/2 -translate-y-1/2 w-3 h-3 bg-white rounded-full shadow-lg"
                style={{ boxShadow: '0 0 20px rgba(22, 89, 156, 0.8)' }}
                initial={{ left: '0%', opacity: 0 }}
                animate={{ 
                    left: isActive ? '100%' : '0%',
                    opacity: isActive ? [0, 1, 1, 0] : 0,
                }}
                transition={{ duration: 1.5, ease: 'easeInOut' }}
            />
        </div>
    );
}

// Pulsing ring effect around target logo
function PulsingRing() {
    return (
        <>
            {[0, 1, 2].map((i) => (
                <motion.div
                    key={i}
                    className="absolute inset-0 rounded-3xl border-2 border-primary/30"
                    initial={{ scale: 1, opacity: 0.6 }}
                    animate={{ scale: 1.5 + i * 0.2, opacity: 0 }}
                    transition={{
                        duration: 2,
                        delay: i * 0.4,
                        repeat: Infinity,
                        ease: 'easeOut',
                    }}
                />
            ))}
        </>
    );
}

// Main overlay component
export default function BuTransitionOverlay() {
    const isTransitioning = useIsTransitioning();
    const phase = useBuTransitionPhase();
    const fromBu = useBuTransitionFrom();
    const toBu = useBuTransitionTo();
    const errorMessage = useBuTransitionError();
    const reset = useBuTransitionStore(state => state.reset);
    
    const [showConnection, setShowConnection] = useState(false);
    
    // Trigger connection animation
    useEffect(() => {
        if (isTransitioning && phase !== 'idle') {
            const timer = setTimeout(() => setShowConnection(true), 500);
            return () => clearTimeout(timer);
        } else {
            setShowConnection(false);
        }
    }, [isTransitioning, phase]);
    
    // Prevent body scroll
    useEffect(() => {
        if (isTransitioning) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = '';
        }
        return () => { document.body.style.overflow = ''; };
    }, [isTransitioning]);
    
    return (
        <AnimatePresence>
            {isTransitioning && (
                <motion.div
                    initial={{ opacity: 0 }}
                    animate={{ opacity: 1 }}
                    exit={{ opacity: 0 }}
                    transition={{ duration: 0.4 }}
                    className="fixed inset-0 z-[99999] flex items-center justify-center overflow-hidden"
                >
                    {/* Animated gradient background */}
                    <motion.div 
                        className="absolute inset-0 bg-gradient-to-br from-slate-50 via-blue-50/50 to-sky-50/50"
                        animate={{
                            background: [
                                'linear-gradient(135deg, #f8fafc 0%, #eef6ff 50%, #f0f7ff 100%)',
                                'linear-gradient(135deg, #f0f7ff 0%, #f8fafc 50%, #eef6ff 100%)',
                                'linear-gradient(135deg, #eef6ff 0%, #f0f7ff 50%, #f8fafc 100%)',
                            ],
                        }}
                        transition={{ duration: 8, repeat: Infinity, ease: 'linear' }}
                    />
                    
                    {/* Grid lines */}
                    <GridLines />
                    
                    {/* Floating orbs */}
                    <FloatingOrb delay={0} duration={25} size={400} color="indigo" initialX={-200} initialY={-100} />
                    <FloatingOrb delay={2} duration={20} size={300} color="purple" initialX={200} initialY={100} />
                    <FloatingOrb delay={4} duration={22} size={350} color="blue" initialX={100} initialY={-200} />
                    <FloatingOrb delay={1} duration={18} size={250} color="cyan" initialX={-150} initialY={200} />
                    <FloatingOrb delay={3} duration={24} size={280} color="pink" initialX={250} initialY={-50} />
                    
                    {/* Particle dots */}
                    <ParticleDots />
                    
                    {/* Content */}
                    <motion.div
                        initial={{ opacity: 0, y: 30 }}
                        animate={{ opacity: 1, y: 0 }}
                        exit={{ opacity: 0, y: -30 }}
                        transition={{ duration: 0.5, delay: 0.1 }}
                        className="relative z-10 flex flex-col items-center"
                    >
                        {phase === 'error' ? (
                            /* Error State - Minimal */
                            <motion.div
                                initial={{ scale: 0.9, opacity: 0 }}
                                animate={{ scale: 1, opacity: 1 }}
                                className="text-center"
                            >
                                <motion.div
                                    className="w-20 h-20 mx-auto mb-6 rounded-full bg-gradient-to-br from-red-400 to-red-600 flex items-center justify-center"
                                    animate={{ scale: [1, 1.05, 1] }}
                                    transition={{ duration: 2, repeat: Infinity }}
                                >
                                    <svg className="w-10 h-10 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </motion.div>
                                
                                <h3 className="text-xl font-semibold text-gray-900 mb-2">
                                    Switch Failed
                                </h3>
                                <p className="text-sm text-gray-500 mb-6 max-w-xs">
                                    {errorMessage || 'Something went wrong'}
                                </p>
                                
                                <motion.button
                                    onClick={reset}
                                    className="px-6 py-2.5 bg-gradient-to-r from-[#16599c] to-[#3b8ed0] text-white rounded-full font-medium text-sm shadow-lg shadow-[#16599c]/30"
                                    whileHover={{ scale: 1.05 }}
                                    whileTap={{ scale: 0.95 }}
                                >
                                    Try Again
                                </motion.button>
                            </motion.div>
                        ) : (
                            /* Main Transition UI - Clean & Modern */
                            <>
                                {/* Logo transition area */}
                                <div className="flex items-center justify-center mb-10">
                                    {/* From Logo */}
                                    <motion.div
                                        initial={{ opacity: 0, x: -50 }}
                                        animate={{ 
                                            opacity: showConnection ? 0.4 : 1, 
                                            x: 0,
                                            scale: showConnection ? 0.85 : 1,
                                        }}
                                        transition={{ duration: 0.6 }}
                                    >
                                        <TransitionLogo 
                                            bu={fromBu} 
                                            size="lg"
                                            variant={showConnection ? 'faded' : 'default'}
                                        />
                                    </motion.div>
                                    
                                    {/* Connection Line */}
                                    <ConnectionLine isActive={showConnection} />
                                    
                                    {/* To Logo with pulsing effect */}
                                    <motion.div
                                        initial={{ opacity: 0, x: 50 }}
                                        animate={{ opacity: 1, x: 0 }}
                                        transition={{ duration: 0.6, delay: 0.2 }}
                                        className="relative"
                                    >
                                        {showConnection && <PulsingRing />}
                                        <TransitionLogo bu={toBu} size="lg" />
                                    </motion.div>
                                </div>
                                
                                {/* Minimal text */}
                                <motion.div
                                    initial={{ opacity: 0, y: 20 }}
                                    animate={{ opacity: 1, y: 0 }}
                                    transition={{ delay: 0.3 }}
                                    className="text-center"
                                >
                                    <h2 className="text-2xl font-semibold text-gray-900 mb-2">
                                        Switching to{' '}
                                        <span className="bg-gradient-to-r from-[#0e3d6b] to-[#3b8ed0] bg-clip-text text-transparent">
                                            {toBu?.name || 'Business Unit'}
                                        </span>
                                    </h2>
                                    
                                    {/* Animated dots instead of progress bar */}
                                    <div className="flex items-center justify-center gap-1.5 mt-4">
                                        {[0, 1, 2].map((i) => (
                                            <motion.div
                                                key={i}
                                                className="w-2 h-2 rounded-full bg-primary"
                                                animate={{
                                                    scale: [1, 1.5, 1],
                                                    opacity: [0.3, 1, 0.3],
                                                }}
                                                transition={{
                                                    duration: 1,
                                                    delay: i * 0.2,
                                                    repeat: Infinity,
                                                }}
                                            />
                                        ))}
                                    </div>
                                </motion.div>
                            </>
                        )}
                    </motion.div>
                </motion.div>
            )}
        </AnimatePresence>
    );
}
