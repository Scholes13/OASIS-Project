<!-- Modern Responsive Sidebar with Blue Gradient -->
<div class="flex h-full flex-col overflow-hidden bg-gradient-to-br from-blue-800 via-blue-900 to-slate-900 shadow-xl"
     x-data="{ 
        expandedItems: {
            @foreach($this->getNavigationItems() as $item)
                @if($item['current'] && count($item['children']) > 0)
                    '{{ $item['name'] }}': true,
                @endif
            @endforeach
        }
     }">
    
    <!-- Logo Header -->
    <div class="flex h-16 flex-shrink-0 items-center border-b border-blue-400/20 px-4 lg:px-6"
         :class="sidebarMinimized ? 'justify-center px-2' : ''">
        <div class="flex items-center space-x-3">
            <!-- App Icon -->
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-blue-400 to-indigo-500 shadow-lg ring-2 ring-white/20">
                <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
            </div>
            <!-- App Name -->
            <div x-show="!sidebarMinimized" 
                 x-transition:enter="transition ease-out duration-200" 
                 x-transition:enter-start="opacity-0" 
                 x-transition:enter-end="opacity-100">
                <h1 class="text-xl font-bold text-white tracking-tight">NumberSys</h1>
                <p class="text-xs text-blue-200 font-medium">Document Management</p>
            </div>
        </div>
    </div>

    <!-- Spacer between logo and navigation -->
    <div class="flex-shrink-0 py-4"></div>

    <!-- Navigation Menu -->
    <nav class="flex-1 overflow-y-auto px-4 lg:px-6 pb-4" :class="sidebarMinimized ? 'px-2' : ''">
        <ul class="space-y-2">
            @foreach($this->getNavigationItems() as $index => $item)
                <li>
                    @if(count($item['children']) > 0)
                        <!-- Expandable Menu Item -->
                        <div>
                            <button 
                                @click="expandedItems['{{ $item['name'] }}'] = !expandedItems['{{ $item['name'] }}']"
                                class="group flex w-full items-center gap-x-3 rounded-xl p-3 text-left text-sm font-medium transition-all duration-200 hover:scale-[1.02]
                                       {{ $item['current'] 
                                          ? 'bg-white/20 text-white shadow-lg ring-1 ring-white/20 backdrop-blur-sm' 
                                          : 'text-blue-200 hover:bg-white/10 hover:text-white' }}"
                                :class="sidebarMinimized ? 'justify-center' : ''"
                                :title="sidebarMinimized ? '{{ $item['name'] }}' : ''">
                                
                                <!-- Icon -->
                                <div class="flex h-6 w-6 shrink-0 items-center justify-center">
                                    @include('components.icons.' . $item['icon'], ['class' => 'h-5 w-5'])
                                </div>
                                
                                <!-- Label -->
                                <span x-show="!sidebarMinimized" 
                                      x-transition:enter="transition ease-out duration-200" 
                                      x-transition:enter-start="opacity-0" 
                                      x-transition:enter-end="opacity-100"
                                      class="flex-1 truncate">{{ $item['name'] }}</span>
                                
                                <!-- Chevron -->
                                <svg x-show="!sidebarMinimized" 
                                     class="h-4 w-4 transition-transform duration-200" 
                                     :class="expandedItems['{{ $item['name'] }}'] ? 'rotate-90' : ''"
                                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 18 6-6-6-6"></path>
                                </svg>
                            </button>

                            <!-- Submenu -->
                            <div x-show="!sidebarMinimized && expandedItems['{{ $item['name'] }}']" 
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="opacity-0 -translate-y-2"
                                 x-transition:enter-end="opacity-100 translate-y-0"
                                 x-transition:leave="transition ease-in duration-150"
                                 x-transition:leave-start="opacity-100 translate-y-0"
                                 x-transition:leave-end="opacity-0 -translate-y-2"
                                 class="mt-2 space-y-1 pl-9">
                                @foreach($item['children'] as $child)
                                    <a href="{{ $child['href'] }}" 
                                       wire:navigate
                                       class="group flex items-center gap-x-3 rounded-lg p-2 text-sm transition-all duration-200
                                              {{ $child['current'] 
                                                 ? 'bg-white/10 text-white font-medium ring-1 ring-white/20' 
                                                 : 'text-blue-200 hover:bg-white/5 hover:text-white' }}">
                                        
                                        <div class="h-1.5 w-1.5 rounded-full bg-current opacity-60"></div>
                                        <span class="truncate">{{ $child['name'] }}</span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <!-- Simple Menu Item -->
                        <a href="{{ $item['href'] }}" 
                           wire:navigate
                           class="group flex w-full items-center gap-x-3 rounded-xl p-3 text-sm font-medium transition-all duration-200 hover:scale-[1.02]
                                  {{ $item['current'] 
                                     ? 'bg-white/20 text-white shadow-lg ring-1 ring-white/20 backdrop-blur-sm' 
                                     : 'text-blue-200 hover:bg-white/10 hover:text-white' }}"
                           :class="sidebarMinimized ? 'justify-center' : ''"
                           :title="sidebarMinimized ? '{{ $item['name'] }}' : ''">
                            
                            <!-- Icon -->
                            <div class="flex h-6 w-6 shrink-0 items-center justify-center">
                                @include('components.icons.' . $item['icon'], ['class' => 'h-5 w-5'])
                            </div>
                            
                            <!-- Label -->
                            <span x-show="!sidebarMinimized" 
                                  x-transition:enter="transition ease-out duration-200" 
                                  x-transition:enter-start="opacity-0" 
                                  x-transition:enter-end="opacity-100"
                                  class="flex-1 truncate">{{ $item['name'] }}</span>
                        </a>
                    @endif
                </li>
            @endforeach
        </ul>
    </nav>

    <!-- Footer Spacer -->
    <div class="flex-shrink-0 p-2"></div>
</div>