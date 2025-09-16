<!-- Toast Notification Container -->
<div 
    x-data="{ 
        notifications: [],
        show(message, type = 'info', duration = 5000) {
            const id = Date.now();
            this.notifications.push({ id, message, type });
            
            setTimeout(() => {
                this.removeNotification(id);
            }, duration);
        },
        removeNotification(id) {
            this.notifications = this.notifications.filter(n => n.id !== id);
        }
    }"
    @notify.window="show($event.detail.message, $event.detail.type || 'info', $event.detail.duration || 5000)"
    class="fixed top-4 right-4 z-50 space-y-2 w-96 max-w-full pointer-events-none">
    
    <template x-for="notification in notifications" :key="notification.id">
        <div 
            x-show="true"
            x-transition:enter="transition-all duration-300 transform"
            x-transition:enter-start="translate-x-full opacity-0"
            x-transition:enter-end="translate-x-0 opacity-100"
            x-transition:leave="transition-all duration-300 transform"
            x-transition:leave-start="translate-x-0 opacity-100"
            x-transition:leave-end="translate-x-full opacity-0"
            class="pointer-events-auto relative overflow-hidden rounded-lg shadow-lg ring-1 ring-black ring-opacity-5"
            :class="{
                'bg-white border-l-4 border-l-green-400': notification.type === 'success',
                'bg-white border-l-4 border-l-red-400': notification.type === 'error',
                'bg-white border-l-4 border-l-yellow-400': notification.type === 'warning',
                'bg-white border-l-4 border-l-blue-400': notification.type === 'info'
            }">
            
            <div class="p-4">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <!-- Success Icon -->
                        <template x-if="notification.type === 'success'">
                            <svg class="h-5 w-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </template>
                        
                        <!-- Error Icon -->
                        <template x-if="notification.type === 'error'">
                            <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </template>
                        
                        <!-- Warning Icon -->
                        <template x-if="notification.type === 'warning'">
                            <svg class="h-5 w-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                            </svg>
                        </template>
                        
                        <!-- Info Icon -->
                        <template x-if="notification.type === 'info'">
                            <svg class="h-5 w-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </template>
                    </div>
                    
                    <div class="ml-3 flex-1">
                        <p class="text-sm font-medium"
                           :class="{
                               'text-green-800': notification.type === 'success',
                               'text-red-800': notification.type === 'error',
                               'text-yellow-800': notification.type === 'warning',
                               'text-blue-800': notification.type === 'info'
                           }"
                           x-text="notification.message">
                        </p>
                    </div>
                    
                    <div class="ml-4 flex-shrink-0">
                        <button
                            @click="removeNotification(notification.id)"
                            class="inline-flex rounded-md p-1.5 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                            :class="{
                                'text-green-500 hover:bg-green-50 focus:ring-green-500': notification.type === 'success',
                                'text-red-500 hover:bg-red-50 focus:ring-red-500': notification.type === 'error',
                                'text-yellow-500 hover:bg-yellow-50 focus:ring-yellow-500': notification.type === 'warning',
                                'text-blue-500 hover:bg-blue-50 focus:ring-blue-500': notification.type === 'info'
                            }">
                            <span class="sr-only">Dismiss</span>
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>