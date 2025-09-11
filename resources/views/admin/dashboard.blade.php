<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col space-y-2 sm:flex-row sm:justify-between sm:items-center sm:space-y-0 lg:ml-4">
            <!-- Left side - Title and welcome -->
            <div class="flex flex-col">
                <h2 class="text-base font-semibold text-gray-800">
                    {{ __('Dashboard') }}
                </h2>
                <p class="text-sm text-gray-600 mt-1">
                    Welcome back, {{ auth()->user()->name }}
                </p>
            </div>
            
            <!-- Right side - Date and role badge -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:space-x-4 space-y-2 sm:space-y-0">
                <!-- Current date -->
                <div class="text-sm text-gray-500">
                    {{ now()->format('l, F j, Y') }}
                </div>
                
                <!-- Role badge -->
                <span class="inline-flex items-center px-2 py-1 rounded-md bg-red-100 text-red-800 text-xs font-medium">
                    <i class="fas fa-crown mr-1 text-xs"></i>
                    Super Admin
                </span>
            </div>
        </div>
    </x-slot>

    <!-- Dashboard Content -->
    <div class="space-y-6 max-w-none">
        <!-- Quick Stats -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 xl:gap-6">
            <!-- Total Users Card -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 lg:p-6 hover:shadow-lg hover:border-blue-300 transition-all duration-200">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-10 h-10 lg:w-12 lg:h-12 bg-gradient-to-br from-blue-100 to-blue-200 rounded-xl flex items-center justify-center">
                            <svg class="w-5 h-5 lg:w-6 lg:h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4 flex-1 min-w-0">
                        <h3 class="text-sm font-medium text-gray-900 truncate">Total Users</h3>
                        <p class="text-2xl lg:text-3xl font-bold text-gray-900">{{ $stats['total_users'] }}</p>
                        <p class="text-xs text-gray-500">{{ $stats['active_users'] }} active</p>
                    </div>
                </div>
            </div>

            <!-- Business Units Card -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 lg:p-6 hover:shadow-lg hover:border-emerald-300 transition-all duration-200">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-10 h-10 lg:w-12 lg:h-12 bg-gradient-to-br from-emerald-100 to-emerald-200 rounded-xl flex items-center justify-center">
                            <svg class="w-5 h-5 lg:w-6 lg:h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4 flex-1 min-w-0">
                        <h3 class="text-sm font-medium text-gray-900 truncate">Business Units</h3>
                        <p class="text-2xl lg:text-3xl font-bold text-gray-900">{{ $stats['total_business_units'] }}</p>
                        <p class="text-xs text-gray-500">{{ $stats['active_business_units'] }} active</p>
                    </div>
                </div>
            </div>

            <!-- Departments Card -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 lg:p-6 hover:shadow-lg hover:border-amber-300 transition-all duration-200">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-10 h-10 lg:w-12 lg:h-12 bg-gradient-to-br from-amber-100 to-amber-200 rounded-xl flex items-center justify-center">
                            <svg class="w-5 h-5 lg:w-6 lg:h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4 flex-1 min-w-0">
                        <h3 class="text-sm font-medium text-gray-900 truncate">Departments</h3>
                        <p class="text-2xl lg:text-3xl font-bold text-gray-900">{{ $stats['total_departments'] }}</p>
                        <p class="text-xs text-gray-500">{{ $stats['total_assignments'] }} assignments</p>
                    </div>
                </div>
            </div>

            <!-- Super Admins Card -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 lg:p-6 hover:shadow-lg hover:border-purple-300 transition-all duration-200">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-10 h-10 lg:w-12 lg:h-12 bg-gradient-to-br from-purple-100 to-purple-200 rounded-xl flex items-center justify-center">
                            <svg class="w-5 h-5 lg:w-6 lg:h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.586-4.414A2 2 0 0019 6.586L17.414 5A2 2 0 0016 4.586l-4 4v6l-1 1h-4l-1-1v-4l4-4z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4 flex-1 min-w-0">
                        <h3 class="text-sm font-medium text-gray-900 truncate">Super Admins</h3>
                        <p class="text-2xl lg:text-3xl font-bold text-gray-900">{{ $stats['super_admins'] }}</p>
                        <p class="text-xs text-gray-500">System administrators</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Admin Data Sections -->
        <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
            <!-- Recent Users -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 hover:shadow-md transition-shadow duration-200">
                <div class="px-4 py-4 lg:px-6 lg:py-5 border-b border-gray-200">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-semibold text-gray-900">Recent Users</h3>
                        <a href="{{ route('admin.users.index') }}" 
                           class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-lg text-blue-700 bg-blue-100 hover:bg-blue-200 transition-colors duration-200">
                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                            </svg>
                            View all
                        </a>
                    </div>
                </div>
                <div class="p-4 lg:p-6">
                    <div class="space-y-4">
                        @forelse($recentUsers as $user)
                            <div class="flex items-center justify-between p-3 rounded-lg hover:bg-gray-50 transition-colors duration-200">
                                <div class="flex items-center space-x-4">
                                    <div class="flex-shrink-0">
                                        <div class="h-10 w-10 rounded-xl bg-gradient-to-br from-blue-100 to-indigo-100 flex items-center justify-center ring-2 ring-blue-200">
                                            <span class="text-blue-700 text-sm font-bold">
                                                {{ strtoupper(substr($user->name, 0, 2)) }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-semibold text-gray-900 truncate">{{ $user->name }}</p>
                                        <p class="text-sm text-gray-500 truncate">{{ $user->email }}</p>
                                    </div>
                                </div>
                                <div class="text-right flex-shrink-0">
                                    <div class="flex flex-wrap gap-1 justify-end mb-1">
                                        @foreach($user->activeBusinessUnits->take(2) as $assignment)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 border border-blue-200">
                                                {{ $assignment->businessUnit->code }}
                                            </span>
                                        @endforeach
                                        @if($user->activeBusinessUnits->count() > 2)
                                            <span class="text-xs text-gray-500 font-medium">+{{ $user->activeBusinessUnits->count() - 2 }}</span>
                                        @endif
                                    </div>
                                    <p class="text-xs text-gray-400">{{ $user->created_at->diffForHumans() }}</p>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-8">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"></path>
                                </svg>
                                <p class="mt-2 text-gray-500">No users found</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Business Unit Distribution -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 hover:shadow-md transition-shadow duration-200">
                <div class="px-4 py-4 lg:px-6 lg:py-5 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Business Unit Distribution</h3>
                </div>
                <div class="p-4 lg:p-6">
                    <div class="space-y-5">
                        @foreach($businessUnitStats as $index => $bu)
                            <div class="group">
                                <div class="flex justify-between items-center mb-2">
                                    <span class="text-sm font-semibold text-gray-700 group-hover:text-gray-900 transition-colors duration-200">{{ $bu->name }}</span>
                                    <div class="flex items-center space-x-2">
                                        <span class="text-sm font-medium text-gray-900">{{ $bu->user_count }}</span>
                                        <span class="text-xs text-gray-500">users</span>
                                    </div>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-3 overflow-hidden">
                                    <div class="h-3 rounded-full transition-all duration-500 ease-out {{ 
                                        $index % 4 == 0 ? 'bg-gradient-to-r from-blue-500 to-blue-600' :
                                        ($index % 4 == 1 ? 'bg-gradient-to-r from-emerald-500 to-green-600' :
                                        ($index % 4 == 2 ? 'bg-gradient-to-r from-amber-500 to-yellow-600' :
                                        'bg-gradient-to-r from-purple-500 to-indigo-600'))
                                    }}" 
                                         style="width: {{ $stats['total_assignments'] > 0 ? ($bu->user_count / $stats['total_assignments']) * 100 : 0 }}%"></div>
                                </div>
                                <div class="mt-1 text-right">
                                    <span class="text-xs text-gray-400">
                                        {{ $stats['total_assignments'] > 0 ? round(($bu->user_count / $stats['total_assignments']) * 100, 1) : 0 }}%
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>