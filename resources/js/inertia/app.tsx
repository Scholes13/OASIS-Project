import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout';
import { ErrorBoundary } from '@/components/ErrorBoundary';
import { initializeErrorHandlers } from '@/lib/errorHandlers';
import { initializeErrorLogger } from '@/lib/errorLogger';
import type { ReactNode } from 'react';

// Make route globally available
declare global {
    function route(name: string, params?: Record<string, any>, absolute?: boolean): string;
}
(window as any).route = route;

// Initialize global error handlers
initializeErrorHandlers();

// Initialize error logger
initializeErrorLogger();

// Type for page components with optional layout
interface PageComponent {
    default: React.ComponentType & {
        layout?: (page: ReactNode) => ReactNode;
    };
}

// Create Inertia App with persistent layout and lazy loading
createInertiaApp({
    title: (title) => title ? `${title} - Oasis` : 'Oasis',
    resolve: async (name) => {
        // Lazy load page components using dynamic imports
        // This creates separate chunks for each page, loaded only when needed
        const pages = import.meta.glob('./Pages/**/*.tsx');
        
        // Construct the page path
        const pagePath = `./Pages/${name}.tsx`;
        
        // Check if the page exists
        if (!pages[pagePath]) {
            throw new Error(`Page not found: ${name}`);
        }
        
        // Dynamically import the page component (lazy loading)
        const page = await pages[pagePath]() as PageComponent;

        // Apply default AppLayout if page doesn't specify a custom layout
        // Pages can opt-out by setting: PageComponent.layout = (page) => page
        // Or use a custom layout: PageComponent.layout = (page) => <CustomLayout>{page}</CustomLayout>
        if (page.default.layout === undefined) {
            page.default.layout = (page: ReactNode) => <AppLayout>{page}</AppLayout>;
        }

        return page;
    },
    setup({ el, App, props }) {
        createRoot(el).render(
            <ErrorBoundary>
                <App {...props} />
            </ErrorBoundary>
        );
    },
    progress: {
        // Progress bar color (Indigo-600 to match primary brand color)
        color: '#4F46E5',
        
        // Show spinner in top-right corner during navigation
        showSpinner: true,
        
        // Delay before showing progress bar (250ms)
        // This prevents the bar from flashing on fast requests
        delay: 250,
        
        // Include default styles for the progress bar
        includeCSS: true,
        
        // Custom CSS for progress bar positioning and styling
        // The progress bar appears at the very top of the viewport (fixed position)
    },
});
