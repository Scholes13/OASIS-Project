# Design Document

## Overview

Employee Task Tracking Module menggunakan shared task model dimana satu task bisa dikerjakan bersama-sama oleh multiple users. Semua participant berbagi status dan timestamp yang sama.

## Architecture

### Module Structure (Following Existing Convention)

```
app/
├── Models/Modules/Activity/
│   ├── EmployeeTask.php
│   ├── TaskParticipant.php
│   ├── TaskAttachment.php
│   ├── ActivityType.php
│   └── SubActivity.php
├── Livewire/Modules/Activity/
│   ├── TaskIndex.php
│   ├── TaskForm.php
│   ├── TaskDetail.php
│   ├── DepartmentTasks.php
│   └── Analytics/
│       ├── PersonalDashboard.php
│       ├── DepartmentAnalytics.php
│       └── BusinessUnitAnalytics.php
├── Services/Modules/Activity/
│   └── TaskService.php
└── Http/Controllers/Admin/
    ├── ActivityTypeController.php
    └── SubActivityController.php

resources/views/livewire/modules/activity/
├── task-index.blade.php
├── task-form.blade.php
├── task-detail.blade.php
├── department-tasks.blade.php
└── analytics/
    ├── personal-dashboard.blade.php
    ├── department-analytics.blade.php
    └── business-unit-analytics.blade.php

database/migrations/modules/activity/
├── 2025_12_29_100000_create_activity_types_table.php
├── 2025_12_29_100001_create_sub_activities_table.php
├── 2025_12_29_100002_create_employee_tasks_table.php
├── 2025_12_29_100003_create_task_participants_table.php
└── 2025_12_29_100004_create_task_attachments_table.php
```

## Business Unit Switcher Integration (Orchestrator Pattern)

Setiap Livewire component HARUS mengikuti pattern ini untuk handle business unit switching:

```php
// 1. Listen to business-unit-switched event
protected $listeners = [
    'business-unit-switched' => 'handleBusinessUnitSwitch',
];

// 2. Track session BU ID
public $businessUnitId;

public function mount(): void
{
    $this->businessUnitId = session('current_business_unit_id');
}

// 3. Handle BU switch - session is single source of truth
public function handleBusinessUnitSwitch($businessUnitId): void
{
    // Update session FIRST (single source of truth)
    session(['current_business_unit_id' => $businessUnitId]);
    
    // Update property
    $this->businessUnitId = $businessUnitId;
    
    // Reload data
    $this->resetPage();
    $this->clearCache();
    
    // ✅ ORCHESTRATOR: Acknowledge completion
    $this->dispatch('bu-switch-acknowledge', component: 'activity-tasks');
    
    $buName = session('current_business_unit_name', 'new business unit');
    $this->dispatch('notify', message: "Switched to {$buName}", type: 'success');
}

// 4. Hydrate - re-check BU after each request
public function hydrate(): void
{
    $sessionBuId = session('current_business_unit_id');
    if ($this->businessUnitId != $sessionBuId) {
        $this->businessUnitId = $sessionBuId;
        // Trigger data reload
    }
}
```

## Database Design

### ERD

```
┌──────────────────┐       ┌──────────────────┐       ┌──────────────────┐
│  business_units  │       │      users       │       │   departments    │
└────────┬─────────┘       └────────┬─────────┘       └────────┬─────────┘
         │                          │                          │
         ▼                          ▼                          ▼
┌────────────────────────────────────────────────────────────────────────────┐
│                         employee_tasks                                      │
├────────────────────────────────────────────────────────────────────────────┤
│ id (PK)                                                                     │
│ business_unit_id (FK)                                                       │
│ department_id (FK)                                                          │
│ created_by (FK → users)                                                     │
│ activity_type_id (FK)                                                       │
│ sub_activity_id (FK, nullable)                                              │
│ task_title (varchar 255)                                                    │
│ due_date (date)                                                             │
│ started_at (timestamp, nullable)                                            │
│ completed_at (timestamp, nullable)                                          │
│ completed_by (FK → users, nullable)                                         │
│ status (enum: planned, in_progress, completed, cancelled)                  │
│ duration_minutes (int, nullable)                                            │
│ notes (text, nullable)                                                      │
│ cancellation_reason (varchar, nullable)                                     │
│ created_at, updated_at                                                      │
└────────────────────────────────────────────────────────────────────────────┘
         │
         │ N:M
         ▼
┌────────────────────────────────────────────────────────────────────────────┐
│                    task_participants                                        │
├────────────────────────────────────────────────────────────────────────────┤
│ id (PK)                                                                     │
│ employee_task_id (FK)                                                       │
│ user_id (FK)                                                                │
│ is_owner (boolean, default false)                                          │
│ joined_at (timestamp)                                                       │
│ created_at, updated_at                                                      │
│ UNIQUE(employee_task_id, user_id)                                          │
└────────────────────────────────────────────────────────────────────────────┘

┌────────────────────────────────────────────────────────────────────────────┐
│                      task_attachments                                       │
├────────────────────────────────────────────────────────────────────────────┤
│ id (PK)                                                                     │
│ employee_task_id (FK)                                                       │
│ file_name (varchar)                                                         │
│ file_path (varchar)                                                         │
│ file_type (varchar)                                                         │
│ file_size (int)                                                             │
│ uploaded_by (FK → users)                                                    │
│ created_at                                                                  │
└────────────────────────────────────────────────────────────────────────────┘

┌────────────────────────────────────────────────────────────────────────────┐
│                      activity_types                                         │
├────────────────────────────────────────────────────────────────────────────┤
│ id (PK)                                                                     │
│ code (varchar, unique)                                                      │
│ name (varchar)                                                              │
│ color (varchar)                                                             │
│ is_active (boolean, default true)                                          │
│ sort_order (int)                                                            │
│ created_at, updated_at                                                      │
└────────────────────────────────────────────────────────────────────────────┘
         │
         │ 1:N
         ▼
┌────────────────────────────────────────────────────────────────────────────┐
│                       sub_activities                                        │
├────────────────────────────────────────────────────────────────────────────┤
│ id (PK)                                                                     │
│ activity_type_id (FK)                                                       │
│ code (varchar)                                                              │
│ name (varchar)                                                              │
│ is_active (boolean, default true)                                          │
│ sort_order (int)                                                            │
│ created_at, updated_at                                                      │
│ UNIQUE(activity_type_id, code)                                             │
└────────────────────────────────────────────────────────────────────────────┘
```

### Default Activity Types & Sub-Activities

| Activity Type | Code | Color | Sub-Activities |
|---------------|------|-------|----------------|
| Meeting | MEETING | blue | Meeting Client, Meeting RAB, Meeting PNL, Meeting Internal, Meeting Vendor |
| Web Development | WEBDEV | indigo | Fix Bug, Update UI, New Feature, Code Review, Deployment |
| Event | EVENT | purple | Event Planning, Event Execution, Event Follow-up |
| Internal Meeting | INTERNAL | gray | Daily Standup, Weekly Review, Monthly Report |
| Administrative | ADMIN | yellow | Documentation, Email, Report Writing |
| Training | TRAINING | green | Internal Training, External Training, Self Learning |

## Component Design

### 1. EmployeeTask Model

```php
namespace App\Models\Modules\Activity;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Core\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class EmployeeTask extends Model
{
    use LogsActivity;
    
    protected $fillable = [
        'business_unit_id', 'department_id', 'created_by',
        'activity_type_id', 'sub_activity_id', 'task_title',
        'due_date', 'started_at', 'completed_at', 'completed_by',
        'status', 'duration_minutes', 'notes', 'cancellation_reason'
    ];
    
    protected $casts = [
        'due_date' => 'date',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];
    
    // Activity Log
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['task_title', 'status', 'due_date'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
    
    // Relationships
    public function businessUnit(): BelongsTo
    {
        return $this->belongsTo(BusinessUnit::class);
    }
    
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
    
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    
    public function completedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }
    
    public function activityType(): BelongsTo
    {
        return $this->belongsTo(ActivityType::class);
    }
    
    public function subActivity(): BelongsTo
    {
        return $this->belongsTo(SubActivity::class);
    }
    
    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'task_participants', 'employee_task_id', 'user_id')
            ->withPivot(['is_owner', 'joined_at'])
            ->withTimestamps();
    }
    
    public function attachments(): HasMany
    {
        return $this->hasMany(TaskAttachment::class, 'employee_task_id');
    }
    
    // Scopes
    public function scopeForBusinessUnit($query, int $businessUnitId)
    {
        return $query->where('business_unit_id', $businessUnitId);
    }
    
    public function scopeForDepartment($query, int $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }
    
    public function scopeForParticipant($query, int $userId)
    {
        return $query->whereHas('participants', fn($q) => $q->where('user_id', $userId));
    }
    
    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now()->toDateString())
            ->whereNotIn('status', ['completed', 'cancelled']);
    }
    
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }
    
    // Helper Methods
    public function isOverdue(): bool
    {
        if (in_array($this->status, ['completed', 'cancelled'])) {
            return false;
        }
        return $this->due_date->isPast();
    }
    
    public function isOwner(int $userId): bool
    {
        return $this->participants()
            ->where('user_id', $userId)
            ->where('is_owner', true)
            ->exists();
    }
    
    public function isParticipant(int $userId): bool
    {
        return $this->participants()->where('user_id', $userId)->exists();
    }
    
    public function canBeEditedBy(User $user): bool
    {
        // Owner can always edit
        if ($this->isOwner($user->id)) {
            return true;
        }
        
        // Department head can edit department tasks
        if ($user->isDepartmentHead() && $this->department_id === $user->primary_department_id) {
            return true;
        }
        
        // Super admin can edit all
        return $user->isSuperAdmin();
    }
}
```

### 2. TaskService

```php
namespace App\Services\Modules\Activity;

use App\Models\Core\User;
use App\Models\Modules\Activity\EmployeeTask;
use Illuminate\Support\Facades\DB;

class TaskService
{
    public function create(array $data, User $user): EmployeeTask
    {
        return DB::transaction(function () use ($data, $user) {
            $task = EmployeeTask::create([
                'business_unit_id' => session('current_business_unit_id'),
                'department_id' => $user->primary_department_id,
                'created_by' => $user->id,
                'status' => 'planned',
                ...$data
            ]);
            
            // Auto-add creator as owner
            $task->participants()->attach($user->id, [
                'is_owner' => true,
                'joined_at' => $task->created_at
            ]);
            
            return $task;
        });
    }
    
    public function join(EmployeeTask $task, User $user): void
    {
        if ($task->isParticipant($user->id)) {
            throw new \Exception('User is already a participant');
        }
        
        $task->participants()->attach($user->id, [
            'is_owner' => false,
            'joined_at' => $task->created_at // Same as task creation time
        ]);
    }
    
    public function start(EmployeeTask $task): void
    {
        if ($task->status !== 'planned') {
            throw new \Exception('Task can only be started from planned status');
        }
        
        $task->update([
            'status' => 'in_progress',
            'started_at' => now()
        ]);
    }
    
    public function complete(EmployeeTask $task, User $user): void
    {
        if (!in_array($task->status, ['planned', 'in_progress'])) {
            throw new \Exception('Task cannot be completed from current status');
        }
        
        $completedAt = now();
        $startedAt = $task->started_at ?? $completedAt;
        $duration = $startedAt->diffInMinutes($completedAt);
        
        $task->update([
            'status' => 'completed',
            'started_at' => $startedAt, // Set if not already set
            'completed_at' => $completedAt,
            'completed_by' => $user->id,
            'duration_minutes' => $duration
        ]);
    }
    
    public function cancel(EmployeeTask $task, string $reason, User $user): void
    {
        if (!$task->isOwner($user->id) && !$user->isSuperAdmin()) {
            throw new \Exception('Only task owner can cancel');
        }
        
        $task->update([
            'status' => 'cancelled',
            'cancellation_reason' => $reason
        ]);
    }
}
```

### 3. TaskIndex Livewire Component (with BU Switcher Pattern)

```php
namespace App\Livewire\Modules\Activity;

use App\Livewire\Traits\HasFilters;
use App\Livewire\Traits\HasLazyLoading;
use App\Models\Modules\Activity\EmployeeTask;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class TaskIndex extends Component
{
    use HasFilters, HasLazyLoading, WithPagination;
    
    const CACHE_TTL_STATS = 300; // 5 minutes
    
    public $businessUnitId;
    
    protected $listeners = [
        'business-unit-switched' => 'handleBusinessUnitSwitch',
        'task-created' => 'refreshTasks',
        'task-updated' => 'refreshTasks',
    ];
    
    public function mount(): void
    {
        $this->businessUnitId = session('current_business_unit_id');
        $this->filters = [
            'search' => '',
            'activity_type_id' => '',
            'status' => '',
            'date_from' => '',
            'date_to' => '',
        ];
    }
    
    public function hydrate(): void
    {
        $sessionBuId = session('current_business_unit_id');
        if ($this->businessUnitId != $sessionBuId) {
            $this->businessUnitId = $sessionBuId;
            $this->resetLazyLoad();
        }
    }
    
    public function handleBusinessUnitSwitch($businessUnitId): void
    {
        // Update session FIRST (single source of truth)
        session(['current_business_unit_id' => $businessUnitId]);
        
        $this->businessUnitId = $businessUnitId;
        $this->resetLazyLoad();
        $this->resetFilters();
        $this->clearCache();
        
        // ✅ ORCHESTRATOR: Acknowledge completion
        $this->dispatch('bu-switch-acknowledge', component: 'activity-tasks');
        
        $buName = session('current_business_unit_name', 'new business unit');
        $this->dispatch('notify', message: "Switched to {$buName}", type: 'success');
    }
    
    public function refreshTasks(): void
    {
        $this->clearCache();
        $this->resetLazyLoad();
    }
    
    protected function clearCache(): void
    {
        $buId = session('current_business_unit_id') ?? $this->businessUnitId;
        $userId = Auth::id();
        Cache::forget("task_stats_{$buId}_{$userId}");
    }
    
    #[Computed]
    public function tasks()
    {
        if (!$this->readyToLoad) {
            return collect();
        }
        
        $user = Auth::user();
        $buId = session('current_business_unit_id') ?? $this->businessUnitId;
        $departmentId = $user->primary_department_id;
        
        return EmployeeTask::query()
            ->where('business_unit_id', $buId)
            ->where(function ($query) use ($user, $departmentId) {
                // Tasks from same department
                $query->where('department_id', $departmentId)
                    // OR tasks where user is participant
                    ->orWhereHas('participants', fn($q) => 
                        $q->where('user_id', $user->id)
                    );
            })
            ->when($this->filters['activity_type_id'] ?? null, fn($q, $typeId) => 
                $q->where('activity_type_id', $typeId)
            )
            ->when($this->filters['status'] ?? null, fn($q, $status) => 
                $q->where('status', $status)
            )
            ->when($this->filters['search'] ?? null, fn($q, $search) => 
                $q->where('task_title', 'like', "%{$search}%")
            )
            ->with(['activityType', 'subActivity', 'participants', 'creator'])
            ->latest()
            ->paginate(20);
    }
    
    #[Computed]
    public function stats()
    {
        if (!$this->readyToLoad) {
            return ['total' => 0, 'completed' => 0, 'in_progress' => 0, 'overdue' => 0];
        }
        
        $buId = session('current_business_unit_id') ?? $this->businessUnitId;
        $user = Auth::user();
        $cacheKey = "task_stats_{$buId}_{$user->id}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL_STATS, function () use ($buId, $user) {
            $departmentId = $user->primary_department_id;
            $today = now()->toDateString();
            
            $baseQuery = EmployeeTask::where('business_unit_id', $buId)
                ->where(function ($query) use ($user, $departmentId) {
                    $query->where('department_id', $departmentId)
                        ->orWhereHas('participants', fn($q) => 
                            $q->where('user_id', $user->id)
                        );
                });
            
            return [
                'total' => (clone $baseQuery)->count(),
                'completed' => (clone $baseQuery)->where('status', 'completed')->count(),
                'in_progress' => (clone $baseQuery)->where('status', 'in_progress')->count(),
                'overdue' => (clone $baseQuery)
                    ->where('due_date', '<', $today)
                    ->whereNotIn('status', ['completed', 'cancelled'])
                    ->count(),
            ];
        });
    }
    
    public function render()
    {
        return view('livewire.modules.activity.task-index')
            ->layout('layouts.app');
    }
}
```

### 4. Permission Matrix

| Role | View Own | View Dept | Edit Own | Edit Dept | Delete | Analytics |
|------|----------|-----------|----------|-----------|--------|-----------|
| Regular User | ✅ | ✅ | ✅ | ❌ | Own only | Personal |
| Dept Head | ✅ | ✅ | ✅ | ✅ | Dept | Department |
| Top Management | ✅ | ✅ | ❌ | ❌ | ❌ | Business Unit |
| Super Admin | ✅ | ✅ | ✅ | ✅ | ✅ | All |

## Routes

```php
// routes/web.php
Route::prefix('activity')->name('activity.')->middleware(['auth', 'verified', 'ensure.business.unit.selected'])->group(function () {
    // Task Management
    Route::get('/', \App\Livewire\Modules\Activity\TaskIndex::class)->name('index');
    Route::get('/create', \App\Livewire\Modules\Activity\TaskForm::class)->name('create');
    Route::get('/{task}', \App\Livewire\Modules\Activity\TaskDetail::class)->name('show');
    Route::get('/{task}/edit', \App\Livewire\Modules\Activity\TaskForm::class)->name('edit');
    
    // Department Tasks (joinable)
    Route::get('/department/tasks', \App\Livewire\Modules\Activity\DepartmentTasks::class)->name('department');
    
    // Analytics
    Route::get('/analytics/personal', \App\Livewire\Modules\Activity\Analytics\PersonalDashboard::class)->name('analytics.personal');
    Route::get('/analytics/department', \App\Livewire\Modules\Activity\Analytics\DepartmentAnalytics::class)
        ->middleware('can:view-department-analytics')
        ->name('analytics.department');
    Route::get('/analytics/business-unit', \App\Livewire\Modules\Activity\Analytics\BusinessUnitAnalytics::class)
        ->middleware('can:view-reports')
        ->name('analytics.business-unit');
});

// Admin Routes
Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin.access'])->group(function () {
    Route::resource('activity-types', \App\Http\Controllers\Admin\ActivityTypeController::class);
    Route::resource('sub-activities', \App\Http\Controllers\Admin\SubActivityController::class);
});
```

## UI Components

### Task Card (Tailwind)

```blade
<div class="bg-white rounded-xl border border-gray-100 p-4">
    <div class="flex items-start justify-between">
        <div>
            <h3 class="font-medium text-gray-900">{{ $task->task_title }}</h3>
            <div class="flex items-center gap-2 mt-1">
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium" 
                      style="background-color: {{ $task->activityType->color }}20; color: {{ $task->activityType->color }}">
                    {{ $task->activityType->name }}
                </span>
                @if($task->subActivity)
                    <span class="text-xs text-gray-500">{{ $task->subActivity->name }}</span>
                @endif
            </div>
        </div>
        <x-task-status-badge :status="$task->status" />
    </div>
    
    <div class="mt-3 flex items-center justify-between text-sm text-gray-500">
        <div class="flex items-center gap-4">
            <span>Due: {{ $task->due_date->format('d M Y') }}</span>
            <span>{{ $task->participants->count() }} participants</span>
        </div>
        @if($task->isOverdue())
            <span class="text-red-600 font-medium">Overdue</span>
        @endif
    </div>
    
    <!-- Participant Avatars -->
    <div class="mt-3 flex -space-x-2">
        @foreach($task->participants->take(5) as $participant)
            <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-xs font-medium text-indigo-700 border-2 border-white">
                {{ substr($participant->user->name, 0, 2) }}
            </div>
        @endforeach
        @if($task->participants->count() > 5)
            <div class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center text-xs font-medium text-gray-600 border-2 border-white">
                +{{ $task->participants->count() - 5 }}
            </div>
        @endif
    </div>
</div>
```

## Implementation Phases

### Phase 1: Core Infrastructure
- [ ] Create migrations
- [ ] Create models with relationships
- [ ] Create TaskService
- [ ] Seed default activity types & sub-activities

### Phase 2: Task CRUD
- [ ] TaskIndex component (list with filters)
- [ ] TaskForm component (create/edit)
- [ ] TaskDetail component (view with actions)
- [ ] Join task functionality

### Phase 3: Analytics
- [ ] PersonalDashboard component
- [ ] DepartmentAnalytics component
- [ ] BusinessUnitAnalytics component

### Phase 4: Admin
- [ ] ActivityType CRUD
- [ ] SubActivity CRUD
- [ ] Attachment upload

### Phase 5: Integration
- [ ] Add to sidebar navigation
- [ ] Add to main dashboard widgets
- [ ] Permissions setup
