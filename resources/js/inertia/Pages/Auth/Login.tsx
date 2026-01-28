/**
 * Modern Corporate Login Page
 * 
 * Clean, professional design with animated background.
 * No layout wrapper - standalone page.
 */

import { useState, useMemo, FormEvent } from 'react';
import { Head, useForm } from '@inertiajs/react';
import { motion } from 'framer-motion';
import { Eye, EyeOff, Mail, Lock, ArrowRight } from 'lucide-react';
import { cn } from '@/lib/utils';
import LoginOverlay from '@/components/auth/LoginOverlay';
import { useLoginStore } from '@/stores/loginStore';

interface Props {
    canResetPassword: boolean;
}

// Floating orbs for background
function FloatingOrbs() {
    const orbs = useMemo(() => [
        { color: 'indigo', size: 400, x: -100, y: -50, duration: 25 },
        { color: 'purple', size: 300, x: 200, y: 100, duration: 20 },
        { color: 'blue', size: 350, x: 100, y: -150, duration: 22 },
        { color: 'cyan', size: 250, x: -150, y: 200, duration: 18 },
    ], []);

    const colorMap: Record<string, string> = {
        indigo: 'from-indigo-400/20 to-indigo-600/10',
        purple: 'from-purple-400/20 to-purple-600/10',
        blue: 'from-blue-400/20 to-blue-600/10',
        cyan: 'from-cyan-400/20 to-cyan-600/10',
    };

    return (
        <div className="absolute inset-0 overflow-hidden">
            {orbs.map((orb, i) => (
                <motion.div
                    key={i}
                    className={cn(
                        "absolute rounded-full bg-gradient-to-br blur-3xl",
                        colorMap[orb.color]
                    )}
                    style={{ width: orb.size, height: orb.size }}
                    initial={{ x: orb.x, y: orb.y, scale: 0.8, opacity: 0 }}
                    animate={{
                        x: [orb.x, orb.x + 80, orb.x - 40, orb.x],
                        y: [orb.y, orb.y - 60, orb.y + 40, orb.y],
                        scale: [0.8, 1.1, 0.9, 0.8],
                        opacity: [0.3, 0.5, 0.4, 0.3],
                    }}
                    transition={{
                        duration: orb.duration,
                        repeat: Infinity,
                        ease: 'easeInOut',
                    }}
                />
            ))}
        </div>
    );
}

// Grid pattern overlay
function GridPattern() {
    return (
        <div className="absolute inset-0 overflow-hidden opacity-[0.02]">
            <div 
                className="absolute inset-0"
                style={{
                    backgroundImage: `
                        linear-gradient(rgba(99, 102, 241, 0.5) 1px, transparent 1px),
                        linear-gradient(90deg, rgba(99, 102, 241, 0.5) 1px, transparent 1px)
                    `,
                    backgroundSize: '50px 50px',
                }}
            />
        </div>
    );
}

function Login({ canResetPassword }: Props) {
    const [showPassword, setShowPassword] = useState(false);
    const { startLogin, isLoggingIn } = useLoginStore();
    
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();
        
        // Show login overlay immediately
        startLogin(data.email.split('@')[0]); // Use email prefix as name initially
        
        post('/login', {
            onError: () => {
                // Hide overlay on error
                useLoginStore.getState().reset();
                reset('password');
            },
        });
    };

    // Combine processing state with login overlay
    const isSubmitting = processing || isLoggingIn;

    return (
        <>
            <Head title="Sign In" />
            
            {/* Login Overlay - shows during login process */}
            <LoginOverlay />
            
            <div className="min-h-screen flex">
                {/* Left side - Branding */}
                <div className="hidden lg:flex lg:w-1/2 relative bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 overflow-hidden">
                    {/* Animated background */}
                    <FloatingOrbs />
                    <GridPattern />
                    
                    {/* Content */}
                    <div className="relative z-10 flex flex-col justify-center px-16 xl:px-24">
                        <motion.div
                            initial={{ opacity: 0, y: 30 }}
                            animate={{ opacity: 1, y: 0 }}
                            transition={{ duration: 0.8 }}
                        >
                            {/* Headline */}
                            <h1 className="text-4xl xl:text-5xl font-bold text-white mb-6 leading-tight">
                                Welcome to{' '}
                                <span className="bg-gradient-to-r from-indigo-400 to-purple-400 bg-clip-text text-transparent">
                                    Oasis
                                </span>
                            </h1>
                            
                            <p className="text-lg text-slate-400 mb-12 max-w-md leading-relaxed">
                                Enterprise office administration platform. Streamline your workflows, 
                                manage approvals, and boost productivity.
                            </p>
                            
                            {/* Features */}
                            <div className="space-y-4">
                                {[
                                    'Purchase & Stock Request Management',
                                    'Multi-Business Unit Support',
                                    'Activity Tracking & Reporting',
                                ].map((feature, i) => (
                                    <motion.div
                                        key={i}
                                        initial={{ opacity: 0, x: -20 }}
                                        animate={{ opacity: 1, x: 0 }}
                                        transition={{ delay: 0.5 + i * 0.1 }}
                                        className="flex items-center gap-3 text-slate-300"
                                    >
                                        <div className="w-2 h-2 rounded-full bg-gradient-to-r from-indigo-400 to-purple-400" />
                                        <span>{feature}</span>
                                    </motion.div>
                                ))}
                            </div>
                        </motion.div>
                        
                        {/* Footer */}
                        <motion.div
                            initial={{ opacity: 0 }}
                            animate={{ opacity: 1 }}
                            transition={{ delay: 1 }}
                            className="absolute bottom-8 left-16 xl:left-24"
                        >
                            <p className="text-sm text-slate-500">
                                © {new Date().getFullYear()} Werkudara Group. All rights reserved.
                            </p>
                        </motion.div>
                    </div>
                </div>
                
                {/* Right side - Login Form */}
                <div className="flex-1 flex items-center justify-center p-8 bg-gradient-to-br from-slate-50 to-indigo-50/30 relative">
                    {/* Mobile background */}
                    <div className="lg:hidden absolute inset-0 overflow-hidden">
                        <FloatingOrbs />
                    </div>
                    
                    <motion.div
                        initial={{ opacity: 0, y: 20 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{ duration: 0.5 }}
                        className="w-full max-w-md relative z-10"
                    >
                        {/* Mobile Header */}
                        <div className="lg:hidden text-center mb-8">
                            <h2 className="text-2xl font-bold text-gray-900">Oasis</h2>
                            <p className="text-sm text-gray-500 mt-1">Enterprise Administration Platform</p>
                        </div>
                        
                        {/* Form Card */}
                        <div className="bg-white/80 backdrop-blur-xl rounded-3xl shadow-xl shadow-indigo-500/5 border border-white/50 p-8 md:p-10">
                            <div className="mb-8">
                                <h2 className="text-2xl font-bold text-gray-900">
                                    Sign in
                                </h2>
                                <p className="mt-2 text-gray-500">
                                    Enter your credentials to access your account
                                </p>
                            </div>
                            
                            <form onSubmit={handleSubmit} className="space-y-6">
                                {/* Email Field */}
                                <div>
                                    <label htmlFor="email" className="block text-sm font-medium text-gray-700 mb-2">
                                        Email address
                                    </label>
                                    <div className="relative">
                                        <div className="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                            <Mail className="w-5 h-5 text-gray-400" />
                                        </div>
                                        <input
                                            id="email"
                                            type="email"
                                            value={data.email}
                                            onChange={(e) => setData('email', e.target.value)}
                                            className={cn(
                                                "w-full pl-12 pr-4 py-3.5 rounded-xl border bg-white/50 transition-all duration-200",
                                                "focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500",
                                                errors.email 
                                                    ? "border-red-300 focus:border-red-500 focus:ring-red-500/20" 
                                                    : "border-gray-200 hover:border-gray-300"
                                            )}
                                            placeholder="you@company.com"
                                            autoComplete="email"
                                            autoFocus
                                        />
                                    </div>
                                    {errors.email && (
                                        <motion.p
                                            initial={{ opacity: 0, y: -10 }}
                                            animate={{ opacity: 1, y: 0 }}
                                            className="mt-2 text-sm text-red-600"
                                        >
                                            {errors.email}
                                        </motion.p>
                                    )}
                                </div>
                                
                                {/* Password Field */}
                                <div>
                                    <label htmlFor="password" className="block text-sm font-medium text-gray-700 mb-2">
                                        Password
                                    </label>
                                    <div className="relative">
                                        <div className="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                            <Lock className="w-5 h-5 text-gray-400" />
                                        </div>
                                        <input
                                            id="password"
                                            type={showPassword ? 'text' : 'password'}
                                            value={data.password}
                                            onChange={(e) => setData('password', e.target.value)}
                                            className={cn(
                                                "w-full pl-12 pr-12 py-3.5 rounded-xl border bg-white/50 transition-all duration-200",
                                                "focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500",
                                                errors.password 
                                                    ? "border-red-300 focus:border-red-500 focus:ring-red-500/20" 
                                                    : "border-gray-200 hover:border-gray-300"
                                            )}
                                            placeholder="••••••••"
                                            autoComplete="current-password"
                                        />
                                        <button
                                            type="button"
                                            onClick={() => setShowPassword(!showPassword)}
                                            className="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 hover:text-gray-600 transition-colors"
                                        >
                                            {showPassword ? (
                                                <EyeOff className="w-5 h-5" />
                                            ) : (
                                                <Eye className="w-5 h-5" />
                                            )}
                                        </button>
                                    </div>
                                    {errors.password && (
                                        <motion.p
                                            initial={{ opacity: 0, y: -10 }}
                                            animate={{ opacity: 1, y: 0 }}
                                            className="mt-2 text-sm text-red-600"
                                        >
                                            {errors.password}
                                        </motion.p>
                                    )}
                                </div>
                                
                                {/* Remember & Forgot */}
                                <div className="flex items-center justify-between">
                                    <label className="flex items-center gap-2 cursor-pointer group">
                                        <input
                                            type="checkbox"
                                            checked={data.remember}
                                            onChange={(e) => setData('remember', e.target.checked)}
                                            className="w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 transition-colors"
                                        />
                                        <span className="text-sm text-gray-600 group-hover:text-gray-900 transition-colors">
                                            Remember me
                                        </span>
                                    </label>
                                    
                                    {canResetPassword && (
                                        <a
                                            href="/forgot-password"
                                            className="text-sm font-medium text-indigo-600 hover:text-indigo-700 transition-colors"
                                        >
                                            Forgot password?
                                        </a>
                                    )}
                                </div>
                                
                                {/* Submit Button */}
                                <motion.button
                                    type="submit"
                                    disabled={isSubmitting}
                                    className={cn(
                                        "w-full flex items-center justify-center gap-2 py-3.5 px-6 rounded-xl font-semibold text-white transition-all duration-200",
                                        "bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-700 hover:to-indigo-800",
                                        "shadow-lg shadow-indigo-500/30 hover:shadow-xl hover:shadow-indigo-500/40",
                                        "focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2",
                                        isSubmitting && "opacity-80 cursor-not-allowed"
                                    )}
                                    whileHover={{ scale: isSubmitting ? 1 : 1.01 }}
                                    whileTap={{ scale: isSubmitting ? 1 : 0.99 }}
                                >
                                    <span>Sign in</span>
                                    <ArrowRight className="w-5 h-5" />
                                </motion.button>
                            </form>
                        </div>
                        
                        {/* Footer */}
                        <p className="mt-8 text-center text-sm text-gray-500">
                            © {new Date().getFullYear()} Werkudara Group
                        </p>
                    </motion.div>
                </div>
            </div>
        </>
    );
}

// Opt-out of default AppLayout - Login page is standalone
Login.layout = (page: React.ReactNode) => page;

export default Login;
