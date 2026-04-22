# OASIS - Office Administration System

**Werkudara Group - Enterprise Office Administration Platform**

A modern Laravel 12 application with Livewire 3 and React/Inertia for managing enterprise office administration workflows with multi-level approval systems and universal business unit support.

## System Overview

OASIS (Office Administration System) is an enterprise-grade platform designed for multi-business unit operations under Werkudara Group. It provides modular architecture supporting various business processes including purchasing, activity tracking, and sales CRM.

### Business Units

- **WG** (Werkudara Group) - Parent holding company
- **WNS** (Werkudara Nirwana Sakti)
- **UK** (Utama Kalapana)
- **MRP** (Maharaja Pratama)

## Modules

### 1. Purchasing Module

Full-featured purchase and stock request management with automated approval workflows.

**Features:**
- Purchase Request (PR) management with multi-level approvals
- Stock Request (ST) management
- Sequential numbering with business unit-specific formats
- QR code verification for document tracking
- Offline approval support for paper-based workflows
- Email notifications for approval requests
- Item-level tracking with images and specifications

### 2. Activity Tracking Module

Employee task and workload management with collaborative features.

**Features:**
- Create and track work activities/tasks
- Collaborative tasks with multiple participants
- Activity types and sub-activities configuration
- Personal, department, and business unit analytics
- Date range filtering for all analytics views

### 3. Activity Reporting Dashboard v2.0 (NEW)

Advanced multi-level reporting and analytics dashboard for productivity monitoring.

**Key Features:**

#### Multi-Level Dashboards
- **BOD Dashboard**: Aggregated metrics across all Business Units for strategic decision-making
- **Manager Dashboard**: Detailed team metrics with workload heatmaps and validation queues
- **Employee Dashboard**: Personal productivity stats and task tracking

#### Strategic Focus Visualization
- Treemap chart showing activity type distribution
- Drill-down capability to Business Unit breakdown
- Warning indicators for activities consuming >40% of total time
- Period comparison with color-coded changes

#### Workload Heatmap
- Visual grid with employees as rows and weeks as columns
- Color-coded workload scores based on task count, priority, and overdue status
- Rich tooltips with task breakdown and top activities
- Department grouping toggle for better organization

#### Gaming Prevention System
- Automatic flagging of suspicious task patterns
- Duration outlier detection (>12 hours)
- Pattern detection for identical durations
- Statistical outlier detection using Z-score analysis
- Manager validation queue for reviewing flagged tasks

#### Auto-Logging System
- Automatic task creation from system events (PR approvals, document uploads)
- Configurable auto-log rules mapping events to activity types
- Visual distinction between manual and auto-logged tasks

#### Cross-Functional Employee Support
- Allocation percentage tracking for employees across multiple BUs
- Weighted contribution calculations for accurate metrics
- Per-BU breakdown for cross-functional employees

#### Team Availability Indicator
- Real-time availability status (Available, Busy, DND, Offline)
- Automatic offline status outside working hours
- Privacy protection for DND status
- Audit logging for compliance

### 4. Sales CRM Module

Customer relationship management for sales operations.

## Architecture

### Technology Stack

- **Laravel Framework**: 12.26.3
- **PHP**: 8.2+
- **Livewire**: 3.6.4 (reactive components)
- **React/Inertia**: For Activity Reporting dashboards
- **Tailwind CSS**: 3.x (utility-first styling)
- **Alpine.js**: 3.x (client-side interactions)
- **Recharts**: 3.6.0 (data visualization)
- **Framer Motion**: 12.26.0 (animations)
- **Spatie Permission**: Role and permission management
- **Spatie Activity Log**: Comprehensive audit trails

### Key Services

- **ReportingService**: Multi-level dashboard data with role-based access
- **MetricsCalculationService**: Utilization rates and workload scores
- **GamingPreventionService**: Task validation and outlier detection
- **AutoLogService**: Event-driven automatic task logging
- **ApprovalWorkflowService**: Rule-based approval routing
- **NumberingService**: Business unit-specific sequential numbering
- **QrCodeService**: PDF verification and tracking

## Quick Start

### Prerequisites

- PHP 8.2+
- Composer
- Node.js & NPM
- MySQL database

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

### Development Mode

```bash
# Run all development services concurrently
composer dev

# This starts:
# - PHP development server
# - Queue worker
# - Vite HMR server
# - Real-time log viewer on non-Windows machines
```

On Windows, `composer dev` now skips `php artisan pail` automatically because `laravel/pail` requires `pcntl`, which is not available in the standard XAMPP PHP build. The dev startup wrappers also clear stale `public/hot` files before startup and after shutdown so OpenCode and production-like environments do not keep pointing at a dead Vite server.

## Project Structure

```
app/
├── Console/Commands/           # Artisan commands
├── Events/Activity/            # Activity module events
├── Http/
│   ├── Controllers/Modules/    # Module controllers
│   │   └── Activity/           # Activity Reporting API
│   └── Middleware/             # HTTP middleware
├── Listeners/Activity/         # Auto-logging event listeners
├── Livewire/                   # Livewire components
│   └── Modules/                # Business module components
├── Models/
│   ├── Core/                   # Core system models
│   └── Modules/                # Business module models
│       └── Activity/           # Activity module models
├── Services/
│   ├── Core/                   # Core services
│   └── Modules/                # Module-specific services
│       └── Activity/           # Activity reporting services
└── View/Components/            # Blade components

resources/
├── js/inertia/                 # React/Inertia components
│   └── Pages/Activity/         # Activity Reporting dashboards
│       └── Reporting/          # BOD, Manager, Employee dashboards
└── views/                      # Blade templates
```

## API Endpoints

### Activity Reporting API

```
GET  /api/activity/dashboard              # Role-based dashboard data
GET  /api/activity/business-units/metrics # Business unit metrics (BOD only)
GET  /api/activity/strategic-focus        # Strategic focus treemap data
GET  /api/activity/workload-heatmap       # Workload heatmap data
GET  /api/activity/validations            # Validation queue (Managers)
POST /api/activity/validations/{id}/approve
POST /api/activity/validations/{id}/reject
```

## Documentation

- **Technical Docs**: `docs/` directory
- **Activity Module**: `docs/activity-module/`
  - `database-schema.md` - Database structure
  - `api-reference.md` - API documentation
  - `service-layer-architecture.md` - Service design
- **Quick Start**: `QUICK-START.md`
- **Index**: `docs/INDEX.md`

## Testing

```bash
# Run all tests
composer test

# Run specific test suite
php artisan test --filter=ActivityReporting
```

## CI/CD (GitHub Actions + VPS)

This repository now includes two workflows:

- `ci` (`.github/workflows/ci.yml`)
  - Runs on push and pull request to `main` and `v4-beta`
  - Executes Laravel migrations + tests
  - Verifies frontend build (`npm run build`)

- `deploy-vps` (`.github/workflows/deploy-vps.yml`)
  - Auto deploys on push to `main`
  - Also supports manual deploy via `workflow_dispatch`
  - Pulls latest code on VPS, installs dependencies, runs migration, and optimizes caches

### Required GitHub Secrets

Set these in repository secrets before enabling deployment:

- `VPS_HOST` - VPS IP/domain
- `VPS_USER` - SSH user with deploy permission
- `VPS_SSH_KEY` - private SSH key content (recommended dedicated deploy key)
- `VPS_APP_PATH` - absolute project path on VPS (example: `/var/www/numbering`)

### VPS Requirements

- Project already cloned on VPS at `VPS_APP_PATH`
- `git`, `php`, `composer` installed and available in PATH
- `npm` installed if frontend build is executed on VPS
- Correct `.env` already present on VPS
- Queue worker managed by Supervisor (recommended)

## Contributing

Please review the contribution guidelines before submitting pull requests.

## Security

If you discover a security vulnerability, please report it responsibly.

## License

This project is proprietary software for Werkudara Group.
