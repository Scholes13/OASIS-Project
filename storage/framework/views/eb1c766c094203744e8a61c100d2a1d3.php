<!-- Modern Responsive Sidebar with Blue Gradient -->
<div class="flex h-full flex-col overflow-hidden bg-gradient-to-br from-blue-800 via-blue-900 to-slate-900 shadow-xl"
     x-data="{ 
        expandedItems: {
            <?php $__currentLoopData = $navigationItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php if($item['current'] && count($item['children']) > 0): ?>
                    '<?php echo e($item['name']); ?>': true,
                <?php endif; ?>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        }
     }"
     wire:key="sidebar-<?php echo e(auth()->id()); ?>-<?php echo e(session('current_user_role')); ?>-<?php echo e($currentRoute); ?>">
    
    <!-- Ultra-Compact Logo Header -->
    <div class="fluid-header border-b border-blue-400/20 fluid-spacing-xs"
         :class="sidebarMinimized ? 'justify-center' : ''">
        <div class="flex items-center minimal-gap">
            <!-- App Icon -->
            <div class="sidebar-logo-standard flex items-center justify-center minimal-border-radius bg-gradient-to-br from-blue-400 to-indigo-500 minimal-shadow ring-1 ring-white/20">
                <svg class="text-white sidebar-icon-standard" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
            </div>
            <!-- App Name -->
            <div x-show="!sidebarMinimized" 
                 x-transition:enter="transition ease-out duration-200" 
                 x-transition:enter-start="opacity-0" 
                 x-transition:enter-end="opacity-100">
                <h1 class="sidebar-text-standard font-bold text-white tracking-tight">NumberSys</h1>
                <p class="fluid-text-xs text-blue-200">Document Management</p>
            </div>
        </div>
    </div>

    <!-- Minimal Spacer -->
    <div class="flex-shrink-0" style="padding: clamp(0.125rem, 0.3vw, 0.25rem);"></div>

    <!-- Ultra-Compact Navigation Menu -->
    <nav class="flex-1 overflow-y-auto fluid-spacing-xs" :class="sidebarMinimized ? 'px-1' : ''">
        <ul class="flex flex-col" style="gap: clamp(0.0625rem, 0.2vw, 0.125rem);">
            <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $navigationItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <li>
                    <!--[if BLOCK]><![endif]--><?php if(count($item['children']) > 0): ?>
                        <!-- Ultra-Compact Expandable Menu Item -->
                        <div>
                            <button 
                                @click.stop="expandedItems['<?php echo e($item['name']); ?>'] = !expandedItems['<?php echo e($item['name']); ?>']"
                                class="sidebar-menu-item group minimal-border-radius font-medium smooth-transition
                                       <?php echo e($item['current'] 
                                          ? 'bg-white/20 text-white minimal-shadow ring-1 ring-white/20 backdrop-blur-sm' 
                                          : 'text-blue-200 hover:bg-white/10 hover:text-white'); ?>"
                                :class="sidebarMinimized ? 'justify-center' : ''"
                                :title="sidebarMinimized ? '<?php echo e($item['name']); ?>' : ''"
                                type="button">
                                
                                <!-- Icon Container - UNIFIED STRUCTURE -->
                                <div class="sidebar-icon-container">
                                    <?php echo $__env->make('components.icons.' . $item['icon'], ['class' => 'sidebar-icon-standard'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                                </div>
                                
                                <!-- Compact Label -->
                                <span x-show="!sidebarMinimized" 
                                      x-transition:enter="transition ease-out duration-200" 
                                      x-transition:enter-start="opacity-0" 
                                      x-transition:enter-end="opacity-100"
                                      class="flex-1 truncate sidebar-text-standard"><?php echo e($item['name']); ?></span>
                                
                                <!-- Minimal Chevron -->
                                <svg x-show="!sidebarMinimized" 
                                     class="smooth-transition sidebar-chevron" 
                                     :class="expandedItems['<?php echo e($item['name']); ?>'] ? 'rotate-90' : ''"
                                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 18 6-6-6-6"></path>
                                </svg>
                            </button>

                            <!-- Submenu -->
                            <div x-show="!sidebarMinimized && expandedItems['<?php echo e($item['name']); ?>']" 
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="opacity-0 -translate-y-2"
                                 x-transition:enter-end="opacity-100 translate-y-0"
                                 x-transition:leave="transition ease-in duration-150"
                                 x-transition:leave-start="opacity-100 translate-y-0"
                                 x-transition:leave-end="opacity-0 -translate-y-2"
                                 class="mt-1 space-y-0.5 pl-7">
                                <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $item['children']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $child): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <a href="<?php echo e($child['href']); ?>" 
                                       wire:navigate
                                       @click.stop
                                       class="group flex items-center gap-x-2 rounded-md px-2 py-1.5 transition-all duration-200
                                              <?php echo e($child['current'] 
                                                 ? 'bg-white/10 text-white font-medium ring-1 ring-white/20' 
                                                 : 'text-blue-200 hover:bg-white/5 hover:text-white'); ?>">
                                        
                                        <div class="h-1 w-1 rounded-full bg-current opacity-60"></div>
                                        <span class="truncate sidebar-text-standard"><?php echo e($child['name']); ?></span>
                                    </a>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Simple Menu Item -->
                        <a href="<?php echo e($item['href']); ?>" 
                           wire:navigate
                           @click.stop
                           class="sidebar-menu-item group minimal-border-radius font-medium smooth-transition
                                  <?php echo e($item['current'] 
                                     ? 'bg-white/20 text-white minimal-shadow ring-1 ring-white/20 backdrop-blur-sm' 
                                     : 'text-blue-200 hover:bg-white/10 hover:text-white'); ?>"
                           :class="sidebarMinimized ? 'justify-center' : ''"
                           :title="sidebarMinimized ? '<?php echo e($item['name']); ?>' : ''">
                            
                            <!-- Icon Container - UNIFIED STRUCTURE -->
                            <div class="sidebar-icon-container">
                                <?php echo $__env->make('components.icons.' . $item['icon'], ['class' => 'sidebar-icon-standard'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                            </div>
                            
                            <!-- Label -->
                            <span x-show="!sidebarMinimized" 
                                  x-transition:enter="transition ease-out duration-200" 
                                  x-transition:enter-start="opacity-0" 
                                  x-transition:enter-end="opacity-100"
                                  class="flex-1 truncate sidebar-text-standard"><?php echo e($item['name']); ?></span>
                        </a>
                    <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                </li>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->
        </ul>
    </nav>

    <!-- Footer Spacer -->
    <div class="flex-shrink-0 p-2"></div>
</div><?php /**PATH E:\Learning\WGProject\Numbering\resources\views/livewire/layout/sidebar.blade.php ENDPATH**/ ?>