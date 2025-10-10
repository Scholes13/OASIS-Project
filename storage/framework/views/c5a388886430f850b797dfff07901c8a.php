<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>" class="h-full bg-gray-50">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
        <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">

        <title><?php echo e(isset($title) ? $title . ' - ' : ''); ?><?php echo e(config('app.name', 'NumberSys')); ?></title>
        <meta name="description" content="Enterprise Document Numbering & Approval System">

        <!-- Fonts - Preconnect for faster loading -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link rel="dns-prefetch" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- FontAwesome - Preload for critical icons -->
        <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
        <noscript><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" /></noscript>

        <!-- Scripts - Vite with automatic versioning -->
        <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
        <?php echo \Livewire\Mechanisms\FrontendAssets\FrontendAssets::styles(); ?>

        
        <!-- Dynamic script stack for lazy loading (Chart.js, etc) -->
        <?php echo $__env->yieldPushContent('scripts'); ?>
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
                        wire:key="mobile-sidebar-<?php echo e(auth()->id()); ?>-<?php echo e(session('current_user_role')); ?>">
                        <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('layout.sidebar', []);

$__html = app('livewire')->mount($__name, $__params, 'lw-2263126955-0', $__slots ?? [], get_defined_vars());

echo $__html;

unset($__html);
unset($__name);
unset($__params);
unset($__split);
if (isset($__slots)) unset($__slots);
?>
                    </div>
                </div>
            </div>

            <!-- Desktop sidebar -->
            <div 
                class="hidden lg:flex lg:flex-shrink-0 smooth-transition"
                :class="sidebarMinimized ? 'lg:w-16' : ''">
                <div class="fluid-sidebar" wire:key="desktop-sidebar-<?php echo e(auth()->id()); ?>-<?php echo e(session('current_user_role')); ?>">
                    <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('layout.sidebar', []);

$__html = app('livewire')->mount($__name, $__params, 'lw-2263126955-1', $__slots ?? [], get_defined_vars());

echo $__html;

unset($__html);
unset($__name);
unset($__params);
unset($__split);
if (isset($__slots)) unset($__slots);
?>
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
                            <?php if(isset($header)): ?>
                                <div class="w-full min-w-0">
                                    <?php if(isset($breadcrumbs)): ?>
                                        <nav class="flex" aria-label="Breadcrumb">
                                            <ol class="flex items-center space-x-2">
                                                <?php echo e($breadcrumbs); ?>

                                            </ol>
                                        </nav>
                                    <?php else: ?>
                                        <?php echo e($header); ?>

                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Right side of top nav -->
                        <div class="flex items-center gap-x-4 lg:gap-x-6">
                            <!-- Business Unit Switcher -->
                            <div wire:key="business-unit-switcher-<?php echo e(auth()->id()); ?>">
                                <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('components.business-unit-switcher', []);

$__html = app('livewire')->mount($__name, $__params, 'lw-2263126955-2', $__slots ?? [], get_defined_vars());

echo $__html;

unset($__html);
unset($__name);
unset($__params);
unset($__split);
if (isset($__slots)) unset($__slots);
?>
                            </div>
                            
                            <!-- Profile dropdown -->
                            <div wire:key="user-menu-<?php echo e(auth()->id()); ?>">
                                <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('layout.user-menu', []);

$__html = app('livewire')->mount($__name, $__params, 'lw-2263126955-3', $__slots ?? [], get_defined_vars());

echo $__html;

unset($__html);
unset($__name);
unset($__params);
unset($__split);
if (isset($__slots)) unset($__slots);
?>
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
                            <?php echo e($slot); ?>

                        </div>
                    </div>
                </main>
            </div>
        </div>
        
        <!-- Toast Notifications -->
        <?php if (isset($component)) { $__componentOriginalf98a32c06d8462f5513d0fb3554f9141 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf98a32c06d8462f5513d0fb3554f9141 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.toast-notification','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('toast-notification'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalf98a32c06d8462f5513d0fb3554f9141)): ?>
<?php $attributes = $__attributesOriginalf98a32c06d8462f5513d0fb3554f9141; ?>
<?php unset($__attributesOriginalf98a32c06d8462f5513d0fb3554f9141); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalf98a32c06d8462f5513d0fb3554f9141)): ?>
<?php $component = $__componentOriginalf98a32c06d8462f5513d0fb3554f9141; ?>
<?php unset($__componentOriginalf98a32c06d8462f5513d0fb3554f9141); ?>
<?php endif; ?>
        
        <?php echo \Livewire\Mechanisms\FrontendAssets\FrontendAssets::scripts(); ?>

        
        <!-- Additional Scripts -->
        <?php echo $__env->yieldPushContent('scripts'); ?>
        
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
                <?php if(session()->has('success')): ?>
                    notify('<?php echo e(session('success')); ?>', 'success');
                <?php endif; ?>
                
                <?php if(session()->has('error')): ?>
                    notify('<?php echo e(session('error')); ?>', 'error');
                <?php endif; ?>
                
                <?php if(session()->has('warning')): ?>
                    notify('<?php echo e(session('warning')); ?>', 'warning');
                <?php endif; ?>
                
                <?php if(session()->has('info')): ?>
                    notify('<?php echo e(session('info')); ?>', 'info');
                <?php endif; ?>
                
                <?php if($errors->any()): ?>
                    <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        notify('<?php echo e($error); ?>', 'error', 8000);
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                <?php endif; ?>
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
<?php /**PATH E:\Learning\WGProject\Numbering\resources\views/layouts/app.blade.php ENDPATH**/ ?>