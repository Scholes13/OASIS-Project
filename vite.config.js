import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/inertia/app.tsx',
            ],
            refresh: true,
        }),
        react({
            // Enable React optimization for production
            jsxRuntime: 'automatic',
        }),
    ],
    resolve: {
        alias: {
            '@': '/resources/js/inertia',
        },
    },
    build: {
        // Disable source maps for production (smaller bundle size)
        sourcemap: false,
        
        // Optimize chunk size warnings
        chunkSizeWarningLimit: 1000,
        
        // Target modern browsers for smaller output
        target: 'es2020',
        
        // Enable CSS code splitting
        cssCodeSplit: true,
        
        // Optimize asset inlining threshold (10KB)
        assetsInlineLimit: 10240,
        
        // Enable reporting of compressed size (gzip)
        reportCompressedSize: true,
        
        rollupOptions: {
            output: {
                // Manual chunk splitting for optimal bundle sizes
                manualChunks: (id) => {
                    // Skip node_modules processing for non-vendor chunks
                    const isNodeModule = id.includes('node_modules');
                    
                    if (isNodeModule) {
                        // Core React libraries - loaded on every page
                        if (id.includes('node_modules/react') || 
                            id.includes('node_modules/react-dom') || 
                            id.includes('node_modules/scheduler')) {
                            return 'vendor-react';
                        }
                        
                        // Inertia (separate from React to avoid circular deps)
                        if (id.includes('node_modules/@inertiajs/react')) {
                            return 'vendor-inertia';
                        }
                        
                        // UI component libraries - shared across pages
                        if (id.includes('node_modules/@headlessui/react') ||
                            id.includes('node_modules/framer-motion') ||
                            id.includes('node_modules/lucide-react')) {
                            return 'vendor-ui';
                        }
                        
                        // Utility libraries - shared utilities
                        if (id.includes('node_modules/clsx') ||
                            id.includes('node_modules/tailwind-merge') ||
                            id.includes('node_modules/class-variance-authority') ||
                            id.includes('node_modules/date-fns')) {
                            return 'vendor-utils';
                        }
                        
                        // State management
                        if (id.includes('node_modules/zustand')) {
                            return 'vendor-state';
                        }
                        
                        // Charts and data visualization (only loaded when needed)
                        if (id.includes('node_modules/recharts') ||
                            id.includes('node_modules/@tanstack/react-table') ||
                            id.includes('node_modules/d3-') ||
                            id.includes('node_modules/victory-')) {
                            return 'vendor-charts';
                        }
                        
                        // Calendar library (only loaded when needed)
                        if (id.includes('node_modules/@fullcalendar')) {
                            return 'vendor-calendar';
                        }
                        
                        // Drag and drop (only loaded when needed)
                        if (id.includes('node_modules/@dnd-kit')) {
                            return 'vendor-dnd';
                        }
                        
                        // Toast notifications
                        if (id.includes('node_modules/sonner')) {
                            return 'vendor-toast';
                        }
                        
                        // Command palette
                        if (id.includes('node_modules/cmdk')) {
                            return 'vendor-cmdk';
                        }
                        
                        // All other node_modules go into vendor-other
                        // This prevents circular dependencies
                        return 'vendor-other';
                    }
                    
                    // Application code splitting (only for non-node_modules)
                    // Lib utilities first (to avoid circular deps with shared-ui)
                    if (id.includes('resources/js/inertia/lib')) {
                        return 'shared-lib';
                    }
                    
                    // Shared layout components - loaded on most pages
                    if (id.includes('resources/js/inertia/layouts') ||
                        id.includes('resources/js/inertia/components/layout')) {
                        return 'shared-layout';
                    }
                    
                    // Shared UI components - loaded on most pages
                    if (id.includes('resources/js/inertia/components/ui')) {
                        return 'shared-ui';
                    }
                    
                    // Error handling components
                    if (id.includes('resources/js/inertia/components/ErrorBoundary')) {
                        return 'shared-error';
                    }
                    
                    // Stores
                    if (id.includes('resources/js/inertia/stores')) {
                        return 'shared-stores';
                    }
                    
                    // Hooks
                    if (id.includes('resources/js/inertia/hooks')) {
                        return 'shared-hooks';
                    }
                    
                    // Module-specific components (loaded only when module is accessed)
                    if (id.includes('resources/js/inertia/components/purchasing')) {
                        return 'module-purchasing';
                    }
                    
                    if (id.includes('resources/js/inertia/components/activity')) {
                        return 'module-activity';
                    }
                    
                    if (id.includes('resources/js/inertia/components/admin')) {
                        return 'module-admin';
                    }
                    
                    // Pages are automatically split by Vite's dynamic import
                    // No need to manually chunk them here
                },
                
                // Optimize chunk file names for caching
                chunkFileNames: (chunkInfo) => {
                    return `js/[name]-[hash].js`;
                },
                
                // Optimize entry file names
                entryFileNames: 'js/[name]-[hash].js',
                
                // Optimize asset file names
                assetFileNames: (assetInfo) => {
                    const info = assetInfo.name.split('.');
                    const ext = info[info.length - 1];
                    if (/png|jpe?g|svg|gif|tiff|bmp|ico/i.test(ext)) {
                        return `images/[name]-[hash][extname]`;
                    } else if (/woff|woff2|eot|ttf|otf/i.test(ext)) {
                        return `fonts/[name]-[hash][extname]`;
                    }
                    return `assets/[name]-[hash][extname]`;
                },
            },
            
            // Optimize tree shaking
            treeshake: {
                moduleSideEffects: 'no-external',
                propertyReadSideEffects: false,
                tryCatchDeoptimization: false,
            },
        },
        
        // Use esbuild for minification (faster than terser)
        minify: 'esbuild',
        
        // Configure esbuild minification options
        esbuild: {
            // Drop console and debugger in production
            drop: process.env.NODE_ENV === 'production' ? ['console', 'debugger'] : [],
            // Optimize for size
            legalComments: 'none',
            // Target modern browsers
            target: 'es2020',
        },
    },
    
    // Optimize dependency pre-bundling
    optimizeDeps: {
        include: [
            'react',
            'react-dom',
            '@inertiajs/react',
            '@headlessui/react',
            'framer-motion',
            'lucide-react',
            'zustand',
            'date-fns',
            'clsx',
            'tailwind-merge',
        ],
        // Exclude large dependencies that should be code-split
        exclude: [
            '@fullcalendar/core',
            '@fullcalendar/react',
            'recharts',
            '@tanstack/react-table',
        ],
    },
    
    // Server configuration for development
    server: {
        // Enable CORS for development
        cors: true,
        // Optimize HMR
        hmr: {
            overlay: true,
        },
        // Watch options
        watch: {
            // Ignore node_modules for better performance
            ignored: ['**/node_modules/**', '**/storage/**', '**/vendor/**'],
        },
    },
    
    // Preview server configuration
    preview: {
        port: 5173,
        strictPort: true,
    },
});
