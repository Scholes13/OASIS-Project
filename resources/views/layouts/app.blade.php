<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-gray-50">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
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
    <body class="h-full font-inter antialiased overflow-hidden" x-data="{ sidebarOpen: false, sidebarMinimized: false }" @resize.window="if ($el.clientWidth >= 1024) sidebarOpen = false">
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
                style="display: none;">
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
                        x-transition:leave-end="-translate-x-full">
                        <livewire:layout.sidebar />
                    </div>
                </div>
            </div>

            <!-- Desktop sidebar -->
            <div 
                class="hidden lg:flex lg:flex-shrink-0 transition-all duration-300 ease-in-out"
                :class="sidebarMinimized ? 'lg:w-16' : 'lg:w-72'">
                <livewire:layout.sidebar />
            </div>

            <!-- Main content area -->
            <div class="flex-1 flex flex-col overflow-hidden">
                <!-- Top navigation bar -->
                <header class="bg-white shadow-sm border-b border-gray-200 flex-shrink-0">
                    <div class="flex h-16 items-center gap-x-4 px-4 sm:gap-x-6 sm:px-6 lg:px-8">
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

                    <!-- Page title and breadcrumbs -->
                    <div class="flex flex-1 gap-x-4 self-stretch lg:gap-x-6">
                        <div class="flex flex-1 items-center">
                            @if (isset($header))
                                <div class="flex items-center space-x-4">
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
                            <livewire:components.business-unit-switcher />
                            
                            <!-- Profile dropdown -->
                            <livewire:layout.user-menu />
                        </div>
                        </div>
                    </div>
                </header>

                <!-- Main content with proper scrolling -->
                <main class="flex-1 overflow-y-auto bg-gray-50">
                    <div class="py-6">
                        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                            <!-- Flash messages -->
                            @if (session('success'))
                                <div class="mb-6 rounded-lg bg-green-50 p-4 border border-green-200 shadow-sm">
                                    <div class="flex">
                                        <div class="flex-shrink-0">
                                            <svg class="h-5 w-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                                        </div>
                                        <div class="ml-auto pl-3">
                                            <button type="button" class="text-green-400 hover:text-green-600" onclick="this.parentElement.parentElement.parentElement.remove()">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endif
                            
                            @if (session('error'))
                                <div class="mb-6 rounded-lg bg-red-50 p-4 border border-red-200 shadow-sm">
                                    <div class="flex">
                                        <div class="flex-shrink-0">
                                            <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
                                        </div>
                                        <div class="ml-auto pl-3">
                                            <button type="button" class="text-red-400 hover:text-red-600" onclick="this.parentElement.parentElement.parentElement.remove()">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endif
                            
                            <!-- Page content -->
                            {{ $slot }}
                        </div>
                    </div>
                </main>
            </div>
        </div>
        
        @livewireScripts
        
        <!-- Additional Scripts -->
        @stack('scripts')
        
        <!-- Ensure proper Alpine.js initialization -->
        <script>
            // Fix Alpine.js initialization issues
            document.addEventListener('DOMContentLoaded', function() {
                // Ensure Alpine is properly initialized
                if (typeof Alpine !== 'undefined' && Alpine.start) {
                    Alpine.start();
                }
            });
            
            // Handle Livewire navigation errors gracefully
            document.addEventListener('livewire:navigating', function() {
                // Show loading state if needed
            });
            
            document.addEventListener('livewire:navigated', function() {
                // Re-initialize any components if needed
                if (typeof Alpine !== 'undefined' && Alpine.initTree) {
                    Alpine.initTree(document.body);
                }
            });
        </script>
    </body>
</html>
