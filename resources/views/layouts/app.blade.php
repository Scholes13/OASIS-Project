<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-gray-50">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ isset($title) ? $title . ' - ' : '' }}{{ config('app.name', 'NumberSys') }}</title>
        <meta name="description" content="Enterprise Document Numbering & Approval System">

        <!-- Fonts - Preconnect for faster loading -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link rel="dns-prefetch" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- FontAwesome - Preload for critical icons -->
        <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
        <noscript><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" /></noscript>

        <!-- Scripts - Vite with automatic versioning -->
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
                            <div wire:key="business-unit-switcher-{{ auth()->id() }}">
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
        
        <!-- GLOBAL LOADING OVERLAY for Business Unit Switching -->
        <div 
            id="global-bu-loader"
            style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(17, 24, 39, 0.75); z-index: 999999; backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px);"
        >
            <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; border-radius: 16px; padding: 40px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); text-align: center; min-width: 320px; max-width: 400px;">
                <!-- Spinner -->
                <svg style="animation: spin 1s linear infinite; width: 56px; height: 56px; color: #2563eb; margin: 0 auto 24px auto;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle style="opacity: 0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path style="opacity: 0.75;" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <!-- Text -->
                <div>
                    <p style="font-size: 18px; font-weight: 700; color: #111827; margin: 0 0 8px 0; letter-spacing: -0.025em;">Switching Business Unit</p>
                    <p style="font-size: 14px; color: #6b7280; margin: 0; line-height: 1.5;">Please wait while we rebuild the form with new data...</p>
                </div>
            </div>
        </div>
        
        <style>
            @keyframes spin {
                from { transform: rotate(0deg); }
                to { transform: rotate(360deg); }
            }
            
            /* Force overlay to be on top of everything */
            #global-bu-loader {
                pointer-events: all !important;
            }
            
            #global-bu-loader[style*="display: block"],
            #global-bu-loader[style*="display:block"] {
                display: block !important;
            }
        </style>
        
        <script>
            // ✅ IMMEDIATE: Define global function BEFORE DOMContentLoaded
            // This ensures it's available when Alpine/Livewire tries to call it
            window.showGlobalBuLoader = function() {
                const globalLoader = document.getElementById('global-bu-loader');
                if (globalLoader) {
                    globalLoader.style.display = 'block';
                    document.body.style.overflow = 'hidden';
                    window._buLoaderShownAt = Date.now();
                    console.log('⚡ Loader shown at', window._buLoaderShownAt);
                    
                    // ✅ FALLBACK: Auto-hide after 5 seconds if no response
                    if (window._buLoaderTimeout) {
                        clearTimeout(window._buLoaderTimeout);
                    }
                    window._buLoaderTimeout = setTimeout(() => {
                        console.log('⚠️ Loader timeout - auto-hiding after 5s');
                        window._buLoaderShownAt = null;
                        window.hideGlobalBuLoader();
                    }, 5000);
                } else {
                    console.error('❌ Loader element not found!');
                }
            };
            
            window.hideGlobalBuLoader = function() {
                const globalLoader = document.getElementById('global-bu-loader');
                if (!globalLoader) return;
                
                const MIN_LOADER_TIME = 300;
                const now = Date.now();
                const elapsed = window._buLoaderShownAt ? (now - window._buLoaderShownAt) : MIN_LOADER_TIME;
                const remaining = Math.max(0, MIN_LOADER_TIME - elapsed);
                
                setTimeout(() => {
                    globalLoader.style.display = 'none';
                    document.body.style.overflow = '';
                    console.log('✅ Loader hidden');
                    
                    if (window._buLoaderTimeout) {
                        clearTimeout(window._buLoaderTimeout);
                        window._buLoaderTimeout = null;
                    }
                    window._buLoaderShownAt = null;
                }, remaining);
            };
            
            // Register Livewire listener when ready
            document.addEventListener('DOMContentLoaded', function() {
                console.log('🎯 Global BU Loader initialized');
                
                if (typeof Livewire !== 'undefined') {
                    // ✅ PRIMARY: Listen for explicit completion event from components
                    Livewire.on('business-unit-switched-complete', () => {
                        console.log('✅ Business unit switching completed (explicit)');
                        requestAnimationFrame(() => {
                            requestAnimationFrame(() => {
                                window.hideGlobalBuLoader();
                            });
                        });
                    });
                    
                    // ✅ FIX: Listen for BU switch on static pages
                    Livewire.on('business-unit-switched', () => {
                        const staticPages = ['/approvals', '/pr-numbers'];
                        const currentPath = window.location.pathname;
                        const isStaticPage = staticPages.some(page => currentPath === page || currentPath.startsWith(page + '?'));
                        
                        if (isStaticPage) {
                            console.log('📄 Static page detected, reloading...');
                            window.showGlobalBuLoader();
                            setTimeout(() => window.location.reload(), 300);
                        }
                    });
                    
                    console.log('✅ Livewire completion listener registered');
                } else {
                    console.error('❌ Livewire not available');
                }
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
