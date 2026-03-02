import axios from 'axios';
import { installBodySpacingGuard } from './inertia/lib/layoutFixes';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Livewire 3 already includes Alpine.js automatically
// No need to manually import or start Alpine

// Basic Livewire hooks for debugging
document.addEventListener('livewire:init', () => {
    console.log('Livewire initialized');
});

installBodySpacingGuard();
