/**
 * LogoutOverlay Component
 * 
 * Modern full-screen overlay with animated goodbye message.
 * Features floating particles, wave animation, and smooth transitions.
 */

import { useEffect, useMemo } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { cn } from '@/lib/utils';
import {
    useLogoutStore,
    useIsLoggingOut,
    useLogoutUserName,
    useLogoutMessage,
} from '@/stores/logoutStore';

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
            color: ['indigo', 'purple', 'pink', 'blue', 'cyan'][Math.floor(Math.random() * 5)],
        })), []
    );

    const colorMap: Record<string, string> = {
        indigo: 'bg-[#16599c]/40',
        purple: 'bg-[#3b8ed0]/40',
        pink: 'bg-[#5ba3d9]/40',
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

// Animated waving hand - responsive sizing with rem
function WavingHand() {
    return (
        <motion.div
            className="text-[4rem] sm:text-[5rem] md:text-[6rem] lg:text-[7rem] leading-none"
            animate={{
                rotate: [0, 14, -8, 14, -4, 10, 0],
            }}
            transition={{
                duration: 2.5,
                repeat: Infinity,
                repeatDelay: 1,
            }}
            style={{ transformOrigin: '70% 70%' }}
        >
            👋
        </motion.div>
    );
}

// Sparkle effect around the hand - responsive distance
function Sparkles() {
    const sparkles = useMemo(() => 
        Array.from({ length: 8 }, (_, i) => ({
            id: i,
            angle: (i * 45) * (Math.PI / 180),
            distance: 100 + Math.random() * 40, // Increased distance for larger hand
            size: 10 + Math.random() * 10, // Slightly larger sparkles
            delay: i * 0.1,
        })), []
    );

    return (
        <div className="absolute inset-0 flex items-center justify-center pointer-events-none">
            {sparkles.map((s) => (
                <motion.div
                    key={s.id}
                    className="absolute"
                    style={{
                        left: `calc(50% + ${Math.cos(s.angle) * s.distance}px)`,
                        top: `calc(50% + ${Math.sin(s.angle) * s.distance}px)`,
                    }}
                    initial={{ scale: 0, opacity: 0 }}
                    animate={{
                        scale: [0, 1, 0],
                        opacity: [0, 1, 0],
                    }}
                    transition={{
                        duration: 1.5,
                        delay: s.delay + 0.5,
                        repeat: Infinity,
                        repeatDelay: 2,
                    }}
                >
                    <svg
                        width={s.size}
                        height={s.size}
                        viewBox="0 0 24 24"
                        fill="none"
                        className="text-[#3b8ed0]"
                    >
                        <path
                            d="M12 2L13.09 8.26L19 7L14.74 11.26L21 14L14.74 14.74L17 21L12 16.26L7 21L9.26 14.74L3 14L9.26 11.26L5 7L10.91 8.26L12 2Z"
                            fill="currentColor"
                        />
                    </svg>
                </motion.div>
            ))}
        </div>
    );
}

// Confetti burst effect
function ConfettiBurst() {
    const confetti = useMemo(() => 
        Array.from({ length: 30 }, (_, i) => ({
            id: i,
            x: (Math.random() - 0.5) * 400,
            y: (Math.random() - 0.5) * 400,
            rotation: Math.random() * 360,
            color: ['#16599c', '#3b8ed0', '#5ba3d9', '#0e3d6b', '#eef6ff', '#124b85'][Math.floor(Math.random() * 6)],
            size: 6 + Math.random() * 6,
            delay: Math.random() * 0.5,
        })), []
    );

    return (
        <div className="absolute inset-0 flex items-center justify-center pointer-events-none overflow-hidden">
            {confetti.map((c) => (
                <motion.div
                    key={c.id}
                    className="absolute rounded-sm"
                    style={{
                        width: c.size,
                        height: c.size * 0.6,
                        backgroundColor: c.color,
                    }}
                    initial={{ 
                        x: 0, 
                        y: 0, 
                        opacity: 0,
                        rotate: 0,
                        scale: 0,
                    }}
                    animate={{
                        x: c.x,
                        y: c.y,
                        opacity: [0, 1, 1, 0],
                        rotate: c.rotation,
                        scale: [0, 1, 1, 0.5],
                    }}
                    transition={{
                        duration: 2,
                        delay: c.delay + 0.3,
                        ease: 'easeOut',
                    }}
                />
            ))}
        </div>
    );
}


// Loading spinner with gradient
function GradientSpinner() {
    return (
        <motion.div
            className="relative w-16 h-16"
            initial={{ opacity: 0, scale: 0.5 }}
            animate={{ opacity: 1, scale: 1 }}
            transition={{ delay: 1.5, duration: 0.5 }}
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
export default function LogoutOverlay() {
    const isLoggingOut = useIsLoggingOut();
    const userName = useLogoutUserName();
    const message = useLogoutMessage();
    
    // Prevent body scroll
    useEffect(() => {
        if (isLoggingOut) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = '';
        }
        return () => { document.body.style.overflow = ''; };
    }, [isLoggingOut]);
    
    // Get first name for personalized message
    const firstName = userName.split(' ')[0];
    
    return (
        <AnimatePresence>
            {isLoggingOut && (
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
                        <motion.p
                            initial={{ opacity: 0, y: 20 }}
                            animate={{ opacity: 1, y: 0 }}
                            transition={{ delay: 0.5, duration: 0.5 }}
                            className="text-xl md:text-2xl text-gray-600 mb-2"
                        >
                            {firstName && (
                                <>
                                    See you later, <span className="font-semibold text-gray-800">{firstName}</span>!
                                </>
                            )}
                        </motion.p>
                        
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
                                    delay: 1.5,
                                    duration: 2,
                                    repeat: Infinity,
                                }}
                                className="text-sm text-gray-400"
                            >
                                Signing you out securely...
                            </motion.p>
                        </motion.div>
                    </motion.div>
                </motion.div>
            )}
        </AnimatePresence>
    );
}
