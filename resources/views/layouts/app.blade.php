<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-gray-50">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ isset($title) ? $title . ' - ' : '' }}{{ config('app.name', 'NumberSys') }}</title>
        <meta name="description" content="Enterprise Document Numbering & Approval System">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- FontAwesome -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="h-full font-inter antialiased" x-data="{ sidebarOpen: false, sidebarMinimized: false }" @resize.window="if ($el.clientWidth >= 1024) sidebarOpen = false">
        <div class="h-full flex overflow-hidden">
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
                class="hidden lg:flex lg:flex-shrink-0 smooth-transition"
                :class="sidebarMinimized ? 'lg:w-16' : ''">
                <div class="fluid-sidebar" wire:key="desktop-sidebar-{{ auth()->id() }}-{{ session('current_user_role') }}">
                    <livewire:layout.sidebar />
                </div>
            </div>

            <!-- Main content area -->
            <div class="flex-1 flex flex-col overflow-hidden">
                <!-- Top navigation bar -->
                <header class="bg-white shadow-sm border-b border-gray-200 flex-shrink-0">
                    <div class="fluid-header px-4 sm:px-6 lg:px-8">
                                            <!-- Mobile menu button -->
                        <button 
                            type="button" 
                            class="-m-2.5 p-2.5 text-gray-700 lg:hidden hover:text-gray-900 transition-colors duration-200" 
                            @click="sidebarOpen = true">
                            <span class="sr-only">Open sidebar</span>
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                            </svg>
                        </button>

                        <!-- Separator -->
                        <div class="h-6 w-px bg-gray-200 lg:hidden"></div>

                        <!-- Desktop sidebar toggle -->
                        <button 
                            type="button" 
                            class="hidden lg:flex -m-2.5 p-2.5 text-gray-700 hover:text-blue-600 transition-colors duration-200 rounded-lg hover:bg-gray-100" 
                            @click="sidebarMinimized = !sidebarMinimized"
                            :title="sidebarMinimized ? 'Expand sidebar' : 'Minimize sidebar'">
                            <span class="sr-only">Toggle sidebar</span>
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25H12" />
                            </svg>
                        </button>

                        <!-- Spacer between toggle and content -->
                        <div class="hidden lg:block w-px h-6 bg-gray-200 mx-4"></div>

                    <!-- Page title and breadcrumbs -->
                    <div class="flex flex-1 gap-x-4 self-stretch lg:gap-x-6 min-w-0">
                        <div class="flex flex-1 items-center min-w-0">
                            @if (isset($header))
                                <div class="w-full min-w-0">
                                    @if (isset($breadcrumbs))
                                        <nav class="flex" aria-label="Breadcrumb">
                                            <ol class="flex items-center space-x-2">
                                                {{ $breadcrumbs }}
                                            </ol>
                                        </nav>
                                    @else
                                        {{ $header }}
                                    @endif
                                </div>
                            @endif
                        </div>
                        
                        <!-- Right side of top nav -->
                        <div class="flex items-center gap-x-4 lg:gap-x-6">
                            <!-- Business Unit Switcher -->
                            <div wire:key="business-unit-switcher-{{ auth()->id() }}-{{ session('current_business_unit_id', 'none') }}">
                                <livewire:components.business-unit-switcher />
                            </div>
                            
                            <!-- Profile dropdown -->
                            <div wire:key="user-menu-{{ auth()->id() }}">
                                <livewire:layout.user-menu />
                            </div>
                        </div>
                        </div>
                    </div>
                </header>

                <!-- Main content with proper scrolling -->
                <main class="flex-1 overflow-y-auto bg-gray-50">
                    <div class="content-spacing">
                        <div class="fluid-container">
                            <!-- Page content -->
                            {{ $slot }}
                        </div>
                    </div>
                </main>
            </div>
        </div>
        
        <!-- Toast Notifications -->
        <x-toast-notification />
        
        @livewireScripts
        
        <!-- Additional Scripts -->
        @stack('scripts')
        
        <!-- Toast Notification Helper -->
        <script>
            // Toast notification helper function
            window.notify = function(message, type = 'info', duration = 5000) {
                window.dispatchEvent(new CustomEvent('notify', {
                    detail: { message, type, duration }
                }));
            };
            
            // Auto-show flash messages as toasts
            document.addEventListener('DOMContentLoaded', function() {
                @if (session()->has('success'))
                    notify('{{ session('success') }}', 'success');
                @endif
                
                @if (session()->has('error'))
                    notify('{{ session('error') }}', 'error');
                @endif
                
                @if (session()->has('warning'))
                    notify('{{ session('warning') }}', 'warning');
                @endif
                
                @if (session()->has('info'))
                    notify('{{ session('info') }}', 'info');
                @endif
                
                @if ($errors->any())
                    @foreach ($errors->all() as $error)
                        notify('{{ $error }}', 'error', 8000);
                    @endforeach
                @endif
            });
        </script>
        
        <!-- Livewire navigation handlers only (Alpine is auto-loaded by Livewire 3) -->
        <script>
            // Handle Livewire navigation gracefully
            document.addEventListener('livewire:navigating', function() {
                // Show loading state if needed
            });
            
            document.addEventListener('livewire:navigated', function() {
                // Page navigation completed
            });
        </script>
    </body>
</html>
