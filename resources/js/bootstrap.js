import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Import Alpine.js
import Alpine from 'alpinejs';

// Make Alpine available globally
window.Alpine = Alpine;

// Configure Alpine to defer start until we're ready
Alpine.plugin((Alpine) => {
    // Add any Alpine plugins here if needed
});

// Start Alpine after DOM is loaded and Livewire is ready
document.addEventListener('DOMContentLoaded', function () {
    // Only start Alpine if it hasn't been started yet
    if (!Alpine.version) {
        Alpine.start();
    }
});

// Livewire compatibility
document.addEventListener('livewire:init', () => {
    console.log('Livewire initialized');
    
    // Handle navigation without Alpine.navigate conflicts
    Livewire.hook('morph.updated', ({ el, component }) => {
        // Re-initialize Alpine for new elements
        if (Alpine.initTree) {
            Alpine.initTree(el);
        }
    });
});

// Handle page navigation errors gracefully
window.addEventListener('error', function(e) {
    if (e.message && e.message.includes('Alpine.navigate')) {
        console.warn('Alpine.navigate error caught and handled:', e.message);
        e.preventDefault();
        return false;
    }
});
