# Purchase Request Management System

**WNS (Werkudara Nusantara Sejahtera) - Universal Purchase Request System**

A modern Laravel 12 application with Livewire 3 for managing enterprise purchase requests with multi-level approval workflows and universal business unit support.

## System Overview

This is an enterprise **Purchase Request Management System** designed specifically for multi-business unit operations with hierarchical approval workflows.

### Core Features

- **Universal Routing System**: Single route structure supporting multiple business units (WNS, UKA, WG)
- **Multi-Level Approval Workflow**: Automatic approval routing based on amount thresholds
- **Sequential PR Numbering**: Business unit-specific numbering with proper sequence management  
- **QR Code Integration**: PDF verification and tracking system
- **Real-time Livewire Interface**: Modern, responsive UI with instant feedback
- **Role-Based Access Control**: Hierarchical permission system with Spatie Permission

### Architecture

- **Laravel Framework**: 12.26.3
- **Livewire**: 3.6.4 for reactive components
- **Tailwind CSS**: 3.x for modern styling
- **Alpine.js**: 3.x for client-side interactions
- **DomPDF**: PDF generation with QR codes
- **Spatie Permission**: Role and permission management

### Key Services

- **UniversalPRNumberingService**: Centralized PR numbering across all business units
- **ApprovalWorkflowService**: Core approval engine with rule-based approver assignment  
- **QrCodeService**: PDF verification and tracking system

## Quick Start

### Prerequisites

- PHP 8.2+
- Composer
- Node.js & NPM
- MySQL/SQLite database

### Installation

```bash
# Clone repository
git clone https://github.com/Scholes13/Numbering.git
cd Numbering

# Install dependencies
composer install
npm install

# Environment setup
cp .env.example .env
php artisan key:generate

# Database setup
php artisan migrate
php artisan db:seed

# Build assets
npm run build

# Start development server
php artisan serve
```

## Project Structure

### Universal Architecture

The system uses a universal architecture where:
- Single route definitions support all business units
- Dynamic component loading based on business unit context  
- Centralized services with business unit awareness
- Unified approval workflow with customizable rules

### Core Components

```
app/
├── Livewire/Modules/WNS/           # Business unit specific components
├── Services/                       # Core business services
│   ├── UniversalPRNumberingService.php
│   └── Modules/WNS/ApprovalWorkflowService.php
├── Models/Modules/WNS/             # Domain models
└── Http/Controllers/               # RESTful API endpoints

resources/views/
├── purchase-requests/              # Universal templates
├── livewire/modules/wns/           # Business unit specific views
└── layouts/                        # Application layouts

routes/
├── web.php                         # Universal web routes
└── api.php                         # RESTful API routes
```

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
