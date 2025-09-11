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

<div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-indigo-50 via-white to-purple-50 py-12 px-4 sm:px-6 lg:px-8" 
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

    <div class="max-w-md w-full space-y-8">
        
        <!-- Logo & Header -->
        <div class="text-center">
            <div class="mx-auto w-20 h-20 bg-gradient-to-br from-indigo-600 to-purple-600 rounded-3xl flex items-center justify-center shadow-xl transform hover:scale-105 transition-transform duration-300">
                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                </svg>
            </div>
            <h2 class="mt-6 text-center text-3xl font-bold bg-gradient-to-r from-gray-900 to-gray-700 bg-clip-text text-transparent">
                Welcome to NumberSys
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                Enterprise Document Numbering & Approval System
            </p>
        </div>
        
        <!-- Login Form Card -->
        <div class="bg-white/80 backdrop-blur-sm py-8 px-6 shadow-2xl rounded-3xl border border-gray-200/50 transform hover:shadow-3xl transition-all duration-300">
            
            <!-- Session Status -->
            @if (session('status'))
                <div class="mb-6 bg-green-50 border border-green-200 rounded-xl p-4">
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
                <div class="mb-6 bg-red-50 border border-red-200 rounded-xl p-4">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-red-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span class="text-red-800 text-sm font-medium">{{ session('error') }}</span>
                    </div>
                </div>
            @endif

            <form wire:submit="login" class="space-y-6" 
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
                <div class="space-y-2">
                    <label for="email" class="block text-sm font-semibold text-gray-700">
                        Email Address
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"></path>
                            </svg>
                        </div>
                        <input wire:model="form.email" 
                               id="email"
                               type="email" 
                               required 
                               autofocus
                               autocomplete="username"
                               class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200 bg-gray-50/50 focus:bg-white"
                               placeholder="Enter your email address">
                    </div>
                    @error('form.email') 
                        <p class="text-sm text-red-600 flex items-center mt-1">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            {{ $message }}
                        </p>
                    @enderror
                </div>
                
                <!-- Password Input -->
                <div class="space-y-2">
                    <label for="password" class="block text-sm font-semibold text-gray-700">
                        Password
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                            </svg>
                        </div>
                        <input wire:model="form.password" 
                               id="password"
                               type="password" 
                               required 
                               autocomplete="current-password"
                               class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200 bg-gray-50/50 focus:bg-white"
                               placeholder="Enter your password">
                    </div>
                    @error('form.password') 
                        <p class="text-sm text-red-600 flex items-center mt-1">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            {{ $message }}
                        </p>
                    @enderror
                </div>
                
                <!-- Remember Me & Forgot Password -->
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input wire:model="form.remember" 
                               id="remember" 
                               type="checkbox" 
                               class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded transition-colors duration-200">
                        <label for="remember" class="ml-3 block text-sm text-gray-700 font-medium">
                            Remember me
                        </label>
                    </div>
                    
                    @if (Route::has('password.request'))
                        <div class="text-sm">
                            <a href="{{ route('password.request') }}" 
                               wire:navigate
                               class="font-semibold text-indigo-600 hover:text-indigo-500 transition-colors duration-200 hover:underline">
                                Forgot password?
                            </a>
                        </div>
                    @endif
                </div>
                
                <!-- Login Button -->
                <div class="space-y-4 flex flex-col items-center">
                    <button type="submit" 
                            class="inline-flex justify-center items-center py-3 px-6 border border-transparent rounded-xl shadow-lg text-sm font-semibold text-white bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transform hover:scale-[1.02] active:scale-[0.98] transition-all duration-200">
                        <svg class="w-5 h-5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                        </svg>
                        <span class="whitespace-nowrap">Sign In</span>
                    </button>
                    
                    <!-- System Admin Note -->
                    <div class="text-center pt-4 border-t border-gray-200">
                        <p class="text-xs text-gray-500">
                            Need access? Contact your System Administrator
                        </p>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Footer -->
        <div class="text-center">
            <p class="text-xs text-gray-500">
                © {{ date('Y') }} NumberSys. Secure enterprise document management.
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
