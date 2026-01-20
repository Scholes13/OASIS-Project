# Livewire to React Migration Spec

## Overview

Spec ini menggabungkan migrasi dari Livewire ke React dengan Inertia.js untuk komponen-komponen berikut:
- **Sidebar** - Navigation sidebar dengan menu dinamis
- **Navbar** - Top navigation bar dengan business unit switcher dan user menu
- **Purchase Request Module** - Index, Create, Show pages

## Problem yang Diperbaiki

### 1. Error NavigationService
**Error:** `Target class [App\Services\Core\NavigationService] does not exist`

**Penyebab:** File `HandleInertiaRequests.php` middleware sudah ada dan mencoba menggunakan `NavigationService`, tetapi service tersebut belum dibuat.

**Solusi:** Membuat `app/Services/Core/NavigationService.php` yang membangun menu navigasi dinamis berdasarkan:
- User permissions
- Business unit context
- User role (Super Admin, Purchasing Admin, Regular User)

### 2. Struktur Migrasi yang Terorganisir
Spec ini menyediakan roadmap lengkap untuk migrasi bertahap dari Livewire ke React, dengan:
- Requirements yang jelas (15 requirements dengan 80+ acceptance criteria)
- Design document yang detail dengan arsitektur dan data flow
- Implementation plan dengan 58 tasks terstruktur dalam 12 fase

## Struktur Spec

### 1. Requirements Document
**File:** `requirements.md`

**Isi:**
- 15 Requirements utama
- 80+ Acceptance Criteria
- Mencakup semua aspek migrasi:
  - Inertia.js setup
  - NavigationService
  - React layout components
  - Business unit switcher
  - Purchase Request pages
  - Toast notifications
  - Loading states
  - Error handling
  - Performance optimization
  - TypeScript type safety
  - Backward compatibility

### 2. Design Document
**File:** `design.md`

**Isi:**
- Technology stack (React 18, TypeScript 5, Inertia.js 1.0+)
- Folder structure lengkap
- Data flow diagram
- Component interfaces dan props
- NavigationService implementation details
- TypeScript type definitions
- Error handling strategy
- Testing strategy
- Performance optimization
- Migration strategy (3 phases)

### 3. Tasks Document
**File:** `tasks.md`

**Isi:**
- 58 tasks terorganisir dalam 12 fase:
  1. Foundation Setup (5 tasks)
  2. NavigationService Implementation (3 tasks)
  3. React Layout Components (8 tasks)
  4. Dashboard Page Migration (4 tasks)
  5. Purchase Request Index Page (5 tasks)
  6. Purchase Request Create Page (5 tasks)
  7. Purchase Request Detail Page (6 tasks)
  8. Loading States (4 tasks)
  9. Error Handling (4 tasks)
  10. Performance Optimization (5 tasks)
  11. Testing and Documentation (5 tasks)
  12. Deployment and Rollout (4 tasks)

## NavigationService yang Sudah Dibuat

**File:** `app/Services/Core/NavigationService.php`

**Fitur:**
- ✅ Membangun menu dinamis berdasarkan user permissions
- ✅ Support untuk Super Admin (full access)
- ✅ Support untuk Purchasing Admin (purchasing admin menu)
- ✅ Support untuk Top Management di Parent BU (reports access)
- ✅ Business unit context aware
- ✅ Organized menu sections:
  - Dashboard
  - Purchasing (PR, ST, Purchasing Admin)
  - Activity Tracking
  - Sales CRM
  - Administration (Super Admin only)

**Methods:**
- `buildMenuForUser(User $user, ?int $businessUnitId): array`
- `getDashboardSection(User $user): array`
- `getPurchasingSection(User $user, int $businessUnitId): array`
- `getActivityTrackingSection(User $user, int $businessUnitId): array`
- `getSalesCrmSection(User $user, int $businessUnitId): array`
- `getAdministrationSection(User $user): array`
- `canAccessPurchasingAdmin(User $user, int $businessUnitId): bool`

## Next Steps

### Immediate Actions (Memperbaiki Error)

1. **Test NavigationService:**
   ```bash
   php artisan tinker
   ```
   ```php
   $user = App\Models\Core\User::find(1);
   $service = new App\Services\Core\NavigationService();
   $menu = $service->buildMenuForUser($user, 1);
   dd($menu);
   ```

2. **Clear Cache:**
   ```bash
   php artisan cache:clear
   php artisan config:clear
   php artisan route:clear
   php artisan view:clear
   ```

3. **Test Application:**
   - Visit any page yang menggunakan Inertia
   - Verify tidak ada error NavigationService lagi

### Mulai Migrasi (Setelah Error Fixed)

**Opsi 1: Mulai dari Task 1 (Full Setup)**
```bash
# Install dependencies
composer require inertiajs/inertia-laravel
npm install @inertiajs/react react react-dom @types/react @types/react-dom typescript
npm install @headlessui/react @heroicons/react
```

**Opsi 2: Fokus pada NavigationService dulu (Task 6-8)**
- NavigationService sudah dibuat ✅
- Tinggal update HandleInertiaRequests untuk menggunakannya
- Test dengan berbagai user roles

**Opsi 3: Mulai dengan Layout Components (Task 9-16)**
- Buat UI components (Button, Input, etc.)
- Buat Sidebar component
- Buat Navbar component
- Buat BusinessUnitSwitcher
- Buat UserMenu

## Rekomendasi

### Untuk Memperbaiki Error Sekarang:
1. ✅ NavigationService sudah dibuat
2. Test dengan `php artisan tinker`
3. Clear all caches
4. Reload aplikasi

### Untuk Memulai Migrasi:
1. Review requirements.md untuk memahami scope
2. Review design.md untuk memahami arsitektur
3. Mulai dari Phase 1 (Foundation Setup) di tasks.md
4. Atau fokus pada komponen spesifik yang ingin dimigrasi dulu

## Backward Compatibility

Spec ini dirancang untuk **gradual migration**:
- ✅ Livewire routes tetap berfungsi
- ✅ Inertia routes bisa ditambahkan secara bertahap
- ✅ Kedua sistem bisa coexist
- ✅ Session state tetap konsisten
- ✅ Authentication bekerja untuk keduanya

## Testing Strategy

- **Unit Tests:** React components, NavigationService
- **Integration Tests:** Inertia flow, form submissions, BU switching
- **E2E Tests:** Complete user workflows
- **Manual Tests:** Browser compatibility, responsive design

## Performance Targets

- **Code Splitting:** Lazy load pages by route
- **Bundle Size:** Optimize with Vite
- **Loading Time:** < 2s initial load, < 500ms navigation
- **Prefetching:** Prefetch linked pages on hover

## Documentation

Semua dokumentasi ada di folder `.kiro/specs/livewire-to-react-migration/`:
- `requirements.md` - Requirements dan acceptance criteria
- `design.md` - Arsitektur dan design decisions
- `tasks.md` - Implementation plan step-by-step
- `README.md` - Overview dan quick start (file ini)

## Support

Jika ada pertanyaan atau butuh klarifikasi:
1. Baca requirements.md untuk memahami "what"
2. Baca design.md untuk memahami "how"
3. Baca tasks.md untuk memahami "step-by-step"
4. Tanya spesifik tentang task atau requirement tertentu

---

**Status:** ✅ Spec Complete, NavigationService Created, Ready to Start Migration
