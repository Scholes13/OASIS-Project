# Code Splitting Strategy

## Overview

This document describes the code splitting and bundle optimization strategy implemented for the Oasis React/Inertia.js application.

## Goals

1. **Reduce Initial Load Time**: Split code into smaller chunks that load only when needed
2. **Optimize Caching**: Separate vendor code from application code for better cache utilization
3. **Improve Performance**: Load only the JavaScript required for each page
4. **Maintain Developer Experience**: Keep the development workflow simple and fast

## Implementation

### 1. Lazy Loading Page Components

**Location**: `resources/js/inertia/app.tsx`

All page components are lazy-loaded using dynamic imports:

```typescript
const pages = import.meta.glob('./Pages/**/*.tsx');
const page = await pages[pagePath]();
```

**Benefits**:
- Each page is a separate chunk
- Pages load only when navigated to
- Reduces initial bundle size
- Faster first contentful paint

### 2. Vendor Bundle Splitting

**Location**: `vite.config.js` → `build.rollupOptions.output.manualChunks`

Vendor libraries are split into logical groups:

#### Core Bundles (Loaded on Every Page)

- **vendor-react**: React, ReactDOM, Inertia React adapter
  - ~150KB (gzipped)
  - Critical for all pages
  - Cached long-term

- **vendor-ui**: UI component libraries
  - Headless UI, Framer Motion, Lucide React
  - ~80KB (gzipped)
  - Used across most pages

- **vendor-utils**: Utility libraries
  - clsx, tailwind-merge, class-variance-authority, date-fns
  - ~30KB (gzipped)
  - Shared utilities

- **vendor-state**: State management
  - Zustand
  - ~5KB (gzipped)
  - Layout state management

#### Feature-Specific Bundles (Loaded on Demand)

- **vendor-charts**: Data visualization
  - Recharts, TanStack Table
  - ~200KB (gzipped)
  - Only loaded on dashboard/analytics pages

- **vendor-calendar**: Calendar functionality
  - FullCalendar
  - ~150KB (gzipped)
  - Only loaded on activity tracking pages

- **vendor-dnd**: Drag and drop
  - DnD Kit
  - ~40KB (gzipped)
  - Only loaded on pages with drag/drop features

- **vendor-toast**: Toast notifications
  - Sonner
  - ~10KB (gzipped)
  - Loaded when needed

- **vendor-cmdk**: Command palette
  - cmdk
  - ~20KB (gzipped)
  - Loaded when command palette is used

### 3. Application Code Splitting

#### Shared Components

- **shared-layout**: Layout components (Sidebar, Navbar, AppLayout)
  - Loaded on most pages
  - ~30KB (gzipped)

- **shared-ui**: Reusable UI components (Button, Input, Card, etc.)
  - Loaded on most pages
  - ~40KB (gzipped)

#### Module-Specific Components

- **module-purchasing**: Purchasing module components
  - Only loaded on purchasing pages
  - ~50KB (gzipped)

- **module-activity**: Activity tracking components
  - Only loaded on activity pages
  - ~40KB (gzipped)

- **module-admin**: Admin panel components
  - Only loaded on admin pages
  - ~30KB (gzipped)

### 4. Page-Level Splitting

Each page component is automatically split into its own chunk:

- `Dashboard.tsx` → `Dashboard-[hash].js`
- `Purchasing/PurchaseRequest/Index.tsx` → `Index-[hash].js`
- `Purchasing/PurchaseRequest/Create.tsx` → `Create-[hash].js`
- etc.

## Bundle Size Targets

### Initial Load (First Visit)
- **Target**: < 300KB (gzipped)
- **Includes**: vendor-react, vendor-ui, vendor-utils, vendor-state, shared-layout, shared-ui, first page

### Subsequent Pages
- **Target**: < 50KB per page (gzipped)
- **Includes**: Page-specific code + module components (if not cached)

### Feature-Specific Bundles
- **Charts**: ~200KB (loaded only on dashboard/analytics)
- **Calendar**: ~150KB (loaded only on activity tracking)
- **DnD**: ~40KB (loaded only on pages with drag/drop)

## Optimization Techniques

### 1. Tree Shaking
- Vite automatically removes unused code
- Import only what you need: `import { Button } from '@/components/ui/button'`

### 2. Minification
- Terser minification enabled in production
- Console.log statements removed in production
- Dead code elimination

### 3. Compression
- Gzip compression enabled on server
- Brotli compression recommended for production

### 4. Caching Strategy
- Vendor bundles: Long-term caching (1 year)
- Application code: Medium-term caching (1 week)
- Page chunks: Short-term caching (1 day)

## Monitoring Bundle Size

### Build Analysis

Run the bundle analyzer after building:

```bash
npm run build:analyze
```

This will show:
- Size of each chunk
- Total bundle size
- Optimization recommendations
- Large chunk warnings (>500KB)

### Example Output

```
📦 Bundle Analysis

📄 JavaScript Chunks:

  ⚛️  React Core         vendor-react-abc123.js              145.23 KB
  🎨 UI Libraries        vendor-ui-def456.js                  78.45 KB
  📈 Charts              vendor-charts-ghi789.js             198.67 KB
  🏗️  Layout             shared-layout-jkl012.js              32.11 KB
  🧩 UI Components       shared-ui-mno345.js                  41.23 KB
  🛒 Purchasing          module-purchasing-pqr678.js          52.34 KB
  🚀 App Entry           app-stu901.js                        12.45 KB

TOTAL JS                                                     560.48 KB

💡 Optimization Tips:
  ✅ All chunks are optimally sized (<500KB)
  📦 Vendor bundles: 5 chunks, 422.35 KB total
```

## Best Practices

### For Developers

1. **Import Strategically**
   ```typescript
   // ✅ Good - imports only what's needed
   import { Button } from '@/components/ui/button';
   
   // ❌ Bad - imports entire module
   import * as UI from '@/components/ui';
   ```

2. **Lazy Load Heavy Components**
   ```typescript
   // For components used conditionally
   const HeavyChart = lazy(() => import('@/components/charts/HeavyChart'));
   ```

3. **Check Bundle Impact**
   - Run `npm run build:analyze` before committing large changes
   - Keep page chunks under 50KB (gzipped)
   - Avoid importing heavy libraries in shared components

4. **Use Dynamic Imports for Conditional Features**
   ```typescript
   // Only load when needed
   if (showChart) {
     const { Chart } = await import('recharts');
   }
   ```

### For Production

1. **Enable Compression**
   - Configure Nginx/Apache for Gzip/Brotli
   - Serve pre-compressed files when available

2. **Set Cache Headers**
   ```nginx
   # Vendor bundles (1 year)
   location ~* vendor-.*\.js$ {
     expires 1y;
     add_header Cache-Control "public, immutable";
   }
   
   # Application code (1 week)
   location ~* \.js$ {
     expires 7d;
     add_header Cache-Control "public";
   }
   ```

3. **Monitor Performance**
   - Use Lighthouse to track bundle size impact
   - Monitor Time to Interactive (TTI)
   - Track First Contentful Paint (FCP)

## Troubleshooting

### Large Bundle Size

If a chunk is too large (>500KB):

1. Check what's included: `npm run build:analyze`
2. Look for heavy dependencies
3. Consider lazy loading or code splitting
4. Check for duplicate dependencies

### Slow Initial Load

If initial load is slow:

1. Check vendor bundle sizes
2. Ensure code splitting is working
3. Verify compression is enabled
4. Check network waterfall in DevTools

### Cache Issues

If users see old code:

1. Verify hash-based filenames are used
2. Check cache headers
3. Ensure service workers are updated
4. Clear CDN cache after deployment

## Future Improvements

1. **Route-Based Prefetching**: Prefetch likely next pages on hover
2. **Component-Level Splitting**: Further split large components
3. **Dynamic Imports**: More aggressive lazy loading
4. **Bundle Analysis CI**: Automated bundle size checks in CI/CD
5. **Performance Budgets**: Set and enforce bundle size limits

## References

- [Vite Code Splitting](https://vitejs.dev/guide/build.html#chunking-strategy)
- [React Lazy Loading](https://react.dev/reference/react/lazy)
- [Inertia.js Performance](https://inertiajs.com/performance)
- [Web.dev Bundle Size](https://web.dev/reduce-javascript-payloads-with-code-splitting/)
