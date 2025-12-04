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

<div class="min-h-screen flex items-center justify-center relative"
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
     x-on:livewire:load="hideOverlay()"
     style="background: linear-gradient(135deg, #e8d5c4 0%, #f0e6dc 15%, #d4e5ed 35%, #c8dde8 50%, #d8e8d4 65%, #e5edd8 80%, #f0f5e8 100%);">

    <!-- Centered Login Card -->
    <div class="w-full max-w-md mx-4">
        <div class="bg-white rounded-lg shadow-lg p-8 sm:p-10">
            
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-2xl font-semibold text-gray-900 mb-1">
                    Sign in
                </h1>
                <p class="text-sm text-gray-500">
                    to continue to OASIS
                </p>
            </div>
            
            <!-- Session Status -->
            @if (session('status'))
                <div class="mb-6 bg-green-50 border-l-4 border-green-400 p-4">
                    <p class="text-green-700 text-sm">{{ session('status') }}</p>
                </div>
            @endif
            
            <!-- Error Messages -->
            @if (session('error'))
                <div class="mb-6 bg-red-50 border-l-4 border-red-400 p-4">
                    <p class="text-red-700 text-sm">{{ session('error') }}</p>
                </div>
            @endif

            <form wire:submit="login" class="space-y-6" 
              x-data="{ 
                  showOverlay() {
                      loggingIn = true;
                      
                      const overlay = document.createElement('div');
                      overlay.id = 'login-overlay';
                      overlay.style.cssText = 'position: fixed; inset: 0; z-index: 99999; display: flex; align-items: center; justify-content: center; background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(8px); opacity: 0; transition: opacity 0.3s ease;';
                      
                      overlay.innerHTML = `
                          <div style='text-align: center; animation: fadeIn 0.3s ease forwards;'>
                              <div style='width: 72px; height: 72px; margin: 0 auto 24px; animation: float 2s ease-in-out infinite;'>
                                  <img src='{{ asset("storage/business-units/aDNLhQNtI0R0KiPTv6oFWC2NwLu11tewYoJwjhTg.png") }}' alt='Werkudara Logo' style='width: 100%; height: 100%; object-fit: contain;'>
                              </div>
                              <div style='position: relative; width: 40px; height: 40px; margin: 0 auto 16px;'>
                                  <div style='position: absolute; inset: 0; border: 3px solid #e5e7eb; border-radius: 50%;'></div>
                                  <div style='position: absolute; inset: 0; border: 3px solid #2b4892; border-radius: 50%; border-top-color: transparent; animation: spin 1s linear infinite;'></div>
                              </div>
                              <p style='font-size: 14px; color: #666; margin: 0;'>Signing you in...</p>
                          </div>
                          <style>
                              @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
                              @keyframes fadeIn { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }
                              @keyframes float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-10px); } }
                          </style>
                      `;
                      document.body.appendChild(overlay);
                      requestAnimationFrame(() => { overlay.style.opacity = '1'; });
                  },
                  hideOverlay() {
                      loggingIn = false;
                      const overlay = document.getElementById('login-overlay');
                      if (overlay) {
                          overlay.style.opacity = '0';
                          setTimeout(() => overlay.remove(), 300);
                      }
                  }
              }"
              x-on:submit="showOverlay()">
                
                <!-- Email Input -->
                <div>
                    <input wire:model="form.email" 
                           id="email"
                           type="email" 
                           required 
                           autofocus
                           autocomplete="username"
                           class="w-full px-0 py-2 text-base border-0 border-b-2 border-gray-300 focus:ring-0 bg-transparent transition-colors duration-200 placeholder-gray-400"
                           style="--tw-border-opacity: 1;"
                           onfocus="this.style.borderColor='#2b4892'" 
                           onblur="this.style.borderColor='#d1d5db'"
                           placeholder="Email address">
                    @error('form.email') 
                        <p class="text-xs text-red-600 mt-2">{{ $message }}</p>
                    @enderror
                </div>
                
                <!-- Password Input -->
                <div>
                    <input wire:model="form.password" 
                           id="password"
                           type="password" 
                           required 
                           autocomplete="current-password"
                           class="w-full px-0 py-2 text-base border-0 border-b-2 border-gray-300 focus:ring-0 bg-transparent transition-colors duration-200 placeholder-gray-400"
                           onfocus="this.style.borderColor='#2b4892'" 
                           onblur="this.style.borderColor='#d1d5db'"
                           placeholder="Password">
                    @error('form.password') 
                        <p class="text-xs text-red-600 mt-2">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Forgot Password -->
                @if (Route::has('password.request'))
                    <div class="text-left">
                        <a href="{{ route('password.request') }}" 
                           wire:navigate
                           class="text-sm hover:underline transition-colors duration-200"
                           style="color: #2b4892;">
                            Forgot password?
                        </a>
                    </div>
                @endif
                
                <!-- Login Button -->
                <div class="pt-2">
                    <button type="submit" 
                            class="w-full py-2.5 px-6 rounded text-sm font-medium text-white focus:outline-none focus:ring-2 focus:ring-offset-2 transition-colors duration-200"
                            style="background-color: #2b4892;"
                            onmouseover="this.style.backgroundColor='#1e3a7a'" 
                            onmouseout="this.style.backgroundColor='#2b4892'">
                        Sign in
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Footer -->
        <div class="mt-6 text-center">
            <p class="text-xs text-gray-500">
                © {{ date('Y') }} Werkudara Group
            </p>
        </div>
    </div>
</div>

<script>
document.addEventListener('livewire:init', () => {
    const smoothHideOverlay = () => {
        const overlay = document.getElementById('login-overlay');
        if (overlay) {
            overlay.style.opacity = '0';
            setTimeout(() => overlay.remove(), 300);
        }
    };
    
    Livewire.on('hideOverlay', () => { smoothHideOverlay(); });
    Livewire.hook('morph.updated', ({ component }) => {
        if (component.name === 'pages.auth.login') { smoothHideOverlay(); }
    });
    Livewire.hook('request.exception', () => { smoothHideOverlay(); });
});
</script>
