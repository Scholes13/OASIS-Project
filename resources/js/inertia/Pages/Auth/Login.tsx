import { FormEvent, useState } from 'react';
import { Head, useForm, Link } from '@inertiajs/react';
import { Droplets, Eye, EyeOff } from 'lucide-react';
import { cn } from '@/lib/utils';
import { useLoginStore } from '@/stores/loginStore';
import LoginOverlay from '@/components/auth/LoginOverlay';

interface Props {
    canResetPassword: boolean;
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

    const isSubmitting = processing || isLoggingIn;

    return (
        <>
            <Head title="Login" />
            
            <LoginOverlay />
            
            <div className="min-h-screen flex font-inter bg-white overflow-hidden">
                {/* Brand Side */}
                <div className="hidden lg:flex flex-1 flex-col justify-between p-12 relative overflow-hidden bg-gradient-to-br from-[#eff6ff] to-[#dbeafe]">
                    {/* Pattern */}
                    <div 
                        className="absolute inset-0 opacity-40 z-0" 
                        style={{
                            backgroundImage: 'radial-gradient(#60a5fa 1px, transparent 1px)',
                            backgroundSize: '32px 32px'
                        }}
                    />
                    
                    <div className="relative z-10 max-w-md">
                        <div className="flex items-center gap-3 text-2xl font-bold text-slate-800 mb-6 tracking-tight">
                            <div className="w-10 h-10 bg-[#16599c] rounded-md flex items-center justify-center text-white">
                                <Droplets className="w-6 h-6" />
                            </div>
                            OASIS
                        </div>
                    </div>

                    <div className="relative z-10 max-w-md mt-auto mb-6">
                        <div className="text-[32px] font-semibold leading-tight text-slate-800 mb-6">
                            Streamline your workflow with intelligent resource management.
                        </div>
                        <div className="text-base text-slate-500 leading-relaxed">
                            Enterprises Office Administration Platform using Oasis to manage purchasing, tracking, and cashflow projection in one unified platform.
                        </div>
                    </div>
                </div>

                {/* Form Side */}
                <div className="w-full lg:w-[560px] bg-white flex flex-col p-8 lg:p-12 lg:border-l border-slate-200 relative min-h-screen lg:min-h-0">
                    <div className="flex-1 flex flex-col justify-center items-center w-full">
                        <div className="w-full max-w-[360px] flex flex-col gap-8">
                            {/* Mobile Logo */}
                            <div className="lg:hidden flex items-center justify-center gap-2 text-2xl font-bold text-slate-800 mb-2 tracking-tight">
                                <div className="w-8 h-8 bg-[#16599c] rounded-md flex items-center justify-center text-white">
                                    <Droplets className="w-5 h-5" />
                                </div>
                                OASIS
                            </div>

                            <div className="text-center flex flex-col gap-2">
                                <h1 className="text-2xl font-semibold text-slate-800 m-0">Welcome back</h1>
                                <div className="text-sm text-slate-500">
                                    Enter your credentials to access your account
                                </div>
                            </div>

                            <form onSubmit={handleSubmit} className="flex flex-col gap-5">
                                <div className="flex flex-col gap-2">
                                    <label className="text-sm font-medium text-slate-800">Email</label>
                                    <input
                                        type="email"
                                        className="h-10 px-3 rounded-md border border-slate-200 text-sm text-slate-800 focus:outline-none focus:border-[#16599c] focus:ring-1 focus:ring-[#16599c] transition-all bg-white w-full"
                                        placeholder="name@company.com"
                                        value={data.email}
                                        onChange={(e) => setData('email', e.target.value)}
                                        autoComplete="email"
                                        autoFocus
                                    />
                                    {errors.email && <span className="text-xs text-red-500">{errors.email}</span>}
                                </div>

                                <div className="flex flex-col gap-2">
                                    <label className="text-sm font-medium text-slate-800">Password</label>
                                    <div className="relative">
                                        <input
                                            type={showPassword ? 'text' : 'password'}
                                            className="h-10 px-3 pr-10 rounded-md border border-slate-200 text-sm text-slate-800 focus:outline-none focus:border-[#16599c] focus:ring-1 focus:ring-[#16599c] transition-all bg-white w-full"
                                            placeholder="••••••••"
                                            value={data.password}
                                            onChange={(e) => setData('password', e.target.value)}
                                            autoComplete="current-password"
                                        />
                                        <button
                                            type="button"
                                            onClick={() => setShowPassword(!showPassword)}
                                            className="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 focus:outline-none"
                                            tabIndex={-1}
                                        >
                                            {showPassword ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
                                        </button>
                                    </div>
                                    {canResetPassword && (
                                        <Link 
                                            href="/forgot-password" 
                                            className="text-[13px] text-[#16599c] hover:underline self-end mt-1 cursor-pointer"
                                        >
                                            Forgot password?
                                        </Link>
                                    )}
                                    {errors.password && <span className="text-xs text-red-500">{errors.password}</span>}
                                </div>

                                <label className="flex items-center gap-2 cursor-pointer mt-1 group w-fit">
                                    <div className="w-4 h-4 border border-slate-300 rounded flex items-center justify-center bg-white relative group-hover:border-[#16599c] transition-colors shrink-0">
                                        <input
                                            type="checkbox"
                                            checked={data.remember}
                                            onChange={(e) => setData('remember', e.target.checked)}
                                            className="w-full h-full opacity-0 absolute cursor-pointer"
                                        />
                                        {data.remember && (
                                            <div className="w-2.5 h-2.5 bg-[#16599c] rounded-sm"></div>
                                        )}
                                    </div>
                                    <span className="text-[13px] text-slate-700">Remember me for 30 days</span>
                                </label>

                                <button 
                                    type="submit"
                                    disabled={isSubmitting}
                                    className={cn(
                                        "h-10 px-4 rounded-md text-sm font-medium text-white flex items-center justify-center transition-all w-full mt-2",
                                        "bg-[#16599c] hover:bg-[#124b85]",
                                        isSubmitting && "opacity-70 cursor-not-allowed"
                                    )}
                                >
                                    {isSubmitting ? 'Signing in...' : 'Sign in'}
                                </button>
                            </form>
                        </div>
                    </div>

                    <div className="mt-8 text-xs text-slate-500 text-center w-full">
                        <div>© 2026 Werkudara - Oasis Enterprise. All rights reserved.</div>
                        <div className="flex gap-4 justify-center mt-2">
                            <Link href="#" className="text-slate-500 hover:text-slate-800 no-underline transition-colors">Privacy Policy</Link>
                            <Link href="#" className="text-slate-500 hover:text-slate-800 no-underline transition-colors">Terms of Service</Link>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}

// Opt-out of default AppLayout - Login page is standalone
Login.layout = (page: React.ReactNode) => page;

export default Login;