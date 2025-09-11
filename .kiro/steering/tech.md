# Technology Stack

## Backend Framework
- **Laravel 12** - PHP web application framework
- **PHP 8.2+** - Required minimum version
- **SQLite** - Database (development), supports MySQL/PostgreSQL for production

## Frontend Stack
- **Livewire 3.6+** - Full-stack framework for dynamic interfaces
- **Alpine.js 3.14+** - Lightweight JavaScript framework
- **Tailwind CSS 3.1+** - Utility-first CSS framework
- **Vite 7.0+** - Build tool and dev server

## Key Packages
- **Spatie Laravel Permission 6.21** - Role and permission management
- **Spatie Laravel Activity Log 4.10** - User activity tracking
- **Laravel Breeze 2.3** - Authentication scaffolding
- **Livewire Volt 1.7** - Single-file Livewire components

## Development Tools
- **Laravel Pint** - Code style fixer
- **PHPUnit 11.5+** - Testing framework
- **Laravel Debugbar** - Debug toolbar
- **Laravel IDE Helper** - IDE autocompletion
- **Laravel Sail** - Docker development environment

## Common Commands

### Development
```bash
# Start development server with all services
composer dev

# Individual services
php artisan serve          # Web server
php artisan queue:listen   # Queue worker
php artisan pail          # Log viewer
npm run dev               # Vite dev server
```

### Testing
```bash
composer test             # Run all tests
php artisan test         # Alternative test command
```

### Code Quality
```bash
./vendor/bin/pint        # Fix code style
php artisan ide-helper:generate  # Generate IDE helpers
```

### Database
```bash
php artisan migrate      # Run migrations
php artisan db:seed      # Run seeders
php artisan migrate:fresh --seed  # Fresh database with seed data
```

### Cache & Optimization
```bash
php artisan config:clear # Clear config cache
php artisan route:clear  # Clear route cache
php artisan view:clear   # Clear view cache
```