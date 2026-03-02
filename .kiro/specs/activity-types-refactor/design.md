# Design Document

## Overview

Refactor Activity Types dan Sub Activities dari arsitektur per-department menjadi arsitektur master global. Super Admin mengelola semua activity types dan sub activities secara terpusat, kemudian assign ke department yang membutuhkan.

### Current State
- Activity types memiliki prefix department (ACC_LEAVE, BAS_LEAVE, CFC_LEAVE)
- Sub activities juga memiliki prefix (ACC_LEAVE_SICK, BAS_LEAVE_SICK)
- Banyak duplikasi data dengan nama yang sama tapi code berbeda
- Total 164 activity types, banyak yang sebenarnya sama

### Target State
- Activity types global tanpa prefix (LEAVE, TRAINING, MEETING)
- Sub activities global terhubung ke parent activity type
- Department assignment melalui pivot table
- Super Admin mengelola semua dari satu tempat

## Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                      Super Admin UI                          │
│  ┌─────────────────┐  ┌─────────────────┐  ┌──────────────┐ │
│  │ Activity Types  │  │ Sub Activities  │  │  Assignment  │ │
│  │    (Master)     │  │    (Master)     │  │  Management  │ │
│  └────────┬────────┘  └────────┬────────┘  └──────┬───────┘ │
└───────────┼────────────────────┼──────────────────┼─────────┘
            │                    │                  │
            ▼                    ▼                  ▼
┌───────────────────┐  ┌───────────────────┐  ┌───────────────┐
│ employee_activity │  │ employee_sub_    │  │ department_   │
│     _types        │◄─┤   activities     │  │ activity_types│
│                   │  │                   │  │   (pivot)     │
│ - id              │  │ - id              │  │               │
│ - code (unique)   │  │ - activity_type_id│  │ - dept_id     │
│ - name            │  │ - code            │  │ - activity_id │
│ - color           │  │ - name            │  │ - is_default  │
│ - is_active       │  │ - is_active       │  │ - sort_order  │
└───────────────────┘  └───────────────────┘  └───────────────┘
            │                                        │
            └────────────────┬───────────────────────┘
                             ▼
                    ┌───────────────────┐
                    │  employee_tasks   │
                    │                   │
                    │ - activity_type_id│
                    │ - sub_activity_id │
                    │ - department_id   │
                    └───────────────────┘
```

## Components and Interfaces

### 1. ActivityTypeController (Updated)

```php
class ActivityTypeController extends Controller
{
    // Super admin sees all activity types globally
    public function index(Request $request): Response
    
    // Create master activity type (no department prefix)
    public function store(Request $request): RedirectResponse
    
    // Show activity type with department assignments
    public function show(ActivityType $activityType): Response
    
    // Update master activity type
    public function update(Request $request, ActivityType $activityType): RedirectResponse
    
    // Delete (with validation for tasks/sub-activities)
    public function destroy(ActivityType $activityType): RedirectResponse
    
    // Assign activity type to departments
    public function assignDepartments(Request $request, ActivityType $activityType): RedirectResponse
    
    // Remove activity type from department
    public function removeDepartment(Request $request, ActivityType $activityType): RedirectResponse
}
```

### 2. SubActivityController (Updated)

```php
class SubActivityController extends Controller
{
    // List all sub activities with activity type filter
    public function index(Request $request): Response
    
    // Create sub activity linked to activity type
    public function store(Request $request): RedirectResponse
    
    // Update sub activity
    public function update(Request $request, SubActivity $subActivity): RedirectResponse
    
    // Delete sub activity
    public function destroy(SubActivity $subActivity): RedirectResponse
}
```

### 3. Migration Service

```php
class ActivityTypeMigrationService
{
    // Consolidate prefixed activity types to global master
    public function consolidateActivityTypes(): array
    
    // Consolidate prefixed sub activities
    public function consolidateSubActivities(): array
    
    // Update task references to new IDs
    public function updateTaskReferences(array $mapping): int
    
    // Cleanup orphaned records
    public function cleanupOrphans(): int
}
```

## Data Models

### ActivityType (Updated)
```php
// Code format: LEAVE, TRAINING, MEETING (no prefix)
// Name: Leave, Training, Meeting
// Relationships:
// - hasMany SubActivity
// - belongsToMany Department (via department_activity_types)
// - hasMany EmployeeTask
```

### SubActivity (Updated)
```php
// Code format: LEAVE_SICK, LEAVE_ANNUAL (no department prefix)
// Name: Sick Leave, Annual Leave
// Relationships:
// - belongsTo ActivityType
```

### Migration Mapping
```php
// Example consolidation:
// ACC_LEAVE, BAS_LEAVE, CFC_LEAVE → LEAVE (id: 1)
// ACC_TRAINING, BAS_TRAINING → TRAINING (id: 2)

// Mapping structure:
[
    'old_id' => 'new_id',
    1 => 1,  // ACC_LEAVE → LEAVE
    5 => 1,  // BAS_LEAVE → LEAVE
    10 => 1, // CFC_LEAVE → LEAVE
]
```

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system-essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property 1: Activity type codes have no department prefix
*For any* activity type in the system after migration, the code should not contain underscore followed by activity name pattern (e.g., no "ACC_", "BAS_" prefix)
**Validates: Requirements 1.1, 1.2**

### Property 2: Deletion constraint for activity types with dependencies
*For any* activity type that has tasks or sub activities, attempting to delete should fail and return an error
**Validates: Requirements 1.4**

### Property 3: Sub activity filter returns correct results
*For any* activity type filter applied to sub activities, all returned sub activities should have activity_type_id matching the filter
**Validates: Requirements 2.3**

### Property 4: Department assignment creates pivot record
*For any* department assignment operation, a corresponding record should exist in department_activity_types with correct department_id and activity_type_id
**Validates: Requirements 3.2**

### Property 5: Removal constraint for assigned activity types with tasks
*For any* activity type assigned to a department that has tasks using it, attempting to remove the assignment should fail
**Validates: Requirements 3.3**

### Property 6: Migration consolidation produces unique names
*For any* set of activity types with the same name (ignoring prefix), after migration there should be exactly one master record with that name
**Validates: Requirements 4.1, 4.2**

### Property 7: Task references remain valid after migration
*For any* employee task, after migration the activity_type_id should point to a valid activity type record
**Validates: Requirements 4.3**

### Property 8: Department assignments preserved after migration
*For any* department that had activity types assigned before migration, after migration the department should have equivalent activity types assigned
**Validates: Requirements 4.4**

### Property 9: Employee sees only assigned activity types
*For any* employee in a department, when creating a task, only activity types assigned to their department should be available
**Validates: Requirements 5.1**

### Property 10: Business unit filter returns correct activity types
*For any* business unit filter, all returned activity types should have at least one department assignment in that business unit
**Validates: Requirements 6.3**

## Error Handling

### Deletion Errors
- Activity type with sub activities: "Cannot delete activity type with sub-activities. Delete sub-activities first."
- Activity type with tasks: "Cannot delete activity type that is being used by tasks."
- Department assignment with tasks: "Cannot remove activity type from department. Tasks exist using this activity type."

### Migration Errors
- Duplicate code conflict: Log and skip, continue with next
- Foreign key violation: Rollback transaction, report affected records
- Missing department: Log warning, skip assignment

## Testing Strategy

### Unit Tests
- ActivityType model: code generation, relationships
- SubActivity model: parent relationship
- Migration service: consolidation logic

### Property-Based Tests
Using Pest with faker for property-based testing:

1. **Property 1**: Generate random activity types, verify no prefix pattern
2. **Property 2**: Create activity type with tasks, verify deletion fails
3. **Property 3**: Create sub activities, filter by type, verify all match
4. **Property 4**: Assign to department, verify pivot record exists
5. **Property 5**: Create task with assignment, verify removal fails
6. **Property 6**: Create prefixed duplicates, run migration, verify single master
7. **Property 7**: Run migration, verify all task references valid
8. **Property 8**: Run migration, verify department assignments preserved
9. **Property 9**: Create department with assignments, verify employee sees only those
10. **Property 10**: Filter by BU, verify all results have assignment in that BU

### Integration Tests
- Full migration flow with sample data
- UI flow for activity type management
- Employee task creation with filtered options
