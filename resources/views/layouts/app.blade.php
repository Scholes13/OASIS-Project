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
        
        <!-- GLOBAL LOADING OVERLAY for Business Unit Switching - Modern Design -->
        <div 
            id="global-bu-loader"
            style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(255, 255, 255, 0.92); z-index: 999999; backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px);"
        >
            <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center; min-width: 360px; max-width: 480px;">
                
                <!-- Logo Transition Container - Staged Animation -->
                <div id="bu-logo-transition" style="display: flex; align-items: center; justify-content: center; gap: 28px; margin-bottom: 36px; min-height: 88px; position: relative;">
                    
                    <!-- From Logo - Starts at center, moves to left, then fades -->
                    <div id="bu-from-logo" style="width: 80px; height: 80px; border-radius: 20px; overflow: visible; display: flex; align-items: center; justify-content: center; opacity: 0; position: relative; flex-shrink: 0;">
                        <span id="bu-from-initials" style="font-size: 32px; font-weight: 700; color: white; display: none; width: 100%; height: 100%; align-items: center; justify-content: center; border-radius: 20px;"></span>
                        <img id="bu-from-img" style="width: 100%; height: 100%; object-fit: contain; display: none;" alt="">
                    </div>
                    
                    <!-- Arrow Animation - Hidden initially -->
                    <div id="bu-arrow-container" style="display: flex; align-items: center; gap: 6px; opacity: 0; transform: scale(0.5);">
                        <div style="width: 10px; height: 10px; background: #3b82f6; border-radius: 50%; animation: arrowPulse 1s ease-in-out infinite;"></div>
                        <div style="width: 10px; height: 10px; background: #3b82f6; border-radius: 50%; animation: arrowPulse 1s ease-in-out infinite; animation-delay: 0.15s;"></div>
                        <div style="width: 10px; height: 10px; background: #3b82f6; border-radius: 50%; animation: arrowPulse 1s ease-in-out infinite; animation-delay: 0.3s;"></div>
                        <svg style="width: 28px; height: 28px; color: #3b82f6; animation: arrowBounce 1s ease-in-out infinite;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                        </svg>
                    </div>
                    
                    <!-- To Logo - Hidden initially, appears from right -->
                    <div id="bu-to-logo" style="width: 80px; height: 80px; border-radius: 20px; overflow: visible; display: flex; align-items: center; justify-content: center; opacity: 0; transform: translateX(30px) scale(0.8); flex-shrink: 0;">
                        <span id="bu-to-initials" style="font-size: 32px; font-weight: 700; color: white; display: none; width: 100%; height: 100%; align-items: center; justify-content: center; border-radius: 20px;"></span>
                        <img id="bu-to-img" style="width: 100%; height: 100%; object-fit: contain; display: none;" alt="">
                    </div>
                </div>
                
                <!-- Animated Spinner Ring -->
                <div style="position: relative; width: 60px; height: 60px; margin: 0 auto 28px;">
                    <div style="position: absolute; inset: 0; border: 3px solid #e5e7eb; border-radius: 50%;"></div>
                    <div style="position: absolute; inset: 0; border: 3px solid #3b82f6; border-radius: 50%; border-top-color: transparent; animation: spin 1s linear infinite;"></div>
                    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);">
                        <svg style="width: 22px; height: 22px; color: #3b82f6;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                        </svg>
                    </div>
                </div>
                
                <!-- Text -->
                <div>
                    <h3 style="font-size: 22px; font-weight: 700; color: #111827; margin: 0 0 10px 0; letter-spacing: -0.025em;">Switching Business Unit</h3>
                    <p id="bu-switch-subtitle" style="font-size: 15px; color: #6b7280; margin: 0 0 20px 0; line-height: 1.5;">
                        Loading <span id="bu-to-name" style="font-weight: 600; color: #3b82f6;">new context</span>...
                    </p>
                </div>
                
                <!-- Progress Bar -->
                <div style="width: 220px; height: 5px; background: #e5e7eb; border-radius: 3px; overflow: hidden; margin: 0 auto;">
                    <div style="height: 100%; background: linear-gradient(90deg, #3b82f6, #60a5fa, #3b82f6); background-size: 200% 100%; animation: shimmer 1.5s ease-in-out infinite; border-radius: 3px;"></div>
                </div>
            </div>
        </div>
        
        <style>
            @keyframes spin {
                from { transform: rotate(0deg); }
                to { transform: rotate(360deg); }
            }
            
            @keyframes shimmer {
                0% { background-position: -200% 0; }
                100% { background-position: 200% 0; }
            }
            
            @keyframes arrowPulse {
                0%, 100% { opacity: 0.4; transform: scale(0.8); }
                50% { opacity: 1; transform: scale(1.1); }
            }
            
            @keyframes arrowBounce {
                0%, 100% { transform: translateX(0); }
                50% { transform: translateX(5px); }
            }
            
            /* Stage 1: FROM logo appears at center with scale */
            @keyframes stage1_fromAppear {
                0% { 
                    opacity: 0; 
                    transform: scale(0.5); 
                }
                100% { 
                    opacity: 1; 
                    transform: scale(1); 
                }
            }
            
            /* Stage 2: FROM logo slides to left */
            @keyframes stage2_fromSlideLeft {
                0% { 
                    opacity: 1; 
                    transform: translateX(54px) scale(1); 
                }
                100% { 
                    opacity: 1; 
                    transform: translateX(0) scale(0.95); 
                }
            }
            
            /* Stage 3: Arrow appears */
            @keyframes stage3_arrowAppear {
                0% { 
                    opacity: 0; 
                    transform: scale(0.3); 
                }
                100% { 
                    opacity: 1; 
                    transform: scale(1); 
                }
            }
            
            /* Stage 3: TO logo appears from right */
            @keyframes stage3_toAppear {
                0% { 
                    opacity: 0; 
                    transform: translateX(40px) scale(0.7); 
                }
                100% { 
                    opacity: 1; 
                    transform: translateX(0) scale(1); 
                }
            }
            
            /* Stage 4: FROM logo fades */
            @keyframes stage4_fromFade {
                0% { 
                    opacity: 1; 
                    transform: translateX(0) scale(0.95); 
                }
                100% { 
                    opacity: 0.35; 
                    transform: translateX(0) scale(0.9); 
                    filter: grayscale(0.5);
                }
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
            // ═══════════════════════════════════════════════════════════════════
            // 🎯 EVENT-DRIVEN SYNCHRONIZATION SYSTEM
            // ═══════════════════════════════════════════════════════════════════
            // Pattern: Orchestrator waits for ALL dependent components to acknowledge
            // before hiding global loader. Pure event-driven, NO timeouts!
            // ═══════════════════════════════════════════════════════════════════
            
            window.BuSwitchOrchestrator = {
                // Track which components need to acknowledge
                pendingAcknowledgments: new Set(),
                isActive: false,
                
                // Components that MUST acknowledge before loader hides
                requiredComponents: [],
                
                /**
                 * Start the switch process - called by BusinessUnitSwitcher
                 */
                startSwitch: function(fromBu, toBu, components = []) {
                    console.log('🚀 BU Switch started', { 
                        from: fromBu?.code, 
                        to: toBu?.code, 
                        waitingFor: components 
                    });
                    
                    this.isActive = true;
                    this.pendingAcknowledgments = new Set(components);
                    this.requiredComponents = [...components];
                    
                    // Show loader with logos
                    window.showGlobalBuLoader(fromBu, toBu);
                    
                    // NO TIMEOUT - pure event-driven!
                    // Loader stays until ALL components acknowledge
                },
                
                /**
                 * Component acknowledges it has finished refreshing
                 */
                acknowledge: function(componentName) {
                    console.log('📥 Acknowledge called:', componentName, {
                        isActive: this.isActive,
                        pending: Array.from(this.pendingAcknowledgments)
                    });
                    
                    if (!this.isActive) {
                        console.log('ℹ️ Acknowledgment received but no active switch');
                        return;
                    }
                    
                    // Remove from pending
                    const wasRemoved = this.pendingAcknowledgments.delete(componentName);
                    
                    console.log('✅ Component acknowledged:', componentName, {
                        wasInSet: wasRemoved,
                        remaining: Array.from(this.pendingAcknowledgments)
                    });
                    
                    // Check if all components have acknowledged
                    if (this.pendingAcknowledgments.size === 0) {
                        console.log('🎉 All components acknowledged - completing switch');
                        this.complete();
                    } else {
                        console.log('⏳ Still waiting for:', Array.from(this.pendingAcknowledgments));
                    }
                },
                
                /**
                 * Complete the switch - hide loader
                 */
                complete: function() {
                    if (!this.isActive) return;
                    
                    console.log('✨ BU Switch complete - hiding loader');
                    this.isActive = false;
                    this.pendingAcknowledgments.clear();
                    
                    // Use requestAnimationFrame to ensure DOM is painted
                    requestAnimationFrame(() => {
                        requestAnimationFrame(() => {
                            window.hideGlobalBuLoader();
                        });
                    });
                },
                
                /**
                 * Debug: Get current state
                 */
                getState: function() {
                    return {
                        isActive: this.isActive,
                        required: this.requiredComponents,
                        pending: Array.from(this.pendingAcknowledgments)
                    };
                }
            };
            
            // ═══════════════════════════════════════════════════════════════════
            // 🎯 REGISTER LIVEWIRE LISTENERS IMMEDIATELY
            // ═══════════════════════════════════════════════════════════════════
            // Must be registered before any Livewire component dispatches events
            
            document.addEventListener('livewire:init', function() {
                console.log('🎯 Livewire:init - Registering BU Switch listeners');
                
                // Listen for component acknowledgments
                Livewire.on('bu-switch-acknowledge', (params) => {
                    // Handle both object format and array format
                    const componentName = params?.component || params?.[0]?.component || params?.[0] || 'unknown';
                    console.log('📨 bu-switch-acknowledge event received:', params, '→', componentName);
                    window.BuSwitchOrchestrator.acknowledge(componentName);
                });
                
                // Handle static pages that need reload
                Livewire.on('business-unit-switched', () => {
                    const staticPages = ['/approvals', '/pr-numbers'];
                    const currentPath = window.location.pathname;
                    const isStaticPage = staticPages.some(page => 
                        currentPath === page || currentPath.startsWith(page + '?')
                    );
                    
                    if (isStaticPage) {
                        console.log('📄 Static page - reloading...');
                        setTimeout(() => window.location.reload(), 300);
                    }
                });
                
                console.log('✅ BU Switch listeners registered');
            });
            
            // ✅ IMMEDIATE: Define global function BEFORE DOMContentLoaded
            // This ensures it's available when Alpine/Livewire tries to call it
            window.showGlobalBuLoader = function(fromBu, toBu) {
                const globalLoader = document.getElementById('global-bu-loader');
                if (globalLoader) {
                    // Get elements
                    const fromLogo = document.getElementById('bu-from-logo');
                    const fromInitials = document.getElementById('bu-from-initials');
                    const fromImg = document.getElementById('bu-from-img');
                    const toLogo = document.getElementById('bu-to-logo');
                    const toInitials = document.getElementById('bu-to-initials');
                    const toImg = document.getElementById('bu-to-img');
                    const toName = document.getElementById('bu-to-name');
                    const arrowContainer = document.getElementById('bu-arrow-container');
                    
                    // ═══════════════════════════════════════════════════════════
                    // RESET ALL STATES
                    // ═══════════════════════════════════════════════════════════
                    fromLogo.style.opacity = '0';
                    fromLogo.style.transform = 'scale(0.5)';
                    fromLogo.style.animation = 'none';
                    fromLogo.style.filter = 'none';
                    
                    toLogo.style.opacity = '0';
                    toLogo.style.transform = 'translateX(30px) scale(0.8)';
                    toLogo.style.animation = 'none';
                    
                    arrowContainer.style.opacity = '0';
                    arrowContainer.style.transform = 'scale(0.3)';
                    arrowContainer.style.animation = 'none';
                    
                    // ═══════════════════════════════════════════════════════════
                    // SETUP LOGO CONTENT
                    // ═══════════════════════════════════════════════════════════
                    
                    // Update FROM logo - NO background/border for images with transparency
                    if (fromBu && fromBu.logo) {
                        fromImg.src = '/storage/' + fromBu.logo;
                        fromImg.style.display = 'block';
                        fromInitials.style.display = 'none';
                        fromLogo.style.background = 'none';
                        fromLogo.style.border = 'none';
                        fromLogo.style.boxShadow = 'none';
                    } else if (fromBu) {
                        fromInitials.textContent = (fromBu.code || 'BU').substring(0, 2);
                        fromInitials.style.display = 'flex';
                        fromImg.style.display = 'none';
                        fromLogo.style.background = 'linear-gradient(135deg, #6366f1, #8b5cf6)';
                        fromLogo.style.border = 'none';
                        fromLogo.style.boxShadow = '0 8px 32px rgba(0,0,0,0.12)';
                    }
                    
                    // Update TO logo - NO background/border for images with transparency
                    if (toBu && toBu.logo) {
                        toImg.src = '/storage/' + toBu.logo;
                        toImg.style.display = 'block';
                        toInitials.style.display = 'none';
                        toLogo.style.background = 'none';
                        toLogo.style.border = 'none';
                        toLogo.style.boxShadow = 'none';
                    } else if (toBu) {
                        toInitials.textContent = (toBu.code || 'BU').substring(0, 2);
                        toInitials.style.display = 'flex';
                        toImg.style.display = 'none';
                        toLogo.style.background = 'linear-gradient(135deg, #10b981, #059669)';
                        toLogo.style.border = 'none';
                        toLogo.style.boxShadow = '0 12px 40px rgba(59, 130, 246, 0.3)';
                    }
                    
                    // Update name
                    if (toBu) {
                        toName.textContent = toBu.name || toBu.code || 'new context';
                    }
                    
                    // Show loader
                    globalLoader.style.display = 'block';
                    document.body.style.overflow = 'hidden';
                    window._buLoaderShownAt = Date.now();
                    
                    // ═══════════════════════════════════════════════════════════
                    // STAGED ANIMATION SEQUENCE
                    // ═══════════════════════════════════════════════════════════
                    // Stage 1 (0ms): FROM logo appears at center
                    // Stage 2 (400ms): FROM logo slides to left
                    // Stage 3 (700ms): Arrow + TO logo appear
                    // Stage 4 (1100ms): FROM logo fades
                    
                    requestAnimationFrame(() => {
                        // Stage 1: FROM appears at center (starts offset to the right)
                        fromLogo.style.transform = 'translateX(54px) scale(0.5)';
                        fromLogo.style.animation = 'stage1_fromAppear 0.4s cubic-bezier(0.34, 1.56, 0.64, 1) forwards';
                        
                        // Stage 2: After appearing, slide to left
                        setTimeout(() => {
                            fromLogo.style.animation = 'stage2_fromSlideLeft 0.35s ease-out forwards';
                        }, 400);
                        
                        // Stage 3: Arrow and TO logo appear
                        setTimeout(() => {
                            arrowContainer.style.animation = 'stage3_arrowAppear 0.3s cubic-bezier(0.34, 1.56, 0.64, 1) forwards';
                            toLogo.style.animation = 'stage3_toAppear 0.4s cubic-bezier(0.34, 1.56, 0.64, 1) forwards';
                        }, 700);
                        
                        // Stage 4: FROM logo fades
                        setTimeout(() => {
                            fromLogo.style.animation = 'stage4_fromFade 0.5s ease-out forwards';
                        }, 1100);
                    });
                    
                    console.log('⚡ Loader shown with staged animations');
                } else {
                    console.error('❌ Loader element not found!');
                }
            };
            
            window.hideGlobalBuLoader = function() {
                const globalLoader = document.getElementById('global-bu-loader');
                if (!globalLoader) return;
                
                // Minimum loader time for smooth UX
                const MIN_LOADER_TIME = 500;
                const now = Date.now();
                const elapsed = window._buLoaderShownAt ? (now - window._buLoaderShownAt) : MIN_LOADER_TIME;
                const remaining = Math.max(0, MIN_LOADER_TIME - elapsed);
                
                setTimeout(() => {
                    requestAnimationFrame(() => {
                        globalLoader.style.display = 'none';
                        document.body.style.overflow = '';
                        window._buLoaderShownAt = null;
                        
                        // Reset animation classes for next use
                        document.getElementById('bu-from-logo')?.classList.remove('animate');
                        document.getElementById('bu-to-logo')?.classList.remove('animate');
                        document.querySelector('.bu-arrow-container')?.classList.remove('animate');
                        
                        console.log('✅ Loader hidden after', elapsed + remaining, 'ms');
                    });
                }, remaining);
            };
            
            // ═══════════════════════════════════════════════════════════════════
            // 👋 LOGOUT OVERLAY - "See You Again" Animation
            // ═══════════════════════════════════════════════════════════════════
            window.showLogoutOverlay = function(userName) {
                const overlay = document.createElement('div');
                overlay.id = 'logout-overlay';
                overlay.style.cssText = 'position: fixed; inset: 0; z-index: 999999; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, rgba(249, 250, 251, 0.98) 0%, rgba(243, 244, 246, 0.98) 100%); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); opacity: 0; transition: opacity 0.4s ease;';
                
                overlay.innerHTML = `
                    <div style='text-align: center; animation: logoutCardAppear 0.6s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;'>
                        <!-- Large Waving Hand -->
                        <div style='font-size: 120px; margin-bottom: 24px; animation: waveHandBig 0.8s ease-in-out infinite; filter: drop-shadow(0 10px 30px rgba(0,0,0,0.1));'>👋</div>
                        
                        <!-- Text -->
                        <div>
                            <h3 style='font-size: 32px; font-weight: 800; color: #111827; margin: 0 0 12px 0; letter-spacing: -0.025em;'>See You Again!</h3>
                            <p style='font-size: 18px; color: #6b7280; margin: 0 0 32px 0;'>Goodbye, <span style='font-weight: 700; color: #3b82f6;'>${userName || 'User'}</span></p>
                        </div>
                        
                        <!-- Animated Exit Door Icon -->
                        <div style='display: flex; align-items: center; justify-content: center; gap: 12px; margin-bottom: 24px;'>
                            <div style='width: 48px; height: 48px; background: linear-gradient(135deg, #3b82f6, #6366f1); border-radius: 12px; display: flex; align-items: center; justify-content: center; animation: doorPulse 1.5s ease-in-out infinite; box-shadow: 0 8px 24px rgba(59, 130, 246, 0.3);'>
                                <svg style='width: 24px; height: 24px; color: white; animation: exitArrow 1s ease-in-out infinite;' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                                    <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1'></path>
                                </svg>
                            </div>
                        </div>
                        
                        <!-- Progress Dots -->
                        <div style='display: flex; align-items: center; justify-content: center; gap: 10px;'>
                            <div style='width: 12px; height: 12px; background: #3b82f6; border-radius: 50%; animation: logoutDotPulse 1.2s ease-in-out infinite;'></div>
                            <div style='width: 12px; height: 12px; background: #3b82f6; border-radius: 50%; animation: logoutDotPulse 1.2s ease-in-out infinite; animation-delay: 0.2s;'></div>
                            <div style='width: 12px; height: 12px; background: #3b82f6; border-radius: 50%; animation: logoutDotPulse 1.2s ease-in-out infinite; animation-delay: 0.4s;'></div>
                        </div>
                        
                        <p style='font-size: 14px; color: #9ca3af; margin-top: 20px;'>Signing you out securely...</p>
                    </div>
                    
                    <style>
                        @keyframes logoutCardAppear { 0% { transform: scale(0.8) translateY(20px); opacity: 0; } 100% { transform: scale(1) translateY(0); opacity: 1; } }
                        @keyframes waveHandBig { 0%, 100% { transform: rotate(0deg); } 20% { transform: rotate(25deg); } 40% { transform: rotate(-15deg); } 60% { transform: rotate(20deg); } 80% { transform: rotate(-10deg); } }
                        @keyframes doorPulse { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.1); } }
                        @keyframes exitArrow { 0%, 100% { transform: translateX(0); } 50% { transform: translateX(4px); } }
                        @keyframes logoutDotPulse { 0%, 100% { transform: scale(0.8); opacity: 0.5; } 50% { transform: scale(1.3); opacity: 1; } }
                    </style>
                `;
                
                document.body.appendChild(overlay);
                
                // Trigger fade in
                requestAnimationFrame(() => {
                    overlay.style.opacity = '1';
                });
                
                return overlay;
            };
        </script>
        
        <!-- Livewire navigation handlers -->
        <script>
            document.addEventListener('livewire:navigating', function() {});
            document.addEventListener('livewire:navigated', function() {});
        </script>
    </body>
</html>