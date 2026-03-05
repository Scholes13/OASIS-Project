/**
 * LoginOverlay Component
 * 
 * Modern full-screen overlay with animated welcome message.
 * Similar style to LogoutOverlay but with welcome theme.
 */

import { useEffect, useMemo } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { cn } from '@/lib/utils';
import {
    useLoginStore,
    useIsLoggingIn,
    useLoginUserName,
    useLoginMessage,
} from '@/stores/loginStore';

// Floating particles for background
function FloatingParticles() {
    const particles = useMemo(() => 
        Array.from({ length: 50 }, (_, i) => ({
            id: i,
            x: Math.random() * 100,
            y: Math.random() * 100,
            size: Math.random() * 6 + 2,
            duration: Math.random() * 15 + 10,
            delay: Math.random() * 5,
            color: ['indigo', 'purple', 'emerald', 'blue', 'cyan'][Math.floor(Math.random() * 5)],
        })), []
    );

    const colorMap: Record<string, string> = {
        indigo: 'bg-[#16599c]/40',
        purple: 'bg-[#3b8ed0]/40',
        emerald: 'bg-[#5ba3d9]/40',
        blue: 'bg-[#0e3d6b]/30',
        cyan: 'bg-[#eef6ff]/60',
    };

    return (
        <div className="absolute inset-0 overflow-hidden">
            {particles.map((p) => (
                <motion.div
                    key={p.id}
                    className={cn("absolute rounded-full", colorMap[p.color])}
                    style={{
                        width: p.size,
                        height: p.size,
                        left: `${p.x}%`,
                        top: `${p.y}%`,
                    }}
                    initial={{ opacity: 0, scale: 0 }}
                    animate={{
                        opacity: [0, 0.8, 0],
                        scale: [0, 1.5, 0],
                        y: [0, -100, -200],
                    }}
                    transition={{
                        duration: p.duration,
                        delay: p.delay,
                        repeat: Infinity,
                        ease: 'easeOut',
                    }}
                />
            ))}
        </div>
    );
}

// Animated wave at bottom — uses CSS translateY for entrance + static SVG paths
// (avoids framer-motion `d` attribute animation which produces "undefined" errors)
function WaveAnimation() {
    return (
        <div className="absolute bottom-0 left-0 right-0 h-52 overflow-hidden">
            {/* Wave 1 - Back layer, slowest */}
            <motion.svg
                viewBox="0 0 1440 320"
                className="absolute bottom-0 w-full"
                preserveAspectRatio="none"
                initial={{ y: 80 }}
                animate={{ y: 0 }}
                transition={{ duration: 1.2, ease: 'easeOut' }}
            >
                <path
                    fill="rgba(22, 89, 156, 0.06)"
                    d="M0,224C120,200,240,260,360,256C480,252,600,200,720,192C840,184,960,224,1080,240C1200,256,1320,240,1380,232L1440,224L1440,320L0,320Z"
                />
            </motion.svg>

            {/* Wave 2 - Middle-back layer */}
            <motion.svg
                viewBox="0 0 1440 320"
                className="absolute bottom-0 w-full"
                preserveAspectRatio="none"
                initial={{ y: 60 }}
                animate={{ y: 0 }}
                transition={{ duration: 1, ease: 'easeOut', delay: 0.1 }}
            >
                <path
                    fill="rgba(59, 142, 208, 0.07)"
                    d="M0,256C180,232,260,280,420,272C580,264,660,224,840,216C1020,208,1100,248,1260,256C1340,260,1400,252,1440,248L1440,320L0,320Z"
                />
            </motion.svg>

            {/* Wave 3 - Middle-front layer */}
            <motion.svg
                viewBox="0 0 1440 320"
                className="absolute bottom-0 w-full"
                preserveAspectRatio="none"
                initial={{ y: 40 }}
                animate={{ y: 0 }}
                transition={{ duration: 0.8, ease: 'easeOut', delay: 0.2 }}
            >
                <path
                    fill="rgba(91, 163, 217, 0.08)"
                    d="M0,272C160,256,320,288,480,288C640,288,720,256,880,248C1040,240,1200,264,1320,272C1380,276,1420,272,1440,270L1440,320L0,320Z"
                />
            </motion.svg>

            {/* Wave 4 - Front layer, most visible */}
            <motion.svg
                viewBox="0 0 1440 320"
                className="absolute bottom-0 w-full"
                preserveAspectRatio="none"
                initial={{ y: 30 }}
                animate={{ y: 0 }}
                transition={{ duration: 0.6, ease: 'easeOut', delay: 0.3 }}
            >
                <path
                    fill="rgba(22, 89, 156, 0.05)"
                    d="M0,288C200,276,400,296,600,296C800,296,900,276,1100,272C1300,268,1400,280,1440,284L1440,320L0,320Z"
                />
            </motion.svg>
        </div>
    );
}

// Checkmark animation
function SuccessCheckmark() {
    return (
        <motion.div
            initial={{ scale: 0, opacity: 0 }}
            animate={{ scale: 1, opacity: 1 }}
            transition={{ 
                type: 'spring',
                stiffness: 200,
                damping: 15,
                delay: 0.2,
            }}
            className="w-20 h-20 rounded-full bg-gradient-to-br from-[#3b8ed0] to-[#16599c] flex items-center justify-center shadow-2xl shadow-[#16599c]/30 mb-8"
        >
            <motion.svg
                className="w-10 h-10 text-white"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                strokeWidth={3}
            >
                <motion.path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    d="M5 13l4 4L19 7"
                    initial={{ pathLength: 0 }}
                    animate={{ pathLength: 1 }}
                    transition={{ duration: 0.5, delay: 0.5 }}
                />
            </motion.svg>
        </motion.div>
    );
}

// Loading spinner with gradient
function GradientSpinner() {
    return (
        <motion.div
            className="relative w-16 h-16"
            initial={{ opacity: 0, scale: 0.5 }}
            animate={{ opacity: 1, scale: 1 }}
            transition={{ delay: 1.2, duration: 0.5 }}
        >
            <motion.div
                className="absolute inset-0 rounded-full border-4 border-transparent border-t-[#16599c] border-r-[#3b8ed0]"
                animate={{ rotate: 360 }}
                transition={{
                    duration: 1,
                    repeat: Infinity,
                    ease: 'linear',
                }}
            />
            <motion.div
                className="absolute inset-2 rounded-full border-4 border-transparent border-b-[#5ba3d9] border-l-[#0e3d6b]"
                animate={{ rotate: -360 }}
                transition={{
                    duration: 1.5,
                    repeat: Infinity,
                    ease: 'linear',
                }}
            />
        </motion.div>
    );
}

// Main overlay component
export default function LoginOverlay() {
    const isLoggingIn = useIsLoggingIn();
    const userName = useLoginUserName();
    const message = useLoginMessage();
    
    // Prevent body scroll
    useEffect(() => {
        if (isLoggingIn) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = '';
        }
        return () => { document.body.style.overflow = ''; };
    }, [isLoggingIn]);
    
    // Get first name for personalized message
    const firstName = userName.split(' ')[0];
    
    return (
        <AnimatePresence>
            {isLoggingIn && (
                <motion.div
                    initial={{ opacity: 0 }}
                    animate={{ opacity: 1 }}
                    exit={{ opacity: 0 }}
                    transition={{ duration: 0.4 }}
                    className="fixed inset-0 z-[99999] flex items-center justify-center overflow-hidden"
                >
                    {/* Animated gradient background */}
                    <motion.div 
                        className="absolute inset-0"
                        initial={{ 
                            background: 'linear-gradient(135deg, #f8fafc 0%, #eef6ff 50%, #f0f7ff 100%)',
                        }}
                        animate={{
                            background: [
                                'linear-gradient(135deg, #f8fafc 0%, #eef6ff 50%, #f0f7ff 100%)',
                                'linear-gradient(135deg, #f0f7ff 0%, #f8fafc 50%, #eef6ff 100%)',
                                'linear-gradient(135deg, #eef6ff 0%, #f0f7ff 50%, #f8fafc 100%)',
                            ],
                        }}
                        transition={{ duration: 6, repeat: Infinity, ease: 'linear' }}
                    />
                    
                    {/* Floating particles */}
                    <FloatingParticles />
                    
                    {/* Wave animation */}
                    <WaveAnimation />
                    
                    {/* Content */}
                    <motion.div
                        initial={{ opacity: 0, y: 50, scale: 0.9 }}
                        animate={{ opacity: 1, y: 0, scale: 1 }}
                        exit={{ opacity: 0, y: -50, scale: 0.9 }}
                        transition={{ 
                            duration: 0.6, 
                            delay: 0.1,
                            type: 'spring',
                            stiffness: 100,
                        }}
                        className="relative z-10 flex flex-col items-center text-center px-6"
                    >
                        {/* Success checkmark */}
                        <SuccessCheckmark />
                        
                        {/* Main title */}
                        <motion.h1
                            initial={{ opacity: 0, y: 20 }}
                            animate={{ opacity: 1, y: 0 }}
                            transition={{ delay: 0.3, duration: 0.5 }}
                            className="text-4xl md:text-5xl font-bold mb-3"
                        >
                            <span className="bg-gradient-to-r from-[#0e3d6b] via-[#16599c] to-[#3b8ed0] bg-clip-text text-transparent">
                                {message.title}
                            </span>
                        </motion.h1>
                        
                        {/* Personalized name */}
                        {firstName && (
                            <motion.p
                                initial={{ opacity: 0, y: 20 }}
                                animate={{ opacity: 1, y: 0 }}
                                transition={{ delay: 0.5, duration: 0.5 }}
                                className="text-xl md:text-2xl text-gray-600 mb-2"
                            >
                                Hello, <span className="font-semibold text-gray-800">{firstName}</span>!
                            </motion.p>
                        )}
                        
                        {/* Subtitle / motivational message */}
                        <motion.p
                            initial={{ opacity: 0, y: 20 }}
                            animate={{ opacity: 1, y: 0 }}
                            transition={{ delay: 0.7, duration: 0.5 }}
                            className="text-lg text-gray-500 mb-10 max-w-md"
                        >
                            {message.subtitle}
                        </motion.p>
                        
                        {/* Loading indicator */}
                        <motion.div
                            initial={{ opacity: 0 }}
                            animate={{ opacity: 1 }}
                            transition={{ delay: 1, duration: 0.5 }}
                            className="flex flex-col items-center gap-4"
                        >
                            <GradientSpinner />
                            
                            <motion.p
                                initial={{ opacity: 0 }}
                                animate={{ opacity: [0, 1, 0.5, 1] }}
                                transition={{ 
                                    delay: 1.2,
                                    duration: 2,
                                    repeat: Infinity,
                                }}
                                className="text-sm text-gray-400"
                            >
                                Preparing your workspace...
                            </motion.p>
                        </motion.div>
                    </motion.div>
                </motion.div>
            )}
        </AnimatePresence>
    );
}
