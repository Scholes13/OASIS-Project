# Code Splitting Quick Reference

## Quick Commands

```bash
# Build for production
npm run build

# Build and analyze bundles
npm run build:analyze

# Development mode
npm run dev
```

## Import Best Practices

### ✅ Good Imports

```typescript
// Named imports - tree-shakeable
import { Button } from '@/components/ui/button';
import { useState, useEffect } from 'react';
import { format } from 'date-fns';

// Dynamic imports for heavy components
const HeavyChart = lazy(() => import('@/components/charts/HeavyChart'));

// Conditional imports
if (showCalendar) {
  const { Calendar } = await import('@fullcalendar/react');
}
```

### ❌ Bad Imports

```typescript
// Namespace imports - not tree-shakeable
import * as UI from '@/components/ui';
import * as Icons from 'lucide-react';

// Importing entire libraries
import _ from 'lodash'; // Use lodash-es instead

// Heavy imports in shared components
// Don't import recharts in shared-ui components
```

## Bundle Size Targets

| Bundle Type | Target Size | Current |
|-------------|-------------|---------|
| Initial Load | < 300 KB | ~140 KB ✅ |
| Page Chunk | < 50 KB | 1-5 KB ✅ |
| Feature Bundle | < 100 KB | Varies |
| Total Bundle | < 2 MB | 1.64 MB ✅ |

## Chunk Categories

### Core (Always Loaded)
- `vendor-react` - React core
- `vendor-inertia` - Inertia.js
- `vendor-ui` - UI libraries
- `vendor-utils` - Utilities
- `shared-layout` - Layout components
- `shared-ui` - UI components

### Feature-Specific (Lazy Loaded)
- `vendor-charts` - Dashboard charts
- `vendor-calendar` - Activity calendar
- `vendor-dnd` - Drag & drop
- `module-purchasing` - PR components
- `module-activity` - Activity components

## Adding New Dependencies

### Before Adding
1. Check bundle size: `npm run build:analyze`
2. Consider alternatives (lighter libraries)
3. Check if already included

### After Adding
1. Build: `npm run build`
2. Analyze: `npm run build:analyze`
3. Check size increase
4. Update manual chunks if needed (vite.config.js)

## Troubleshooting

### Bundle Too Large

```bash
# 1. Analyze current bundles
npm run build:analyze

# 2. Check what's included
# Look for large chunks in output

# 3. Consider:
# - Lazy loading heavy components
# - Using lighter alternatives
# - Code splitting further
```

### Slow Build

```bash
# 1. Check dependencies
npm list --depth=0

# 2. Clear cache
rm -rf node_modules/.vite

# 3. Rebuild
npm run build
```

### Circular Dependencies

```bash
# Warning: Circular chunk detected
# Usually non-critical, but can be fixed by:
# 1. Refactoring shared code
# 2. Adjusting manual chunks in vite.config.js
# 3. Breaking circular imports
```

## Performance Checklist

- [ ] Initial bundle < 300 KB (gzipped)
- [ ] Page chunks < 50 KB each
- [ ] Heavy features lazy loaded
- [ ] Vendor bundles separated
- [ ] Cache headers configured
- [ ] Compression enabled (Gzip/Brotli)
- [ ] Bundle analysis run
- [ ] No large chunk warnings

## Monitoring

### Regular Checks
- Run `npm run build:analyze` weekly
- Monitor bundle sizes in CI/CD
- Check Lighthouse scores
- Review network waterfall

### Key Metrics
- Time to Interactive (TTI)
- First Contentful Paint (FCP)
- Total Bundle Size
- Number of Chunks

## Common Patterns

### Lazy Loading Pages
```typescript
// Automatic with Inertia
// Pages are lazy loaded by default
```

### Lazy Loading Components
```typescript
import { lazy, Suspense } from 'react';

const HeavyComponent = lazy(() => import('./HeavyComponent'));

function MyPage() {
  return (
    <Suspense fallback={<LoadingSpinner />}>
      <HeavyComponent />
    </Suspense>
  );
}
```

### Conditional Loading
```typescript
async function loadFeature() {
  if (userWantsFeature) {
    const { Feature } = await import('./Feature');
    return <Feature />;
  }
}
```

## Quick Fixes

### Chunk Too Large
```typescript
// Split into smaller chunks
// Move heavy code to separate file
// Use dynamic imports
```

### Too Many Chunks
```typescript
// Combine related code
// Adjust manual chunks in vite.config.js
// Group by feature/module
```

### Slow Initial Load
```typescript
// Reduce core bundle size
// Lazy load more features
// Check vendor bundle sizes
// Enable compression
```

## Resources

- [Full Documentation](./CODE-SPLITTING-STRATEGY.md)
- [Bundle Structure](./BUNDLE-STRUCTURE.md)
- [Task Report](./.kiro/specs/livewire-to-react-migration/TASK-45-CODE-SPLITTING-IMPLEMENTATION.md)
- [Vite Docs](https://vitejs.dev/guide/build.html)
- [React Lazy](https://react.dev/reference/react/lazy)

---

**Last Updated**: January 19, 2026
