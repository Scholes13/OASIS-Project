<!-- Modern Sidebar with Aesthetic Treeline Design -->
<div class="flex h-full flex-col bg-white overflow-visible"
     x-data="{ 
        expandedItems: {
            @foreach($navigationItems as $item)
                @if($item['current'] && count($item['children']) > 0)
                    '{{ $item['name'] }}': true,
                @endif
            @endforeach
        },
        flyoutOpen: null,
        flyoutPosition: { top: 0, left: 0 },
        flyoutName: '',
        flyoutChildren: [],
        flyoutTimeout: null,
        openFlyout(name, el, children) {
            if (typeof sidebarMinimized === 'undefined' || !sidebarMinimized) return;
            clearTimeout(this.flyoutTimeout);
            const rect = el.getBoundingClientRect();
            this.flyoutPosition = { 
                top: rect.top, 
                left: rect.right + 8 
            };
            this.flyoutName = name;
            this.flyoutChildren = children;
            this.flyoutOpen = true;
        },
        closeFlyoutDelayed() {
            this.flyoutTimeout = setTimeout(() => {
                this.flyoutOpen = false;
                this.flyoutName = '';
                this.flyoutChildren = [];
            }, 100);
        },
        keepFlyoutOpen() {
            clearTimeout(this.flyoutTimeout);
        }
     }"
     wire:key="sidebar-{{ auth()->id() }}-{{ session('current_user_role') }}-{{ $currentRoute }}">
    
    <!-- Single Flyout Container (teleported to body) -->
    <template x-teleport="body">
        <div x-show="sidebarMinimized && flyoutOpen"
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 translate-x-1"
             x-transition:enter-end="opacity-100 translate-x-0"
             x-transition:leave="transition ease-in duration-100"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @mouseenter="keepFlyoutOpen()"
             @mouseleave="closeFlyoutDelayed()"
             class="fixed z-[9999] min-w-[200px]"
             :style="`left: ${flyoutPosition.left}px; top: ${flyoutPosition.top}px;`">
            
            <!-- Flyout Card -->
            <div class="bg-white rounded-xl shadow-xl border border-gray-200 overflow-hidden">
                <!-- Header with parent name -->
                <div class="px-4 py-2.5 bg-gradient-to-r from-indigo-50 to-white border-b border-gray-100">
                    <span class="text-sm font-semibold text-indigo-700" x-text="flyoutName"></span>
                </div>
                
                <!-- Submenu items -->
                <div class="py-2">
                    <template x-for="(child, idx) in flyoutChildren" :key="idx">
                        <a :href="child.href" 
                           class="flex items-center px-4 py-2 text-sm transition-colors duration-150 hover:bg-gray-50"
                           :class="child.current ? 'text-indigo-600 bg-indigo-50/50 font-medium' : 'text-gray-600 hover:text-gray-900'"
                           @click.stop>
                            <!-- Dot indicator -->
                            <span class="w-1.5 h-1.5 rounded-full mr-3 flex-shrink-0"
                                  :class="child.current ? 'bg-indigo-500' : 'bg-gray-300'"></span>
                            <span x-text="child.name"></span>
                        </a>
                    </template>
                </div>
            </div>
        </div>
    </template>
    
    <!-- Logo Header -->
    <div class="flex items-center px-5 py-4 border-b border-gray-100"
         :class="sidebarMinimized ? 'justify-center px-2' : ''">
        <div class="flex items-center gap-3">
            <!-- App Icon -->
            <div class="w-9 h-9 flex items-center justify-center rounded-xl bg-gradient-to-br from-indigo-500 to-indigo-600 shadow-sm flex-shrink-0">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
            </div>
            <!-- App Name -->
            <div x-show="!sidebarMinimized" x-transition.opacity.duration.200ms class="sidebar-app-name">
                <h1 class="text-base font-semibold text-gray-900">Oasis</h1>
                <p class="text-xs text-gray-400">Office Administration</p>
            </div>
        </div>
    </div>

    <!-- Navigation Menu -->
    <nav class="flex-1 overflow-y-auto overflow-x-visible px-3 py-4" :class="sidebarMinimized ? 'px-3 overflow-visible' : ''">
        <ul class="space-y-1">
            @foreach($navigationItems as $index => $item)
                <li class="relative">
                    @if(count($item['children']) > 0)
                        @php
                            $hasActiveChild = collect($item['children'])->contains('current', true);
                        @endphp
                        <!-- Expandable Menu Item with Treeline -->
                        <div class="relative"
                             @mouseenter="sidebarMinimized && openFlyout('{{ $item['name'] }}', $event.currentTarget, {{ json_encode($item['children']) }})"
                             @mouseleave="sidebarMinimized && closeFlyoutDelayed()">
                            
                            <!-- Parent Button -->
                            <button 
                                x-ref="parent_{{ $index }}"
                                @click.stop="!sidebarMinimized && (expandedItems['{{ $item['name'] }}'] = !expandedItems['{{ $item['name'] }}'])"
                                class="sidebar-menu-item w-full flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200
                                       {{ $item['current'] 
                                          ? 'bg-gray-50 text-indigo-600' 
                                          : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}"
                                :class="sidebarMinimized ? 'justify-center px-2' : ''"
                                :title="sidebarMinimized ? '{{ $item['name'] }}' : ''"
                                type="button">
                                
                                <!-- Icon -->
                                <div class="w-5 h-5 flex-shrink-0 flex items-center justify-center">
                                    @include('components.icons.' . $item['icon'], ['class' => 'w-5 h-5'])
                                </div>
                                
                                <!-- Label -->
                                <span x-show="!sidebarMinimized" x-transition.opacity.duration.200ms
                                      class="sidebar-label flex-1 text-left text-sm font-medium truncate">{{ $item['name'] }}</span>
                                
                                <!-- Chevron - hidden when minimized -->
                                <svg x-show="!sidebarMinimized" 
                                     x-cloak
                                     class="sidebar-chevron w-4 h-4 text-gray-400 transition-transform duration-200 flex-shrink-0" 
                                     :class="expandedItems['{{ $item['name'] }}'] ? 'rotate-180' : ''"
                                     fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m19 9-7 7-7-7"></path>
                                </svg>
                            </button>

                            <!-- Submenu with Treeline (Expanded Mode) -->
                            <div x-show="!sidebarMinimized && expandedItems['{{ $item['name'] }}']" 
                                 x-cloak
                                 class="sidebar-treeline"
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="opacity-0 -translate-y-1"
                                 x-transition:enter-end="opacity-100 translate-y-0"
                                 x-transition:leave="transition ease-in duration-150"
                                 x-transition:leave-start="opacity-100"
                                 x-transition:leave-end="opacity-0"
                                 style="margin-left: 26px; margin-top: 4px;">
                                
                                @foreach($item['children'] as $childIndex => $child)
                                    @php $isLast = $loop->last; @endphp
                                    <a href="{{ $child['href'] }}" 
                                       wire:navigate
                                       @click.stop
                                       style="display: flex; align-items: center; position: relative; height: 36px; padding-left: 20px; text-decoration: none; color: {{ $child['current'] ? '#4f46e5' : '#6b7280' }};">
                                        
                                        {{-- Vertical Line - stops before curve for last item --}}
                                        <span style="position: absolute; left: 0; top: 0; {{ $isLast ? 'height: 10px;' : 'height: 100%;' }} width: 1.5px; background-color: #d1d5db;"></span>
                                        
                                        {{-- Smooth curved corner using arc --}}
                                        <svg style="position: absolute; left: 0; top: 10px; width: 16px; height: 9px;" viewBox="0 0 16 9" fill="none">
                                            <path d="M0.75 0 L0.75 0.5 A7.5 7.5 0 0 0 8.25 8 L16 8" stroke="#d1d5db" stroke-width="1.5" fill="none" stroke-linecap="round"/>
                                        </svg>
                                        
                                        {{-- Child Label --}}
                                        @if($child['current'])
                                            <span style="font-size: 0.875rem; line-height: 1; font-weight: 500; background-color: #eef2ff; color: #4338ca; padding: 6px 12px; border-radius: 6px;">
                                                {{ $child['name'] }}
                                            </span>
                                        @else
                                            <span style="font-size: 0.875rem; line-height: 1; font-weight: 400;">
                                                {{ $child['name'] }}
                                            </span>
                                        @endif
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <!-- Simple Menu Item (No Children) -->
                        <a href="{{ $item['href'] }}" 
                           wire:navigate
                           @click.stop
                           class="sidebar-menu-item flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200
                                  {{ $item['current'] 
                                     ? 'bg-indigo-50 text-indigo-600' 
                                     : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}"
                           :class="sidebarMinimized ? 'justify-center px-2' : ''"
                           :title="sidebarMinimized ? '{{ $item['name'] }}' : ''">
                            
                            <!-- Icon -->
                            <div class="w-5 h-5 flex-shrink-0 flex items-center justify-center">
                                @include('components.icons.' . $item['icon'], ['class' => 'w-5 h-5'])
                            </div>
                            
                            <!-- Label -->
                            <span x-show="!sidebarMinimized" x-transition.opacity.duration.200ms
                                  class="sidebar-label text-sm font-medium truncate">{{ $item['name'] }}</span>
                        </a>
                    @endif
                </li>
            @endforeach
        </ul>
    </nav>

    <!-- Footer -->
    <div class="flex-shrink-0 p-3"></div>
</div>