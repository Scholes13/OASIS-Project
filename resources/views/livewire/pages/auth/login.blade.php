<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public LoginForm $form;
    public bool $loading = false;

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        $this->loading = true;
        
        try {
            $this->validate();
            $this->form->authenticate();
            Session::regenerate();
            $this->redirectIntended(default: route('dashboard', absolute: false));
        } catch (\Exception $e) {
            $this->loading = false;
            throw $e;
        }
    }
    
    public function updatedForm()
    {
        $this->loading = false;
    }
}; ?>

<div class="min-h-screen flex relative" 
     x-data="{ 
         loggingIn: false,
         hideOverlay() {
             this.loggingIn = false;
             const overlay = document.getElementById('login-overlay');
             if (overlay) {
                 overlay.remove();
             }
         }
     }"
     x-on:livewire:load="hideOverlay()">

    <!-- Logo Header - Fixed Top Left of Page -->
    <div class="absolute top-3.5 left-3.5 sm:top-5 sm:left-5 z-50 flex items-center gap-2 sm:gap-3">
        <img src="{{ asset('storage/business-units/aDNLhQNtI0R0KiPTv6oFWC2NwLu11tewYoJwjhTg.png') }}" 
             alt="Werkudara Logo" 
             class="h-8 w-8 sm:h-10 sm:w-10 lg:h-12 lg:w-12 object-contain">
        <div class="text-gray-700 text-xs sm:text-sm lg:text-base">
            <span class="font-bold">Werkudara</span> Group
        </div>
    </div>

    <!-- Left Side - Login Form (40%) -->
        <!-- Left Side - Login Form (40%) -->
    <div class="w-full lg:w-2/5 flex items-center justify-center bg-white px-6 py-8 sm:px-8 lg:px-10">
        <div class="w-full max-w-sm space-y-8">
            
            <!-- Header -->
            <div>
                <h1 class="text-3xl sm:text-4xl font-bold text-gray-900 mb-2">
                    Welcome to OASIS
                </h1>
                <p class="text-base sm:text-lg text-gray-600">
                    Sign into your account
                </p>
            </div>
            
            <!-- Login Form -->
            <div class="mt-8">
                
                <!-- Session Status -->
                @if (session('status'))
                    <div class="mb-6 bg-green-50 border border-green-200 rounded-lg p-4">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-green-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span class="text-green-800 text-sm font-medium">{{ session('status') }}</span>
                        </div>
                    </div>
                @endif
                
                <!-- Error Messages -->
                @if (session('error'))
                    <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-red-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span class="text-red-800 text-sm font-medium">{{ session('error') }}</span>
                        </div>
                    </div>
                @endif

                <form wire:submit="login" class="space-y-5" 
                  x-data="{ 
                      showOverlay() {
                          loggingIn = true;
                          
                          // Create and show loading overlay
                          const overlay = document.createElement('div');
                          overlay.id = 'login-overlay';
                          overlay.className = 'fixed inset-0 z-[9999] flex items-center justify-center bg-gray-900/50 backdrop-blur-sm';
                          overlay.innerHTML = `
                              <div class='bg-white rounded-lg shadow-xl p-6 flex items-center space-x-4'>
                                  <svg class='animate-spin h-6 w-6 text-indigo-600' fill='none' viewBox='0 0 24 24'>
                                      <circle class='opacity-25' cx='12' cy='12' r='10' stroke='currentColor' stroke-width='4'></circle>
                                      <path class='opacity-75' fill='currentColor' d='M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z'></path>
                                  </svg>
                                  <span class='text-gray-700 font-medium'>Signing in...</span>
                              </div>
                          `;
                          document.body.appendChild(overlay);
                      },
                      hideOverlay() {
                          loggingIn = false;
                          const overlay = document.getElementById('login-overlay');
                          if (overlay) {
                              overlay.remove();
                          }
                      }
                  }"
                  x-on:submit="showOverlay()">

                    
                    <!-- Email Input -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                            Phone or Email address
                        </label>
                        <input wire:model="form.email" 
                               id="email"
                               type="email" 
                               required 
                               autofocus
                               autocomplete="username"
                               class="w-full px-4 py-2.5 sm:py-3 text-sm sm:text-base border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200"
                               placeholder="Phone or Email address">
                        @error('form.email') 
                            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <!-- Password Input -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                            Password
                        </label>
                        <input wire:model="form.password" 
                               id="password"
                               type="password" 
                               required 
                               autocomplete="current-password"
                               class="w-full px-4 py-2.5 sm:py-3 text-sm sm:text-base border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200"
                               placeholder="Password">
                        @error('form.password') 
                            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <!-- Login Button -->
                    <div>
                        <button type="submit" 
                                class="w-full py-2.5 sm:py-3 px-6 border border-transparent rounded-lg text-sm sm:text-base font-semibold text-white bg-blue-500 hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200">
                            Log In
                        </button>
                    </div>
                    
                    <!-- Forgot Password -->
                    @if (Route::has('password.request'))
                        <div class="text-center">
                            <a href="{{ route('password.request') }}" 
                               wire:navigate
                               class="text-sm font-medium text-blue-500 hover:text-blue-600 transition-colors duration-200">
                                Forgot password?
                            </a>
                        </div>
                    @endif
                </form>
            </div>
            
            <!-- Footer -->
            <div class="mt-6 sm:mt-8 text-center">
                <p class="text-xs text-gray-500">
                    © {{ date('Y') }} OASIS. Secure office administration system.
                </p>
            </div>
        </div>
    </div>

    <!-- Right Side - Illustration (60%) -->
    <div class="hidden lg:flex lg:w-3/5 bg-gradient-to-br from-blue-500 via-blue-600 to-blue-700 relative overflow-hidden items-center justify-center p-6 lg:p-8 xl:p-10">
        <!-- Decorative Background Elements -->
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-10 left-10 w-1/3 h-1/3 bg-white rounded-full blur-3xl"></div>
            <div class="absolute bottom-10 right-10 w-1/2 h-1/2 bg-cyan-300 rounded-full blur-3xl"></div>
            <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-2/5 h-2/5 bg-purple-400 rounded-full blur-3xl"></div>
        </div>
        
        <!-- Isometric Illustration -->
        <div class="relative z-10 text-center text-white w-full max-w-3xl 2xl:max-w-4xl">
            <div class="mb-6 xl:mb-8 2xl:mb-10">
                <svg class="w-full h-auto mx-auto" viewBox="0 0 600 450" preserveAspectRatio="xMidYMid meet" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <!-- Abstract OASIS Illustration -->
                    <g opacity="0.95">
                        <!-- Central Circle - Representing "O" in OASIS -->
                        <circle cx="300" cy="225" r="120" fill="#60A5FA" opacity="0.3"/>
                        <circle cx="300" cy="225" r="90" fill="#93C5FD" opacity="0.4"/>
                        <circle cx="300" cy="225" r="60" fill="#DBEAFE" opacity="0.5"/>
                        
                        <!-- Floating Documents - Left -->
                        <g transform="translate(100, 150) rotate(-15)">
                            <rect x="0" y="0" width="80" height="100" rx="8" fill="#F472B6" opacity="0.9"/>
                            <line x1="15" y1="20" x2="65" y2="20" stroke="white" stroke-width="3" opacity="0.7"/>
                            <line x1="15" y1="35" x2="65" y2="35" stroke="white" stroke-width="3" opacity="0.6"/>
                            <line x1="15" y1="50" x2="55" y2="50" stroke="white" stroke-width="3" opacity="0.5"/>
                        </g>
                        
                        <!-- Approval Checkmark - Top Right -->
                        <g transform="translate(450, 100)">
                            <circle cx="0" cy="0" r="50" fill="#10B981" opacity="0.95"/>
                            <path d="M-20 0 L-8 12 L20 -20" stroke="white" stroke-width="8" stroke-linecap="round" stroke-linejoin="round"/>
                        </g>
                        
                        <!-- Analytics Chart - Top Left -->
                        <g transform="translate(120, 100)">
                            <rect x="0" y="0" width="90" height="110" rx="8" fill="#A78BFA" opacity="0.9"/>
                            <path d="M15 75 L30 50 L45 60 L60 35 L75 45" stroke="#DBEAFE" stroke-width="4" stroke-linecap="round"/>
                            <!-- Bar chart -->
                            <rect x="15" y="85" width="8" height="20" fill="#DBEAFE" opacity="0.8"/>
                            <rect x="28" y="75" width="8" height="30" fill="#DBEAFE" opacity="0.8"/>
                            <rect x="41" y="80" width="8" height="25" fill="#DBEAFE" opacity="0.8"/>
                            <rect x="54" y="70" width="8" height="35" fill="#DBEAFE" opacity="0.8"/>
                        </g>
                        
                        <!-- File/Folder - Right -->
                        <g transform="translate(450, 280) rotate(10)">
                            <rect x="0" y="0" width="85" height="105" rx="8" fill="#34D399" opacity="0.9"/>
                            <line x1="15" y1="25" x2="70" y2="25" stroke="white" stroke-width="3" opacity="0.7"/>
                            <line x1="15" y1="40" x2="70" y2="40" stroke="white" stroke-width="3" opacity="0.6"/>
                            <line x1="15" y1="55" x2="60" y2="55" stroke="white" stroke-width="3" opacity="0.5"/>
                            <line x1="15" y1="70" x2="70" y2="70" stroke="white" stroke-width="3" opacity="0.4"/>
                        </g>
                        
                        <!-- Notification Badge - Bottom Left -->
                        <g transform="translate(130, 330)">
                            <rect x="0" y="0" width="100" height="70" rx="10" fill="#6366F1" opacity="0.9"/>
                            <circle cx="20" cy="25" r="8" fill="white"/>
                            <line x1="35" y1="20" x2="85" y2="20" stroke="white" stroke-width="3" opacity="0.8"/>
                            <line x1="35" y1="30" x2="75" y2="30" stroke="white" stroke-width="3" opacity="0.6"/>
                            <line x1="15" y1="50" x2="85" y2="50" stroke="white" stroke-width="3" opacity="0.5"/>
                        </g>
                        
                        <!-- Task Completed - Bottom Right -->
                        <g transform="translate(420, 340)">
                            <rect x="0" y="0" width="90" height="65" rx="10" fill="#EC4899" opacity="0.9"/>
                            <path d="M25 35 L38 48 L65 20" stroke="white" stroke-width="6" stroke-linecap="round" stroke-linejoin="round"/>
                        </g>
                        
                        <!-- Central Icon - OASIS Symbol -->
                        <g transform="translate(300, 225)">
                            <!-- Letter "O" stylized as circular flow -->
                            <circle cx="0" cy="0" r="35" stroke="#1E40AF" stroke-width="6" fill="none" opacity="0.8"/>
                            <!-- Arrow indicating flow/system -->
                            <path d="M25 -5 L35 0 L25 5" fill="#1E40AF" opacity="0.8"/>
                            <circle cx="0" cy="0" r="12" fill="#3B82F6" opacity="0.9"/>
                        </g>
                        
                        <!-- Connecting Lines - Showing System Integration -->
                        <line x1="240" y1="180" x2="180" y2="150" stroke="#DBEAFE" stroke-width="2" stroke-dasharray="5,5" opacity="0.3"/>
                        <line x1="360" y1="180" x2="450" y2="150" stroke="#DBEAFE" stroke-width="2" stroke-dasharray="5,5" opacity="0.3"/>
                        <line x1="240" y1="270" x2="180" y2="330" stroke="#DBEAFE" stroke-width="2" stroke-dasharray="5,5" opacity="0.3"/>
                        <line x1="360" y1="270" x2="470" y2="340" stroke="#DBEAFE" stroke-width="2" stroke-dasharray="5,5" opacity="0.3"/>
                        
                        <!-- Floating Particles/Dots -->
                        <circle cx="250" cy="120" r="4" fill="white" opacity="0.6"/>
                        <circle cx="350" cy="140" r="3" fill="white" opacity="0.5"/>
                        <circle cx="200" cy="250" r="5" fill="white" opacity="0.4"/>
                        <circle cx="400" cy="260" r="4" fill="white" opacity="0.5"/>
                        <circle cx="320" cy="360" r="3" fill="white" opacity="0.6"/>
                    </g>
                </svg>
            </div>
            
            <h2 class="text-2xl sm:text-3xl xl:text-4xl 2xl:text-5xl font-bold mb-3 xl:mb-4 2xl:mb-6">Streamline Your Office Workflow</h2>
            <p class="text-base sm:text-lg xl:text-xl 2xl:text-2xl text-blue-100 max-w-xl xl:max-w-2xl mx-auto px-4">
                Manage documents, approvals, and administration tasks efficiently in one place
            </p>
        </div>
    </div>
</div>

<script>
document.addEventListener('livewire:init', () => {
    // Listen for Livewire events to hide overlay
    Livewire.on('hideOverlay', () => {
        const overlay = document.getElementById('login-overlay');
        if (overlay) {
            overlay.remove();
        }
    });
    
    // Hide overlay when component updates (including errors)
    Livewire.hook('morph.updated', ({ component }) => {
        if (component.name === 'pages.auth.login') {
            const overlay = document.getElementById('login-overlay');
            if (overlay) {
                overlay.remove();
            }
        }
    });
    
    // Hide overlay on any error
    Livewire.hook('request.exception', () => {
        const overlay = document.getElementById('login-overlay');
        if (overlay) {
            overlay.remove();
        }
    });
});
</script>
