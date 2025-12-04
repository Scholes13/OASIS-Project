<div class="relative" x-data="{ open: false, loggingOut: false }" x-on:click.away="open = false">
    <!-- Profile Button -->
    <button 
        type="button" 
        x-on:click.stop="open = !open"
        class="relative flex rounded-full bg-white text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-all duration-200 hover:ring-2 hover:ring-indigo-300 z-10">
        <span class="sr-only">Open user menu</span>
        <div class="h-8 w-8 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-md">
            <span class="text-sm font-bold text-white">
                {{ substr(Auth::user()->name, 0, 1) }}
            </span>
        </div>
    </button>

    <!-- Dropdown Menu -->
    <div x-show="open" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="transform opacity-0 scale-95"
         x-transition:enter-end="transform opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="transform opacity-100 scale-100"
         x-transition:leave-end="transform opacity-0 scale-95"
         class="absolute right-0 z-50 mt-2 w-72 origin-top-right rounded-lg bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
         style="display: none;">
         
        <!-- User Info Section -->
        <div class="px-4 py-3 border-b border-gray-200">
            <div class="flex items-center space-x-3">
                <div class="h-10 w-10 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-md">
                    <span class="text-sm font-bold text-white">
                        {{ substr(Auth::user()->name, 0, 1) }}
                    </span>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 truncate">
                        {{ Auth::user()->name }}
                    </p>
                    <p class="text-sm text-gray-500 truncate">
                        {{ Auth::user()->email }}
                    </p>
                    <div class="flex items-center space-x-1 mt-1">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-800">
                            {{ ucfirst(session('current_user_role', 'user')) }}
                        </span>
                        @if(session('current_business_unit_code'))
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                                {{ session('current_business_unit_code') }}
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Menu Items -->
        <div class="py-1">
            <a href="{{ route('profile') }}" 
               wire:navigate
               x-on:click="open = false"
               class="group flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 hover:text-indigo-600 transition-colors duration-200">
                <svg class="mr-3 h-4 w-4 text-gray-400 group-hover:text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
                Your Profile
            </a>
            
            <a href="https://request.werkudara.com" 
               target="_blank"
               x-on:click="open = false"
               class="group flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 hover:text-indigo-600 transition-colors duration-200">
                <svg class="mr-3 h-4 w-4 text-gray-400 group-hover:text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Help & Support
                <svg class="ml-1 h-3 w-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                </svg>
            </a>
        </div>
        
        <!-- Separator -->
        <div class="border-t border-gray-200"></div>
        
        <!-- Logout -->
        <div class="py-1">
            <form method="POST" action="{{ route('logout') }}" class="w-full" 
                  x-on:submit.prevent="
                      loggingOut = true; 
                      open = false;
                      
                      // Get user name
                      const userName = '{{ Auth::user()->name ?? 'User' }}';
                      
                      // Create clean logout overlay - text focused
                      const overlay = document.createElement('div');
                      overlay.id = 'logout-overlay';
                      overlay.style.cssText = 'position: fixed; inset: 0; z-index: 999999; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, rgba(249, 250, 251, 0.98) 0%, rgba(243, 244, 246, 0.98) 100%); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); opacity: 0; transition: opacity 0.4s ease;';
                      
                      overlay.innerHTML = `
                          <div style='text-align: center; animation: logoutCardAppear 0.5s ease-out forwards;'>
                              <!-- Main Text -->
                              <h3 style='font-size: 36px; font-weight: 800; color: #111827; margin: 0 0 16px 0; letter-spacing: -0.025em; animation: textFadeIn 0.6s ease-out forwards;'>See You Again!</h3>
                              <p style='font-size: 18px; color: #6b7280; margin: 0 0 8px 0; animation: textFadeIn 0.6s ease-out 0.1s forwards; opacity: 0;'>Have a nice day, <span style='font-weight: 700; color: #3b82f6;'>${userName}</span></p>
                              <p style='font-size: 15px; color: #9ca3af; margin: 0 0 32px 0; animation: textFadeIn 0.6s ease-out 0.2s forwards; opacity: 0;'>We will miss you! 💙</p>
                              
                              <!-- Progress Bar -->
                              <div style='width: 180px; height: 3px; background: #e5e7eb; border-radius: 2px; overflow: hidden; margin: 0 auto;'>
                                  <div style='height: 100%; background: linear-gradient(90deg, #3b82f6, #8b5cf6); animation: progressBar 1.5s ease-in-out infinite; border-radius: 2px;'></div>
                              </div>
                              
                              <p style='font-size: 13px; color: #b0b0b0; margin-top: 16px; animation: textFadeIn 0.6s ease-out 0.3s forwards; opacity: 0;'>Signing you out...</p>
                          </div>
                          
                          <style>
                              @keyframes logoutCardAppear { 
                                  0% { transform: scale(0.95); opacity: 0; } 
                                  100% { transform: scale(1); opacity: 1; } 
                              }
                              @keyframes textFadeIn { 
                                  0% { opacity: 0; transform: translateY(10px); } 
                                  100% { opacity: 1; transform: translateY(0); } 
                              }
                              @keyframes progressBar { 
                                  0% { width: 0%; }
                                  50% { width: 100%; }
                                  100% { width: 0%; }
                              }
                          </style>
                      `;
                      document.body.appendChild(overlay);
                      requestAnimationFrame(() => { overlay.style.opacity = '1'; });
                      
                      // Submit form after showing animation
                      setTimeout(() => { $el.submit(); }, 1500);
                  ">
                @csrf
                <button 
                    type="submit"
                    x-bind:disabled="loggingOut"
                    class="group flex w-full items-center px-4 py-2 text-sm text-red-700 hover:bg-red-50 hover:text-red-900 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed">
                    
                    <!-- Loading spinner (shown when logging out) -->
                    <svg x-show="loggingOut" 
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         class="mr-3 h-4 w-4 text-red-500 animate-spin" 
                         fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    
                    <!-- Normal logout icon (hidden when logging out) -->
                    <svg x-show="!loggingOut" 
                         class="mr-3 h-4 w-4 text-red-500 group-hover:text-red-700" 
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                    </svg>
                    
                    <!-- Text that changes based on state -->
                    <span x-show="!loggingOut">Sign Out</span>
                    <span x-show="loggingOut" 
                          x-transition:enter="transition ease-out duration-200"
                          x-transition:enter-start="opacity-0"
                          x-transition:enter-end="opacity-100">Signing Out...</span>
                </button>
            </form>
        </div>
    </div>
</div>