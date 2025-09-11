# Project Structure

## Laravel Standard Structure

This project follows Laravel's conventional directory structure with some domain-specific organization.

## Key Directories

### `/app` - Application Logic
- **`/Console/Commands`** - Custom Artisan commands
- **`/Http/Controllers`** - Request handling logic
  - **`/Admin`** - Admin panel controllers (UserManagementController, DashboardController, etc.)
- **`/Http/Middleware`** - Custom middleware
  - `AdminAccess.php` - Custom admin access control
  - `EnsureBusinessUnitSelected.php` - Business unit validation
  - `CheckBusinessUnitAccess.php` - Business unit access control
- **`/Livewire`** - Livewire components organized by feature
  - **`/Layout`** - Layout components (Sidebar, etc.)
  - **`/PurchaseRequests`** - Purchase request related components
  - **`/Components`** - Reusable UI components
- **`/Models`** - Eloquent models
  - **`/Modules/WNS`** - Business domain models
- **`/Services`** - Business logic services
- **`/View/Components`** - Blade components

### `/resources` - Frontend Assets
- **`/views`** - Blade templates
  - **`/admin`** - Admin panel views organized by feature
    - **`/users`** - User management views (index, create, edit, show)
    - **`/business-units`** - Business unit management
    - **`/departments`** - Department management
  - **`/livewire`** - Livewire component views
  - **`/components`** - Reusable view components
- **`/css`** - Stylesheets
- **`/js`** - JavaScript files

### `/database` - Database Related
- **`/migrations`** - Database schema migrations
- **`/seeders`** - Database seeders
- **`/factories`** - Model factories for testing

### `/tests` - Test Suite
- **`/Feature`** - Feature tests
- **`/Unit`** - Unit tests

## Naming Conventions

### Controllers
- Admin controllers: `Admin/[Feature]Controller.php`
- Resource controllers follow Laravel conventions (index, create, store, show, edit, update, destroy)

### Models
- PascalCase: `User.php`, `BusinessUnit.php`
- Domain models in `/Modules/WNS/` for business-specific entities

### Livewire Components
- Organized by feature area
- Class names: PascalCase (`RequestNumber.php`)
- View names: kebab-case (`request-number.blade.php`)

### Views
- Admin views: `/admin/[feature]/[action].blade.php`
- Livewire views: `/livewire/[feature]/[component].blade.php`

### Middleware
- PascalCase class names
- Kebab-case aliases in `bootstrap/app.php`

## Architecture Patterns

### Role-Based Access Control
- Uses Spatie Permission package for roles and permissions
- Custom middleware for business-specific access control
- Global roles stored in `users.global_role` field

### Business Unit Hierarchy
- Users belong to business units
- Business units contain departments
- Departments have positions
- Access control based on business unit assignments

### Activity Logging
- Uses Spatie Activity Log for audit trails
- Applied to critical models and actions

### Livewire Component Organization
- Feature-based organization
- Separate directories for different functional areas
- Reusable components in `/Components` directory