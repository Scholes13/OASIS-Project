# Prefetching Strategy Documentation

## Overview

This document describes the prefetching implementation for Inertia.js navigation in the Oasis application. Prefetching improves perceived performance by loading page data before users actually navigate to a page.

## Implementation

### Hook: `usePrefetch`

Location: `resources/js/inertia/hooks/usePrefetch.ts`

The `usePrefetch` hook provides hover-based prefetching functionality for Inertia links.

#### Features

1. **Hover-based Prefetching**: Automatically prefetches page data when users hover over links
2. **Configurable Delay**: Prevents unnecessary requests when users quickly move their cursor
3. **Duplicate Prevention**: Tracks prefetched URLs to avoid redundant requests
4. **Automatic Cancellation**: Cancels prefetch if user moves cursor away before delay expires
5. **Partial Data Support**: Can prefetch only specific data keys to reduce bandwidth
6. **Silent Operation**: Prefetching happens in the background without UI indicators

#### How It Works

```typescript
// 1. User hovers over a link
// 2. Timer starts (default: 100ms delay)
// 3. If cursor stays on link for full delay:
//    - Fetch page data with Inertia headers
//    - Store response in browser cache
//    - Mark URL as prefetched
// 4. If cursor leaves before delay:
//    - Cancel timer
//    - No request is made
// 5. When user clicks link:
//    - Inertia uses cached response
//    - Page loads instantly
```

### Configuration Options

```typescript
interface PrefetchConfig {
    delay?: number;      // Delay in ms before prefetch (default: 100)
    only?: string[];     // Specific data keys to prefetch (optional)
}
```

### Usage Examples

#### Basic Usage

```tsx
import { usePrefetch } from '@/hooks/usePrefetch';

function MyComponent() {
    const { onMouseEnter, onMouseLeave } = usePrefetch({ delay: 100 });

    return (
        <Link 
            href="/purchase-requests/123"
            onMouseEnter={onMouseEnter}
            onMouseLeave={onMouseLeave}
        >
            View PR
        </Link>
    );
}
```

#### Prefetch Specific Data Only

```tsx
const { onMouseEnter, onMouseLeave } = usePrefetch({ 
    delay: 150,
    only: ['purchaseRequest', 'items', 'approvals']
});
```

#### Using `usePrefetchLink` Helper

```tsx
import { usePrefetchLink } from '@/hooks/usePrefetch';

function MyComponent() {
    const prefetchProps = usePrefetchLink('/purchase-requests/123', { delay: 100 });

    return (
        <Link href="/purchase-requests/123" {...prefetchProps}>
            View PR
        </Link>
    );
}
```

## Current Implementation

### Components Using Prefetch

1. **PurchaseRequestTable** (`resources/js/inertia/components/purchasing/PurchaseRequestTable.tsx`)
   - Prefetches PR detail pages on hover
   - Delay: 100ms
   - Prefetches: `['purchaseRequest', 'items', 'approvals']`

2. **Sidebar** (`resources/js/inertia/components/layout/Sidebar.tsx`)
   - Prefetches all navigation links on hover
   - Delay: 150ms (slightly longer to avoid excessive prefetching)
   - Prefetches: All page data

### Delay Configuration

Different delays are used based on context:

- **100ms**: Action buttons, table rows (user is likely to click)
- **150ms**: Sidebar navigation (user may be browsing)
- **200ms+**: Less critical links (optional, not currently used)

## Performance Considerations

### Benefits

1. **Instant Navigation**: Pages load instantly when data is already cached
2. **Reduced Perceived Latency**: Users don't wait for data to load
3. **Better UX**: Smoother, more responsive application feel
4. **Bandwidth Efficient**: Only prefetches when user shows intent (hover)

### Optimizations

1. **Duplicate Prevention**: Each URL is only prefetched once per session
2. **Delay-based Throttling**: Prevents prefetching on accidental hovers
3. **Automatic Cancellation**: Cancels requests if user moves away
4. **Partial Data**: Can prefetch only necessary data to reduce payload size
5. **Browser Caching**: Leverages native browser cache for storage

### Potential Issues

1. **Increased Server Load**: More requests to server (mitigated by delay and duplicate prevention)
2. **Bandwidth Usage**: Additional data transfer (mitigated by partial data and caching)
3. **Stale Data**: Cached data may become outdated (Inertia handles version checking)

## Browser Compatibility

- **Modern Browsers**: Full support (Chrome, Firefox, Safari, Edge)
- **Fetch API**: Required (supported in all modern browsers)
- **Cache API**: Uses browser's native caching mechanism

## Testing

### Manual Testing

1. **Hover Test**: Hover over a link and wait for delay
   - Open Network tab in DevTools
   - Should see prefetch request after delay
   - Request should have `X-Inertia: true` header

2. **Cancellation Test**: Hover over link and move away quickly
   - Should NOT see prefetch request
   - Timer should be cancelled

3. **Duplicate Test**: Hover over same link multiple times
   - Should only see ONE prefetch request
   - Subsequent hovers should not trigger new requests

4. **Navigation Test**: Hover over link, then click
   - Page should load instantly
   - Should use cached response (no new request)

### Performance Testing

```javascript
// Measure navigation time with prefetch
performance.mark('nav-start');
router.visit('/purchase-requests/123');
performance.mark('nav-end');
performance.measure('navigation', 'nav-start', 'nav-end');

// Compare with non-prefetched navigation
// Prefetched: ~50-100ms
// Non-prefetched: ~200-500ms
```

## Future Enhancements

### Potential Improvements

1. **Predictive Prefetching**: Prefetch based on user behavior patterns
2. **Priority Queue**: Prioritize certain routes over others
3. **Network-aware**: Disable on slow connections
4. **Service Worker**: Use service worker for more advanced caching
5. **Analytics**: Track prefetch hit rate and effectiveness

### Configuration Options to Add

```typescript
interface PrefetchConfig {
    // Existing
    delay?: number;
    only?: string[];
    
    // Potential additions
    priority?: 'high' | 'low';           // Request priority
    networkAware?: boolean;              // Disable on slow connections
    maxCacheSize?: number;               // Limit cached responses
    cacheExpiry?: number;                // Cache expiration time
    onPrefetch?: () => void;             // Callback when prefetch starts
    onPrefetchComplete?: () => void;     // Callback when prefetch completes
}
```

## Troubleshooting

### Prefetch Not Working

1. **Check Network Tab**: Verify requests are being made
2. **Check Headers**: Ensure `X-Inertia: true` header is present
3. **Check Delay**: Ensure cursor stays on link long enough
4. **Check Console**: Look for JavaScript errors

### Prefetch Not Improving Performance

1. **Check Cache**: Verify responses are being cached
2. **Check Network Speed**: Prefetch may not help on very fast connections
3. **Check Data Size**: Large responses may take time to cache
4. **Check Server Response Time**: Slow server responses limit benefits

### Too Many Prefetch Requests

1. **Increase Delay**: Use longer delay (200ms+)
2. **Reduce Scope**: Prefetch fewer links
3. **Use Partial Data**: Prefetch only necessary data keys

## Best Practices

1. **Use Appropriate Delays**: 100-150ms for most cases
2. **Prefetch Selectively**: Don't prefetch every link
3. **Use Partial Data**: Prefetch only what's needed
4. **Monitor Performance**: Track prefetch effectiveness
5. **Test on Slow Connections**: Ensure good experience on all networks

## References

- [Inertia.js Documentation](https://inertiajs.com/)
- [Fetch API](https://developer.mozilla.org/en-US/docs/Web/API/Fetch_API)
- [Cache API](https://developer.mozilla.org/en-US/docs/Web/API/Cache)
- [Web Performance](https://web.dev/performance/)
