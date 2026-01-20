# Prefetching Visual Guide

## How Prefetching Works

```
┌─────────────────────────────────────────────────────────────────┐
│                     USER INTERACTION FLOW                        │
└─────────────────────────────────────────────────────────────────┘

1. USER HOVERS OVER LINK
   ┌──────────────────┐
   │  [View PR] Link  │ ← Mouse enters
   └──────────────────┘
          │
          ▼
   ┌──────────────────┐
   │  Timer Starts    │ (100ms delay)
   │  ⏱️ 0ms...       │
   └──────────────────┘

2. CURSOR STAYS ON LINK
   ┌──────────────────┐
   │  Timer Running   │
   │  ⏱️ 50ms...      │
   └──────────────────┘
          │
          ▼
   ┌──────────────────┐
   │  Timer Complete  │
   │  ⏱️ 100ms ✓      │
   └──────────────────┘
          │
          ▼
   ┌──────────────────────────────┐
   │  PREFETCH REQUEST SENT       │
   │  GET /purchase-requests/123  │
   │  X-Inertia: true            │
   │  X-Inertia-Partial-Data:    │
   │    purchaseRequest,items    │
   └──────────────────────────────┘
          │
          ▼
   ┌──────────────────────────────┐
   │  RESPONSE CACHED             │
   │  Browser Cache: ✓            │
   │  URL Marked: ✓               │
   └──────────────────────────────┘

3. USER CLICKS LINK
   ┌──────────────────┐
   │  Click Event     │
   └──────────────────┘
          │
          ▼
   ┌──────────────────────────────┐
   │  INSTANT NAVIGATION          │
   │  Uses Cached Response        │
   │  Load Time: ~50ms            │
   │  (vs ~300ms without cache)   │
   └──────────────────────────────┘

ALTERNATIVE: CURSOR LEAVES EARLY
   ┌──────────────────┐
   │  Timer Running   │
   │  ⏱️ 50ms...      │ ← Mouse leaves
   └──────────────────┘
          │
          ▼
   ┌──────────────────┐
   │  Timer Cancelled │
   │  ❌ No Request   │
   └──────────────────┘
```

## Component Integration

```
┌─────────────────────────────────────────────────────────────────┐
│                    PURCHASE REQUEST TABLE                        │
└─────────────────────────────────────────────────────────────────┘

┌────────────────────────────────────────────────────────────────┐
│ DEPT │ NO. PR  │ USED FOR │ AMOUNT │ DATE │ STATUS │ ACTIONS  │
├────────────────────────────────────────────────────────────────┤
│ IT   │ PR-001  │ Laptop   │ $1,000 │ ...  │ Draft  │ [View] ← │
│                                                         ▲        │
│                                                         │        │
│                                                    Hover here    │
│                                                    triggers      │
│                                                    prefetch      │
└────────────────────────────────────────────────────────────────┘

Code:
const { onMouseEnter, onMouseLeave } = usePrefetch({ 
    delay: 100,
    only: ['purchaseRequest', 'items', 'approvals']
});

<Link 
    href="/purchase-requests/123"
    onMouseEnter={onMouseEnter}  ← Starts timer
    onMouseLeave={onMouseLeave}  ← Cancels timer
>
    View
</Link>
```

```
┌─────────────────────────────────────────────────────────────────┐
│                         SIDEBAR NAVIGATION                       │
└─────────────────────────────────────────────────────────────────┘

┌──────────────────────┐
│  🏠 Dashboard        │ ← Hover triggers prefetch (150ms)
│  🛒 Purchasing       │ ← Hover triggers prefetch (150ms)
│    ├─ 📄 PR List    │ ← Hover triggers prefetch (150ms)
│    ├─ 📦 ST List    │ ← Hover triggers prefetch (150ms)
│    └─ ⚙️  Admin     │ ← Hover triggers prefetch (150ms)
│  📊 Activity         │ ← Hover triggers prefetch (150ms)
│  👥 Users            │ ← Hover triggers prefetch (150ms)
└──────────────────────┘

Code:
const { onMouseEnter, onMouseLeave } = usePrefetch({ 
    delay: 150  // Slightly longer for sidebar
});

<Link
    href={item.href}
    onMouseEnter={onMouseEnter}
    onMouseLeave={onMouseLeave}
>
    {item.name}
</Link>
```

## Performance Comparison

```
┌─────────────────────────────────────────────────────────────────┐
│                    WITHOUT PREFETCH                              │
└─────────────────────────────────────────────────────────────────┘

User clicks link
    │
    ▼
Request sent ────────────────────────────────────────────┐
    │                                                     │
    │ ⏱️ 200-500ms (network + server + processing)      │
    │                                                     │
    ▼                                                     │
Response received ◄──────────────────────────────────────┘
    │
    ▼
Page renders
    │
    ▼
✓ Total: 200-500ms (noticeable delay)


┌─────────────────────────────────────────────────────────────────┐
│                     WITH PREFETCH                                │
└─────────────────────────────────────────────────────────────────┘

User hovers (100ms before click)
    │
    ▼
Prefetch request ────────────────────────────────────────┐
    │                                                     │
    │ ⏱️ 200-500ms (happens in background)              │
    │                                                     │
    ▼                                                     │
Response cached ◄────────────────────────────────────────┘
    │
    │ (User clicks link)
    │
    ▼
Use cached response (instant!)
    │
    ▼
Page renders
    │
    ▼
✓ Total: ~50-100ms (instant feel)

IMPROVEMENT: 2-5x faster perceived load time
```

## Network Tab View

```
┌─────────────────────────────────────────────────────────────────┐
│                    BROWSER DEVTOOLS - NETWORK TAB                │
└─────────────────────────────────────────────────────────────────┘

Name                          Status  Type      Size    Time
────────────────────────────────────────────────────────────────
purchase-requests/123         200     xhr       15.2KB  245ms
  Request Headers:
    X-Inertia: true
    X-Inertia-Version: abc123
    X-Inertia-Partial-Data: purchaseRequest,items,approvals
    Accept: text/html, application/xhtml+xml
  
  Response Headers:
    Content-Type: application/json
    X-Inertia: true
    Cache-Control: private, must-revalidate
  
  Response Preview:
    {
      "component": "Purchasing/PurchaseRequest/Show",
      "props": {
        "purchaseRequest": { ... },
        "items": [ ... ],
        "approvals": [ ... ]
      },
      "url": "/purchase-requests/123",
      "version": "abc123"
    }

(When user clicks, this cached response is used instantly)
```

## Cache Behavior

```
┌─────────────────────────────────────────────────────────────────┐
│                        CACHE LIFECYCLE                           │
└─────────────────────────────────────────────────────────────────┘

SESSION START
    │
    ▼
┌──────────────────┐
│ Prefetch Cache   │
│ (empty)          │
└──────────────────┘
    │
    │ User hovers /purchase-requests/123
    │
    ▼
┌──────────────────┐
│ Prefetch Cache   │
│ ✓ /pr/123        │
└──────────────────┘
    │
    │ User hovers /purchase-requests/456
    │
    ▼
┌──────────────────┐
│ Prefetch Cache   │
│ ✓ /pr/123        │
│ ✓ /pr/456        │
└──────────────────┘
    │
    │ User hovers /pr/123 again
    │ (Already cached - no new request)
    │
    ▼
┌──────────────────┐
│ Prefetch Cache   │
│ ✓ /pr/123        │ ← Reused
│ ✓ /pr/456        │
└──────────────────┘
    │
    │ User navigates away / refreshes
    │
    ▼
┌──────────────────┐
│ Prefetch Cache   │
│ (cleared)        │
└──────────────────┘
```

## Configuration Examples

```typescript
// EXAMPLE 1: Basic prefetch (all data)
const { onMouseEnter, onMouseLeave } = usePrefetch({ 
    delay: 100 
});

// EXAMPLE 2: Prefetch specific data only
const { onMouseEnter, onMouseLeave } = usePrefetch({ 
    delay: 100,
    only: ['purchaseRequest', 'items']  // Only these props
});

// EXAMPLE 3: Longer delay for less critical links
const { onMouseEnter, onMouseLeave } = usePrefetch({ 
    delay: 200  // Wait longer before prefetching
});

// EXAMPLE 4: Using the helper hook
const prefetchProps = usePrefetchLink('/purchase-requests/123', { 
    delay: 100 
});

<Link href="/purchase-requests/123" {...prefetchProps}>
    View PR
</Link>
```

## Testing Checklist

```
┌─────────────────────────────────────────────────────────────────┐
│                        TESTING GUIDE                             │
└─────────────────────────────────────────────────────────────────┘

✅ BASIC FUNCTIONALITY
   □ Hover over link for 100ms
   □ See prefetch request in Network tab
   □ Request has X-Inertia: true header
   □ Click link - loads instantly

✅ CANCELLATION
   □ Hover over link
   □ Move cursor away before 100ms
   □ No prefetch request should appear

✅ DUPLICATE PREVENTION
   □ Hover over same link multiple times
   □ Only ONE prefetch request should appear
   □ Subsequent hovers should not trigger new requests

✅ PARTIAL DATA
   □ Check request headers
   □ Should have X-Inertia-Partial-Data header
   □ Should list only requested props

✅ PERFORMANCE
   □ Measure navigation time with prefetch
   □ Measure navigation time without prefetch
   □ Prefetch should be 2-5x faster

✅ BROWSER COMPATIBILITY
   □ Test in Chrome
   □ Test in Firefox
   □ Test in Safari
   □ Test in Edge
```

## Troubleshooting

```
┌─────────────────────────────────────────────────────────────────┐
│                    COMMON ISSUES & SOLUTIONS                     │
└─────────────────────────────────────────────────────────────────┘

ISSUE: Prefetch not triggering
├─ Check: Cursor stays on link for full delay
├─ Check: Link has onMouseEnter/onMouseLeave handlers
└─ Solution: Increase delay or check console for errors

ISSUE: Prefetch not improving performance
├─ Check: Response is being cached (Network tab)
├─ Check: Cache-Control headers allow caching
└─ Solution: Verify server response headers

ISSUE: Too many prefetch requests
├─ Check: Delay is too short
├─ Check: User is hovering over many links
└─ Solution: Increase delay or reduce prefetch scope

ISSUE: Prefetch using too much bandwidth
├─ Check: Prefetching entire page data
├─ Check: Large response payloads
└─ Solution: Use 'only' option to prefetch partial data
```

## Best Practices

```
┌─────────────────────────────────────────────────────────────────┐
│                        BEST PRACTICES                            │
└─────────────────────────────────────────────────────────────────┘

✓ DO:
  • Use 100-150ms delay for most cases
  • Prefetch only necessary data with 'only' option
  • Apply to high-traffic navigation links
  • Test on slow connections
  • Monitor prefetch effectiveness

✗ DON'T:
  • Prefetch every link on the page
  • Use very short delays (<50ms)
  • Prefetch large payloads unnecessarily
  • Ignore browser compatibility
  • Forget to test cancellation behavior
```
