---
inclusion: always
---

# Technology Stack & Conventions

## Core Stack

| Layer | Technology | Version |
|-------|------------|---------|
| Backend | Laravel | 12.x |
| PHP | PHP | 8.2+ |
| Database | MySQL (prod) / SQLite (dev) | - |
| Frontend | React/Inertia (new) + Livewire (legacy) | - |
| Styling | Tailwind CSS | 3.4.x |
| Interactivity | Alpine.js | 3.14.x |
| Build | Vite | 7.x |

## Critical Rules

### 🚫 FORBIDDEN Actions
- **NEVER** run `migrate:fresh`, `migrate:refresh`, `db:wipe`, or any DROP database commands
- **NEVER** hardcode ENV values in code (security risk, especially in frontend)
- **NEVER** expose sensitive config/env values to frontend JavaScript
- **NEVER** rewrite existing components - extend or compose instead

### Frontend Stack Selection
- **New features**: React/Inertia with TypeScript (mandatory)
- **Legacy maintenance**: Livewire (Purchasing module only)
- **Styling**: Tailwind CSS exclusively - NO custom CSS or Bootstrap

### Component Reuse & Code Organization
- **Reuse existing components** - check `resources/js/inertia/components/` before creating new ones
- **Keep files short** - if a file exceeds ~300 lines, split into smaller reusable components
- **Compose, don't rewrite** - extend existing components rather than duplicating code

### Security: Config & Environment
- Backend: Use `config('key')` - never `env()` directly except in config files
- Frontend: Pass only necessary data via Inertia props from controller
- **NEVER** expose API keys, secrets, or sensitive env values to client-side code

### Business Unit Context
All queries MUST filter by `current_business_unit_id` from session. Never assume global data access.

## Tailwind Styling Patterns

### Color Palette
| Purpose | Colors |
|---------|--------|
| Primary | `indigo-600`, `indigo-700` |
| Success | `emerald-100/600/700` |
| Warning | `amber-100/600/700` |
| Danger | `red-100/600/700` |
| Info | `blue-100/600/700` |
| Offline | `purple-100/600/700` |

### Component Patterns

```blade
{{-- Card --}}
<div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
    <div class="px-5 py-4 border-b border-gray-100">
        <h3 class="text-base font-semibold text-gray-900">Title</h3>
    </div>
    <div class="p-6">Content</div>
</div>

{{-- Primary Button --}}
<button class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors">
    Action
</button>

{{-- Status Badge --}}
<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700">
    Approved
</span>

{{-- Form Input --}}
<input class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
```

### Icons
- Use Heroicons (outline style) as inline SVG
- Standard sizes: `w-4 h-4` or `w-5 h-5`
- Stroke width: 1.5

## Key Packages

| Package | Purpose |
|---------|---------|
| Spatie Permission | Roles & permissions |
| Spatie Activity Log | Audit trails |
| Spatie Browsershot | PDF generation |
| SimpleSoftwareIO QR Code | QR codes |
| Laravel Boost | MCP server for AI |

## Development Commands

```bash
composer dev          # Full stack: server + queue + logs + vite
npm run dev           # Vite HMR only
npm run build         # Production build
./vendor/bin/pint     # Code style fix
php artisan test      # Run tests
```

## MCP Tools (Laravel Boost)

Use MCP tools to verify instead of assuming. Key tools:

| Tool | When to Use |
|------|-------------|
| `application_info` | First - understand environment |
| `database_schema` | Before migrations/queries |
| `list_routes` | Before creating/referencing routes |
| `tinker` | Test code snippets |
| `search_docs` | Package-specific features |
| `last_error` / `read_log_entries` | Debugging |

### MCP Workflow Example

```bash
# 1. Check environment
mcp_laravel_boost_application_info

# 2. Verify schema before migration
mcp_laravel_boost_database_schema

# 3. Test logic before implementing
mcp_laravel_boost_tinker(code: "Model::count()")

# 4. Debug errors
mcp_laravel_boost_last_error
```

### Rules
- ✅ Always verify database schema before writing migrations
- ✅ Check routes exist before referencing them
- ✅ Use tinker to validate code snippets
- ❌ Never assume package versions
- ❌ Never guess database structure
- ❌ Never hardcode config values
