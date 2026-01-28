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
        indigo: 'bg-indigo-400/40',
        purple: 'bg-purple-400/40',
        emerald: 'bg-emerald-400/40',
        blue: 'bg-blue-400/40',
        cyan: 'bg-cyan-400/40',
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

// Animated wave at bottom
function WaveAnimation() {
    return (
        <div className="absolute bottom-0 left-0 right-0 h-40 overflow-hidden">
            <motion.svg
                viewBox="0 0 1440 320"
                className="absolute bottom-0 w-full"
                initial={{ y: 100 }}
                animate={{ y: 0 }}
                transition={{ duration: 1, ease: 'easeOut' }}
            >
                <motion.path
                    fill="rgba(16, 185, 129, 0.1)"
                    d="M0,192L48,197.3C96,203,192,213,288,229.3C384,245,480,267,576,250.7C672,235,768,181,864,181.3C960,181,1056,235,1152,234.7C1248,235,1344,181,1392,154.7L1440,128L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"
                    animate={{
                        d: [
                            "M0,192L48,197.3C96,203,192,213,288,229.3C384,245,480,267,576,250.7C672,235,768,181,864,181.3C960,181,1056,235,1152,234.7C1248,235,1344,181,1392,154.7L1440,128L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z",
                            "M0,160L48,181.3C96,203,192,245,288,261.3C384,277,480,267,576,234.7C672,203,768,149,864,154.7C960,160,1056,224,1152,229.3C1248,235,1344,181,1392,154.7L1440,128L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z",
                        ],
                    }}
                    transition={{
                        duration: 4,
                        repeat: Infinity,
                        repeatType: 'reverse',
                        ease: 'easeInOut',
                    }}
                />
            </motion.svg>
            <motion.svg
                viewBox="0 0 1440 320"
                className="absolute bottom-0 w-full"
                initial={{ y: 100 }}
                animate={{ y: 0 }}
                transition={{ duration: 1.2, ease: 'easeOut', delay: 0.1 }}
            >
                <motion.path
                    fill="rgba(99, 102, 241, 0.08)"
                    d="M0,256L48,240C96,224,192,192,288,181.3C384,171,480,181,576,208C672,235,768,277,864,277.3C960,277,1056,235,1152,208C1248,181,1344,171,1392,165.3L1440,160L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"
                    animate={{
                        d: [
                            "M0,256L48,240C96,224,192,192,288,181.3C384,171,480,181,576,208C672,235,768,277,864,277.3C960,277,1056,235,1152,208C1248,181,1344,171,1392,165.3L1440,160L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z",
                            "M0,224L48,213.3C96,203,192,181,288,192C384,203,480,245,576,261.3C672,277,768,267,864,240C960,213,1056,171,1152,165.3C1248,160,1344,192,1392,208L1440,224L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z",
                        ],
                    }}
                    transition={{
                        duration: 5,
                        repeat: Infinity,
                        repeatType: 'reverse',
                        ease: 'easeInOut',
                        delay: 0.5,
                    }}
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
            className="w-20 h-20 rounded-full bg-gradient-to-br from-emerald-400 to-emerald-600 flex items-center justify-center shadow-2xl shadow-emerald-500/30 mb-8"
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
                className="absolute inset-0 rounded-full border-4 border-transparent border-t-emerald-500 border-r-indigo-500"
                animate={{ rotate: 360 }}
                transition={{
                    duration: 1,
                    repeat: Infinity,
                    ease: 'linear',
                }}
            />
            <motion.div
                className="absolute inset-2 rounded-full border-4 border-transparent border-b-purple-500 border-l-cyan-500"
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
                            background: 'linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 50%, #f0f9ff 100%)',
                        }}
                        animate={{
                            background: [
                                'linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 50%, #f0f9ff 100%)',
                                'linear-gradient(135deg, #f0f9ff 0%, #f0fdf4 50%, #ecfdf5 100%)',
                                'linear-gradient(135deg, #ecfdf5 0%, #f0f9ff 50%, #f0fdf4 100%)',
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
                            <span className="bg-gradient-to-r from-emerald-600 via-teal-600 to-indigo-600 bg-clip-text text-transparent">
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
