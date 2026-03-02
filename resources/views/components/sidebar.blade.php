@php
    $user = auth()->user();
    $currentRoute = request()->route()?->getName() ?? '';
    $currentBusinessUnitId = session('current_business_unit_id');
@endphp

<div class="flex h-full flex-col bg-gradient-to-b from-slate-900 to-slate-800" data-testid="sidebar">
    <!-- Logo -->
    <div class="flex h-16 shrink-0 items-center px-6 border-b border-slate-700/50">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
            <div class="w-8 h-8 bg-primary rounded-lg flex items-center justify-center">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
            </div>
            <span class="sidebar-app-name text-lg font-semibold text-white">Oasis</span>
        </a>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1">
        <!-- Dashboard -->
        <a href="{{ route('dashboard') }}" 
           class="sidebar-menu-item flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors {{ $currentRoute === 'dashboard' ? 'bg-blue-50 text-white' : 'text-slate-300 hover:bg-slate-700/50 hover:text-white' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
            </svg>
            <span class="sidebar-label">Dashboard</span>
        </a>

        <!-- Purchasing Section -->
        <div class="pt-4">
            <p class="sidebar-label px-3 text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Purchasing</p>
            
            <a href="{{ route('purchase-requests.index') }}" 
               class="sidebar-menu-item flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors {{ str_starts_with($currentRoute, 'purchase-requests') ? 'bg-blue-50 text-white' : 'text-slate-300 hover:bg-slate-700/50 hover:text-white' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <span class="sidebar-label">Purchase Requests</span>
            </a>

            <a href="{{ route('stock-requests.index') }}" 
               class="sidebar-menu-item flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors {{ str_starts_with($currentRoute, 'stock-requests') ? 'bg-blue-50 text-white' : 'text-slate-300 hover:bg-slate-700/50 hover:text-white' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                </svg>
                <span class="sidebar-label">Stock Requests</span>
            </a>

            <a href="{{ route('approvals.index') }}" 
               class="sidebar-menu-item flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors {{ str_starts_with($currentRoute, 'approvals') ? 'bg-blue-50 text-white' : 'text-slate-300 hover:bg-slate-700/50 hover:text-white' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span class="sidebar-label">Approvals</span>
            </a>
        </div>

        <!-- Activity Section -->
        <div class="pt-4">
            <p class="sidebar-label px-3 text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Activity</p>
            
            <a href="{{ route('activity.task.index') }}" 
               class="sidebar-menu-item flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors {{ str_starts_with($currentRoute, 'activity.task') ? 'bg-blue-50 text-white' : 'text-slate-300 hover:bg-slate-700/50 hover:text-white' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                </svg>
                <span class="sidebar-label">My Tasks</span>
            </a>
        </div>

        @if($user && ($user->isSuperAdmin() || $user->can('access-admin')))
        <!-- Admin Section -->
        <div class="pt-4">
            <p class="sidebar-label px-3 text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Administration</p>
            
            <a href="{{ route('admin.dashboard') }}" 
               class="sidebar-menu-item flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors {{ str_starts_with($currentRoute, 'admin') ? 'bg-blue-50 text-white' : 'text-slate-300 hover:bg-slate-700/50 hover:text-white' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <span class="sidebar-label">Admin Panel</span>
            </a>
        </div>
        @endif
    </nav>

    <!-- User Info at Bottom -->
    <div class="border-t border-slate-700/50 p-4">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center text-white font-medium">
                {{ substr($user->name ?? 'U', 0, 1) }}
            </div>
            <div class="sidebar-label flex-1 min-w-0">
                <p class="text-sm font-medium text-white truncate">{{ $user->name ?? 'User' }}</p>
                <p class="text-xs text-slate-400 truncate">{{ $user->email ?? '' }}</p>
            </div>
        </div>
    </div>
</div>
