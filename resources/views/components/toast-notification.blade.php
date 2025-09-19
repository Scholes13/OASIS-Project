<!-- Simple Toast Notification Container -->
<div id="toast-container" class="fixed top-4 right-4 z-50 space-y-2 w-96 max-w-full" style="z-index: 9999;">
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
        
        // Create toast element
        const toast = document.createElement('div');
        toast.id = toastId;
        toast.className = `transform transition-all duration-300 translate-x-full opacity-0 scale-95 pointer-events-auto relative overflow-hidden rounded-lg bg-white shadow-xl ring-1 ring-black ring-opacity-5 border-l-4 ${this.getBorderClass(type)}`;
        
        toast.innerHTML = `
            <div class="p-4">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <div class="flex items-center justify-center w-8 h-8 rounded-full ${this.getIconBgClass(type)}">
                            ${this.getIcon(type)}
                        </div>
                    </div>
                    <div class="ml-3 flex-1 pt-0.5">
                        <div class="text-sm font-medium text-gray-900">${message}</div>
                    </div>
                    <div class="ml-4 flex-shrink-0">
                        <button onclick="window.toastManager.remove('${toastId}')" class="inline-flex rounded-md p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                            <span class="sr-only">Dismiss</span>
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
            <div class="w-full bg-gray-200 h-1">
                <div class="h-1 rounded-br-lg transition-all ease-linear ${this.getProgressBarClass(type)}" style="width: 100%; animation: shrink ${duration}ms linear forwards;"></div>
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

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => window.toastManager.init());
} else {
    window.toastManager.init();
}

// Reinitialize after navigation
document.addEventListener('livewire:navigated', () => {
    setTimeout(() => window.toastManager.init(), 100);
});
</script>

<style>
@keyframes shrink {
    from { width: 100%; }
    to { width: 0%; }
}

/* Pause animation on hover */
#toast-container > div:hover .h-1 {
    animation-play-state: paused !important;
}
</style>