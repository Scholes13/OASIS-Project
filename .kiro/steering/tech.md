# Technology Stack

## Core Framework

- **Laravel**: 12.26.3 (latest stable)
- **PHP**: 8.2.12+
- **Database**: MySQL (production), SQLite (development/testing)
- **Timezone**: Asia/Jakarta

## Frontend Stack

- **Livewire**: 3.6.4 (reactive components, primary UI framework)
- **Volt**: 1.7.2 (Livewire single-file components)
- **Alpine.js**: 3.14.9 (client-side interactivity)
- **Tailwind CSS**: 3.4.17 (utility-first styling - **PRIMARY STYLING METHOD**)
- **Vite**: 7.0.4 (asset bundling)
- **Chart.js**: Via CDN (dashboard charts)

### Frontend Styling Guidelines

**This project uses Tailwind CSS exclusively for all styling. DO NOT use traditional CSS or Bootstrap.**

#### Styling Approach
- **100% Tailwind Utility Classes**: All views use Tailwind utility classes directly in Blade templates
- **No Custom CSS**: Avoid writing custom CSS unless absolutely necessary
- **Component-Based**: Reusable UI patterns through Blade components with Tailwind classes
- **Responsive Design**: Use Tailwind responsive prefixes (sm:, md:, lg:, xl:, 2xl:)
- **Dark Mode**: Not currently implemented

#### Common Patterns in This Project

**Cards & Containers:**
```blade
<div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
    <div class="px-5 py-4 border-b border-gray-100">
        <h3 class="text-base font-semibold text-gray-900">Title</h3>
    </div>
    <div class="p-6">
        <!-- Content -->
    </div>
</div>
```

**Status Badges:**
```blade
<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700">
    <svg class="w-3.5 h-3.5 mr-1">...</svg>
    Approved
</span>
```

**Buttons:**
```blade
<!-- Primary Action -->
<button class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors">
    <svg class="w-4 h-4 mr-2">...</svg>
    Action
</button>

<!-- Secondary Action -->
<button class="inline-flex items-center px-3 py-1.5 text-sm text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-md transition-colors">
    Edit
</button>
```

**Tables:**
```blade
<table class="min-w-full divide-y divide-gray-200">
    <thead class="bg-gray-50">
        <tr>
            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Header</th>
        </tr>
    </thead>
    <tbody class="bg-white divide-y divide-gray-100">
        <tr class="hover:bg-gray-50 transition-colors">
            <td class="px-5 py-4 whitespace-nowrap text-sm text-gray-900">Data</td>
        </tr>
    </tbody>
</table>
```

**Forms:**
```blade
<div class="space-y-4">
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Label</label>
        <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
    </div>
</div>
```

#### Color Palette
- **Primary**: Indigo (indigo-600, indigo-700)
- **Success**: Emerald (emerald-100, emerald-600, emerald-700)
- **Warning**: Amber (amber-100, amber-600, amber-700)
- **Danger**: Red (red-100, red-600, red-700)
- **Info**: Blue (blue-100, blue-600, blue-700)
- **Neutral**: Gray (gray-50 to gray-900)
- **Special**: Purple for offline approvals (purple-100, purple-600, purple-700)

#### Icons
- **Heroicons**: Inline SVG icons from Heroicons (outline style)
- **Size**: Typically w-4 h-4 or w-5 h-5
- **Stroke Width**: 1.5 for consistency

#### Alpine.js Integration
```blade
<div x-data="{ open: false }">
    <button @click="open = !open">Toggle</button>
    <div x-show="open" x-transition>Content</div>
</div>
```

#### When to Create New Views
- **Always use Tailwind**: Never write custom CSS classes
- **Check existing patterns**: Look at similar views for consistency
- **Reuse components**: Use existing Blade components when possible
- **Mobile-first**: Design for mobile, then add responsive classes

## Key Packages

### Laravel Ecosystem
- **Laravel Breeze**: 2.3.8 (authentication scaffolding)
- **Laravel Tinker**: 2.10.1 (REPL)
- **Laravel Pint**: 1.24.0 (code style fixer)
- **Laravel Sail**: 1.45.0 (Docker development environment)
- **Laravel Boost**: 1.1+ (MCP server for AI assistance)

### Third-Party Packages
- **Spatie Permission**: 6.21+ (roles & permissions)
- **Spatie Activity Log**: 4.10+ (audit trails)
- **Spatie Browsershot**: 5.0+ (PDF generation via Puppeteer)
- **SimpleSoftwareIO QR Code**: 4.2+ (QR code generation)
- **Predis**: 3.2+ (Redis client)

### Development Tools
- **PHPUnit**: 11.5.35 (testing framework)
- **Laravel Debugbar**: 3.16+ (dev debugging)
- **Laravel IDE Helper**: 3.6+ (IDE autocomplete)
- **Laravel Pail**: 1.2.2+ (log viewer)

## Build System

### Development Commands

```bash
# Install dependencies
composer install
npm install

# Environment setup
cp .env.example .env
php artisan key:generate

# Database
php artisan migrate
php artisan db:seed

# Asset compilation
npm run dev          # Development with HMR
npm run build        # Production build

# Development server
php artisan serve    # Single server
composer dev         # Full stack (server + queue + logs + vite)
```

### Testing

```bash
composer test        # Run PHPUnit tests
php artisan test     # Alternative test command
```

### Code Quality

```bash
./vendor/bin/pint    # Fix code style (Laravel Pint)
```

### IDE Helpers

```bash
php artisan ide-helper:generate    # Generate helper files
php artisan ide-helper:models      # Generate model annotations
php artisan ide-helper:meta        # Generate PhpStorm meta
```

## Asset Pipeline

- **Vite Configuration**: `vite.config.js`
- **Entry Points**: 
  - `resources/css/app.css` (Tailwind)
  - `resources/js/app.js` (Alpine.js bootstrap)
- **Tailwind Config**: `tailwind.config.js` (custom animations, fonts)
- **PostCSS**: `postcss.config.js`

## Development Workflow

### Concurrent Development (composer dev)
Runs 4 processes simultaneously:
1. `php artisan serve` - Web server (port 8000)
2. `php artisan queue:listen` - Queue worker
3. `php artisan pail` - Real-time log viewer
4. `npm run dev` - Vite HMR server

### Queue System
- Driver: Database (default)
- Jobs: Email notifications, PDF generation
- Command: `php artisan queue:listen --tries=1`

## Browser Requirements

- Modern browsers with ES6+ support
- JavaScript enabled (Alpine.js, Livewire)
- Puppeteer for PDF generation (server-side)

## MCP (Model Context Protocol) Best Practices

### Laravel Boost MCP Server

This project uses **Laravel Boost** (v1.1+) MCP server for AI assistance. Always use MCP tools instead of making assumptions.

### Available MCP Tools

#### Application Information
- `mcp_laravel_boost_application_info` - Get PHP version, Laravel version, database engine, installed packages, and models
- **Use this FIRST** when starting work to understand the current environment

#### Database Operations
- `mcp_laravel_boost_database_schema` - Read complete database schema (tables, columns, indexes, foreign keys)
- `mcp_laravel_boost_database_query` - Execute read-only SQL queries (SELECT, SHOW, EXPLAIN, DESCRIBE)
- `mcp_laravel_boost_database_connections` - List configured database connections
- **Always check schema before making database assumptions**

#### Configuration & Environment
- `mcp_laravel_boost_list_available_config_keys` - List all config keys from config/*.php
- `mcp_laravel_boost_get_config` - Get specific config value using dot notation
- `mcp_laravel_boost_list_available_env_vars` - List environment variables from .env files
- **Verify config values instead of assuming defaults**

#### Routes & Commands
- `mcp_laravel_boost_list_routes` - List all routes (with filters: method, path, name, action, domain)
- `mcp_laravel_boost_list_artisan_commands` - List all registered Artisan commands
- `mcp_laravel_boost_get_absolute_url` - Get absolute URL for path or named route

#### Code Execution & Debugging
- `mcp_laravel_boost_tinker` - Execute PHP code in Laravel context (like artisan tinker)
- `mcp_laravel_boost_last_error` - Get details of last backend error/exception
- `mcp_laravel_boost_read_log_entries` - Read last N entries from application log
- `mcp_laravel_boost_browser_logs` - Read last N entries from browser log (for frontend debugging)
- **Use tinker to test code snippets before implementing**

#### Documentation
- `mcp_laravel_boost_search_docs` - Search version-specific docs for Laravel ecosystem packages
- **Always search docs for package-specific features (Laravel, Livewire, Spatie, etc.)**

### MCP Usage Guidelines

#### DO ✅
1. **Start with `application_info`** to understand the environment
2. **Check database schema** before writing queries or migrations
3. **Verify routes** before creating new ones or referencing existing routes
4. **Search documentation** for package-specific features (Livewire 3.6.4, Spatie Permission 6.21+, etc.)
5. **Use tinker** to test business logic, check if functions exist, validate code snippets
6. **Check logs** when debugging issues (`read_log_entries`, `browser_logs`, `last_error`)
7. **Query database** to verify data state before making changes
8. **List config keys** to understand available configuration options

#### DON'T ❌
1. **Don't assume** package versions - use `application_info` to get exact versions
2. **Don't guess** database structure - use `database_schema` to see actual tables/columns
3. **Don't assume** routes exist - use `list_routes` to verify
4. **Don't hardcode** config values - use `get_config` to read actual values
5. **Don't assume** Eloquent relationships - check models or use `database_schema` for foreign keys
6. **Don't guess** error causes - use `last_error` and `read_log_entries` to see actual errors

### Example Workflow

```bash
# 1. Understand the environment
mcp_laravel_boost_application_info

# 2. Check database structure
mcp_laravel_boost_database_schema

# 3. Verify existing routes
mcp_laravel_boost_list_routes(path: "purchase-requests")

# 4. Test code before implementing
mcp_laravel_boost_tinker(code: "App\\Models\\Modules\\PurchaseRequest\\PurchaseRequest::count()")

# 5. Search for package-specific documentation
mcp_laravel_boost_search_docs(queries: ["livewire lazy loading"], packages: ["livewire/livewire"])

# 6. Check for errors
mcp_laravel_boost_last_error
mcp_laravel_boost_read_log_entries(entries: 20)
```

### Business Unit Context Verification

When working with business unit-specific features:

```bash
# Check current session data
mcp_laravel_boost_tinker(code: "session()->all()")

# Verify business unit structure
mcp_laravel_boost_database_query(query: "SELECT * FROM business_units")

# Check user business unit assignments
mcp_laravel_boost_database_query(query: "SELECT * FROM user_business_units WHERE user_id = 1")
```

## Deployment Considerations

- PHP 8.2+ required
- Node.js for asset compilation
- MySQL database
- Redis recommended for caching/sessions
- Puppeteer/Chromium for PDF generation
- SMTP server for email notifications
