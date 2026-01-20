import { router } from '@inertiajs/react';
import { useCallback, useRef } from 'react';

/**
 * Prefetch Configuration
 * 
 * Controls the behavior of link prefetching to optimize performance
 * and reduce unnecessary network requests.
 */
interface PrefetchConfig {
    /**
     * Delay in milliseconds before prefetching starts after hover
     * Default: 100ms
     * 
     * This prevents prefetching when users quickly move their cursor
     * across multiple links, reducing unnecessary requests.
     */
    delay?: number;

    /**
     * Whether to prefetch only specific data keys
     * Default: undefined (prefetch all data)
     * 
     * Example: ['purchaseRequest', 'items'] to only prefetch specific props
     */
    only?: string[];
}

/**
 * usePrefetch Hook
 * 
 * Provides prefetching functionality for Inertia links to improve
 * perceived performance by loading page data before navigation.
 * 
 * Strategy:
 * - Uses native browser fetch API to prefetch page data
 * - Stores response in browser cache for instant loading
 * - Inertia will use cached response when user actually navigates
 * 
 * Features:
 * - Hover-based prefetching with configurable delay
 * - Automatic cancellation if hover ends before delay
 * - Prevents duplicate prefetch requests
 * - Respects Inertia headers for proper data fetching
 * 
 * @param config - Prefetch configuration options
 * @returns Object with prefetch handlers for onMouseEnter and onMouseLeave
 * 
 * @example
 * ```tsx
 * const { onMouseEnter, onMouseLeave } = usePrefetch({ delay: 100 });
 * 
 * <Link 
 *   href="/purchase-requests/123"
 *   onMouseEnter={onMouseEnter}
 *   onMouseLeave={onMouseLeave}
 * >
 *   View PR
 * </Link>
 * ```
 * 
 * @example With specific data prefetching
 * ```tsx
 * const { onMouseEnter, onMouseLeave } = usePrefetch({ 
 *   delay: 150,
 *   only: ['purchaseRequest', 'items']
 * });
 * ```
 */
export function usePrefetch(config: PrefetchConfig = {}) {
    const {
        delay = 100,
        only,
    } = config;

    // Store timeout ID to allow cancellation
    const timeoutRef = useRef<NodeJS.Timeout | null>(null);
    
    // Track prefetched URLs to avoid duplicate requests
    const prefetchedRef = useRef<Set<string>>(new Set());

    /**
     * Handle mouse enter event
     * Starts prefetch timer when user hovers over a link
     */
    const onMouseEnter = useCallback((event: React.MouseEvent<HTMLAnchorElement>) => {
        // Get the href from the link element
        const href = event.currentTarget.getAttribute('href');
        
        if (!href) return;

        // Skip if already prefetched
        if (prefetchedRef.current.has(href)) return;

        // Clear any existing timeout
        if (timeoutRef.current) {
            clearTimeout(timeoutRef.current);
        }

        // Set new timeout for prefetch
        timeoutRef.current = setTimeout(() => {
            // Mark as prefetched
            prefetchedRef.current.add(href);

            // Perform prefetch using native fetch with Inertia headers
            // This allows the browser to cache the response
            const headers: HeadersInit = {
                'X-Inertia': 'true',
                'X-Inertia-Version': (window as any).Inertia?.version || '',
                'Accept': 'text/html, application/xhtml+xml',
            };

            // Add X-Inertia-Partial-Data header if only specific data is requested
            if (only && only.length > 0) {
                headers['X-Inertia-Partial-Data'] = only.join(',');
                headers['X-Inertia-Partial-Component'] = (window as any).Inertia?.page?.component || '';
            }

            // Fetch the page data
            fetch(href, {
                method: 'GET',
                headers: headers,
                credentials: 'same-origin',
                // Use 'force-cache' to ensure browser caches the response
                cache: 'force-cache',
            }).catch(() => {
                // Silently fail - prefetch is optional
                // Remove from prefetched set so it can be retried
                prefetchedRef.current.delete(href);
            });
        }, delay);
    }, [delay, only]);

    /**
     * Handle mouse leave event
     * Cancels prefetch if user moves cursor away before delay expires
     */
    const onMouseLeave = useCallback(() => {
        if (timeoutRef.current) {
            clearTimeout(timeoutRef.current);
            timeoutRef.current = null;
        }
    }, []);

    return {
        onMouseEnter,
        onMouseLeave,
    };
}

/**
 * usePrefetchLink Hook
 * 
 * Simplified version of usePrefetch that returns props object
 * ready to be spread onto a Link component.
 * 
 * @param href - The URL to prefetch
 * @param config - Prefetch configuration options
 * @returns Props object with onMouseEnter and onMouseLeave handlers
 * 
 * @example
 * ```tsx
 * const prefetchProps = usePrefetchLink('/purchase-requests/123', { delay: 100 });
 * 
 * <Link href="/purchase-requests/123" {...prefetchProps}>
 *   View PR
 * </Link>
 * ```
 */
export function usePrefetchLink(href: string, config: PrefetchConfig = {}) {
    const { onMouseEnter, onMouseLeave } = usePrefetch(config);
    
    return {
        onMouseEnter,
        onMouseLeave,
    };
}
