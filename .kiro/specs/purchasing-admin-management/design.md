# Design Document

## Overview

The Purchasing Admin Management system extends the Oasis purchasing module to track procurement follow-up and price efficiency. When PRs/STs are approved, admin tasks are created for purchasing administrators to claim, process, and complete while tracking follow-up times and realized vs estimated prices.

## Architecture

### Folder Structure

Following the modular pattern established in the project:

```
app/
├── Models/Modules/Purchasing/Admin/
│   ├── AdminTask.php
│   └── SlaSettings.php
│
├── Services/Modules/Purchasing/Admin/
│   ├── AdminTaskService.php
│   ├── AdminTaskAssignmentService.php
│   ├── PriceEfficiencyService.php
│   └── SlaMonitoringService.php
│
├── Livewire/Modules/Purchasing/Admin/
│   ├── AdminDashboard.php
│   ├── TaskList.php
│   ├── TaskDetail.php
│   ├── PerformanceMetrics.php
│   ├── DepartmentReport.php
│   └── ConsolidatedReport.php
│
└── Notifications/Purchasing/Admin/
    ├── TaskAssigned.php
    └── SlaExceeded.php
```

### System Components

**Models:**
- `App\Models\Modules\Purchasing\Admin\AdminTask` - Task tracking model
- `App\Models\Modules\Purchasing\Admin\SlaSettings` - SLA configuration
- `App\Models\Core\Department` (extended) - Purchasing department flags
- `App\Models\Core\UserBusinessUnit` (extended) - Admin assignment

**Services:**
- `App\Services\Modules\Purchasing\Admin\AdminTaskService` - Task lifecycle management
- `App\Services\Modules\Purchasing\Admin\AdminTaskAssignmentService` - Auto-assignment logic
- `App\Services\Modules\Purchasing\Admin\PriceEfficiencyService` - Savings calculations
- `App\Services\Modules\Purchasing\Admin\SlaMonitoringService` - SLA violation detection

**Livewire Components:**
- `App\Livewire\Modules\Purchasing\Admin\AdminDashboard` - Main dashboard
- `App\Livewire\Modules\Purchasing\Admin\TaskList` - Task listing with filters
- `App\Livewire\Modules\Purchasing\Admin\TaskDetail` - Task detail and actions
- `App\Livewire\Modules\Purchasing\Admin\PerformanceMetrics` - Individual metrics
- `App\Livewire\Modules\Purchasing\Admin\DepartmentReport` - Department aggregates
- `App\Livewire\Modules\Purchasing\Admin\ConsolidatedReport` - Cross-BU reports

**Notifications:**
- `App\Notifications\Purchasing\Admin\TaskAssigned` - Task assignment notification
- `App\Notifications\Purchasing\Admin\SlaExceeded` - SLA violation alert

### Integration Points

- Event listener on PR/ST approval triggers task creation
- Leverages existing BU session context
- Extends user_business_units with is_purchasing_admin flag
- Uses existing notification infrastructure

## Components and Interfaces

### Key Models

**Department (Extended):**
- is_purchasing_department (boolean)
- default_purchasing_admin_id (FK to users)

**UserBusinessUnit (Extended):**
- is_purchasing_admin (boolean)

**AdminTask (New):**
- Polymorphic to PR/ST via taskable_type/taskable_id
- Tracks: status, timestamps, prices, savings, time metrics
- Status enum: pending_followup, in_progress, done

### Services

**AdminTaskService:** Create, start, complete tasks
**AdminTaskAssignmentService:** Auto-assign or leave unassigned based on admin count
**PriceEfficiencyService:** Calculate savings metrics
**SlaMonitoringService:** Check SLA violations and trigger alerts


## Data Models

### Database Schema

**admin_tasks table:**
```sql
id bigint PRIMARY KEY
taskable_type varchar (PR or ST model class)
taskable_id bigint
business_unit_id bigint FK
department_id bigint FK
assigned_admin_id bigint FK nullable
status enum(pending_followup, in_progress, done)
entered_at timestamp
started_at timestamp nullable
completed_at timestamp nullable
estimated_total_price decimal(15,2)
realized_total_price decimal(15,2) nullable
savings_amount decimal(15,2) nullable
savings_percentage decimal(5,2) nullable
followup_time_minutes int nullable
completion_time_minutes int nullable
notes text nullable
created_at, updated_at timestamps

INDEX(business_unit_id, status)
INDEX(assigned_admin_id, status)
INDEX(department_id, status)
INDEX(entered_at)
```

**departments table (extended):**
```sql
is_purchasing_department boolean DEFAULT false
default_purchasing_admin_id bigint FK nullable
```

**user_business_units table (extended):**
```sql
is_purchasing_admin boolean DEFAULT false
```

**sla_settings table (new):**
```sql
id bigint PRIMARY KEY
business_unit_id bigint FK nullable (null = global)
followup_sla_hours int
completion_sla_hours int
email_alerts_enabled boolean DEFAULT true
created_at, updated_at timestamps
```


## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system-essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property 1: Department flag persistence
*For any* department, when flagged as a purchasing department, querying the database should return is_purchasing_department as true
**Validates: Requirements 1.1**

### Property 2: Default admin persistence
*For any* department, when a default admin is assigned, querying the database should return the correct user ID in default_purchasing_admin_id
**Validates: Requirements 1.2**

### Property 3: Access control based on department and admin flag
*For any* user, access to purchasing admin features should be granted if and only if they have is_purchasing_admin true in a purchasing department for the current business unit
**Validates: Requirements 1.3, 2.2, 2.3, 11.1, 11.2**

### Property 4: Auto-assignment uses default admin
*For any* department with a default admin assigned, new tasks should be automatically assigned to that admin
**Validates: Requirements 1.4, 3.5**

### Property 5: Admin flag persistence
*For any* user, when assigned as purchasing admin, the is_purchasing_admin flag should be true in user_business_units
**Validates: Requirements 2.1**

### Property 6: Multi-BU admin access
*For any* user assigned as purchasing admin in multiple business units, they should have access to purchasing admin features in each assigned business unit
**Validates: Requirements 2.5**

### Property 7: Task creation on PR approval
*For any* Purchase Request that transitions to approved status, an admin task with status pending_followup should be created
**Validates: Requirements 3.1**

### Property 8: Task creation on ST approval
*For any* Stock Request that transitions to approved status, an admin task with status pending_followup should be created
**Validates: Requirements 3.2**

### Property 9: Entry timestamp recording
*For any* admin task creation, the entered_at timestamp should be set to the current time
**Validates: Requirements 3.3**

### Property 10: Estimated price copying
*For any* admin task, the estimated_total_price should equal the total_amount from the source PR or ST
**Validates: Requirements 3.4**

### Property 11: Multiple admin unassigned state
*For any* department with more than one purchasing admin, new tasks should remain unassigned (assigned_admin_id is null)
**Validates: Requirements 3.6**

### Property 12: Initial savings null state
*For any* newly created admin task, savings_amount and savings_percentage should be null
**Validates: Requirements 3.7**

### Property 13: Unassigned task visibility
*For any* purchasing admin in a business unit, they should see all unassigned tasks for that business unit
**Validates: Requirements 4.1**

### Property 14: Task claiming assignment
*For any* unassigned task, when claimed by an admin, the assigned_admin_id should be set to that admin's user ID
**Validates: Requirements 4.2**

### Property 15: Task claiming exclusivity
*For any* task, once claimed by an admin, it should not be claimable by other admins (assigned_admin_id should not change)
**Validates: Requirements 4.3**

### Property 16: Start task status and timestamp
*For any* pending task, when started by an admin, status should change to in_progress and started_at should be set to current time
**Validates: Requirements 5.1**

### Property 17: Follow-up time calculation
*For any* task transitioning to in_progress, followup_time_minutes should equal the duration between entered_at and started_at
**Validates: Requirements 5.2**

### Property 18: Completion requires realized price
*For any* task completion attempt, if realized_total_price is not provided, the status should not change to done
**Validates: Requirements 5.3**

### Property 19: Complete task status and timestamp
*For any* in_progress task, when completed with realized price, status should change to done and completed_at should be set to current time
**Validates: Requirements 5.4**

### Property 20: Completion time calculation
*For any* task transitioning to done, completion_time_minutes should equal the duration between started_at and completed_at
**Validates: Requirements 5.5**

### Property 21: Savings amount calculation
*For any* completed task, savings_amount should equal estimated_total_price minus realized_total_price
**Validates: Requirements 5.6**

### Property 22: Savings percentage calculation
*For any* completed task, savings_percentage should equal ((estimated_total_price - realized_total_price) / estimated_total_price) × 100
**Validates: Requirements 5.7**

### Property 23: PR/ST status preservation
*For any* admin task marked as done, the source PR or ST status should remain as approved
**Validates: Requirements 5.8**

### Property 24: Task grouping by status
*For any* admin viewing the dashboard, tasks should be correctly grouped into pending_followup, in_progress, and done categories
**Validates: Requirements 6.1**

### Property 25: Pending task filtering
*For any* admin, the pending view should show all unassigned tasks plus tasks assigned to that admin with pending_followup status
**Validates: Requirements 6.2**

### Property 26: In-progress task filtering
*For any* admin, the in-progress view should show only tasks assigned to that admin with in_progress status
**Validates: Requirements 6.3**

### Property 27: Completed task filtering
*For any* admin, the completed view should show only tasks completed by that admin with done status
**Validates: Requirements 6.4**

### Property 28: Date range filtering
*For any* date range filter, tasks displayed should have entered_at within the specified range
**Validates: Requirements 6.5**

### Property 29: Type filtering
*For any* PR or ST type filter, tasks displayed should match the selected taskable_type
**Validates: Requirements 6.6**

### Property 30: Admin completed task count
*For any* admin, the total tasks completed metric should equal the count of tasks with status done and assigned_admin_id matching that admin
**Validates: Requirements 7.1**

### Property 31: Admin average follow-up time
*For any* admin, the average follow-up time should equal the mean of followup_time_minutes across all their completed tasks
**Validates: Requirements 7.2**

### Property 32: Admin average completion time
*For any* admin, the average completion time should equal the mean of completion_time_minutes across all their completed tasks
**Validates: Requirements 7.3**

### Property 33: Admin total savings
*For any* admin, the total savings amount should equal the sum of savings_amount across all their completed tasks
**Validates: Requirements 7.4**

### Property 34: Admin average savings percentage
*For any* admin, the average savings percentage should equal the mean of savings_percentage across all their completed tasks
**Validates: Requirements 7.5**

### Property 35: Department total savings
*For any* department, the total savings should equal the sum of savings_amount across all completed tasks in that department
**Validates: Requirements 8.2**

### Property 36: Department average follow-up time
*For any* department, the average follow-up time should equal the mean of followup_time_minutes across all completed tasks in that department
**Validates: Requirements 8.3**

### Property 37: Department average completion time
*For any* department, the average completion time should equal the mean of completion_time_minutes across all completed tasks in that department
**Validates: Requirements 8.4**

### Property 38: Business unit total savings
*For any* business unit, the total savings should equal the sum of savings_amount across all completed tasks in that business unit
**Validates: Requirements 9.2**

### Property 39: Business unit average savings percentage
*For any* business unit, the average savings percentage should equal the mean of savings_percentage across all completed tasks in that business unit
**Validates: Requirements 9.3**

### Property 40: Business unit task count
*For any* business unit, the total tasks completed should equal the count of tasks with status done in that business unit
**Validates: Requirements 9.7**

### Property 41: SLA configuration persistence
*For any* SLA configuration, the followup_sla_hours and completion_sla_hours should be correctly stored in the database
**Validates: Requirements 10.1, 10.2**

### Property 42: SLA alert conditional sending
*For any* task exceeding SLA targets, email alerts should be sent if and only if email_alerts_enabled is true
**Validates: Requirements 10.3, 10.4**

### Property 43: Super admin full access
*For any* super admin user, they should have access to purchasing admin features across all business units
**Validates: Requirements 11.3**

### Property 44: Parent BU top management consolidated access
*For any* top management user in the parent business unit, they should have access to consolidated reports
**Validates: Requirements 11.4**

### Property 45: BU switch access update
*For any* user switching business units, menu visibility should update based on their is_purchasing_admin status in the new business unit
**Validates: Requirements 11.5**

### Property 46: Super admin audit access
*For any* super admin, audit history should include all admin task activities across all business units
**Validates: Requirements 12.1**

### Property 47: Department manager scoped audit access
*For any* department manager, audit history should include only admin task activities for their department
**Validates: Requirements 12.2**

### Property 48: Admin personal history access
*For any* purchasing admin, their task history should include all tasks where assigned_admin_id equals their user ID
**Validates: Requirements 12.3**


## Error Handling

### Validation Errors
- Task claiming: Verify task is unassigned before allowing claim
- Task start: Verify task is pending and assigned to requesting admin
- Task completion: Verify realized price is positive and task is in_progress
- Admin assignment: Verify user exists in purchasing department for target BU

### Business Logic Errors
- Prevent status transitions that skip states (pending → done without in_progress)
- Prevent reassignment of claimed tasks
- Prevent completion without realized price input
- Handle division by zero in savings percentage when estimated price is zero

### Concurrency Handling
- Use database transactions for task claiming to prevent double-assignment
- Lock tasks during status transitions
- Handle race conditions in auto-assignment logic

### Error Messages
- User-friendly messages for validation failures
- Detailed logging for system errors
- Email notifications for SLA violations include task details

## Testing Strategy

### Unit Testing
- Test calculation functions (savings amount, savings percentage, time durations)
- Test access control logic (isPurchasingAdmin, canAccessConsolidatedReports)
- Test assignment logic (auto-assign vs manual claim decision)
- Test SLA violation detection
- Test filtering and scoping queries

### Property-Based Testing
Property-based testing will be implemented using **Pest PHP** with the **pest-plugin-faker** for data generation. Each property test will run a minimum of 100 iterations with randomized inputs.

**Test Tagging Convention:**
Each property-based test must include a comment tag in this exact format:
```php
// Feature: purchasing-admin-management, Property {number}: {property_text}
```

**Key Properties to Test:**
- Property 10: Estimated price copying - Generate random PRs/STs, verify task estimated price matches source
- Property 17: Follow-up time calculation - Generate random timestamps, verify calculation accuracy
- Property 21: Savings amount calculation - Generate random prices, verify savings = estimated - realized
- Property 22: Savings percentage calculation - Generate random prices, verify percentage formula
- Property 31: Admin average follow-up time - Generate random task sets, verify mean calculation
- Property 34: Admin average savings percentage - Generate random completed tasks, verify mean calculation

### Integration Testing
- Test PR approval → task creation flow
- Test ST approval → task creation flow
- Test task lifecycle: create → claim → start → complete
- Test email notification sending on SLA violations
- Test business unit switching with access control updates
- Test multi-admin claiming race conditions

### UI Testing
- Test dashboard displays correct task counts by status
- Test filtering by date range and type
- Test performance metrics calculations display correctly
- Test charts render with correct data
- Test access control hides/shows menu based on permissions


## Frontend Architecture & Compatibility

### Livewire Integration

**Business Unit Switcher Compatibility:**
All Livewire components must listen to the `business-unit-switched` event to maintain compatibility with the existing BusinessUnitSwitcher component:

```php
use Livewire\Attributes\On;

class AdminDashboard extends Component
{
    public $activeBusinessUnitId;
    
    public function mount(): void
    {
        $this->activeBusinessUnitId = session('current_business_unit_id');
    }
    
    #[On('business-unit-switched')]
    public function handleBusinessUnitSwitch($businessUnitId): void
    {
        $this->activeBusinessUnitId = $businessUnitId;
        $this->resetPage(); // Reset pagination
        // Refresh data without page reload
    }
}
```

**Lazy Loading Pattern:**
Use the existing `HasLazyLoading` trait for performance:

```php
use App\Livewire\Traits\HasLazyLoading;

class TaskList extends Component
{
    use HasLazyLoading, WithPagination;
    
    public function render()
    {
        if (!$this->readyToLoad) {
            return view('livewire.modules.purchasing.admin.task-list', [
                'tasks' => new LengthAwarePaginator([], 0, 10)
            ]);
        }
        
        return view('livewire.modules.purchasing.admin.task-list', [
            'tasks' => $this->getTasks()
        ]);
    }
}
```

**Real-time Updates:**
Use Livewire events for real-time updates without page refresh:

```php
// After task claimed
$this->dispatch('task-claimed', taskId: $task->id);

// After task completed
$this->dispatch('task-completed', taskId: $task->id);

// Refresh dashboard metrics
$this->dispatch('refresh-metrics');
```

### Responsive Design with REM Units

**Tailwind Configuration:**
All spacing, font sizes, and dimensions must use REM-based Tailwind classes for responsive design:

```blade
{{-- Card Container --}}
<div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
    {{-- Header with REM-based padding --}}
    <div class="px-5 py-4 border-b border-gray-100">
        <h3 class="text-base font-semibold text-gray-900">Admin Tasks</h3>
    </div>
    
    {{-- Content with REM-based spacing --}}
    <div class="p-6 space-y-4">
        {{-- Task cards with responsive sizing --}}
        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 truncate">PR-WNS-2024-001</p>
                <p class="text-xs text-gray-500 mt-1">Entered 2 hours ago</p>
            </div>
            <button class="ml-4 px-4 py-2 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                Claim Task
            </button>
        </div>
    </div>
</div>
```

**Responsive Breakpoints:**
```blade
{{-- Mobile-first responsive design --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
    {{-- Metric cards --}}
    <div class="bg-white p-6 rounded-xl border border-gray-100">
        <p class="text-sm text-gray-500">Total Savings</p>
        <p class="text-2xl font-bold text-gray-900 mt-2">Rp 15,000,000</p>
    </div>
</div>

{{-- Responsive table --}}
<div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">Task</th>
                <th class="hidden md:table-cell px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                <th class="hidden lg:table-cell px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">Savings</th>
            </tr>
        </thead>
    </table>
</div>
```

**Chart.js Integration:**
Charts must be responsive and use REM-based sizing:

```blade
<div class="bg-white p-6 rounded-xl border border-gray-100">
    <h3 class="text-base font-semibold text-gray-900 mb-4">Savings Trend</h3>
    <div class="relative" style="height: 20rem;">
        <canvas id="savingsTrendChart"></canvas>
    </div>
</div>

<script>
    // Chart.js with responsive config
    new Chart(ctx, {
        type: 'line',
        data: chartData,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            // ... other options
        }
    });
</script>
```

### Navigation Integration

**Sidebar Menu:**
Add purchasing admin menu item that respects existing sidebar structure:

```blade
{{-- In resources/views/livewire/layout/sidebar.blade.php --}}
@if($canAccessPurchasingAdmin)
<li>
    <a href="{{ route('purchasing.admin.dashboard') }}" 
       class="flex items-center px-4 py-2.5 text-sm {{ request()->routeIs('purchasing.admin.*') ? 'bg-indigo-50 text-indigo-600' : 'text-gray-700 hover:bg-gray-50' }} rounded-lg transition-colors">
        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
        </svg>
        Purchasing Admin
    </a>
</li>
@endif
```

**Access Control Gate:**
```php
// In AppServiceProvider
Gate::define('access-purchasing-admin', function (User $user) {
    $currentBuId = session('current_business_unit_id');
    
    // Super Admin
    if ($user->isSuperAdmin()) {
        return true;
    }
    
    // Top Management in Parent BU
    $isTopManagementParent = $user->businessUnits()
        ->whereHas('businessUnit', fn($q) => $q->whereNull('parent_id'))
        ->whereHas('position', fn($q) => $q->whereIn('access_level', [1, 2]))
        ->exists();
    
    if ($isTopManagementParent) {
        return true;
    }
    
    // Purchasing Admin in current BU
    return $user->businessUnits()
        ->where('business_unit_id', $currentBuId)
        ->where('is_purchasing_admin', true)
        ->whereHas('department', fn($q) => $q->where('is_purchasing_department', true))
        ->exists();
});
```

### Toast Notifications

Use existing toast helper for user feedback:

```php
// In Livewire component
$this->dispatch('toast', [
    'type' => 'success',
    'message' => 'Task claimed successfully'
]);

$this->dispatch('toast', [
    'type' => 'error',
    'message' => 'Failed to complete task. Please provide realized price.'
]);
```

