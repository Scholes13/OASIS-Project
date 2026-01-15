<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-gray-50">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title inertia>{{ config('app.name', 'NumberSys') }}</title>
        <meta name="description" content="Enterprise Document Numbering & Approval System">

        <!-- Fonts - Preconnect for faster loading -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link rel="dns-prefetch" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- FontAwesome -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
        
        {{-- Ziggy Routes for React --}}
        @routes
        
        {{-- React/Inertia Scripts --}}
        @viteReactRefresh
        @vite(['resources/js/inertia/app.tsx'])
        @inertiaHead
        
        <!-- Prevent layout shift: Read sidebar state before render -->
        <script>
            (function() {
                var isMinimized = localStorage.getItem('sidebarMinimized') === 'true';
                if (isMinimized) {
                    document.documentElement.classList.add('sidebar-minimized');
                }
            })();
        </script>
        <style>
            /* Initial sidebar state to prevent layout shift */
            .sidebar-minimized .desktop-sidebar-container { width: 5rem !important; }
            .sidebar-minimized .fluid-sidebar { width: 5rem !important; }
            .sidebar-minimized .sidebar-label,
            .sidebar-minimized .sidebar-chevron,
            .sidebar-minimized .sidebar-treeline,
            .sidebar-minimized .sidebar-app-name { display: none !important; }
            .sidebar-minimized .sidebar-menu-item { justify-content: center !important; padding-left: 0.5rem !important; padding-right: 0.5rem !important; }
        </style>
    </head>
    <body class="h-full font-inter antialiased" 
          x-data="{ 
              sidebarOpen: false, 
              sidebarMinimized: localStorage.getItem('sidebarMinimized') === 'true',
              toggleSidebar() {
                  this.sidebarMinimized = !this.sidebarMinimized;
                  localStorage.setItem('sidebarMinimized', this.sidebarMinimized);
              }
          }" 
          x-init="$watch('sidebarMinimized', value => { 
              localStorage.setItem('sidebarMinimized', value);
              document.documentElement.classList.toggle('sidebar-minimized', value);
          })"
          @resize.window="if ($el.clientWidth >= 1024) sidebarOpen = false">
        <div class="h-full flex">
            <!-- Mobile sidebar overlay -->
            <div 
                x-show="sidebarOpen" 
                x-transition:enter="transition-opacity ease-linear duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition-opacity ease-linear duration-300"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 z-50 lg:hidden" 
                role="dialog" 
                aria-modal="true"
                style="display: none !important;"
                x-cloak>
                <!-- Background overlay -->
                <div class="fixed inset-0 bg-gray-900/80 backdrop-blur-sm" @click="sidebarOpen = false"></div>
                
                <!-- Mobile Sidebar -->
                <div class="fixed inset-y-0 left-0 z-50 w-72 overflow-y-auto">
                    <div 
                        x-show="sidebarOpen"
                        x-transition:enter="transition ease-in-out duration-300 transform"
                        x-transition:enter-start="-translate-x-full"
                        x-transition:enter-end="translate-x-0"
                        x-transition:leave="transition ease-in-out duration-300 transform"
                        x-transition:leave-start="translate-x-0"
                        x-transition:leave-end="-translate-x-full"
                        wire:key="mobile-sidebar-{{ auth()->id() }}-{{ session('current_user_role') }}">
                        <livewire:layout.sidebar />
                    </div>
                </div>
            </div>

            <!-- Desktop sidebar -->
            <div 
                class="hidden lg:flex lg:flex-shrink-0 smooth-transition overflow-visible desktop-sidebar-container"
                :class="sidebarMinimized ? 'lg:w-20' : ''">
                <div class="fluid-sidebar overflow-visible" :class="sidebarMinimized ? 'w-20' : ''" wire:key="desktop-sidebar-{{ auth()->id() }}-{{ session('current_user_role') }}">
                    <livewire:layout.sidebar />
                </div>
            </div>

            <!-- Main content area -->
            <div class="flex-1 flex flex-col overflow-hidden min-w-0">
                <!-- Top Header with Business Unit Switcher (LIVEWIRE - shared) -->
                <header class="bg-white border-b border-gray-200 shrink-0">
                    <div class="flex items-center justify-between h-16 px-4 sm:px-6 lg:px-8">
                        <!-- Mobile menu button -->
                        <button 
                            type="button" 
                            class="lg:hidden -ml-0.5 -mt-0.5 inline-flex items-center justify-center h-10 w-10 rounded-md text-gray-500 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500"
                            @click="sidebarOpen = true">
                            <span class="sr-only">Open sidebar</span>
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                            </svg>
                        </button>

                        <!-- Desktop toggle button -->
                        <button 
                            type="button" 
                            class="hidden lg:flex items-center justify-center h-10 w-10 rounded-md text-gray-500 hover:text-gray-900 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500 smooth-transition"
                            @click="toggleSidebar()">
                            <span class="sr-only">Toggle sidebar</span>
                            <svg 
                                class="h-6 w-6 transform smooth-transition" 
                                :class="sidebarMinimized ? 'rotate-180' : ''"
                                fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12H12m-8.25 5.25h16.5" />
                            </svg>
                        </button>

                        <!-- Business Unit Switcher (LIVEWIRE) -->
                        <div class="flex-1 flex items-center justify-end gap-4">
                            <livewire:components.business-unit-switcher wire:key="bu-switcher-inertia-{{ auth()->id() }}" />
                        </div>
                    </div>
                </header>

                <!-- Page Content - INERTIA -->
                <main class="flex-1 overflow-y-auto bg-gray-50">
                    @inertia
                </main>
            </div>
        </div>

        <!-- Toast Notification System (LIVEWIRE - shared) -->
        <div 
            x-data="notificationSystem()" 
            x-on:notify.window="addNotification($event.detail)"
            class="fixed bottom-4 right-4 z-[100] flex flex-col gap-2 pointer-events-none"
        >
            <template x-for="notification in notifications" :key="notification.id">
                <div 
                    x-show="notification.show"
                    x-transition:enter="transform ease-out duration-300"
                    x-transition:enter-start="translate-y-2 opacity-0"
                    x-transition:enter-end="translate-y-0 opacity-100"
                    x-transition:leave="transform ease-in duration-200"
                    x-transition:leave-start="translate-y-0 opacity-100"
                    x-transition:leave-end="translate-y-2 opacity-0"
                    class="pointer-events-auto min-w-[300px] max-w-md rounded-lg shadow-lg p-4 border"
                    :class="{
                        'bg-green-50 border-green-200': notification.type === 'success',
                        'bg-red-50 border-red-200': notification.type === 'error',
                        'bg-yellow-50 border-yellow-200': notification.type === 'warning',
                        'bg-blue-50 border-blue-200': notification.type === 'info'
                    }"
                >
                    <div class="flex items-start gap-3">
                        <div class="flex-shrink-0">
                            <template x-if="notification.type === 'success'">
                                <svg class="h-5 w-5 text-green-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </template>
                            <template x-if="notification.type === 'error'">
                                <svg class="h-5 w-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                                </svg>
                            </template>
                            <template x-if="notification.type === 'warning'">
                                <svg class="h-5 w-5 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                </svg>
                            </template>
                            <template x-if="notification.type === 'info'">
                                <svg class="h-5 w-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                                </svg>
                            </template>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium" 
                               :class="{
                                   'text-green-800': notification.type === 'success',
                                   'text-red-800': notification.type === 'error',
                                   'text-yellow-800': notification.type === 'warning',
                                   'text-blue-800': notification.type === 'info'
                               }"
                               x-text="notification.message"></p>
                        </div>
                        <button @click="removeNotification(notification.id)" class="flex-shrink-0">
                            <svg class="h-4 w-4 text-gray-400 hover:text-gray-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            </template>
        </div>

        @livewireScripts

        <script>
            // Toast Notification System
            function notificationSystem() {
                return {
                    notifications: [],
                    addNotification(detail) {
                        const id = Date.now();
                        const notification = {
                            id,
                            message: detail.message || detail,
                            type: detail.type || 'info',
                            show: true
                        };
                        this.notifications.push(notification);
                        
                        setTimeout(() => {
                            this.removeNotification(id);
                        }, detail.duration || 4000);
                    },
                    removeNotification(id) {
                        const index = this.notifications.findIndex(n => n.id === id);
                        if (index > -1) {
                            this.notifications[index].show = false;
                            setTimeout(() => {
                                this.notifications = this.notifications.filter(n => n.id !== id);
                            }, 300);
                        }
                    }
                };
            }

            // Bridge Livewire events to Inertia/React
            document.addEventListener('livewire:dispatch', (event) => {
                if (event.detail && event.detail.name === 'business-unit-switched') {
                    // Dispatch custom event for React components
                    window.dispatchEvent(new CustomEvent('bu-switched', { 
                        detail: event.detail.params || {}
                    }));
                }
            });
        </script>
    </body>
</html>
