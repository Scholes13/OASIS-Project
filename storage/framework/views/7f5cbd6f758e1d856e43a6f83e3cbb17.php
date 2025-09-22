<!-- Slim Toast Notification Container -->
<div id="toast-container" class="fixed top-4 right-4 z-50 space-y-1 w-80 max-w-full" style="z-index: 9999;">
    <!-- Toasts will be dynamically added here -->
</div>

<script>
// Simple global toast system
window.toastManager = {
    container: null,
    lastToastTime: 0,
    lastToastMessage: '',
    
    init() {
        this.container = document.getElementById('toast-container');
        
        if (!this.container) {
            console.error('Toast container not found - creating fallback');
            // Create fallback container if not found
            this.createFallbackContainer();
        }
        
        // Listen for Livewire events
        document.addEventListener('livewire:init', () => {
            if (typeof Livewire !== 'undefined') {
                Livewire.on('notify', (data) => {
                    console.log('Livewire notify received:', data);
                    this.show(data.message, data.type, data.duration);
                });
            }
        });
        
        // Listen for custom events
        window.addEventListener('show-toast', (e) => {
            console.log('Custom toast event:', e.detail);
            this.show(e.detail.message, e.detail.type, e.detail.duration);
        });
    },
    
    createFallbackContainer() {
        const container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'fixed top-4 right-4 z-50 space-y-1 w-80 max-w-full';
        container.style.zIndex = '9999';
        document.body.appendChild(container);
        this.container = container;
        console.log('Fallback toast container created');
    },
    
    show(message, type = 'info', duration = 5000) {
        if (!this.container) {
            console.error('Toast container not found');
            return;
        }
        
        // Debounce - prevent rapid duplicate toasts
        const now = Date.now();
        if (now - this.lastToastTime < 100 && this.lastToastMessage === message) {
            console.log('Duplicate toast prevented by debounce');
            return;
        }
        this.lastToastTime = now;
        this.lastToastMessage = message;
        
        console.log('Showing toast:', { message, type, duration });
        
        // Enhanced duplicate prevention - check for existing similar messages
        const existingToasts = this.container.querySelectorAll('[id^="toast-"]');
        for (let toast of existingToasts) {
            const existingMessage = toast.querySelector('.text-sm').innerHTML;
            if (existingMessage === message) {
                console.log('Duplicate toast prevented - identical message found');
                return;
            }
        }
        
        const toastId = 'toast-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
        
        // Create toast element with slim design
        const toast = document.createElement('div');
        toast.id = toastId;
        toast.className = `transform transition-all duration-300 translate-x-full opacity-0 scale-95 pointer-events-auto relative overflow-hidden rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 border-l-4 ${this.getBorderClass(type)} mb-2`;
        
        toast.innerHTML = `
            <div class="px-3 py-2 flex items-center">
                <div class="flex-shrink-0 mr-2">
                    <div class="flex items-center justify-center w-5 h-5 rounded-full ${this.getIconBgClass(type)}">
                        ${this.getSlimIcon(type)}
                    </div>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="text-sm font-medium text-gray-900 truncate">${message}</div>
                </div>
                <div class="ml-2 flex-shrink-0">
                    <button onclick="window.toastManager.remove('${toastId}')" class="inline-flex rounded-md p-1 text-gray-400 hover:text-gray-600 hover:bg-gray-100 focus:outline-none focus:ring-1 focus:ring-gray-300 transition-colors">
                        <span class="sr-only">Dismiss</span>
                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
            <div class="w-full bg-gray-200 h-0.5">
                <div class="h-0.5 transition-all ease-linear ${this.getProgressBarClass(type)}" style="width: 100%; animation: shrink ${duration}ms linear forwards;"></div>
            </div>
        `;
        
        // Add to container
        this.container.appendChild(toast);
        
        // Trigger enter animation
        setTimeout(() => {
            toast.className = toast.className.replace('translate-x-full opacity-0 scale-95', 'translate-x-0 opacity-100 scale-100');
        }, 10);
        
        // Auto remove
        setTimeout(() => {
            this.remove(toastId);
        }, duration);
    },
    
    remove(toastId) {
        const toast = document.getElementById(toastId);
        if (!toast) return;
        
        // Trigger exit animation
        toast.className = toast.className.replace('translate-x-0 opacity-100 scale-100', 'translate-x-full opacity-0 scale-95');
        
        // Remove from DOM after animation
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 300);
    },
    
    getBorderClass(type) {
        const classes = {
            'success': 'border-l-green-600',
            'error': 'border-l-red-600',
            'warning': 'border-l-yellow-600',
            'info': 'border-l-blue-600'
        };
        return classes[type] || classes.info;
    },
    
    getIconBgClass(type) {
        const classes = {
            'success': 'bg-green-100',
            'error': 'bg-red-100',
            'warning': 'bg-yellow-100',
            'info': 'bg-blue-100'
        };
        return classes[type] || classes.info;
    },
    
    getProgressBarClass(type) {
        const classes = {
            'success': 'bg-green-600',
            'error': 'bg-red-600',
            'warning': 'bg-yellow-600',
            'info': 'bg-blue-600'
        };
        return classes[type] || classes.info;
    },
    
    getSlimIcon(type) {
        const icons = {
            'success': '<svg class="h-3 w-3 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>',
            'error': '<svg class="h-3 w-3 text-red-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>',
            'warning': '<svg class="h-3 w-3 text-yellow-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v4a1 1 0 102 0V5zm0 8a1 1 0 10-2 0 1 1 0 002 0z" clip-rule="evenodd"/></svg>',
            'info': '<svg class="h-3 w-3 text-blue-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>'
        };
        return icons[type] || icons.info;
    },

    getIcon(type) {
        const icons = {
            'success': '<svg class="h-5 w-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>',
            'error': '<svg class="h-5 w-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>',
            'warning': '<svg class="h-5 w-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path></svg>',
            'info': '<svg class="h-5 w-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>'
        };
        return icons[type] || icons.info;
    }
};

// Enhanced initialization with retry mechanism
function initializeToastManager() {
    try {
        if (window.toastManager) {
            window.toastManager.init();
            console.log('Toast manager initialized successfully');
        }
    } catch (error) {
        console.error('Toast manager initialization failed:', error);
        // Retry after a short delay
        setTimeout(initializeToastManager, 100);
    }
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeToastManager);
} else {
    initializeToastManager();
}

// Reinitialize after navigation with delay to ensure DOM is ready
document.addEventListener('livewire:navigated', () => {
    setTimeout(initializeToastManager, 150);
});
</script>

<style>
@keyframes shrink {
    from { width: 100%; }
    to { width: 0%; }
}

/* Pause animation on hover */
#toast-container > div:hover .h-0.5 {
    animation-play-state: paused !important;
}

/* Mobile responsive */
@media (max-width: 640px) {
    #toast-container {
        top: 15px !important;
        right: 15px !important;
        left: 15px !important;
        width: calc(100vw - 30px) !important;
        max-width: none !important;
    }
    
    #toast-container > div {
        margin-bottom: 8px !important;
    }
}
</style><?php /**PATH E:\Learning\WGProject\Numbering\resources\views/components/toast-notification.blade.php ENDPATH**/ ?>