import { useState, FormEvent } from 'react';
import { Head, useForm, usePage } from '@inertiajs/react';
import { PageProps } from '@/types';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import {
    User,
    Mail,
    Shield,
    Building2,
    Calendar,
    Lock,
    Eye,
    EyeOff,
    CheckCircle2,
    AlertCircle
} from 'lucide-react';

interface ProfileUser {
    id: number;
    name: string;
    email: string;
    role: string | null;
    department: string | null;
    avatar_url: string | null;
    created_at: string;
}

interface ProfileProps extends PageProps {
    user: ProfileUser;
}

export default function ProfileIndex({ user }: ProfileProps) {
    const { flash } = usePage().props as any;
    const [showCurrentPassword, setShowCurrentPassword] = useState(false);
    const [showNewPassword, setShowNewPassword] = useState(false);
    const [showConfirmPassword, setShowConfirmPassword] = useState(false);

    const { data, setData, post, processing, errors, reset, recentlySuccessful } = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();
        post(route('profile.password'), {
            preserveScroll: true,
            onSuccess: () => reset(),
        });
    };

    // Generate initials for avatar fallback
    const initials = user.name
        .split(' ')
        .map(n => n[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);

    return (
        <>
            <Head title="Profile" />
            <div className="min-h-screen bg-slate-50">
                <div className="w-full px-4 sm:px-6 lg:px-8 py-6">
                    {/* Header */}
                    <div className="mb-8">
                        <h1 className="text-2xl font-semibold text-gray-900">Profile Settings</h1>
                        <p className="mt-1 text-sm text-gray-500">
                            Manage your account settings and password
                        </p>
                    </div>

                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        {/* Left Column: User Card */}
                        <div className="lg:col-span-1">
                            <div className="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                                {/* Profile Header with Gradient */}
                                <div className="h-24 bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500"></div>

                                {/* Avatar */}
                                <div className="px-6 pb-6">
                                    <div className="-mt-12 relative">
                                        {user.avatar_url ? (
                                            <img
                                                src={user.avatar_url}
                                                alt={user.name}
                                                className="w-24 h-24 rounded-full border-4 border-white shadow-lg object-cover"
                                            />
                                        ) : (
                                            <div className="w-24 h-24 rounded-full border-4 border-white shadow-lg bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center">
                                                <span className="text-2xl font-bold text-white">{initials}</span>
                                            </div>
                                        )}
                                    </div>

                                    <div className="mt-4">
                                        <h2 className="text-xl font-bold text-gray-900">{user.name}</h2>
                                        <p className="text-sm text-gray-500">{user.email}</p>
                                    </div>

                                    {/* Stats */}
                                    <div className="mt-6 pt-6 border-t border-gray-100 space-y-4">
                                        {user.role && (
                                            <div className="flex items-center gap-3 text-sm">
                                                <div className="p-2 bg-indigo-50 rounded-lg">
                                                    <Shield className="h-4 w-4 text-indigo-600" />
                                                </div>
                                                <div>
                                                    <p className="text-gray-400 text-xs">Role</p>
                                                    <p className="font-medium text-gray-900 capitalize">{user.role}</p>
                                                </div>
                                            </div>
                                        )}

                                        {user.department && (
                                            <div className="flex items-center gap-3 text-sm">
                                                <div className="p-2 bg-emerald-50 rounded-lg">
                                                    <Building2 className="h-4 w-4 text-emerald-600" />
                                                </div>
                                                <div>
                                                    <p className="text-gray-400 text-xs">Department</p>
                                                    <p className="font-medium text-gray-900">{user.department}</p>
                                                </div>
                                            </div>
                                        )}

                                        <div className="flex items-center gap-3 text-sm">
                                            <div className="p-2 bg-amber-50 rounded-lg">
                                                <Calendar className="h-4 w-4 text-amber-600" />
                                            </div>
                                            <div>
                                                <p className="text-gray-400 text-xs">Member Since</p>
                                                <p className="font-medium text-gray-900">{user.created_at}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Right Column: Forms */}
                        <div className="lg:col-span-2 space-y-6">
                            {/* Profile Information - Read Only */}
                            <div className="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                                <div className="flex items-center gap-3 mb-6">
                                    <div className="p-2 bg-gray-100 rounded-lg">
                                        <User className="h-5 w-5 text-gray-600" />
                                    </div>
                                    <div>
                                        <h3 className="text-lg font-semibold text-gray-900">Profile Information</h3>
                                        <p className="text-sm text-gray-500">Your account details managed by administrator</p>
                                    </div>
                                </div>

                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1.5">
                                            Full Name
                                        </label>
                                        <div className="flex items-center gap-3 px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg">
                                            <User className="h-4 w-4 text-gray-400" />
                                            <span className="text-gray-700">{user.name}</span>
                                        </div>
                                    </div>

                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1.5">
                                            Email Address
                                        </label>
                                        <div className="flex items-center gap-3 px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg">
                                            <Mail className="h-4 w-4 text-gray-400" />
                                            <span className="text-gray-700">{user.email}</span>
                                        </div>
                                    </div>

                                    {user.role && (
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1.5">
                                                Role
                                            </label>
                                            <div className="flex items-center gap-3 px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg">
                                                <Shield className="h-4 w-4 text-gray-400" />
                                                <span className="text-gray-700 capitalize">{user.role}</span>
                                            </div>
                                        </div>
                                    )}

                                    {user.department && (
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1.5">
                                                Department
                                            </label>
                                            <div className="flex items-center gap-3 px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg">
                                                <Building2 className="h-4 w-4 text-gray-400" />
                                                <span className="text-gray-700">{user.department}</span>
                                            </div>
                                        </div>
                                    )}
                                </div>

                                <div className="mt-4 p-3 bg-blue-50 border border-blue-100 rounded-lg">
                                    <p className="text-sm text-blue-700 flex items-center gap-2">
                                        <AlertCircle className="h-4 w-4" />
                                        Contact your administrator to update your profile information.
                                    </p>
                                </div>
                            </div>

                            {/* Password Update Form */}
                            <div className="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                                <div className="flex items-center gap-3 mb-6">
                                    <div className="p-2 bg-gray-100 rounded-lg">
                                        <Lock className="h-5 w-5 text-gray-600" />
                                    </div>
                                    <div>
                                        <h3 className="text-lg font-semibold text-gray-900">Update Password</h3>
                                        <p className="text-sm text-gray-500">Ensure your account is using a secure password</p>
                                    </div>
                                </div>

                                {/* Success Message */}
                                {(recentlySuccessful || flash?.success) && (
                                    <div className="mb-6 p-4 bg-emerald-50 border border-emerald-200 rounded-lg flex items-center gap-3">
                                        <CheckCircle2 className="h-5 w-5 text-emerald-600" />
                                        <p className="text-sm text-emerald-700 font-medium">
                                            {flash?.success || 'Password updated successfully!'}
                                        </p>
                                    </div>
                                )}

                                <form onSubmit={handleSubmit} className="space-y-4">
                                    <div>
                                        <label htmlFor="current_password" className="block text-sm font-medium text-gray-700 mb-1.5">
                                            Current Password
                                        </label>
                                        <div className="relative">
                                            <input
                                                id="current_password"
                                                type={showCurrentPassword ? 'text' : 'password'}
                                                value={data.current_password}
                                                onChange={(e) => setData('current_password', e.target.value)}
                                                className={cn(
                                                    "w-full px-4 py-3 pr-12 border rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors",
                                                    errors.current_password ? "border-red-300 bg-red-50" : "border-gray-200"
                                                )}
                                                placeholder="Enter your current password"
                                            />
                                            <button
                                                type="button"
                                                onClick={() => setShowCurrentPassword(!showCurrentPassword)}
                                                className="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                                            >
                                                {showCurrentPassword ? <EyeOff className="h-5 w-5" /> : <Eye className="h-5 w-5" />}
                                            </button>
                                        </div>
                                        {errors.current_password && (
                                            <p className="mt-1.5 text-sm text-red-600">{errors.current_password}</p>
                                        )}
                                    </div>

                                    <div>
                                        <label htmlFor="password" className="block text-sm font-medium text-gray-700 mb-1.5">
                                            New Password
                                        </label>
                                        <div className="relative">
                                            <input
                                                id="password"
                                                type={showNewPassword ? 'text' : 'password'}
                                                value={data.password}
                                                onChange={(e) => setData('password', e.target.value)}
                                                className={cn(
                                                    "w-full px-4 py-3 pr-12 border rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors",
                                                    errors.password ? "border-red-300 bg-red-50" : "border-gray-200"
                                                )}
                                                placeholder="Enter new password"
                                            />
                                            <button
                                                type="button"
                                                onClick={() => setShowNewPassword(!showNewPassword)}
                                                className="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                                            >
                                                {showNewPassword ? <EyeOff className="h-5 w-5" /> : <Eye className="h-5 w-5" />}
                                            </button>
                                        </div>
                                        {errors.password && (
                                            <p className="mt-1.5 text-sm text-red-600">{errors.password}</p>
                                        )}
                                    </div>

                                    <div>
                                        <label htmlFor="password_confirmation" className="block text-sm font-medium text-gray-700 mb-1.5">
                                            Confirm New Password
                                        </label>
                                        <div className="relative">
                                            <input
                                                id="password_confirmation"
                                                type={showConfirmPassword ? 'text' : 'password'}
                                                value={data.password_confirmation}
                                                onChange={(e) => setData('password_confirmation', e.target.value)}
                                                className="w-full px-4 py-3 pr-12 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                                                placeholder="Confirm new password"
                                            />
                                            <button
                                                type="button"
                                                onClick={() => setShowConfirmPassword(!showConfirmPassword)}
                                                className="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                                            >
                                                {showConfirmPassword ? <EyeOff className="h-5 w-5" /> : <Eye className="h-5 w-5" />}
                                            </button>
                                        </div>
                                    </div>

                                    <div className="pt-4">
                                        <Button
                                            type="submit"
                                            disabled={processing}
                                            className="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2.5 rounded-lg font-medium transition-colors disabled:opacity-50"
                                        >
                                            {processing ? 'Updating...' : 'Update Password'}
                                        </Button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}
