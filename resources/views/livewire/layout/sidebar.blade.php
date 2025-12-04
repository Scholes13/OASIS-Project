<!-- Modern Responsive Sidebar with Clean White Design -->
<div class="flex h-full flex-col overflow-hidden bg-white shadow-lg border-r border-gray-200"
     x-data="{ 
        expandedItems: {
            @foreach($navigationItems as $item)
                @if($item['current'] && count($item['children']) > 0)
                    '{{ $item['name'] }}': true,
                @endif
            @endforeach
        }
     }"
     wire:key="sidebar-{{ auth()->id() }}-{{ session('current_user_role') }}-{{ $currentRoute }}">
    
    <!-- Ultra-Compact Logo Header -->
    <div class="fluid-header border-b border-gray-200 fluid-spacing-xs"
         :class="sidebarMinimized ? 'justify-center' : ''">
        <div class="flex items-center minimal-gap">
            <!-- App Icon -->
            <div class="sidebar-logo-standard flex items-center justify-center minimal-border-radius bg-gradient-to-br from-blue-600 to-indigo-600 shadow-md">
                <svg class="text-white sidebar-icon-standard" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
            </div>
            <!-- App Name -->
            <div x-show="!sidebarMinimized" 
                 x-transition:enter="transition ease-out duration-200" 
                 x-transition:enter-start="opacity-0" 
                 x-transition:enter-end="opacity-100">
                <h1 class="sidebar-text-standard font-bold text-gray-900 tracking-tight">NumberSys</h1>
                <p class="fluid-text-xs text-gray-500">Document Management</p>
            </div>
        </div>
    </div>

    <!-- Minimal Spacer -->
    <div class="flex-shrink-0" style="padding: clamp(0.125rem, 0.3vw, 0.25rem);"></div>

    <!-- Ultra-Compact Navigation Menu -->
    <nav class="flex-1 overflow-y-auto fluid-spacing-xs" :class="sidebarMinimized ? 'px-1' : ''">
        <ul class="flex flex-col" style="gap: clamp(0.0625rem, 0.2vw, 0.125rem);">
            @foreach($navigationItems as $index => $item)
                <li>
                    @if(count($item['children']) > 0)
                        <!-- Ultra-Compact Expandable Menu Item -->
                        <div>
                            <button 
                                @click.stop="expandedItems['{{ $item['name'] }}'] = !expandedItems['{{ $item['name'] }}']"
                                class="sidebar-menu-item group font-medium smooth-transition
                                       {{ $item['current'] 
                                          ? 'sidebar-menu-active bg-blue-50/80 text-blue-600' 
                                          : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}"
                                :class="sidebarMinimized ? 'justify-center' : ''"
                                :title="sidebarMinimized ? '{{ $item['name'] }}' : ''"
                                type="button">
                                
                                <!-- Icon Container - UNIFIED STRUCTURE -->
                                <div class="sidebar-icon-container">
                                    @include('components.icons.' . $item['icon'], ['class' => 'sidebar-icon-standard'])
                                </div>
                                
                                <!-- Compact Label -->
                                <span x-show="!sidebarMinimized" 
                                      x-transition:enter="transition ease-out duration-200" 
                                      x-transition:enter-start="opacity-0" 
                                      x-transition:enter-end="opacity-100"
                                      class="flex-1 truncate sidebar-text-standard">{{ $item['name'] }}</span>
                                
                                <!-- Minimal Chevron -->
                                <svg x-show="!sidebarMinimized" 
                                     class="smooth-transition sidebar-chevron" 
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
                                 class="mt-1 space-y-0.5 pl-10">
                                @foreach($item['children'] as $child)
                                    <a href="{{ $child['href'] }}" 
                                       wire:navigate
                                       @click.stop
                                       class="group flex items-center gap-x-3 rounded-md px-3 py-2 transition-all duration-200
                                              {{ $child['current'] 
                                                 ? 'text-blue-600 font-medium' 
                                                 : 'text-gray-500 hover:text-gray-900' }}">
                                        
                                        <div class="h-1 w-1 rounded-full {{ $child['current'] ? 'bg-blue-500' : 'bg-gray-300' }}"></div>
                                        <span class="truncate sidebar-text-standard">{{ $child['name'] }}</span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <!-- Simple Menu Item -->
                        <a href="{{ $item['href'] }}" 
                           wire:navigate
                           @click.stop
                           class="sidebar-menu-item group font-medium smooth-transition
                                  {{ $item['current'] 
                                     ? 'sidebar-menu-active bg-blue-50/80 text-blue-600' 
                                     : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}"
                           :class="sidebarMinimized ? 'justify-center' : ''"
                           :title="sidebarMinimized ? '{{ $item['name'] }}' : ''"
                        >
                            <!-- Icon Container - UNIFIED STRUCTURE -->
                            <div class="sidebar-icon-container">
                                @include('components.icons.' . $item['icon'], ['class' => 'sidebar-icon-standard'])
                            </div>
                            
                            <!-- Label -->
                            <span x-show="!sidebarMinimized" 
                                  x-transition:enter="transition ease-out duration-200" 
                                  x-transition:enter-start="opacity-0" 
                                  x-transition:enter-end="opacity-100"
                                  class="flex-1 truncate sidebar-text-standard">{{ $item['name'] }}</span>
                        </a>
                    @endif
                </li>
            @endforeach
        </ul>
    </nav>

    <!-- Footer Spacer -->
    <div class="flex-shrink-0 p-2"></div>
</div>