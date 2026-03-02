# Requirements Document

## Introduction

Optimasi shared data pada Inertia middleware untuk mengurangi beban query database pada setiap request. Saat ini `availableBusinessUnits` dan `navigation` di-query ulang pada setiap request meskipun data tersebut jarang berubah. Implementasi caching akan meningkatkan performa aplikasi secara signifikan.

## Glossary

- **Inertia Middleware**: `HandleInertiaRequests` middleware yang menyediakan shared props ke semua halaman React
- **Shared Props**: Data yang di-share ke semua halaman Inertia (auth, navigation, flash, dll)
- **Navigation Data**: Struktur menu navigasi berdasarkan role dan permission user
- **Business Unit Data**: Daftar business unit yang dapat diakses oleh user
- **Cache Key**: Identifier unik untuk menyimpan data di cache
- **Cache Invalidation**: Proses menghapus cache ketika data source berubah

## Requirements

### Requirement 1

**User Story:** As a system administrator, I want navigation and business unit data to be cached, so that the application responds faster without repeated database queries.

#### Acceptance Criteria

1. WHEN a user accesses any page THEN the System SHALL retrieve navigation data from cache if available
2. WHEN a user accesses any page THEN the System SHALL retrieve available business units from cache if available
3. WHEN cache is empty or expired THEN the System SHALL query the database and store the result in cache
4. WHEN navigation data is cached THEN the System SHALL use a cache key that includes user ID and business unit ID
5. WHEN business unit data is cached THEN the System SHALL use a cache key that includes user ID

### Requirement 2

**User Story:** As a system administrator, I want cache to be invalidated when relevant data changes, so that users always see accurate navigation and business unit information.

#### Acceptance Criteria

1. WHEN a user's role or permission changes THEN the System SHALL clear that user's navigation cache
2. WHEN a user's business unit assignment changes THEN the System SHALL clear that user's navigation and business unit cache
3. WHEN a business unit is created, updated, or deleted THEN the System SHALL clear all users' business unit cache
4. WHEN a user switches business unit THEN the System SHALL use the new business unit ID in the cache key
5. WHEN cache is invalidated THEN the System SHALL regenerate cache on the next request

### Requirement 3

**User Story:** As a developer, I want a configurable cache TTL (Time To Live), so that I can balance between performance and data freshness.

#### Acceptance Criteria

1. WHEN caching navigation data THEN the System SHALL use a configurable TTL defaulting to 60 minutes
2. WHEN caching business unit data THEN the System SHALL use a configurable TTL defaulting to 60 minutes
3. WHEN TTL is configured via environment variable THEN the System SHALL respect the configured value
4. WHEN cache expires THEN the System SHALL automatically refresh from database on next request

### Requirement 4

**User Story:** As a developer, I want current business unit data to remain uncached, so that session-based context switching works correctly.

#### Acceptance Criteria

1. WHEN retrieving current business unit THEN the System SHALL query the database directly without caching
2. WHEN user switches business unit THEN the System SHALL immediately reflect the change without cache delay
3. WHEN current business unit is retrieved THEN the System SHALL use the session value as the source of truth

### Requirement 5

**User Story:** As a system administrator, I want a manual cache clear mechanism, so that I can force refresh navigation data when needed.

#### Acceptance Criteria

1. WHEN an artisan command `cache:clear-navigation` is executed THEN the System SHALL clear all navigation cache entries
2. WHEN an artisan command `cache:clear-navigation --user={id}` is executed THEN the System SHALL clear navigation cache for that specific user
3. WHEN cache is manually cleared THEN the System SHALL log the action for audit purposes
