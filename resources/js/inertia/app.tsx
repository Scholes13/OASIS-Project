import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { route } from 'ziggy-js';

// Make route globally available
declare global {
    function route(name: string, params?: Record<string, any>, absolute?: boolean): string;
}
(window as any).route = route;

// Create Inertia App
createInertiaApp({
    title: (title) => title ? `${title} - Activity` : 'Activity',
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.tsx`,
            import.meta.glob('./Pages/**/*.tsx')
        ),
    setup({ el, App, props }) {
        createRoot(el).render(<App {...props} />);
    },
    progress: {
        color: '#4F46E5', // Indigo-600
        showSpinner: true,
    },
});
