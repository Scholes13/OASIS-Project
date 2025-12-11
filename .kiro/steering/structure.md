# Project Structure & Conventions

## Architecture Pattern

**Universal Multi-Business Unit Architecture**: Single route structure with dynamic business unit context switching. All modules support multiple business units through session-based context.

## Directory Organization

### Application Layer (`app/`)

```
app/
├── Console/Commands/          # Artisan commands
├── Http/
│   ├── Controllers/          # RESTful controllers (traditional routes)
│   └── Middleware/           # HTTP middleware
├── Livewire/                 # Livewire components (primary UI)
│   ├── Actions/             # Single-action components (e.g., Logout)
│   ├── Components/          # Reusable UI components (e.g., BusinessUnitSwitcher)
│   ├── Dashboard/           # Dashboard-specific components
│   ├── Forms/               # Form components (e.g., LoginForm)
│   ├── Layout/              # Layout components (Sidebar, UserMenu)
│   ├── Modules/             # Business module components
│   │   ├── Purchasing/      # Purchasing module (PR, ST)
│   │   │   ├── PurchaseRequest/
│   │   │   └── StockRequest/
│   │   └── SalesCrm/
│   └── Traits/              # Reusable component traits
│       ├── HasFilters.php
│       └── HasLazyLoading.php
├── Models/
│   ├── Core/                # Core system models (User, BusinessUnit, Department)
│   └── Modules/             # Business module models
│       ├── Purchasing/      # Purchasing module models
│       │   ├── PurchaseRequest/
│       │   └── StockRequest/
│       └── SalesCrm/
├── Notifications/           # Email/notification classes
│   └── Purchasing/          # Purchasing module notifications
│       ├── PurchaseRequest/
│       └── StockRequest/
├── Providers/
│   ├── AppServiceProvider.php    # Dynamic mailer, Gates
│   └── VoltServiceProvider.php
├── Services/                # Business logic services
│   ├── Core/               # Core services (NumberingService, QrCodeService)
│   └── Modules/            # Module-specific services
│       ├── Purchasing/     # Purchasing module services
│       │   ├── PurchaseRequest/
│       │   └── StockRequest/
│       └── SalesCrm/
└── View/Components/         # Blade components
```

### Resources Layer (`resources/`)

```
resources/
├── css/
│   └── app.css             # Tailwind entry point
├── js/
│   ├── app.js              # Alpine.js bootstrap
│   ├── bootstrap.js        # Axios, Echo setup
│   └── toast-helpers.js    # Toast notification helpers
└── views/
    ├── admin/              # Admin panel views
    ├── components/         # Blade components
    ├── emails/             # Email templates
    │   ├── layouts/        # Email layout templates
    │   └── purchasing/     # Purchasing module emails
    │       ├── purchase-request/
    │       └── stock-request/
    ├── layouts/            # Layout templates
    ├── livewire/           # Livewire component views
    │   └── modules/        # Module-specific Livewire views
    │       └── purchasing/ # Purchasing module Livewire views
    │           ├── purchase-request/
    │           └── stock-request/
    ├── pdf/                # PDF templates
    ├── purchasing/         # Purchasing module views
    │   ├── purchase-requests/
    │   ├── stock-requests/
    │   └── approvals/
    │       ├── purchase-request/
    │       └── stock-request/
    ├── reports/            # Report views
    └── dashboard.blade.php
```

### Database Layer (`database/`)

```
database/
├── factories/              # Model factories
│   ├── Models/            # Module-specific factories
│   ├── BusinessUnitFactory.php
│   └── UserFactory.php
├── migrations/
│   ├── modules/           # Module-specific migrations
│   └── README-INDEX-STANDARDS.md  # Index naming conventions
└── seeders/               # Database seeders
    ├── DatabaseSeeder.php
    ├── SuperAdminSeeder.php
    └── [Module]Seeder.php
```

### Configuration (`config/`)

- `app.php` - Application config (timezone: Asia/Jakarta)
- `approval.php` - Approval workflow configuration
- `boost.php` - Laravel Boost MCP server config
- `livewire.php` - Livewire configuration
- `notification.php` - Notification settings
- `permission.php` - Spatie Permission config

### Routes (`routes/`)

- `web.php` - Universal web routes (supports all business units)
- `api.php` - RESTful API routes

## Naming Conventions

### Models
- **Core Models**: `App\Models\Core\[ModelName]`
- **Module Models**: `App\Models\Modules\[ModuleName]\[SubModule]\[ModelName]`
- **Purchasing Models**: `App\Models\Modules\Purchasing\PurchaseRequest\*` or `App\Models\Modules\Purchasing\StockRequest\*`
- Use singular names: `PurchaseRequest`, `User`, `Department`

### Livewire Components
- **Namespace**: `App\Livewire\[Category]\[ComponentName]`
- **Class**: PascalCase (e.g., `PurchaseRequestIndex`)
- **View**: kebab-case (e.g., `purchase-request-index.blade.php`)
- **Location**: `resources/views/livewire/[category]/[component-name].blade.php`

### Services
- **Namespace**: `App\Services\[Core|Modules]\[ServiceName]`
- **Naming**: `[Purpose]Service.php` (e.g., `ApprovalWorkflowService`)
- **Pattern**: Dependency injection via constructor

### Database
- **Tables**: snake_case, plural (e.g., `purchase_requests`, `pr_items`)
- **Foreign Keys**: `[table]_id` (e.g., `business_unit_id`)
- **Pivot Tables**: alphabetical order (e.g., `user_business_units`)
- **Indexes**: See `database/migrations/README-INDEX-STANDARDS.md`

### Routes
- **Pattern**: `/[resource]/[action]` (e.g., `/purchase-requests/create`)
- **Universal**: Single route definition for all business units
- **Business Unit Context**: Via session (`current_business_unit_id`)

## Code Patterns

### Livewire Components

```php
namespace App\Livewire\Modules\Purchasing\[SubModule];

use App\Livewire\Traits\HasLazyLoading;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\On;

class ComponentName extends Component
{
    use HasLazyLoading, WithPagination;
    
    public $activeBusinessUnitId;
    
    public function mount(): void
    {
        $this->activeBusinessUnitId = session('current_business_unit_id');
    }
    
    #[On('business-unit-switched')]
    public function handleBusinessUnitSwitch($businessUnitId): void
    {
        $this->activeBusinessUnitId = $businessUnitId;
        $this->resetPage();
    }
    
    public function render()
    {
        if (!$this->readyToLoad) {
            return view('livewire.modules.purchasing.[sub-module].[view-name]', ['data' => new LengthAwarePaginator([], 0, 10)]);
        }
        
        return view('livewire.modules.purchasing.[sub-module].[view-name]', ['data' => $this->getData()]);
    }
}
```

### Models

```php
namespace App\Models\Modules\Purchasing\[SubModule];

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class ModelName extends Model
{
    use LogsActivity;
    
    protected $fillable = [...];
    
    protected $casts = [
        'date_field' => 'date',
        'datetime_field' => 'datetime',
        'json_field' => 'array',
        'boolean_field' => 'boolean',
        'decimal_field' => 'decimal:2',
    ];
    
    // Relationships
    public function relation(): BelongsTo|HasMany
    {
        return $this->belongsTo(RelatedModel::class);
    }
    
    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
    
    // Activity Log
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
```

### Services

```php
namespace App\Services\Modules\Purchasing\[SubModule];

class ServiceName
{
    public function __construct(
        protected DependencyClass $dependency
    ) {}
    
    public function performAction(array $data): Result
    {
        // Business logic here
    }
}
```

## Business Unit Context

### Session Keys
- `current_business_unit_id` - Active business unit ID
- `current_business_unit_name` - Active business unit name
- `current_business_unit_code` - Active business unit code

### Switching Context
- Component: `BusinessUnitSwitcher` (Livewire)
- Event: `business-unit-switched` (dispatched globally)
- Listeners: All module components listen via `#[On('business-unit-switched')]`

## Authorization

### Gates (defined in AppServiceProvider)
- `view-reports` - Top management only (General Manager, Director, CEO, Finance Manager, Super Admin)

### Permissions (Spatie)
- Managed via `spatie/laravel-permission`
- Assigned to roles and users
- Checked via `$user->can('permission-name')`

### Super Admin
- Method: `$user->isSuperAdmin()`
- Bypasses all authorization checks

## Activity Logging

- Package: Spatie Activity Log
- Trait: `LogsActivity` on models
- Configuration: `logAll()`, `logOnlyDirty()`, `dontSubmitEmptyLogs()`
- Access: `$model->activities` relationship

## Documentation

- **Technical Docs**: `docs/` directory
- **Bug Fixes**: `docs/bug-fixes/`
- **Tasks**: `docs/tasks/`
- **Quick Start**: `QUICK-START.md`
- **Index**: `docs/INDEX.md`

## Testing

- **Location**: `tests/Feature/`, `tests/Unit/`
- **Framework**: PHPUnit 11.5+
- **Base Class**: `Tests\TestCase`
- **Factories**: `database/factories/`
